<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Routeur (brief §2 : point d'entrée unique, codé à la main).
 *
 * Matching EXACT prioritaire ; à défaut, routes à segments dynamiques
 * (ex. /{slug}) testées dans l'ordre d'enregistrement. Les routes exactes (admin)
 * gagnent donc toujours sur le catch-all front des Pages.
 *
 * Un handler est soit un callable, soit [Controller::class, 'method']. Pour une
 * route dynamique, le handler reçoit le tableau associatif des paramètres.
 */
final class Router
{
    /** @var array<string, array<string, callable|array{0: class-string, 1: string}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    /** @var array<string, array<int, array{regex: string, names: array<int,string>, handler: callable|array{0: class-string, 1: string}}>> */
    private array $patterns = ['GET' => [], 'POST' => []];

    /** Préfixe d'URL à retirer (déploiement en sous-dossier), ex. "/flow-cms". */
    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     */
    private function add(string $method, string $path, callable|array $handler): void
    {
        $path = $this->normalize($path);
        if (!str_contains($path, '{')) {
            $this->routes[$method][$path] = $handler;
            return;
        }
        // Segment dynamique : {name} → un segment d'URL (sans slash).
        $names = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $m) use (&$names): string {
            $names[] = $m[1];
            return '([^/]+)';
        }, $path);
        $this->patterns[$method][] = [
            'regex'   => '#^' . $regex . '$#',
            'names'   => $names,
            'handler' => $handler,
        ];
    }

    /**
     * Résout et exécute la route correspondant à la requête courante.
     */
    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = $this->stripBase($this->normalize(parse_url($uri, PHP_URL_PATH) ?: '/'));

        // 1) Route exacte (prioritaire).
        $handler = $this->routes[$method][$path] ?? null;
        if ($handler !== null) {
            $this->invoke($handler, []);
            return;
        }

        // 2) Routes à segments dynamiques, dans l'ordre d'enregistrement.
        foreach ($this->patterns[$method] ?? [] as $route) {
            if (preg_match($route['regex'], $path, $m)) {
                $params = [];
                foreach ($route['names'] as $i => $name) {
                    $params[$name] = rawurldecode($m[$i + 1]);
                }
                $this->invoke($route['handler'], $params);
                return;
            }
        }

        $this->notFound();
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     * @param array<string, string> $params
     */
    private function invoke(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $action] = $handler;
            (new $class())->{$action}($params);
            return;
        }
        $handler($params);
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function stripBase(string $path): string
    {
        if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath));
            $path = $path === '' ? '/' : $path;
        }
        return $path;
    }

    private function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>404</title>'
            . '<h1>404 — Page introuvable</h1>';
    }
}
