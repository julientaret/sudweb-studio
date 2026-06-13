<?php
/**
 * Layout admin commun — shell dashboard moderne.
 *
 * Variables attendues :
 *  - string  $title
 *  - string  $content   (HTML déjà rendu et échappé par la vue appelante)
 *  - ?User   $user      utilisateur connecté (ou null sur le login)
 *  - array   $menu      entrées de menu visibles : [['label','url'], ...]
 *  - string  $base      préfixe d'URL applicatif
 */
/** @var string $title */
/** @var string $content */
$user = $user ?? null;
$menu = $menu ?? [];
$base = $base ?? '';

// Chemin courant (sans le préfixe) pour l'état actif du menu.
$curr = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($base !== '' && str_starts_with($curr, $base)) {
    $curr = substr($curr, strlen($base)) ?: '/';
}

// Icône (SVG inline, style trait) selon l'URL de l'entrée de menu — source
// unique partagée avec les cartes du tableau de bord (helpers.php → admin_icon).
$icon = static fn (string $url): string => admin_icon($url);

// Initiales pour l'avatar.
$display = $user !== null ? ($user->name !== '' ? $user->name : $user->email) : '';
$initials = '';
foreach (preg_split('/[\s@._-]+/', $display) ?: [] as $part) {
    if ($part !== '' && mb_strlen($initials) < 2) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
}
$initials = $initials !== '' ? $initials : 'U';

