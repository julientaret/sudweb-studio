<?php

declare(strict_types=1);

/**
 * Front controller unique de Flow (brief §2).
 *
 * Seul ce dossier (public/) est exposé par le serveur web. Toutes les requêtes
 * passent ici : on démarre le noyau, qui charge la config, la session, les
 * en-têtes de sécurité, les modules, puis dispatche la route.
 */

use App\Core\Kernel;

require dirname(__DIR__) . '/vendor/autoload.php';

Kernel::boot(dirname(__DIR__))->handleWeb();
