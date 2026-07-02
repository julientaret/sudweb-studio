<?php

declare(strict_types=1);

namespace App\Core\Blocks;

use App\Core\Block;

/**
 * Bloc vidéo / embed (statique). data : { url, provider, video_id }.
 *
 * Bloc de contenu de base (niveau Core) : toujours disponible pour tout module
 * qui utilise le page-builder, sans dépendance inter-modules.
 *
 * L'URL collée (YouTube ou Vimeo) est analysée à la sauvegarde pour n'en garder
 * que l'identifiant : le `src` de l'iframe est reconstruit à partir de gabarits
 * fixes, jamais de l'URL brute — aucune valeur utilisateur n'atteint l'attribut
 * `src`. YouTube est intégré via `youtube-nocookie.com` (mode vie privée
 * renforcée, cohérent avec le RGPD-first du projet).
 *
 * CSP : nécessite `frame-src` autorisant ces deux domaines (Kernel::sendSecurityHeaders()) —
 * sinon l'iframe est bloquée silencieusement par le navigateur.
 */
final class VideoBlock implements Block
{
    public function type(): string
    {
        return 'video';
    }

    public function label(): string
    {
        return 'Vidéo (YouTube / Vimeo)';
    }

    public function render(array $data): string
    {
        $provider = (string) ($data['provider'] ?? '');
        $id = (string) ($data['video_id'] ?? '');
        if ($provider === '' || $id === '') {
            return '';
        }

        $src = $provider === 'vimeo'
            ? 'https://player.vimeo.com/video/' . rawurlencode($id)
            : 'https://www.youtube-nocookie.com/embed/' . rawurlencode($id);

        return '<div class="block-video"><iframe src="' . e($src) . '" title="Vidéo" loading="lazy" '
            . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" '
            . 'allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>';
    }

    public function form(array $data): string
    {
        $url = (string) ($data['url'] ?? '');

        return '<label>URL de la vidéo (YouTube ou Vimeo)</label>'
            . '<input type="text" name="blocks[__KEY__][data][url]" value="' . e($url) . '" placeholder="https://www.youtube.com/watch?v=…">'
            . '<p class="hint">Collez le lien tel quel depuis YouTube ou Vimeo.</p>';
    }

    public function sanitize(array $input): array
    {
        $url = trim((string) ($input['url'] ?? ''));
        $parsed = $this->parse($url);

        return [
            'url'      => $url,
            'provider' => $parsed['provider'] ?? '',
            'video_id' => $parsed['id'] ?? '',
        ];
    }

    /**
     * @return array{provider:string,id:string}|null
     */
    private function parse(string $url): ?array
    {
        if ($url === '') {
            return null;
        }
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i', $url, $m)) {
            return ['provider' => 'youtube', 'id' => $m[1]];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
            return ['provider' => 'vimeo', 'id' => $m[1]];
        }
        return null;
    }
}
