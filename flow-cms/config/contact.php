<?php

declare(strict_types=1);

use App\Core\Config;

/**
 * Configuration du module Contact (formulaire public + demandes en base).
 *
 * Les adresses viennent de .env.local (partagées avec les autres usages mail) ;
 * le reste règle l'anti-spam maison et la conservation RGPD.
 */
return [
    // Destinataire de la notification : l'e-mail du propriétaire du site.
    // Par défaut EMAIL_CONTACT du .env.local.
    'recipient' => (string) Config::env('EMAIL_CONTACT', ''),

    // Expéditeur technique (From) : un domaine maîtrisé pour passer SPF/DKIM.
    // L'e-mail du visiteur, lui, est mis en Reply-To (jamais en From).
    'sender' => (string) Config::env('EMAIL_SENDER', (string) Config::env('EMAIL_CONTACT', '')),

    // --- RGPD : limitation de la conservation -------------------------------
    // Les demandes plus anciennes que ce délai sont purgées (bin/flow contact:purge,
    // déclenché aussi de façon opportuniste). 0 = pas de purge automatique.
    'retention_months' => 12,

    // --- Anti-spam (maison, sans tiers ni cookie de pistage) ----------------
    // Délai minimum réaliste entre l'affichage et l'envoi du formulaire (s).
    'min_seconds' => 3,
    // Limitation par IP : au plus N envois dans la fenêtre (s).
    'rate_limit_max'    => 5,
    'rate_limit_window' => 3600, // 1 heure
    // Plafond global (toutes IP) dans la même fenêtre, contre les IP tournantes.
    // 0 = désactivé.
    'rate_limit_global_max' => 30,

    // --- Champs du formulaire ----------------------------------------------
    // Sujets proposés (menu déroulant). Tableau vide → champ « sujet » libre.
    'subjects' => [
        'Question générale',
        'Demande de devis',
        'Support',
        'Autre',
    ],
    'message_min_length' => 10,

    // --- RGPD : consentement & information ----------------------------------
    // Texte de la case à cocher (obligatoire). Versionné pour la traçabilité.
    'consent_text'    => "J'accepte que les informations saisies soient utilisées pour traiter ma demande. Elles ne seront jamais cédées à des tiers.",
    'consent_version' => '2026-06-12',
    // Lien vers la politique de confidentialité (affiché sous le formulaire).
    'privacy_url'     => '/politique-de-confidentialite',
];
