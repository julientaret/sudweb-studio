# Module Contact

## Rôle

Fournit un **formulaire de contact public** sur le site, conserve chaque demande
en **base de données**, la rend consultable dans l'**admin** (nouveau module) et
**notifie le propriétaire du site par e-mail** (template HTML soigné). Conçu
**conforme RGPD** : consentement explicite tracé, données minimales, IP seulement
hachée (anti-abus), purge automatique au-delà du délai de conservation.

Aucune dépendance à un autre module (pas de blocs, pas de média).

## Parcours

1. La page **statique** [`frontend/static/contact.php`](../../../frontend/static/contact.php)
   affiche le formulaire. Le site public étant sans état, elle démarre une
   **session légère** propre au formulaire (cookie strictement nécessaire) pour
   porter le jeton CSRF, l'horodatage anti-bot et les messages flash.
2. Le formulaire **POSTe vers `/contact/envoyer`** — route enregistrée par le
   module et traitée par `ContactController::submit` (via `Kernel::handleFront`).
3. En cas d'erreur : redirection vers `/contact` avec erreurs + ressaisie (flash).
   En cas de succès : enregistrement en base, e-mail au propriétaire, puis
   redirection vers `/contact?envoye=1`.

> La route a **deux segments** (`/contact/envoyer`) : pas de conflit avec le
> catch-all `/{slug}` des Pages, ni avec la page statique `/contact` (qui est
> servie par le front controller avant le noyau, en GET).

## Données

Migration [`001_create_contact_submissions.sql`](../../app/Modules/Contact/migrations/001_create_contact_submissions.sql).

- **`contact_submissions`** : `id`, `name`, `email`, `subject`, `message`,
  `consent` (bool), `consent_version` (texte de consentement versionné),
  `ip_hash` (**IP hachée** SHA-256, jamais en clair — anti-abus), `status`
  (`new`|`read`|`archived`), `created_at`.

## Routes

Déclarées dans `ContactModule::boot()`.

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/contact`         | `index`        | `contacts.view` |
| GET  | `/admin/contact/show`    | `show`         | `contacts.view` |
| POST | `/admin/contact/status`  | `updateStatus` | `contacts.view` + CSRF |
| POST | `/admin/contact/delete`  | `destroy`      | `contacts.delete` + CSRF |
| POST | `/contact/envoyer`       | `submit`       | public (CSRF de session + anti-spam) |

## Sécurité & anti-spam

Choix : **anti-spam maison, sans service tiers ni cookie de pistage → RGPD-clean**
(pas de Google reCAPTCHA, qui imposerait un bandeau de consentement). Trois lignes
de défense, cumulatives :

1. **Honeypot** — deux champs pièges (`website`, `company_url`) cachés hors écran.
   Remplis ⇒ bot ⇒ faux succès silencieux (rien n'est enregistré).
2. **Délai minimum** — horodatage d'affichage en session ; un envoi en moins de
   `min_seconds` est traité comme un bot (drop silencieux). Vérifié seulement si
   l'horodatage est présent (tolérant aux navigateurs sans cookie).
3. **Rate-limit par IP hachée** — au plus `rate_limit_max` envois par
   `rate_limit_window` (défaut : 5 / heure). S'y ajoute un **plafond global**
   (toutes IP) de `rate_limit_global_max` envois sur la même fenêtre (défaut :
   30 ; 0 = désactivé), contre les envois en masse via des IP tournantes.

S'ajoutent un **jeton CSRF** (session) et une **validation serveur** stricte
(nom, e-mail, sujet dans la liste, longueur du message, consentement obligatoire).

> Le spam reste rare avec ce dispositif. S'il devenait un problème, on pourrait
> brancher **Cloudflare Turnstile** (privacy-first) en option — voir la note dans
> [`config/contact.php`](../../config/contact.php). On évite Google reCAPTCHA.

## E-mail au propriétaire

[`App\Core\Mailer`](../../app/Core/Mailer.php) (PHP `mail()`, zéro dépendance)
envoie un message **HTML** rendu par
[`views/email_owner.php`](../../app/Modules/Contact/views/email_owner.php) :

- **From** = `EMAIL_SENDER` (domaine maîtrisé, SPF/DKIM), **Reply-To** = e-mail du
  visiteur (répondre = écrire au visiteur), enveloppe `-f` sur l'expéditeur ;
- protection contre l'**injection d'en-têtes** (CR/LF neutralisés, adresses
  validées), sujet encodé UTF-8.

L'échec d'envoi n'interrompt pas le parcours : **la demande est toujours
persistée** en base (l'e-mail est une notification, pas la source de vérité).

## RGPD

- **Consentement** explicite (case obligatoire), avec texte **versionné** stocké
  en base pour la traçabilité.
- **Minimisation** : seules les données utiles sont collectées ; l'IP n'est
  stockée que **hachée**, au seul titre de l'anti-abus.
- **Limitation de conservation** : purge automatique au-delà de `retention_months`
  (défaut **12 mois**), via `bin/flow contact:purge` (cron conseillé) et de façon
  **opportuniste** à l'ouverture de la liste admin.
- **Information** : note sous le formulaire + page `/politique-de-confidentialite`,
  désormais gérée par le module [Pages légales](legal-pages.md) (éditable dans l'admin).
- **Droits** (accès, rectification, effacement) : l'admin peut consulter et
  **supprimer** une demande à tout moment.

## Permissions

| Permission | Détenue par |
|------------|-------------|
| `contacts.view`   | `admin` (`*`), `manager` |
| `contacts.delete` | `admin` (`*`), `manager` |

Espace de permission **`contacts`**, mappé dans
[`config/roles.php`](../../config/roles.php).

## Configuration

[`config/contact.php`](../../config/contact.php) : destinataire/expéditeur (depuis
`.env.local`), `retention_months`, paramètres anti-spam (`min_seconds`,
`rate_limit_max`, `rate_limit_window`), `subjects`, texte/version de consentement,
`privacy_url`. Le secret de hachage des IP vient de `APP_SECRET` (`.env.local`),
avec repli sur la config DB s'il est absent.

## CLI

```bash
php flow-cms/bin/flow contact:purge   # purge RGPD des demandes expirées
```
