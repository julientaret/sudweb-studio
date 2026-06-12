<?php

declare(strict_types=1);

namespace App\Modules\GoogleReviews;

/**
 * Client minimal pour l'API Google Places (endpoints legacy). Zéro dépendance :
 * cURL natif (brief §1).
 *
 * Deux usages :
 *  - findPlace()   : Find Place from Text — résout un nom d'établissement tapé
 *    dans l'admin en candidats (place_id, nom, adresse) ;
 *  - fetchLatest() : Place Details — les 5 avis les plus récents.
 *
 * On utilise volontairement la version legacy : c'est la seule qui expose
 * reviews_sort=newest, indispensable pour récupérer les avis les PLUS RÉCENTS.
 * La nouvelle API Places (v1) renvoie les avis « les plus pertinents » sans
 * option de tri.
 */
final class GoogleReviewsClient
{
    private const DETAILS_ENDPOINT = 'https://maps.googleapis.com/maps/api/place/details/json';
    private const FIND_ENDPOINT    = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';

    public function __construct(private string $apiKey)
    {
    }

    /**
     * Recherche un établissement par son nom (champs Basic uniquement : gratuit).
     *
     * @return array<int, array{place_id: string, name: string, address: string}>
     * @throws \RuntimeException en cas d'erreur réseau ou de statut API non OK.
     */
    public function findPlace(string $query): array
    {
        $data = $this->request(self::FIND_ENDPOINT, [
            'input'     => $query,
            'inputtype' => 'textquery',
            'fields'    => 'place_id,name,formatted_address',
            'language'  => 'fr',
        ], allowZeroResults: true);

        $candidates = [];
        foreach ((array) ($data['candidates'] ?? []) as $c) {
            if (!is_array($c) || ($c['place_id'] ?? '') === '') {
                continue;
            }
            $candidates[] = [
                'place_id' => (string) $c['place_id'],
                'name'     => (string) ($c['name'] ?? ''),
                'address'  => (string) ($c['formatted_address'] ?? ''),
            ];
        }
        return $candidates;
    }

    /**
     * Récupère les 5 avis les plus récents + la note globale de l'établissement.
     *
     * @return array{rating: float|null, total: int|null, reviews: array<int, array<string, mixed>>}
     * @throws \RuntimeException en cas d'erreur réseau ou de statut API non OK.
     */
    public function fetchLatest(string $placeId): array
    {
        $data = $this->request(self::DETAILS_ENDPOINT, [
            'place_id'     => $placeId,
            'fields'       => 'reviews,rating,user_ratings_total',
            'reviews_sort' => 'newest',
            'language'     => 'fr',
        ]);

        $result = $data['result'] ?? [];

        return [
            'rating'  => isset($result['rating']) ? (float) $result['rating'] : null,
            'total'   => isset($result['user_ratings_total']) ? (int) $result['user_ratings_total'] : null,
            'reviews' => array_values($result['reviews'] ?? []),
        ];
    }

    /**
     * Appel GET commun : cURL, décodage JSON et contrôle du statut applicatif.
     *
     * @param array<string, string> $params
     * @return array<string, mixed>
     */
    private function request(string $endpoint, array $params, bool $allowZeroResults = false): array
    {
        $params['key'] = $this->apiKey;

        $ch = curl_init($endpoint . '?' . http_build_query($params));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException("Échec de l'appel Google Places : {$err}");
        }
        if ($code !== 200) {
            throw new \RuntimeException("Google Places a répondu HTTP {$code}.");
        }

        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Réponse Google Places illisible.');
        }

        // status Google : OK, ZERO_RESULTS, REQUEST_DENIED, OVER_QUERY_LIMIT, ...
        $status = $data['status'] ?? 'UNKNOWN';
        if ($status === 'ZERO_RESULTS' && $allowZeroResults) {
            return $data;
        }
        if ($status !== 'OK') {
            $msg = $data['error_message'] ?? $status;
            throw new \RuntimeException("Google Places : {$msg}");
        }

        return $data;
    }
}
