# Module Media

## Rôle

Bibliothèque d'images du site : upload sécurisé, stockage normalisé, déclinaisons
et versions WebP générées à l'upload. Brique transverse — les autres modules
référencent une image par son **`id`**, jamais par URL.

## Données

Migrations [`001_create_media.sql`](../../app/Modules/Media/migrations/001_create_media.sql),
[`002_add_crop_to_media.sql`](../../app/Modules/Media/migrations/002_add_crop_to_media.sql),
[`003_create_media_folders.sql`](../../app/Modules/Media/migrations/003_create_media_folders.sql).

- **`media`** — référentiel, source de vérité.
  `id`, `filename`, `original_name`, `mime`, `width`, `height`, `size`, `alt`,
  `folder_id` (NULL = « Non classé »), `crop_x`, `crop_y`, `crop_w`, `crop_h`, `created_at`.
- **`media_folders`** — dossiers de classement : `id`, `name` (unique), `created_at`.
  Groupement **purement logique** (aucun lien avec le stockage disque, toujours année/mois).
  - `filename` = chemin **relatif à `public/uploads/`** de l'original re-encodé
    (ex. `2026/06/photo-ab12cd34.jpg`).
  - `width`/`height` = dimensions de **l'original** (source de vérité).
  - `crop_*` = recadrage **non destructif** en pixels de l'original (NULL = aucun).
  - **Aucune ligne par variante** : déclinaisons et WebP sont dérivés du `filename`
    (voir `MediaUrl`).

## Routes

