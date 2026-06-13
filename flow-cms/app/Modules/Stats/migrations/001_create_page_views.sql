-- Module Stats — mesure d'audience légère du site public.
--
-- RGPD (exemption « mesure d'audience » CNIL) : aucune donnée directement
-- identifiante n'est stockée. Pas d'IP en clair, pas de User-Agent brut, aucun
-- cookie de pistage.
--
--  - `visitor_hash` : empreinte NON RÉVERSIBLE = sha256(IP + UA + sel quotidien
--    + secret serveur). Le sel changeant CHAQUE JOUR, l'empreinte d'un même
--    visiteur diffère d'un jour à l'autre : impossible de le suivre dans le
--    temps ni de reconstituer un profil. Elle ne sert qu'à dédoublonner les
--    visites d'UNE journée (visiteurs uniques/jour).
--  - `referer_host` : uniquement l'hôte du référent (jamais l'URL complète ni
--    son query-string), pour connaître les sources de trafic.
--
-- Conservation limitée : purge au-delà de stats.retention_months
-- (config/stats.php, 13 mois par défaut — recommandation CNIL).
CREATE TABLE IF NOT EXISTS page_views (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    path VARCHAR(255) NOT NULL,
    visitor_hash CHAR(64) NOT NULL,
    referer_host VARCHAR(255) NULL,
    device ENUM('desktop','mobile','tablet','other') NOT NULL DEFAULT 'other',
    viewed_at DATETIME NOT NULL,
    viewed_on DATE NOT NULL,
    PRIMARY KEY (id),
    KEY idx_viewed_on (viewed_on),
    KEY idx_path (path, viewed_on),
    KEY idx_visitor_day (visitor_hash, viewed_on),
    KEY idx_referer (referer_host, viewed_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
