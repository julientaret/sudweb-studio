-- Avis Google importés via l'API Places (module GoogleReviews).
-- fingerprint = sha1(auteur + '#' + review_time) : dédup entre synchros.
-- is_visible est la curation de l'admin : jamais réécrit par une synchro.
CREATE TABLE google_reviews (
    id                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    fingerprint       CHAR(40)         NOT NULL,
    author_name       VARCHAR(255)     NOT NULL,
    author_url        VARCHAR(512)     NULL,
    profile_photo_url VARCHAR(512)     NULL,
    rating            TINYINT UNSIGNED NOT NULL DEFAULT 0,
    text              TEXT             NULL,
    language          VARCHAR(10)      NULL,
    relative_time     VARCHAR(100)     NULL,
    review_time       INT UNSIGNED     NOT NULL DEFAULT 0,
    is_visible        TINYINT(1)       NOT NULL DEFAULT 1,
    synced_at         DATETIME         NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_fingerprint (fingerprint),
    KEY idx_visible_time (is_visible, review_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
