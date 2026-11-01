# Flow — Manuel de maintenance « à la main »

> **Postulat de ce document** : il n'y a plus d'assistant IA. Vous êtes seul·e
> devant le code, avec un éditeur, un terminal, un navigateur et un client FTP/SSH.
> Ce manuel doit suffire pour **comprendre**, **modifier**, **déployer** et
> **dépanner** un site construit sur Flow, sans rien deviner.
>
> Il ne remplace pas les docs existantes, il les relie :
>
> | Document | Contenu |
> |----------|---------|
> | [`README.md`](README.md) | Présentation rapide du dépôt |
> | [`flow-cms/flow-brief.md`](flow-cms/flow-brief.md) | Document fondateur : philosophie et choix techniques |
> | [`flow-cms/docs/README.md`](flow-cms/docs/README.md) | Composants du Core + checklist « nouveau module » |
> | [`flow-cms/docs/modules/*.md`](flow-cms/docs/modules/) | Une fiche détaillée par module (tables, routes, permissions, pièges) |
> | [`flow-cms/docs/maintenance.md`](flow-cms/docs/maintenance.md) / [`demo.md`](flow-cms/docs/demo.md) | Mode maintenance / mode démo |
> | **Ce fichier** | Le mode d'emploi complet pour intervenir seul·e |

---

## Sommaire

