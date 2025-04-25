<?php
// Configuration de la base de données
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_NAME', getenv('DB_DATABASE') ?: 'business_care');
define('DB_USER', getenv('DB_USERNAME') ?: 'business_care_user');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Paramètres de l'application
define('APP_NAME', 'Business Care');
define('APP_VERSION', '0.7.0');

// URLs de l'application
define('ROOT_URL', 'http://localhost');
define('WEBADMIN_URL', ROOT_URL . '/admin');

define('SHARED_URL', ROOT_URL . '/shared');
define('ASSETS_URL', ROOT_URL . '/assets');
define('API_URL', ROOT_URL . '/api');
define('UPLOAD_URL', ROOT_URL . '/uploads/');
define('JAVA_URL', ROOT_URL . '/java-app');

// Constantes pour les rôles utilisateurs
define('ROLE_ADMIN', 1);
define('ROLE_SALARIE', 2);
define('ROLE_PRESTATAIRE', 3);
define('ROLE_ENTREPRISE', 4);

// Constantes pour les tables
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
define('TABLE_API_TOKENS', 'api_tokens');
define('TABLE_HABILITATIONS', 'habilitations');
define('TABLE_PROVIDER_SERVICES', 'prestataires_prestations');
define('TABLE_PROVIDER_AVAILABILITY', 'prestataires_disponibilites');
define('TABLE_EVENEMENTS', 'evenements');
define('TABLE_EVENEMENT_INSCRIPTIONS', 'evenement_inscriptions');

// Constantes pour les devis (quotes)
define('QUOTE_STATUS_PENDING', 'en_attente');
define('QUOTE_STATUS_ACCEPTED', 'accepté');
define('QUOTE_STATUS_REFUSED', 'refusé');
define('QUOTE_STATUS_EXPIRED', 'expiré');
define('QUOTE_STATUSES', [QUOTE_STATUS_PENDING, QUOTE_STATUS_ACCEPTED, QUOTE_STATUS_REFUSED, QUOTE_STATUS_EXPIRED]);

// Constantes pour les packs/services principaux
define('PACK_STARTER_NAME', 'Starter Pack');
define('PACK_BASIC_NAME', 'Basic Pack');
define('PACK_PREMIUM_NAME', 'Premium Pack');
define('SERVICE_TYPES', [
    PACK_STARTER_NAME,
    PACK_BASIC_NAME,
    PACK_PREMIUM_NAME
]);

// Taux de TVA standard
define('TVA_RATE', 0.20); // 20%

// Durée de vie de la session en secondes (30 minutes)
define('SESSION_LIFETIME', 1800);

// Constantes pour les statuts utilisateurs
define('USER_STATUSES', ['actif', 'inactif', 'en_attente', 'suspendu']);

// Constantes pour les tailles d'entreprise
define('COMPANY_SIZES', ['1-10', '11-50', '51-200', '201-500', '500+']);

// Constantes pour les contrats
define('CONTRACT_STATUSES', ['actif', 'expiré', 'résilié', 'en_attente']);
define('DEFAULT_CONTRACT_STATUS', 'en_attente');

// Constantes pour les prestations (services)
define('PRESTATION_TYPES', ['conférence', 'webinar', 'atelier', 'consultation', 'événement', 'autre']);
define('PRESTATION_DIFFICULTIES', ['débutant', 'intermédiaire', 'avancé']);
define('DEFAULT_PRESTATION_STATUS', 'actif'); 

// Constantes pour les rendez-vous (appointments)
define('APPOINTMENT_STATUSES', ['planifié', 'confirmé', 'annulé', 'terminé', 'no_show']);
define('APPOINTMENT_TYPES', ['présentiel', 'visio', 'téléphone']);

// Constantes pour les dons
define('DONATION_STATUSES', ['en_attente', 'validé', 'refusé']);
define('DONATION_TYPES', ['financier', 'matériel']);

// Constantes globales de statut
define('STATUS_ACTIVE', 'actif');

// Constantes pour les factures des prestataires
define('TABLE_PRACTITIONER_INVOICES', 'factures_prestataires');

// Statuts pour les factures des prestataires
define('PRACTITIONER_INVOICE_STATUS_UNPAID', 'impayée');
define('PRACTITIONER_INVOICE_STATUS_PAID', 'payée');
define('PRACTITIONER_INVOICE_STATUS_PENDING_GENERATION', 'génération_attendue');
define('PRACTITIONER_INVOICE_STATUSES', [
    PRACTITIONER_INVOICE_STATUS_UNPAID, 
    PRACTITIONER_INVOICE_STATUS_PAID, 
    PRACTITIONER_INVOICE_STATUS_PENDING_GENERATION
]);
define('TABLE_PRACTITIONER_INVOICE_LINES', 'facture_prestataire_lignes');

define('PRACTITIONER_INVOICE_PREFIX', 'FP');

// Constantes pour les habilitations des prestataires
define('HABILITATION_STATUS_PENDING', 'en_attente_validation');
define('HABILITATION_STATUS_VERIFIED', 'vérifiée');
define('HABILITATION_STATUS_REJECTED', 'rejetée');
define('HABILITATION_STATUS_EXPIRED', 'expirée');
define('HABILITATION_STATUSES', [HABILITATION_STATUS_PENDING, HABILITATION_STATUS_VERIFIED, HABILITATION_STATUS_REJECTED, HABILITATION_STATUS_EXPIRED]);

// Constantes pour la disponibilité des prestataires
define('AVAILABILITY_TYPE_RECURRING', 'récurrente');
define('AVAILABILITY_TYPE_SPECIFIC', 'spécifique');
define('AVAILABILITY_TYPE_UNAVAILABLE', 'indisponible');
define('AVAILABILITY_TYPES', [AVAILABILITY_TYPE_RECURRING, AVAILABILITY_TYPE_SPECIFIC, AVAILABILITY_TYPE_UNAVAILABLE]);

// Limites diverses
define('DASHBOARD_RECENT_ACTIVITIES_LIMIT', 5);

// Constantes financières
define('FINANCIAL_RECENT_PAYMENT_DAYS', 7);

// Définit le fuseau horaire
date_default_timezone_set('Europe/Paris');

// Constantes pour les factures (clients)
define('INVOICE_STATUS_PENDING', 'en_attente');
define('INVOICE_STATUS_PAID', 'payée');
define('INVOICE_STATUS_CANCELLED', 'annulée');
define('INVOICE_STATUS_LATE', 'retard');
define('INVOICE_STATUS_UNPAID', 'impayée');
define('INVOICE_STATUSES', [INVOICE_STATUS_PENDING, INVOICE_STATUS_PAID, INVOICE_STATUS_CANCELLED, INVOICE_STATUS_LATE, INVOICE_STATUS_UNPAID]);
define('INVOICE_PAYMENT_MODES', ['virement', 'carte', 'prélèvement']);
define('INVOICE_PREFIX', 'F');
