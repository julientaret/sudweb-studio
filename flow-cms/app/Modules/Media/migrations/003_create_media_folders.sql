-- Module Media — dossiers logiques (organisation de la médiathèque).
-- Le dossier est un simple groupement : il n'a aucun lien avec le stockage disque
-- (toujours année/mois). Un média sans dossier (folder_id NULL) est « Non classé ».
CREATE TABLE IF NOT EXISTS media_folders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE media
    ADD COLUMN folder_id INT UNSIGNED NULL DEFAULT NULL AFTER alt,
    ADD KEY idx_folder (folder_id);
