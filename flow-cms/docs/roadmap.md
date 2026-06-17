# Flow — Améliorations futures (roadmap)

> Analyse des évolutions possibles du CMS, classées par priorité.
> Flow est un CMS modulaire **PHP vanilla, zéro dépendance runtime** (Composer
> pour l'autoload uniquement), architecturalement très propre : noyau unique,
> modules autonomes avec leurs migrations, séparation front public / admin,
> RGPD-first, sécurité soignée (CSRF, géo-filtrage admin, anti-brute-force).
>
> Les manques identifiés sont surtout **transverses** (outillage de fiabilité)
> et quelques capacités produit absentes — pas dans la qualité des modules.

---

## 🔴 Priorité haute — fiabilité & socle

### 1. Tests automatisés + CI
Le manque le plus structurant : aucun test, aucun `.github/workflows`. Pour un
CMS multi-client, c'est le plus gros risque de régression.

- PHPUnit sur le noyau : `Router`, `Csrf`, `Auth`/permissions, `Migrator`,
  `Mailer` (anti-injection d'en-têtes), `IpGeoGuard`. Reste dans l'esprit
  « Composer = dev only ».
- Workflow GitHub Actions : `composer install`, lint PHP (`php -l`), PHPUnit,
  et `npm run build` côté frontend.
- Tests de smoke sur les routes admin (403 sans permission, 200 avec).

### 2. Logging structuré
Aujourd'hui : `error_log()` éparpillé dans plusieurs fichiers du noyau
(`Database`, `ModuleRegistry`, `View`, `BlockRegistry`, `Kernel`).

- Petit `Logger` Core (toujours zéro-dépendance, fichiers JSON-lines avec
  rotation), sans aller jusqu'à Monolog.
- Traçabilité réelle : erreurs DB, échecs d'envoi mail, tentatives de connexion
  bloquées.

### 3. Sauvegarde & export
Aucune commande de backup aujourd'hui.

- Commande CLI `php flow-cms/bin/flow backup` : dump SQL + archive `uploads/`
  et `documents/`.
- Sécurise les déploiements et les migrations client.

---

## 🟠 Priorité moyenne — produit

### 4. Versionnage / brouillons des Pages
Le page-builder n'a pas de révisions ni d'état brouillon/publié visible. C'est
ce que les clients attendent en premier.

- Statut `draft` / `published`.
- Prévisualisation avant publication.
- Historique des versions avec rollback.

### 5. Enrichir la bibliothèque de blocs
Le noyau n'expose que `HeadingBlock` et `RichtextBlock`. Axe à plus fort impact
perçu côté éditeur.

- Blocs à ajouter : galerie, bouton/CTA, colonnes, citation, vidéo/embed,
  accordéon.

### 6. SEO & cache
- **SEO** : gestion par page (override meta title/description, OpenGraph),
  `sitemap.xml` auto, `robots.txt`.
- **Cache** : aucun aujourd'hui — chaque page dynamique tape la DB. Cache HTML
  simple par slug (invalidé à la sauvegarde) + en-têtes `Cache-Control` / `ETag`
  sur assets et médias → meilleur TTFB.

### 7. Recherche & médias
- Recherche dans l'admin (utile dès que Pages / Actualités / Documents
  grossissent).
- Module Media (images via GD seulement aujourd'hui) : miniature PDF, alt text
  obligatoire (accessibilité + SEO), tags / dossiers.

---

## 🟡 Priorité basse — évolutions de fond

### 8. Rôles & permissions en base
Déjà prévu dans le code (`config/roles.php` : « le jour où un client veut des
rôles sur mesure, on bascule ce mapping vers la DB »). La mécanique
`module.action` est prête — à faire quand un client le demande.

### 9. API JSON / mode headless
Aucune couche API. Si un usage front découplé (JS, mobile) émerge : exposer
Pages / Actualités / Agenda en lecture JSON.

### 10. i18n / multilingue
Aucune trace de traduction. Pertinent uniquement pour un client multilingue —
gros chantier, à ne pas anticiper sans besoin.

### 11. Accessibilité (RGAA)
Cohérent avec le positionnement RGPD / qualité : audit a11y du front généré
(contrastes, focus, alt obligatoires, ARIA).

---

## Séquencement recommandé

1. **Socle de fiabilité (1 → 3)** : invisible pour le client, mais rend les
   évolutions suivantes sûres.
2. **Brouillons / versions de pages (4)** et **nouveaux blocs (5)** : les
   demandes client les plus probables.
3. Le reste **au besoin** (8 → 11), selon les demandes réelles.
