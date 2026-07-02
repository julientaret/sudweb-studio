# Module Sliders

## Rôle

Gère une **collection de diapositives** : chaque diapositive a un **titre**, une
**description en texte riche** (éditeur Quill) et une **photo** issue de la
bibliothèque Média. Calqué sur la base commune « contenu » (brief §3/§4).

> **Le bloc `slider` a été retiré du sélecteur de blocs.** Le module n'a donc
> **plus aucun point d'affichage public** pour l'instant : les diapositives
> restent créables/éditables en admin (back-office intact), mais n'apparaissent
> plus sur le site tant qu'un nouveau point d'affichage n'est pas réintroduit.
> Voir la section [Bloc fourni](#bloc-fourni-historique) ci-dessous pour le détail
> technique (classe toujours présente, simplement non enregistrée).

## Données

Migration [`001_create_slides.sql`](../../app/Modules/Sliders/migrations/001_create_slides.sql).

- **`slides`** : `id`, `title`, `description` (LONGTEXT — **Delta Quill JSON**,
  jamais de HTML stocké), `media_id` (ID média, nullable), `position` (SMALLINT,
  ordre croissant), `status` (`draft`|`published`), `created_at`, `updated_at`.
- `description` est rendue côté serveur par [`App\Core\DeltaRenderer`](../../app/Core/DeltaRenderer.php)
  (prose bornée, échappement systématique) ; elle réutilise le **sanitizer du bloc
  Texte du Core** ([`RichtextBlock`](../../app/Core/Blocks/RichtextBlock.php)) à
  l'enregistrement.
- `media_id` est référencé par **ID média**, jamais par URL (brief §4/§5).

## Routes

Déclarées dans `SlidersModule::boot()`. Toutes en back-office (pas de front).

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/sliders`        | `index`   | `content.edit` |
| GET  | `/admin/sliders/create` | `create`  | `content.edit` |
| POST | `/admin/sliders/create` | `store`   | `content.edit` + CSRF |
| GET  | `/admin/sliders/edit`   | `edit`    | `content.edit` |
| POST | `/admin/sliders/edit`   | `update`  | `content.edit` + CSRF |
| POST | `/admin/sliders/delete` | `destroy` | `content.edit` + CSRF |

## Bloc fourni (historique)

**`slider`** ([`Blocks/SliderBlock.php`](../../app/Modules/Sliders/Blocks/SliderBlock.php),
libellé « Slider ») — **classe conservée mais plus enregistrée** dans
`SlidersModule::boot()` (retirée du `BlockRegistry`, donc du sélecteur de blocs de
tous les éditeurs). Son `render()` requêtait la base en live (brief §4) et rendait
les diapositives **publiées**, dans l'ordre des `position`, jusqu'à la limite
choisie. Données du bloc : `{ title?: string, limit: int (1..30, défaut 8) }`.

Markup neutre : `<section class="slider" data-slider>` → `<ul class="slider__track">`
→ `<li class="slider__slide">` (photo `<picture>` WebP responsive + légende). **Le
style ET le comportement de carrousel vivent dans le thème** : aucun JS n'est
imposé par le module (cohérent avec « le thème possède la présentation »). Le thème
peut cibler `[data-slider]` pour activer son carrousel.

## Permissions

Espace de permission **`content`** partagé avec Pages, Actualités et Agenda.

| Permission | Détenue par |
|------------|-------------|
| `content.edit`    | `admin` (`*`), `manager` (`content.*`), `editor` |
| `content.publish` | `admin` (`*`), `manager` (`content.*`) |

Un `editor` crée/édite en **brouillon** ; la **publication** exige
`content.publish`.

## Éditeur

[`views/editor.php`](../../app/Modules/Sliders/views/editor.php) : titre, champ
**Quill** (description), champ **photo** ouvrant la **modale média partagée**,
ordre et statut. Le JS [`collection-editor.js`](../../public/assets/js/collection-editor.js)
— partagé avec Réalisations, découplé du page-builder — instancie Quill et pilote
la modale.

## Tutoriel

Rubrique « Les sliders » dans [`tutorial.php`](../../app/Core/views/tutorial.php)
(titre, description rich text, photo, ordre, statut ; affichage via le bloc, effet
de carrousel laissé au thème). Elle n'apparaît que si le module `Sliders` est actif
(`$sectionModule`).

## Notes / pièges

- **Module indépendant** : ne dépend d'aucun autre module. La photo est une
  option fournie par Media (dégradée proprement si Media est désactivé).
- **Ordre** : champ `position` (entier, croissant) ; à `position` égale, le plus
  récent d'abord.
- **Carrousel** : le module ne fournit que la structure ; le défilement
  (autoplay, flèches, points) est à implémenter dans le thème.
- **Aucun affichage public actuellement** : le bloc `slider` n'est plus enregistré
  (voir [Bloc fourni](#bloc-fourni-historique)). Pour réafficher les diapositives,
  soit ré-enregistrer `SliderBlock` dans `SlidersModule::boot()`, soit exposer une
  route/partial front dédiée.
