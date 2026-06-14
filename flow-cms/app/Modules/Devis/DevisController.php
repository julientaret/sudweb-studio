<?php

declare(strict_types=1);

namespace App\Modules\Devis;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Mailer;

/**
 * Devis — back-office (liste / détail / statut / suppression), constructeur de
 * formulaire (étapes/questions du pop-up) et réception du pop-up public (AJAX).
 *
 * Côté front, le site public est sans état : on démarre une session légère,
 * propre au pop-up, juste pour porter le jeton CSRF et l'horodatage anti-bot. Le
 * pop-up récupère ce jeton à l'ouverture (POST /devis/token) puis envoie la
 * demande (POST /devis/envoyer), les deux en JSON.
 *
 * Le formulaire est dynamique : ses étapes et questions sont définies dans
 * l'admin (DevisFormSettings). La validation à la réception s'appuie sur ce même
 * schéma. Anti-spam maison (sans tiers, RGPD-clean) : honeypot + délai minimum +
 * rate limit par IP hachée.
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

    // --- Back-office : demandes -------------------------------------------

    public function index(): void
    {
        $this->requirePermission('devis.view');
        $this->purgeExpired(); // RGPD : nettoyage opportuniste à l'ouverture.

        $this->renderPage($this->views(), 'list', 'Demandes de devis', [
            'items'        => $this->repo->all(),
            'notice'       => $this->input('notice'),
            'canConfigure' => $this->auth->can('devis.configure'),
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

    // --- Back-office : constructeur de formulaire -------------------------

    public function editForm(): void
    {
        $this->requirePermission('devis.configure');
        $settings = new DevisFormSettings();

        $this->renderPage($this->views(), 'form', 'Formulaire de devis', [
            'schemaJson' => (string) json_encode(
                ['steps' => $settings->schema()],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            'types'  => DevisFormSettings::TYPES,
            'notice' => $this->input('notice'),
        ]);
    }

    public function saveForm(): void
    {
        $this->requirePermission('devis.configure');
        $this->requireCsrf();

        $raw = $_POST['schema'] ?? '';
        $data = is_string($raw) ? json_decode($raw, true) : null;
        $steps = is_array($data) && isset($data['steps']) && is_array($data['steps']) ? $data['steps'] : [];

        (new DevisFormSettings())->save($steps);
        $this->redirect('/admin/devis/form?notice=' . rawurlencode('Formulaire enregistré.'));
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
     * {ok:false, errors:[...]}. Validation pilotée par le schéma admin.
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

        // 4) Saisie + validation selon le schéma défini dans l'admin.
        $form = new DevisFormSettings();
        $emailKey = $form->emailKey();

        $errors = [];
        $answers = [];
        $name = '';
        $email = '';

        foreach ($form->fields() as $f) {
            $key = (string) $f['key'];
            $label = (string) $f['label'];
            $type = (string) $f['type'];
            $required = (bool) $f['required'];
            $value = $type === 'textarea'
                ? mb_substr(trim($this->input($key)), 0, 5000)
                : $this->cleanLine($this->input($key), 255);

            if ($type === 'email') {
                $value = strtolower($value);
                if ($required && $value === '') {
                    $errors[] = 'Merci d’indiquer votre adresse e-mail.';
                } elseif ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    $errors[] = 'Merci d’indiquer une adresse e-mail valide.';
                }
            } elseif ($type === 'select') {
                $options = (array) $f['options'];
                if ($value === '' && $required) {
                    $errors[] = 'Merci de choisir une option pour « ' . $label . ' ».';
                } elseif ($value !== '' && $options !== [] && !in_array($value, $options, true)) {
                    $errors[] = 'Choix invalide pour « ' . $label . ' ».';
                }
            } elseif ($type === 'textarea') {
                $min = (int) $f['minlength'];
                if ($required && $value === '') {
                    $errors[] = 'Merci de renseigner « ' . $label . ' ».';
                } elseif ($value !== '' && $min > 0 && mb_strlen($value) < $min) {
                    $errors[] = '« ' . $label .' » : trop court (au moins ' . $min . ' caractères).';
                }
            } else { // text, tel
                if ($required && $value === '') {
                    $errors[] = 'Merci de renseigner « ' . $label . ' ».';
                }
            }

            if ($key === 'name') {
                $name = $value;
            }
            if ($key === $emailKey) {
                $email = $value;
            }
            // Réponses stockées : tout sauf les champs cœur (nom/e-mail, en colonnes).
            if (!(bool) $f['core'] && $value !== '') {
                $answers[] = ['label' => $label, 'value' => $value];
            }
        }

        $consent = $this->input('consent') !== '';
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
        $answersJson = (string) json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->repo->create($name, $email, $answersJson, $consent, (string) $this->config('consent_version', ''), $ipHash);

        // 7) Notification à l'adresse de contact.
        $this->notifyOwner($name, $email, $answers);

        // Anti double-envoi : on consomme l'horodatage.
        unset($_SESSION['devis_form_ts']);
        $this->json(['ok' => true]);
    }

    // --- Interne ----------------------------------------------------------

    /**
     * @param array<int, array{label:string, value:string}> $answers
     */
    private function notifyOwner(string $name, string $email, array $answers): void
    {
        $recipient = (string) $this->config('recipient', '');
        if ($recipient === '') {
            return; // pas de destinataire configuré : on s'arrête (demande déjà en base).
        }

        $siteName = (string) Config::get('app.site_name', 'Flow');
        $html = $this->view($this->views())->render('email_owner', [
            'siteName' => $siteName,
            'name'     => $name,
            'email'    => $email,
            'answers'  => $answers,
            'date'     => date('d/m/Y à H:i'),
            'adminUrl' => rtrim((string) Config::get('app.url', ''), '/'),
        ]);

        $opts = [
            'from'      => (string) $this->config('sender', ''),
            'from_name' => $siteName,
        ];
        if ($email !== '') {
            $opts['reply_to'] = $email; // répondre = écrire directement au demandeur.
        }

        Mailer::sendHtml($recipient, '[' . $siteName . '] Nouvelle demande de devis', $html, $opts);
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
