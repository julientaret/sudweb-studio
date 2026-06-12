<?php
/**
 * En-tête commun du site public.
 *
 * @var string $siteName
 */

use App\Core\Config;

$base = rtrim((string) Config::get('app.base_path', ''), '/');
?>
<header class="site-header">
    <div class="wrap">
        <a class="site-brand" href="<?= e($base) ?>/"><?= e($siteName) ?></a>
    </div>
</header>
