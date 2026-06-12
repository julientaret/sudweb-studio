<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Templating PHP minimal (brief §1 : zéro dépendance).
 *
 * Les templates sont de simples fichiers .php. Les variables passées sont
 * extraites dans la portée du template, dont la sortie est capturée.
 *
 * L'échappement n'est jamais automatique : on appelle e() en sortie,
 * systématiquement (brief §4/§6).
 */
final class View
{
    /** Dossiers où chercher les templates, dans l'ordre. */
    private array $paths;

    /**
     * @param array<int, string> $paths
     */
    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * Rend un template et renvoie le HTML produit.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $file = $this->resolve($template);

        $render = static function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            include $__file;
            return (string) ob_get_clean();
        };

        return $render($file, $data);
    }

    /**
     * Rend un template et l'envoie directement au client.
     *
     * @param array<string, mixed> $data
     */
    public function display(string $template, array $data = []): void
    {
        echo $this->render($template, $data);
    }

    private function resolve(string $template): string
    {
        $template = ltrim($template, '/');
        foreach ($this->paths as $path) {
            $candidate = rtrim($path, '/') . '/' . $template . '.php';
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        throw new RuntimeException("Template introuvable : {$template}");
    }
}
