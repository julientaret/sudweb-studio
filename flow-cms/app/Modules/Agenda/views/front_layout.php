<?php
/** @var string $title */
/** @var ?\App\Modules\Agenda\Event $event */
/** @var string $body  HTML déjà rendu et échappé par les blocs */
/** @var ?string $thumbUrl  URL de la vignette (taille large) ou null */
/** @var string $siteName */

/** Formatte une date Y-m-d en français long (ex. « 12 juin 2026 »). */
$fmt = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '';
    }
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if ($d === false) {
        return $date;
    }
    $mois = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return (int) $d->format('j') . ' ' . ($mois[(int) $d->format('n')] ?? '') . ' ' . $d->format('Y');
};
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — <?= e($siteName) ?></title>
    <style>
        :root { --txt:#1c2230; --muted:#5b6472; --accent:#2b8bff; --line:#e6e8ec; }
        * { box-sizing:border-box; }
        body { margin:0; font:17px/1.65 Georgia, 'Times New Roman', serif; color:var(--txt); background:#fff; }
        .wrap { max-width:720px; margin:0 auto; padding:3rem 1.2rem 5rem; }
        h1,h2,h3 { font-family:system-ui, sans-serif; line-height:1.25; }
        h1 { font-size:2.2rem; margin:0 0 .6rem; }
        h2 { font-size:1.6rem; margin:2rem 0 .8rem; }
        h3 { font-size:1.25rem; margin:1.6rem 0 .6rem; }
        p { margin:0 0 1.1rem; }
        a { color:var(--accent); }
        ul, ol { margin:0 0 1.1rem 1.4rem; }
        .rte :last-child { margin-bottom:0; }
        figure.block-image { margin:1.8rem 0; }
        figure.block-image img { max-width:100%; height:auto; border-radius:8px; display:block; }
        .flow-row { display:grid; gap:1.6rem; margin:1.6rem 0; }
        .flow-row.cols-1 { grid-template-columns:1fr; }
        .flow-row.cols-2 { grid-template-columns:repeat(2,1fr); }
        .flow-row.cols-3 { grid-template-columns:repeat(3,1fr); }
        .flow-row.cols-4 { grid-template-columns:repeat(4,1fr); }
        .flow-col > :first-child { margin-top:0; }
        @media (max-width:640px) { .flow-row { grid-template-columns:1fr; } }
        /* En-tête d'événement (Agenda). */
        .event-dates { font-family:system-ui, sans-serif; font-size:.95rem; color:var(--accent);
            font-weight:600; margin:0 0 1.5rem; }
        .event-hero { margin:0 0 2rem; }
        .event-hero img { width:100%; height:auto; border-radius:12px; display:block; }
    </style>
</head>
<body>
    <main class="wrap">
        <?php if ($event !== null): ?>
            <h1><?= e($event->title) ?></h1>
            <p class="event-dates">
                <?php if ($event->endDate !== null && $event->endDate !== $event->startDate): ?>
                    Du <?= e($fmt($event->startDate)) ?> au <?= e($fmt($event->endDate)) ?>
                <?php else: ?>
                    Le <?= e($fmt($event->startDate)) ?>
                <?php endif; ?>
            </p>
            <?php if ($thumbUrl !== null): ?>
                <div class="event-hero"><img src="<?= e($thumbUrl) ?>" alt=""></div>
            <?php endif; ?>
        <?php endif; ?>
        <?= $body ?>
    </main>
</body>
</html>
