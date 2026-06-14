<?php

declare(strict_types=1);

namespace App\Modules\Devis;

use App\Core\Config;
use App\Core\Kernel;

/**
 * Schéma du formulaire de devis, éditable depuis l'admin.
 *
 * Le pop-up public n'est plus figé : ses étapes et ses questions sont définies
 * ici et stockées dans storage/devis_form.json (comme les autres réglages
 * pilotés par l'admin — modules.json, maintenance.json). Tant que rien n'est
 * enregistré, on sert un schéma par défaut dérivé de config/devis.php, si bien
 * que le comportement reste identique jusqu'à la première personnalisation.
 *
 * Forme d'une étape :   ['title' => string, 'fields' => Field[]]
 * Forme d'une question : ['key','label','type','required','options','minlength','core']
 *
 * Deux questions « cœur » (clé `name` et `email`) sont toujours présentes : elles
 * alimentent les colonnes dédiées (liste admin, Reply-To de la notification) et
 * ne peuvent donc être ni supprimées ni changées de type.
 */
final class DevisFormSettings
{
    /** Types de question autorisés. */
    public const TYPES = ['text', 'email', 'tel', 'textarea', 'select'];

    /** Questions cœur : clé => type imposé. Toujours présentes, non supprimables. */
    public const CORE = ['name' => 'text', 'email' => 'email'];

    /** Clés réservées (champs techniques du formulaire) : interdites aux questions. */
    private const RESERVED = ['_csrf', 'website', 'company_url', 'consent', 'schema'];

    private string $statePath;

    public function __construct(?string $basePath = null)
    {
        $basePath ??= Kernel::instance()->basePath();
        $this->statePath = rtrim($basePath, '/') . '/storage/devis_form.json';
    }

    /**
     * Schéma effectif : celui enregistré s'il est valide, sinon le défaut.
     *
     * @return array<int, array{title:string, fields:array<int, array<string, mixed>>}>
     */
    public function schema(): array
    {
        if (is_file($this->statePath)) {
            $data = json_decode((string) file_get_contents($this->statePath), true);
            if (is_array($data) && isset($data['steps']) && is_array($data['steps'])) {
                return $this->normalize($data['steps']);
            }
        }
        return $this->normalize(self::defaultSteps());
    }

    /**
     * Enregistre un schéma (étapes brutes venues du constructeur admin).
     * Normalise, force les questions cœur, déduplique les clés.
     *
     * @param array<int, mixed> $steps
     */
    public function save(array $steps): void
    {
        $normalized = $this->normalize($steps);
        $json = json_encode(['steps' => $normalized], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->statePath, $json . "\n", LOCK_EX);
    }

    /**
     * Toutes les questions du schéma, à plat, dans l'ordre.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fields(): array
    {
        $out = [];
        foreach ($this->schema() as $step) {
            foreach ($step['fields'] as $field) {
                $out[] = $field;
            }
        }
        return $out;
    }

    /** Clé de la question utilisée comme e-mail de contact (Reply-To). */
    public function emailKey(): string
    {
        foreach ($this->fields() as $f) {
            if ($f['type'] === 'email') {
                return (string) $f['key'];
            }
        }
        return 'email';
    }

    // --- Normalisation ----------------------------------------------------

    /**
     * Nettoie un jeu d'étapes : types valides, clés sûres et uniques, options
     * pour les listes, et présence garantie des questions cœur.
     *
     * @param array<int, mixed> $steps
     * @return array<int, array{title:string, fields:array<int, array<string, mixed>>}>
     */
    private function normalize(array $steps): array
    {
        $seen = [];
        $out = [];

        foreach ($steps as $step) {
            if (!is_array($step)) {
                continue;
            }
            $title = $this->cleanLine((string) ($step['title'] ?? ''), 120);
            $fields = [];
            foreach ((array) ($step['fields'] ?? []) as $field) {
                $clean = $this->normalizeField($field, $seen);
                if ($clean !== null) {
                    $fields[] = $clean;
                    $seen[$clean['key']] = true;
                }
            }
            if ($title === '' && $fields === []) {
                continue;
            }
            $out[] = ['title' => $title !== '' ? $title : 'Étape', 'fields' => $fields];
        }

        if ($out === []) {
            $out = $this->normalize(self::defaultSteps());
            $seen = [];
            foreach ($out as $s) {
                foreach ($s['fields'] as $f) {
                    $seen[$f['key']] = true;
                }
            }
        }

        // Garantit les questions cœur (name + email) : réinjectées en fin de
        // dernière étape si l'admin les a supprimées.
        foreach (self::CORE as $key => $type) {
            if (!isset($seen[$key])) {
                $out[count($out) - 1]['fields'][] = [
                    'key'      => $key,
                    'label'    => $key === 'name' ? 'Nom' : 'E-mail',
                    'type'     => $type,
                    'required' => true,
                    'options'  => [],
                    'minlength' => 0,
                    'core'     => true,
                ];
                $seen[$key] = true;
            }
        }

        return $out;
    }

