# Module Pages

## Rôle

Page-builder par blocs (brief §4). Le client compose ses pages avec des blocs
prédéfinis et contrôlés ; chaque page a une URL front `/slug`. Le contenu est
stocké en JSON ordonné, rendu côté serveur avec échappement systématique.

## Données

Migration [`001_create_pages.sql`](../../app/Modules/Pages/migrations/001_create_pages.sql).

- **`pages`** : `id`, `title`, `slug` (unique), `content` (LONGTEXT JSON),
  `status` (`draft`|`published`), `created_at`, `updated_at`.
- `content` = tableau ordonné de **lignes** :
  `{ id, cols (1-4), columns: [ [ {id,type,data}, … ], … ] }`.
  Chaque ligne a 1 à 4 colonnes ; chaque colonne contient des blocs ordonnés.
  Les images sont référencées par **ID média**, jamais par URL (brief §4/§5).
- Le rendu **tolère l'ancien format plat** (liste de blocs) en l'enveloppant en
  lignes d'une seule colonne (`normalizeRows`).

## Routes

Déclarées dans `PagesModule::boot()`.

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/pages`        | `index`   | `content.edit` |
| GET  | `/admin/pages/create` | `create`  | `content.edit` |
| POST | `/admin/pages/create` | `store`   | `content.edit` + CSRF |
| GET  | `/admin/pages/edit`   | `edit`    | `content.edit` |
| POST | `/admin/pages/edit`   | `update`  | `content.edit` + CSRF |
| POST | `/admin/pages/delete` | `destroy` | `content.edit` + CSRF |
| GET  | `/{slug}`             | `show`    | public (pages publiées) |

> `/{slug}` est une **route à segment dynamique** (ajoutée au routeur pour Pages).
> Les routes exactes (admin) restent prioritaires sur ce catch-all front.

## Blocs fournis

Enregistrés dans `boot()` via `Kernel::blocks()`. Forms d'édition : les champs
utilisent le jeton `__KEY__`, substitué par l'éditeur (clé de bloc) — ce qui
permet l'ajout dynamique et le réordonnancement.

| Type | Statique/dynamique | `data` |
|------|--------------------|--------|
| `heading`  | statique  | `text`, `level` (1\|2\|3) |
| `richtext` | statique  | `delta` (Delta Quill, voir §8) |
| `image`    | dynamique | `media_id`, `alt` (rend `<picture>` WebP + repli original) |

## Permissions

| Permission | Détenue par |
|------------|-------------|
| `content.edit`    | `admin` (`*`), `manager` (`content.*`), `editor` |
| `content.publish` | `admin` (`*`), `manager` (`content.*`) |

Espace de permission **`content`** partagé par les modules de contenu (Pages, et
plus tard Blog/News) — cohérent avec la « base commune contenu » (brief §3) et le
mapping [`config/roles.php`](../../config/roles.php). Un `editor` édite et
enregistre en **brouillon** ; la **publication** exige `content.publish` (sinon le
statut retombe en brouillon côté serveur).

## Éditeur & richtext (brief §8)

- **Quill** vendorisé ([`public/assets/vendor/quill/`](../../public/assets/vendor/quill/)),
  bridé à la prose : gras, italique, liens, titres h2/h3, listes.
- **On stocke le Delta (JSON), pas du HTML.** Le HTML front est produit par
  [`DeltaRenderer`](../../app/Modules/Pages/DeltaRenderer.php), qui n'émet que les
  balises autorisées et échappe tout le texte → **pas de HTML Purifier, zéro
  dépendance préservé**.
- L'éditeur ([`pages-editor.js`](../../public/assets/js/pages-editor.js)) gère
  ajout/suppression, **drag & drop** (poignée), init Quill, et sérialise l'ordre
  (`block_order`) + les Delta à la sauvegarde. JS externe (CSP `default-src 'self'`).

## Mise en page (lignes & colonnes)

Chaque ligne se règle de **1 à 4 colonnes** via le sélecteur de l'éditeur. Les
blocs vivent dans les colonnes et se **glissent-déposent entre colonnes** ; les
lignes se réordonnent par leur poignée. Réduire le nombre de colonnes ne perd
aucun contenu : les blocs des colonnes retirées sont déplacés dans la dernière
colonne conservée. Front : `display:grid` responsive (`.flow-row.cols-N`),
empilé en une colonne sous 640 px.

## Flux de sauvegarde

Champ caché `layout` (JSON : lignes → colonnes → clés de blocs) +
`blocks[clé][type|data]` → le serveur parcourt les lignes/colonnes dans l'ordre,
passe chaque bloc par `Block::sanitize()`, génère les `id` et reconstruit le JSON
(brief §4). Au rendu : `normalizeRows()` → boucle lignes/colonnes →
`BlockRegistry::render(type, data)`.

## Config

- [`config/app.php`](../../config/app.php) : `site_name` (titre front).
- [`config/modules.php`](../../config/modules.php) : `Pages` dans `available`.

## Notes / pièges

- **Dépendance inter-modules** : le bloc `image` lit la bibliothèque Media
  (référence par ID). Media doit être activé pour l'utiliser pleinement.
- **Sélection d'image** : le bloc `image` n'a pas de `<select>` mais un **aperçu +
  bouton** qui ouvre une **modale de vignettes** partagée (un seul `#media-modal`
  pour tout l'éditeur, alimenté côté serveur par `MediaRepository::all()`). Le clic
  sur une vignette réinjecte l'ID et l'aperçu via `createElement`/`textContent`
  (jamais d'`innerHTML` → aucune évasion d'attribut possible).
- **Page d'accueil** : `/` redirige encore vers l'admin (route par défaut du
  Kernel). Désigner une page comme accueil du site est une évolution à venir.
- **À venir** : versions/brouillons, désignation d'une page d'accueil, et passage
  des routes admin à `/{id}` quand utile.
