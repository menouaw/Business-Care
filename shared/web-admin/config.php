<?php
// configuration de la base de donnees
define('DB_HOST', 'localhost');
define('DB_NAME', 'business_care');
define('DB_USER', 'business_care_user');
define('DB_PASS', 'business_care_password');
define('DB_CHARSET', 'utf8mb4');

// parametres de l'application
define('APP_NAME', 'Business Care');
define('APP_VERSION', '0.5.0');

// url de l'application
define('WEBADMIN_URL', 'http://localhost/Business-Care/web-admin');
define('ROOT_URL', 'http://localhost/Business-Care');
define('SHARED_URL', ROOT_URL . '/shared');
define('ASSETS_URL', ROOT_URL . '/assets');
define('API_URL', ROOT_URL . '/api');
define('UPLOAD_URL', ROOT_URL . '/uploads/');

// constantes pour les roles utilisateurs
define('ROLE_ADMIN', 1);
define('ROLE_SALARIE', 2);
define('ROLE_PRESTATAIRE', 3);
define('ROLE_ENTREPRISE', 4);

// constantes pour les tables
define('TABLE_COMPANIES', 'entreprises');
define('TABLE_USERS', 'personnes');
define('TABLE_CONTRACTS', 'contrats');
define('TABLE_ROLES', 'roles');
define('TABLE_SERVICES', 'services');
define('TABLE_PRESTATIONS', 'prestations');
define('TABLE_QUOTE_PRESTATIONS', 'devis_prestations');
define('TABLE_APPOINTMENTS', 'rendez_vous');
define('TABLE_EVALUATIONS', 'evaluations');
define('TABLE_DONATIONS', 'dons');
define('TABLE_QUOTES', 'devis');
define('TABLE_INVOICES', 'factures');
define('TABLE_USER_PREFERENCES', 'preferences_utilisateurs');
define('TABLE_LOGS', 'logs');
define('TABLE_REMEMBER_ME', 'remember_me_tokens');

// constantes pour les devis (quotes)
define('QUOTE_STATUS_PENDING', 'en_attente');
define('QUOTE_STATUS_ACCEPTED', 'accepte');
define('QUOTE_STATUS_REFUSED', 'refuse');
define('QUOTE_STATUS_EXPIRED', 'expire');
define('QUOTE_STATUSES', [QUOTE_STATUS_PENDING, QUOTE_STATUS_ACCEPTED, QUOTE_STATUS_REFUSED, QUOTE_STATUS_EXPIRED]);

// constantes pour les packs/services principaux

define('PACK_STARTER_NAME', 'Starter Pack');
define('PACK_BASIC_NAME', 'Basic Pack');
define('PACK_PREMIUM_NAME', 'Premium Pack');
define('SERVICE_TYPES', [
    PACK_STARTER_NAME,
    PACK_BASIC_NAME,
    PACK_PREMIUM_NAME
]);

// taux de tva standard
define('TVA_RATE', 0.20); // 20%

// duree de vie de la session en secondes (30 minutes)
define('SESSION_LIFETIME', 1800);

// constantes pour les statuts utilisateurs
define('USER_STATUSES', ['actif', 'inactif', 'en_attente', 'suspendu']);

// constantes pour les tailles d'entreprise
define('COMPANY_SIZES', ['1-10', '11-50', '51-200', '201-500', '500+']);

// constantes pour les contrats
define('CONTRACT_STATUSES', ['actif', 'expire', 'resilie', 'en_attente']);
define('DEFAULT_CONTRACT_STATUS', 'en_attente');

// constantes pour les prestations (services)
define('PRESTATION_TYPES', ['conference', 'webinar', 'atelier', 'consultation', 'evenement', 'autre']);
define('PRESTATION_DIFFICULTIES', ['debutant', 'intermediaire', 'avance']);
define('DEFAULT_PRESTATION_STATUS', 'actif'); 

// constantes pour les rendez-vous (appointments)
define('APPOINTMENT_STATUSES', ['planifie', 'confirme', 'annule', 'termine', 'no_show']);
define('APPOINTMENT_TYPES', ['presentiel', 'visio', 'telephone']);

// constantes pour les dons
define('DONATION_STATUSES', ['en_attente', 'valide', 'refuse']);
define('DONATION_TYPES', ['financier', 'materiel']);

// constantes globales de statut
define('STATUS_ACTIVE', 'actif');

// constantes pour les factures des prestataires
define('TABLE_PRACTITIONER_INVOICES', 'factures_prestataires'); // Or use TABLE_INVOICES if structure allows

// Statuses for practitioner invoices
define('PRACTITIONER_INVOICE_STATUS_UNPAID', 'impayee');
define('PRACTITIONER_INVOICE_STATUS_PAID', 'payee');
define('PRACTITIONER_INVOICE_STATUS_PENDING_GENERATION', 'generation_attendue');
define('PRACTITIONER_INVOICE_STATUSES', [
    PRACTITIONER_INVOICE_STATUS_UNPAID, 
    PRACTITIONER_INVOICE_STATUS_PAID, 
    PRACTITIONER_INVOICE_STATUS_PENDING_GENERATION
]);
define('TABLE_PRACTITIONER_INVOICE_LINES', 'facture_prestataire_lignes');

// Prefix for practitioner invoices
define('PRACTITIONER_INVOICE_PREFIX', 'FP'); 

// limites diverses
define('DASHBOARD_RECENT_ACTIVITIES_LIMIT', 5);

// definit le fuseau horaire
date_default_timezone_set('Europe/Paris');