Déclarées dans `MediaModule::boot()`.

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/media`             | `MediaController::index`        | `media.upload` (param `?folder=all\|none\|id`) |
| POST | `/admin/media/upload`      | `MediaController::upload`       | `media.upload` + CSRF |
| POST | `/admin/media/upload/ajax` | `MediaController::uploadAjax`   | `media.upload` + CSRF (réponse JSON) |
| POST | `/admin/media/alt`         | `MediaController::updateAlt`    | `media.upload` + CSRF |
| POST | `/admin/media/move`        | `MediaController::move`         | `media.upload` + CSRF |
| POST | `/admin/media/folder/create`| `MediaController::createFolder`| `media.manage` + CSRF |
| POST | `/admin/media/folder/rename`| `MediaController::renameFolder`| `media.manage` + CSRF |
| POST | `/admin/media/folder/delete`| `MediaController::deleteFolder`| `media.manage` + CSRF |
| GET  | `/admin/media/edit`        | `MediaController::editor`       | `media.upload` |
| POST | `/admin/media/crop`        | `MediaController::crop`         | `media.upload` + CSRF |
| POST | `/admin/media/crop/reset`  | `MediaController::resetCrop`    | `media.upload` + CSRF |
| POST | `/admin/media/delete`      | `MediaController::delete`       | `media.manage` + CSRF |

## Blocs fournis

Le bloc **`image`** ([`Blocks/ImageBlock.php`](../../app/Modules/Media/Blocks/ImageBlock.php)),
enregistré dans `MediaModule::boot()` via `Kernel::blocks()`. Il référence un média
par **ID** (jamais par URL) et rend un `<picture>` : **srcset WebP responsive**
(`mobile`/`tablet`/`desktop`) — le navigateur choisit la largeur selon l'écran via
l'attribut `sizes` — avec repli sur l'original et `width`/`height` (zéro saut de mise
en page).
Comme il est fourni par Media, il n'apparaît dans les éditeurs (Pages, Actualités,
Agenda) **que lorsque Media est actif** — les autres modules ne dépendent donc pas
de Media : le bloc disparaît simplement s'il est désactivé.

**Effets visuels** : le bloc porte aussi `radius` (`none`/`sm`/`md`/`lg`/`full` —
`md` = 8px, reproduit le rendu d'origine pour les images déjà en place) et `shadow`
(`none`/`sm`/`md`/`lg`), rendus en classes CSS `img-radius-*`/`img-shadow-*` (jamais
de style inline, échappement simple). Choisis dans l'éditeur via des **vignettes
radio** avec aperçu visuel (`.effect-picker`, [`block-editor.css`](../../public/assets/css/block-editor.css))
plutôt qu'un `<select>` texte. Ces classes sont définies à **deux endroits à garder
en phase** : le thème `frontend/src/css/blocks.css` et les gabarits de repli
`Modules/{Pages,Agenda,Actualites}/views/front_layout.php` (CSS inline, pas de
bundler) — voir aussi la section AOS de [pages.md](pages.md).

À venir : un bloc `gallery` référençant plusieurs `media.id`.

## Permissions

| Permission | Détenue par |
|------------|-------------|
| `media.upload` | `admin` (`*`), `manager` (`media.*`), `editor` |
| `media.manage` | `admin` (`*`), `manager` (`media.*`) |

`editor` peut téléverser et éditer l'alt, mais **pas supprimer**.

## Config

[`config/media.php`](../../config/media.php) :
- `sizes` — presets de déclinaisons : `thumb` (cover 200², grille admin) + les
  largeurs front `mobile` 640, `tablet` 1024, `desktop` 1600 (fit). En `fit` on
  n'agrandit jamais : la largeur réelle est bornée à la source.
- `srcset_widths` — sous-ensemble de `sizes` servi au public, dans l'ordre croissant.
- `sizes_attr` — attribut `sizes` par défaut des images de contenu (`100vw`).
- `ratios` — formats proposés au recadrage (`libre`, `banniere` 16:5, `paysage` 3:2,
  `carre` 1:1). Verrouillent le Cropper ; **non stockés** (encodés par le crop).
- `allowed_mimes` — types réels acceptés → extension.
- `max_size`, `jpeg_quality`, `webp_quality`.

## Traitement (MediaProcessor)

1. **Limite de taille** (config + limites PHP).
2. **Type réel via `finfo`** — jamais l'extension ni le Content-Type.
3. **Re-encodage GD** (decode → re-encode) : réécrit le fichier proprement et
   **détruit tout payload planqué**. Échec de décodage = refus.
4. Stockage en sous-dossier **année/mois**, nom **`slug + hash court`** (jamais le
   nom client brut ; le vrai nom reste en DB).
5. Génération **WebP plein format** + déclinaisons (`-thumb`, `-mobile`, `-tablet`,
   `-desktop`). `cover` remplit et recadre ; `fit` garde le ratio sans rogner ni agrandir.

Sécurité complémentaire : exécution PHP coupée sur `public/uploads/` via
[.htaccess](../../public/uploads/.htaccess) ; upload accepté seulement si
`is_uploaded_file()`.

## Upload (glisser-déposer multiple)

La zone d'envoi de la bibliothèque est une **dropzone** : glisser-déposer ou clic,
plusieurs images en une fois.

- **Amélioration progressive** : sans JS, c'est le formulaire mono-fichier classique
  (`POST /admin/media/upload` → redirect). Avec JS
  ([`public/assets/js/media-upload.js`](../../public/assets/js/media-upload.js)), la
  carte devient une dropzone qui envoie **un fichier par requête** vers
  `POST /admin/media/upload/ajax` (réponse JSON).
- **Un XHR par image** → barre de progression réelle (`xhr.upload.progress`) et statut
  par fichier (en cours → vert / rouge + motif). Concurrence limitée à **3 uploads
  simultanés**.
- **Validation client** (type image + `max_size`) pour un retour immédiat ; le serveur
  reste seul juge (`uploadAjax()` rejoue tous les garde-fous de `upload()` :
  permission, CSRF, `is_uploaded_file`, `MediaProcessor`).
- Fin de lot **sans erreur** → rechargement vers le dossier courant pour afficher les
  vraies cartes (avec leurs formulaires alt / déplacer / recadrer). **Avec erreurs** →
  elles restent visibles, plus un lien « actualiser ».
- Le JS est **externe** (pas inline) pour respecter la CSP `default-src 'self'`.

## Crop manuel (Cropper.js — brief §5)

Recadrage **non destructif** : Cropper.js (vendorisé dans
[`public/assets/vendor/cropper/`](../../public/assets/vendor/cropper/)) renvoie
`x, y, w, h` en pixels de l'original ; on stocke ces coordonnées en base et
`MediaProcessor::regenerate()` les ré-applique sur l'original (jamais écrasé)
avant de produire WebP + déclinaisons.

**Format imposé (ratio)** : une barre de boutons (config `ratios`) verrouille
l'`aspectRatio` du Cropper — *Libre*, *Bannière 16:5*, *Paysage 3:2*, *Carré 1:1*.
Le ratio n'est **pas stocké** : il est entièrement encodé par les dimensions du crop
(`crop_w`/`crop_h`) renvoyées au serveur. Les largeurs `mobile`/`tablet`/`desktop`
sont ensuite déclinées **sur ce même recadrage** — d'où le responsive automatique.

Conséquence clé : le crop **survit à `media:regen`** (il fait partie de l'état du
média, pas d'un fichier dérivé jetable). « Réinitialiser » remet `crop_*` à NULL
et régénère depuis l'original entier.

- Édition : `/admin/media/edit?id=` → [`views/crop.php`](../../app/Modules/Media/views/crop.php)
  + [`public/assets/js/media-crop.js`](../../public/assets/js/media-crop.js).
- Le JS d'init est **externe** (pas inline) pour respecter la CSP `default-src 'self'`.

## Dossiers

Classement logique de la médiathèque pour s'y retrouver quand les images sont
nombreuses. Un dossier ne change rien au stockage : seul `media.folder_id` relie
une image à un dossier (NULL = « Non classé »).

- Navigation par **chips** en haut de la bibliothèque : *Tous*, *Non classé*, puis
  un chip par dossier (avec compteur). Le filtre passe par `?folder=`.
- **Upload ciblé** : téléverser depuis un dossier y dépose directement l'image.
- **Déplacement** : chaque vignette a un select « déplacer vers ».
- **Gestion** (`media.manage`) : créer, renommer, supprimer un dossier. Supprimer un
  dossier **ne supprime pas les images** — elles repassent en « Non classé ».
- Niveau unique (pas de sous-dossiers) en v1 ; l'imbrication reste une évolution
  possible (un `parent_id` suffirait).

## URLs (MediaUrl)

- `MediaUrl::original($m)` → format d'origine (`…/photo-x.jpg`)
- `MediaUrl::webp($m)` → `…/photo-x.webp`
- `MediaUrl::size($m, 'desktop')` → `…/photo-x-desktop.webp` (retombe sur le WebP plein format si preset inconnu)
- `MediaUrl::srcset($m)` → chaîne `url Nw, …` responsive (déjà échappée) bornée à la
  source et dédupliquée (pas trois entrées de même largeur si l'image est petite).
- `MediaUrl::intrinsic($m)` → `[largeur, hauteur]` affichées (le crop s'il existe,
  sinon l'original) — pour poser `width`/`height` sur le `<img>`.
- Param `$bust = true` ajoute `?v=mtime` (cache-busting back-office après recadrage).

## Régénération (obligatoire — brief §5)

```bash
php bin/flow media:regen
```

Reparcourt `media` et reconstruit WebP + déclinaisons depuis chaque original.
À lancer après l'ajout ou la modification d'un preset, sinon le parc d'images
devient hétérogène.

## Notes / pièges

- **GD est la cible garantie** ; Imagick non requis. WebP doit être compilé dans GD.
- **GIF animés aplatis** (limite GD) : seule la première frame est conservée.
- **Pas de rotation EXIF automatique** pour l'instant (à ajouter si besoin photo).
- **Crop manuel (Cropper.js)** : implémenté, non destructif (voir section dédiée).
  L'original reste toujours intact ; seules les déclinaisons sont régénérées.
- **Cropper.js est vendorisé** (v1.6.2, auto-hébergé) — pas de CDN, compatible CSP.
