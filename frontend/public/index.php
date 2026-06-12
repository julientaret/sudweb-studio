<?php

declare(strict_types=1);

/**
 * Front controller unique du site public (frontend).
 *
 * Réutilise le noyau du CMS (App\Core\Kernel) : routage, résolution de slug et
 * rendu des blocs restent l'unique source de vérité dans flow-cms/app. Ici on
 * se contente de démarrer ce noyau en mode front-only et de pointer le thème
 * (layouts/, includes/) vers ce dossier frontend.
 */

use App\Core\Config;
use App\Core\Kernel;

// Racine du CMS (dossier voisin) : autoload PSR-4 + config + modules y vivent.
$cmsRoot = dirname(__DIR__, 2) . '/flow-cms';

require $cmsRoot . '/vendor/autoload.php';

$kernel = Kernel::boot($cmsRoot);

// Thème du site public : ses templates surchargent ceux des modules (front_layout).
Config::set('app.front_views', [dirname(__DIR__) . '/layouts']);

$kernel->handleFront();
