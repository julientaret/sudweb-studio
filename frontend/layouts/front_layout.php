<?php
/**
 * Thème du site public (frontend) — surcharge le front_layout par défaut des
 * modules (cherché en premier via app.front_views).
 *
 * @var string $title
 * @var string $body      HTML déjà rendu et échappé par les blocs
 * @var string $siteName
 */

use App\Core\Config;

$base = rtrim((string) Config::get('app.base_path', ''), '/');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — <?= e($siteName) ?></title>
    <link rel="stylesheet" href="<?= e($base) ?>/assets/site.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="wrap">
        <?= $body ?>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
