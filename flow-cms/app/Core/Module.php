<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Contrat commun à tous les modules de Flow (brief §3).
 *
 * Un module est un dossier autosuffisant : il porte ses migrations, ses routes
 * (front + admin), ses entrées de menu et — via boot() — l'enregistrement de ses
 * blocs et permissions.
 */
interface Module
{
    /** Identifiant court et stable du module (ex. "users", "blog"). */
    public function name(): string;

    /**
     * Point d'entrée appelé au démarrage par le ModuleRegistry.
     * C'est ici que le module enregistre ses routes, ses blocs et ses permissions.
     */
    public function boot(Router $router): void;

    /**
     * Entrées du back-office.
     * Chaque entrée : ['label' => string, 'url' => string, 'permission' => ?string].
     *
     * @return array<int, array{label: string, url: string, permission?: string}>
     */
    public function adminMenu(): array;

    /**
     * Chemins absolus des fichiers SQL à appliquer, dans l'ordre.
     *
     * @return array<int, string>
     */
    public function migrations(): array;
}
