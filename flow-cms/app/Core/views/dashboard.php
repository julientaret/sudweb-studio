<?php
/** @var array<int, array{label:string, url:string}> $cards */
/** @var array<int, array{title:string,status:string,published:bool,updatedAt:string,editUrl:string,kind:string,badge:string}> $recent */
/** @var array<int, \App\Modules\Media\Media> $gallery */
/** @var \App\Modules\Users\User|null $user */
/** @var string $base */

$display = $user !== null ? ($user->name !== '' ? $user->name : $user->email) : '';

// Icône (SVG trait) + description selon la section. Les icônes restent alignées
// sur celles de la barre latérale (layout.php).
$meta = static function (string $url): array {
    $svg = static fn (string $p): string =>
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
        . 'stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';

    if (str_contains($url, '/pages')) {
        return [
            $svg('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'),
            'Créer, modifier et publier les pages du site.',
        ];
    }
    if (str_contains($url, '/media')) {
        return [
            $svg('<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'),
            'Téléverser et organiser la bibliothèque d’images.',
        ];
    }
    if (str_contains($url, '/documents')) {
        return [
            $svg('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/>'),
            'Téléverser et réutiliser les documents (PDF, Word…).',
        ];
    }
    if (str_contains($url, '/users')) {
        return [
            $svg('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'),
            'Gérer les comptes et leurs rôles.',
        ];
    }
    if (str_contains($url, '/tutoriel')) {
        return [
            $svg('<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>'),
            'Prendre en main les différentes parties du CMS.',
        ];
    }
    return [
        $svg('<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>'),
        'Ouvrir cette section.',
    ];
};
?>
<?php
// Rend une cellule de carte (conteneur déplaçable + carte + bouton retirer).
// Utilisée aussi bien pour la grille visible que pour le stock des cartes masquées.
$cell = static function (array $c, bool $fixed) use ($meta, $base): void {
    [$icon, $desc] = $meta($c['url']);
    $tuto = str_contains($c['url'], '/tutoriel');
    $cls = 'dash-cell' . ($fixed ? ' dash-cell--fixed' : '') . ($tuto ? ' span-2' : '');
    ?>
    <div class="<?= $cls ?>"<?= $fixed ? '' : ' data-key="' . e($c['url']) . '"' ?>>
        <a class="dash-card<?= $tuto ? ' dash-card--tuto' : '' ?>" href="<?= e($base . $c['url']) ?>">
            <span class="dash-ic"><?= $icon ?></span>
            <span class="dash-body">
                <strong><?= e($c['label']) ?></strong>
                <span class="dash-desc"><?= e($desc) ?></span>
            </span>
            <span class="dash-arrow" aria-hidden="true">→</span>
        </a>
        <?php if (!$fixed): ?>
            <button type="button" class="dash-remove" data-remove aria-label="Retirer cette carte">×</button>
        <?php endif; ?>
    </div>
<?php
};
?>
<div class="dash-head">
    <h1>Bonjour<?= $display !== '' ? ', ' . e($display) : '' ?> 👋</h1>
    <p class="muted">Accès rapide aux différentes parties de l’administration.</p>
    <button type="button" class="dash-edit" data-dash-edit>Personnaliser</button>
</div>

<div class="dash-grid" data-dash-grid
     data-save-url="<?= e($base . '/admin/dashboard/layout') ?>"
     data-csrf="<?= e($csrf) ?>">
    <?php foreach ($cards as $c) { $cell($c, false); } ?>
    <?php $cell(['label' => 'Tutoriel', 'url' => '/admin/tutoriel'], true); ?>
</div>

<div class="dash-add" data-dash-add hidden>
    <h3 class="dash-add-title">Ajouter une carte</h3>
    <div class="dash-add-list" data-add-list></div>
    <p class="muted dash-add-empty">Toutes les cartes sont affichées.</p>
</div>

