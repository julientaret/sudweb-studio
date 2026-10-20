<?php

declare(strict_types=1);

namespace App\Modules\Contact;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Mailer;

/**
 * Contact — back-office (liste / détail / statut / suppression) et réception du
 * formulaire public.
 *
 * Côté front, le site public est sans état : on démarre donc une session
 * légère, propre au formulaire de contact, juste pour porter le jeton CSRF,
 * l'horodatage anti-bot et les messages flash (erreurs + ressaisie). La page du
 * formulaire (frontend/static/contact.php) démarre la même session pour émettre
 * ces jetons.
 *
 * Anti-spam maison (sans tiers, RGPD-clean) : honeypot + délai minimum + rate
 * limit par IP hachée. Voir docs/modules/contact.md.
 */
final class ContactController extends Controller
{
    private ContactRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new ContactRepository();
    }

    private function views(): array
    {
        return [__DIR__ . '/views'];
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return Config::get('contact.' . $key, $default);
    }

    // --- Back-office ------------------------------------------------------

    public function index(): void
    {
        $this->requirePermission('contacts.view');
        $this->purgeExpired(); // RGPD : nettoyage opportuniste à l'ouverture.

        $this->renderPage($this->views(), 'list', 'Demandes de contact', [
            'items'  => $this->repo->all(),
            'notice' => $this->input('notice'),
        ]);
    }

    public function show(): void
    {
        $this->requirePermission('contacts.view');
        $item = $this->repo->find((int) $this->input('id', '0'));
        if ($item === null) {
            $this->redirect('/admin/contact');
        }

        // Première consultation : on passe « non lue » → « lue ».
        if ($item->isNew()) {
            $this->repo->updateStatus($item->id, 'read');
        }

        $this->renderPage($this->views(), 'show', 'Demande de contact', [
            'item'        => $item,
            'consentText' => (string) $this->config('consent_text', ''),
        ]);
    }

    public function updateStatus(): void
    {
        $this->requirePermission('contacts.view');
        $this->requireCsrf();
        $id = (int) $this->input('id', '0');
        $status = $this->input('status');
        if (in_array($status, ['new', 'read', 'archived'], true)) {
            $this->repo->updateStatus($id, $status);
        }
        $this->redirect('/admin/contact?notice=' . rawurlencode('Statut mis à jour.'));
    }

    public function destroy(): void
    {
        $this->requirePermission('contacts.delete');
        $this->requireCsrf();
        $this->repo->delete((int) $this->input('id', '0'));
        $this->redirect('/admin/contact?notice=' . rawurlencode('Demande supprimée.'));
    }

    // --- Front : réception du formulaire ----------------------------------

    public function submit(): void
    {
        $this->startFormSession();

        // 1) Honeypot : un bot remplit le champ piège invisible. On feint le
        //    succès (ne pas renseigner le bot) sans rien enregistrer.
        if ($this->input('website') !== '' || $this->input('company_url') !== '') {
            $this->redirectSent();
        }

        // 2) CSRF (jeton émis à l'affichage du formulaire).
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            $this->backToForm(['Votre session a expiré, merci de renvoyer le formulaire.'], []);
        }

        // 3) Délai minimum (uniquement si l'horodatage est présent — tolérant
        //    aux navigateurs sans cookie pour ne pas perdre de vrais messages).
        $ts = (int) ($_SESSION['contact_form_ts'] ?? 0);
        $minSeconds = (int) $this->config('min_seconds', 3);
        if ($ts > 0 && (time() - $ts) < $minSeconds) {
            $this->redirectSent(); // trop rapide pour un humain : drop silencieux.
        }

        // 4) Saisie + validation.
        $name    = $this->cleanLine($this->input('name'), 150);
        $email   = strtolower($this->cleanLine($this->input('email'), 255));
        $subject = $this->cleanLine($this->input('subject'), 200);
        $message = trim($this->input('message'));
        $consent = $this->input('consent') !== '';

        $old = ['name' => $name, 'email' => $email, 'subject' => $subject, 'message' => $message];
        $errors = [];

        if ($name === '') {
            $errors[] = 'Merci d’indiquer votre nom.';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Merci d’indiquer une adresse e-mail valide.';
        }
        $subjects = (array) $this->config('subjects', []);
        if ($subjects !== [] && !in_array($subject, $subjects, true)) {
            $errors[] = 'Merci de choisir un sujet dans la liste.';
        }
        $minLen = (int) $this->config('message_min_length', 10);
        if (mb_strlen($message) < $minLen) {
            $errors[] = 'Votre message est trop court (au moins ' . $minLen . ' caractères).';
        }
        if (mb_strlen($message) > 5000) {
            $errors[] = 'Votre message est trop long (5000 caractères maximum).';
        }
        if (!$consent) {
            $errors[] = 'Merci d’accepter l’utilisation de vos données pour traiter la demande.';
        }

        if ($errors !== []) {
            $this->backToForm($errors, $old);
        }

        // 5) Rate limit par IP hachée (anti-abus).
        $ipHash = ContactModule::hashIp($this->clientIp());
        $window = (int) $this->config('rate_limit_window', 3600);
        $max    = (int) $this->config('rate_limit_max', 5);
        if ($this->repo->countByIpSince($ipHash, date('Y-m-d H:i:s', time() - $window)) >= $max) {
            $this->backToForm(['Trop de demandes envoyées récemment. Merci de réessayer plus tard.'], $old);
        }
        // Plafond global, toutes IP confondues : la limite par IP ne suffit pas
        // face à des IP tournantes (envois en masse via le formulaire).
        $globalMax = (int) $this->config('rate_limit_global_max', 30);
        if ($globalMax > 0 && $this->repo->countSince(date('Y-m-d H:i:s', time() - $window)) >= $globalMax) {
            $this->backToForm(['Trop de demandes envoyées récemment. Merci de réessayer plus tard.'], $old);
        }

        // 6) Persistance (la demande est conservée même si l'e-mail échoue).
        $this->repo->create(
            $name,
            $email,
            $subject,
            $message,
            $consent,
            (string) $this->config('consent_version', ''),
            $ipHash,
        );

        // 7) Notification au propriétaire du site.
        $this->notifyOwner($name, $email, $subject, $message);

        // Anti double-envoi : on consomme l'horodatage.
        unset($_SESSION['contact_form_ts'], $_SESSION['contact_flash']);
        $this->redirectSent();
    }

    // --- Interne ----------------------------------------------------------

    private function notifyOwner(string $name, string $email, string $subject, string $message): void
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
            'subject'  => $subject !== '' ? $subject : '(sans sujet)',
            'message'  => $message,
            'date'     => date('d/m/Y à H:i'),
            'adminUrl' => rtrim((string) Config::get('app.url', ''), '/'),
        ]);

        $mailSubject = '[' . $siteName . '] Nouvelle demande de contact'
            . ($subject !== '' ? ' — ' . $subject : '');

        Mailer::sendHtml($recipient, $mailSubject, $html, [
            'from'      => (string) $this->config('sender', ''),
            'from_name' => $siteName,
            'reply_to'  => $email, // répondre = écrire directement au visiteur.
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
     * Démarre (au besoin) la session légère propre au formulaire de contact,
     * avec des cookies durcis (voir ContactModule::startSession).
     */
    private function startFormSession(): void
    {
        ContactModule::startSession();
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
     * Redirige vers la page de contact avec les erreurs et la ressaisie (flash).
     *
     * @param array<int, string>        $errors
     * @param array<string, string>     $old
     */
    private function backToForm(array $errors, array $old): never
    {
        $_SESSION['contact_flash'] = ['errors' => $errors, 'old' => $old];
        $this->redirect('/contact#formulaire');
    }

    private function redirectSent(): never
    {
        $this->redirect('/contact?envoye=1#formulaire');
    }
}
