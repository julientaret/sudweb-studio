# Module Réalisations

## Rôle

Gère une **collection de réalisations** : chaque élément a un **titre**, une
**description en texte riche** (éditeur Quill) et une **photo** issue de la
bibliothèque Média. Le module n'expose **aucune URL front** : les réalisations
publiées s'affichent via le **bloc dynamique `realisations`**, insérable dans
n'importe quelle page par le page-builder (même pattern que [Avis Google](google-reviews.md)).
Calqué sur la base commune « contenu » (brief §3/§4).

## Données

Migration [`001_create_realisations.sql`](../../app/Modules/Realisations/migrations/001_create_realisations.sql).

- **`realisations`** : `id`, `title`, `description` (LONGTEXT — **Delta Quill JSON**,
  jamais de HTML stocké), `media_id` (ID média, nullable), `position` (SMALLINT,
  ordre croissant), `status` (`draft`|`published`), `created_at`, `updated_at`.
- `description` est rendue côté serveur par [`App\Core\DeltaRenderer`](../../app/Core/DeltaRenderer.php)
  (prose bornée, échappement systématique) ; elle réutilise le **sanitizer du bloc
  Texte du Core** ([`RichtextBlock`](../../app/Core/Blocks/RichtextBlock.php)) à
  l'enregistrement — aucune logique de texte riche dupliquée.
- `media_id` est référencé par **ID média**, jamais par URL (brief §4/§5).

## Routes

Déclarées dans `RealisationsModule::boot()`. Toutes en back-office (pas de front).

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/realisations`        | `index`   | `content.edit` |
| GET  | `/admin/realisations/create` | `create`  | `content.edit` |
| POST | `/admin/realisations/create` | `store`   | `content.edit` + CSRF |
| GET  | `/admin/realisations/edit`   | `edit`    | `content.edit` |
| POST | `/admin/realisations/edit`   | `update`  | `content.edit` + CSRF |
| POST | `/admin/realisations/delete` | `destroy` | `content.edit` + CSRF |

## Bloc fourni

**`realisations`** (libellé « Réalisations »), enregistré au boot dans le
`BlockRegistry` partagé. Son `render()` requête la base en live (brief §4) et rend
les réalisations **publiées**, dans l'ordre des `position`, jusqu'à la limite
choisie. Données du bloc : `{ title?: string, limit: int (1..30, défaut 6) }`.
Markup neutre (classes `realisations-*`, photo en `<picture>` WebP responsive) :
le style vit dans le thème.

## Permissions

Espace de permission **`content`** partagé avec Pages, Actualités et Agenda.

| Permission | Détenue par |
|------------|-------------|
| `content.edit`    | `admin` (`*`), `manager` (`content.*`), `editor` |
| `content.publish` | `admin` (`*`), `manager` (`content.*`) |

Un `editor` crée/édite en **brouillon** ; la **publication** exige
`content.publish` (sinon le statut retombe en brouillon côté serveur).

## Éditeur

[`views/editor.php`](../../app/Modules/Realisations/views/editor.php) : titre,
champ **Quill** (description), champ **photo** ouvrant la **modale média partagée**
(`partials/media_modal`), ordre et statut. Le JS
[`collection-editor.js`](../../public/assets/js/collection-editor.js) — découplé
du page-builder — instancie Quill et pilote la modale. Réutilise les styles de
[`block-editor.css`](../../public/assets/css/block-editor.css).

## Tutoriel

Rubrique « Les réalisations » dans [`tutorial.php`](../../app/Core/views/tutorial.php)
(titre, description rich text, photo, ordre, statut ; affichage via le bloc). Elle
n'apparaît que si le module `Realisations` est actif (`$sectionModule`).

## Notes / pièges

- **Module indépendant** : ne dépend d'aucun autre module. La photo est une
  option fournie par Media : si Media est désactivé, le choix d'image disparaît
  (éditeur + bloc) mais le module reste utilisable.
- **Ordre** : champ `position` (entier, croissant) ; à `position` égale, le plus
  récent d'abord.
- **À venir** : réordonnancement par glisser-déposer dans la liste d'admin.
