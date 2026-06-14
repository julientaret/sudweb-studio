-- Module Devis — demandes de devis envoyées depuis le pop-up du site public.
--
-- RGPD : on collecte le nécessaire au chiffrage et à la reprise de contact
-- (type de projet, budget, délai, description + coordonnées) ainsi que la preuve
-- de consentement (case + version du texte). `ip_hash` est l'IP HACHÉE (jamais en
-- clair), conservée au seul titre de l'anti-abus (rate-limit). La conservation
-- est limitée dans le temps (purge, voir config/devis.php).
CREATE TABLE IF NOT EXISTS quote_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_type VARCHAR(150) NOT NULL DEFAULT '',
    budget VARCHAR(60) NOT NULL DEFAULT '',
    deadline VARCHAR(60) NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(40) NOT NULL DEFAULT '',
    company VARCHAR(150) NOT NULL DEFAULT '',
    consent TINYINT(1) NOT NULL DEFAULT 0,
    consent_version VARCHAR(32) NOT NULL DEFAULT '',
    ip_hash CHAR(64) NULL,
    status ENUM('new','read','quoted','archived') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_status (status),
    KEY idx_created (created_at),
    KEY idx_ip (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
