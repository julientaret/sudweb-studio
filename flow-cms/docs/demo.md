# Mode démonstration

Fonction **transverse** (niveau Core, pas un module du catalogue) : crée en un
clic un compte de démonstration limité et quelques données factices, pour faire
visiter le CMS à un prospect ou un client sans exposer le vrai contenu du site.

## Rôle

Un bouton (carte « Mode démonstration », onglet Administration) fait tout le
travail : créer le compte, semer les données factices, marquer chaque ligne
créée pour un nettoyage ciblé plus tard. Pourquoi **Core et non module** : même
raisonnement que l'onglet Administration et le mode maintenance — un
interrupteur qui pilote un compte/des données transverses à plusieurs modules
ne doit pas dépendre du catalogue de modules qu'il manipule.

## Fonctionnement

Toute la logique vit dans [`DemoSeeder`](../app/Core/DemoSeeder.php), piloté
depuis [`AdministrationController::toggleDemo()`](../app/Core/AdministrationController.php).

### Activation

1. **Purge défensive** d'un éventuel reste d'une activation précédente mal
   terminée (`DELETE ... WHERE is_demo = 1` sur chaque table concernée) et
   suppression d'un éventuel compte démo existant (par e-mail).
2. **Création du compte** : e-mail fixe `demo@flow.local`, mot de passe aléatoire
   (`bin2hex(random_bytes(6))`), rôle dédié `demo` (voir Permissions ci-dessous).
3. **Semis de données factices**, un ou plusieurs enregistrements par module —
   *uniquement* dans les modules **actuellement actifs**
   (`module_enabled()`) : Pages, Agenda, Actualités, Réalisations, Devis,
   Contact. **Jamais de photo factice** (Media/Documents non touchés).
4. Chaque ligne insérée est marquée `is_demo = 1` immédiatement après sa
   création (`INSERT` via le `Repository::create()` habituel du module, puis
   `UPDATE ... SET is_demo = 1 WHERE id = :id`) — mêmes garde-fous de
   sanitation que si la ligne avait été créée depuis l'admin.
5. Le mot de passe généré est **retourné à l'appelant, jamais persisté en
   clair** ; seul son hash (`password_hash()`, `Auth::hashAlgo()`) va en base.
   Le contrôleur **rend la page directement** (pas de redirection) pour
   afficher l'e-mail et le mot de passe une seule fois, sans jamais les faire
   transiter par une URL (logs serveur, historique du navigateur).

### Désactivation

1. `DELETE ... WHERE is_demo = 1` sur les six tables concernées.
2. Suppression du compte démo (par e-mail).
3. État remis à `enabled: false`.

Aucune des deux opérations ne touche à une ligne qui ne porte pas `is_demo = 1` :
impossible de perdre de vraies données, même si un titre coïncide avec un
exemple factice.

## État (storage)

Stocké dans `storage/demo.json`
([`DemoSettings`](../app/Core/DemoSettings.php)), non versionné — un état
d'admin, pas une constante serveur. Même approche que `storage/maintenance.json`
et `storage/aos.json`.

```json
{
    "enabled": true,
    "user_id": 42
}
```

## Données marquées `is_demo`

Une migration par module ajoute la colonne (+ index) :

| Module | Table | Migration |
|--------|-------|-----------|
| Pages | `pages` | [`002_add_is_demo.sql`](../app/Modules/Pages/migrations/002_add_is_demo.sql) |
| Agenda | `events` | [`002_add_is_demo.sql`](../app/Modules/Agenda/migrations/002_add_is_demo.sql) |
| Actualités | `articles` | [`002_add_is_demo.sql`](../app/Modules/Actualites/migrations/002_add_is_demo.sql) |
| Réalisations | `realisations` | [`002_add_is_demo.sql`](../app/Modules/Realisations/migrations/002_add_is_demo.sql) |
| Devis | `quote_requests` | [`003_add_is_demo.sql`](../app/Modules/Devis/migrations/003_add_is_demo.sql) |
| Contact | `contact_submissions` | [`002_add_is_demo.sql`](../app/Modules/Contact/migrations/002_add_is_demo.sql) |

Media, Documents, Sliders, GoogleReviews, LegalPages et Stats ne reçoivent
**jamais** de données factices (photos exclues par choix ; les autres ne s'y
prêtent pas — avis synchronisés depuis Google, statistiques dérivées du trafic
réel, etc.).

## Routes

Déclarées dans [`Kernel::handleWeb()`](../app/Core/Kernel.php) (niveau Core),
servies par [`AdministrationController`](../app/Core/AdministrationController.php).

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| POST | `/admin/administration/demo` | `toggleDemo` | `admin.modules` + CSRF |

## Permissions

| Permission | Détenue par | Rôle |
|------------|-------------|------|
| `admin.modules` | `admin` seul | Activer/désactiver le mode démo (onglet Administration) |

Le rôle **`demo`** lui-même ([`config/roles.php`](../config/roles.php)) est
volontairement restreint : `content.edit`, `content.publish`, `media.upload`,
`devis.view`, `contacts.view`, `stats.view`. **Aucune permission de
suppression**, aucun accès à `users.*`/`admin.*`/`googlereviews.*` — un compte
partagé avec un tiers ne doit jamais pouvoir casser de vraies données ou la
configuration du site.

## Notes / pièges

- **Réactiver = nouveau compte.** Chaque activation génère un nouveau mot de
  passe (et, si un compte démo existait déjà, le supprime d'abord). Impossible
  de récupérer un mot de passe déjà affiché : il faut réactiver pour en obtenir
  un nouveau.
- **Best-effort à la purge.** La suppression `DELETE ... WHERE is_demo = 1` est
  entourée d'un `try/catch` par table : une table absente (module jamais activé,
  migration jamais jouée) n'interrompt pas le reste du nettoyage.
- **Bornée aux modules actifs.** Si un module est désactivé *après* l'activation
  du mode démo, ses données factices restent en base (marquées `is_demo`) mais
  invisibles, comme n'importe quelle donnée d'un module désactivé — la
  désactivation du mode démo les nettoie normalement, qu'importe l'état du
  module à ce moment-là.
- **Collision de slug, cas limite non géré.** Les pages/événements/actualités
  factices utilisent des slugs fixes (ex. `accueil-demo`) ; si une vraie page
  porte déjà ce slug, l'activation échoue sur la contrainte d'unicité. Cas très
  improbable en pratique, pas de retry automatique en v1.