    /**
     * Normalise une question. Renvoie null si elle est inexploitable.
     *
     * @param array<string, bool> $seen clés déjà prises (déduplication)
     * @return array<string, mixed>|null
     */
    private function normalizeField(mixed $field, array $seen): ?array
    {
        if (!is_array($field)) {
            return null;
        }

        $label = $this->cleanLine((string) ($field['label'] ?? ''), 120);
        $rawKey = (string) ($field['key'] ?? '');
        $key = $this->safeKey($rawKey !== '' ? $rawKey : $label);
        if ($key === '' || in_array($key, self::RESERVED, true) || isset($seen[$key])) {
            // Clé vide, réservée ou déjà prise → on dérive une clé unique.
            $base = $key !== '' && !in_array($key, self::RESERVED, true) ? $key : 'question';
            $i = 2;
            $key = $base;
            while ($key === '' || isset($seen[$key]) || in_array($key, self::RESERVED, true)) {
                $key = $base . '_' . $i++;
            }
        }

        // Type : imposé pour les questions cœur, validé sinon.
        $core = isset(self::CORE[$key]);
        $type = $core ? self::CORE[$key] : (string) ($field['type'] ?? 'text');
        if (!in_array($type, self::TYPES, true)) {
            $type = 'text';
        }

        $options = [];
        if ($type === 'select') {
            foreach ((array) ($field['options'] ?? []) as $opt) {
                $opt = $this->cleanLine((string) $opt, 150);
                if ($opt !== '') {
                    $options[] = $opt;
                }
            }
        }

        $minlength = max(0, (int) ($field['minlength'] ?? 0));

        return [
            'key'       => $key,
            'label'     => $label !== '' ? $label : ucfirst($key),
            'type'      => $type,
            'required'  => $core ? true : (bool) ($field['required'] ?? false),
            'options'   => $options,
            'minlength' => $type === 'textarea' ? $minlength : 0,
            'core'      => $core,
        ];
    }

    private function safeKey(string $value): string
    {
        $key = slug($value);            // translittère + minuscule + tirets
        $key = str_replace('-', '_', $key);
        return mb_substr($key, 0, 60);
    }

    private function cleanLine(string $value, int $max): string
    {
        $value = trim(strip_tags($value));
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        return mb_substr($value, 0, $max);
    }

    /**
     * Schéma par défaut, dérivé de config/devis.php pour la continuité.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultSteps(): array
    {
        $types = (array) Config::get('devis.project_types', []);
        $budgets = (array) Config::get('devis.budgets', []);
        $deadlines = (array) Config::get('devis.deadlines', []);
        $descMin = (int) Config::get('devis.description_min_length', 10);

        return [
            ['title' => 'Votre projet', 'fields' => [
                ['key' => 'project_type', 'label' => 'Type de prestation', 'type' => 'select', 'required' => true, 'options' => $types],
            ]],
            ['title' => 'Détails du besoin', 'fields' => [
                ['key' => 'budget', 'label' => 'Budget estimé', 'type' => 'select', 'required' => true, 'options' => $budgets],
                ['key' => 'deadline', 'label' => 'Délai souhaité', 'type' => 'select', 'required' => true, 'options' => $deadlines],
                ['key' => 'description', 'label' => 'Décrivez votre projet', 'type' => 'textarea', 'required' => true, 'minlength' => $descMin],
            ]],
            ['title' => 'Vos coordonnées', 'fields' => [
                ['key' => 'name', 'label' => 'Nom', 'type' => 'text', 'required' => true],
                ['key' => 'company', 'label' => 'Société', 'type' => 'text', 'required' => false],
                ['key' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true],
                ['key' => 'phone', 'label' => 'Téléphone', 'type' => 'tel', 'required' => false],
            ]],
        ];
    }
}
