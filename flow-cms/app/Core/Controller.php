<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Contrôleur de base.
 *
 * Mutualise l'accès à l'auth et au rendu de vues, ainsi que les garde-fous
 * communs (permissions, redirections, vérification CSRF). Les contrôleurs de
 * module en héritent.
 */
abstract class Controller
{
    protected Auth $auth;

    public function __construct()
    {
        $this->auth = new Auth();
    }

    /**
     * Construit une vue cherchant d'abord dans les templates du module,
     * puis dans les vues communes du Core (layout, partials).
     *
     * @param array<int, string> $viewPaths
     */
    protected function view(array $viewPaths): View
    {
        $viewPaths[] = __DIR__ . '/views';
        return new View($viewPaths);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . $this->url($path));
        exit;
    }

    /**
     * Préfixe un chemin applicatif avec la base_url configurée.
     */
    protected function url(string $path): string
    {
        $base = rtrim((string) Config::get('app.base_path', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }

    /**
     * Exige une session authentifiée, sinon redirige vers le login.
     */
    protected function requireAuth(): void
    {
        if (!$this->auth->check()) {
            $this->redirect('/admin/login');
        }
    }

    /**
     * Exige une permission (brief §7), sinon 403.
     */
    protected function requirePermission(string $permission): void
    {
        $this->requireAuth();
        if (!$this->auth->can($permission)) {
            http_response_code(403);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><meta charset="utf-8"><title>403</title>'
                . '<h1>403 — Accès refusé</h1><p>Permission requise : '
                . e($permission) . '</p>';
            exit;
        }
    }

    /**
     * Vérifie le token CSRF d'une requête POST, sinon 419.
     */
    protected function requireCsrf(): void
    {
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><meta charset="utf-8"><title>419</title>'
                . '<h1>419 — Session expirée</h1><p>Jeton CSRF invalide.</p>';
            exit;
        }
    }

    protected function input(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    protected function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Rend une vue de module dans le layout admin commun.
     *
     * @param array<int, string>      $viewPaths dossiers de vues du module
     * @param array<string, mixed>    $data      variables de la vue interne
     */
    protected function renderPage(array $viewPaths, string $template, string $title, array $data = []): void
    {
        $view = $this->view($viewPaths);
        $content = $view->render($template, $data + ['base' => $this->basePath()]);

        $view->display('layout', [
            'title'   => $title,
            'content' => $content,
            'user'    => $this->auth->user(),
            'menu'    => $this->visibleMenu(),
            'base'    => $this->basePath(),
        ]);
    }

    protected function basePath(): string
    {
        return rtrim((string) Config::get('app.base_path', ''), '/');
    }

    /**
     * Entrées de menu admin visibles pour l'utilisateur courant (filtrées par permission).
     *
     * @return array<int, array{label: string, url: string}>
     */
    protected function visibleMenu(): array
    {
        $menu = [];
        foreach (Kernel::instance()->registry()->adminMenu() as $entry) {
            $permission = $entry['permission'] ?? null;
            if ($permission !== null && !$this->auth->can($permission)) {
                continue;
            }
            $menu[] = ['label' => $entry['label'], 'url' => $entry['url']];
        }
        return $menu;
    }
}
