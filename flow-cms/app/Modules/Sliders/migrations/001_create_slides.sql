-- Module Sliders — diapositives affichées sur le front via le bloc dynamique
-- `slider` (brief §3/§4, base commune « contenu »). `description` = Delta Quill
-- (JSON), rendu par App\Core\DeltaRenderer (jamais de HTML brut stocké).
-- `media_id` = photo référencée par ID média (jamais par URL, brief §4/§5).
-- `position` = ordre des diapositives (ASC). Pas de slug : aucune URL front, le
-- rendu passe exclusivement par le bloc.
CREATE TABLE IF NOT EXISTS slides (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    media_id INT UNSIGNED NULL,
    position SMALLINT NOT NULL DEFAULT 0,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_status (status),
    KEY idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