<?php // Stock des cartes masquées : le JS les transforme en pastilles au chargement. ?>
<div data-hidden-cells hidden>
    <?php foreach ($hidden as $c) { $cell($c, false); } ?>
</div>

<div class="dash-bottom<?= $gallery === [] ? ' single' : '' ?>">
    <section class="dash-recent card">
        <h2 class="dr-title-h">Dernières modifications</h2>
        <?php if ($recent === []): ?>
            <p class="muted" style="margin:.3rem 0 0;">Aucune modification de contenu pour l'instant.</p>
        <?php else: ?>
            <ul class="dr-list">
                <?php foreach ($recent as $item): ?>
                    <li>
                        <a class="dr-item" href="<?= e($base . $item['editUrl']) ?>">
                            <span class="dr-name">
                                <span class="dr-kind"><?= e($item['kind']) ?></span>
                                <?= e($item['title']) ?>
                            </span>
                            <span class="dr-meta">
                                <span class="dr-badge dr-<?= e($item['status']) ?>"><?= e($item['badge']) ?></span>
                                <span class="dr-date"><?= e(date('d/m/Y H:i', strtotime($item['updatedAt']) ?: time())) ?></span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if ($gallery !== []): ?>
        <section class="dash-gallery" data-gallery>
            <div class="dg-head">
                <h2>Galerie</h2>
                <div class="dg-nav">
                    <button type="button" class="dg-btn" data-prev aria-label="Image précédente">‹</button>
                    <button type="button" class="dg-btn" data-next aria-label="Image suivante">›</button>
                </div>
            </div>
            <div class="dg-frame">
                <div class="dg-track">
                    <?php foreach ($gallery as $m): ?>
                        <?php $caption = $m->alt !== '' ? $m->alt : $m->originalName; ?>
                        <figure class="dg-slide">
                            <picture>
                                <source type="image/webp" srcset="<?= e(\App\Modules\Media\MediaUrl::size($m, 'medium')) ?>">
                                <img src="<?= e(\App\Modules\Media\MediaUrl::original($m)) ?>" alt="<?= e($m->alt) ?>" loading="lazy">
                            </picture>
                            <?php if ($caption !== ''): ?>
                                <figcaption><?= e($caption) ?></figcaption>
                            <?php endif; ?>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="dg-dots" data-dots></div>
        </section>
    <?php endif; ?>
</div>

