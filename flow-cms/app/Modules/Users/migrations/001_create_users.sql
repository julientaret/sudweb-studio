-- Module Users — comptes (brief §7).
-- Un champ `role` + le mapping config/roles.php suffisent en v1 (pas de tables
-- roles/permissions). Le code teste toujours une permission, jamais un rôle.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(190) NOT NULL DEFAULT '',
    role VARCHAR(50) NOT NULL DEFAULT 'editor',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
