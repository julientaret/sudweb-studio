<?php

declare(strict_types=1);

namespace App\Modules\GoogleReviews;

use App\Core\Config;
use App\Core\Controller;

/**
 * Avis Google — back-office. Cinq actions :
 *   - index()       : la liste curable + le choix de l'établissement (la
 *     recherche Find Place passe par ?q=, lecture seule donc GET) ;
 *   - selectPlace() : enregistre l'établissement choisi parmi les candidats ;
 *   - sync()        : récupère les 5 derniers avis (clic bouton, verrou
 *     1/jour appliqué côté serveur) ;
 *   - toggle()      : bascule l'affichage d'un avis sur le front ;
 *   - purge()       : vide la table et lève le verrou de synchro.
 *
 * Permissions dans l'espace « googlereviews » (brief §7) :
 *   - view / sync / manage : détenues par admin et manager (le client) ;
 *   - configure : admin seul (via le wildcard `*`). Réservé à l'agence : choix
 *     de l'établissement (recherche Find Place) ET dispense du verrou 1/jour.
 *     Le manager reste limité à une synchro par jour.
 */
final class GoogleReviewsController extends Controller
{
    private GoogleReviewsRepository $repo;
    private GoogleReviewsSettings $settings;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new GoogleReviewsRepository();
        $this->settings = new GoogleReviewsSettings();
    }

    /** @return array<int, string> */
    private function views(): array
    {
        return [__DIR__ . '/views'];
    }

    public function index(): void
    {
        $this->requirePermission('googlereviews.view');

        $canManage    = $this->auth->can('googlereviews.manage');
        $canConfigure = $this->auth->can('googlereviews.configure');
        $hasApiKey    = $this->apiKey() !== '';

        // Recherche d'établissement (Find Place) : lancée depuis le formulaire ?q=.
        // Réservée à l'agence (googlereviews.configure).
        $q = $this->input('q');
        $candidates = [];
        $searchError = '';
        if ($q !== '' && $canConfigure && $hasApiKey) {
            try {
                $candidates = (new GoogleReviewsClient($this->apiKey()))->findPlace($q);
            } catch (\RuntimeException $e) {
                $searchError = 'Recherche impossible : ' . $e->getMessage();
            }
        }

        // Le verrou 1/jour ne s'applique pas à l'agence (configure).
        $syncLocked = $this->settings->syncedToday() && !$canConfigure;

        $this->renderPage($this->views(), 'list', 'Avis Google', [
            'reviews'      => $this->repo->allForAdmin(),
            'lastSync'     => $this->repo->lastSyncedAt(),
            'notice'       => $this->input('notice'),
            'error'        => $this->input('error'),
            'canSync'      => $this->auth->can('googlereviews.sync'),
            'canManage'    => $canManage,
            'canConfigure' => $canConfigure,
            'hasApiKey'    => $hasApiKey,
            'place'        => $this->settings->place(),
            'q'            => $q,
            'candidates'   => $candidates,
            'searchError'  => $searchError,
            'configured'   => $this->configured(),
            'syncLocked'   => $syncLocked,
        ]);
    }

    public function selectPlace(): void
    {
        $this->requirePermission('googlereviews.configure');
        $this->requireCsrf();

        $placeId = $this->input('place_id');
        if ($placeId === '') {
            $this->redirect('/admin/google-reviews?error=' . rawurlencode('Aucun établissement sélectionné.'));
        }

        $name = $this->input('name');
        $this->settings->savePlace($placeId, $name, $this->input('address'));

        $this->redirect('/admin/google-reviews?notice=' . rawurlencode(
            'Établissement enregistré' . ($name !== '' ? " : {$name}" : '') . '. Vous pouvez synchroniser les avis.'
        ));
    }

    public function sync(): void
    {
        $this->requirePermission('googlereviews.sync');
        $this->requireCsrf();

        if (!$this->configured()) {
            $this->redirect('/admin/google-reviews?error=' . rawurlencode(
                'Synchronisation impossible : clé API ou établissement manquant.'
            ));
        }

        // Verrou 1 synchro/jour, appliqué côté serveur (le bouton disabled ne
        // suffit pas). Une synchro en échec ne consomme pas le quota du jour.
        // L'agence (googlereviews.configure) n'est pas concernée par le verrou.
        if ($this->settings->syncedToday() && !$this->auth->can('googlereviews.configure')) {
            $this->redirect('/admin/google-reviews?error=' . rawurlencode(
                'Synchronisation déjà effectuée aujourd\'hui — réessayez demain.'
            ));
        }

        try {
            $payload = (new GoogleReviewsClient($this->apiKey()))->fetchLatest($this->settings->placeId());
            $this->settings->markSynced();
            $inserted = $this->repo->upsertMany($payload['reviews']);

            $notice = $inserted > 0
                ? "{$inserted} nouvel(s) avis importé(s)."
                : 'Synchronisation OK — aucun nouvel avis depuis la dernière fois.';
            $this->redirect('/admin/google-reviews?notice=' . rawurlencode($notice));
        } catch (\RuntimeException $e) {
            $this->redirect('/admin/google-reviews?error=' . rawurlencode(
                'Synchronisation impossible : ' . $e->getMessage()
            ));
        }
    }

    public function toggle(): void
    {
        $this->requirePermission('googlereviews.manage');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $visible = $this->input('visible') === '1';
        if ($id > 0) {
            $this->repo->setVisibility($id, $visible);
        }

        $this->redirect('/admin/google-reviews');
    }

    public function purge(): void
    {
        $this->requirePermission('googlereviews.manage');
        $this->requireCsrf();

        $this->repo->deleteAll();
        // Repartir de zéro sans attendre demain : la purge lève le verrou.
        $this->settings->clearLastSync();

        $this->redirect('/admin/google-reviews?notice=' . rawurlencode(
            'Table vidée. Lancez une synchronisation pour réimporter les avis.'
        ));
    }

    private function apiKey(): string
    {
        return (string) Config::get('google_reviews.api_key', '');
    }

    private function configured(): bool
    {
        return $this->apiKey() !== '' && $this->settings->placeId() !== '';
    }
}
