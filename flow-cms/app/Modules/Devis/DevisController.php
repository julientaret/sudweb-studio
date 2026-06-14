<?php

declare(strict_types=1);

namespace App\Modules\Devis;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Mailer;

/**
 * Devis — back-office (liste / détail / statut / suppression) et réception du
 * pop-up public (AJAX).
 *
 * Côté front, le site public est sans état : on démarre une session légère,
 * propre au pop-up, juste pour porter le jeton CSRF et l'horodatage anti-bot. Le
 * pop-up récupère ce jeton à l'ouverture (POST /devis/token) puis envoie la
 * demande (POST /devis/envoyer), les deux en JSON.
 *
 * Anti-spam maison (sans tiers, RGPD-clean) : honeypot + délai minimum + rate
 * limit par IP hachée.
 */
final class DevisController extends Controller
{
    private DevisRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new DevisRepository();
    }

    private function views(): array
    {
        return [__DIR__ . '/views'];
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return Config::get('devis.' . $key, $default);
    }

    // --- Back-office ------------------------------------------------------

    public function index(): void
    {
        $this->requirePermission('devis.view');
        $this->purgeExpired(); // RGPD : nettoyage opportuniste à l'ouverture.

        $this->renderPage($this->views(), 'list', 'Demandes de devis', [
            'items'  => $this->repo->all(),
            'notice' => $this->input('notice'),
        ]);
    }

    public function show(): void
    {
        $this->requirePermission('devis.view');
        $item = $this->repo->find((int) $this->input('id', '0'));
        if ($item === null) {
            $this->redirect('/admin/devis');
        }

        // Première consultation : on passe « non lue » → « lue ».
        if ($item->isNew()) {
            $this->repo->updateStatus($item->id, 'read');
        }

        $this->renderPage($this->views(), 'show', 'Demande de devis', [
            'item'        => $item,
            'consentText' => (string) $this->config('consent_text', ''),
        ]);
    }

    public function updateStatus(): void
    {
        $this->requirePermission('devis.view');
        $this->requireCsrf();
        $id = (int) $this->input('id', '0');
        $status = $this->input('status');
        if (in_array($status, ['new', 'read', 'quoted', 'archived'], true)) {
            $this->repo->updateStatus($id, $status);
        }
        $this->redirect('/admin/devis?notice=' . rawurlencode('Statut mis à jour.'));
    }

    public function destroy(): void
    {
        $this->requirePermission('devis.delete');
        $this->requireCsrf();
        $this->repo->delete((int) $this->input('id', '0'));
        $this->redirect('/admin/devis?notice=' . rawurlencode('Demande supprimée.'));
    }

    // --- Front : pop-up (AJAX) --------------------------------------------

    /**
     * Émet le jeton CSRF du pop-up et pose l'horodatage anti-bot. Démarre la
     * session légère du formulaire (cookie strictement nécessaire à la sécurité).
     * Servi en POST : il modifie l'état de session et reste hors mesure d'audience.
     */
    public function token(): void
    {
        DevisModule::startSession();
        $_SESSION['devis_form_ts'] = time();
        $this->json(['token' => Csrf::token()]);
    }

    /**
     * Réception d'une demande de devis (JSON). Renvoie {ok:true} ou
     * {ok:false, errors:[...]}.
     */
    public function submit(): void
    {
        DevisModule::startSession();
        header('Content-Type: application/json; charset=utf-8');

        // 1) Honeypot : un bot remplit le champ piège invisible. Faux succès.
        if ($this->input('website') !== '' || $this->input('company_url') !== '') {
            $this->json(['ok' => true]);
        }

        // 2) CSRF (jeton émis par /devis/token).
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            $this->json(['ok' => false, 'errors' => ['Votre session a expiré, merci de rouvrir le formulaire.']]);
        }

        // 3) Délai minimum (uniquement si l'horodatage est présent).
        $ts = (int) ($_SESSION['devis_form_ts'] ?? 0);
        $minSeconds = (int) $this->config('min_seconds', 3);
        if ($ts > 0 && (time() - $ts) < $minSeconds) {
            $this->json(['ok' => true]); // trop rapide pour un humain : drop silencieux.
        }

        // 4) Saisie + validation.
        $projectType = $this->cleanLine($this->input('project_type'), 150);
        $budget      = $this->cleanLine($this->input('budget'), 60);
        $deadline    = $this->cleanLine($this->input('deadline'), 60);
        $description = trim($this->input('description'));
        $name        = $this->cleanLine($this->input('name'), 150);
        $email       = strtolower($this->cleanLine($this->input('email'), 255));
        $phone       = $this->cleanLine($this->input('phone'), 40);
        $company     = $this->cleanLine($this->input('company'), 150);
        $consent     = $this->input('consent') !== '';

        $errors = [];

        $projectTypes = (array) $this->config('project_types', []);
        if ($projectTypes !== [] && !in_array($projectType, $projectTypes, true)) {
            $errors[] = 'Merci de choisir un type de projet dans la liste.';
        }
        $budgets = (array) $this->config('budgets', []);
        if ($budgets !== [] && !in_array($budget, $budgets, true)) {
            $errors[] = 'Merci de choisir un budget dans la liste.';
        }
        $deadlines = (array) $this->config('deadlines', []);
        if ($deadlines !== [] && !in_array($deadline, $deadlines, true)) {
            $errors[] = 'Merci de choisir un délai dans la liste.';
        }
        $minLen = (int) $this->config('description_min_length', 10);
        if (mb_strlen($description) < $minLen) {
            $errors[] = 'Décrivez votre besoin en quelques mots (au moins ' . $minLen . ' caractères).';
        }
        if (mb_strlen($description) > 5000) {
            $errors[] = 'Votre description est trop longue (5000 caractères maximum).';
        }
        if ($name === '') {
            $errors[] = 'Merci d’indiquer votre nom.';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Merci d’indiquer une adresse e-mail valide.';
        }
        if (!$consent) {
            $errors[] = 'Merci d’accepter l’utilisation de vos données pour traiter la demande.';
        }

        if ($errors !== []) {
            $this->json(['ok' => false, 'errors' => $errors]);
        }

        // 5) Rate limit par IP hachée (anti-abus).
        $ipHash = DevisModule::hashIp($this->clientIp());
        $window = (int) $this->config('rate_limit_window', 3600);
        $max    = (int) $this->config('rate_limit_max', 5);
        if ($this->repo->countByIpSince($ipHash, date('Y-m-d H:i:s', time() - $window)) >= $max) {
            $this->json(['ok' => false, 'errors' => ['Trop de demandes envoyées récemment. Merci de réessayer plus tard.']]);
        }

        // 6) Persistance (la demande est conservée même si l'e-mail échoue).
        $this->repo->create(
            $projectType,
            $budget,
            $deadline,
            $description,
            $name,
            $email,
            $phone,
            $company,
            $consent,
            (string) $this->config('consent_version', ''),
            $ipHash,
        );

        // 7) Notification à l'adresse de contact.
        $this->notifyOwner($projectType, $budget, $deadline, $description, $name, $email, $phone, $company);

        // Anti double-envoi : on consomme l'horodatage.
        unset($_SESSION['devis_form_ts']);
        $this->json(['ok' => true]);
    }

    // --- Interne ----------------------------------------------------------

    private function notifyOwner(
        string $projectType,
        string $budget,
        string $deadline,
        string $description,
        string $name,
        string $email,
        string $phone,
        string $company,
    ): void {
        $recipient = (string) $this->config('recipient', '');
        if ($recipient === '') {
            return; // pas de destinataire configuré : on s'arrête (demande déjà en base).
        }

        $siteName = (string) Config::get('app.site_name', 'Flow');
        $html = $this->view($this->views())->render('email_owner', [
            'siteName'    => $siteName,
            'projectType' => $projectType !== '' ? $projectType : '(non précisé)',
            'budget'      => $budget !== '' ? $budget : '(non précisé)',
            'deadline'    => $deadline !== '' ? $deadline : '(non précisé)',
            'description' => $description,
            'name'        => $name,
            'email'       => $email,
            'phone'       => $phone,
            'company'     => $company,
            'date'        => date('d/m/Y à H:i'),
            'adminUrl'    => rtrim((string) Config::get('app.url', ''), '/'),
        ]);

        $mailSubject = '[' . $siteName . '] Nouvelle demande de devis'
            . ($projectType !== '' ? ' — ' . $projectType : '');

        Mailer::sendHtml($recipient, $mailSubject, $html, [
            'from'      => (string) $this->config('sender', ''),
            'from_name' => $siteName,
            'reply_to'  => $email, // répondre = écrire directement au demandeur.
        ]);
    }

    /**
     * RGPD : supprime les demandes au-delà du délai de conservation.
     */
    private function purgeExpired(): void
    {
        $months = (int) $this->config('retention_months', 0);
        if ($months <= 0) {
            return;
        }
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $months . ' months') ?: time());
        $this->repo->purgeOlderThan($cutoff);
    }

    /**
     * Nettoie une valeur sur une ligne : retire le HTML et borne la longueur.
     */
    private function cleanLine(string $value, int $max): string
    {
        $value = trim(strip_tags($value));
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        return mb_substr($value, 0, $max);
    }

    /**
     * Émet une réponse JSON et termine la requête.
     *
     * @param array<string, mixed> $data
     */
    private function json(array $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
