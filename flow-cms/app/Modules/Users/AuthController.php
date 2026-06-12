<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Controller;

/**
 * Connexion / déconnexion (brief §6).
 */
final class AuthController extends Controller
{
    /** Dossiers de vues du module. */
    private function views(): array
    {
        return [__DIR__ . '/views'];
    }

    public function showLogin(): void
    {
        if ($this->auth->check()) {
            $this->redirect('/admin');
        }
        $this->renderLogin();
    }

    public function login(): void
    {
        $this->requireCsrf();

        $email = $this->input('email');
        $password = (string) ($_POST['password'] ?? '');

        $user = $this->auth->attempt($email, $password, $this->clientIp());
        if ($user === null) {
            $this->renderLogin('Identifiants invalides ou compte temporairement bloqué.', $email);
            return;
        }

        $this->redirect('/admin');
    }

    public function logout(): void
    {
        $this->requireCsrf();
        $this->auth->logout();
        $this->redirect('/admin/login');
    }

    private function renderLogin(string $error = '', string $email = ''): void
    {
        // Page de login : pas de layout admin (pas de session), vue autoportante.
        $view = $this->view($this->views());
        echo $view->render('layout', [
            'title'   => 'Connexion',
            'user'    => null,
            'menu'    => [],
            'base'    => $this->basePath(),
            'content' => $view->render('login', [
                'error' => $error,
                'email' => $email,
                'base'  => $this->basePath(),
            ]),
        ]);
    }
}
