<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Registre des blocs de page (brief §4).
 *
 * Mappe type => instance de Block. Les modules y enregistrent leurs blocs dans
 * boot(). Contrat posé dès le départ ; pas encore exploité tant que le module
 * Pages n'existe pas.
 */
final class BlockRegistry
{
    /** @var array<string, Block> */
    private array $blocks = [];

    public function register(Block $block): void
    {
        $this->blocks[$block->type()] = $block;
    }

    public function has(string $type): bool
    {
        return isset($this->blocks[$type]);
    }

    public function get(string $type): Block
    {
        if (!isset($this->blocks[$type])) {
            throw new RuntimeException("Bloc inconnu : {$type}");
        }
        return $this->blocks[$type];
    }

    /**
     * Rend un bloc à partir de son type et de ses données (sortie échappée par le bloc).
     *
     * @param array<string, mixed> $data
     */
    public function render(string $type, array $data): string
    {
        return $this->get($type)->render($data);
    }
}
