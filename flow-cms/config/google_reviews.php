<?php

declare(strict_types=1);

use App\Core\Config;

/**
 * Module Avis Google : clé de l'API Google Places (brief §1 : les constantes
 * propres au serveur vivent dans .env.local). Facturation activée sur le projet
 * GCP ; le crédit mensuel couvre largement un site vitrine.
 *
 * L'établissement suivi (place_id) ne se configure PAS ici : il se choisit
 * depuis l'admin du module (champ « Rechercher l'établissement », API Find
 * Place) et vit dans storage/google_reviews.json.
 */
return [
    'api_key' => Config::env('GOOGLE_PLACES_API_KEY', ''),
];
