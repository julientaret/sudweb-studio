-- Module Devis — passage à un formulaire dynamique (étapes/questions éditables
-- depuis l'admin). Les réponses ne sont plus des colonnes figées mais un JSON
-- ordonné [{label, value}, …] dans `answers`. On conserve `name` et `email` en
-- colonnes dédiées (liste admin, Reply-To de la notification) ; les autres
-- colonnes spécifiques deviennent inutiles.
ALTER TABLE quote_requests
    ADD COLUMN answers TEXT NULL AFTER email,
    DROP COLUMN project_type,
    DROP COLUMN budget,
    DROP COLUMN deadline,
    DROP COLUMN description,
    DROP COLUMN phone,
    DROP COLUMN company;
