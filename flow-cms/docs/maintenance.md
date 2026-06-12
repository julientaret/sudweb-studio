# Mode maintenance

Fonction **transverse** (niveau Core, pas un module du catalogue) : ferme le site
public le temps d'une intervention, avec deux mécanismes de contournement
(bypass) — une URL secrète à jeton et une liste d'IP autorisées.

## Rôle

Quand le mode est actif, **tout le site public** (frontend) renvoie une page de
maintenance en **HTTP 503** : redirections, pages statiques écrites à la main et
pages dynamiques du CMS sont toutes interceptées. L'**administration**
(`flow-cms`) reste toujours joignable — le garde-fou ne vit que dans le front
controller public, jamais dans celui de l'admin.

Pourquoi **Core et non module** : un interrupteur qui pilote la disponibilité du
site ne doit pas pouvoir se désactiver lui-même via le catalogue des modules
(onglet Administration), et le garde-fou frontend doit fonctionner indépendamment
de l'état d'activation des modules. Même raisonnement que l'onglet Administration.

## Fonctionnement

Garde-fou posé dans [`frontend/public/index.php`](../../frontend/public/index.php)
**avant toute résolution** (redirections, statiques, dynamiques), via
`MaintenanceSettings::enforce()` :

1. mode inactif → ne fait rien ;
2. l'URL porte le **jeton de bypass** valide (`?acces=<jeton>`) → enregistre l'IP
   du visiteur, pose le cookie `flow_bypass`, puis **redirige vers l'URL propre**
   (jeton retiré) ;
3. visiteur **autorisé** (IP listée **ou** cookie au jeton courant) → laisse passer ;
4. sinon → **page de maintenance (503)** + en-têtes `Retry-After: 3600` et
   `Cache-Control: no-store`, puis arrêt de la requête.

### URL de contournement (bypass)

Forme : `https://<site>/?acces=<jeton>`. Le **jeton** (« hash ») est un aléa de
32 caractères hexadécimaux (`bin2hex(random_bytes(16))`), généré et persisté à la
première lecture — l'URL existe donc dès l'ouverture de l'onglet, avant même
toute activation. Le bouton **« Régénérer le jeton »** en crée un nouveau et
**invalide l'ancienne URL** (et les cookies déjà posés).

Visiter une URL de bypass valide **ajoute l'IP du visiteur** à la liste autorisée
et pose un cookie `flow_bypass` (30 jours, `HttpOnly`, `SameSite=Lax`,
`Secure` en HTTPS) : utile si l'IP du visiteur change ensuite.

### Adresses IP autorisées

Liste d'IP qui contournent la maintenance **sans jeton**. Alimentée
automatiquement à chaque bypass réussi, et complétable/épurable à la main dans
l'admin (l'IP courante du gestionnaire est pré-remplie). Validation **IPv4 et
IPv6** (`filter_var(... FILTER_VALIDATE_IP)`), dédoublonnée.

### Page de maintenance

Une page intégrée sobre (503, `noindex`) est rendue par défaut. Le site peut la
**personnaliser** en déposant un fichier `frontend/maintenance.php` : s'il existe,
il est utilisé à la place de la page intégrée.

## État (storage)

Stocké dans `storage/maintenance.json`
([`MaintenanceSettings`](../../app/Core/MaintenanceSettings.php)), non versionné —
un état d'admin, pas une constante serveur. Même approche que
`storage/modules.json`.

```json
{
    "enabled": true,
    "token": "c6d1b1f88a61ec3fd41a1d9e4b8a8949",
    "ips": ["203.0.113.4", "2001:db8::1"]
}
```

## Routes

Déclarées dans [`Kernel::handleWeb()`](../../app/Core/Kernel.php) (niveau Core),
servies par [`MaintenanceController`](../../app/Core/MaintenanceController.php).

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/maintenance`             | `index`      | `admin.maintenance` |
| POST | `/admin/maintenance/toggle`      | `toggle`     | `admin.maintenance` + CSRF |
| POST | `/admin/maintenance/regenerate`  | `regenerate` | `admin.maintenance` + CSRF |
| POST | `/admin/maintenance/ip/add`      | `addIp`      | `admin.maintenance` + CSRF |
| POST | `/admin/maintenance/ip/remove`   | `removeIp`   | `admin.maintenance` + CSRF |

## Permissions

| Permission | Détenue par | Rôle |
|------------|-------------|------|
| `admin.maintenance` | `admin` seul | Tout l'onglet Maintenance |

`admin.maintenance` n'est couvert que par le wildcard `*` du rôle `admin` dans
[`config/roles.php`](../../config/roles.php). Les rôles `manager` et `editor` ne
voient ni l'entrée de menu, ni la rubrique du tutoriel, et reçoivent 403 sur les
routes.

## Notes / pièges

- **L'admin n'est jamais bloquée.** Le garde-fou est dans le front controller du
  site public uniquement ; celui de l'admin (`flow-cms/public/index.php`) ne
  l'appelle pas. Impossible donc de s'enfermer dehors.
- **Penser à désactiver.** Tant que le mode est actif, les visiteurs dont l'IP
  n'est pas listée voient la page de fermeture. Rappel affiché dans le tutoriel.
- **Régénérer = tout invalider.** Un nouveau jeton casse l'ancienne URL *et* les
  cookies `flow_bypass` déjà distribués. Les IP déjà listées, elles, restent
  valables (elles ne dépendent pas du jeton).
- **IP derrière proxy/CDN.** L'IP retenue est `REMOTE_ADDR`. Derrière un reverse
  proxy ou un CDN, c'est l'IP du proxy qui est vue — adapter si une telle
  infrastructure est en place (lecture d'un en-tête de confiance type
  `X-Forwarded-For`).
- **503 volontaire.** Le code HTTP et `Retry-After` indiquent aux moteurs une
  indisponibilité **temporaire** : pas de déréférencement. La page porte aussi
  `noindex`.
