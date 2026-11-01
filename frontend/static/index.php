<?php
/**
 * Accueil — page statique (servie sur « / »).
 *
 * Intégration de la maquette « Landing Alt » (design/Sud Web Studio - Landing
 * Alt.html). Les sections sont dans l'ordre : hero, expertises, ancrage local,
 * clientèle, bandeau défilant, réalisations, CTA.
 *
 * Le contenu vit ici volontairement : ce sont des pages de marque, écrites une
 * fois, pas du contenu éditorial géré dans l'admin.
 */

$title = 'Création de sites web à Villeneuve-sur-Lot (47)';
$description = 'Sud Web Studio, studio de développement web à Villeneuve-sur-Lot '
    . '(Lot-et-Garonne) : création de sites internet, sites sur mesure, référencement, '
    . 'hébergement et applications mobiles.';

require dirname(__DIR__) . '/includes/header.php';

/** Bulles flottantes du hero : libellé, position et diamètre (cf. maquette). */
$bubbles = [
    ['Site internet',       'top:2%;left:-8%',     108],
    ['Site sur mesure',     'top:18%;right:-11%',  124],
    ['Référencement',       'top:40%;left:-14%',   132],
    ['Hébergement',         'bottom:24%;right:-13%', 118],
    ['Web Design',          'bottom:4%;left:1%',   104],
    ['Apps mobiles',        'bottom:0%;right:4%',  126],
    ['Villeneuve-sur-Lot',  'top:0%;right:27%',    110],
    ['Lot-et-Garonne',      'bottom:-6%;left:34%', 112],
    ['Agen',                'top:-4%;left:22%',    88],
];

/** Les quatre expertises mises en avant. */
$expertises = [
    ['Sites vitrines', 'Des sites élégants et impactants pour présenter votre activité et convertir vos visiteurs.',
        '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>'],
    ['Identité de marque', 'Logo, univers visuel et direction artistique pour une marque cohérente et mémorable.',
        '<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.563-2.512 5.563-5.563C21.965 6.012 17.461 2 12 2z"/>'],
    ['Web sur-mesure', 'Des solutions adaptées à vos besoins métiers pour des projets sans limite.',
        '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15 9 12a22 22 0 0 1 8-9 14 14 0 0 1 4 0 14 14 0 0 1 0 4 22 22 0 0 1-9 8z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>'],
    ['Référencement', 'Améliorez votre visibilité et attirez plus de trafic qualifié sur votre site.',
        '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>'],
];

