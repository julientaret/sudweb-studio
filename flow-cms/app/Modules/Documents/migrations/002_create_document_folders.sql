-- Module Documents — dossiers logiques (organisation de la bibliothèque).
-- Le dossier est un simple groupement : il n'a aucun lien avec le stockage disque
-- (toujours année/mois). Un document sans dossier (folder_id NULL) est « Non classé ».
CREATE TABLE IF NOT EXISTS document_folders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
