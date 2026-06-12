-- Module Media — référentiel des images (brief §5).
-- Source de vérité : le reste de Flow référence l'`id`, jamais une URL.
-- `filename` est le chemin RELATIF à public/uploads/ de l'original re-encodé
-- (ex. "2026/06/photo-ab12cd34.jpg"). Les déclinaisons sont dérivées du filename,
-- sans ligne DB par variante.
CREATE TABLE IF NOT EXISTS media (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime VARCHAR(100) NOT NULL,
    width INT UNSIGNED NOT NULL DEFAULT 0,
    height INT UNSIGNED NOT NULL DEFAULT 0,
    size INT UNSIGNED NOT NULL DEFAULT 0,
    alt VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_filename (filename),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
