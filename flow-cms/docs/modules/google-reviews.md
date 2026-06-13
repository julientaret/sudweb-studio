# Module Avis Google

## Rôle

Importe les **avis Google** d'un établissement et les affiche sur le site. La
synchronisation est **manuelle** (clic sur un bouton dans l'admin) : à chaque
appel, les **5 avis les plus récents** sont récupérés via l'API Google Places,
puis empilés en base avec déduplication. L'admin choisit ensuite, avis par avis,
lesquels sont affichés sur le front via le bloc dynamique `google_reviews`.

L'établissement suivi se choisit **directement dans l'admin** : un champ
« Rechercher l'établissement » interroge l'API Find Place, propose les
candidats (nom + adresse), et le `place_id` du candidat cliqué est stocké dans
`storage/google_reviews.json` (même approche que `storage/modules.json`).
Aucun `place_id` à coller en config au déploiement.

## Données

Migration [`001_create_google_reviews.sql`](../../app/Modules/GoogleReviews/migrations/001_create_google_reviews.sql).

- **`google_reviews`** : `id`, `fingerprint` (CHAR(40), unique — empreinte de
  dédup `sha1(auteur + '#' + review_time)`), `author_name`, `author_url`,
  `profile_photo_url`, `rating` (1 à 5), `text` (peut être vide), `language`,
  `relative_time` (libellé « il y a 2 mois » fourni par Google), `review_time`
  (timestamp Unix, sert au tri), `is_visible` (curation admin — **préservé entre
  les synchros**), `synced_at`.
- Index `idx_visible_time (is_visible, review_time)` pour le rendu front.

## Routes

Déclarées dans `GoogleReviewsModule::boot()`.

| Méthode | Chemin | Action | Protection |
|---------|--------|--------|------------|
| GET  | `/admin/google-reviews`        | `index`       | `googlereviews.view` (la recherche `?q=` exige en plus `googlereviews.configure`) |
| POST | `/admin/google-reviews/place`  | `selectPlace` | `googlereviews.configure` + CSRF |
| POST | `/admin/google-reviews/sync`   | `sync`        | `googlereviews.sync` + CSRF |
| POST | `/admin/google-reviews/toggle` | `toggle`      | `googlereviews.manage` + CSRF |
| POST | `/admin/google-reviews/purge`  | `purge`       | `googlereviews.manage` + CSRF |

Aucune route front : l'affichage passe exclusivement par le bloc.

## Blocs fournis

- **`google_reviews`** (dynamique, brief §4 : son `render()` requête la base en
  live). Données : `{ "title": string, "limit": int }` (6 par défaut, 20 max).
  Rend les avis `is_visible = 1`, du plus récent au plus ancien. Markup neutre
  (classes `gr-*`) stylé par le thème.
- Le libellé éditeur est porté par le bloc lui-même (`Block::label()`), enregistré
  dans `GoogleReviewsModule::boot()` ; si le module est désactivé, le bloc disparaît
  simplement de l'éditeur et du rendu (l'éditeur construit son catalogue depuis le
  registre).

## Permissions

| Permission | Détenue par | Rôle |
|------------|-------------|------|
| `googlereviews.view`      | `admin`, `manager` | Consulter la liste |
| `googlereviews.sync`      | `admin`, `manager` | Lancer une synchro (le `manager` est limité à 1×/jour, voir ci-dessous) |
| `googlereviews.manage`    | `admin`, `manager` | Afficher/masquer un avis, vider la table |
| `googlereviews.configure` | `admin` seul | Choisir l'établissement (recherche Find Place) **et** synchroniser sans limite de fréquence |

`googlereviews.configure` est **réservé à l'agence** : seul le rôle `admin` le
détient (via le wildcard `*` de [`config/roles.php`](../../config/roles.php)). Le
`manager` reçoit `googlereviews.view/sync/manage` en **liste explicite** — surtout
pas un `googlereviews.*`, qui couvrirait aussi `configure`. Concrètement, le
client peut curer et resynchroniser ses avis mais ne touche pas au choix de
l'établissement, et il reste soumis au verrou d'une synchro par jour.

## Config

- [`config/modules.php`](../../config/modules.php) : `GoogleReviews` dans
  `available`. Aucune dépendance de module (le bloc n'apparaît dans l'éditeur
  que si Pages est actif, mais l'admin du module vit seul).
- [`config/google_reviews.php`](../../config/google_reviews.php), depuis
  `.env.local` : `GOOGLE_PLACES_API_KEY` — clé API Google Places (facturation
  activée sur le projet GCP ; le crédit mensuel couvre largement un site).
- L'établissement (place_id + nom + adresse pour réaffichage) vit dans
  `storage/google_reviews.json`
  ([`GoogleReviewsSettings`](../../app/Modules/GoogleReviews/GoogleReviewsSettings.php)),
  non versionné — état choisi dans l'admin, pas une constante serveur.

## Notes / pièges

- **API legacy assumée.** On utilise *Place Details (legacy)* car c'est le seul
  endpoint exposant `reviews_sort=newest`. La v1 ne trie pas les avis. Si Google
  retire la legacy, basculer sur la v1 = perte du tri par date. La recherche
  d'établissement passe par *Find Place from Text* (legacy aussi), champs Basic
  uniquement (`place_id,name,formatted_address`) : gratuit.
- **Changer d'établissement ne purge pas la table.** Les avis déjà importés du
  précédent établissement restent en base (masquables à la main). Utiliser le
  bouton **« Vider la table »** avant la première synchro du nouveau.
- **« Vider la table » lève le verrou de synchro.** Une purge suivie d'une
  resynchro immédiate doit rester possible (sinon site sans avis jusqu'au
  lendemain) ; c'est aussi le moyen assumé de contourner le verrou pour un
  `manager`. La curation (`is_visible`) est perdue avec la purge.
- **5 avis max par appel.** Limite dure de l'API. La table grossit dans le temps
  via les synchros successives + dédup ; on ne récupère jamais l'historique
  antérieur à la première synchro.
- **Une synchro par jour (manager).** Verrou appliqué côté serveur (horodatage
  `last_sync` dans `storage/google_reviews.json`), le bouton est aussi désactivé
  dans l'admin jusqu'au lendemain. Une synchro **en échec** (erreur API) ne
  consomme pas le quota du jour. L'**agence** (`googlereviews.configure`) n'est
  pas concernée : elle resynchronise à volonté.
- **Curation préservée.** L'upsert ne réécrit jamais `is_visible` : un avis
  masqué le reste après resynchro. Un nouvel avis arrive visible par défaut
  (changer le `1` en dur dans `upsertMany()` pour modérer avant publication).
- **CGU Google.** Le stockage des avis n'est toléré que sur une durée limitée.
  Resynchroniser régulièrement plutôt que de figer la base indéfiniment.
- **Dédup par empreinte.** Si un utilisateur édite son avis, `review_time` peut
  changer → l'avis édité est alors traité comme un nouveau. Acceptable en
  pratique.
- **Avatars non affichés.** `profile_photo_url` est stocké mais ni l'admin ni le
  bloc ne l'affichent : la CSP du front (`img-src 'self' data:`) bloquerait les
  URLs Google. À élargir si besoin.