<style>
    .dash-head { position:relative; margin-bottom:1.7rem; }
    .dash-head h1 { margin:0 0 .35rem; }
    .dash-head .muted { color:var(--muted); margin:0; }
    /* Bouton « Personnaliser » (bascule le mode édition). */
    .dash-edit { position:absolute; top:0; right:0; padding:.45rem .9rem; font-size:.85rem;
                 background:var(--panel); border:1px solid var(--line); border-radius:10px;
                 color:var(--txt); cursor:pointer; box-shadow:var(--shadow-sm); }
    .dash-edit:hover { border-color:var(--accent); color:var(--accent-600); }
    .dash-edit.is-active { background:var(--grad); border-color:transparent; color:#fff; }
    .dash-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:1.1rem; }
    .dash-cell { position:relative; display:flex; }
    .dash-cell.span-2 { grid-column:span 2; }
    .dash-card { flex:1 1 auto; }
    /* Carte Tutoriel : mise en avant en bleu (sélecteur composé pour primer sur .dash-card). */
    .dash-card.dash-card--tuto { background:var(--grad); border-color:transparent; color:#fff; }
    .dash-card.dash-card--tuto:hover { border-color:transparent; }
    .dash-card--tuto .dash-ic { background:rgba(255,255,255,.22); color:#fff; }
    .dash-card--tuto .dash-desc { color:rgba(255,255,255,.88); }
    .dash-card--tuto .dash-arrow { color:#fff; }
    .dash-card--tuto:hover .dash-arrow { color:#fff; }
    @media (max-width:900px) { .dash-grid { grid-template-columns:repeat(2, 1fr); } }
    @media (max-width:560px) { .dash-grid { grid-template-columns:1fr; } .dash-cell.span-2 { grid-column:auto; } }
    .dash-card { position:relative; display:flex; gap:1rem; align-items:flex-start; padding:1.2rem 1.25rem;
                 background:var(--panel); border:1px solid var(--line); border-radius:16px;
                 text-decoration:none; color:inherit; box-shadow:var(--shadow-sm);
                 transition:transform .15s, box-shadow .15s, border-color .15s; }
    .dash-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-md); border-color:var(--accent); }
    .dash-ic { width:46px; height:46px; flex:0 0 auto; border-radius:13px; display:grid; place-items:center;
               background:var(--accent-soft); color:var(--accent-600); }
    .dash-ic svg { width:23px; height:23px; }
    .dash-body { display:flex; flex-direction:column; gap:.2rem; }
    .dash-body strong { font-size:1rem; }
    .dash-desc { color:var(--muted); font-size:.85rem; line-height:1.45; }
    .dash-arrow { margin-left:auto; align-self:center; color:var(--muted); font-size:1.1rem;
                  transition:transform .15s, color .15s; }
    .dash-card:hover .dash-arrow { transform:translateX(3px); color:var(--accent-600); }

    /* --- Mode édition de l'accès rapide ---------------------------------- */
    /* Bouton retirer (croix) : masqué hors édition, en surimpression sinon. */
    .dash-remove { position:absolute; top:-8px; right:-8px; width:24px; height:24px; display:none;
                   place-items:center; padding:0; border:0; border-radius:50%; background:#e5484d;
                   color:#fff; font-size:1rem; line-height:1; cursor:pointer; z-index:3;
                   box-shadow:var(--shadow-sm); }
    .dash-remove:hover { background:#c4373b; filter:none; }
    .dash-grid.editing .dash-cell[data-key] { cursor:grab; }
    .dash-grid.editing .dash-cell[data-key]:active { cursor:grabbing; }
    .dash-grid.editing .dash-cell[data-key] .dash-card { pointer-events:none; }
    .dash-grid.editing .dash-cell[data-key] .dash-remove { display:grid; }
    .dash-grid.editing .dash-cell[data-key] .dash-card { border-style:dashed; }
    .dash-cell.dragging { opacity:.45; }
    .dash-cell.drop-target { outline:2px dashed var(--accent); outline-offset:3px; border-radius:16px; }

    /* Panneau d'ajout : pastilles des cartes masquées. */
    .dash-add { margin-top:1.4rem; padding:1.1rem 1.25rem; background:var(--panel);
                border:1px dashed var(--line); border-radius:16px; }
    .dash-add-title { margin:0 0 .8rem; font-size:1rem; }
    .dash-add-list { display:flex; flex-wrap:wrap; gap:.7rem; }
    .dash-chip { display:inline-flex; align-items:center; gap:.55rem; padding:.5rem .8rem;
                 background:var(--accent-soft); border:1px solid transparent; border-radius:12px;
                 color:var(--txt); font-size:.9rem; cursor:pointer; }
    .dash-chip:hover { border-color:var(--accent); filter:none; }
    .dash-chip-ic { width:22px; height:22px; display:grid; place-items:center; color:var(--accent-600); }
    .dash-chip-ic svg { width:18px; height:18px; }
    .dash-chip-plus { font-size:1.1rem; line-height:1; color:var(--accent-600); }
    .dash-add-empty { margin:0; }
    .dash-add-list:empty + .dash-add-empty { display:block; }
    .dash-add-list:not(:empty) + .dash-add-empty { display:none; }

    /* Rangée basse : modifications récentes (gauche) + galerie (droite). */
    .dash-bottom { display:grid; grid-template-columns:1fr 1fr; gap:1.4rem; align-items:stretch;
                   grid-auto-rows:minmax(0, 400px); margin-top:2rem; }
    .dash-bottom.single { grid-template-columns:1fr; }
    @media (max-width:900px) { .dash-bottom { grid-template-columns:1fr; } }

    /* Dernières modifications de contenu. */
    .dash-recent { display:flex; flex-direction:column; min-height:0; overflow:hidden; }
    .dr-title-h { margin:0 0 .6rem; font-size:1.1rem; }
    .dr-list { list-style:none; margin:0; padding:0; flex:1; min-height:0; overflow:auto; }
    .dr-list li + li { border-top:1px solid var(--line); }
    .dr-item { display:flex; align-items:center; justify-content:space-between; gap:.9rem;
               padding:.7rem 0; text-decoration:none; color:inherit; }
    .dr-name { font-weight:600; font-size:.92rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .dr-item:hover .dr-name { color:var(--accent-600); }
    .dr-kind { display:inline-block; margin-right:.4rem; font-size:.68rem; font-weight:600;
               text-transform:uppercase; letter-spacing:.03em; color:var(--accent);
               background:var(--accent-soft); padding:.1rem .4rem; border-radius:5px; vertical-align:middle; }
    .dr-meta { display:flex; align-items:center; gap:.6rem; flex:0 0 auto; }
    .dr-badge { font-size:.72rem; padding:.15rem .5rem; border-radius:6px; font-weight:600; }
    .dr-published { background:#e7f6ec; color:#1c6b3a; }
    .dr-draft { background:#fdf0d5; color:#8a5a00; }
    .dr-date { color:var(--muted); font-size:.78rem; white-space:nowrap; }

    /* Visionneuse galerie (slider). En colonne flex pour s'aligner sur la hauteur
       du bloc voisin : le cadre remplit l'espace restant (flex:1). */
    .dash-gallery { position:relative; display:flex; flex-direction:column; }
    /* En-tête (titre + flèches) superposé en haut du slider. */
    .dg-head { position:absolute; top:0; left:0; right:0; z-index:2; margin:0; padding:.7rem .9rem;
               display:flex; align-items:center; justify-content:space-between; border-radius:16px 16px 0 0;
               background:linear-gradient(rgba(0,0,0,.5), transparent); }
    .dg-head h2 { margin:0; font-size:1.1rem; color:#fff; }
    .dg-nav { display:flex; gap:.4rem; }
    .dg-btn { padding:.2rem .7rem; background:rgba(255,255,255,.85); border:0; color:var(--txt);
              border-radius:9px; box-shadow:none; font-size:1.15rem; line-height:1.2; }
    .dg-btn:hover { background:#fff; color:var(--accent-600); filter:none; box-shadow:none; }
    .dg-frame { flex:1; min-height:300px; overflow:hidden; border-radius:16px; border:1px solid var(--line); background:#0b0d12; }
    .dg-track { display:flex; height:100%; transition:transform .4s ease; }
    .dg-slide { position:relative; margin:0; flex:0 0 100%; height:100%; }
    .dg-slide img { width:100%; height:100%; object-fit:contain; display:block; }
    .dg-slide figcaption { position:absolute; left:0; right:0; bottom:0; padding:1.6rem .9rem .9rem; color:#fff;
                           font-size:.85rem; background:linear-gradient(transparent, rgba(0,0,0,.65)); }
    /* Pager (points) superposé en bas du slider. */
    .dg-dots { position:absolute; bottom:.7rem; left:0; right:0; z-index:2;
               display:flex; gap:.4rem; justify-content:center; }
    .dg-dot { width:8px; height:8px; padding:0; border:0; border-radius:50%; background:rgba(255,255,255,.5);
              cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,.4); transition:background .15s; }
    .dg-dot:hover { filter:none; box-shadow:0 1px 2px rgba(0,0,0,.4); background:rgba(255,255,255,.8); }
    .dg-dot.active { background:#fff; }
</style>

<script src="<?= e($base . '/assets/js/dashboard.js') ?>"></script>
