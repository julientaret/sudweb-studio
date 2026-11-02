<?php
/**
 * Tarifs — page statique (servie sur « /tarifs »).
 *
 * Intégration de la maquette design/Sud Web Studio - Tarifs.html : en-tête avec
 * bascule paiement unique / LOA, grille de formules, explication de la LOA,
 * comparatif, modules de gestion autonome et FAQ.
 *
 * Les montants et la FAQ vivent dans les tableaux ci-dessous : c'est le seul
 * endroit à toucher quand les tarifs bougent.
 */

$title = 'Tarifs';
$description = 'Tarifs de création de site web par Sud Web Studio : paiement unique ou '
    . "abonnement LOA tout inclus, à partir de 29 € par mois. Formules Starter, Essentiel et sur mesure.";

require dirname(__DIR__) . '/includes/header.php';

/** Espace fine insécable, séparateur de milliers typographique français. */
const NBSP_FINE = "\u{202F}";

/** Montant formaté à la française (1299 → « 1 299 »). */
$prix = static fn (int $n): string => number_format($n, 0, ',', NBSP_FINE);

/**
 * Les formules, dans l'ordre d'affichage.
 *
 * price.once / price.loa portent les deux faces de la bascule. Le JS ne permute
 * que le montant et son unité ; les libellés coexistent dans le HTML et c'est
 * le CSS qui masque celui du mode inactif (cf. .only-once / .only-loa).
 */
$formules = [
    [
        'name' => 'Starter One Page',
        'flag' => 'Pour se lancer',
        'soft' => true,
        'desc' => "Une page unique, soignée et efficace — idéale pour artisans et auto-entrepreneurs.",
        'cta'  => 'Choisir Starter',
        'price' => [
            'once' => ['pre' => 'À partir de — unique', 'amount' => 499, 'per' => '€',
                       'sub' => 'Livré clé en main · vous êtes propriétaire'],
            'loa'  => ['pre' => 'À partir de — loyer', 'amount' => 29, 'per' => '€ / mois',
                       'sub' => "24 mois · option d'achat <b>49&nbsp;€</b> en fin de contrat"],
        ],
        'feats' => [
            ['Site une page sur mesure, livré en 2 semaines', true],
            ['Galerie de réalisations', true],
            ['Avis Google — 5 synchronisations / mois', true],
            ['Formulaire de demande de devis', true],
            ['Pages multiples &amp; blog', false],
        ],
    ],
    [
        'name' => 'Essentiel',
        'desc' => "Le site vitrine idéal pour lancer votre activité et asseoir votre présence en ligne.",
        'cta'  => 'Choisir Essentiel',
        'price' => [
            'once' => ['pre' => 'Paiement unique', 'amount' => 1299, 'per' => '€',
                       'sub' => 'Livré clé en main · vous êtes propriétaire'],
            'loa'  => ['pre' => 'Loyer mensuel', 'amount' => 59, 'per' => '€ / mois',
                       'sub' => "36 mois · option d'achat <b>149&nbsp;€</b> en fin de contrat"],
        ],
        'feats' => [
            ["Jusqu'à 5 pages sur mesure", true],
            ['Formulaire de contact', true],
            ['Avis Google', true],
            ['Demande de devis', true],
            ['Actualités / Blog', true],
            ['Réalisations', true],
        ],
    ],
    [
        'name'     => 'Sur Mesure',
        'flag'     => 'Sans limite',
        'featured' => true,
        'desc'     => "Un devis adapté à vos demandes et à vos besoins — pour un projet pensé entièrement "
            . "autour de votre activité.",
        'cta'      => 'Demander un devis',
        'quote'    => ['label' => 'Sur devis', 'sub' => "Un projet chiffré selon vos besoins réels, sans surprise."],
        'feats' => [
            ['Pages illimitées &amp; fonctionnalités sur mesure', true],
            ['Actualités, agenda &amp; contenus dynamiques', true],
            ['Identité visuelle &amp; direction artistique sur mesure', true],
            ['Connexions à vos outils métier (CRM, stocks, API)', true],
            ['Accompagnement stratégique dédié', true],
        ],
    ],
];

/** Les trois temps de la LOA. */
$etapes = [
    ['On crée votre site', "Conception, design et mise en ligne, sans gros investissement de départ. Vous démarrez tout de suite.",
        '<path d="M12 20h9M3 20h2M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/>'],
    ['Un loyer tout inclus', "Hébergement, maintenance, mises à jour et petites évolutions sont compris dans votre mensualité. Aucune surprise.",
        '<path d="M3 10h18M5 6h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/>'],
    ['Le site vous appartient', "Au terme de la formule, vous devenez pleinement propriétaire de votre site — sans frais caché. Vous pouvez aussi le faire évoluer ou renouveler.",
        '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m9 12 2 2 4-4"/>'],
];

