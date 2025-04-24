<?php
// SUPPRESSION du bloc de chargement .env d'ici.
// Il doit être fait dans le script d'entrée (ex: invoices.php) AVANT d'inclure ce fichier.
/*
if (class_exists('Dotenv\Dotenv')) { ... } else { ... }
*/
// --- Fin suppression ---


// configuration de la base de donnees
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'business_care');
define('DB_USER', getenv('MYSQL_USER') ?: 'business_care_user');
define('DB_PASS', getenv('DB_PASSWORD') ?: 'root'); // Note: Utiliser getenv pour le mot de passe aussi !
define('DB_CHARSET', 'utf8mb4');

// parametres de l'application
define('APP_NAME', 'Business Care');
define('APP_VERSION', '0.4.0');

// url de l'application
define('ROOT_URL', 'http://localhost/Business-Care');
define('WEBCLIENT_URL', ROOT_URL . '/web-client');

define('SHARED_URL', ROOT_URL . '/shared');
define('ASSETS_URL', ROOT_URL . '/assets');
define('API_URL', ROOT_URL . '/api');
define('UPLOAD_URL', ROOT_URL . '/uploads/');

// constantes pour les roles utilisateurs
define('ROLE_ADMIN', 1);
define('ROLE_SALARIE', 2);
define('ROLE_PRESTATAIRE', 3);
define('ROLE_ENTREPRISE', 4);

// Noms des tables
define('TABLE_USERS', 'personnes');
define('TABLE_COMPANIES', 'entreprises');
define('TABLE_PRESTATIONS', 'prestations');
define('TABLE_APPOINTMENTS', 'rendez_vous');
define('TABLE_CONTRACTS', 'contrats');
define('TABLE_USER_PREFERENCES', 'preferences_utilisateurs');
define('TABLE_LOGS', 'logs');
define('TABLE_DONATIONS', 'dons');
define('TABLE_COMMUNAUTES', 'communautes');
define('TABLE_NOTIFICATIONS', 'notifications');
define('TABLE_REMEMBER_ME', 'remember_me_tokens');
define('TABLE_SIGNALEMENTS', 'signalements');
define('TABLE_QUOTES', 'devis');

// Statuts et types divers
define('STATUS_ACTIVE', 'actif');
define('DEFAULT_DONATION_STATUS', 'pending');
define('APPOINTMENT_CANCELABLE_STATUSES', ['planifie', 'confirme']);
define('APPOINTMENT_TYPES', ['visio', 'telephone', 'presentiel']); // Assuming these types
define('DONATION_TYPES', ['financier', 'materiel']); // Assuming these types
define('EVENT_TYPES', ['conference', 'webinar', 'atelier', 'defi_sportif', 'consultation', 'autre']);
define('USER_STATUSES', ['actif', 'inactif', 'suspendu', 'supprime']);

// Limites et configurations
define('MAX_APPOINTMENTS_PER_DAY', 8);
define('DASHBOARD_ITEMS_LIMIT', 5);
define('MIN_PASSWORD_LENGTH', 8);

// Formats par défaut
define('DEFAULT_DATE_FORMAT', 'd/m/Y H:i');
define('DEFAULT_CURRENCY', '€');
define('DEFAULT_CURRENCY_CODE', 'eur');
define('DEFAULT_ITEMS_PER_PAGE', 10);

// duree de vie de la session en secondes (30 minutes)
define('SESSION_LIFETIME', 1800);

// definit le fuseau horaire
date_default_timezone_set('Europe/Paris');

// --- Configuration Stripe ---
// Lire les clés depuis les variables d'environnement (qui DOIVENT être chargées AVANT)
// Essayer $_ENV d'abord (rempli par Dotenv), puis getenv() en fallback.
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY') ?: '');
define('STRIPE_PUBLIC_KEY', $_ENV['STRIPE_PUBLIC_KEY'] ?? getenv('STRIPE_PUBLIC_KEY') ?: '');
define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET'] ?? getenv('STRIPE_WEBHOOK_SECRET') ?: ''); // Secret pour vérifier les webhooks

// constantes specifiques au web-client (s'assurer qu'elles n'entrent pas en conflit)
// define('STRIPE_PUBLIC_KEY', ''); // Ancienne définition, à commenter ou supprimer
define('STRIPE_JS_URL', 'https://js.stripe.com/v3/');

// Constantes pour les factures (clients)
define('INVOICE_STATUS_PENDING', 'en_attente');
define('INVOICE_STATUS_PAID', 'payee');
define('INVOICE_STATUS_CANCELLED', 'annulee');
define('INVOICE_STATUS_LATE', 'retard');
define('INVOICE_STATUS_UNPAID', 'impayee');
define('INVOICE_STATUSES', [INVOICE_STATUS_PENDING, INVOICE_STATUS_PAID, INVOICE_STATUS_CANCELLED, INVOICE_STATUS_LATE, INVOICE_STATUS_UNPAID]);
define('INVOICE_PAYMENT_MODES', ['virement', 'carte', 'prelevement']);
define('INVOICE_PREFIX', 'F');


if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
    define('K_TCPDF_EXTERNAL_CONFIG', true);
}
// Removing the if(!defined(...)) checks for the constants below
define('PDF_PAGE_ORIENTATION', 'P');
define('PDF_UNIT', 'mm');
define('PDF_PAGE_FORMAT', 'A4');
define('PDF_CREATOR', 'Business Care TCPDF');
define('PDF_AUTHOR', APP_NAME ?? 'Business Care');
define('PDF_MARGIN_LEFT', 15);
define('PDF_MARGIN_TOP', 15);
define('PDF_MARGIN_RIGHT', 15);
define('PDF_MARGIN_BOTTOM', 15);
define('PDF_FONT_NAME_MAIN', 'helvetica');
define('PDF_FONT_SIZE_MAIN', 10);
// --- Fin Constantes TCPDF ---
