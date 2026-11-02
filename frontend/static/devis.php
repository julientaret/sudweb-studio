<?php
/**
 * Demande de devis — page statique (servie sur « /devis »).
 *
 * Intégration de la maquette design/Sud Web Studio - Demande de devis.html.
 *
 * Le formulaire est rendu À PARTIR DU SCHÉMA du module Devis
 * (flow-cms/storage/devis_form.json, éditable depuis l'admin) et poste sur ses
 * routes existantes : /devis/token puis /devis/envoyer. Toute la validation,
 * l'anti-spam, la trace de consentement et les notifications restent côté
 * noyau — cette page n'est qu'un habillage.
 *
 * Conséquence : une question ajoutée dans l'admin apparaît ici d'office, avec
 * un rendu générique. Les clés listées dans $habillage ci-dessous reçoivent en
 * plus le rendu riche de la maquette (cartes à cocher, sous-libellés).
 */

use App\Core\Config;
use App\Modules\Devis\DevisFormSettings;

$title = 'Demande de devis';
$description = "Décrivez votre projet à Sud Web Studio : on revient vers vous sous 48 h avec "
    . "un devis personnalisé, en paiement unique ou en LOA. Sans engagement.";

require dirname(__DIR__) . '/includes/header.php';

$form = new DevisFormSettings();
$schema = $form->schema();

$consentText = (string) Config::get('devis.consent_text', '');
$privacyUrl  = (string) Config::get('devis.privacy_url', '');

/**
 * Habillage riche par clé de question. Le schéma reste maître du type, des
 * options et du caractère obligatoire ; on n'ajoute ici que la présentation.
 *
 *   mode    'cards' (choix en cartes) ou 'checks' (cases multiples) ;
 *   cols    densité de la grille de cartes ;
 *   subs    sous-libellé par option ;
 *   place   texte indicatif d'un champ libre ;
 *   hint    précision sous le champ.
 */
$habillage = [
    'project_type' => ['mode' => 'cards', 'cols' => 2, 'subs' => [
        'Site vitrine'          => 'Présenter mon activité',
        'Site + actualités'     => 'Avec blog ou agenda',
        'Site + réservation'    => 'Prise de rendez-vous',
        'Refonte / sur-mesure'  => 'Refaire ou cas spécifique',
    ]],
    'description' => ['place' => "Votre activité, vos objectifs, ce qui vous plaît, des exemples de sites que vous aimez…",
        'hint' => "Plus c'est précis, plus le devis sera juste — mais l'essentiel suffit pour démarrer."],
    'formule' => ['mode' => 'cards', 'cols' => 3, 'subs' => [
        'Starter One Page' => 'dès 499 € / 29 €·mois',
        'Essentiel'        => 'dès 1 299 € / 59 €·mois',
        'Sur Mesure'       => 'sur devis',
    ]],
    'paiement' => ['mode' => 'cards', 'cols' => 2, 'subs' => [
        'Paiement unique'  => 'Propriétaire à la livraison',
        'Abonnement LOA'   => 'Loyer mensuel tout inclus',
    ]],
    'maintenance' => ['mode' => 'checks', 'cols' => 1, 'value' => 'Maintenance mensuelle', 'choix' => [
        'Maintenance mensuelle' => ['Ajouter la maintenance mensuelle',
            "Gestion du nom de domaine, hébergement &amp; dépannage en cas de panne, modifications mineures incluses"],
    ]],
    'modules' => ['mode' => 'checks', 'cols' => 2, 'choix' => [
        'Médias'            => ['Médias', "Bibliothèque d'images"],
        'Documents'         => ['Documents', 'PDF téléchargeables'],
        'Pages'             => ['Pages', 'Builder par blocs'],
        'Agenda'            => ['Agenda', 'Événements datés'],
        'Actualités'        => ['Actualités', 'Articles &amp; blog'],
        'Avis Google'       => ['Avis Google', 'Import &amp; affichage'],
        'Contact'           => ['Contact', 'Formulaire &amp; demandes'],
        'Demande de devis'  => ['Demande de devis', 'Formulaire personnalisable'],
        'Réalisations'      => ['Réalisations', 'Galerie de vos travaux'],
        'Pages légales'     => ['Pages légales', 'Mentions &amp; RGPD'],
        'Statistiques'      => ['Statistiques', 'Audience sans cookie'],
        'Boîtes mail pro'   => ['Boîtes mail pro', 'Adresses à votre domaine'],
        'Module sur-mesure' => ['Autre / sur-mesure', 'Décrit plus haut'],
    ]],
    'domaine' => ['place' => 'ex. mon-entreprise.fr',
        'hint' => "Pour votre site et vos boîtes mail pro (nom@votre-domaine.fr). Pas encore d'idée&nbsp;? On vous conseille."],
    'name'    => ['place' => 'Camille Durand', 'autocomplete' => 'name'],
    'company' => ['place' => 'Nom de votre activité', 'autocomplete' => 'organization'],
    'email'   => ['place' => 'camille@exemple.fr', 'autocomplete' => 'email'],
    'phone'   => ['place' => '06 12 34 56 78', 'autocomplete' => 'tel'],
];

