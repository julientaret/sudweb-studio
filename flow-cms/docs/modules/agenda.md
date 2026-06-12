# Module Agenda

## Rôle

Gère des **événements datés** : titre, date de début, date de fin (optionnelle),
image vignette et un contenu composé avec le **page-builder par blocs**. Chaque
événement publié a une URL front `/agenda/{slug}`. Le contenu est stocké en JSON
ordonné et rendu côté serveur avec échappement systématique.

## Données

Migration [`001_create_events.sql`](../../app/Modules/Agenda/migrations/001_create_events.sql).

- **`events`** : `id`, `title`, `slug` (unique), `start_date` (DATE),
  `end_date` (DATE, nullable), `thumbnail_id` (ID média, nullable),
  `content` (LONGTEXT JSON), `status` (`draft`|`published`),
  `created_at`, `updated_at`.
- `content` suit **exactement le format des pages** (lignes → colonnes → blocs) ;
  il est construit, normalisé et rendu par
  [`App\Core\BlockContent`](../../app/Core/BlockContent.php) — aucune logique de
  blocs dupliquée dans le module (base commune « contenu », brief §3).
- `thumbnail_id` et les images de contenu sont référencés par **ID média**, jamais
  par URL (brief §4/§5).

## Routes

Déclarées dans `AgendaModule::boot()`.

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/agenda`         | `index`   | `content.edit` |
| GET  | `/admin/agenda/create`  | `create`  | `content.edit` |
| POST | `/admin/agenda/create`  | `store`   | `content.edit` + CSRF |
| GET  | `/admin/agenda/edit`    | `edit`    | `content.edit` |
| POST | `/admin/agenda/edit`    | `update`  | `content.edit` + CSRF |
| POST | `/admin/agenda/delete`  | `destroy` | `content.edit` + CSRF |
| GET  | `/agenda/{slug}`        | `show`    | public (événements publiés) |

> `/agenda/{slug}` a **deux segments** : il ne peut pas entrer en conflit avec le
> catch-all front `/{slug}` des Pages, qui ne matche qu'un seul segment.

## Blocs fournis

Aucun. Le module **réutilise les blocs enregistrés par Pages** (`heading`,
`richtext`, `image`) via le `BlockRegistry` partagé. Les libellés affichés dans
l'éditeur proviennent de `PagesModule::BLOCK_LABELS`.

## Permissions

| Permission | Détenue par |
|------------|-------------|
| `content.edit`    | `admin` (`*`), `manager` (`content.*`), `editor` |
| `content.publish` | `admin` (`*`), `manager` (`content.*`) |

Espace de permission **`content`** partagé avec Pages (et plus tard Blog/News),
conforme à la « base commune contenu » (brief §3) et au mapping
[`config/roles.php`](../../config/roles.php). Un `editor` édite et enregistre en
**brouillon** ; la **publication** exige `content.publish` (sinon le statut
retombe en brouillon côté serveur).

## Éditeur

L'éditeur ([`views/editor.php`](../../app/Modules/Agenda/views/editor.php))
réutilise tel quel le moteur du page-builder :
[`pages-editor.js`](../../public/assets/js/pages-editor.js) et la feuille de styles
partagée [`block-editor.css`](../../public/assets/css/block-editor.css). Il reprend
donc les mêmes identifiants (`#page-form`, `#rows`, `#layout`, `#add-row`,
gabarits `tpl-*`, modale `#media-modal`) que l'éditeur de pages.

- **Dates** : deux champs `<input type="date">` (début requis, fin optionnelle).
  La date de fin ne peut pas précéder le début (validé côté serveur).
- **Vignette** : champ image (aperçu + bouton) qui ouvre la **modale de vignettes
  partagée**, comme le bloc `image`. Le champ vit hors de `#rows` : la délégation
  de clic des champs image dans `pages-editor.js` est posée au niveau `document`
  pour les couvrir aussi.

## Rendu front

[`views/front_layout.php`](../../app/Modules/Agenda/views/front_layout.php) :
en-tête avec le titre, les dates (« Le … » ou « Du … au … »), la vignette en hero
(taille `large`) puis le contenu rendu par `BlockContent::render()`.

## Intégrations transverses

- **Tableau de bord** : les événements remontent dans « Dernières modifications »
  ([DashboardController::recentContent](../../app/Core/DashboardController.php)),
  fusionnés avec les pages et triés par date de modification. Chaque ligne porte
  une étiquette de type (« Page » / « Événement ») et son URL d'édition propre.
- **Tutoriel** : rubrique « L'agenda » dans
  [`tutorial.php`](../../app/Core/views/tutorial.php) (réglages, dates, vignette ;
  le contenu renvoie à la rubrique « Les pages »).

## Config

- [`config/modules.php`](../../config/modules.php) : `Agenda` dans `available`
  (**après `Pages` et `Media`**, dont il dépend).
- [`config/app.php`](../../config/app.php) : `site_name` (titre front).

## Notes / pièges

- **Dépendances inter-modules** : Agenda dépend de **Pages** (blocs + éditeur JS +
  `BlockContent`) et de **Media** (vignette + bloc image). Les trois doivent être
  activés.
- **Dates** : stockées en `DATE` (`Y-m-d`), sans heure. Si des horaires deviennent
  nécessaires, migrer vers `DATETIME` + `datetime-local`.
- **Dashboard agnostique** : `recentContent()` ne dépend d'aucun module en dur ;
  il n'agrège un module de contenu que s'il est listé dans `modules.enabled`. Un
  nouveau module de contenu (Blog/News) s'y ajoutera de la même façon.
- **À venir** : liste/agenda front (`/agenda`), tri par à-venir/passés, bloc
  dynamique « prochains événements » (brief §4 : un bloc dont `render()` requête la
  base en live).
