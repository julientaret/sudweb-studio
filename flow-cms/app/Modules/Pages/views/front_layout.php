<?php
/** @var string $title */
/** @var string $body  HTML déjà rendu et échappé par les blocs */
/** @var string $siteName */
/** @var string $base */
/** @var int $aosDuration */
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — <?= e($siteName) ?></title>
    <link rel="stylesheet" href="<?= e($base . '/assets/vendor/aos/aos.css') ?>">
    <style>
        :root { --txt:#1c2230; --muted:#5b6472; --accent:#2b8bff; --line:#e6e8ec; }
        * { box-sizing:border-box; }
        body { margin:0; font:17px/1.65 Georgia, 'Times New Roman', serif; color:var(--txt); background:#fff; }
        .wrap { max-width:720px; margin:0 auto; padding:3rem 1.2rem 5rem; }
        h1,h2,h3 { font-family:system-ui, sans-serif; line-height:1.25; }
        h1 { font-size:2.2rem; margin:0 0 1.5rem; }
        h2 { font-size:1.6rem; margin:2rem 0 .8rem; }
        h3 { font-size:1.25rem; margin:1.6rem 0 .6rem; }
        p { margin:0 0 1.1rem; }
        a { color:var(--accent); }
        ul, ol { margin:0 0 1.1rem 1.4rem; }
        .rte :last-child { margin-bottom:0; }
        figure.block-image { margin:1.8rem 0; }
        figure.block-image img { max-width:100%; height:auto; display:block; }
        .img-radius-none { border-radius:0; }
        .img-radius-sm { border-radius:4px; }
        .img-radius-md { border-radius:8px; }
        .img-radius-lg { border-radius:20px; }
        .img-radius-full { border-radius:50%; aspect-ratio:1/1; }
        .img-shadow-none { box-shadow:none; }
        .img-shadow-sm { box-shadow:0 2px 8px rgba(0,0,0,.12); }
        .img-shadow-md { box-shadow:0 8px 20px rgba(0,0,0,.16); }
        .img-shadow-lg { box-shadow:0 16px 36px rgba(0,0,0,.22); }
        blockquote.block-quote { margin:2rem 0; padding:0 0 0 1.4rem; border-left:4px solid var(--accent); }
        blockquote.block-quote p { margin:0; font-size:1.15rem; font-style:italic; }
        blockquote.block-quote cite { display:block; margin-top:.6rem; font-style:normal; font-size:.9rem; color:var(--muted); }
        a.block-button { display:inline-block; margin:.4rem 0; padding:.75rem 1.5rem; border-radius:9px; background:var(--accent); color:#fff; text-decoration:none; font-family:system-ui, sans-serif; font-weight:600; font-size:.95rem; }
        a.block-button:hover { filter:brightness(.9); }
        .block-accordion { margin:1.8rem 0; }
        .block-accordion-item { border-bottom:1px solid var(--line); padding:.9rem 0; }
        .block-accordion-item summary { cursor:pointer; font-weight:600; list-style:none; display:flex; align-items:center; justify-content:space-between; gap:1rem; }
        .block-accordion-item summary::-webkit-details-marker { display:none; }
        .block-accordion-item summary::after { content:"+"; flex:0 0 auto; font-size:1.3rem; line-height:1; color:var(--accent); }
        .block-accordion-item[open] summary::after { content:"\2212"; }
        .block-accordion-a { margin-top:.7rem; color:var(--muted); }
        .block-video { position:relative; margin:1.8rem 0; padding-top:56.25%; height:0; overflow:hidden; border-radius:8px; }
        .block-video iframe { position:absolute; inset:0; width:100%; height:100%; border:0; }
        .block-gallery { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:1rem; margin:1.8rem 0; }
        .block-gallery figure { margin:0; }
        .block-gallery img { width:100%; height:100%; aspect-ratio:4/3; object-fit:cover; border-radius:8px; display:block; }
        /* Lignes en colonnes (1 à 4), responsives. */
        .flow-row { display:grid; gap:1.6rem; margin:1.6rem 0; }
        .flow-row.cols-1 { grid-template-columns:1fr; }
        .flow-row.cols-2 { grid-template-columns:repeat(2,1fr); }
        .flow-row.cols-3 { grid-template-columns:repeat(3,1fr); }
        .flow-row.cols-4 { grid-template-columns:repeat(4,1fr); }
        .flow-col > :first-child { margin-top:0; }
        @media (max-width:640px) { .flow-row { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <main class="wrap">
        <?= $body ?>
    </main>
    <script src="<?= e($base . '/assets/vendor/aos/aos.js') ?>"></script>
    <script src="<?= e($base . '/assets/js/aos-init.js') ?>" data-aos-duration="<?= e((string) $aosDuration) ?>"></script>
</body>
</html>
