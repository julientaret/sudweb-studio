-- Module Réalisations — collection d'éléments « réalisation » affichés sur le
-- front via le bloc dynamique `realisations` (brief §3/§4, base commune
-- « contenu »). `description` = Delta Quill (JSON), rendu par App\Core\DeltaRenderer
-- (jamais de HTML brut stocké). `media_id` = photo référencée par ID média (jamais
-- par URL, brief §4/§5). `position` = ordre d'affichage (ASC). Pas de slug : le
-- module n'expose aucune URL front, l'affichage passe exclusivement par le bloc.
CREATE TABLE IF NOT EXISTS realisations (
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
