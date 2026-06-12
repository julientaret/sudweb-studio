<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Migrations SQL (brief §2).
 *
 * Collecte les fichiers .sql exposés par chaque module (Module::migrations())
 * plus le dossier transverse /migrations, et applique ceux qui ne l'ont pas
 * encore été. Le suivi se fait dans la table flow_migrations.
 *
 * Idempotent : relancer n'applique que les nouveaux fichiers.
 */
final class Migrator
{
    private PDO $pdo;
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->pdo = Database::pdo();
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Applique toutes les migrations en attente.
     *
     * @param ModuleRegistry $registry modules dont on collecte les migrations
     * @return array<int, string> noms des fichiers appliqués durant cet appel
     */
    public function run(ModuleRegistry $registry): array
    {
        $this->ensureTable();

        $applied = $this->appliedFiles();
        $done = [];

        foreach ($this->collect($registry) as $file) {
            $key = $this->key($file);
            if (in_array($key, $applied, true)) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                continue;
            }

            // Pas de transaction autour du SQL : en MySQL, le DDL (CREATE TABLE…)
            // provoque un commit implicite, ce qui rendrait un BEGIN/COMMIT explicite
            // inopérant. Chaque fichier de migration est donc sa propre unité.
            $this->pdo->exec($sql);
            $stmt = $this->pdo->prepare(
                'INSERT INTO flow_migrations (filename, applied_at) VALUES (:f, :a)'
            );
            $stmt->execute(['f' => $key, 'a' => date('Y-m-d H:i:s')]);

            $done[] = $key;
        }

        return $done;
    }

    private function ensureTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS flow_migrations (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                filename VARCHAR(255) NOT NULL,
                applied_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_filename (filename)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * @return array<int, string>
     */
    private function appliedFiles(): array
    {
        return $this->pdo->query('SELECT filename FROM flow_migrations')
            ->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Liste ordonnée des fichiers .sql à considérer.
     *
     * @return array<int, string>
     */
    private function collect(ModuleRegistry $registry): array
    {
        $files = [];

        // Migrations transverses du Core.
        foreach (glob($this->basePath . '/migrations/*.sql') ?: [] as $file) {
            $files[] = $file;
        }

        // Migrations de chaque module activé.
        foreach ($registry->modules() as $module) {
            foreach ($module->migrations() as $file) {
                if (is_file($file)) {
                    $files[] = $file;
                }
            }
        }

        sort($files);
        return $files;
    }

    /**
     * Clé stable et lisible d'une migration (relative au projet si possible).
     */
    private function key(string $file): string
    {
        if (str_starts_with($file, $this->basePath . '/')) {
            return substr($file, strlen($this->basePath) + 1);
        }
        return basename($file);
    }
}
