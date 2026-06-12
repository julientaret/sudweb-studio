<?php

declare(strict_types=1);

namespace App\Modules\GoogleReviews\Blocks;

use App\Core\Block;
use App\Modules\GoogleReviews\GoogleReviewsRepository;

/**
 * Bloc front « Avis Google » (dynamique, brief §4) : son render() requête la
 * base en live et rend les avis marqués visibles, du plus récent au plus ancien.
 *
 * data : { title: string, limit: int (1..20, défaut 6) }.
 * Markup volontairement neutre (classes gr-*) : le style vit dans le thème.
 */
final class ReviewsBlock implements Block
{
    private const DEFAULT_LIMIT = 6;
    private const MAX_LIMIT = 20;

    public function type(): string
    {
        return 'google_reviews';
    }

    public function render(array $data): string
    {
        $limit   = $this->normalizeLimit($data['limit'] ?? self::DEFAULT_LIMIT);
        $reviews = (new GoogleReviewsRepository())->visible($limit);

        if ($reviews === []) {
            return '';
        }

        $title = e((string) ($data['title'] ?? 'Ils nous ont laissé un avis'));

        $items = '';
        foreach ($reviews as $r) {
            $items .= $this->renderItem($r);
        }

        return '<section class="gr-reviews">'
            . '<h2 class="gr-reviews__title">' . $title . '</h2>'
            . '<ul class="gr-reviews__list">' . $items . '</ul>'
            . '</section>';
    }

    public function form(array $data): string
    {
        $title = (string) ($data['title'] ?? '');
        $limit = $this->normalizeLimit($data['limit'] ?? self::DEFAULT_LIMIT);

        return '<label>Titre de la section</label>'
            . '<input type="text" name="blocks[__KEY__][data][title]" value="' . e($title) . '"'
            . ' placeholder="Ils nous ont laissé un avis">'
            . '<label>Nombre d\'avis affichés</label>'
            . '<input type="number" name="blocks[__KEY__][data][limit]" value="' . $limit . '"'
            . ' min="1" max="' . self::MAX_LIMIT . '">';
    }

    public function sanitize(array $input): array
    {
        return [
            'title' => trim((string) ($input['title'] ?? '')),
            'limit' => $this->normalizeLimit($input['limit'] ?? self::DEFAULT_LIMIT),
        ];
    }

    /** @param array<string, mixed> $r */
    private function renderItem(array $r): string
    {
        $author = e((string) $r['author_name']);
        $rating = max(0, min(5, (int) $r['rating']));
        $stars  = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
        $date   = e((string) ($r['relative_time'] ?? ''));

        $text = trim((string) ($r['text'] ?? ''));
        $body = $text !== ''
            ? '<p class="gr-review__text">' . e($text) . '</p>'
            : '';

        return '<li class="gr-review">'
            . '<div class="gr-review__head">'
            . '<span class="gr-review__author">' . $author . '</span>'
            . '<span class="gr-review__stars" aria-label="' . $rating . ' sur 5">' . $stars . '</span>'
            . '</div>'
            . $body
            . '<time class="gr-review__date">' . $date . '</time>'
            . '</li>';
    }

    private function normalizeLimit(mixed $limit): int
    {
        $limit = (int) $limit;
        return $limit >= 1 && $limit <= self::MAX_LIMIT ? $limit : self::DEFAULT_LIMIT;
    }
}
