-- Module Contact — demandes envoyées depuis le formulaire public.
--
-- RGPD : on collecte le strict nécessaire au traitement de la demande
-- (nom, e-mail, sujet, message) + la preuve de consentement (case + version du
-- texte + horodatage). `ip_hash` est l'IP HACHÉE (jamais en clair), conservée au
-- seul titre de l'anti-abus (rate-limit) ; elle n'identifie pas directement le
-- visiteur. La conservation est limitée dans le temps (purge, voir config/contact.php).
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(200) NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    consent TINYINT(1) NOT NULL DEFAULT 0,
    consent_version VARCHAR(32) NOT NULL DEFAULT '',
    ip_hash CHAR(64) NULL,
    status ENUM('new','read','archived') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_status (status),
    KEY idx_created (created_at),
    KEY idx_ip (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
