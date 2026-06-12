# Documentation de Flow

Flow est un CMS modulaire en PHP vanilla, édité par **Sud Web Studio**.

- **Socle, conventions et règles de dev** : voir [flow-brief.md](../flow-brief.md)
  à la racine du dépôt. C'est le document fondateur (philosophie, arborescence,
  système de modules, blocs, médias, sécurité, permissions).
- **Une fiche par module** dans [`modules/`](modules/), suivant le template du §9
  du brief.

> Règle de *definition of done* (brief §1/§9) : une partie de Flow n'est terminée
> que lorsque sa fiche de doc est écrite et à jour. La doc se construit **au fil de
> l'eau**, en même temps que le code.

## État actuel

Fondations posées :

| Composant | Rôle | Source |
|-----------|------|--------|
| `App\Core\Kernel` | Démarrage (config, session, headers, modules, dispatch) | [app/Core/Kernel.php](../app/Core/Kernel.php) |
| `App\Core\Config` | Env (`.env.local`) + fichiers `/config` | [app/Core/Config.php](../app/Core/Config.php) |
| `App\Core\Database` | PDO singleton (requêtes préparées) | [app/Core/Database.php](../app/Core/Database.php) |
| `App\Core\Router` | Routeur minimal (matching exact) | [app/Core/Router.php](../app/Core/Router.php) |
| `App\Core\View` | Templating PHP + `e()` | [app/Core/View.php](../app/Core/View.php) |
| `App\Core\Csrf` | Token CSRF par session | [app/Core/Csrf.php](../app/Core/Csrf.php) |
| `App\Core\Auth` | Login, sessions, anti-brute-force, `can()` | [app/Core/Auth.php](../app/Core/Auth.php) |
| `App\Core\Migrator` | Migrations SQL idempotentes | [app/Core/Migrator.php](../app/Core/Migrator.php) |
| `App\Core\ModuleRegistry` | Scanne et démarre les modules | [app/Core/ModuleRegistry.php](../app/Core/ModuleRegistry.php) |
| `App\Core\BlockRegistry` | Registre des blocs (contrat posé) | [app/Core/BlockRegistry.php](../app/Core/BlockRegistry.php) |
| `App\Core\BlockContent` | Base commune « contenu » du page-builder (build/normalise/rendu/éditeur) partagée par les modules de contenu | [app/Core/BlockContent.php](../app/Core/BlockContent.php) |
| `App\Core\DashboardController` | Accueil admin (`/admin`) : accès rapide + dernières modifs de contenu (Pages + Agenda + Actualités, agrégées) + visionneuse galerie | [app/Core/DashboardController.php](../app/Core/DashboardController.php) |
| `App\Core\TutorialController` | Page tutoriel (`/admin/tutoriel`) : prise en main du CMS (tableau de bord, pages, agenda, médias, utilisateurs) | [app/Core/TutorialController.php](../app/Core/TutorialController.php) |
| `App\Core\ModuleSettings` | Activation des modules pilotée depuis l'admin : catalogue (`config/modules.php` → `available`) − désactivés (`storage/modules.json`), dépendances en cascade, `Users` protégé | [app/Core/ModuleSettings.php](../app/Core/ModuleSettings.php) |
| `App\Core\AdministrationController` | Onglet Administration (`/admin/administration`, permission `admin.modules`, rôle admin seul) : choix des modules disponibles pour les autres utilisateurs | [app/Core/AdministrationController.php](../app/Core/AdministrationController.php) |

Modules :

- **Users** — [modules/users.md](modules/users.md)
- **Media** — [modules/media.md](modules/media.md)
- **Documents** — [modules/documents.md](modules/documents.md)
- **Pages** — [modules/pages.md](modules/pages.md)
- **Agenda** — [modules/agenda.md](modules/agenda.md)
- **Actualités** — [modules/actualites.md](modules/actualites.md)
- **Avis Google** — [modules/google-reviews.md](modules/google-reviews.md)

## Mise en route (dev)

```bash
composer install            # ou: composer dump-autoload
cp .env.example .env.local  # puis renseigner DB_*, secrets, etc. (jamais versionné)
# créer la base déclarée dans .env.local (DB_NAME), puis :
php bin/flow migrate
php bin/flow user:create admin@flow.test motdepasse8 admin "Admin"
php -S localhost:8000 -t public
# → http://localhost:8000/  (redirige vers /admin/login)
```

## Déploiement (serveurs PHP)

Aller **sur le dossier de Flow mène directement à l'outil** : la racine `/`
redirige vers `/admin` si une session est active, sinon vers `/admin/login`.
Après connexion, on arrive sur le **tableau de bord** (`/admin`).

Deux configurations possibles :

1. **Idéal** — la racine du vhost pointe sur `public/`. `base_path` auto-détecté = vide.
2. **Sous-dossier / mutualisé** — on accède au dossier du projet
   (ex. `http://host/flow-cms/`). Le [`.htaccess`](../.htaccess) racine renvoie
   toutes les requêtes dans `public/` ; `app/`, `config/`, `storage/` restent hors
   contenu servi. `base_path` est auto-détecté (`/flow-cms`).

`base_path` est déduit de `SCRIPT_NAME` au démarrage. Pour forcer une valeur,
renseigner `base_path` dans [config/app.php](../config/app.php).
