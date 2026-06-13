<?php
/** @var string $title */
/** @var string $body  HTML déjà rendu et échappé par DeltaRenderer */
/** @var string $siteName */

// Gabarit de repli : en pratique surchargé par le thème du site public
// (frontend/layouts/front_layout.php), cherché en premier via app.front_views.
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — <?= e($siteName) ?></title>
    <style>
        :root { --txt:#1c2230; --muted:#5b6472; --accent:#2b8bff; }
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
        blockquote { margin:1.2rem 0; padding-left:1rem; border-left:3px solid #e6e8ec; color:var(--muted); }
        .rte :last-child { margin-bottom:0; }
    </style>
</head>
<body>
    <main class="wrap">
        <h1><?= e($title) ?></h1>
        <div class="rte"><?= $body ?></div>
    </main>
</body>
</html>
