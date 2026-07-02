-- Mode démonstration (App\Core\DemoSettings) : marque les événements factices
-- créés à l'activation, pour un nettoyage ciblé (DELETE WHERE is_demo = 1) qui
-- ne touche jamais les vraies données à la désactivation.
ALTER TABLE events
    ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0,
    ADD KEY idx_is_demo (is_demo);