/**
 * Comparatif : chaque ligne est [prestation, Starter, Essentiel, Sur Mesure].
 * true => coche, false => tiret, chaîne => texte.
 */
$comparatif = [
    ['Pages sur mesure',               '1',        '5',          'Illimité'],
    ['Design &amp; direction artistique', 'Soigné', 'Essentiel',  'Sur mesure'],
    ['Référencement SEO',              'Local',    'Local',      'Stratégique'],
    ['Blog &amp; actualités',          false,      false,        true],
    ["Espace d'édition autonome",      'Limité',   true,         true],
    ['Hébergement &amp; maintenance (LOA)', true,  true,         true],
    ['Accompagnement &amp; suivi',     'Lancement', 'Lancement', 'Dédié'],
];

/** Modules d'administration proposés (ceux du CMS). */
$modules = [
    ['Médias', "Bibliothèque d'images partagée (vignettes, blocs image).",
        '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.6-3.6a2 2 0 0 0-2.8 0L6 20"/>'],
    ['Documents', "Bibliothèque de documents téléchargeables (PDF…).",
        '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>'],
    ['Pages', "Pages du site et page-builder par blocs.",
        '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>'],
    ['Agenda', "Événements datés avec page publique /agenda/.",
        '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
    ['Actualités', "Articles d'actualité avec page publique /actualites/.",
        '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9h4"/><path d="M18 14h-8M15 18h-5M10 6h8v4h-8z"/>'],
    ['Avis Google', "Import des avis Google de l'établissement et bloc d'affichage.",
        '<path d="m12 17.3-6.2 3.7 1.6-7.1L2 9.2l7.2-.6L12 2l2.8 6.6 7.2.6-5.4 4.7 1.6 7.1z"/>'],
    ['Contact', "Formulaire public, demandes en base et notification e-mail au propriétaire.",
        '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="m22 7-10 6L2 7"/>'],
    ['Pages légales', "Mentions légales, politique de confidentialité et pages juridiques, au texte riche.",
        '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>'],
    ['Statistiques', "Mesure d'audience légère, sans cookie ni service tiers (RGPD).",
        '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>'],
    ['Boîtes mail pro', "Adresses e-mail à votre nom de domaine (contact@votre-entreprise.fr), créées et gérées en quelques clics.",
        '<circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"/>'],
    ['Demande de devis', "Formulaire de demande de devis personnalisable : champs sur mesure adaptés à votre métier.",
        '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h3M9 17h6M9 9h1"/>'],
    ['Réalisations', "Galerie de réalisations pour montrer vos travaux, avec page publique /realisations/.",
        '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>'],
];

/** Questions fréquentes : [question, réponse en HTML simple]. */
$faq = [
    ["Quelle différence entre le paiement unique et la LOA&nbsp;?",
        "En <b>paiement unique</b>, vous réglez le site en une fois et en êtes propriétaire dès la "
        . "livraison. En <b>LOA</b>, vous payez un loyer mensuel tout inclus (hébergement, maintenance, "
        . "évolutions) et levez une option d'achat à la fin du contrat pour en devenir propriétaire."],
    ["Que comprend le loyer mensuel de la LOA&nbsp;?",
        "Tout&nbsp;: l'<b>hébergement</b>, le nom de domaine, la <b>maintenance technique</b>, les mises "
        . "à jour de sécurité et un volume de petites évolutions chaque mois. Vous n'avez aucun frais "
        . "caché à prévoir."],
    ["J'achète mon site en une fois — puis-je avoir une maintenance&nbsp;?",
        "Oui. Même en <b>paiement unique</b>, vous pouvez souscrire une <b>maintenance mensuelle</b> "
        . "optionnelle qui comprend la <b>gestion du nom de domaine</b>, l'<b>hébergement</b> et le "
        . "<b>dépannage en cas de panne</b>, ainsi que les <b>modifications mineures incluses</b>. Vous "
        . "restez propriétaire de votre site, et c'est sans engagement de durée — vous l'activez si et "
        . "quand vous le souhaitez."],
    ["Suis-je engagé sur une longue durée&nbsp;?",
        "En LOA, le contrat court sur <b>24 mois</b> pour la formule Starter et <b>36 mois</b> pour "
        . "Essentiel. C'est ce qui permet de lisser le coût et d'inclure tous les services. Le paiement "
        . "unique, lui, est <b>sans aucun engagement</b> de durée."],
    ["Que se passe-t-il à la fin du contrat LOA&nbsp;?",
        "Vous avez trois choix&nbsp;: <b>lever l'option d'achat</b> pour devenir pleinement propriétaire, "
        . "<b>renouveler</b> avec une refonte, ou <b>poursuivre</b> en formule maintenance seule. C'est "
        . "vous qui décidez."],
    ["Les prix incluent-ils la TVA&nbsp;?",
        "Les tarifs affichés sont indiqués <b>hors taxes</b>. Un devis détaillé et personnalisé vous est "
        . "remis avant tout démarrage, avec le montant TTC et l'échéancier précis."],
];

/** Pictogramme 24×24 au trait, dans le style du design system. */
$icon = static fn (string $paths, string $w = '1.6'): string =>
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $w
    . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';

/** Coche et croix des listes de prestations. */
$check = static fn (): string => $icon('<path d="M20 6 9 17l-5-5"/>', '3');
$cross = static fn (): string => $icon('<path d="M18 6 6 18M6 6l12 12"/>', '3');
?>

<?php /* data-billing porte le mode courant pour toute la page : le JS ne change
         que cet attribut, le CSS fait le reste. */ ?>
<main id="contenu" data-billing="once">

    <!-- ============================ EN-TÊTE ============================ -->
    <section class="price-hero page-hero section">
        <div class="shell">
            <span class="eyebrow reveal">Tarifs &amp; formules</span>
            <h1>
                <span class="line-mask"><span>Un site qui vous</span></span>
                <span class="line-mask"><span>ressemble, <em>à votre rythme</em></span></span>
            </h1>
            <p class="lead reveal">Choisissez le paiement qui vous arrange&nbsp;: un règlement
                <b>en une fois</b> pour devenir propriétaire immédiatement, ou un
                <b>abonnement mensuel LOA</b> — tout inclus, sans gros budget de départ.</p>

            <div class="billing reveal">
                <div class="toggle" role="group" aria-label="Mode de paiement">
                    <span class="thumb" aria-hidden="true"></span>
                    <button type="button" data-billing-mode="once" aria-pressed="true">Paiement unique</button>
                    <button type="button" data-billing-mode="loa" aria-pressed="false">
                        Abonnement LOA <span class="tag">dès 29&nbsp;€/mois</span>
                    </button>
                </div>
                <p class="billing-note" aria-live="polite">
                    <span class="only-once">Vous devenez <b>propriétaire</b> dès la livraison</span>
                    <span class="only-loa">Loyer tout inclus — en fin de formule,
                        <b>le site vous appartient entièrement, sans frais caché</b></span>
                </p>
            </div>
        </div>
    </section>

    <!-- ============================ FORMULES ============================ -->
    <section class="pricing" aria-label="Formules">
        <div class="shell">
            <div class="price-grid">
                <?php foreach ($formules as $f): ?>
                <article class="tier<?= !empty($f['featured']) ? ' featured' : '' ?>">
                    <?php if (!empty($f['flag'])): ?>
                    <span class="tier-flag<?= !empty($f['soft']) ? ' tier-flag-soft' : '' ?>"><?= e($f['flag']) ?></span>
                    <?php endif; ?>

                    <h3 class="tier-name"><?= e($f['name']) ?></h3>
                    <p class="tier-desc"><?= e($f['desc']) ?></p>

                    <?php if (isset($f['quote'])): ?>
                    <div class="tier-price tier-price-quote">
                        <span class="quote-label"><?= e($f['quote']['label']) ?></span>
                        <p class="quote-sub"><?= e($f['quote']['sub']) ?></p>
                    </div>
                    <?php else: ?>
                    <div class="tier-price">
                        <span class="price-pre">
                            <span class="only-once"><?= e($f['price']['once']['pre']) ?></span>
                            <span class="only-loa"><?= e($f['price']['loa']['pre']) ?></span>
                        </span>
                        <p class="price-row">
                            <?php /* Montant et unité sont permutés par le JS : lui seul doit changer
                                     de valeur, pour que le compteur puisse l'animer. */ ?>
                            <span class="amount"
                                  data-once="<?= (int) $f['price']['once']['amount'] ?>"
                                  data-loa="<?= (int) $f['price']['loa']['amount'] ?>"><?= $prix($f['price']['once']['amount']) ?></span>
                            <span class="per"
                                  data-once="<?= e($f['price']['once']['per']) ?>"
                                  data-loa="<?= e($f['price']['loa']['per']) ?>"><?= e($f['price']['once']['per']) ?></span>
                        </p>
                        <p class="price-sub">
                            <span class="only-once"><?= $f['price']['once']['sub'] ?></span>
                            <span class="only-loa"><?= $f['price']['loa']['sub'] ?></span>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php /* La formule choisie voyage en paramètre : la page de devis la
                             coche d'avance (elle n'accepte que ses propres options). */ ?>
                    <a class="btn <?= !empty($f['featured']) ? 'btn-primary' : 'btn-ghost' ?>"
                       href="<?= e(module_enabled('Devis')
                           ? site_url('/devis') . '?formule=' . rawurlencode($f['name'])
                           : site_url('/contact')) ?>" data-magnetic="0.2">
                        <?= e($f['cta']) ?> <span class="ico" aria-hidden="true">↗</span>
                    </a>

                    <ul class="tier-feats">
                        <?php foreach ($f['feats'] as [$label, $inclus]): ?>
                        <li<?= $inclus ? '' : ' class="off"' ?>>
                            <span class="ck"><?= $inclus ? $check() : $cross() ?></span><?= $label ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </article>
                <?php endforeach; ?>
            </div>

            <div class="price-assure reveal">
                <span><span class="ai"><?= $icon('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>', '1.7') ?></span>Sans engagement caché</span>
                <span><span class="ai"><?= $icon('<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"/><path d="M12 6v6l4 2"/>', '1.7') ?></span>Mise en ligne sous 4 à 6 semaines</span>
                <span><span class="ai"><?= $icon('<path d="M3 11l19-9-9 19-2-8-8-2z"/>', '1.7') ?></span>Interlocuteur unique en Lot-et-Garonne</span>
            </div>

            <div class="loa-note reveal">
                <span class="ni"><?= $icon('<path d="M14.7 6.3a4 4 0 0 0-5.6 5.6l-6.4 6.4a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l6.4-6.4a4 4 0 0 0 5.6-5.6l-2.5 2.5-2.4-.7-.7-2.4z"/>', '1.7') ?></span>
                <p><b>Vous achetez votre site en une fois&nbsp;?</b><br>Une <b>maintenance mensuelle</b>
                    optionnelle reste possible — sans aucune obligation. Elle comprend la <b>gestion du nom
                    de domaine</b>, l'<b>hébergement</b> et le <b>dépannage en cas de panne</b>, ainsi que
                    les <b>modifications mineures incluses</b>. Vous restez propriétaire de votre
                    site&nbsp;; on veille sur la technique.</p>
            </div>
        </div>
    </section>

    <!-- ============================ LE PRINCIPE LOA ============================ -->
    <section class="section loa" id="loa">
        <div class="shell">
            <div class="loa-head">
                <span class="eyebrow reveal">Le principe LOA</span>
                <h2>
                    <span class="line-mask"><span>La location avec</span></span>
                    <span class="line-mask"><span><em>option d'achat</em></span></span>
                </h2>
                <p class="reveal">Comme pour une voiture, la LOA vous permet d'avoir un site web
                    professionnel <b>sans avancer le budget complet</b>. Vous payez un loyer mensuel qui
                    inclut tout — et vous choisissez de devenir propriétaire à la fin.</p>
            </div>

            <div class="loa-steps">
                <?php foreach ($etapes as $i => [$titre, $texte, $paths]): ?>
                <article class="loa-step reveal">
                    <span class="step-n">Étape <?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <span class="step-ic"><?= $icon($paths) ?></span>
                    <h3><?= e($titre) ?></h3>
                    <p><?= e($texte) ?></p>
                </article>
                <?php endforeach; ?>
            </div>

            <div class="loa-note reveal">
                <span class="ni"><?= $icon('<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>', '1.7') ?></span>
                <p><b>Paiement unique ou LOA, le site est le même.</b> La LOA lisse simplement le coût dans
                    le temps et intègre l'hébergement et la maintenance que vous paieriez de toute façon.
                    Idéal pour préserver votre trésorerie au lancement.</p>
            </div>
        </div>
    </section>

    <!-- ============================ COMPARATIF ============================ -->
    <section class="section" id="comparatif">
        <div class="shell">
            <div class="sec-head">
                <h2>
                    <span class="line-mask"><span>Comparez les</span></span>
                    <span class="line-mask"><span>trois <em>formules</em></span></span>
                </h2>
                <p class="lead reveal">Tout ce qui est inclus, en un coup d'œil. Chaque formule est
                    disponible en paiement unique ou en LOA.</p>
            </div>

            <div class="compare-wrap reveal">
                <table class="compare">
                    <thead>
                        <tr>
                            <th scope="col">Prestation</th>
                            <th scope="col">Starter</th>
                            <th scope="col">Essentiel</th>
                            <th scope="col" class="col-feat">Sur Mesure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comparatif as [$label, $starter, $essentiel, $surmesure]): ?>
                        <tr>
                            <th scope="row"><?= $label ?></th>
                            <?php foreach ([$starter, $essentiel, $surmesure] as $col => $valeur):
                                // La dernière colonne — la formule mise en avant — est teintée.
                                $cls = $col === 2 ? ' class="col-feat-cell"' : '';
                            ?>
                            <td<?= $cls ?>>
                                <?php if ($valeur === true): ?>
                                <span class="yes"><?= $check() ?><span class="sr-only">Inclus</span></span>
                                <?php elseif ($valeur === false): ?>
                                <span class="no" aria-label="Non inclus">—</span>
                                <?php else: ?>
                                <?= $valeur ?>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ============================ GESTION AUTONOME ============================ -->
    <section class="section" id="modules">
        <div class="shell">
            <div class="modules-head">
                <span class="eyebrow reveal">Espace d'édition autonome</span>
                <h2>
                    <span class="line-mask"><span>Les options de</span></span>
                    <span class="line-mask"><span><em>gestion autonome</em></span></span>
                </h2>
                <p class="reveal">Selon la formule choisie, vous pilotez vous-même tout ou partie de votre
                    site. Voici les modules d'administration disponibles&nbsp;: on compose votre interface
                    avec ceux qui correspondent à votre besoin et à votre formule.</p>
            </div>

            <div class="modules-grid">
                <?php foreach ($modules as [$nom, $texte, $paths]): ?>
                <article class="module reveal">
                    <span class="module-ic"><?= $icon($paths) ?></span>
                    <div class="module-body">
                        <h3><?= e($nom) ?></h3>
                        <p><?= e($texte) ?></p>
                    </div>
                    <span class="module-add" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    </span>
                </article>
                <?php endforeach; ?>

                <article class="module-custom reveal">
                    <span class="mc-ic"><?= $icon('<path d="M12 20h9M3 20h2M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/>', '1.7') ?></span>
                    <div>
                        <h3>Et bien sûr, du sur-mesure</h3>
                        <p>Cette liste n'est qu'un point de départ. Réservation en ligne, espace membre,
                            catalogue, connexion à vos outils métier… on développe le module dont <b>vous</b>
                            avez besoin, pensé exactement pour votre activité.</p>
                    </div>
                    <a class="btn btn-primary mc-cta"
                       href="<?= e(site_url(module_enabled('Devis') ? '/devis' : '/contact')) ?>" data-magnetic="0.25">
                        Décrire mon besoin <span class="ico" aria-hidden="true">↗</span>
                    </a>
                </article>
            </div>

            <p class="modules-foot reveal">
                <span class="mf-ic"><?= $icon('<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>', '1.8') ?></span>
                Les modules inclus sont définis avec vous au moment du devis, en fonction de votre formule.
            </p>
        </div>
    </section>

    <!-- ============================ FAQ ============================ -->
    <section class="section" id="faq">
        <div class="shell">
            <div class="faq-grid">
                <div class="faq-intro">
                    <span class="eyebrow reveal">Questions fréquentes</span>
                    <h2>
                        <span class="line-mask"><span>Tout savoir</span></span>
                        <span class="line-mask"><span>avant de <em>choisir</em></span></span>
                    </h2>
                    <p class="reveal">Une question qui n'est pas ici&nbsp;? Écrivez-nous, on vous répond
                        rapidement et sans jargon.</p>
                </div>

                <div class="faq-list reveal">
                    <?php foreach ($faq as $i => [$question, $reponse]): $id = 'faq-' . ($i + 1); ?>
                    <div class="faq-item">
                        <button type="button" class="faq-q" aria-expanded="false" aria-controls="<?= $id ?>">
                            <?= $question ?>
                            <span class="faq-ic"><?= $icon('<path d="M12 5v14M5 12h14"/>', '2') ?></span>
                        </button>
                        <div class="faq-a" id="<?= $id ?>">
                            <div class="faq-a-inner"><div><?= $reponse ?></div></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <?php
    $ctaEyebrow = 'Une question sur les tarifs&nbsp;?';
    $ctaTitle   = 'Construisons votre devis';
    $ctaText    = "Dites-nous où vous en êtes&nbsp;: on vous conseille la formule la plus juste, en une fois ou en LOA.";
    require dirname(__DIR__) . '/includes/cta.php';
    ?>

</main>

<?php require dirname(__DIR__) . '/includes/footer.php';
