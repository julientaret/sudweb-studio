<?php

declare(strict_types=1);

namespace App\Core;

use App\Modules\Actualites\ArticleRepository;
use App\Modules\Agenda\EventRepository;
use App\Modules\Contact\ContactRepository;
use App\Modules\Devis\DevisRepository;
use App\Modules\Pages\PageRepository;
use App\Modules\Realisations\RealisationRepository;
use App\Modules\Users\UserRepository;

/**
 * Crée/supprime le compte de démonstration et les données factices associées
 * (App\Core\DemoSettings porte l'état ; cette classe fait le travail).
 *
 * Chaque module de contenu reçoit ses données via son Repository::create()
 * habituel (mêmes garde-fous que l'admin), puis la ligne insérée est marquée
 * `is_demo = 1` pour un nettoyage ciblé et sûr à la désactivation — jamais de
 * DELETE qui pourrait toucher de vraies données. Volontairement : PAS de photos
 * factices (Media/Documents non touchés), et seulement les modules activés.
 */
final class DemoSeeder
{
    private const EMAIL = 'demo@flow.local';
    private const NAME = 'Compte de démonstration';

    /** Tables marquées is_demo, dans l'ordre où les modules sont semés (purge dans le même ordre). */
    private const TABLES = ['pages', 'events', 'articles', 'realisations', 'quote_requests', 'contact_submissions'];

    private DemoSettings $settings;

    public function __construct(DemoSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Active le mode démo : purge d'abord un éventuel reste (activation
     * précédente mal terminée), crée le compte et les données factices.
     *
     * @return string mot de passe en clair du compte démo — jamais persisté,
     *                 à afficher une seule fois par l'appelant.
     */
    public function activate(): string
    {
        $this->purgeContent();
        $this->deleteExistingDemoUser();

        $password = bin2hex(random_bytes(6));
        $userId = (new UserRepository())->create(
            self::EMAIL,
            password_hash($password, Auth::hashAlgo()),
            self::NAME,
            'demo'
        );

        $this->seedPages();
        $this->seedAgenda();
        $this->seedActualites();
        $this->seedRealisations();
        $this->seedDevis();
        $this->seedContact();

        $this->settings->setUserId($userId);
        $this->settings->setEnabled(true);

        return $password;
    }

    public function deactivate(): void
    {
        $this->purgeContent();
        $this->deleteExistingDemoUser();
        $this->settings->setUserId(null);
        $this->settings->setEnabled(false);
    }

    public function accountEmail(): string
    {
        return self::EMAIL;
    }

    // --- Compte -------------------------------------------------------

    private function deleteExistingDemoUser(): void
    {
        $repo = new UserRepository();
        $existing = $repo->findByEmail(self::EMAIL);
        if ($existing !== null) {
            $repo->delete($existing->id);
        }
    }

    // --- Purge ----------------------------------------------------------

    private function purgeContent(): void
    {
        $pdo = Database::pdo();
        foreach (self::TABLES as $table) {
            // Best-effort : une table peut ne pas exister si son module n'a
            // jamais été activé (migrations jamais jouées).
            try {
                $pdo->exec("DELETE FROM {$table} WHERE is_demo = 1");
            } catch (\Throwable) {
                // silencieux
            }
        }
    }

    private function markDemo(string $table, int $id): void
    {
        $stmt = Database::pdo()->prepare("UPDATE {$table} SET is_demo = 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    // --- Semis par module (uniquement si le module est actif) -----------

    private function seedPages(): void
    {
        if (!module_enabled('Pages')) {
            return;
        }
        $repo = new PageRepository();
        $this->markDemo('pages', $repo->create(
            'Accueil (démo)',
            'accueil-demo',
            $this->demoContent('Bienvenue', 'Ceci est une page de démonstration. Modifiez-la, publiez-la, explorez librement l\'éditeur de blocs — rien ici n\'est définitif.'),
            'published'
        ));
        $this->markDemo('pages', $repo->create(
            'À propos (démo)',
            'a-propos-demo',
            $this->demoContent('Notre histoire', 'Racontez ici l\'histoire de votre entreprise, votre équipe, vos valeurs.'),
            'draft'
        ));
    }

    private function seedAgenda(): void
    {
        if (!module_enabled('Agenda')) {
            return;
        }
        $repo = new EventRepository();
        $this->markDemo('events', $repo->create(
            'Portes ouvertes (démo)',
            'portes-ouvertes-demo',
            date('Y-m-d', strtotime('+14 days')),
            null,
            null,
            $this->demoContent('Portes ouvertes', 'Un exemple d\'événement, avec ses dates et son contenu propre.'),
            'published'
        ));
    }

    private function seedActualites(): void
    {
        if (!module_enabled('Actualites')) {
            return;
        }
        $repo = new ArticleRepository();
        $this->markDemo('articles', $repo->create(
            'Nouveau site en ligne (démo)',
            'nouveau-site-demo',
            date('Y-m-d'),
            null,
            $this->demoContent('Nouveau site en ligne', 'Un exemple d\'actualité, pour annoncer vos nouveautés.'),
            'published'
        ));
    }

    private function seedRealisations(): void
    {
        if (!module_enabled('Realisations')) {
            return;
        }
        $repo = new RealisationRepository();
        $titles = ['Projet Alpha (démo)', 'Projet Beta (démo)', 'Projet Gamma (démo)'];
        foreach ($titles as $i => $title) {
            $this->markDemo('realisations', $repo->create(
                $title,
                ['ops' => [['insert' => "Description d'exemple pour cette réalisation.\n"]]],
                null,
                $i,
                'published'
            ));
        }
    }

    private function seedDevis(): void
    {
        if (!module_enabled('Devis')) {
            return;
        }
        $repo = new DevisRepository();
        $answers = (string) json_encode([
            ['label' => 'Type de projet', 'value' => 'Site vitrine'],
            ['label' => 'Budget', 'value' => '3 000 – 5 000 €'],
        ], JSON_UNESCAPED_UNICODE);
        $this->markDemo('quote_requests', $repo->create(
            'Camille Martin',
            'camille.martin@exemple.fr',
            $answers,
            true,
            'demo',
            null
        ));
    }

    private function seedContact(): void
    {
        if (!module_enabled('Contact')) {
            return;
        }
        $repo = new ContactRepository();
        $this->markDemo('contact_submissions', $repo->create(
            'Julien Petit',
            'julien.petit@exemple.fr',
            'Question sur vos tarifs',
            'Bonjour, je souhaiterais en savoir plus sur vos prestations. Ceci est une demande de démonstration.',
            true,
            'demo',
            null
        ));
    }

    /**
     * Contenu de démo minimal (titre + paragraphe), au format attendu par
     * App\Core\BlockContent (lignes → colonnes → blocs).
     *
     * @return array<int, array<string, mixed>>
     */
    private function demoContent(string $heading, string $text): array
    {
        return [
            [
                'id'      => 'r' . bin2hex(random_bytes(3)),
                'cols'    => 1,
                'columns' => [[
                    ['id' => 'b' . bin2hex(random_bytes(3)), 'type' => 'heading', 'data' => ['text' => $heading, 'level' => 2]],
                ]],
            ],
            [
                'id'      => 'r' . bin2hex(random_bytes(3)),
                'cols'    => 1,
                'columns' => [[
                    ['id' => 'b' . bin2hex(random_bytes(3)), 'type' => 'richtext', 'data' => ['delta' => ['ops' => [['insert' => $text . "\n"]]]]],
                ]],
            ],
        ];
    }
}
