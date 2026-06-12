-- Module Agenda — événements datés (brief §3, base commune « contenu »).
-- `content` = page-builder par blocs (même format JSON que pages, voir
-- App\Core\BlockContent). `thumbnail_id` = image vignette référencée par ID média
-- (jamais par URL, brief §4/§5). `slug` unique = URL front (/agenda/slug).
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    thumbnail_id INT UNSIGNED NULL,
    content LONGTEXT NOT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_slug (slug),
    KEY idx_status (status),
    KEY idx_start (start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
