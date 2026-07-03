# Module Horaires d'ouverture

## Rôle

Gère les **horaires d'ouverture** de l'établissement : une plage horaire (ou
fermé) pour chacun des 7 jours de la semaine, plus une liste de **fermetures
exceptionnelles** (congés, jours fériés…). L'affichage sur le site se fait
exclusivement via le bloc dynamique `horaires`, à poser dans le contenu d'une
page.

## Données

Pas de table dédiée : état stocké dans `storage/horaires.json`
([`HorairesSettings`](../../app/Modules/Horaires/HorairesSettings.php)), non
versionné — même approche que `storage/google_reviews.json` ou
`storage/demo.json`, une donnée d'admin simple ne justifie pas de migration.

```json
{
  "weekly": {
    "lundi": {"closed": false, "open": "08:30", "close": "18:00"},
    "...": "... (7 jours, clés ASCII : lundi..dimanche)"
  },
  "exceptions": [
    {"start": "2026-12-25", "end": "2026-12-25", "label": "Noël"}
  ]
}
```

## Routes

Déclarées dans `HorairesModule::boot()`.

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/horaires`             | `index`          | `horaires.view` |
| POST | `/admin/horaires/weekly`      | `saveWeekly`      | `horaires.manage` + CSRF |
| POST | `/admin/horaires/exceptions`  | `saveExceptions`  | `horaires.manage` + CSRF |

Aucune route front : l'affichage passe exclusivement par le bloc.

## Blocs fournis

- **`horaires`** (dynamique : son `render()` lit `HorairesSettings` en live,
  comme le bloc Avis Google lit sa base). Données : `{ "title": string }` — le
  bloc ne configure que le titre affiché ; les horaires eux-mêmes se gèrent
  depuis l'écran dédié.
- Les jours consécutifs ayant les mêmes horaires sont fusionnés à l'affichage
  (ex. « Lundi – Vendredi »). Les fermetures exceptionnelles **passées**
  (date de fin < aujourd'hui) ne sont plus affichées.

## Permissions

| Permission | Détenue par | Rôle |
|------------|-------------|------|
| `horaires.view`   | `admin`, `manager` | Consulter l'écran |
| `horaires.manage` | `admin`, `manager` | Modifier les horaires hebdomadaires et les fermetures exceptionnelles |

## Config

- [`config/modules.php`](../../config/modules.php) : `Horaires` dans
  `available`. Aucune dépendance de module (le bloc n'apparaît dans l'éditeur
  que si Pages est actif, mais l'admin du module vit seul).
- [`config/roles.php`](../../config/roles.php) : `horaires.view` /
  `horaires.manage` en liste explicite pour `manager` (pas de
  `horaires.*` qui n'apporterait rien ici, les deux permissions couvrent déjà
  tout l'écran).

## Notes / pièges

- **Fermetures exceptionnelles resoumises en entier.** Le formulaire renvoie
  toute la liste à chaque sauvegarde (pas de route de suppression unitaire) :
  liste assez courte pour que ce ne soit pas un problème, et ça évite un jeu
  d'identifiants à gérer côté client comme côté serveur.
- **Dates inversées corrigées automatiquement.** Si la date de fin saisie est
  antérieure à la date de début, `HorairesSettings` les échange avant
  d'enregistrer.
