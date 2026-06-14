<?php

declare(strict_types=1);

use App\Core\Config;

/**
 * Configuration du module Devis (pop-up de demande de devis + demandes en base).
 *
 * Les adresses viennent de .env.local (partagées avec les autres usages mail) ;
 * le reste règle les options du formulaire, l'anti-spam maison et la
 * conservation RGPD.
 */
return [
    // Destinataire de la notification : l'e-mail de contact du site.
    // Par défaut EMAIL_CONTACT du .env.local.
    'recipient' => (string) Config::env('EMAIL_CONTACT', ''),

    // Expéditeur technique (From) : un domaine maîtrisé pour passer SPF/DKIM.
    // L'e-mail du demandeur, lui, est mis en Reply-To (jamais en From).
    'sender' => (string) Config::env('EMAIL_SENDER', (string) Config::env('EMAIL_CONTACT', '')),

    // --- RGPD : limitation de la conservation -------------------------------
    // Les demandes plus anciennes que ce délai sont purgées (bin/flow devis:purge,
    // déclenché aussi de façon opportuniste). 0 = pas de purge automatique.
    'retention_months' => 12,

    // --- Anti-spam (maison, sans tiers ni cookie de pistage) ----------------
    // Délai minimum réaliste entre l'ouverture du pop-up et l'envoi (s).
    'min_seconds' => 3,
    // Limitation par IP : au plus N envois dans la fenêtre (s).
    'rate_limit_max'    => 5,
    'rate_limit_window' => 3600, // 1 heure

    // --- Étapes du pop-up (graine par défaut) -------------------------------
    // Le formulaire (étapes + questions) est désormais ÉDITABLE depuis l'admin
    // (Demandes de devis → « Configurer le formulaire » ; stocké dans
    // storage/devis_form.json). Les valeurs ci-dessous ne servent que de schéma
    // initial, tant qu'aucune personnalisation n'a été enregistrée.
    // Étape 1 — type de prestation (liste déroulante). Vide → champ libre.
    'project_types' => [
        'Site vitrine',
        'Site e-commerce',
        'Application web',
        'Refonte de site',
        'Référencement (SEO)',
        'Maintenance',
        'Autre',
    ],
    // Étape 2 — budget et délai estimés.
    'budgets' => [
        'Moins de 1 000 €',
        '1 000 – 3 000 €',
        '3 000 – 6 000 €',
        '6 000 – 10 000 €',
        'Plus de 10 000 €',
        'À définir ensemble',
    ],
    'deadlines' => [
        'Dès que possible',
        'Sous 1 mois',
        '1 à 3 mois',
        '3 à 6 mois',
        'Pas de date précise',
    ],
    // Longueur minimale de la description du besoin (étape 2).
    'description_min_length' => 10,

    // --- RGPD : consentement & information ----------------------------------
    // Texte de la case à cocher (obligatoire). Versionné pour la traçabilité.
    'consent_text'    => "J'accepte que les informations saisies soient utilisées pour traiter ma demande de devis. Elles ne seront jamais cédées à des tiers.",
    'consent_version' => '2026-06-14',
    // Lien vers la politique de confidentialité (affiché dans le pop-up).
    'privacy_url'     => '/politique-de-confidentialite',
];