/*
 * Pré-sélection depuis l'URL (« /devis?formule=Essentiel », posé par les boutons
 * de la page Tarifs). La valeur n'est retenue que si le schéma la propose :
 * rien d'arbitraire n'entre dans le formulaire.
 */
$preselect = trim((string) ($_GET['formule'] ?? ''));

/** Questions reprises dans le récapitulatif : clé => intitulé court. */
$recap = [
    'project_type' => 'Type',
    'formule'      => 'Formule',
    'paiement'     => 'Paiement',
    'maintenance'  => 'Maintenance',
    'budget'       => 'Budget',
    'deadline'     => 'Délai',
    'domaine'      => 'Nom de domaine',
];

/** Sous-titres des blocs, par rang d'étape. */
$sousTitres = [
    "De quel type de site avez-vous besoin&nbsp;?",
    "Une idée de départ — rien n'est figé.",
    "Cochez les modules que vous aimeriez pouvoir gérer vous-même. On affine ensemble au devis.",
    "Pour calibrer le devis et le planning.",
    "Pour vous envoyer le devis et en discuter.",
];

/** Pictogramme 24×24 au trait. */
$icon = static fn (string $paths, string $w = '1.8'): string =>
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $w
    . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
$check = static fn (): string => $icon('<path d="M20 6 9 17l-5-5"/>', '3.2');

/** Libellé d'une question, suivi du marqueur « optionnel » issu du schéma. */
$label = static function (array $f): string {
    $out = e($f['label']);
    if (!$f['required']) {
        $out .= ' <span class="opt">· optionnel</span>';
    }
    return $out;
};
?>

