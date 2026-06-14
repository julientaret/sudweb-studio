-- Module Users — adresses IP de connexion connues par compte (sécurité §6).
--
-- Sert à détecter une connexion depuis une IP inhabituelle : à chaque connexion
-- réussie, l'IP est enregistrée ; si le compte avait déjà au moins une IP connue
-- et que la nouvelle n'en fait pas partie, un bandeau d'alerte s'affiche dans
-- l'admin et un e-mail est envoyé au support.
--
-- L'IP est conservée EN CLAIR (et non hachée comme côté visiteurs anonymes du
-- module Contact) : il s'agit ici de la donnée d'un compte d'administration
-- (intérêt légitime de sécurité), et l'alerte n'a de sens que si elle indique
-- l'adresse concernée.
CREATE TABLE IF NOT EXISTS user_login_ips (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    ip VARCHAR(45) NOT NULL,
    first_seen DATETIME NOT NULL,
    last_seen DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_user_ip (user_id, ip),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
