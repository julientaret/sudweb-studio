# Module Pages légales

## Rôle

Gère les **pages juridiques** du site (mentions légales, politique de
confidentialité, CGU, cookies…), **éditées au texte riche** dans l'admin. Deux
pages sont créées « pour commencer » (mentions légales, politique de
confidentialité) ; l'admin peut en **ajouter** autant que nécessaire. Chaque page
a une **adresse publique à la racine** d'après son slug (ex. `/mentions-legales`).

Volontairement plus simple que Pages/Actualités : **une seule zone de texte riche**
par page (pas de page-builder par blocs), adaptée à de la prose juridique.

## Données

Migration [`001_create_legal_pages.sql`](../../app/Modules/LegalPages/migrations/001_create_legal_pages.sql).

- **`legal_pages`** : `id`, `title`, `slug` (unique = URL front), `content`
  (**Delta Quill** JSON `{"ops":[…]}`, jamais du HTML), `status`
  (`draft`|`published`), `created_at`, `updated_at`.
- **Seed** (idempotent, `INSERT IGNORE`) : `mentions-legales` (vide) et
  `politique-de-confidentialite` (pré-remplie avec le texte RGPD repris de
  l'ancienne page statique, converti en Delta). Le module devient la **source
  unique** de la politique de confidentialité référencée par le module Contact
  (`config/contact.php` → `privacy_url`).

## Routes

Déclarées dans `LegalPagesModule::boot()`.

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/pages-legales`         | `index`   | `content.edit` |
| GET  | `/admin/pages-legales/create`  | `create`  | `content.edit` |
| POST | `/admin/pages-legales/create`  | `store`   | `content.edit` + CSRF |
| GET  | `/admin/pages-legales/edit`    | `edit`    | `content.edit` |
| POST | `/admin/pages-legales/edit`    | `update`  | `content.edit` + CSRF |
| POST | `/admin/pages-legales/delete`  | `destroy` | `content.edit` + CSRF |
| GET  | `/<slug>` (par page **publiée**) | `show`  | public |

> **Front à la racine sans conflit** : `boot()` enregistre une **route exacte**
> par page publiée (`publishedSlugs()`). Les routes exactes priment sur le
> catch-all `/{slug}` des Pages dans [`Router::dispatch`](../../app/Core/Router.php) ;
> un slug inconnu retombe donc naturellement sur la résolution Pages (puis 404).
> La requête de slugs au boot est **best-effort** (try/catch) : aucune route front
> tant que la table n'est pas migrée. Une page en **brouillon** n'est pas routée
> (404 en front).
>
> Attention : un **fichier statique** `frontend/static/<slug>.php` est servi
> **avant** le CMS — il masquerait une page légale de même slug.

## Blocs fournis

Aucun. Le module ne s'enregistre pas dans le page-builder.

## Permissions

| Permission | Détenue par |
|------------|-------------|
| `content.edit`    | `admin` (`*`), `manager`, `editor` |
| `content.publish` | `admin` (`*`), `manager` |

Réutilise l'espace commun **`content`** (comme Pages/Actualités), mappé dans
[`config/roles.php`](../../config/roles.php) — aucune permission nouvelle. Le
toggle *Publiée* est réservé à `content.publish` : un rédacteur (`editor`)
enregistre en brouillon.

## Config

Aucune clé propre.

## Notes / pièges

- **Texte riche borné** : on stocke le Delta, jamais du HTML. Le rendu front passe
  par [`App\Core\DeltaRenderer`](../../app/Core/DeltaRenderer.php)
  (gras, italique, souligné, barré, couleur de palette, liens sûrs, titres h2/h3,
  listes, citation, code, alignement — tout le reste est ignoré, texte échappé).
  C'est une **classe du Core**, partagée par tout module éditant du texte riche :
  Pages légales est donc **indépendant** (aucune dépendance à Pages). La palette de l'éditeur
  ([`public/assets/js/legal-editor.js`](../../public/assets/js/legal-editor.js))
  doit rester alignée sur `DeltaRenderer::ALLOWED_COLORS`.
- **Éditeur** : Quill, assets déjà embarqués (`public/assets/vendor/quill/`).
  `legal-editor.js` est une init monozone minimale (vs le page-builder de
  `pages-editor.js`).
- **Rendu front** : `front_layout` du module sert de repli ; en pratique le thème
  du site public (`frontend/layouts/front_layout.php`) le surcharge via
  `app.front_views` (même mécanisme que Pages).
- **Liens en pied de page** : `frontend/includes/footer.php` affiche
  **dynamiquement** un lien par page **publiée** (`LegalPageRepository::publishedLinks()`),
  uniquement si le module est actif (`modules.enabled`) — best-effort, jamais
  d'erreur si le module est désactivé. Styles `.site-legal` dans
  `frontend/src/css/footer.css` (rebuild `npm run build`).
- **Éditeur admin** : la vue charge `assets/css/block-editor.css` (en plus de
  Quill) pour réutiliser le **toggle Brouillon/Publiée** commun aux autres modules.
