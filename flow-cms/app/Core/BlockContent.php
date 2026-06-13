<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base commune « contenu » du page-builder (brief §3 & §4).
 *
 * Factorise la logique de blocs partagée par les modules de contenu (Pages,
 * Agenda, et plus tard Blog/News) : reconstruction du JSON depuis le POST,
 * normalisation du contenu stocké, rendu front HTML et modèle de vue de
 * l'éditeur. Aucune duplication par module : un seul endroit pour le contrat §4.
 *
 * Le registre des blocs est partagé (Kernel::blocks()) ; chaque bloc porte son
 * propre libellé (Block::label()), si bien que l'éditeur construit son catalogue
 * directement depuis le registre, sans table de libellés ni dépendance entre
 * modules de contenu.
 */
final class BlockContent
{
    /**
     * Reconstruit le contenu depuis le POST : structure de lignes décrite par le
     * champ `layout` (JSON), chaque bloc référencé par clé étant passé par son
     * sanitize() (brief §4). Chaque ligne porte 1 à 4 colonnes de blocs ordonnés.
     *
     * @return array<int, array{id:string,cols:int,columns:array<int,array<int,array{id:string,type:string,data:array<string,mixed>}>>}>
     */
    public static function fromRequest(): array
    {
        $layout = json_decode((string) ($_POST['layout'] ?? ''), true);
        $blocks = $_POST['blocks'] ?? [];
        if (!is_array($layout) || !is_array($blocks)) {
            return [];
        }

        $registry = Kernel::instance()->blocks();
        $rows = [];

        foreach ($layout as $row) {
            if (!is_array($row)) {
                continue;
            }
            $cols = max(1, min(4, (int) ($row['cols'] ?? 1)));
            $rawColumns = is_array($row['columns'] ?? null) ? $row['columns'] : [];

            $columns = [];
            for ($i = 0; $i < $cols; $i++) {
                $keys = is_array($rawColumns[$i] ?? null) ? $rawColumns[$i] : [];
                $colBlocks = [];
                foreach ($keys as $key) {
                    $raw = $blocks[$key] ?? null;
                    if (!is_array($raw)) {
                        continue;
                    }
                    $type = (string) ($raw['type'] ?? '');
                    if (!$registry->has($type)) {
                        continue;
                    }
                    $data = is_array($raw['data'] ?? null) ? $raw['data'] : [];
                    $colBlocks[] = [
                        'id'   => 'b' . bin2hex(random_bytes(3)),
                        'type' => $type,
                        'data' => $registry->get($type)->sanitize($data),
                    ];
                }
                $columns[] = $colBlocks;
            }

            $rows[] = ['id' => 'r' . bin2hex(random_bytes(3)), 'cols' => $cols, 'columns' => $columns];
        }

        return $rows;
    }

    /**
     * Rend le contenu stocké en HTML front (sortie échappée par chaque bloc).
     * Lignes en grille `.flow-row.cols-N`, blocs empilés par colonne `.flow-col`.
     *
     * @param array<int, mixed> $content
     */
    public static function render(array $content): string
    {
        $registry = Kernel::instance()->blocks();
        $html = '';
        foreach (self::normalizeRows($content) as $row) {
            $cols = $row['cols'];
            $html .= '<div class="flow-row cols-' . $cols . '">';
            foreach ($row['columns'] as $column) {
                $html .= '<div class="flow-col">';
                foreach ($column as $block) {
                    $type = $block['type'] ?? '';
                    if (is_string($type) && $registry->has($type)) {
                        $html .= $registry->render($type, (array) ($block['data'] ?? []));
                    }
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * Modèle de vue de l'éditeur : lignes → colonnes → cartes de blocs prêtes à
     * afficher (form() avec __KEY__ substitué par la clé du bloc). Un contenu vide
     * est amorcé avec une ligne d'une colonne vide.
     *
     * @param array<int, mixed>          $content
     * @return array<int, array{key:string,cols:int,columns:array<int,array<int,array{key:string,type:string,label:string,form:string}>>}>
     */
    public static function editorRows(array $content): array
    {
        $registry = Kernel::instance()->blocks();
        $labels = $registry->labels();

        $card = static function (array $block) use ($registry, $labels): ?array {
            $type = (string) ($block['type'] ?? '');
            if (!$registry->has($type)) {
                return null;
            }
            $key = (string) ($block['id'] ?? ('b' . bin2hex(random_bytes(3))));
            return [
                'key'   => $key,
                'type'  => $type,
                'label' => $labels[$type] ?? $type,
                'form'  => str_replace('__KEY__', $key, $registry->get($type)->form((array) ($block['data'] ?? []))),
            ];
        };

        $rows = [];
        foreach (self::normalizeRows($content) as $row) {
            $columns = [];
            foreach ($row['columns'] as $column) {
                $cards = [];
                foreach ($column as $block) {
                    $c = $card($block);
                    if ($c !== null) {
                        $cards[] = $c;
                    }
                }
                $columns[] = $cards;
            }
            $rows[] = [
                'key'     => 'r' . bin2hex(random_bytes(3)),
                'cols'    => $row['cols'],
                'columns' => $columns,
            ];
        }

        // Contenu vide : on amorce avec une ligne d'une colonne vide.
        if ($rows === []) {
            $rows[] = [
                'key'     => 'r' . bin2hex(random_bytes(3)),
                'cols'    => 1,
                'columns' => [[]],
            ];
        }

        return $rows;
    }

    /**
     * Gabarits vides par type de bloc pour l'éditeur (gardent __KEY__ pour le JS).
     * Le catalogue (type => libellé, ordre d'enregistrement) vient du registre :
     * seuls les blocs effectivement disponibles sont proposés.
     *
     * @return array<int, array{type:string,label:string,form:string}>
     */
    public static function templates(): array
    {
        $registry = Kernel::instance()->blocks();
        $templates = [];
        foreach ($registry->labels() as $type => $label) {
            $templates[] = [
                'type'  => $type,
                'label' => $label,
                'form'  => $registry->get($type)->form([]),
            ];
        }
        return $templates;
    }

    /**
     * Normalise un contenu stocké en lignes, en tolérant l'ancien format plat
     * (liste de blocs) qu'on enveloppe alors en lignes d'une seule colonne.
     *
     * @param array<int, mixed> $content
     * @return array<int, array{cols:int,columns:array<int,array<int,array<string,mixed>>>}>
     */
    public static function normalizeRows(array $content): array
    {
        $rows = [];
        foreach ($content as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['columns']) && is_array($item['columns'])) {
                $cols = max(1, min(4, (int) ($item['cols'] ?? count($item['columns']))));
                $columns = array_values(array_filter($item['columns'], 'is_array'));
                $rows[] = ['cols' => $cols, 'columns' => $columns];
            } elseif (isset($item['type'])) {
                // Ancien format plat : un bloc = une ligne d'une colonne.
                $rows[] = ['cols' => 1, 'columns' => [[$item]]];
            }
        }
        return $rows;
    }
}
