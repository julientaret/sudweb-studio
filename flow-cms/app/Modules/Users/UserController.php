<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;

/**
 * Gestion des comptes (brief §7).
 *
 * Tout est protégé par la permission users.manage. Le routeur étant minimal
 * (matching exact), l'identifiant cible des actions edit/update/destroy passe
 * en paramètre de requête `id` plutôt qu'en segment d'URL.
 */
final class UserController extends Controller
{
    private UserRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new UserRepository();
    }

    private function views(): array
    {
        return [__DIR__ . '/views'];
    }

    public function index(): void
    {
        $this->requirePermission('users.manage');
        $this->renderPage($this->views(), 'list', 'Utilisateurs', [
            'users' => $this->repo->all(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('users.manage');
        $this->renderForm(null);
    }

    public function store(): void
    {
        $this->requirePermission('users.manage');
        $this->requireCsrf();

        $email = $this->input('email');
        $name = $this->input('name');
        $role = $this->input('role', 'editor');
        $password = (string) ($_POST['password'] ?? '');

        $error = $this->validate($email, $role, $password, null);
        if ($error !== null) {
            $this->renderForm(null, $error, ['email' => $email, 'name' => $name, 'role' => $role]);
            return;
        }

        $this->repo->create($email, password_hash($password, Auth::hashAlgo()), $name, $role);
        $this->redirect('/admin/users');
    }

    public function edit(): void
    {
        $this->requirePermission('users.manage');
        $user = $this->repo->find((int) $this->input('id', '0'));
        if ($user === null || !$this->canManage($user)) {
            $this->redirect('/admin/users');
        }
        $this->renderForm($user);
    }

    public function update(): void
    {
        $this->requirePermission('users.manage');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        $user = $this->repo->find($id);
        // Anti-escalade : pas de modification (e-mail, mot de passe, rôle) d'un
        // compte dont le rôle dépasse nos propres droits.
        if ($user === null || !$this->canManage($user)) {
            $this->redirect('/admin/users');
        }

        $email = $this->input('email');
        $name = $this->input('name');
        $role = $this->input('role', 'editor');
        $password = (string) ($_POST['password'] ?? '');

        $error = $this->validate($email, $role, $password, $id);
        if ($error !== null) {
            $this->renderForm($user, $error, ['email' => $email, 'name' => $name, 'role' => $role]);
            return;
        }

        $hash = $password !== '' ? password_hash($password, Auth::hashAlgo()) : null;
        $this->repo->update($id, $email, $name, $role, $hash);
        $this->redirect('/admin/users');
    }

    public function destroy(): void
    {
        $this->requirePermission('users.manage');
        $this->requireCsrf();

        $id = (int) $this->input('id', '0');
        // Garde-fou : on ne supprime pas son propre compte.
        $current = $this->auth->user();
        $target = $this->repo->find($id);
        if ($current !== null && $current->id !== $id && $target !== null && $this->canManage($target)) {
            $this->repo->delete($id);
        }
        $this->redirect('/admin/users');
    }

    /**
     * @param array<string, string> $values
     */
    private function renderForm(?User $user, string $error = '', array $values = []): void
    {
        $this->renderPage($this->views(), 'form', $user ? 'Modifier un compte' : 'Nouveau compte', [
            'user'   => $user,
            'error'  => $error,
            'values' => $values,
            'roles'  => $this->grantableRoles(),
        ]);
    }

    /**
     * Rôles que l'utilisateur courant peut attribuer (ceux dont il détient
     * déjà toutes les permissions).
     *
     * @return array<int, string>
     */
    private function grantableRoles(): array
    {
        $current = $this->auth->user();
        if ($current === null) {
            return [];
        }
        return array_values(array_filter(
            array_keys((array) Config::get('roles', [])),
            static fn (string $role): bool => $current->canGrantRole($role),
        ));
    }

    /** Le compte cible est-il à notre portée (rôle couvert par nos droits) ? */
    private function canManage(User $target): bool
    {
        $current = $this->auth->user();
        return $current !== null && $current->canGrantRole($target->role);
    }

    /**
     * Valide les champs ; renvoie un message d'erreur ou null.
     */
    private function validate(string $email, string $role, string $password, ?int $editingId): ?string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Adresse e-mail invalide.';
        }
        if (!array_key_exists($role, (array) Config::get('roles', []))) {
            return 'Rôle inconnu.';
        }
        if (!in_array($role, $this->grantableRoles(), true)) {
            return 'Vous ne pouvez pas attribuer ce rôle.';
        }
        // Mot de passe obligatoire à la création ; facultatif en édition.
        if ($editingId === null && strlen($password) < 8) {
            return 'Le mot de passe doit faire au moins 8 caractères.';
        }
        if ($editingId !== null && $password !== '' && strlen($password) < 8) {
            return 'Le mot de passe doit faire au moins 8 caractères.';
        }
        $existing = $this->repo->findByEmail($email);
        if ($existing !== null && $existing->id !== $editingId) {
            return 'Cette adresse e-mail est déjà utilisée.';
        }
        return null;
    }
}
