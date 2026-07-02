-- Mode démonstration (App\Core\DemoSettings) : marque les demandes de contact
-- factices créées à l'activation, pour un nettoyage ciblé
-- (DELETE WHERE is_demo = 1) qui ne touche jamais les vraies demandes reçues à
-- la désactivation.
ALTER TABLE contact_submissions
    ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0,
    ADD KEY idx_is_demo (is_demo);
