-- Module Users — anti-brute-force (brief §6).
-- Une ligne par tentative échouée (compte + IP). Le lockout est calculé sur une
-- fenêtre glissante ; les lignes sont purgées au succès et peuvent l'être par cron.
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    identifier VARCHAR(190) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_identifier (identifier),
    KEY idx_ip (ip),
    KEY idx_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
