# Module Documents

## Rôle

Bibliothèque de **documents réutilisables** du site : upload sécurisé, stockage
normalisé, classement par dossiers. Pendant « non-image » du module Media — pour
des fichiers bureautiques (PDF, Word, Excel, PowerPoint, OpenDocument, texte). Les
documents sont réutilisés en **copiant leur lien public** depuis la bibliothèque
(à coller dans un lien de page, un bouton, un e-mail…).

## Données

Migrations [`001_create_documents.sql`](../../app/Modules/Documents/migrations/001_create_documents.sql),
[`002_create_document_folders.sql`](../../app/Modules/Documents/migrations/002_create_document_folders.sql).

- **`documents`** — référentiel, source de vérité.
  `id`, `filename`, `original_name`, `title`, `mime`, `ext`, `size`,
  `folder_id` (NULL = « Non classé »), `created_at`.
  - `filename` = chemin **relatif à `public/documents/`** du fichier assaini
    (ex. `2026/06/rapport-ab12cd34.pdf`).
  - `title` = libellé **éditorial** facultatif (réutilisable) ; à défaut on affiche
    `original_name`.
  - `ext` = extension assainie (`pdf`, `docx`…) ; `mime` = type réel détecté.
  - **Aucune URL stockée** : le lien public est dérivé du `filename` (voir `DocumentUrl`).
- **`document_folders`** — dossiers de classement : `id`, `name` (unique), `created_at`.
  Groupement **purement logique** (aucun lien avec le stockage disque, toujours année/mois).

## Routes

Déclarées dans `DocumentsModule::boot()`.

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/documents`             | `DocumentController::index`        | `documents.upload` (param `?folder=all\|none\|id`) |
| POST | `/admin/documents/upload`      | `DocumentController::upload`       | `documents.upload` + CSRF |
| POST | `/admin/documents/upload/ajax` | `DocumentController::uploadAjax`   | `documents.upload` + CSRF (réponse JSON) |
| POST | `/admin/documents/title`       | `DocumentController::updateTitle`  | `documents.upload` + CSRF |
| POST | `/admin/documents/move`        | `DocumentController::move`         | `documents.upload` + CSRF |
| POST | `/admin/documents/folder/create`| `DocumentController::createFolder`| `documents.manage` + CSRF |
| POST | `/admin/documents/folder/rename`| `DocumentController::renameFolder`| `documents.manage` + CSRF |
| POST | `/admin/documents/folder/delete`| `DocumentController::deleteFolder`| `documents.manage` + CSRF |
| POST | `/admin/documents/delete`      | `DocumentController::delete`       | `documents.manage` + CSRF |

## Permissions

| Permission | Détenue par |
|------------|-------------|
| `documents.upload` | `admin` (`*`), `manager` (`documents.*`), `editor` |
| `documents.manage` | `admin` (`*`), `manager` (`documents.*`) |

`editor` peut téléverser et éditer le libellé, mais **pas supprimer** (documents ni
dossiers).

## Config

[`config/documents.php`](../../config/documents.php) :
- `allowed` — extensions autorisées → libellé lisible (1er garde-fou).
- `allowed_mimes` — types réels acceptés via `finfo` (2nd garde-fou).
- `max_size` — taille max d'un upload (20 Mo par défaut).

## Traitement (DocumentProcessor)

Pas de re-encodage possible (ce ne sont pas des images), donc la sécurité repose
sur une **double liste blanche** :

1. **Limite de taille** (config + limites PHP).
2. **Extension** dans la liste blanche `allowed` — un `.php` ne passe jamais.
3. **Type réel via `finfo`** dans `allowed_mimes` — jamais le Content-Type du client.
4. Stockage en sous-dossier **année/mois**, nom **`slug + hash court`** (jamais le
   nom client brut ; le vrai nom reste en DB).

Sécurité complémentaire : exécution de scripts coupée sur `public/documents/` via
[.htaccess](../../public/documents/.htaccess) ; upload accepté seulement si
`is_uploaded_file()`.

> Note OOXML/ODF : `.docx`, `.xlsx`, `.pptx` et les formats OpenDocument sont des
> conteneurs ZIP ; selon le système, `finfo` renvoie le mime précis **ou**
> `application/zip`. Les deux sont acceptés, l'extension faisant foi en premier.

## Upload (glisser-déposer multiple)

Identique aux médias. La zone d'envoi est une **dropzone** : glisser-déposer ou clic,
plusieurs fichiers en une fois.

- **Amélioration progressive** : sans JS, formulaire mono-fichier classique
  (`POST /admin/documents/upload` → redirect). Avec JS
  ([`public/assets/js/documents-upload.js`](../../public/assets/js/documents-upload.js)),
  la carte devient une dropzone qui envoie **un fichier par requête** vers
  `POST /admin/documents/upload/ajax` (réponse JSON).
- **Un XHR par fichier** → barre de progression réelle et statut par fichier.
  Concurrence limitée à **3 uploads simultanés**.
- **Validation client** (extension + `max_size`) pour un retour immédiat ; le serveur
  reste seul juge.
- Le JS est **externe** (pas inline) pour respecter la CSP `default-src 'self'`.

## Réutilisation sur le site

Chaque carte porte un bouton **« Copier le lien »** : il place l'URL publique du
document dans le presse-papiers (ex. `/documents/2026/06/rapport-ab12cd34.pdf`),
prête à coller dans un lien de page (bloc Texte enrichi), un bouton ou un e-mail.

- Le lien est **stable** : déplacer un document entre dossiers ne le change pas
  (le dossier est purement logique, le stockage reste année/mois).
- Le **libellé** (`title`) sert d'intitulé éditorial réutilisable, indépendant du
  nom de fichier d'origine.

## Dossiers

Classement logique de la bibliothèque, comme pour les médias. Un dossier ne change
rien au stockage : seul `documents.folder_id` relie un document à un dossier
(NULL = « Non classé »).

- Navigation par **chips** : *Tous*, *Non classé*, puis un chip par dossier (compteur).
- **Upload ciblé** : téléverser depuis un dossier y dépose directement le document.
- **Déplacement** : chaque carte a un select « déplacer vers ».
- **Gestion** (`documents.manage`) : créer, renommer, supprimer un dossier. Supprimer
  un dossier **ne supprime pas les documents** — ils repassent en « Non classé ».
- Niveau unique (pas de sous-dossiers) en v1.

## Liens (DocumentUrl)

- `DocumentUrl::public($d)` → `…/documents/2026/06/rapport-x.pdf` (préfixé du `base_path`).

## Notes / pièges

- **Pas de prévisualisation** intégrée : « Ouvrir » pointe vers le fichier, le
  navigateur l'affiche (PDF) ou le télécharge selon le type.
- **CSV** : attention à l'injection de formules à l'ouverture dans un tableur
  (donnée fournie par un tiers) — hors périmètre de Flow, à garder en tête.
- Stockage séparé des images : `public/documents/` (et non `public/uploads/`).
