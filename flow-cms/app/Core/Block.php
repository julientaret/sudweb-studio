<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Contrat d'un bloc de page (brief §4).
 *
 * Un bloc peut être statique (contenu figé) ou dynamique (son render() requête la
 * base en live) : le système ne fait pas de distinction. Les modules enregistrent
 * leurs propres blocs dans boot().
 *
 * Contrat posé dès le départ ; pas encore exploité tant que le module Pages n'existe pas.
 */
interface Block
{
    /** Type unique du bloc (ex. "heading", "gallery"). */
    public function type(): string;

    /**
     * Rend le HTML front du bloc. La sortie DOIT être échappée.
     *
     * @param array<string, mixed> $data
     */
    public function render(array $data): string;

    /**
     * Rend l'UI d'édition admin du bloc.
     *
     * @param array<string, mixed> $data
     */
    public function form(array $data): string;

    /**
     * Nettoie l'entrée utilisateur avant stockage.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitize(array $input): array;
}
