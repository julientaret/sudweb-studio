<?php
/**
 * Réalisations — page statique (servie sur « /realisations »).
 *
 * Intégration de la maquette design/Sud Web Studio - Realisations.html :
 * en-tête de page, barre de filtres par catégorie, puis une ligne par projet
 * en alternance visuel / texte.
 *
 * Les projets vivent dans le tableau $projets ci-dessous. Le module
 * Realisations du CMS ne porte aujourd'hui ni catégorie, ni URL, ni année, ni
 * tags : le brancher demanderait une migration et une reprise de son éditeur.
 */

$title = 'Réalisations';
$description = 'Sélection de sites web réalisés par Sud Web Studio à Villeneuve-sur-Lot '
    . 'et dans le Sud-Ouest : sites vitrines, web sur-mesure et sites événementiels.';

require dirname(__DIR__) . '/includes/header.php';

/*
 * Projets et catégories : source unique partagée avec l'accueil
 * (frontend/config/realisations.php).
 */
$data       = (array) require dirname(__DIR__) . '/config/realisations.php';
$categories = (array) $data['categories'];
$projets    = (array) $data['projets'];

/** Nombre de projets par catégorie, pour les compteurs des filtres. */
$counts = array_count_values(array_column($projets, 'cat'));

/** Compteur à deux chiffres, comme dans la maquette (« 04 »). */
$pad = static fn (int $n): string => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
?>

<main id="contenu">

    <!-- ============================ EN-TÊTE ============================ -->
    <section class="work-hero page-hero">
        <div class="shell">
            <span class="eyebrow reveal">Réalisations</span>
            <h1>
                <span class="line-mask"><span>Des projets</span></span>
                <span class="line-mask"><span><em>bien réels</em></span></span>
            </h1>
            <p class="lead reveal">Chaque site que nous livrons répond à un besoin concret&nbsp;: plus de
                visibilité, plus de contacts, une image à la hauteur du métier. Voici une
                <b>sélection de réalisations</b> menées pour des entreprises du Sud-Ouest et d'ailleurs.</p>

            <div class="wh-meta reveal">
                <div class="stat"><b>100%</b><span>Sur-mesure</span></div>
                <span class="sep" aria-hidden="true"></span>
                <div class="stat"><b>47</b><span>Lot-et-Garonne</span></div>
            </div>
        </div>
    </section>

    <?php /* Filtres et liste partagent un conteneur : un élément collant est borné
             par son parent, sans quoi la barre resterait épinglée jusqu'au bas
             de la page, par-dessus le CTA. */ ?>
    <div class="work-browse">

    <!-- ============================ FILTRES ============================ -->
    <div class="filters">
        <div class="shell" role="group" aria-label="Filtrer par catégorie">
            <button class="filter-btn active" type="button" data-filter="all" aria-pressed="true">
                Tous <span class="n"><?= $pad(count($projets)) ?></span>
            </button>
            <?php foreach ($categories as $key => $label): ?>
            <?php if (empty($counts[$key])) { continue; } ?>
            <button class="filter-btn" type="button" data-filter="<?= e($key) ?>" aria-pressed="false">
                <?= e($label) ?> <span class="n"><?= $pad($counts[$key]) ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ============================ PROJETS ============================ -->
    <section class="work-list" aria-label="Projets">
        <div class="shell">
            <?php foreach ($projets as $i => $projet):
                // Une ligne sur deux inverse visuel et texte.
                $rev = $i % 2 === 1;
                // L'étiquette du cadre reprend le domaine, sans « www. ».
                $host = preg_replace('#^www\.#', '', (string) parse_url($projet['url'], PHP_URL_HOST));
            ?>
            <article class="proj<?= $rev ? ' rev' : '' ?>" data-cat="<?= e($projet['cat']) ?>">
                <div class="proj-visual">
                    <div class="frame">
                        <div class="bar" aria-hidden="true">
                            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                            <span class="url"><?= e($host) ?></span>
                        </div>
                        <div class="shot-wrap">
                            <img class="shot" src="<?= e(asset_url('/assets/img/' . $projet['image'] . '.webp')) ?>"
                                 alt="Aperçu du site <?= e($projet['title']) ?>" loading="lazy">
                        </div>
                    </div>
                </div>
                <div class="proj-body">
                    <span class="proj-cat"><?= e($categories[$projet['cat']] ?? '') ?></span>
                    <h2><?= e($projet['title']) ?></h2>
                    <p class="desc"><?= $projet['desc'] ?></p>
                    <ul class="proj-tags">
                        <?php foreach ($projet['tags'] as $tag): ?>
                        <li><?= e($tag) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="proj-foot">
                        <a class="proj-link" href="<?= e($projet['url']) ?>" target="_blank" rel="noopener">
                            Visiter le site <span class="ico" aria-hidden="true">↗</span>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>

            <?php /* Affiché par le filtre quand une catégorie ne renvoie rien. */ ?>
            <p class="work-empty" data-work-empty hidden role="status">
                Aucune réalisation dans cette catégorie pour le moment.
            </p>
        </div>
    </section>

    </div><!-- /.work-browse -->

    <?php
    $ctaEyebrow = 'Votre projet, la suite&nbsp;?';
    $ctaTitle   = 'Et si le prochain était le vôtre&nbsp;?';
    $ctaText    = 'Racontez-nous votre activité et vos objectifs&nbsp;: on imagine ensemble le site qui vous ressemble.';
    require dirname(__DIR__) . '/includes/cta.php';
    ?>

</main>

<?php require dirname(__DIR__) . '/includes/footer.php';