/** Arguments de l'ancrage local. */
$localPoints = [
    ['Proximité réelle', 'Des rendez-vous en présentiel ou en visioconférence, partout en Lot-et-Garonne.',
        '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>'],
    ['Connaissance du terrain', 'On connaît le tissu économique local et ses enjeux.',
        '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 0 20M12 2a15.3 15.3 0 0 0 0 20"/>'],
    ['Disponibles &amp; réactifs', 'Un interlocuteur unique, joignable et impliqué.',
        '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
];

/** Les familles de clients du territoire. */
$clients = [
    ['Artisans &amp; bâtiment', 'Plombiers, menuisiers, électriciens, paysagistes&nbsp;: un site qui inspire confiance et fait sonner le téléphone.',
        '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>'],
    ['Commerces &amp; boutiques', 'Boutiques, fleuristes, opticiens&nbsp;: une vitrine en ligne aussi soignée que votre devanture.',
        '<path d="M3 9h18l-1.5-5.5A1 1 0 0 0 18.5 3h-13a1 1 0 0 0-1 .5L3 9z"/><path d="M3 9v11a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V9M9 21v-6h6v6"/>'],
    ['Restaurants &amp; métiers de bouche', 'Restaurateurs, traiteurs, cavistes&nbsp;: menu, horaires et réservations toujours à jour.',
        '<path d="M3 2v7a3 3 0 0 0 6 0V2M6 2v20M16 2c-1.7 0-3 2-3 5s1.3 5 3 5 3-2 3-5-1.3-5-3-5zM16 12v10"/>'],
    ['Professions libérales', 'Avocats, kinés, architectes&nbsp;: une présence sérieuse et rassurante pour vos futurs clients.',
        '<path d="M12 2 4 6v6c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6l-8-4z"/><path d="m9 12 2 2 4-4"/>'],
    ['Producteurs &amp; vignerons', 'Domaines, fermes, marchés&nbsp;: valorisez vos produits du terroir et vendez en circuit court.',
        '<path d="M12 2c-1.5 3-4 4.5-7 5 0 7 3 11 7 13 4-2 7-6 7-13-3-.5-5.5-2-7-5z"/><path d="M9 12c1.5 1 4.5 1 6 0"/>'],
    ['Associations &amp; TPE', 'Clubs, associations, jeunes entreprises&nbsp;: un site clair et simple à faire vivre, sans budget démesuré.',
        '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>'],
];

/** Derniers projets livrés : titre, catégorie, URL, image (src/img/work/). */
$projets = [
    ['AppleMousse Studio', "Studio d'applications mobiles", 'https://applemousse-studio.fr', 'applemousse'],
    ['Cuisines Lamy', "Cuisiniste près d'Artigues", 'https://www.cuisineslamy.com', 'cuisineslamy'],
    ["Rad'o Lot", 'Événement caritatif — course de radeaux', 'https://radolot.fr', 'radolot'],
    ["Cabinet d'Artagnan", "Cabinet d'affaires en fonds de commerce", 'https://www.cabinet-dartagnan.com', 'dartagnan'],
];

/** Mots du bandeau défilant (dupliqués côté rendu pour une boucle sans couture). */
$marquee = ['Identité de marque', 'Web design', 'Développement', 'Avis Google', 'Référencement', 'Direction artistique'];

/** Pictogramme 24×24 au trait, dans le style du design system. */
$icon = static function (string $paths, string $extra = ''): string {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . ($extra !== '' ? $extra : '1.6')
        . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
};
?>

<main id="contenu">

    <!-- ============================ HERO ============================ -->
    <section class="hero section" id="top">
        <div class="shell">
            <div class="hero-grid">
                <div class="hero-copy">
                    <span class="eyebrow reveal">Studio de développement web · Lot-et-Garonne</span>
                    <h1>
                        <span class="line-mask"><span>Création de sites web à</span></span>
                        <span class="line-mask"><span class="accent">Villeneuve-sur-Lot</span></span>
                    </h1>
                    <p class="hero-sub reveal">Studio web basé dans le Lot-et-Garonne, nous concevons des sites
                        internet sur mesure, alliant design, performance et stratégie pour faire rayonner votre marque.</p>
                    <div class="hero-actions reveal">
                        <a class="btn btn-primary" href="#realisations" data-magnetic="0.3">Voir nos réalisations <span class="ico" aria-hidden="true">↗</span></a>
                        <a class="btn btn-ghost" href="#expertises" data-magnetic="0.3">Nos services <span class="ico" aria-hidden="true">↓</span></a>
                    </div>
                </div>

                <div class="hero-stage">
                    <div class="glow" aria-hidden="true"></div>
                    <div class="ring r1" aria-hidden="true"></div>
                    <div class="ring r2" aria-hidden="true"></div>
                    <?php /* Sous 620px la scène entière est masquée (hero.css). Masquer en CSS
                             n'empêche pas le téléchargement : on sert donc un pixel transparent
                             sur mobile plutôt que le logo 3D. Si la fenêtre s'élargit, le
                             navigateur resélectionne tout seul la vraie source. */ ?>
                    <picture>
                        <source media="(max-width: 620px)" srcset="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
                        <img class="hero-logo" src="<?= e(asset_url('/assets/img/logo3d-hero.webp')) ?>"
                             alt="Monogramme Sud Web Studio en volume" width="1254" height="1254" fetchpriority="high">
                    </picture>
                    <div class="hero-bubbles" aria-hidden="true">
                        <?php foreach ($bubbles as [$label, $pos, $size]): ?>
                        <span class="bubble" style="<?= e($pos) ?>;--sz:<?= (int) $size ?>px"><?= e($label) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <span class="scroll-cue mono" aria-hidden="true"><span class="bar"></span>Défiler</span>
    </section>

    <!-- ============================ EXPERTISES ============================ -->
    <section class="section" id="expertises">
        <div class="shell">
            <div class="exp-wrap">
                <div class="exp-intro">
                    <span class="eyebrow reveal">Nos expertises</span>
                    <h2>
                        <span class="line-mask"><span>Des solutions</span></span>
                        <span class="line-mask"><span>numériques qui</span></span>
                        <span class="line-mask"><span><em>font la différence</em></span></span>
                    </h2>
                    <p class="reveal">Chaque projet est unique. Nous mettons notre savoir-faire au service de vos
                        objectifs pour créer des sites à la fois utiles, soignés et performants.</p>
                    <a class="more reveal" href="#contact">Découvrir tous nos services <span class="ico" aria-hidden="true">↗</span></a>
                </div>

                <div class="exp-grid">
                    <?php foreach ($expertises as $i => [$titre, $texte, $paths]): ?>
                    <article class="exp-card">
                        <span class="num" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="exp-ic"><?= $icon($paths) ?></span>
                        <h3><?= $titre ?></h3>
                        <p><?= $texte ?></p>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================ ANCRAGE LOCAL ============================ -->
    <section class="section local" id="implantation">
        <div class="shell">
            <div class="local-grid">
                <div class="local-copy">
                    <span class="eyebrow reveal">Ancrage local</span>
                    <h2>
                        <span class="line-mask"><span>Votre studio web</span></span>
                        <span class="line-mask"><span>en <em>Lot-et-Garonne</em></span></span>
                    </h2>
                    <p class="reveal">Basés à <b>Villeneuve-sur-Lot</b>, au cœur du Lot-et-Garonne, nous accompagnons
                        les entreprises et artisans du territoire — de Marmande à Agen. La proximité, c'est notre façon
                        de créer&nbsp;: on se rencontre, on comprend votre métier, on construit ensemble.</p>
                    <ul class="local-points">
                        <?php foreach ($localPoints as [$titre, $texte, $paths]): ?>
                        <li class="reveal">
                            <span class="lp-ic"><?= $icon($paths) ?></span>
                            <div><b><?= $titre ?></b><span><?= $texte ?></span></div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="local-map reveal">
                    <div class="map-canvas">
                        <img src="<?= e(asset_url('/assets/img/map.webp')) ?>" width="1154" height="819" loading="lazy"
                             alt="Carte du Lot-et-Garonne situant Villeneuve-sur-Lot, Marmande et Agen">
                        <?php /* Tracés animés entre les trois villes : la ligne pointillée est
                                 révélée par un masque qui se dessine puis s'efface (home.css). */ ?>
                        <svg class="map-trace" viewBox="0 0 1154 819" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
                            <defs>
                                <mask id="traceMask1"><path class="trace-mask" d="M 776 360 Q 560 250 332 246" pathLength="100"/></mask>
                                <mask id="traceMask2"><path class="trace-mask" d="M 786 378 Q 695 515 542 560" pathLength="100"/></mask>
                                <mask id="traceMask3"><path class="trace-mask" d="M 332 246 Q 400 430 542 560" pathLength="100"/></mask>
                            </defs>
                            <path class="trace-line" d="M 776 360 Q 560 250 332 246" mask="url(#traceMask1)"/>
                            <path class="trace-line" d="M 786 378 Q 695 515 542 560" mask="url(#traceMask2)"/>
                            <path class="trace-line" d="M 332 246 Q 400 430 542 560" mask="url(#traceMask3)"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================ CLIENTÈLE ============================ -->
    <section class="section clients" id="clientele">
        <div class="shell">
            <div class="clients-head">
                <span class="eyebrow reveal">Notre clientèle</span>
                <h2>
                    <span class="line-mask"><span>Au service des</span></span>
                    <span class="line-mask"><span><em>PME locales</em></span></span>
                </h2>
                <p class="reveal">On travaille main dans la main avec les artisans, commerçants et entreprises du
                    territoire — celles et ceux qui font vivre le Lot-et-Garonne. Des sites pensés pour
                    <b>votre réalité</b>&nbsp;: un budget maîtrisé, des délais courts et un interlocuteur qui connaît
                    votre métier.</p>
            </div>

            <div class="clients-grid">
                <?php foreach ($clients as [$titre, $texte, $paths]): ?>
                <article class="client-card reveal">
                    <span class="client-ic"><?= $icon($paths) ?></span>
                    <h3><?= $titre ?></h3>
                    <p><?= $texte ?></p>
                </article>
                <?php endforeach; ?>
            </div>

            <p class="clients-foot reveal">
                <span class="cf-ic"><?= $icon('<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>', '1.7') ?></span>
                <span>Vous êtes une <b>PME du Sud-Ouest</b>&nbsp;? On parle le même langage — et on connaît déjà votre terrain.</span>
            </p>
        </div>
    </section>

    <!-- ============================ BANDEAU DÉFILANT ============================ -->
    <div class="strip" aria-hidden="true">
        <div class="marquee">
            <?php /* Deux copies identiques : motion.js boucle sur la moitié de la largeur. */ ?>
            <?php for ($i = 0; $i < 2; $i++): ?>
            <span><?php foreach ($marquee as $mot): ?><?= e($mot) ?> <i>✦</i> <?php endforeach; ?></span>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ============================ RÉALISATIONS ============================ -->
    <section class="section work" id="realisations">
        <div class="shell">
            <div class="work-head">
                <div class="l">
                    <span class="eyebrow reveal">Réalisations</span>
                    <h2><span class="line-mask"><span>Nos derniers projets</span></span></h2>
                </div>
            </div>

            <div class="work-grid">
                <?php foreach ($projets as [$nom, $categorie, $url, $image]): ?>
                <a class="work-card" href="<?= e($url) ?>" target="_blank" rel="noopener">
                    <div class="work-media">
                        <img class="shot" src="<?= e(asset_url('/assets/img/' . $image . '.webp')) ?>"
                             alt="Aperçu du site <?= e($nom) ?>" loading="lazy">
                    </div>
                    <div class="work-meta">
                        <div class="l">
                            <h3><?= e($nom) ?></h3>
                            <p class="work-cat"><?= $categorie ?></p>
                        </div>
                        <span class="work-go"><?= $icon('<path d="M7 17 17 7M9 7h8v8"/>', '2') ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ============================ CTA ============================ -->
    <section class="section cta" id="contact">
        <div class="shell">
            <div class="cta-card">
                <div class="cta-logo">
                    <img src="<?= e(asset_url('/assets/img/logo3d-hero.webp')) ?>" alt="" aria-hidden="true" loading="lazy" width="1254" height="1254">
                </div>
                <div class="cta-body">
                    <span class="eyebrow">Prêt à démarrer&nbsp;?</span>
                    <h2><span class="line-mask"><span>Parlons de votre projet</span></span></h2>
                    <p>Discutons de vos idées et construisons ensemble un site web à la hauteur de vos ambitions.</p>
                </div>
                <div class="cta-action">
                    <?php if (module_enabled('Devis')): ?>
                    <button type="button" class="btn btn-primary" data-devis-open data-magnetic="0.3">
                        Discutons de votre projet <span class="ico" aria-hidden="true">↗</span>
                    </button>
                    <?php else: ?>
                    <a class="btn btn-primary" href="<?= e(site_url('/contact')) ?>" data-magnetic="0.3">
                        Discutons de votre projet <span class="ico" aria-hidden="true">↗</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

</main>

<?php require dirname(__DIR__) . '/includes/footer.php';
