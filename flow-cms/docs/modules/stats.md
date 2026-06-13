# Module Statistiques

## Rôle

Fournit une **mesure d'audience légère** du site public : pages vues, visiteurs
uniques par jour, sources de trafic et répartition par appareil, présentées dans
un **tableau de bord d'admin**. Conçu **conforme RGPD sans bandeau de
consentement** : collecte **server-side**, **sans cookie ni service tiers**, IP
et User-Agent **jamais stockés en clair**, purge automatique au-delà du délai de
conservation.

Aucune dépendance à un autre module (pas de blocs, pas de média, aucune route
front).

## Cadre RGPD (exemption « mesure d'audience » CNIL)

Le module reste dans le périmètre que la CNIL **exempte de consentement** :

- **aucun cookie ni traceur** posé pour mesurer l'audience ;
- **aucun service tiers** (pas de Google Analytics, pas de pixel, pas de JS) ;
- **pas de suivi inter-sites** ni de profil individuel réutilisable ;
- **IP anonymisée** : elle n'entre que dans un hash non réversible ;
- **données agrégées** et **conservation limitée** (13 mois par défaut,
  recommandation CNIL).

Cohérent avec la posture privacy-first du projet (voir aussi le module Contact).

## Parcours

1. À chaque page servie par le site public, le **front controller**
   [`frontend/public/index.php`](../../../frontend/public/index.php) appelle
   `StatsCollector::record($slug)` — **après** le filtre des redirections, donc
   pour les pages **statiques ET dynamiques** (mais pas les 301/302).
2. `StatsCollector` applique les exclusions puis enregistre une ligne via
   `StatsRepository`. Tout est **best-effort** (try/catch) : une stat ratée ne
   casse jamais l'affichage.
3. L'admin consulte `/admin/stats` (permission `stats.view`).

> La collecte vit dans le front controller car les **pages statiques**
> court-circuitent le routage des modules ; c'est le seul point traversé par
> toutes les requêtes de page. Le site public reste **sans état** : aucune
> session n'est démarrée, aucun cookie n'est posé.

## Empreinte visiteur (dédoublonnage sans pistage)

`visitor_hash = sha256(IP + User-Agent + sel_quotidien + secret_serveur)`

Le **sel quotidien** (date du jour) fait que l'empreinte d'un même visiteur
**change chaque jour** : impossible de le suivre dans le temps ou de reconstituer
un profil. Elle ne sert qu'à compter les **visiteurs uniques d'une même
journée**. Le secret serveur est `APP_SECRET` (sinon dérivé de la config DB),
comme pour le `ip_hash` du module Contact.

> Conséquence assumée : le total « visiteurs uniques » sur une période est le
> **cumul des uniques quotidiens** (exact sur une journée). C'est le compromis
> normal du privacy-first sans cookie.

## Exclusions

| Cible | Fiabilité | Méthode |
|-------|-----------|---------|
| **Admin / back-office** | ~100 % | front controller distinct (`/flow-cms`) — jamais traversé |
| **Membre connecté** sur le site public | ~100 % | présence du cookie de session admin (`stats.exclude_admins`) |
| **IP de l'agence/bureau** | exacte | `stats.exclude_ips` (comparée en clair à `REMOTE_ADDR`, non stockée) |
| **Robots déclarés** | ~95 % | motifs de User-Agent (`stats.bot_user_agents`) + UA vide |
| **Assets** | exacte | extensions de fichier écartées (et servis par Apache, hors `index.php`) |
| Requêtes non-GET | exacte | seules les consultations comptent |

Le filtrage des bots est **best-effort** — vrai de tout outil de stats, y compris
GA/Matomo : un robot masquant son User-Agent ne peut être écarté avec certitude.

## Données

Migration [`001_create_page_views.sql`](../../app/Modules/Stats/migrations/001_create_page_views.sql).

- **`page_views`** : `id`, `path`, `visitor_hash` (**empreinte SHA-256 à sel
  quotidien**, jamais d'IP en clair), `referer_host` (**hôte du référent
  uniquement**, jamais l'URL ni le query-string ; null si accès direct/interne),
  `device` (`desktop`|`mobile`|`tablet`|`other`), `viewed_at`, `viewed_on`.

## Routes

Déclarée dans `StatsModule::boot()`.

- `GET /admin/stats` — tableau de bord (`stats.view`). Paramètre `?days=` parmi
  `7|30|90|365` (défaut 30).

## Tableau de bord

[`views/dashboard.php`](../../app/Modules/Stats/views/dashboard.php) — rendu
**100 % server-side**, graphes en **CSS pur** (aucun JavaScript ni librairie
tierce, cohérent avec le « zéro dépendance runtime »).

- **KPIs** : pages vues et visiteurs uniques sur la période sélectionnée.
- **Mois en cours** : petit graphique jour par jour, du 1er du mois à aujourd'hui
  (indépendant du sélecteur de période) — `StatsController::currentMonthSeries`.
  Le libellé du mois est francisé sans dépendre de la locale serveur
  (`frenchMonthLabel`).
- **Fréquentation par jour** : barres sur la période (`fillDailySeries` comble
  les jours sans vue à 0 pour un graphe régulier).
- **Pages les plus vues**, **sources de trafic** (hôtes référents), **appareils**
  (barres de répartition).

## Tutoriel

Une rubrique **« Les statistiques »** est intégrée au tutoriel admin
([`app/Core/views/tutorial.php`](../../app/Core/views/tutorial.php)), en langage
non technique (vie privée, qui est compté, estimation des visiteurs uniques).
Elle n'apparaît que si le module Stats est actif (clé `stats` →
`Stats` dans `$sectionModule`).

## Configuration — [`config/stats.php`](../../config/stats.php)

| Clé | Défaut | Rôle |
|-----|--------|------|
| `retention_months` | `13` | purge au-delà (0 = désactivée) |
| `exclude_admins` | `true` | ne pas compter un membre connecté |
| `exclude_ips` | `[]` | IPs à ignorer (agence, bureau…) |
| `bot_user_agents` | liste | motifs de User-Agent considérés robots |

## Permissions

- `stats.view` — `admin` (via `*`) et `manager`. Le rôle `editor` n'y a pas accès.

## Conservation (purge RGPD)

Deux déclencheurs :

- **opportuniste** : à chaque ouverture de `/admin/stats`
  (`StatsController::purgeExpired`) ;
- **planifiable** : `bin/flow stats:purge` (à mettre en cron quotidien).

Les deux suppriment les vues plus anciennes que `retention_months`.

## Désactivation

Retirer **Stats** depuis l'onglet Administration coupe la collecte
(`StatsCollector::record` devient un no-op via `modules.enabled`) et le menu, sans
toucher aux données déjà en base.
