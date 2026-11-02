# Flow

CMS modulaire en **PHP vanilla** (zéro dépendance runtime, Composer seulement pour
l'autoload PSR-4), édité par **Sud Web Studio**. Le projet réunit deux applications
qui partagent un même noyau et une même base de données :

| Partie | Dossier | Rôle | URL (dev) |
|--------|---------|------|-----------|
| **Site public** | `frontend/` | Le site visité par le public | `http://localhost/sud-web-studio/` |
| **Admin (CMS)** | `flow-cms/` | Le back-office de gestion | `http://localhost/sud-web-studio/flow-cms/` |

> **Intervenir seul·e sur le projet (architecture, recettes, déploiement,
> dépannage)** : voir le [`MANUEL.md`](MANUEL.md).
>
> Pour les choix d'architecture du CMS et la doc par module, voir
> [`flow-cms/flow-brief.md`](flow-cms/flow-brief.md) (document fondateur) et
> [`flow-cms/docs/`](flow-cms/docs/).

---

## 1. Arborescence

```
sud-web-studio/
├── .env.local            # secrets du serveur (DB, APP_URL…) — JAMAIS versionné
├── .env.example          # modèle à copier en .env.local
├── .htaccess             # sert le site public à la racine du projet
│
├── frontend/             # SITE PUBLIC
│   ├── public/           # seul dossier exposé : front controller + assets
│   │   ├── index.php      #   point d'entrée (démarre le noyau partagé)
│   │   ├── assets/        #   CSS du site
│   │   ├── uploads/       #   médias (images) — déplacés depuis le CMS
│   │   └── documents/     #   documents (PDF…) — déplacés depuis le CMS
│   ├── config/
│   │   ├── site.php       #   paramètres du site (nom, nav, footer, social…)
│   │   └── static_routes.php  # alias d'URL + redirections
│   ├── static/           # pages statiques écrites à la main (1 fichier = 1 page)
│   │   ├── _template.php  #   gabarit à copier pour une nouvelle page
│   │   └── index.php      #   page d'accueil
│   ├── includes/         # header.php, footer.php, helpers.php
│   └── layouts/          # front_layout.php (gabarit des pages dynamiques du CMS)
│
└── flow-cms/             # ADMIN (CMS)
    ├── public/index.php  # front controller de l'admin
    ├── app/Core/         # Kernel, Router, DB, View, Auth, Csrf, Migrator…
    ├── app/Modules/      # modules (Pages, Media, Documents, Users…)
    ├── config/           # app.php, storage.php, modules.php…
    ├── bin/flow          # CLI (migrations, comptes, régénération médias)
    └── vendor/           # autoload Composer
```

Le **noyau** (`flow-cms/app/`) est l'unique source de vérité : routage, rendu des
blocs, accès DB, médias. Le site public ne fait que démarrer ce noyau en
**mode front-only** et l'habiller de son propre thème.

---

## 2. Comment marche le site public

Le front controller [`frontend/public/index.php`](frontend/public/index.php)
résout chaque requête dans cet ordre :

1. **Redirection** déclarée dans `frontend/config/static_routes.php` (301/302) ;
2. **Page statique** — un fichier `frontend/static/<slug>.php` (ou un alias
   déclaré dans `static_routes.php`) ;
3. **Page dynamique du CMS** — résolue par *slug* via le noyau (pages composées
   par blocs dans l'admin).

Un fichier statique l'emporte donc toujours sur une page CMS de même URL.

### Créer une page statique

```bash
cp frontend/static/_template.php frontend/static/contact.php
# → servie sur /contact
```

On renseigne `$title` / `$description`, on écrit le contenu entre les partials
`header` et `footer` (qui fournissent `<head>`, CSS, en-tête et pied de page
communs). Le préfixe `_` rend `_template.php` non servable.

### Pages dynamiques

Les pages créées dans l'admin (Pages → éditeur de blocs) sont servies
automatiquement par leur slug. Elles utilisent le même squelette HTML que les
pages statiques, via [`frontend/layouts/front_layout.php`](frontend/layouts/front_layout.php).

---

## 3. Configuration

### 3.1 Paramètres du site — `frontend/config/site.php`

Source unique des valeurs de gabarit (header/footer/pages statiques), lue via
l'accesseur `site()` :

```php
return [
    'name'   => 'Mon Site',
    'lang'   => 'fr',
    'nav'    => ['Accueil' => '/', 'Contact' => '/contact'],
    'footer' => ['copyright' => 'Mon Site SARL'],
    'social' => ['Instagram' => 'https://instagram.com/monsite'],
];
```

### 3.2 Routes & redirections — `frontend/config/static_routes.php`

```php
return [
    'routes' => [                       // alias : URL ≠ nom de fichier
        'a-propos' => 'about',          //   /a-propos → static/about.php
    ],
    'redirects' => [
        'home'          => '/',                                   // 301
        'ancienne-page' => ['to' => '/nouvelle-page', 'status' => 301],
        'partenaire'    => ['to' => 'https://exemple.com', 'status' => 302],
    ],
];
```

### 3.3 Environnement — `.env.local` (à la racine)

Secrets propres au serveur, **partagés par l'admin et le site public**, jamais
versionnés. Copier le modèle puis renseigner :

```bash
cp .env.example .env.local
```

| Variable | Rôle |
|----------|------|
| `DB_HOST` `DB_NAME` `DB_USER` `DB_PASS` | Connexion MySQL/MariaDB |
| `APP_URL` | **URL absolue du site public** (sans slash final). Base des URLs médias/documents et des liens absolus. |
| `EMAIL_CONTACT` `EMAIL_SENDER` | Adresses e-mail |
| `GOOGLE_PLACES_API_KEY` | Module Avis Google (optionnel) |
| `UPLOADS_PATH/URL`, `DOCUMENTS_PATH/URL` | Déplacer le stockage en prod (optionnel, voir [`config/storage.php`](flow-cms/config/storage.php)) |

`APP_URL` est la clé de portabilité : c'est **la seule valeur à changer** pour
renommer le projet ou déployer en prod.

```ini
# dev
APP_URL=http://localhost/sud-web-studio
# prod
APP_URL=https://www.monsite.com
```

→ les médias deviennent `https://www.monsite.com/uploads/...`, **identiques côté
admin et côté site public**.

---

## 4. Médias & documents

Les fichiers uploadés (images **et** documents) vivent dans le site public, sous
`frontend/public/uploads` et `frontend/public/documents`, et sont servis à la
**même URL** depuis l'admin et le front — pour qu'un média de page ne casse jamais.

L'URL est résolue en trois paliers (voir
[`MediaStorage`](flow-cms/app/Modules/Media/MediaStorage.php) /
[`DocumentStorage`](flow-cms/app/Modules/Documents/DocumentStorage.php)) :

1. `UPLOADS_URL` / `DOCUMENTS_URL` — override explicite (CDN, autre domaine) ;
2. `APP_URL` + `/uploads` — **URL absolue, indépendante du nom de dossier** (recommandé) ;
3. chemin auto-détecté `{base}/uploads` — fallback zéro-config.

> Sécurité : l'exécution de scripts est coupée dans `uploads/` et `documents/`
> via leur `.htaccess`. Les images sont re-encodées par GD à l'upload.

---

## 5. Mise en route (dev)

Prérequis : **PHP ≥ 8.2** (extensions `pdo_mysql`, `gd`, `finfo`), MySQL/MariaDB,
Composer.

```bash
# 1. Dépendances (autoload uniquement) — dans flow-cms/
cd flow-cms && composer install && cd ..

# 2. Configuration
cp .env.example .env.local           # renseigner DB_*, APP_URL…
#    créer la base déclarée dans DB_NAME

# 3. Schéma + premier compte admin
php flow-cms/bin/flow migrate
php flow-cms/bin/flow user:create admin@flow.test motdepasse8 admin "Admin"
```

Servir le projet à la racine `sud-web-studio/` (pour que `http://localhost/sud-web-studio/`
fonctionne et corresponde à `APP_URL`) :

- **Apache / MAMP** : `DocumentRoot` sur le dossier **parent** de `sud-web-studio/`
  (les `.htaccess` font le reste). Site public → `http://localhost/sud-web-studio/`,
  admin → `http://localhost/sud-web-studio/flow-cms/`.
- **Vérif rapide d'une seule app** avec le serveur PHP intégré :
  `php -S localhost:8000 -t frontend/public` (mais sans le préfixe `/flow` ;
  pensez à aligner `APP_URL=http://localhost:8000`).

### 5.1 Build des assets du frontend

Les CSS/JS/images du site public sont compilés par une petite chaîne
(esbuild + Lightning CSS + sharp). Node n'est requis qu'en **dev** : les sorties
de `public/assets` sont versionnées (le serveur de prod n'a pas Node).

```bash
cd frontend
npm install
npm run build     # src/ → public/assets/  (CSS bundle+minify, JS, images→WebP)
npm run watch     # rebuild à chaque modification de src/
```

| Source | Sortie | Outil |
|--------|--------|-------|
| `src/css/site.css` (+ `@import` de partials) | `public/assets/site.css` | Lightning CSS (bundle, autoprefix, minify) |
| `src/js/site.js` (+ GSAP, bundlé depuis `node_modules`) | `public/assets/site.js` | esbuild (bundle, minify) |
| `src/img/**/*.{png,jpg}` | `public/assets/img/*.webp` | sharp |

Les partials CSS suivent le design system (`design/Sud Web Studio - Design
System.html`) : `tokens.css` (couleurs, mesures, courbes) et `fonts.css`
(Spectral + Spline Sans Mono **auto-hébergées** dans `public/assets/fonts/` —
la CSP du noyau est en `default-src 'self'`, un lien Google Fonts serait bloqué)
d'abord, puis `base.css`, `header.css`, `footer.css`, `hero.css`, `home.css`,
`pages.css`, et enfin les feuilles des modules du CMS rendus sur le site public.

Le JS se découpe de même : `nav.js` (barre fixe + menu mobile, sans dépendance)
et `motion.js` (GSAP + ScrollTrigger). Le site doit rester entièrement lisible
sans la couche motion : les états masqués ne s'appliquent que sous `html.js`
(drapeau posé par `public/assets/js-flag.js`), et `prefers-reduced-motion`
désactive toutes les animations.

> Le CSS s'organise en partials (`base.css`, `header.css`, `footer.css`…) importés
> par `site.css` ; ils sont fusionnés en un seul fichier servi. La conversion WebP
> ici ne concerne que les **images de design** committées ; les médias uploadés
> sont déjà convertis par le CMS (module Media).

#### Favicon et carte de partage

Un second build, séparé parce qu'il a besoin de **Chrome** (un build d'assets
ordinaire ne doit pas échouer sur une machine qui ne l'a pas) :

```bash
cd frontend
npm run build:brand     # favicons + image d'aperçu Open Graph
```

| Source | Sortie | Outil |
|--------|--------|-------|
| `src/brand/icon.svg` | `public/favicon.ico` (16/32/48), `public/assets/brand/icon.svg`, `apple-touch-icon.png` | sharp |
| `src/brand/og-default.html` | `public/assets/brand/og-default.png` (1200 × 630) | Chrome sans interface + sharp |

- **Favicon** : le monogramme complet du site (les trois lettres S-W-S, les
  diagonales centrales étant le W) sur carré arrondi sombre, pour s'afficher
  aussi bien sur un onglet clair que sombre. À 16 px — `favicon.ico` sur écran
  non retina — les diagonales se mélangent en un aplat : c'est la limite du
  dessin, assumée. Dès 32 px, soit un onglet retina ou un favori, les trois
  lettres se détachent ; d'où le peu de marge autour du tracé.
- **Carte de partage** : ce qu'affichent Messenger, WhatsApp, LinkedIn, Slack ou
  X quand on colle un lien. Elle récapitule prestations et contact — **pas de
  tarifs** : une image est mise en cache par les réseaux sociaux et survivrait
  des mois à un changement de prix. **On la modifie dans le HTML**, jamais sur
  l'image : `og-default.html` s'ouvre tel quel dans un navigateur, et le script y
  injecte les polices et le logo en base64 avant la capture (Chrome refuse les
  requêtes `file://` croisées). Les valeurs y doublent celles des pages : si
  l'adresse de contact ou le domaine changent, les reporter et relancer le build.
- Cherche Chrome aux emplacements habituels de macOS et Linux, ou dans
  `CHROME_PATH`. Sans Chrome, les favicons sont produits et l'image est laissée
  telle quelle.

Les balises sont posées une fois pour tout le site par `includes/header.php`
(`og:*`, `twitter:card`, `rel="icon"`). Les réglages par défaut vivent dans
`config/site.php`, clé `og` ; une page peut poser `$ogImage` (et `$ogType`) avant
d'inclure l'en-tête pour partager son propre visuel.

---

## 6. Modules

Activables par site dans [`flow-cms/config/modules.php`](flow-cms/config/modules.php),
puis désactivables depuis l'admin (onglet Administration). Une fiche par module
dans [`flow-cms/docs/modules/`](flow-cms/docs/modules/).

| Module | Rôle |
|--------|------|
| **Users** | Comptes, rôles, permissions, anti-brute-force (socle, toujours actif) |
| **Pages** | Page-builder par blocs (Titre, Texte, Image…) |
| **Media** | Bibliothèque d'images : upload, re-encodage GD, déclinaisons WebP |
| **Documents** | Bibliothèque de fichiers (PDF, bureautique) |
| **Agenda** | Événements |
| **Actualités** | Articles / news |
| **GoogleReviews** | Affichage des avis Google Places |
| **Contact** | Formulaire public (RGPD), demandes en base + admin, notification e-mail |
| **Pages légales** | Pages juridiques (mentions légales, confidentialité…) éditées au texte riche |

---

## 7. Ligne de commande

```bash
php flow-cms/bin/flow migrate                                  # applique les migrations
php flow-cms/bin/flow user:create <email> <pass> [role] [nom]  # crée un compte
php flow-cms/bin/flow media:regen                              # régénère les déclinaisons d'images
php flow-cms/bin/flow contact:purge                            # purge RGPD des demandes de contact expirées
php flow-cms/bin/flow help
```

---

## 8. Routage & base d'URL

Le `base_path` (préfixe d'URL) est **auto-détecté** au démarrage depuis
`SCRIPT_NAME`, et fonctionne aussi bien à la racine d'un vhost qu'en sous-dossier :

| Déploiement | Site public | Admin |
|-------------|-------------|-------|
| Sous-dossier `/sud-web-studio` | `/sud-web-studio/` | `/sud-web-studio/flow-cms/` |
| Racine de vhost | `/` | `/flow-cms/` |

Pour forcer une valeur, renseigner `base_path` dans
[`flow-cms/config/app.php`](flow-cms/config/app.php).
