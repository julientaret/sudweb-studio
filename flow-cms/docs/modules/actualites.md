# Module Actualités

## Rôle

Gère des **articles d'actualité datés** : titre, date de publication, image
vignette et un contenu composé avec le **page-builder par blocs**. Chaque
actualité publiée a une URL front `/actualites/{slug}`. Le contenu est stocké en
JSON ordonné et rendu côté serveur avec échappement systématique. Module calqué
sur [Agenda](agenda.md) (même architecture, une seule date au lieu de début/fin).

## Données

Migration [`001_create_articles.sql`](../../app/Modules/Actualites/migrations/001_create_articles.sql).

- **`articles`** : `id`, `title`, `slug` (unique), `published_date` (DATE),
  `thumbnail_id` (ID média, nullable), `content` (LONGTEXT JSON),
  `status` (`draft`|`published`), `created_at`, `updated_at`.
- `content` suit **exactement le format des pages** (lignes → colonnes → blocs) ;
  il est construit, normalisé et rendu par
  [`App\Core\BlockContent`](../../app/Core/BlockContent.php) — aucune logique de
  blocs dupliquée dans le module (base commune « contenu », brief §3).
- `thumbnail_id` et les images de contenu sont référencés par **ID média**, jamais
  par URL (brief §4/§5).

## Routes

Déclarées dans `ActualitesModule::boot()`.

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/actualites`         | `index`   | `content.edit` |
| GET  | `/admin/actualites/create`  | `create`  | `content.edit` |
| POST | `/admin/actualites/create`  | `store`   | `content.edit` + CSRF |
| GET  | `/admin/actualites/edit`    | `edit`    | `content.edit` |
| POST | `/admin/actualites/edit`    | `update`  | `content.edit` + CSRF |
| POST | `/admin/actualites/delete`  | `destroy` | `content.edit` + CSRF |
| GET  | `/actualites/{slug}`        | `show`    | public (actualités publiées) |

> `/actualites/{slug}` a **deux segments** : il ne peut pas entrer en conflit avec
> le catch-all front `/{slug}` des Pages, qui ne matche qu'un seul segment.

## Blocs fournis

Aucun. Le module **consomme le page-builder partagé** : blocs de base `heading` et
`richtext` fournis par le Core, bloc `image` fourni par Media (présent seulement si
Media est actif), via le `BlockRegistry` partagé. Chaque bloc porte son libellé
(`Block::label()`) : l'éditeur construit son catalogue depuis le registre, sans
table de libellés ni référence à un autre module.

## Permissions

| Permission | Détenue par |
|------------|-------------|
| `content.edit`    | `admin` (`*`), `manager` (`content.*`), `editor` |
| `content.publish` | `admin` (`*`), `manager` (`content.*`) |

Espace de permission **`content`** partagé avec Pages et Agenda, conforme à la
« base commune contenu » (brief §3) et au mapping
[`config/roles.php`](../../config/roles.php). Un `editor` édite et enregistre en
**brouillon** ; la **publication** exige `content.publish` (sinon le statut
retombe en brouillon côté serveur).

## Éditeur

L'éditeur ([`views/editor.php`](../../app/Modules/Actualites/views/editor.php))
réutilise tel quel le moteur du page-builder :
[`pages-editor.js`](../../public/assets/js/pages-editor.js) et la feuille de styles
partagée [`block-editor.css`](../../public/assets/css/block-editor.css). Il reprend
donc les mêmes identifiants (`#page-form`, `#rows`, `#layout`, `#add-row`,
gabarits `tpl-*`, modale `#media-modal`) que l'éditeur de pages.

- **Date de publication** : un champ `<input type="date">` requis, pré-rempli à
  la date du jour à la création. Validé côté serveur (`Y-m-d`).
- **Vignette** : champ image (aperçu + bouton) qui ouvre la **modale de vignettes
  partagée**, comme le bloc `image`. Le champ vit hors de `#rows` : la délégation
  de clic des champs image dans `pages-editor.js` est posée au niveau `document`
  pour les couvrir aussi.

## Rendu front

[`views/front_layout.php`](../../app/Modules/Actualites/views/front_layout.php) :
en-tête avec le titre, la date (« Publié le … »), la vignette en hero
(`<picture>` srcset WebP responsive `mobile`/`tablet`/`desktop`, `sizes="100vw"`,
repli original) puis le contenu rendu par `BlockContent::render()`.

## Intégrations transverses

- **Tableau de bord** : les actualités remontent dans « Dernières modifications »
  ([DashboardController::recentContent](../../app/Core/DashboardController.php)),
  fusionnées avec les pages et événements, triées par date de modification.
  Chaque ligne porte une étiquette de type (« Actualité ») et son URL d'édition.
- **Tutoriel** : rubrique « Les actualités » dans
  [`tutorial.php`](../../app/Core/views/tutorial.php) (réglages, date, vignette ;
  le contenu renvoie à la rubrique « Les pages »).

## Config

- [`config/modules.php`](../../config/modules.php) : `Actualites` dans `available`.
- [`config/app.php`](../../config/app.php) : `site_name` (titre front).

## Notes / pièges

- **Module indépendant** : Actualités ne dépend d'aucun autre module. Le contenu
  s'appuie sur le page-builder partagé (Core). La **vignette** et le bloc `image`
  sont des options fournies par Media : si Media est désactivé, le choix de vignette
  et le bloc image disparaissent, mais le module reste pleinement utilisable.
- **Date** : stockée en `DATE` (`Y-m-d`), sans heure. Elle est purement
  éditoriale : une actualité publiée avec une date future est tout de même
  visible (pas de publication différée pour l'instant).
- **À venir** : liste front (`/actualites`), pagination, bloc dynamique
  « dernières actualités » (brief §4 : un bloc dont `render()` requête la base en
  live).
