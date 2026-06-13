<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Contrat d'un bloc de page (brief §4).
 *
 * Un bloc peut être statique (contenu figé) ou dynamique (son render() requête la
 * base en live) : le système ne fait pas de distinction. Les blocs de base
 * (titre, texte) sont fournis par le Core ; les modules enregistrent leurs blocs
 * additionnels dans boot() (ex. Media → image, GoogleReviews → avis).
 */
interface Block
{
    /** Type unique du bloc (ex. "heading", "gallery"). */
    public function type(): string;

    /**
     * Libellé affiché dans le sélecteur de blocs de l'éditeur. Chaque bloc se
     * décrit lui-même : l'éditeur construit son catalogue depuis le registre, sans
     * table de libellés centralisée ni dépendance inter-modules.
     */
    public function label(): string;

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
