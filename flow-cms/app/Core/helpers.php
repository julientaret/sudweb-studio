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

if (!function_exists('module_enabled')) {
    /**
     * Indique si un module est actif pour cette requête (liste effective publiée
     * au boot dans `modules.enabled`). Permet aux modules de contenu de dégrader
     * proprement une fonctionnalité optionnelle — p. ex. masquer le choix d'image
     * si Media est désactivé — sans en dépendre.
     */
    function module_enabled(string $name): bool
    {
        $enabled = (array) \App\Core\Config::get('modules.enabled', []);
        return in_array($name, $enabled, true);
    }
}

if (!function_exists('admin_icon')) {
    /**
     * Icône SVG (style trait) d'une section de l'admin, choisie d'après son URL.
     *
     * Source unique partagée par la barre latérale (layout.php) et les cartes du
     * tableau de bord (dashboard.php) : les deux ne peuvent donc plus diverger,
     * et chaque section a une icône distincte. L'ordre des tests compte —
     * `pages-legales` est vérifié avant `pages` (sous-chaîne commune).
     */
    function admin_icon(string $url): string
    {
        $svg = static fn (string $path): string =>
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
            . 'stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';

        return match (true) {
            // Pages légales — balance (justice). Avant /pages : sous-chaîne commune.
            str_contains($url, 'pages-legales') => $svg('<path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>'),
            // Pages — document avec lignes de texte.
            str_contains($url, 'pages') => $svg('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'),
            // Agenda — calendrier.
            str_contains($url, 'agenda') => $svg('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'),
            // Actualités — journal.
            str_contains($url, 'actualites') => $svg('<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/>'),
            // Avis Google — étoile.
            str_contains($url, 'google-reviews') => $svg('<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'),
            // Contact — enveloppe (demandes reçues).
            str_contains($url, 'contact') => $svg('<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>'),
            // Médias — image.
            str_contains($url, 'media') => $svg('<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'),
            // Documents — fichier avec flèche de téléchargement.
            str_contains($url, 'documents') => $svg('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/>'),
            // Utilisateurs — deux personnes.
            str_contains($url, 'users') => $svg('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'),
            // Statistiques — barres.
            str_contains($url, 'stats') => $svg('<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'),
            // Administration — curseurs/réglages.
            str_contains($url, 'administration') => $svg('<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>'),
            // Maintenance — clé à molette.
            str_contains($url, 'maintenance') => $svg('<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>'),
            // Tutoriel — point d'interrogation.
            str_contains($url, 'tutoriel') => $svg('<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>'),
            // Accueil / tableau de bord — disposition en grille (icône par défaut).
            default => $svg('<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>'),
        };
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