<main id="contenu">

    <!-- ============================ EN-TÊTE ============================ -->
    <section class="quote-hero page-hero" id="devis">
        <div class="shell">
            <div>
                <span class="eyebrow reveal">Demande de devis</span>
                <h1>
                    <span class="line-mask"><span>Parlez-nous de</span></span>
                    <span class="line-mask"><span>votre <em>projet</em></span></span>
                </h1>
            </div>
            <div>
                <p class="lead reveal">Décrivez votre activité et vos besoins&nbsp;: on revient vers vous
                    avec un <b>devis personnalisé</b> et l'échéancier précis, en paiement unique ou en LOA.
                    Aucun engagement à ce stade.</p>
                <div class="quote-steps-meta reveal">
                    <span><span class="qi"><?= $icon('<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"/><path d="M12 6v6l4 2"/>') ?></span>5 min à remplir</span>
                    <span><span class="qi"><?= $icon('<path d="M4 4h16v16H4z"/><path d="m22 6-10 7L2 6"/>') ?></span>Réponse sous 48h</span>
                    <span><span class="qi"><?= $icon('<path d="M20 6 9 17l-5-5"/>') ?></span>Sans engagement</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================ FORMULAIRE ============================ -->
    <section class="quote-wrap">
        <div class="shell">
            <div class="quote-grid">

                <?php /* Les URLs des routes du module voyagent en data-*, la CSP interdisant
                         le script inline qui les aurait posées. */ ?>
                <form class="qform" id="devis-page-form" novalidate
                      data-token-url="<?= e(site_url('/devis/token')) ?>"
                      data-submit-url="<?= e(site_url('/devis/envoyer')) ?>">

                    <div class="qhp" aria-hidden="true">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                        <input type="text" name="company_url" tabindex="-1" autocomplete="off">
                    </div>

                    <?php foreach ($schema as $i => $step): ?>
                    <div class="qblock reveal">
                        <div class="qblock-head">
                            <span class="qblock-n" aria-hidden="true"><?= $i + 1 ?></span>
                            <div>
                                <h2><?= e($step['title']) ?></h2>
                                <?php if (isset($sousTitres[$i])): ?>
                                <p><?= $sousTitres[$i] ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php
                        // Les champs courts de la dernière étape vont deux par deux.
                        $champs = $step['fields'];
                        $paires = count($champs) === 4 && $i === count($schema) - 1;
                        foreach ($champs as $n => $f):
                            $key = $f['key'];
                            $hab = $habillage[$key] ?? [];
                            $id  = 'q-' . $key;
                            $ouvrePaire  = $paires && $n % 2 === 0;
                            $fermePaire  = $paires && $n % 2 === 1;
                        ?>
                        <?php if ($ouvrePaire): ?><div class="qrow"><?php endif; ?>

                        <?php if (($hab['mode'] ?? '') === 'cards'): ?>
                        <?php /* Groupe de boutons radio : une seule valeur postée, donc
                                 interchangeable avec le <select> que déclare le schéma. */ ?>
                        <div class="qfield" role="group" aria-labelledby="<?= $id ?>-l">
                            <span class="qlabel" id="<?= $id ?>-l"><?= $label($f) ?></span>
                            <div class="qchoices cols-<?= (int) ($hab['cols'] ?? 2) ?>" data-sum="<?= e($key) ?>">
                                <?php foreach ($f['options'] as $opt): ?>
                                <label class="qchoice radio">
                                    <input type="radio" name="<?= e($key) ?>" value="<?= e($opt) ?>"<?= $f['required'] ? ' required' : '' ?><?= $key === 'formule' && $opt === $preselect ? ' checked' : '' ?>>
                                    <span class="qmark" aria-hidden="true"><?= $check() ?></span>
                                    <span class="qchoice-body"><b><?= e($opt) ?></b><?php
                                        if (!empty($hab['subs'][$opt])) { echo '<span>' . $hab['subs'][$opt] . '</span>'; }
                                    ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php elseif (($hab['mode'] ?? '') === 'checks'): ?>
                        <?php
                        /*
                         * Cases multiples. Le module ne connaît que des valeurs simples :
                         * les cases n'ont donc pas de name, et le JS consigne la liste
                         * cochée dans le champ caché. Sans JS, cette question optionnelle
                         * part vide — le reste du formulaire fonctionne.
                         */
                        $multiple = count($hab['choix']) > 1;
                        ?>
                        <div class="qfield" role="group" aria-labelledby="<?= $id ?>-l">
                            <span class="qlabel" id="<?= $id ?>-l"><?= $label($f) ?></span>
                            <?php if ($multiple): ?>
                            <input type="hidden" name="<?= e($key) ?>" data-checks-value="<?= e($key) ?>">
                            <?php endif; ?>
                            <div class="qchoices<?= $multiple ? ' qmodules' : '' ?> cols-<?= (int) ($hab['cols'] ?? 1) ?>" data-sum="<?= e($key) ?>">
                                <?php foreach ($hab['choix'] as $valeur => [$titre, $sub]): ?>
                                <label class="qchoice">
                                    <input type="checkbox" value="<?= e($valeur) ?>"
                                           <?= $multiple ? 'data-checks="' . e($key) . '"' : 'name="' . e($key) . '"' ?>>
                                    <span class="qmark" aria-hidden="true"><?= $check() ?></span>
                                    <span class="qchoice-body"><b><?= $titre ?></b><span><?= $sub ?></span></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php elseif ($f['type'] === 'select'): ?>
                        <div class="qfield">
                            <label class="qlabel" for="<?= $id ?>"><?= $label($f) ?></label>
                            <select class="qselect" id="<?= $id ?>" name="<?= e($key) ?>" data-sum="<?= e($key) ?>"<?= $f['required'] ? ' required' : '' ?>>
                                <option value=""><?= $f['required'] ? 'Choisissez…' : 'À définir ensemble' ?></option>
                                <?php foreach ($f['options'] as $opt): ?>
                                <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php elseif ($f['type'] === 'textarea'): ?>
                        <div class="qfield">
                            <label class="qlabel" for="<?= $id ?>"><?= $label($f) ?></label>
                            <textarea class="qtextarea" id="<?= $id ?>" name="<?= e($key) ?>" maxlength="5000"
                                      placeholder="<?= e((string) ($hab['place'] ?? '')) ?>"<?= $f['required'] ? ' required' : '' ?><?= $f['minlength'] > 0 ? ' minlength="' . (int) $f['minlength'] . '"' : '' ?>></textarea>
                            <?php if (!empty($hab['hint'])): ?><span class="qhint"><?= $hab['hint'] ?></span><?php endif; ?>
                        </div>

                        <?php else: ?>
                        <div class="qfield">
                            <label class="qlabel" for="<?= $id ?>"><?= $label($f) ?></label>
                            <input class="qinput" id="<?= $id ?>" name="<?= e($key) ?>" type="<?= e($f['type']) ?>"
                                   maxlength="255" data-sum="<?= e($key) ?>"
                                   placeholder="<?= e((string) ($hab['place'] ?? '')) ?>"
                                   <?= !empty($hab['autocomplete']) ? 'autocomplete="' . e($hab['autocomplete']) . '"' : '' ?>
                                   <?= $f['required'] ? ' required' : '' ?>>
                            <?php if (!empty($hab['hint'])): ?><span class="qhint"><?= $hab['hint'] ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($fermePaire): ?></div><?php endif; ?>
                        <?php endforeach; ?>

                        <?php if ($i === count($schema) - 1): ?>
                        <?php /* Consentement explicite : le module en garde la trace et la
                                 version. La maquette n'avait qu'une phrase, qui n'en laisse
                                 aucune. */ ?>
                        <div class="qconsent-field">
                            <input type="checkbox" id="q-consent" name="consent" value="1" required>
                            <label for="q-consent">
                                <?= e($consentText !== '' ? $consentText : "J'accepte que mes données soient utilisées pour traiter ma demande.") ?>
                                <?php if ($privacyUrl !== ''): ?>
                                <a href="<?= e(site_url($privacyUrl)) ?>">En savoir plus</a>.
                                <?php endif; ?>
                            </label>
                        </div>

                        <div class="qerrors" role="alert" hidden></div>

                        <div class="qsubmit-row">
                            <button type="submit" class="btn btn-primary" data-magnetic="0.25">
                                Envoyer ma demande <span class="ico" aria-hidden="true">↗</span>
                            </button>
                            <p class="qconsent">Réponse sous 48 h. Vos données servent uniquement à traiter
                                votre demande et ne sont jamais cédées à des tiers.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </form>

                <div class="qdone" id="devis-page-done" hidden>
                    <div class="qdone-ic" aria-hidden="true"><?= $icon('<path d="M20 6 9 17l-5-5"/>', '2.4') ?></div>
                    <h2>Demande envoyée&nbsp;!</h2>
                    <p>Merci, on a bien reçu votre projet. On revient vers vous sous 48 h avec un devis
                        personnalisé et l'échéancier précis.</p>
                </div>

                <aside class="qaside">
                    <div class="qsummary reveal">
                        <h3>Récapitulatif</h3>
                        <p class="sub">Mis à jour en temps réel</p>
                        <ul class="qsum-list">
                            <?php foreach ($recap as $key => $intitule): ?>
                            <li><span class="k"><?= e($intitule) ?></span><span class="v empty" data-out="<?= e($key) ?>">—</span></li>
                            <?php endforeach; ?>
                            <li class="stacked">
                                <span class="k">Options autonomes</span>
                                <span class="qsum-mods" data-out="modules">
                                    <span class="v empty">Aucune sélectionnée</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                    <div class="qnote reveal">
                        <span class="ni"><?= $icon('<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>') ?></span>
                        <p><b>Pas sûr de vos choix&nbsp;?</b> Remplissez seulement ce que vous savez. On
                            affine tout ensemble lors de l'échange.</p>
                    </div>
                </aside>

            </div>
        </div>
    </section>

</main>

<?php require dirname(__DIR__) . '/includes/footer.php';