1. [Vue d'ensemble en 2 minutes](#1-vue-densemble-en-2-minutes)
2. [Comment une requête est traitée (et routage des URL)](#2-comment-une-requête-est-traitée)
3. [Carte du code : « je veux changer X → j'ouvre Y »](#3-carte-du-code--je-veux-changer-x--jouvre-y)
4. [Installer un environnement de développement](#4-installer-un-environnement-de-développement)
5. [Configuration : les trois couches](#5-configuration--les-trois-couches)
6. [Recettes : modifier le site public](#6-recettes--modifier-le-site-public)
7. [Recettes : modifier l'admin / le CMS](#7-recettes--modifier-ladmin--le-cms)
8. [Base de données & migrations](#8-base-de-données--migrations)
9. [Conventions de code à respecter](#9-conventions-de-code-à-respecter)
10. [Déployer en production](#10-déployer-en-production)
11. [Mettre à jour un site en production](#11-mettre-à-jour-un-site-en-production)
12. [Sauvegarde & restauration](#12-sauvegarde--restauration)
13. [Tâches planifiées (cron)](#13-tâches-planifiées-cron)
14. [Dépannage](#14-dépannage)
15. [Sécurité : check-list](#15-sécurité--check-list)
16. [Git : workflow conseillé](#16-git--workflow-conseillé)
17. [Pièges connus](#17-pièges-connus)
18. [Aide-mémoire des commandes](#18-aide-mémoire-des-commandes)

---

## 1. Vue d'ensemble en 2 minutes

Flow est un **CMS en PHP pur** (aucun framework, aucune librairie PHP en
production). Composer ne sert **qu'à générer l'autoloader**. Il y a deux
applications qui partagent **le même noyau, la même base et le même `.env.local`** :

```
                    ┌───────────────────────────────────────────┐
  Visiteur  ──────► │ frontend/   SITE PUBLIC                   │
  /flow/…           │  public/index.php  (front controller)     │──┐
                    │  static/*.php      (pages écrites à main) │  │
                    │  includes/, layouts/, config/  (thème)    │  │  démarre
                    └───────────────────────────────────────────┘  │  le noyau
                                                                    ▼
                    ┌───────────────────────────────────────────┐   ┌──────────────┐
  Gestionnaire ───► │ flow-cms/   ADMIN (CMS)                   │──►│ flow-cms/app │
  /flow/flow-cms/…  │  public/index.php  (front controller)     │   │  Core/       │
                    └───────────────────────────────────────────┘   │  Modules/    │
                                                                    └──────┬───────┘
                                                                           │ PDO
                                     .env.local (racine) ─────────► MySQL/MariaDB
```

Points clés à retenir :

- **Le noyau** (`flow-cms/app/Core`) fait tout : config, routage, base, auth,
  rendu des blocs. Le site public ne fait que le démarrer et l'habiller.
- **Tout est module** (`flow-cms/app/Modules/<Nom>`) : Pages, Media, Agenda,
  Contact… Chaque module déclare ses routes, ses migrations SQL, son menu admin et
  ses blocs de page.
- **Les pages du CMS sont composées de blocs** (titre, texte, image, vidéo…),
  stockées en **JSON** dans la base. Le texte riche est stocké en **Delta Quill**
  (JSON), jamais en HTML.
- **Les fichiers uploadés** (images, PDF) vivent dans `frontend/public/uploads`
  et `frontend/public/documents` — **pas dans git**.
- **Les assets du site** (CSS/JS) sont compilés avec Node **en local**, et le
  résultat compilé **est commité** : le serveur n'a pas besoin de Node.

Stack requise : **PHP ≥ 8.2** (extensions `pdo_mysql`, `gd`, `fileinfo`,
`mbstring`), **MySQL 5.7+/MariaDB 10.3+**, **Apache avec `mod_rewrite`** (les
`.htaccess` font le routage), Composer. Node ≥ 18 uniquement en local pour les
assets.

---

## 2. Comment une requête est traitée

Comprendre ce chemin, c'est savoir où chercher quand quelque chose casse.

### 2.1 Requête sur le site public (ex. `GET /flow/contact`)

1. **Apache** lit [`.htaccess`](.htaccess) (racine) :
   - refuse en 404 tout fichier caché (`.env.local`, `.git/…`) et les fichiers
     `*.md`, `*.sql`, `composer.json`… ;
   - laisse passer `/flow-cms/…` à l'admin ;
   - sert directement les fichiers réels de `frontend/public/` (CSS, images) ;
   - envoie tout le reste à `frontend/public/index.php`.
2. [`frontend/public/index.php`](frontend/public/index.php) :
   1. charge l'autoloader de `flow-cms/vendor/` et démarre le noyau
      (`Kernel::boot`) → lit `.env.local` + tous les `flow-cms/config/*.php` ;
   2. **mode maintenance** : si actif et visiteur non autorisé → page 503, fin ;
   3. **redirection** déclarée dans `frontend/config/static_routes.php` ? → 301/302, fin ;
   4. enregistre la vue de page (module Stats, si actif) ;
   5. **page statique** `frontend/static/<slug>.php` (ou alias) ? → `require`, fin ;
   6. sinon **`Kernel::handleFront()`** : démarre les modules et dispatche au
      routeur → `/{slug}` (Pages), `/actualites/{slug}`, `/agenda/{slug}`,
      pages légales, `POST /contact/envoyer`, `POST /devis/envoyer`…
   7. rien trouvé → 404.

> **Priorité** : redirection > page statique > page CMS. Un fichier
> `static/contact.php` masque une page CMS de slug `contact`.

### 2.2 Requête sur l'admin (ex. `GET /flow/flow-cms/admin/pages`)

1. [`flow-cms/.htaccess`](flow-cms/.htaccess) renvoie tout dans `flow-cms/public/`.
2. [`flow-cms/public/.htaccess`](flow-cms/public/.htaccess) : fichier réel ? servi ;
   sinon → `index.php`.
3. [`flow-cms/public/index.php`](flow-cms/public/index.php) → `Kernel::boot()->handleWeb()` :
   session, en-têtes de sécurité (CSP, X-Frame-Options…), filtre géographique
   (`IpGeoGuard`), routes du Core (`/admin`, `/admin/administration`,
   `/admin/maintenance`…), puis `bootAll()` des modules, puis dispatch.
4. Le **routeur** ([`Router.php`](flow-cms/app/Core/Router.php)) cherche d'abord
   une **route exacte**, puis les routes à paramètres (`/{slug}`) **dans l'ordre
   d'enregistrement**.
5. Le contrôleur (`extends App\Core\Controller`) vérifie la **permission**
   (`requirePermission`) et, en POST, le **jeton CSRF** (`requireCsrf`), puis rend
   sa vue dans le layout admin (`renderPage`).

### 2.3 Le « base path » (préfixe d'URL)

Le préfixe (`/flow`, `/flow/flow-cms`, ou vide) est **auto-détecté** depuis
`SCRIPT_NAME`. On peut donc installer le projet à la racine d'un domaine ou dans
un sous-dossier sans rien changer. Pour forcer une valeur :
`flow-cms/config/app.php` → `'base_path' => '/mon-prefixe'`.

Dans le code, ne jamais écrire d'URL en dur : utiliser `site_url('/contact')`
(front) ou `$this->url('/admin/…')` / la variable `$base` (admin).

### 2.4 Le routage des URL en détail

Une URL traverse **trois étages**. Quand une URL ne mène pas où on l'attend, on
les vérifie dans cet ordre.

| Étage | Où | Rôle |
|-------|----|------|
| 1. Apache | `.htaccess` (racine), `flow-cms/.htaccess`, `*/public/.htaccess` | Bloque les fichiers sensibles, sert les fichiers réels (CSS, images), envoie le reste au bon `index.php` |
| 2. Front controller public | `frontend/public/index.php` | Redirections → pages statiques → routeur PHP (voir §2.1) |
| 3. Routeur PHP | `flow-cms/app/Core/Router.php` | Associe un chemin + une méthode HTTP à un contrôleur |

#### Carte des URL publiques

| URL | Servie par | Déclarée dans |
|-----|-----------|---------------|
| Toute URL listée dans `redirects` | Redirection 301/302 | `frontend/config/static_routes.php` |
| `/` | `frontend/static/index.php` (sinon la page CMS `app.home_slug`) | fichier statique / `Kernel::handleFront()` |
| `/<chemin>` correspondant à un fichier `static/<chemin>.php` ou à un alias `routes` | Page statique | `frontend/static/`, `static_routes.php` |
| `/<slug-page-légale>` | Module LegalPages (une route **exacte** par page publiée) | `LegalPagesModule::boot()` |
| `/<slug>` (un seul segment) | Module Pages (page publiée, sinon 404 aux couleurs du site) | `PagesModule::boot()` |
| `/actualites/<slug>` | Module Actualites | `ActualitesModule::boot()` |
| `/agenda/<slug>` | Module Agenda | `AgendaModule::boot()` |
| `POST /contact/envoyer` | Formulaire de contact | `ContactModule::boot()` |
| `POST /devis/token`, `POST /devis/envoyer` | Pop-up de devis | `DevisModule::boot()` |
| `/assets/…`, `/uploads/…`, `/documents/…` | Fichiers réels servis directement par Apache | — |

Côté admin, tout est sous `/flow-cms/admin/…` : les routes du Core sont
déclarées dans `Kernel::handleWeb()`, les autres dans le `boot()` de chaque module.

#### Règles du routeur

- **Deux méthodes seulement** : `$router->get()` et `$router->post()`. Pas de
  PUT/DELETE : une suppression est un `POST …/delete`. Une requête `HEAD` (outils
  de monitoring) reçoit un 404 : configurer ces outils en `GET`.
- **Handler** : soit `[MonController::class, 'methode']` (instancié à chaque
  requête), soit une fonction anonyme.
- **Paramètres** : `{nom}` capture **un seul segment** (pas de `/`). Ils arrivent
  dans le handler sous forme de tableau, déjà décodés :

```php
$router->get('/partenaires/{slug}', [PartenaireController::class, 'show']);

public function show(array $params = []): void
{
    $slug = $params['slug'] ?? '';
}
```

- **Query string** : non prise en compte pour choisir la route. L'admin s'en sert
  pour les identifiants (`/admin/pages/edit?id=12`), lus avec `$this->input('id')`.
- **Priorité** :
  1. une **route exacte** gagne toujours, quel que soit l'ordre de déclaration ;
  2. sinon, les routes à paramètres sont testées **dans l'ordre de déclaration** :
     d'abord les routes du Core, puis les modules dans l'ordre de
     `flow-cms/config/modules.php`. La première qui correspond l'emporte.
- **Normalisation** : le slash final est ignoré (`/contact/` = `/contact`) et le
  préfixe (`/flow`) est retiré avant la comparaison.
- **Aucune route trouvée** : `Router::notFound()` affiche un 404 minimal. Pour le
  personnaliser, modifier cette méthode. Les URL à un seul segment ne tombent
  presque jamais dans ce cas, car elles sont captées par `/{slug}` (Pages), qui
  affiche son propre 404 dans le gabarit du site.
- Les modules sont démarrés **dans les deux applications** : leurs routes sont
  donc enregistrées à la fois côté admin et côté site public. Utiliser
  uniquement les URL d'admin sous `/flow-cms/` et les URL publiques à la racine.

#### Recettes

**Ajouter une page publique d'un module à un seul segment** (ex. `/partenaires`) :
la déclarer en route **exacte** dans le `boot()` du module. Elle passera devant
le `/{slug}` des Pages.

```php
$router->get('/partenaires', [PartenaireController::class, 'listPublic']);
```

**Ajouter une page de détail** (ex. `/partenaires/acme`) : utiliser un préfixe
propre au module pour ne pas entrer en conflit avec les autres routes.

```php
$router->get('/partenaires/{slug}', [PartenaireController::class, 'show']);
```

À éviter : une route à paramètre sur un seul segment (`/{quelquechose}`) dans
un nouveau module. Elle entrerait en concurrence avec celle des Pages, et la
première déclarée gagnerait.

**Ajouter un écran d'admin** : `$router->get('/admin/xxx', …)` (et la route
`post` de son formulaire), plus une entrée dans `adminMenu()` pour qu'il
apparaisse dans le menu.

**Changer l'URL d'une page existante** (slug modifié, refonte) : ajouter une
redirection 301 dans `frontend/config/static_routes.php`, pour ne perdre ni le
référencement ni les liens existants.

```php
'redirects' => ['ancien-slug' => '/nouveau-slug'],
```

**Conflits possibles**, du plus fort au plus faible :

1. redirection (`static_routes.php`) ;
2. page statique (`frontend/static/`) ;
3. route exacte d'un module (page légale, `/partenaires`…) ;
4. route à paramètre, dans l'ordre de déclaration (`/{slug}` des Pages…).

Une page CMS dont le slug est déjà pris par un niveau plus fort devient
**inaccessible** sans aucun message d'erreur. Pour voir qui prend une URL :

```bash
grep -rn "'/partenaires" flow-cms/app frontend/config
ls frontend/static/
```

---

## 3. Carte du code : « je veux changer X → j'ouvre Y »

### 3.1 Arborescence commentée

```
flow/
├── .env.local                 secrets du serveur (DB, APP_URL, e-mails) — JAMAIS dans git
├── .env.example               modèle de .env.local (dans git)
├── .htaccess                  routage racine + blocage des fichiers sensibles
├── index.php                  filet : redirige vers frontend/ si mod_rewrite absent
├── install.php                installateur (base + migrations + 1er admin) — À SUPPRIMER en prod
├── MANUEL.md                  ce fichier
│
├── frontend/                  ===== SITE PUBLIC =====
│   ├── public/                seul dossier « web » du front
│   │   ├── index.php          front controller (voir §2.1)
│   │   ├── assets/            CSS/JS COMPILÉS (générés par npm, mais commités)
│   │   │   └── vendor/aos/    lib d'animation au scroll (commitée)
│   │   ├── uploads/           images uploadées depuis l'admin (hors git)
│   │   └── documents/         PDF etc. uploadés depuis l'admin (hors git)
│   ├── config/
│   │   ├── site.php           nom du site, navigation, footer, réseaux sociaux
│   │   └── static_routes.php  alias d'URL + redirections
│   ├── static/                pages écrites à la main (1 fichier = 1 URL)
│   │   ├── _template.php      gabarit à copier (le « _ » le rend non servable)
│   │   ├── index.php          page d'accueil
│   │   └── contact.php        page contact
│   ├── includes/              header.php, footer.php, helpers.php (site(), site_url(), asset_url())
│   ├── layouts/front_layout.php  gabarit des pages dynamiques du CMS
│   ├── src/                   SOURCES des assets (à éditer)
│   │   ├── css/               site.css (imports) + partials base/header/footer/blocks/contact/devis
│   │   ├── js/                site.js (+ devis.js)
│   │   └── img/               images de design PNG/JPG → converties en WebP
│   ├── scripts/build.mjs      chaîne de build (Lightning CSS, esbuild, sharp)
│   └── package.json           npm run build / npm run watch
│
└── flow-cms/                  ===== ADMIN + NOYAU =====
    ├── .htaccess              renvoie tout vers public/
    ├── public/
    │   ├── index.php          front controller de l'admin
    │   ├── .htaccess          routage + taille d'upload (mod_php)
    │   ├── .user.ini          taille d'upload (php-fpm)
    │   └── assets/            CSS/JS de l'admin + vendor/ (Quill, Cropper, AOS) — édités directement, pas de build
    ├── app/
    │   ├── Core/              Kernel, Router, Config, Database, View, Controller, Auth, Csrf,
    │   │   │                  Migrator, ModuleRegistry, BlockRegistry, BlockContent,
    │   │   │                  DeltaRenderer, Mailer, Maintenance*, Demo*, Aos*, IpGeoGuard…
    │   │   ├── Blocks/        blocs de base : heading, richtext, quote, button, accordion, video
    │   │   ├── views/         layout admin, dashboard, tutoriel, administration, maintenance
    │   │   └── helpers.php    e(), partial(), module_enabled(), slug(), admin_icon()
    │   └── Modules/<Nom>/     un dossier par module :
    │       ├── <Nom>Module.php        routes, menu, migrations, blocs
    │       ├── *Controller.php        actions HTTP
    │       ├── *Repository.php        requêtes SQL
    │       ├── <Entité>.php           objet de données (fromRow)
    │       ├── Blocks/                blocs de page fournis par le module
    │       ├── views/                 templates PHP
    │       └── migrations/NNN_*.sql   schéma
    ├── config/                app, modules, roles, security, media, documents, storage,
    │                          contact, devis, stats, google_reviews
    ├── storage/               état d'admin en JSON + logs/ + cache/  (voir §5.3)
    ├── migrations/            migrations transverses (vide aujourd'hui)
    ├── bin/flow               CLI : migrate, user:create, media:regen, contact:purge, stats:purge
    ├── vendor/                autoloader Composer (GÉNÉRÉ, hors git)
    ├── composer.json          autoload PSR-4 App\ → app/ + helpers.php
    └── docs/                  docs du CMS
```

### 3.2 Table de correspondance

| Je veux… | Fichier(s) |
|----------|-----------|
| Changer le nom du site, le menu, le footer, les réseaux sociaux | `frontend/config/site.php` |
| Changer le `<head>` / l'en-tête HTML du site | `frontend/includes/header.php` |
| Changer le pied de page / la popup devis / les scripts chargés | `frontend/includes/footer.php` |
| Changer la page d'accueil | `frontend/static/index.php` |
| Créer une page codée à la main | copier `frontend/static/_template.php` (§6.2) |
| Ajouter une redirection ou un alias d'URL | `frontend/config/static_routes.php` |
| Changer le style du site | `frontend/src/css/*.css` puis `npm run build` |
| Changer le JS du site | `frontend/src/js/*.js` puis `npm run build` |
| Changer le gabarit des pages créées dans l'admin | `frontend/layouts/front_layout.php` |
| Changer le HTML produit par un bloc (titre, image…) | `flow-cms/app/Core/Blocks/*Block.php` ou `flow-cms/app/Modules/<Nom>/Blocks/*Block.php` |
| Changer l'apparence de l'admin | `flow-cms/public/assets/css/*`, `flow-cms/app/Core/views/layout.php` |
| Changer un écran d'admin d'un module | `flow-cms/app/Modules/<Nom>/views/*.php` |
| Changer qui a le droit de faire quoi | `flow-cms/config/roles.php` |
| Activer/retirer un module du site | `flow-cms/config/modules.php` (+ onglet Administration) |
| Tailles d'images générées | `flow-cms/config/media.php` puis `php flow-cms/bin/flow media:regen` |
| Sujets / textes RGPD du formulaire de contact | `flow-cms/config/contact.php` |
| Durée de session, anti-brute-force | `flow-cms/config/app.php` |
| Pays autorisés pour l'admin | `flow-cms/config/security.php` |
| Durée de conservation des stats | `flow-cms/config/stats.php` |
| Base de données, URL du site, e-mails | `.env.local` |
| En-têtes de sécurité (CSP…) | `flow-cms/app/Core/Kernel.php` → `sendSecurityHeaders()` |
| Page de maintenance personnalisée | créer `frontend/maintenance.php` |

---

## 4. Installer un environnement de développement

### 4.1 Prérequis

- PHP 8.2+ en ligne de commande (`php -v`) avec `pdo_mysql`, `gd`, `fileinfo`.
- MySQL ou MariaDB.
- Composer (`composer -V`).
- Node 18+ et npm (uniquement pour recompiler le CSS/JS du site).
- Apache avec `mod_rewrite` (MAMP, Laragon, XAMPP…) — recommandé pour reproduire la prod.

### 4.2 Pas à pas

```bash
git clone git@github.com:julientaret/flow.git
cd flow
```

```bash
# 1. Autoloader PHP (crée flow-cms/vendor/)
cd flow-cms && composer install && cd ..
```

```bash
# 2. Configuration
cp .env.example .env.local
# → éditer .env.local : DB_HOST, DB_NAME, DB_USER, DB_PASS, APP_URL=http://localhost/flow
```

```bash
# 3. Base + migrations + 1er compte admin (crée la base si elle n'existe pas)
php install.php admin@exemple.test MotDePasse123 "Admin"
```

(Équivalent manuel : créer la base vous-même, puis
`php flow-cms/bin/flow migrate` et
`php flow-cms/bin/flow user:create admin@exemple.test MotDePasse123 admin "Admin"`.)

```bash
# 4. Assets du site (seulement si vous touchez au CSS/JS)
cd frontend && npm install && npm run build && cd ..
```

### 4.3 Servir le projet

**Option A — Apache/MAMP (recommandé)** : le `DocumentRoot` pointe sur le dossier
**parent** de `flow/`. Ensuite :
- site public : `http://localhost/flow/`
- admin : `http://localhost/flow/flow-cms/`

`APP_URL` doit valoir `http://localhost/flow` (sans slash final).

**Option B — serveur PHP intégré** (rapide, sans `.htaccess`) :

```bash
php -S localhost:8000 -t flow-cms/public
```

→ admin sur `http://localhost:8000/`. Pour le front :
`php -S localhost:8001 -t frontend/public` (et `APP_URL=http://localhost:8001`
pour que les images s'affichent). Limite : la taille d'upload n'est pas relevée,
ajouter `-d upload_max_filesize=25M -d post_max_size=28M`.

### 4.4 Vérifier que tout marche

1. `http://localhost/flow/` affiche la page d'accueil.
2. `http://localhost/flow/flow-cms/` redirige vers `/admin/login`.
3. Connexion → tableau de bord.
4. Médias → uploader une image → elle s'affiche (sinon voir §14).
5. Pages → créer une page `test`, la publier → `http://localhost/flow/test` l'affiche.

---

## 5. Configuration : les trois couches

### 5.1 `.env.local` — propre à chaque serveur, jamais commité

Lu par `App\Core\Config::env()`. Format `CLE=valeur`, `#` pour commenter.

| Variable | Obligatoire | Rôle |
|----------|:-:|------|
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | oui | Connexion MySQL |
| `APP_URL` | oui | URL absolue du site public, **sans slash final**. Base des URLs d'images/documents et des liens dans les e-mails |
| `PROD` | en prod | `true` → les erreurs PHP ne sont plus affichées (seulement loguées) |
| `EMAIL_CONTACT` | si Contact/Devis | Destinataire des demandes |
| `EMAIL_SENDER` | si Contact/Devis | Expéditeur technique (domaine avec SPF/DKIM) |
| `APP_SECRET` | recommandé | Sel pour hacher les IP (anti-spam). Générer : `php -r "echo bin2hex(random_bytes(32));"` |
| `GOOGLE_PLACES_API_KEY` | si Avis Google | Clé API Google Places |
| `UPLOADS_PATH` / `UPLOADS_URL`, `DOCUMENTS_PATH` / `DOCUMENTS_URL` | non | Déplacer le stockage des fichiers (voir `flow-cms/config/storage.php`) |

> **`APP_URL` est la valeur clé de la portabilité.** Si les images sont cassées
> après un changement de domaine, c'est presque toujours elle.

### 5.2 `flow-cms/config/*.php` — réglages du code, commités

Chaque fichier retourne un tableau ; on y accède par clé pointée :
`Config::get('contact.retention_months')`, `Config::get('app.session_idle_timeout')`.
**Tous** les fichiers du dossier sont chargés automatiquement : créer
`config/monmodule.php` suffit pour avoir `Config::get('monmodule.cle')`.

| Fichier | Contenu |
|---------|---------|
| `app.php` | nom, session (nom, durée, inactivité 2 h), `base_path`, `home_slug`, anti-brute-force (5 essais / 10 min) |
| `modules.php` | **catalogue** des modules installés et ordre de démarrage |
| `roles.php` | rôles → permissions |
| `security.php` | filtre géographique de l'admin (FR par défaut, *fail-open*) |
| `media.php` | tailles d'images, ratios de recadrage, types acceptés, limites |
| `documents.php` | types et taille max des documents |
| `storage.php` | emplacement des uploads (lit `.env.local`) |
| `contact.php` | destinataire, rétention RGPD, anti-spam, sujets, texte de consentement |
| `devis.php` | réglages du formulaire de devis |
| `stats.php` | rétention, exclusions (admins, IP, robots) |
| `google_reviews.php` | réglages du module Avis Google |

`frontend/config/site.php` et `frontend/config/static_routes.php` sont l'équivalent
pour le thème public.

### 5.3 `flow-cms/storage/*.json` — état modifié depuis l'admin

Ces fichiers sont écrits par l'admin. **Ce sont des données, pas du code** :
ne pas les écraser lors d'un déploiement.

| Fichier | Écrit par |
|---------|-----------|
| `modules.json` | Administration → modules désactivés |
| `aos.json` | Administration → durée des animations |
| `demo.json` | Administration → mode démo |
| `maintenance.json` | Maintenance → actif / jeton / IP autorisées |
| `horaires.json` | Module Horaires |
| `google_reviews.json` | Module Avis Google (établissement suivi, quota) |
| `devis_form.json` | Module Devis (champs du formulaire) |

Le dossier `storage/` (et `logs/`, `cache/`) doit être **inscriptible par PHP**.

---

## 6. Recettes : modifier le site public

### 6.1 Texte du menu, footer, nom du site

Éditer `frontend/config/site.php` :

```php
'name' => 'Boulangerie Martin',
'nav'  => [
    'Accueil'     => '/',
    'Nos pains'   => '/nos-pains',      // page CMS ou statique
    'Contact'     => '/contact',
    'Instagram'   => 'https://instagram.com/…', // lien externe : laissé tel quel
],
'footer' => ['copyright' => 'Boulangerie Martin'],
```

Aucune compilation nécessaire, rechargez la page.

### 6.2 Créer une page codée à la main

```bash
cp frontend/static/_template.php frontend/static/a-propos.php
```

Remplir `$title`, `$description` et le HTML entre header et footer. La page est
servie sur `/a-propos`. Sous-dossiers possibles : `static/services/web.php` →
`/services/web`. Noms autorisés : lettres, chiffres, `-`, `_` (pas de point).

Dans une page statique vous disposez de : `e()` (échappement — **toujours** sur
une donnée variable), `site('cle')`, `site_url('/chemin')`, `asset_url('/assets/…')`,
`module_enabled('Nom')`, et de toutes les classes du CMS (ex. un repository pour
afficher les dernières actualités).

### 6.3 Alias et redirections

`frontend/config/static_routes.php` :

```php
return [
    'routes' => [
        'qui-sommes-nous' => 'a-propos',   // /qui-sommes-nous sert static/a-propos.php
    ],
    'redirects' => [
        'ancienne-page' => '/nouvelle-page',                       // 301 par défaut
        'promo'         => ['to' => '/offres', 'status' => 302],   // temporaire
        'boutique'      => ['to' => 'https://shop.exemple.fr', 'status' => 301],
    ],
];
```

Les clés sont des chemins **sans slash** de début ni de fin.

### 6.4 Modifier le CSS / JS du site

On n'édite **jamais** `frontend/public/assets/site.css` ou `site.js` : ils sont
écrasés au build.

```bash
cd frontend
npm run watch      # recompile à chaque sauvegarde pendant que vous travaillez
# ... éditer src/css/*.css ou src/js/*.js ...
npm run build      # build final avant commit
```

- Nouveau fichier CSS : créer `src/css/ma-zone.css` puis ajouter
  `@import "ma-zone.css";` dans `src/css/site.css` (l'ordre compte).
- Nouveau module JS : `import './mon-module.js';` depuis `src/js/site.js`.
- Images de design : déposer un PNG/JPG dans `src/img/` → `npm run build` →
  `public/assets/img/<nom>.webp`, à utiliser via
  `<img src="<?= e(asset_url('/assets/img/nom.webp')) ?>">`.
- **Commiter les fichiers compilés** de `frontend/public/assets/` : le serveur n'a pas Node.
- Le cache navigateur est invalidé automatiquement (`asset_url()` ajoute `?v=<date du fichier>`).

**Sans Node ?** En dépannage, on peut éditer `public/assets/site.css` directement
(c'est du CSS minifié), mais la modification sera perdue au prochain build :
reportez-la dans `src/`.

**CSP** : le site interdit le JavaScript inline (`<script>…</script>` dans le
HTML, `onclick="…"`). Tout le JS va dans `src/js/`. Les scripts externes
(Google Maps, analytics…) seront bloqués tant que la CSP n'est pas élargie
(§7.10).

### 6.5 Modifier le gabarit des pages créées dans l'admin

`frontend/layouts/front_layout.php` reçoit `$title` et `$body` (HTML déjà rendu
par les blocs) et réutilise `includes/header.php` / `footer.php`. Les pages
Actualités, Agenda et Pages légales utilisent le même mécanisme : un fichier
`front_layout.php` dans `frontend/layouts/` est cherché **avant** celui du module
(voir `app.front_views` dans `frontend/public/index.php`).

Le HTML des blocs utilise des classes stables (`.flow-row.cols-N`, `.flow-col`,
`.block-quote`, `figure.block-image`, `.realisations-*`…) : stylez-les dans
`frontend/src/css/blocks.css`.

### 6.6 Page de maintenance personnalisée

Créer `frontend/maintenance.php` (HTML complet). Elle remplace la page intégrée
quand le mode maintenance est actif. Détails : [`flow-cms/docs/maintenance.md`](flow-cms/docs/maintenance.md).

### 6.7 Changer la page d'accueil

Deux possibilités :
- **Page codée** : `frontend/static/index.php` (prioritaire, c'est le cas actuel).
- **Page du CMS** : supprimer/renommer `static/index.php` ; la racine affichera alors
  la page CMS dont le slug vaut `app.home_slug` (`accueil` par défaut ; pour en changer, ajouter
  `'home_slug' => 'mon-slug',` dans `flow-cms/config/app.php`).

---

## 7. Recettes : modifier l'admin / le CMS

### 7.1 Activer ou désactiver un module

- **Pour un site** (retirer définitivement) : supprimer la ligne dans
  `flow-cms/config/modules.php` → `available`. Le code et les tables restent,
  rien n'est effacé.
- **Temporairement**, sans toucher au code : admin → **Administration** →
  décocher. L'état est stocké dans `storage/modules.json`.
- `Users` ne peut pas être désactivé.
- Ajouter un module au catalogue puis lancer `php flow-cms/bin/flow migrate`
  pour créer ses tables.

### 7.2 Rôles et permissions

Le code ne teste **jamais** un rôle, toujours une permission
(`$this->requirePermission('content.edit')`, `$this->auth->can('content.publish')`).
Le mapping est dans `flow-cms/config/roles.php` :

```php
'admin'   => ['*'],                         // tout
'manager' => ['content.*', 'media.*', …],   // wildcard par module
'editor'  => ['content.edit', 'media.upload', 'documents.upload'],
```

- **Donner un droit à un rôle** : ajouter la permission à sa liste.
- **Créer un rôle** : ajouter une clé (ex. `'stagiaire' => ['content.edit']`).
  Le rôle d'un compte est la colonne `users.role`. Si le formulaire d'admin ne
  propose pas le nouveau rôle, voir `flow-cms/app/Modules/Users/views/form.php`
  et `UserController.php`, ou utiliser la CLI : `bin/flow user:create … stagiaire`.
- Trouver toutes les permissions existantes :

```bash
grep -rhoE "(requirePermission|can)\('[a-z_.]+'\)" flow-cms/app | sort -u
```

Permissions principales : `content.edit`, `content.publish`, `content.delete`,
`media.*`, `documents.*`, `users.manage`, `contacts.view/delete`,
`devis.view/delete/configure`, `horaires.view/manage`, `stats.view`,
`googlereviews.view/sync/manage/configure`, `admin.modules`, `admin.maintenance`.

### 7.3 Gérer les comptes en ligne de commande

Créer un compte :

```bash
php flow-cms/bin/flow user:create jean@exemple.fr 'MotDePasseSolide' manager "Jean Dupont"
```

**Mot de passe oublié** (il n'y a pas de commande dédiée) : générer un hash puis
le mettre en base.

```bash
php -r "echo password_hash('NouveauMotDePasse', defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT), PHP_EOL;"
```

```sql
UPDATE users SET password_hash = '<hash collé ici>' WHERE email = 'jean@exemple.fr';
```

**Compte verrouillé** après trop d'essais : attendre 10 min, ou vider les
tentatives :

```sql
DELETE FROM login_attempts;
```

### 7.4 Changer les tailles d'images générées

1. Éditer `flow-cms/config/media.php` → `sizes` (`cover` = recadre, `fit` = garde le ratio).
2. Régénérer toutes les déclinaisons existantes :

```bash
php flow-cms/bin/flow media:regen
```

Sans cette commande, les anciennes images n'auront pas la nouvelle taille.

### 7.5 Créer un nouveau bloc de page

Exemple : un bloc « Encadré » avec un titre et un texte.

1. Créer `flow-cms/app/Core/Blocks/CalloutBlock.php` (bloc de base) — ou
   `flow-cms/app/Modules/<Nom>/Blocks/` s'il dépend d'un module :

```php
<?php

declare(strict_types=1);

namespace App\Core\Blocks;

use App\Core\Block;

final class CalloutBlock implements Block
{
    public function type(): string { return 'callout'; }      // identifiant stocké en base : NE PLUS LE CHANGER ensuite
    public function label(): string { return 'Encadré'; }    // libellé dans l'éditeur

    public function render(array $data): string
    {
        $title = trim((string) ($data['title'] ?? ''));
        $text  = trim((string) ($data['text'] ?? ''));
        if ($title === '' && $text === '') {
            return '';
        }
        return '<aside class="block-callout">'
            . ($title !== '' ? '<strong>' . e($title) . '</strong>' : '')
            . '<p>' . e($text) . '</p></aside>';
    }

    public function form(array $data): string
    {
        // Les noms de champs suivent TOUJOURS blocks[__KEY__][data][<champ>]
        return '<label>Titre</label>'
            . '<input type="text" name="blocks[__KEY__][data][title]" value="' . e((string) ($data['title'] ?? '')) . '">'
            . '<label>Texte</label>'
            . '<textarea name="blocks[__KEY__][data][text]" rows="3">' . e((string) ($data['text'] ?? '')) . '</textarea>';
    }

    public function sanitize(array $input): array
    {
        return [
            'title' => mb_substr(trim((string) ($input['title'] ?? '')), 0, 200),
            'text'  => mb_substr(trim((string) ($input['text'] ?? '')), 0, 2000),
        ];
    }
}
```

2. L'enregistrer :
   - bloc de base → `flow-cms/app/Core/Kernel.php`, constructeur, à côté de
     `$this->blocks->register(new \App\Core\Blocks\QuoteBlock());` ;
   - bloc de module → dans le `boot()` du module :
     `Kernel::instance()->blocks()->register(new CalloutBlock());`
3. Le styler dans `frontend/src/css/blocks.css` puis `npm run build`.
4. Il apparaît automatiquement dans le sélecteur de blocs de l'éditeur.

Règles : **tout** ce qui sort dans `render()` passe par `e()` ; `sanitize()`
borne chaque valeur ; les images se référencent **par ID média**, jamais par URL
(voir `flow-cms/app/Modules/Media/Blocks/ImageBlock.php` comme modèle) ; le texte
riche réutilise `RichtextBlock` + `DeltaRenderer` (voir `RealisationController`).

> Ne jamais renommer `type()` d'un bloc existant : les pages déjà en base
> référencent ce nom et le bloc disparaîtrait silencieusement.

### 7.6 Créer un nouveau module

La procédure de référence (9 points) est dans
[`flow-cms/docs/README.md`](flow-cms/docs/README.md#créer-un-nouveau-module--checklist).
Le plus simple est de **copier un module existant proche** :

- liste d'éléments + bloc d'affichage, sans URL publique → `Realisations` ;
- contenu avec page publique `/xxx/{slug}` → `Actualites` ;
- réglages simples sans table (JSON) → `Horaires`.

Résumé pour un module `Partenaires` :

```
flow-cms/app/Modules/Partenaires/
├── PartenairesModule.php        implements App\Core\Module
├── PartenaireController.php     extends App\Core\Controller
├── PartenaireRepository.php     requêtes préparées via Database::pdo()
├── Partenaire.php               objet + fromRow()
├── Blocks/PartenairesBlock.php  (optionnel) bloc front
├── views/list.php, editor.php
└── migrations/001_create_partenaires.sql
```

```php
final class PartenairesModule implements Module
{
    public function name(): string { return 'partenaires'; }

    public function boot(Router $router): void
    {
        Kernel::instance()->blocks()->register(new PartenairesBlock());
        $router->get('/admin/partenaires', [PartenaireController::class, 'index']);
        $router->post('/admin/partenaires/create', [PartenaireController::class, 'store']);
        // …
    }

    public function adminMenu(): array
    {
        return [['label' => 'Partenaires', 'url' => '/admin/partenaires', 'permission' => 'content.edit']];
    }

    public function migrations(): array
    {
        return [__DIR__ . '/migrations/001_create_partenaires.sql'];
    }
}
```

Puis :
1. ajouter `'Partenaires'` dans `flow-cms/config/modules.php` ;
2. ajouter libellé + description dans `ModuleSettings::INFO`
   (`flow-cms/app/Core/ModuleSettings.php`) ;
3. déclarer les permissions éventuelles dans `config/roles.php` ;
4. `php flow-cms/bin/flow migrate` ;
5. (optionnel) rubrique du tutoriel dans `flow-cms/app/Core/views/tutorial.php` ;
6. écrire `flow-cms/docs/modules/partenaires.md`.

Convention **obligatoire** : le dossier `Foo` contient la classe `FooModule`
dans le namespace `App\Modules\Foo`. Sinon : « Module introuvable ».

Dans un contrôleur, le squelette d'une action POST est toujours :

```php
public function store(): void
{
    $this->requirePermission('content.edit');   // 1. droit
    $this->requireCsrf();                       // 2. jeton CSRF
    $nom = $this->input('nom');                 // 3. lecture + validation
    // 4. repository → 5. redirect avec notice
    $this->redirect('/admin/partenaires?notice=' . rawurlencode('Enregistré.'));
}
```

et chaque `<form method="post">` d'une vue contient le champ CSRF :
`<?= \App\Core\Csrf::field() ?>` (regardez une vue existante pour la forme exacte).

### 7.7 Modifier un écran d'admin existant

1. Trouver la route : `grep -rn "'/admin/agenda" flow-cms/app`.
2. Elle mène à `[EventController::class, 'index']` → méthode `index()`.
3. La méthode appelle `renderPage($this->views(), 'list', …)` → template
   `flow-cms/app/Modules/Agenda/views/list.php`.
4. Le CSS de l'admin est dans `flow-cms/public/assets/css/`, le JS dans
   `flow-cms/public/assets/js/` (pas de build : on édite directement).

### 7.8 Ajouter une variable de configuration

- **Secret / dépend du serveur** → `.env.local` + `.env.example` (documentée),
  lue par `Config::env('MA_VAR', 'défaut')`.
- **Réglage du code** → un fichier `flow-cms/config/xxx.php`, lu par
  `Config::get('xxx.cle', 'défaut')`.

### 7.9 Envoyer un e-mail

Utiliser `App\Core\Mailer` (exemple : `ContactController`). Il passe par la
fonction `mail()` de PHP : le serveur doit savoir envoyer des mails, et
`EMAIL_SENDER` doit être sur un domaine dont le SPF/DKIM autorise ce serveur,
sinon les messages finissent en spam.

### 7.10 Autoriser un service externe (CSP)

La Content-Security-Policy est dans `Kernel::sendSecurityHeaders()` :

```php
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; frame-src https://www.youtube-nocookie.com https://player.vimeo.com");
```

Exemple pour une carte OpenStreetMap en iframe : ajouter
`https://www.openstreetmap.org` à `frame-src`. Pour des images d'un CDN : à
`img-src`. N'ajoutez jamais `'unsafe-inline'` à `script-src`.
Penser au RGPD : tout service tiers chargé sans consentement est un problème
(voir le choix « pas de reCAPTCHA » du projet).

---

## 8. Base de données & migrations

### 8.1 Tables

| Table | Module |
|-------|--------|
| `flow_migrations` | Core — liste des migrations appliquées |
| `users`, `login_attempts`, `password_resets`, `user_login_ips` | Users |
| `media`, `media_folders` | Media |
| `documents`, `document_folders` | Documents |
| `pages` | Pages |
| `events` | Agenda |
| `articles` | Actualites |
| `realisations` | Realisations |
| `slides` | Sliders |
| `legal_pages` | LegalPages |
| `contact_submissions` | Contact |
| `quote_requests` | Devis |
| `page_views` | Stats |
| `google_reviews` | GoogleReviews |

Les colonnes `is_demo` marquent les données factices du mode démo.

### 8.2 Format des contenus

- `pages.content` (et contenus similaires) : JSON = **lignes → colonnes (1 à 4) → blocs**.
  Chaque bloc : `{ "id", "type", "aos", "aos_delay", "data": {…} }`.
- Texte riche : **Delta Quill** JSON (`{"ops":[…]}`), converti en HTML au rendu
  par `App\Core\DeltaRenderer` (balises autorisées seulement).
- Images : **ID** de la table `media` ; le fichier et ses déclinaisons sont
  retrouvés à partir du `filename`.

Modifier ces JSON à la main en SQL est possible mais risqué : préférez l'admin.

### 8.3 Faire évoluer le schéma

Le `Migrator` applique chaque fichier `.sql` **une seule fois** (suivi dans
`flow_migrations`, par chemin de fichier).

**Règle d'or : on ne modifie JAMAIS une migration déjà appliquée quelque part.**
On en ajoute une nouvelle.

1. Créer `flow-cms/app/Modules/Pages/migrations/003_add_meta_description.sql` :

```sql
ALTER TABLE pages ADD COLUMN meta_description VARCHAR(300) NOT NULL DEFAULT '';
```

2. L'ajouter dans `PagesModule::migrations()` (l'ordre du tableau n'a pas
   d'importance : les fichiers sont triés par chemin, d'où la numérotation
   `001_`, `002_`…).
3. Appliquer :

```bash
php flow-cms/bin/flow migrate
```

Une migration transverse (hors module) se dépose dans `flow-cms/migrations/`.

Pièges : MySQL valide chaque `CREATE`/`ALTER` immédiatement (pas de rollback) ;
si une migration plante à mi-chemin, corrigez la base à la main puis relancez.
Préférez `CREATE TABLE IF NOT EXISTS`. Pas de commande de retour arrière : pour
annuler, écrivez une nouvelle migration inverse.

---

## 9. Conventions de code à respecter

1. `declare(strict_types=1);` en tête de chaque fichier PHP.
2. **Échapper toute sortie** : `<?= e($variable) ?>`. Seul le HTML déjà produit
   par des blocs (`$body`) s'affiche sans `e()`.
3. **SQL uniquement en requêtes préparées** (`$pdo->prepare(...)->execute([...])`),
   jamais de concaténation de valeurs utilisateur.
4. **Tout POST** : `requirePermission()` + `requireCsrf()`.
5. **Tester une permission, jamais un rôle.**
6. **Images par ID**, jamais par URL stockée.
7. **Texte riche en Delta**, jamais de HTML brut en base.
8. Pas de librairie PHP tierce. Côté JS admin, libs auto-hébergées dans
   `public/assets/vendor/` (pas de CDN).
9. Un module ne dépend pas d'un autre : utiliser `module_enabled('Media')` pour
   dégrader proprement une fonctionnalité optionnelle.
10. Pas d'URL en dur : `site_url()`, `asset_url()`, `$this->url()`, `$base`.
11. Commentaires et docs en français, comme le reste du code.
12. Documenter en même temps qu'on code (fiche `flow-cms/docs/modules/…`).

**Vérifier la syntaxe** sans framework de test :

```bash
find flow-cms/app frontend -name '*.php' -not -path '*/node_modules/*' -print0 | xargs -0 -n1 php -l | grep -v "No syntax errors"
```

(rien ne s'affiche = tout est bon). Le projet n'a pas de tests automatisés :
testez à la main les écrans touchés (§4.4).

---

## 10. Déployer en production

### 10.1 Ce qui part / ne part pas sur le serveur

| Part sur le serveur | Ne part PAS |
|---------------------|-------------|
| `.htaccess`, `index.php` (racine) | `.git/` (risque d'exposition du dépôt) |
| `frontend/` sauf `node_modules/`, `src/`, `scripts/` | `frontend/node_modules/` |
| `flow-cms/` (dont `vendor/` généré) | `.env.local` de dev (on en crée un pour la prod) |
| | `.claude/`, `.agents/`, `.DS_Store`, `*.md` (facultatif, déjà bloqués par `.htaccess`) |
| | `install.php` après la 1ʳᵉ installation |

**Données du serveur à ne jamais écraser** lors d'une mise à jour :
`.env.local`, `frontend/public/uploads/`, `frontend/public/documents/`,
`flow-cms/storage/*.json`, `flow-cms/storage/logs/`.

### 10.2 Préparer l'hébergement

- PHP 8.2+ avec `pdo_mysql`, `gd`, `fileinfo`, `mbstring`.
- Apache + `mod_rewrite` + `AllowOverride All` (les `.htaccess` doivent être lus).
  Sur Nginx, il faut traduire les `.htaccess` à la main (non fourni).
- Une base MySQL vide et son utilisateur (droits CREATE/ALTER/INDEX/SELECT/INSERT/UPDATE/DELETE).
- HTTPS (Let's Encrypt). Le chemin `/.well-known/` reste accessible.
- Domaine pointé de façon à ce que la racine web corresponde au dossier `flow/`
  (ou que `flow/` en soit un sous-dossier).

### 10.3 Première mise en ligne

1. **En local**, préparer :

```bash
cd frontend && npm ci && npm run build && cd ..
cd flow-cms && composer install --no-dev --optimize-autoloader && cd ..
git status   # tout doit être commité
```

2. **Transférer** les fichiers (FTP/SFTP ou rsync) :

```bash
rsync -avz --delete \
  --exclude '.git/' --exclude '.env.local' --exclude '.DS_Store' \
  --exclude '.claude/' --exclude '.agents/' \
  --exclude 'frontend/node_modules/' --exclude 'frontend/src/' --exclude 'frontend/scripts/' \
  --exclude 'frontend/public/uploads/*' --exclude 'frontend/public/documents/*' \
  --exclude 'flow-cms/storage/*.json' --exclude 'flow-cms/storage/logs/*' --exclude 'flow-cms/storage/cache/*' \
  ./ utilisateur@serveur:/var/www/monsite/
```

   (sur FTP : envoyer les mêmes dossiers à la main, en respectant les exclusions.)
   Pour la 1ʳᵉ mise en ligne, `install.php` doit partir aussi.

3. **Sur le serveur**, créer `.env.local` à la racine (depuis `.env.example`) :

```ini
DB_HOST=localhost
DB_NAME=monsite_prod
DB_USER=monsite
DB_PASS=********
PROD=true
APP_URL=https://www.monsite.fr
EMAIL_CONTACT=contact@monsite.fr
EMAIL_SENDER=no-reply@monsite.fr
APP_SECRET=<64 caractères hex aléatoires>
```

4. **Droits d'écriture** pour l'utilisateur PHP (ex. `www-data`) :

```bash
chmod -R u+rwX,g+rwX flow-cms/storage frontend/public/uploads frontend/public/documents
chown -R www-data:www-data flow-cms/storage frontend/public/uploads frontend/public/documents
```

   (en mutualisé : 755 sur les dossiers / 644 sur les fichiers suffit souvent.)

5. **Installer la base** : ouvrir `https://www.monsite.fr/install.php`
   → création des tables → formulaire « Compte administrateur » →
   **bouton « Supprimer install.php »**. Vérifier qu'il a bien disparu.
   (Par SSH : `php install.php email motdepasse "Nom"` puis `rm install.php`.)

6. **Tests de fumée** :
   - `https://www.monsite.fr/` → accueil ;
   - `https://www.monsite.fr/flow-cms/` → login ;
   - `https://www.monsite.fr/.env.local` → **404** (sinon STOP, voir §15) ;
   - `https://www.monsite.fr/flow-cms/config/app.php` → pas de contenu servi ;
   - upload d'une image dans Médias, envoi du formulaire de contact (vérifier la réception du mail).

7. Configurer les **tâches cron** (§13) et les **sauvegardes** (§12).

### 10.4 Configurations particulières

- **Le site doit être à la racine du domaine** (`www.monsite.fr/` et non
  `/flow/`) : c'est automatique si le contenu de `flow/` est à la racine web.
  Admin → `www.monsite.fr/flow-cms/`.
- **Renommer l'URL de l'admin** : renommer le dossier `flow-cms` impose de
  modifier les chemins en dur dans `frontend/public/index.php` (`$cmsRoot`),
  `.htaccess` racine (`^flow-cms`) et `install.php`. À faire avec prudence.
- **Sortir les uploads du dossier web** : `UPLOADS_PATH` / `DOCUMENTS_PATH` dans
  `.env.local` + symlink ou Alias Apache, et **recopier les `.htaccess`** de
  protection (voir `flow-cms/config/storage.php`).
- **Filtre géographique** : l'admin n'est accessible que depuis la France *si*
  l'hébergeur fournit le pays de l'IP ; sinon il laisse passer (`geo_fail_open`).
  Pour l'étendre : `flow-cms/config/security.php` → `geo_allowed_countries`.
- **Derrière un proxy/CDN** (Cloudflare…) : l'IP vue est celle du proxy
  (`REMOTE_ADDR`) — impacte anti-brute-force, maintenance par IP, anti-spam.

---

## 11. Mettre à jour un site en production

1. En local : faire la modification, tester (§4.4), `npm run build` si CSS/JS,
   `git commit`.
2. **Sauvegarder la prod** (base + uploads) — §12.
3. (Optionnel) activer le **mode maintenance** dans l'admin pendant l'opération.
4. Envoyer les fichiers modifiés (même `rsync` qu'au §10.3, **sans** `install.php`
   et en gardant les exclusions de données).
5. Si `composer.json` ou des classes ont été ajoutées : régénérer l'autoloader
   (soit en local avant l'envoi de `flow-cms/vendor/`, soit sur le serveur :
   `cd flow-cms && composer dump-autoload --optimize`).
6. Si des migrations ont été ajoutées :

```bash
php flow-cms/bin/flow migrate
```

   **Sans SSH** : réenvoyer temporairement `install.php`, l'ouvrir (il n'applique
   que les migrations manquantes, ne recrée rien, et refuse de créer un compte s'il
   en existe déjà), puis cliquer « Supprimer install.php ».
7. Si les tailles d'images ont changé : `php flow-cms/bin/flow media:regen`.
8. Vérifier le site, désactiver la maintenance.

**Revenir en arrière** : renvoyer la version précédente des fichiers
(`git checkout <commit-précédent>` en local puis rsync). Si une migration a été
appliquée, restaurer aussi la sauvegarde de la base, ou écrire une migration
inverse.

> **Un patch du noyau = un redéploiement par site.** Chaque client a sa propre
> installation. Tenez une liste des sites et de leur version (commit) déployée.

---

## 12. Sauvegarde & restauration

Trois choses à sauvegarder, **ensemble** :

| Quoi | Où |
|------|----|
| Base de données | MySQL |
| Fichiers uploadés | `frontend/public/uploads/`, `frontend/public/documents/` |
| État et secrets | `.env.local`, `flow-cms/storage/*.json` |

Le code, lui, est dans git.

```bash
# Sauvegarde
mysqldump --single-transaction -u USER -p DB_NAME | gzip > flow-$(date +%F).sql.gz
tar czf flow-files-$(date +%F).tar.gz frontend/public/uploads frontend/public/documents flow-cms/storage/*.json .env.local
```

```bash
# Restauration
gunzip < flow-2026-10-20.sql.gz | mysql -u USER -p DB_NAME
tar xzf flow-files-2026-10-20.tar.gz
```

Après restauration sur un **autre domaine** : adapter `APP_URL` dans `.env.local`.
Les images sont référencées par ID et le chemin est recalculé : rien d'autre à faire.

**Copier la prod en local** : même procédure, avec `APP_URL=http://localhost/flow`.
Attention, la base de prod contient des données personnelles (demandes de
contact, devis) : ne pas la faire circuler, la purger si besoin.

---

## 13. Tâches planifiées (cron)

Purges RGPD (rétention définie dans `config/contact.php` et `config/stats.php`) :

```cron
# Tous les jours à 3 h
0 3 * * *  cd /var/www/monsite && php flow-cms/bin/flow contact:purge >> flow-cms/storage/logs/cron.log 2>&1
15 3 * * * cd /var/www/monsite && php flow-cms/bin/flow stats:purge  >> flow-cms/storage/logs/cron.log 2>&1
```

Sans cron disponible : lancer ces commandes à la main une fois par mois.
La synchronisation des avis Google se déclenche depuis l'admin (bouton).

---

## 14. Dépannage

**Premier réflexe** : lire le log d'erreurs PHP / Apache de l'hébergeur. En
local, mettre `PROD=false` dans `.env.local` pour afficher les erreurs.

| Symptôme | Cause probable | Correctif |
|----------|---------------|-----------|
| Page blanche / erreur 500 partout | `flow-cms/vendor/` absent | `cd flow-cms && composer install` |
| 500 juste après avoir édité un `.htaccess` | Directive interdite (ex. `php_admin_flag`) ou module Apache absent | Annuler la modif ; lire le log Apache |
| « Connexion base de données impossible » | `.env.local` absent/mal rempli, mauvais emplacement (doit être à la **racine** `flow/`, pas dans `flow-cms/`) | Corriger `.env.local` |
| « Table … doesn't exist » | Migrations non appliquées | `php flow-cms/bin/flow migrate` |
| « Module introuvable : App\Modules\X\XModule » | Nom dans `config/modules.php` ≠ dossier/classe, ou autoloader périmé | Vérifier la casse ; `composer dump-autoload` |
| « Class … not found » pour une nouvelle classe | Namespace ≠ chemin du fichier | `App\Modules\Foo\Bar` ⇔ `app/Modules/Foo/Bar.php` |
| Toutes les URL du site en 404, sauf l'accueil | `mod_rewrite` inactif ou `AllowOverride None` | Activer `mod_rewrite`, `AllowOverride All` |
| Admin en boucle sur le login / « 419 Session expirée » | Cookies de session bloqués, `storage`/session non inscriptibles, onglet resté ouvert longtemps | Recharger la page ; vérifier `session.save_path` ; 2 h d'inactivité = déconnexion |
| « 403 Accès refusé — Permission requise : x » | Le rôle n'a pas la permission | `config/roles.php` |
| Admin inaccessible depuis l'étranger | Filtre géographique | `config/security.php` |
| Compte verrouillé | Anti-brute-force | Attendre 10 min ou `DELETE FROM login_attempts;` |
| Images cassées (admin ou site) | `APP_URL` incorrect | Corriger `APP_URL` (URL du **site public**, sans `/` final) |
| Upload refusé / échoue sans message | Limite PHP `upload_max_filesize` | `public/.htaccess` (mod_php) ou `public/.user.ini` (php-fpm) ; `php -S` : options `-d` |
| Upload : erreur d'écriture | Droits sur `frontend/public/uploads` | `chmod`/`chown` (§10.3) |
| Upload : image non traitée | Extension `gd` absente | Activer `gd` (avec support WebP) |
| CSS modifié mais pas visible | Fichier source modifié sans build, ou build non envoyé | `npm run build` puis déployer `public/assets/` |
| Script ou iframe externe ne s'affiche pas | CSP | §7.10 ; voir la console du navigateur |
| Le site affiche « maintenance » | Mode maintenance actif | Admin → Maintenance → désactiver (l'admin n'est jamais bloquée) ; ou `"enabled": false` dans `flow-cms/storage/maintenance.json` |
| Mails de contact jamais reçus | `mail()` non configuré, SPF/DKIM | Tester `php -r "var_dump(mail('vous@x.fr','t','t'));"` ; vérifier `EMAIL_SENDER` |
| Un bloc a disparu d'une page | Son module a été désactivé, ou `type()` renommé | Réactiver le module / restaurer le nom |
| Une page CMS n'apparaît pas | Statut brouillon, ou un fichier `static/<slug>.php` la masque, ou une redirection | Publier ; vérifier `static/` et `static_routes.php` |
| Modules désactivés reviennent / état perdu après déploiement | `storage/*.json` écrasé | Exclure `flow-cms/storage/*.json` du déploiement |

**Désactiver un module qui fait planter tout l'admin** : éditer
`flow-cms/storage/modules.json` (ex. `{"disabled": ["Stats"]}`)
ou le retirer de `config/modules.php`.

---

## 15. Sécurité : check-list

À vérifier à chaque mise en production :

- [ ] `https://site/.env.local` → 404. `https://site/.git/config` → 404.
- [ ] `install.php` supprimé du serveur.
- [ ] `PROD=true` dans `.env.local`.
- [ ] HTTPS actif (HSTS est envoyé automatiquement en HTTPS).
- [ ] `APP_SECRET` défini.
- [ ] Le dossier `.git/` n'a pas été déployé.
- [ ] Un fichier `.php` déposé dans `uploads/` n'est pas exécuté (les `.htaccess`
      de `uploads/` et `documents/` sont présents).
- [ ] Mots de passe admin forts, comptes inutiles supprimés, mode démo désactivé.
- [ ] Sauvegardes automatiques en place et **testées** (une restauration au moins une fois).

Ce que le code assure déjà : mots de passe Argon2id/bcrypt, session stricte
`HttpOnly`/`SameSite`, CSRF sur tous les POST, requêtes préparées, re-encodage
des images par GD, anti-brute-force avec alerte e-mail, alerte de connexion depuis
une nouvelle IP, CSP, anti-spam maison (pot de miel, délai, limitation de débit)
sans service tiers.

---

## 16. Git : workflow conseillé

- `main` = ce qui est en production.
- Une branche par modification : `git switch -c fix/menu-mobile`.
- Messages de commit au format utilisé dans l'historique :
  `type(portée): description` — ex. `feat(admin): …`, `fix(security): …`, `docs: …`.
- Avant de commiter : `php -l` sur les fichiers touchés (§9), `npm run build` si
  CSS/JS, test manuel.
- Jamais de `.env.local`, d'uploads ou de dump SQL dans git (le `.gitignore` les
  exclut déjà ; vérifier `git status` avant chaque commit).
- Taguer chaque version déployée : `git tag deploy-monsite-2026-10-20`.

---

## 17. Pièges connus

- **`flow-cms/storage/maintenance.json` et `devis_form.json` sont suivis par git**
  (contrairement aux autres `storage/*.json`). Un déploiement par `git pull` ou un
  rsync sans exclusion **écrase l'état de prod** (jeton de bypass, IP autorisées,
  formulaire de devis personnalisé). Les exclure du déploiement — ou mieux, les
  retirer de git (`git rm --cached`) et les ajouter au `flow-cms/.gitignore`.
- **`flow-cms/vendor/` n'est pas dans git** : un déploiement depuis un clone
  « nu » sans `composer install` donne une erreur 500.
- **Les assets compilés sont dans git** : modifier `src/` sans lancer `npm run build`
  ne change rien en ligne.
- **Un fichier statique masque une page CMS** de même slug, et une redirection
  masque les deux.
- **Routes `/{slug}`** : un module qui ajoute une route publique à un seul segment
  (`/partenaires`) doit l'enregistrer en route **exacte** ; une route à paramètre
  enregistrée après celle de Pages ne serait jamais atteinte. Voir §2.4.
- **Le `.htaccess` racine bloque tous les `.md`, `.sql`, `.log`, `.ini`, `.yml`** :
  ne pas servir de fichier légitime avec ces extensions.
- **JavaScript inline interdit** (CSP) : les valeurs à passer au JS passent par
  des attributs `data-*` (cf. `data-aos-duration` dans `footer.php`).
- **Ne jamais modifier une migration déjà appliquée** ; ne jamais renommer le
  `type()` d'un bloc.
- **L'IP vue est `REMOTE_ADDR`** : derrière un proxy, toutes les fonctions basées
  sur l'IP voient la même adresse.
- **`base_path` auto-détecté** : si les liens générés ont un mauvais préfixe
  (configuration Apache exotique), le forcer dans `flow-cms/config/app.php`.

---

## 18. Aide-mémoire des commandes

```bash
# --- Installation ---
cd flow-cms && composer install && cd ..
cp .env.example .env.local
php install.php admin@exemple.fr 'MotDePasse' "Nom"

# --- CLI Flow (depuis la racine flow/) ---
php flow-cms/bin/flow help
php flow-cms/bin/flow migrate
php flow-cms/bin/flow user:create <email> <motdepasse> [admin|manager|editor] [nom]
php flow-cms/bin/flow media:regen
php flow-cms/bin/flow contact:purge
php flow-cms/bin/flow stats:purge

# --- Assets du site ---
cd frontend && npm install
npm run watch
npm run build

# --- Autoloader après ajout de classes / helpers ---
cd flow-cms && composer dump-autoload --optimize

# --- Vérification syntaxe PHP ---
find flow-cms/app frontend -name '*.php' -not -path '*/node_modules/*' -print0 | xargs -0 -n1 php -l | grep -v "No syntax errors"

# --- Serveurs de dev sans Apache ---
php -S localhost:8000 -t flow-cms/public
php -S localhost:8001 -t frontend/public

# --- Sauvegarde ---
mysqldump --single-transaction -u USER -p DB_NAME | gzip > flow-$(date +%F).sql.gz
tar czf flow-files-$(date +%F).tar.gz frontend/public/uploads frontend/public/documents flow-cms/storage/*.json .env.local

# --- Trouver où une URL est gérée ---
grep -rn "'/admin/pages" flow-cms/app
# --- Trouver toutes les permissions ---
grep -rhoE "(requirePermission|can)\('[a-z_.]+'\)" flow-cms/app | sort -u
```