// Logo : PNG fourni par le client si présent, sinon le SVG de repli.
$logoRoot = \App\Core\Kernel::instance()->basePath() . '/public';
$logoUrl = match (true) {
    is_file($logoRoot . '/assets/flow-cms-logo.png') => $base . '/assets/flow-cms-logo.png',
    is_file($logoRoot . '/assets/img/flow-logo.png')  => $base . '/assets/img/flow-logo.png',
    default                                            => $base . '/assets/img/flow-logo.svg',
};
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — Flow</title>
    <style>
        :root {
            --bg:#f3f4fb; --panel:#ffffff; --line:#e9eaf3; --txt:#1b2030; --muted:#71768a;
            --accent:#2b8bff; --accent-600:#1f6fe0; --accent-soft:#e8f2ff;
            --grad:linear-gradient(135deg,#2b8bff 0%,#37c6f0 100%);
            --shadow-sm:0 1px 2px rgba(16,24,40,.05),0 1px 3px rgba(16,24,40,.05);
            --shadow-md:0 8px 24px rgba(16,24,40,.08);
            --rail:76px;
        }
        * { box-sizing:border-box; }
        body { margin:0; font:15px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI",system-ui,sans-serif;
               background:var(--bg); color:var(--txt); -webkit-font-smoothing:antialiased; }
        a { color:var(--accent-600); }

        /* ---- Shell ---- */
        .app { min-height:100vh; }
        .sidebar {
            position:fixed; top:0; left:0; bottom:0; width:var(--rail); z-index:50;
            background:var(--panel); border-right:1px solid var(--line);
            display:flex; flex-direction:column; padding:16px 0; overflow:hidden;
            transition:width .22s cubic-bezier(.4,0,.2,1), box-shadow .22s;
        }
        .sidebar:hover { width:252px; box-shadow:var(--shadow-md); }
        .brand { display:flex; align-items:center; gap:13px; padding:4px 18px 20px; }
        .brand .mark { width:44px; height:44px; flex:0 0 auto; border-radius:13px; background:#fff;
                       color:#fff; display:grid; place-items:center; font-weight:800; font-size:1.2rem;
                       box-shadow:0 8px 18px rgba(43,139,255,.45); overflow:hidden; }
        .brand .mark img { width:100%; height:100%; object-fit:cover; display:block; }
        .brand .word { font-weight:800; letter-spacing:.14em; font-size:1.05rem; white-space:nowrap;
                       opacity:0; transition:opacity .18s; }
        .sidebar:hover .brand .word { opacity:1; }

        .nav { flex:1; display:flex; flex-direction:column; gap:3px; padding:6px 14px; }
        .nav-item { display:flex; align-items:center; gap:15px; padding:11px 11px; border-radius:12px;
                    color:var(--muted); text-decoration:none; white-space:nowrap;
                    transition:background .15s,color .15s; }
        .nav-item svg { width:22px; height:22px; flex:0 0 auto; }
        .nav-item .label { opacity:0; transition:opacity .18s; font-weight:500; }
        .sidebar:hover .nav-item .label { opacity:1; }
        .nav-item:hover { background:var(--bg); color:var(--txt); }
        .nav-item.active { background:var(--accent-soft); color:var(--accent-600); font-weight:600; }

        .side-foot { padding:10px 16px 4px; }
        .side-tag { display:flex; align-items:center; gap:10px; color:var(--muted); font-size:.78rem; white-space:nowrap; }
        .side-tag .dot { width:8px; height:8px; border-radius:50%; background:#22c55e; flex:0 0 auto; box-shadow:0 0 0 3px rgba(34,197,94,.18); }
        .side-tag .txt { opacity:0; transition:opacity .18s; }
        .sidebar:hover .side-tag .txt { opacity:1; }

        .main { margin-left:var(--rail); min-height:100vh; display:flex; flex-direction:column; }
        .topbar { position:sticky; top:0; z-index:20; height:64px; display:flex; align-items:center;
                  justify-content:flex-end; gap:14px; padding:0 28px;
                  background:var(--grad); color:#fff;
                  border-bottom:1px solid rgba(255,255,255,.18);
                  box-shadow:0 2px 10px rgba(43,139,255,.25); }
        .user-chip { display:flex; align-items:center; gap:11px; }
        .user-meta { display:flex; flex-direction:column; line-height:1.15; margin-right:18px; }
        .user-meta strong { font-size:.86rem; color:#fff; }
        .user-meta span { font-size:.72rem; color:rgba(255,255,255,.75); text-transform:capitalize; }
        .icon-btn { width:38px; height:38px; margin:0; padding:0; border-radius:11px;
                    border:1px solid rgba(255,255,255,.4);
                    background:rgba(255,255,255,.12); color:#fff; display:grid; place-items:center; cursor:pointer;
                    transition:.15s; }
        .icon-btn:hover { color:#fff; border-color:#fff; background:rgba(255,255,255,.25); box-shadow:none; }
        .icon-btn svg { width:18px; height:18px; }
        .content { flex:1; width:100%; max-width:1500px; margin:0 auto; padding:28px 32px 56px; }
        .admin-foot { padding:18px 32px; border-top:1px solid var(--line); color:var(--muted);
                      font-size:.82rem; text-align:center; }
        .admin-foot a { color:var(--accent-600); text-decoration:none; font-weight:600; }
        .admin-foot a:hover { text-decoration:underline; }

        /* ---- Composants partagés (utilisés par les vues) ---- */
        h1 { font-size:1.6rem; font-weight:700; letter-spacing:-.02em; margin:0 0 1.3rem; }
        h2 { font-weight:700; letter-spacing:-.01em; }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:16px;
                padding:1.6rem; box-shadow:var(--shadow-sm); }
        /* Champs */
        label { display:block; margin:1.1rem 0 .4rem; color:var(--txt); font-size:.83rem; font-weight:600; }
        label:first-child { margin-top:0; }
        .field { margin-bottom:1.1rem; }
        .field:last-child { margin-bottom:0; }
        .field-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 1.1rem; }
        @media (max-width:560px){ .field-grid { grid-template-columns:1fr; } }
        .hint { margin:.4rem 0 0; color:var(--muted); font-size:.8rem; }
        .req { color:#e0457b; }

        input:not([type=checkbox]):not([type=radio]):not([type=file]):not([type=hidden]),
        select, textarea {
            width:100%; padding:.72rem .9rem; background:#fff; border:1px solid var(--line);
            border-radius:11px; color:var(--txt); font:inherit; font-size:.95rem;
            transition:border-color .15s, box-shadow .15s, background .15s;
        }
        textarea { min-height:90px; resize:vertical; }
        input::placeholder, textarea::placeholder { color:#a7adc0; }
        input:not([type=checkbox]):not([type=radio]):not([type=file]):hover,
        select:hover, textarea:hover { border-color:#cdd1e2; }
        input:focus, select:focus, textarea:focus { outline:none; border-color:var(--accent);
            background:#fff; box-shadow:0 0 0 4px rgba(43,139,255,.15); }

        /* Select : chevron personnalisé */
        select { appearance:none; -webkit-appearance:none; padding-right:2.5rem; cursor:pointer;
            background-image:url("data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='16'%20height='16'%20viewBox='0%200%2024%2024'%20fill='none'%20stroke='%2371768a'%20stroke-width='2.2'%20stroke-linecap='round'%20stroke-linejoin='round'%3E%3Cpolyline%20points='6%209%2012%2015%2018%209'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right .9rem center; background-size:16px; }

        /* Champ fichier (dropzone) */
        input[type=file] { width:100%; font:inherit; font-size:.9rem; color:var(--muted); cursor:pointer;
            border:1.5px dashed #cfd3e6; border-radius:12px; padding:.85rem; background:#fbfbff; transition:.15s; }
        input[type=file]:hover { border-color:var(--accent); background:#f6f7ff; }
        input[type=file]::file-selector-button { margin-right:.9rem; padding:.5rem .95rem; border:0;
            border-radius:9px; background:var(--accent-soft); color:var(--accent-600); font-weight:600;
            font:inherit; cursor:pointer; transition:.15s; }
        input[type=file]:hover::file-selector-button { background:#e2e5ff; }

        /* Cases & boutons radio */
        input[type=checkbox], input[type=radio] { width:auto; accent-color:var(--accent); }

        /* Champ avec icône */
        .input-icon { position:relative; }
        .input-icon > svg { position:absolute; left:.95rem; top:50%; transform:translateY(-50%);
            width:18px; height:18px; color:var(--muted); pointer-events:none; }
        .input-icon input:not([type=checkbox]):not([type=radio]):not([type=file]):not([type=hidden]) { padding-left:2.6rem; }

        /* Boutons */
        button, .btn { margin:0; padding:.7rem 1.2rem; background:var(--grad); color:#fff; border:0;
            border-radius:11px; cursor:pointer; font:inherit; font-weight:600; line-height:1;
            display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
            box-shadow:0 6px 14px rgba(43,139,255,.28); transition:transform .12s, box-shadow .15s, filter .15s; }
        button:hover, .btn:hover { filter:brightness(1.05); box-shadow:0 10px 22px rgba(43,139,255,.36); }
        button:active, .btn:active { transform:translateY(1px); }
        button.block { width:100%; padding:.82rem; }
        button.link { background:none; color:var(--muted); padding:0; box-shadow:none; font-weight:500; }
        button.link:hover { color:var(--accent-600); filter:none; box-shadow:none; }
        button.ghost, button.secondary { background:var(--accent-soft); color:var(--accent-600); box-shadow:none; }
        button.ghost:hover, button.secondary:hover { background:#e2e5ff; filter:none; }
        a.btn-secondary { display:inline-flex; align-items:center; gap:.4rem; padding:.7rem 1.1rem; border-radius:11px;
            border:1px solid var(--line); background:#fff; color:var(--txt); text-decoration:none; font-weight:600;
            transition:.15s; }
        a.btn-secondary:hover { border-color:var(--accent); color:var(--accent-600); }
        .form-actions { display:flex; align-items:center; gap:.9rem; margin-top:1.5rem; padding-top:1.2rem;
            border-top:1px solid var(--line); }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:.7rem .6rem; border-bottom:1px solid var(--line); }
        th { color:var(--muted); font-weight:600; font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; }
        tbody tr { transition:background .12s; }
        tbody tr:hover { background:#fafbff; }
        .alert { background:#fdecec; border:1px solid #f6bcbc; color:#a3211f; padding:.8rem 1rem; border-radius:11px; margin-bottom:1rem; }
        .notice { background:#e7f8ee; border:1px solid #a9e0c0; color:#15724a; padding:.8rem 1rem; border-radius:11px; margin-bottom:1rem; }
        .actions { display:flex; gap:1rem; align-items:center; }
        a.btn { display:inline-block; text-decoration:none; }
        .muted { color:var(--muted); }

        /* ---- Login ---- */
        .login-wrap { min-height:100vh; display:grid; place-items:center; padding:1.5rem;
                      background:radial-gradient(1200px 600px at 50% -10%, #eaecff 0%, var(--bg) 55%); }
        .login-box { width:100%; max-width:400px; }
        .login-brand { display:flex; flex-direction:column; align-items:center; gap:14px; margin-bottom:1.6rem; }
        .login-brand img { width:150px; height:auto; filter:drop-shadow(0 12px 26px rgba(43,139,255,.22)); }
        .login-brand .mark { width:54px; height:54px; border-radius:15px; background:var(--grad); color:#fff;
                             display:grid; place-items:center; font-weight:800; font-size:1.5rem;
                             box-shadow:0 10px 24px rgba(43,139,255,.45); }
        .login-box .card { border-radius:18px; box-shadow:var(--shadow-md); }
        .login-box h1 { text-align:center; font-size:1.3rem; }

        @media (max-width:760px) {
            .content { padding:20px 16px 40px; }
            .topbar { padding:0 16px; }
            .user-meta { display:none; }
        }
    </style>
</head>
<?php if ($user === null): ?>
<body>
    <div class="login-wrap">
        <div class="login-box">
            <div class="login-brand"><img src="<?= e($logoUrl) ?>" alt="Flow"></div>
            <?= $content ?>
        </div>
    </div>
</body>
<?php else: ?>
<body class="app">
    <aside class="sidebar">
        <a class="brand" href="<?= e($base . '/admin') ?>" style="text-decoration:none;color:inherit">
            <span class="mark"><img src="<?= e($base . '/assets/favicon.png') ?>" alt="Flow"></span><span class="word">Flow CMS</span>
        </a>
        <nav class="nav">
            <?php $dashActive = $curr === '/admin'; ?>
            <a class="nav-item<?= $dashActive ? ' active' : '' ?>" href="<?= e($base . '/admin') ?>">
                <?= $icon('/admin') ?><span class="label">Tableau de bord</span>
            </a>
            <?php foreach ($menu as $entry): ?>
                <?php $active = $curr === $entry['url'] || str_starts_with($curr, $entry['url'] . '/'); ?>
                <a class="nav-item<?= $active ? ' active' : '' ?>" href="<?= e($base . $entry['url']) ?>">
                    <?= $icon($entry['url']) ?><span class="label"><?= e($entry['label']) ?></span>
                </a>
            <?php endforeach; ?>
            <?php $tutoActive = $curr === '/admin/tutoriel'; ?>
            <a class="nav-item<?= $tutoActive ? ' active' : '' ?>" href="<?= e($base . '/admin/tutoriel') ?>">
                <?= $icon('/tutoriel') ?><span class="label">Tutoriel</span>
            </a>
            <?php if ($user->can('admin.modules')): ?>
                <?php $adminActive = $curr === '/admin/administration' || str_starts_with($curr, '/admin/administration/'); ?>
                <a class="nav-item<?= $adminActive ? ' active' : '' ?>" href="<?= e($base . '/admin/administration') ?>">
                    <?= $icon('/administration') ?><span class="label">Administration</span>
                </a>
            <?php endif; ?>
            <?php if ($user->can('admin.maintenance')): ?>
                <?php $mtnActive = $curr === '/admin/maintenance' || str_starts_with($curr, '/admin/maintenance/'); ?>
                <a class="nav-item<?= $mtnActive ? ' active' : '' ?>" href="<?= e($base . '/admin/maintenance') ?>">
                    <?= $icon('/maintenance') ?><span class="label">Maintenance</span>
                </a>
            <?php endif; ?>
        </nav>
        <div class="side-foot">
            <span class="side-tag"><span class="dot"></span><span class="txt">Flow · en ligne</span></span>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="user-chip">
                <div class="user-meta">
                    <strong><?= e($display) ?></strong>
                    <span><?= e($user->role) ?></span>
                </div>
                <form method="post" action="<?= e($base . '/admin/logout') ?>">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="icon-btn" title="Déconnexion">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </button>
                </form>
            </div>
        </header>
        <main class="content">
            <?= $content ?>
        </main>
        <footer class="admin-foot">
            Flow CMS — Copyright
            <a href="https://sudweb-studio.com" target="_blank" rel="noopener">Sud Web Studio</a>
        </footer>
    </div>
</body>
<?php endif; ?>
</html>
