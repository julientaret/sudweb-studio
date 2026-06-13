<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Activation des modules pilotée depuis l'admin (onglet Administration).
 *
 * Deux sources :
 *  - config/modules.php (`modules.available`) : le **catalogue** des modules
 *    installés pour ce site — il fixe aussi l'ordre de boot ;
 *  - storage/modules.json : l'état choisi par l'admin, qui stocke les modules
 *    **désactivés** (un module ajouté au catalogue est donc actif par défaut).
 *
 * La liste effective (catalogue − désactivés, dépendances résolues) est calculée
 * au boot par le Kernel, qui la publie dans `Config::set('modules.enabled')` :
 * tout le code existant (dashboard, etc.) continue de lire cette clé.
 *
 * Désactiver un module retire ses routes et son menu mais **ne touche pas à ses
 * données** : tables et fichiers restent en place pour une réactivation.
 */
final class ModuleSettings
{
    /** Modules non désactivables : l'authentification repose sur Users. */
    public const PROTECTED = ['Users'];

    /**
     * Dépendances inter-modules : un module n'est actif que si toutes ses
     * dépendances le sont (désactivation en cascade sinon).
     *
     * Volontairement vide : chaque module est indépendant. Les fonctionnalités
     * transverses sont soit fournies par le Core (page-builder, blocs de base,
     * DeltaRenderer), soit dégradées proprement quand le module fournisseur est
     * absent (p. ex. le choix d'image disparaît si Media est désactivé, sans
     * rendre Pages/Actualités/Agenda indisponibles). Le mécanisme de cascade
     * reste en place si une dépendance dure devait réapparaître un jour.
     *
     * @var array<string, array<int, string>>
     */
    public const DEPENDENCIES = [];

    /** Libellés et descriptions affichés dans l'onglet Administration. */
    public const INFO = [
        'Users'      => ['Utilisateurs', 'Comptes, rôles et connexion à l\'admin.'],
        'Media'      => ['Médias', 'Bibliothèque d\'images partagée (vignettes, blocs image).'],
        'Documents'  => ['Documents', 'Bibliothèque de documents téléchargeables (PDF…).'],
        'Pages'      => ['Pages', 'Pages du site et page-builder par blocs.'],
        'Agenda'     => ['Agenda', 'Événements datés avec page publique /agenda/….'],
        'Actualites' => ['Actualités', 'Articles d\'actualité avec page publique /actualites/….'],
        'GoogleReviews' => ['Avis Google', 'Import des avis Google de l\'établissement et bloc d\'affichage.'],
        'Contact'    => ['Contact', 'Formulaire public, demandes en base et notification e-mail au propriétaire.'],
        'LegalPages' => ['Pages légales', 'Mentions légales, politique de confidentialité et autres pages juridiques, éditées au texte riche.'],
        'Stats'      => ['Statistiques', 'Mesure d\'audience légère du site public, sans cookie ni service tiers (RGPD).'],
    ];

    private string $statePath;

    public function __construct(string $basePath)
    {
        $this->statePath = rtrim($basePath, '/') . '/storage/modules.json';
    }

    /**
     * Catalogue des modules installés (ordre de boot), depuis config/modules.php.
     *
     * @return array<int, string>
     */
    public function catalog(): array
    {
        return array_values(array_filter((array) Config::get('modules.available', []), 'is_string'));
    }

    /**
     * Modules désactivés par l'admin (état storage/modules.json).
     *
     * @return array<int, string>
     */
    public function disabled(): array
    {
        if (!is_file($this->statePath)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->statePath), true);
        if (!is_array($data)) {
            return [];
        }
        return array_values(array_filter((array) ($data['disabled'] ?? []), 'is_string'));
    }

    /**
     * Liste effective des modules actifs : catalogue moins les désactivés, modules
     * protégés forcés, et fermeture des dépendances (un module dont une dépendance
     * est inactive est désactivé en cascade). Préserve l'ordre du catalogue.
     *
     * @return array<int, string>
     */
    public function enabled(): array
    {
        $disabled = $this->disabled();
        $wanted = array_values(array_filter(
            $this->catalog(),
            fn (string $name): bool => !in_array($name, $disabled, true) || $this->isProtected($name)
        ));
        return $this->resolveDependencies($wanted)[0];
    }

    /**
     * Enregistre le choix de l'admin. Reçoit les modules **cochés** ; les modules
     * protégés sont forcés actifs et les dépendances résolues en cascade.
     *
     * @param array<int, string> $requested noms de modules à activer
     * @return array<int, string> modules désactivés en cascade (pour information)
     */
    public function save(array $requested): array
    {
        $wanted = array_values(array_filter(
            $this->catalog(),
            fn (string $name): bool => in_array($name, $requested, true) || $this->isProtected($name)
        ));
        [$enabled, $cascaded] = $this->resolveDependencies($wanted);

        $disabled = array_values(array_diff($this->catalog(), $enabled));
        $json = json_encode(['disabled' => $disabled], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->statePath, $json . "\n", LOCK_EX);

        return $cascaded;
    }

    public function isProtected(string $name): bool
    {
        return in_array($name, self::PROTECTED, true);
    }

    /** Libellé d'affichage d'un module (repli : son nom technique). */
    public function label(string $name): string
    {
        return self::INFO[$name][0] ?? $name;
    }

    /** Description d'affichage d'un module (repli : chaîne vide). */
    public function description(string $name): string
    {
        return self::INFO[$name][1] ?? '';
    }

    /**
     * Modules du catalogue qui dépendent (directement) du module donné.
     *
     * @return array<int, string>
     */
    public function dependents(string $name): array
    {
        $out = [];
        foreach ($this->catalog() as $candidate) {
            if (in_array($name, self::DEPENDENCIES[$candidate] ?? [], true)) {
                $out[] = $candidate;
            }
        }
        return $out;
    }

    /**
     * Ne garde que les modules dont toutes les dépendances sont actives,
     * en cascade jusqu'à stabilité.
     *
     * @param array<int, string> $wanted
     * @return array{0: array<int, string>, 1: array<int, string>} [actifs, retirés en cascade]
     */
    private function resolveDependencies(array $wanted): array
    {
        $cascaded = [];
        do {
            $changed = false;
            foreach ($wanted as $i => $name) {
                foreach (self::DEPENDENCIES[$name] ?? [] as $dep) {
                    if (!in_array($dep, $wanted, true)) {
                        unset($wanted[$i]);
                        $cascaded[] = $name;
                        $changed = true;
                        break;
                    }
                }
            }
            $wanted = array_values($wanted);
        } while ($changed);

        return [$wanted, $cascaded];
    }
}
