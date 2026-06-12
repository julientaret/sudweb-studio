-- Module Documents — référentiel des documents réutilisables.
-- Pendant « non-image » de la table media : on stocke le fichier tel quel (pas de
-- déclinaisons). `filename` est le chemin RELATIF à public/documents/ du fichier
-- assaini (ex. "2026/06/rapport-ab12cd34.pdf"). `title` est un libellé éditorial
-- facultatif ; `ext` l'extension assainie ; `folder_id` NULL = « Non classé ».
CREATE TABLE IF NOT EXISTS documents (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL DEFAULT '',
    mime VARCHAR(150) NOT NULL,
    ext VARCHAR(12) NOT NULL DEFAULT '',
    size INT UNSIGNED NOT NULL DEFAULT 0,
    folder_id INT UNSIGNED NULL DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_filename (filename),
    KEY idx_folder (folder_id),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
