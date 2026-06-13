-- Module Pages légales — pages juridiques éditées au texte riche (brief §3).
-- `content` = Delta Quill (JSON {"ops":[...]}, jamais du HTML), rendu borné par
-- App\Modules\Pages\DeltaRenderer. `slug` unique = URL front (/<slug>).
CREATE TABLE IF NOT EXISTS legal_pages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_slug (slug),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pages par défaut « pour commencer », prêtes à éditer dans l'admin.
-- INSERT IGNORE = idempotent (slug unique) → rejouable sans doublon.
-- « Mentions légales » : créée vide (à compléter selon le client).
-- « Politique de confidentialité » : pré-remplie avec le texte RGPD repris de
-- l'ancienne page statique (frontend/static/politique-de-confidentialite.php),
-- converti en Delta Quill — le module en devient la source unique, éditable.
-- (Le contenu n'utilise que des apostrophes typographiques : aucune apostrophe
--  droite, donc aucun échappement SQL nécessaire dans la chaîne JSON.)
INSERT IGNORE INTO legal_pages (slug, title, content, status, created_at, updated_at) VALUES
    ('mentions-legales', 'Mentions légales', '{"ops":[{"insert":"\\n"}]}', 'published', NOW(), NOW()),
    ('politique-de-confidentialite', 'Politique de confidentialité', '{"ops":[{"insert":"La présente politique explique comment ce site traite les données personnelles transmises via le formulaire de contact."},{"insert":"\\n"},{"insert":"Données collectées"},{"insert":"\\n","attributes":{"header":2}},{"insert":"Lorsque vous utilisez le formulaire de contact, nous collectons :"},{"insert":"\\n"},{"insert":"votre "},{"insert":"nom","attributes":{"bold":true}},{"insert":" ;"},{"insert":"\\n","attributes":{"list":"bullet"}},{"insert":"votre "},{"insert":"adresse e-mail","attributes":{"bold":true}},{"insert":" ;"},{"insert":"\\n","attributes":{"list":"bullet"}},{"insert":"le "},{"insert":"sujet","attributes":{"bold":true}},{"insert":" et le "},{"insert":"message","attributes":{"bold":true}},{"insert":" de votre demande."},{"insert":"\\n","attributes":{"list":"bullet"}},{"insert":"Pour prévenir les abus (envois automatisés), une "},{"insert":"empreinte technique de votre adresse IP","attributes":{"bold":true}},{"insert":" (valeur hachée, non réversible) est conservée temporairement. Votre adresse IP n’est jamais stockée en clair."},{"insert":"\\n"},{"insert":"Finalité et base légale"},{"insert":"\\n","attributes":{"header":2}},{"insert":"Ces données sont utilisées "},{"insert":"uniquement pour traiter votre demande","attributes":{"bold":true}},{"insert":" et y répondre. Le traitement repose sur votre "},{"insert":"consentement","attributes":{"bold":true}},{"insert":", recueilli au moment de l’envoi du formulaire."},{"insert":"\\n"},{"insert":"Destinataires"},{"insert":"\\n","attributes":{"header":2}},{"insert":"Vos données sont adressées au responsable du site et ne sont "},{"insert":"jamais cédées ni revendues à des tiers","attributes":{"bold":true}},{"insert":"."},{"insert":"\\n"},{"insert":"Durée de conservation"},{"insert":"\\n","attributes":{"header":2}},{"insert":"Les demandes sont conservées au maximum "},{"insert":"12 mois","attributes":{"bold":true}},{"insert":", puis supprimées automatiquement."},{"insert":"\\n"},{"insert":"Vos droits"},{"insert":"\\n","attributes":{"header":2}},{"insert":"Conformément au RGPD, vous disposez d’un droit d’accès, de rectification, d’effacement, de limitation et d’opposition concernant vos données. Pour exercer ces droits, contactez-nous via le "},{"insert":"formulaire de contact","attributes":{"link":"/contact"}},{"insert":". Vous pouvez également introduire une réclamation auprès de la CNIL."},{"insert":"\\n"}]}', 'published', NOW(), NOW());
