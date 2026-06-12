-- Préférences du tableau de bord par compte : ordre et cartes masquées de
-- l'accès rapide (JSON). NULL = aucune personnalisation (ordre par défaut,
-- toutes les cartes visibles).
ALTER TABLE users ADD COLUMN dashboard_prefs TEXT NULL;
