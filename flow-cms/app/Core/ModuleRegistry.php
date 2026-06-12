<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Registre des modules (brief §3).
 *
 * Lit la liste des modules activés (config/modules.php), instancie chaque
 * classe *Module et — au boot — appelle boot() pour qu'ils enregistrent leurs
 * routes, blocs et permissions. L'activation par site se fait donc en une ligne
 * de config.
 *
 * Convention : un module "Foo" vit dans App\Modules\Foo\FooModule.
 */
final class ModuleRegistry
{
    /** @var array<int, Module> */
    private array $modules = [];

    private bool $booted = false;

    /**
     * @param array<int, string> $enabled noms de modules (ex. ["Users"])
     */
    public function __construct(array $enabled)
    {
        foreach ($enabled as $name) {
            $this->modules[] = $this->instantiate($name);
        }
    }

    private function instantiate(string $name): Module
    {
        $class = "App\\Modules\\{$name}\\{$name}Module";
        if (!class_exists($class)) {
            throw new RuntimeException("Module introuvable : {$class}");
        }
        $module = new $class();
        if (!$module instanceof Module) {
            throw new RuntimeException("{$class} n'implémente pas App\\Core\\Module");
        }
        return $module;
    }

    /**
     * Démarre tous les modules : enregistrement des routes, blocs, permissions.
     */
    public function bootAll(Router $router): void
    {
        if ($this->booted) {
            return;
        }
        foreach ($this->modules as $module) {
            $module->boot($router);
        }
        $this->booted = true;
    }

    /**
     * @return array<int, Module>
     */
    public function modules(): array
    {
        return $this->modules;
    }

    /**
     * Entrées de menu admin agrégées de tous les modules.
     *
     * @return array<int, array{label: string, url: string, permission?: string}>
     */
    public function adminMenu(): array
    {
        $menu = [];
        foreach ($this->modules as $module) {
            foreach ($module->adminMenu() as $entry) {
                $menu[] = $entry;
            }
        }
        return $menu;
    }
}
