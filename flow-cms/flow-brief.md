# Flow — Brief & documentation d'architecture

> **Projet :** Flow, CMS modulaire en PHP.
> **Éditeur :** Sud Web Studio.
> Document fondateur : choix techniques, conventions et règles de développement.
> Toute nouvelle partie de Flow doit être documentée ici en suivant le template module (§9).

---

## 1. Philosophie & principes

- **PHP vanilla**, sans framework. Le cœur (routeur, accès DB, templating, auth) est codé à la main.
- **Zéro dépendance tierce côté PHP en production.** On assemble des briques natives éprouvées, on ne ramène pas de framework ni de lib runtime.
- **Composer uniquement pour l'autoload** PSR-4. Aucun code tiers n'est embarqué en prod.
- **Une instance isolée par site.** Chaque client a sa propre installation : base de données dédiée, fichiers dédiés, aucune fuite possible entre sites. Coût assumé : un patch du cœur = N redéploiements (à automatiser par script).
- **Tout est module.** Les fonctionnalités (actualités, blog, agenda…) sont des modules autonomes et interchangeables derrière un contrat commun.
- **Documentation au fil de l'eau.** Chaque partie du code est documentée *dans la foulée de son écriture*, jamais après coup. Une partie de Flow n'est considérée terminée que lorsque sa documentation est écrite. La doc globale (§9) se construit incrémentalement avec le code, pour rester complète et juste.

