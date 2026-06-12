<?php

declare(strict_types=1);

/**
 * Fonctions globales de Flow.
 *
 * Chargées par l'autoload Composer ("files"). On les garde minimales : tout ce
 * qui peut vivre dans une classe du Core y reste.
 */

if (!function_exists('e')) {
    /**
     * Échappement HTML systématique en sortie (brief §4/§6).
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('partial')) {
    /**
     * Rend un fragment de vue partagé depuis app/Core/views (ex. la modale de
     * sélection d'image, réutilisée par les éditeurs Pages/Agenda/Actualités).
     * Sortie échappée par le partial lui-même, comme toute vue.
     *
     * @param array<string, mixed> $data
     */
    function partial(string $name, array $data = []): string
    {
        $views = \App\Core\Kernel::instance()->basePath() . '/app/Core/views';
        return (new \App\Core\View([$views]))->render($name, $data);
    }
}

if (!function_exists('slug')) {
    /**
     * Translittère une chaîne en slug minuscule sûr pour un nom de fichier/URL.
     */
    function slug(string $value): string
    {
        // Translittération fiable : intl si disponible, sinon table d'accents, et
        // iconv en dernier recours (comportement variable selon l'OS).
        if (class_exists(\Transliterator::class)) {
            $tr = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
            if ($tr !== null) {
                $value = (string) $tr->transliterate($value);
            }
        } else {
            $map = [
                'à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï',
                'ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ','œ','æ',
            ];
            $rep = [
                'a','a','a','a','a','a','c','e','e','e','e','i','i','i','i',
                'n','o','o','o','o','o','u','u','u','u','y','y','oe','ae',
            ];
            $value = str_replace($map, $rep, mb_strtolower($value, 'UTF-8'));
            $value = (string) (@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value);
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');
        return $value !== '' ? $value : 'element';
    }
}
