# Module Devis

## Rôle

Fournit un **pop-up de demande de devis multi-étapes** sur le site public,
conserve chaque demande en **base de données**, la rend consultable dans
l'**admin** et **notifie l'adresse de contact par e-mail** (template HTML).
Le formulaire (étapes + questions) est **entièrement configurable depuis
l'admin**. Conçu **conforme RGPD** : consentement explicite tracé, données
minimales, IP seulement hachée (anti-abus), purge automatique au-delà du délai
de conservation.

Module **indépendant** : pas de blocs, pas de média, pas de dépendance au module
Contact (il porte sa propre session légère et son propre hachage d'IP).

## Parcours (front)

1. Le **pop-up** vit dans le pied de page du site public
   ([`frontend/includes/footer.php`](../../../frontend/includes/footer.php)),
   rendu **dynamiquement** d'après le schéma admin. Il s'ouvre depuis un lien
   d'en-tête (`Demander un devis`) **et** un bouton flottant présent sur toutes
   les pages.
2. À l'ouverture, le JS ([`frontend/src/js/devis.js`](../../../frontend/src/js/devis.js))
   récupère un **jeton CSRF** via **POST `/devis/token`** (qui démarre la session
   légère et pose l'horodatage anti-bot).
3. La saisie se fait **étape par étape** (validation cliente à chaque « Suivant »).
4. L'envoi **POSTe vers `/devis/envoyer`** (AJAX, JSON). Réponse `{ok:true}` →
   écran de remerciement ; `{ok:false, errors:[…]}` → messages affichés.

> Les routes ont **deux segments** (`/devis/token`, `/devis/envoyer`) : pas de
> conflit avec le catch-all `/{slug}` des Pages. Le jeton est servi en **POST**
> pour rester hors mesure d'audience (le module Stats ne compte que les GET).

## Formulaire configurable

Le schéma est géré par
[`DevisFormSettings`](../../app/Modules/Devis/DevisFormSettings.php) et stocké
dans **`storage/devis_form.json`**. Tant que rien n'est enregistré, un **schéma
par défaut** dérivé de [`config/devis.php`](../../config/devis.php) est servi
(comportement identique jusqu'à la première personnalisation).

- **Forme** : une liste d'**étapes** `{title, fields}` ; chaque **question**
  `{key, label, type, required, options, minlength, core}`.
- **Types** : `text`, `email`, `tel`, `textarea`, `select` (avec options).
- **Questions cœur** (`name`, `email`) : toujours présentes, **non supprimables**
  et de type imposé — elles alimentent les colonnes dédiées et le Reply-To de la
  notification. Réinjectées automatiquement si retirées.
- **Constructeur visuel** dans l'admin
  ([`views/form.php`](../../app/Modules/Devis/views/form.php) +
  [`public/assets/js/devis-form.js`](../../public/assets/js/devis-form.js),
  externe pour la CSP) : ajout/suppression/déplacement d'étapes et de questions.
  Le schéma est sérialisé en JSON dans un champ caché, **renormalisé côté serveur**
  à l'enregistrement (types valides, clés sûres/uniques, clés réservées
  interdites : `_csrf`, `website`, `company_url`, `consent`, `schema`).

## Données

Migrations [`001_create_quote_requests.sql`](../../app/Modules/Devis/migrations/001_create_quote_requests.sql)
puis [`002_dynamic_answers.sql`](../../app/Modules/Devis/migrations/002_dynamic_answers.sql)
(passage au formulaire dynamique).

- **`quote_requests`** : `id`, `name`, `email`, **`answers`** (JSON ordonné
  `[{label, value}]` des réponses, hors nom/e-mail), `consent` (bool),
  `consent_version`, `ip_hash` (**IP hachée** SHA-256, jamais en clair),
  `status` (`new`|`read`|`quoted`|`archived`), `created_at`.

## Routes

Déclarées dans `DevisModule::boot()`.

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/devis`         | `index`        | `devis.view` |
| GET  | `/admin/devis/show`    | `show`         | `devis.view` |
| POST | `/admin/devis/status`  | `updateStatus` | `devis.view` + CSRF |
| POST | `/admin/devis/delete`  | `destroy`      | `devis.delete` + CSRF |
| GET  | `/admin/devis/form`    | `editForm`     | `devis.configure` |
| POST | `/admin/devis/form`    | `saveForm`     | `devis.configure` + CSRF |
| POST | `/devis/token`         | `token`        | public (émet le jeton CSRF) |
| POST | `/devis/envoyer`       | `submit`       | public (CSRF de session + anti-spam) |

## Sécurité & anti-spam

Même dispositif que le module Contact : **anti-spam maison, sans service tiers
ni cookie de pistage → RGPD-clean** (pas de Google reCAPTCHA). Trois lignes de
défense cumulatives :

1. **Honeypot** — deux champs pièges (`website`, `company_url`) cachés hors écran.
2. **Délai minimum** — horodatage en session ; un envoi en moins de `min_seconds`
   est traité comme un bot (drop silencieux), seulement si l'horodatage est présent.
3. **Rate-limit par IP hachée** — au plus `rate_limit_max` envois par
   `rate_limit_window` (défaut : 5 / heure). S'y ajoute un **plafond global**
   (toutes IP) de `rate_limit_global_max` envois sur la même fenêtre (défaut :
   30 ; 0 = désactivé), contre les envois en masse via des IP tournantes.

S'ajoutent un **jeton CSRF** (session) et une **validation serveur** pilotée par
le schéma (champs obligatoires, format e-mail, appartenance aux options d'une
liste, longueur minimale d'une zone de texte, consentement obligatoire).

## E-mail à l'adresse de contact

[`App\Core\Mailer`](../../app/Core/Mailer.php) envoie un message **HTML** rendu
par [`views/email_owner.php`](../../app/Modules/Devis/views/email_owner.php), qui
liste **génériquement** les réponses :

- **From** = `EMAIL_SENDER` (domaine maîtrisé, SPF/DKIM), **Reply-To** = e-mail du
  demandeur, enveloppe `-f` sur l'expéditeur ;
- protection contre l'**injection d'en-têtes**, sujet encodé UTF-8.

L'échec d'envoi n'interrompt pas le parcours : **la demande est toujours
persistée** (l'e-mail est une notification, pas la source de vérité).

## RGPD

- **Consentement** explicite (case obligatoire), texte **versionné** en base.
- **Minimisation** : l'IP n'est stockée que **hachée**, au seul titre de l'anti-abus.
- **Limitation de conservation** : purge automatique au-delà de `retention_months`
  (défaut **12 mois**), de façon **opportuniste** à l'ouverture de la liste admin.
- **Droits** : l'admin peut consulter et **supprimer** une demande à tout moment.

## Permissions

| Permission | Détenue par |
|------------|-------------|
| `devis.view`      | `admin` (`*`), `manager` |
| `devis.delete`    | `admin` (`*`), `manager` |
| `devis.configure` | `admin` (`*`), `manager` |

Espace de permission **`devis`**, mappé dans
[`config/roles.php`](../../config/roles.php).

## Configuration

[`config/devis.php`](../../config/devis.php) : destinataire/expéditeur (depuis
`.env.local`), `retention_months`, paramètres anti-spam, et le **schéma par
défaut** (`project_types`, `budgets`, `deadlines`, `description_min_length`) servi
tant qu'aucune personnalisation n'est enregistrée dans `storage/devis_form.json`.
Texte/version de consentement et `privacy_url` y figurent également.