Le front (JavaScript de l'admin) n'est pas concerné par la règle « zéro dépendance » : on s'autorise des libs JS ciblées (Cropper.js, Quill).

---

## 2. Arborescence

Seul `public/` est exposé par le serveur web. Tout le reste vit hors webroot.

```
/public          → seul dossier exposé : index.php (front controller) + assets + uploads
/app
  /Core          → Router, DB (PDO), View, Auth, Csrf, Migrator…
  /Modules
    /News
    /Blog
    /Agenda
    /Pages       → page-builder (système de blocs)
    /Media       → bibliothèque d'images
    /Users       → gestion des comptes & rôles
/storage         → logs, cache, originaux d'images (hors public)
/config
/migrations
/docs            → documentation de Flow (voir §9)
```

- **Point d'entrée unique** : `public/index.php` (front controller). Toutes les requêtes y passent.
- **Autoload PSR-4** : namespace racine `App\` mappé sur `app/`. Le chemin des classes suit celui des dossiers (`App\Core\Router`, `App\Modules\Blog\BlogModule`).

`composer.json` minimal :

```json
{
  "autoload": {
    "psr-4": { "App\\": "app/" }
  }
}
```

---

## 3. Système de modules

Chaque module est un dossier autosuffisant qui embarque entité/migration, back-office, rendu front et routes, et s'enregistre au démarrage.

### Contrat

```php
interface Module {
    public function name(): string;
    public function boot(Router $router): void;  // routes front + admin
    public function adminMenu(): array;          // entrées du back-office
    public function migrations(): array;         // fichiers SQL à appliquer
}
```

- Un `ModuleRegistry` scanne `/app/Modules`, instancie chaque module et appelle `boot()` au démarrage.
- **Activation par site** via la config : activer/désactiver un module = une ligne.
- **Base commune « contenu »** : Blog et Actualités partagent ~90 % de leur logique (contenu daté, catégories, statut publié/brouillon). Cette base est factorisée pour éviter la duplication.

C'est aussi dans `boot()` que le module enregistre ses **blocs** (§4) et ses **permissions** (§7).

---

## 4. Pages & système de blocs

La mise en page autonome repose sur un système de blocs : le client compose ses pages avec des blocs prédéfinis qu'on contrôle. Garde-fous sur le rendu, liberté réelle de composition.

### Stockage

Une page a une colonne `content` en **JSON** : un tableau ordonné de blocs.

```json
[
  { "id": "b1", "type": "heading",  "data": { "text": "Bienvenue", "level": 1 } },
  { "id": "b2", "type": "richtext", "data": { "delta": { } } },
  { "id": "b3", "type": "gallery",  "data": { "images": [12, 45, 78], "columns": 3 } }
]
```

- Chaque bloc porte un `id` (pour le réordonnancement en drag & drop).
- Les images sont référencées par **ID** de la Media library, **jamais par URL** : régénérer les déclinaisons ou changer de stockage ne casse aucune page.

### Contrat de bloc

```php
interface Block {
    public function type(): string;               // "heading", "gallery"…
    public function render(array $data): string;  // HTML front (échappé)
    public function form(array $data): string;    // UI d'édition admin
    public function sanitize(array $input): array; // nettoyage avant stockage
}
```

- Un `BlockRegistry` mappe `type => instance`.
- **Les modules enregistrent leurs propres blocs** dans `boot()`. Un bloc peut être statique (contenu figé) ou dynamique (ex. « prochains événements » dont le `render()` requête la base en live). Le système ne fait pas de distinction.

### Flux

- **Rendu front** : décodage du JSON → boucle `$registry->render($block['type'], $block['data'])`.
- **Édition admin** : chaque bloc rend son `form()` ; le drag réordonne les `id` ; à la sauvegarde, reconstruction du JSON en passant chaque bloc par `sanitize()`.

### Sécurité

Le payload vient de l'utilisateur → `sanitize()` à l'écriture **et** échappement systématique au rendu. Cas particulier du bloc `richtext` : voir §8.

---

## 5. Bibliothèque d'images (Media)

### Référentiel

Table `media`, source de vérité : `id`, `filename`, `original_name`, `mime`, `width`, `height`, `size`, `alt`, `created_at`. Le reste de Flow référence l'`id`.

### Stockage disque

- Dans `public/uploads/`, servi directement par le serveur web (perf).
- Sous-dossiers par **année/mois** (`2026/06/`) — jamais un dossier unique qui gonfle à l'infini.
- **Renommage systématique** : `slug` + hash court. Jamais le nom client brut. Le vrai nom reste en DB pour l'affichage.
- L'**original reste toujours intact**. Un crop produit une nouvelle version, il n'écrase jamais la source.

### Traitement

- **GD par défaut** (présent partout, y compris mutualisés). Détection d'Imagick possible pour l'utiliser s'il est disponible, mais GD est la cible garantie.
- **Sécurité upload — re-encodage obligatoire** : on ne fait jamais confiance à l'extension ni au Content-Type. Vérification du type réel via `finfo`, puis chaque image est **repassée par GD** (decode → re-encode) — ça réécrit le fichier proprement et détruit tout payload planqué. Combiné avec : refus de tout non-image, limite de taille, exécution PHP désactivée sur `uploads/` (`.htaccess`).
- **WebP en sortie** en plus du format d'origine.

### Déclinaisons

Tailles fixes générées **à l'upload**, presets définis en config :

```php
'media_sizes' => [
    'thumb'  => ['w' => 200,  'h' => 200,  'mode' => 'cover'],
    'medium' => ['w' => 800,  'h' => null, 'mode' => 'fit'],
    'large'  => ['w' => 1600, 'h' => null, 'mode' => 'fit'],
],
```

- `cover` = remplit la boîte et recadre (vignettes alignées) ; `fit` = garde le ratio sans rogner (images de contenu).
- **Nommage dérivable** : `img.webp`, `img-thumb.webp`, `img-medium.webp`. Pas de ligne DB par variante : on reconstruit le chemin à partir du filename + preset.
- **Crop manuel** : Cropper.js (front) renvoie les coordonnées `x, y, w, h` ; GD découpe l'original ; les presets sont régénérés à partir du résultat.

> **Obligatoire dès le départ : un script de régénération.** Quand un preset est ajouté ou modifié, les images déjà uploadées n'ont pas la nouvelle taille. Une commande qui reparcourt `media` et reconstruit toutes les déclinaisons règle ça en une passe. Sans ce filet, le parc d'images devient hétérogène et irrattrapable.

---

## 6. Sécurité & authentification

Tout est couvert par PHP natif. Aucune lib d'auth.

- **Mots de passe** : `password_hash()` (`PASSWORD_DEFAULT`, ou `PASSWORD_ARGON2ID` si dispo), `password_verify()` au login, `password_needs_rehash()` pour faire monter le coût. Jamais md5/sha1.
- **Sessions** : `session_regenerate_id(true)` à chaque connexion réussie (anti-fixation). Cookie `HttpOnly` + `Secure` + `SameSite=Lax`, nom custom, durée maîtrisée.
- **Anti-brute-force** : compteur de tentatives en DB (compte + IP), lockout temporaire progressif après X échecs. À poser sur le login et le reset.
- **CSRF** : token par session injecté dans chaque formulaire, vérifié avec `hash_equals()`. Tous les POST y passent.
- **Headers & transport** : HTTPS forcé + HSTS, `X-Frame-Options: DENY` sur l'admin, `X-Content-Type-Options: nosniff`, CSP. Posés dans le front controller.
- **Injection / XSS** : PDO en requêtes préparées partout, `htmlspecialchars` systématique en sortie.
- **Reset de mot de passe** : token `random_bytes`, stocké **hashé** en DB, à usage unique et expirant, envoyé par mail.
- **2FA (TOTP)** : option future, faisable en vanilla, non prioritaire pour la v1.

### Quand déroger au natif

Une lib ciblée (jamais un framework) se justifie uniquement pour : **OAuth / social login**, **SSO / SAML**, **JWT** pour une API stateless, ou **TOTP** si on ne veut pas l'implémenter. Login classique + permissions = natif suffisant et sûr.

---

## 7. Permissions & rôles

Plusieurs utilisateurs par site, droits distincts — sans usine à gaz.

- **Règle d'or : le code teste une permission, jamais un rôle.** `$user->can('blog.publish')`, **jamais** `if ($user->role === 'admin')`. Ajouter ou modifier un rôle ne touche alors qu'un seul fichier.
- **Permissions nommées `module.action`**, déclarées par chaque module dans `boot()` : `pages.edit`, `blog.publish`, `media.upload`, `users.manage`…
- **Mapping rôle → permissions en config**, avec wildcards :

```php
'roles' => [
    'admin'   => ['*'],                                     // Sud Web Studio : tout
    'manager' => ['content.*', 'media.*', 'users.manage'],  // client référent
    'editor'  => ['content.edit', 'media.upload'],          // rédacteur, ne publie pas
],
```

- Pas de tables `roles`/`permissions` pour l'instant : un champ `role` sur `users` + le mapping en config suffisent. Le jour où un client veut des rôles sur mesure, on bascule le mapping vers la DB **sans toucher une seule vérification** dans le code.
- La **gestion des utilisateurs est un module** (`Users`), protégé par `users.manage`.

---

## 8. Éditeur de texte (bloc richtext)

- **Quill** (licence BSD, libre et redistribuable, léger, fonctionne en vanilla JS).
- **Stocker le Delta (JSON), pas du HTML brut.** Le HTML est rendu **côté serveur** à partir du Delta, en n'émettant que les balises autorisées. Conséquence : aucun HTML arbitraire à purifier → pas de HTML Purifier → zéro-dépendance préservé. Nécessite un petit renderer Delta → HTML en PHP (borné).
- **Richtext borné à la prose** : gras, italique, listes, liens, titres h2/h3. La mise en page reste le job des blocs de page — pas un second constructeur dans le richtext.
- **Écartés** : TinyMCE et CKEditor (licence commerciale requise pour une application propriétaire). **Alternative future** : TipTap (MIT, headless, sortie JSON) si besoin d'un contrôle total.

---

## 9. Documentation de Flow

La documentation n'est pas une étape de fin de projet : elle est produite **au fil de l'eau**, partie par partie, en même temps que le code (voir le principe en §1).

### Règle de développement

- Toute partie codée (module, composant du Core, bloc) est documentée dans la foulée.
- Une partie n'est « terminée » que lorsque sa fiche de doc est écrite et à jour — c'est un critère de *definition of done*, au même titre que le code qui tourne.
- La doc globale s'assemble incrémentalement et reste donc toujours le reflet exact de l'état réel du code.

### Organisation

- **Emplacement** : `/docs` dans le repo, versionné avec le code. Format **Markdown**.
- **Structure** :
  - Ce document = socle, conventions transverses et règles de dev.
  - **Une fiche par module**, suivant le template ci-dessous, pour que la doc épouse l'archi modulaire (prévisible, cohérente).
- Si un site de doc devient utile plus tard (MkDocs, Docusaurus), il se construit par-dessus ce Markdown — aucun de ces choix n'est fermé.

### Template de fiche module (`/docs/modules/<nom>.md`)

```markdown
# Module <Nom>

## Rôle
Ce que fait le module, en deux phrases.

## Données
Tables, colonnes, relations. Migrations associées.

## Routes
Front et admin exposées par boot().

## Blocs fournis
Blocs enregistrés (type, statique/dynamique, data attendue).

## Permissions
Permissions déclarées (module.action) et qui les détient.

## Config
Clés de configuration spécifiques au module.

## Notes / pièges
Points de vigilance, dépendances inter-modules.
```
