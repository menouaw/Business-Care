<?php

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'business_care');
define('DB_USER', getenv('MYSQL_USER') ?: 'business_care_user');
define('DB_PASS', getenv('DB_PASSWORD') ?: 'root');
define('DB_CHARSET', 'utf8mb4');


define('APP_NAME', getenv('APP_NAME') ?: 'Business Care');
define('APP_VERSION', getenv('APP_VERSION') ?: '0.4.0');


define('ROOT_URL', 'http://192.168.213.22');
define('WEBCLIENT_URL', ROOT_URL . '/client');

define('SHARED_URL', ROOT_URL . '/../shared');
define('ASSETS_URL', ROOT_URL . '/assets');
define('API_URL', ROOT_URL . '/../api');
define('UPLOAD_URL', ROOT_URL . '/uploads/');


define('ROLE_ADMIN', 1);
define('ROLE_SALARIE', 2);
define('ROLE_PRESTATAIRE', 3);
define('ROLE_ENTREPRISE', 4);


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
define('TABLE_HABILITATIONS', 'habilitations');


define('STATUS_ACTIVE', 'actif');
define('DEFAULT_DONATION_STATUS', 'pending');
define('APPOINTMENT_CANCELABLE_STATUSES', ['planifie', 'confirme']);
define('APPOINTMENT_TYPES', ['visio', 'telephone', 'presentiel']);
define('DONATION_TYPES', ['financier', 'materiel']);
define('EVENT_TYPES', ['conference', 'webinar', 'atelier', 'defi_sportif', 'consultation', 'autre']);
define('USER_STATUSES', ['actif', 'inactif', 'suspendu', 'supprime']);


define('MAX_APPOINTMENTS_PER_DAY', getenv('MAX_APPOINTMENTS_PER_DAY') ? (int)getenv('MAX_APPOINTMENTS_PER_DAY') : 8);
define('DASHBOARD_ITEMS_LIMIT', getenv('DASHBOARD_ITEMS_LIMIT') ? (int)getenv('DASHBOARD_ITEMS_LIMIT') : 5);
define('MIN_PASSWORD_LENGTH', getenv('MIN_PASSWORD_LENGTH') ? (int)getenv('MIN_PASSWORD_LENGTH') : 8);

define('DEFAULT_DATE_FORMAT', getenv('DEFAULT_DATE_FORMAT') ?: 'd/m/Y H:i');
define('DEFAULT_CURRENCY', getenv('DEFAULT_CURRENCY') ?: '€');
define('DEFAULT_CURRENCY_CODE', getenv('DEFAULT_CURRENCY_CODE') ?: 'eur');
define('DEFAULT_ITEMS_PER_PAGE', getenv('DEFAULT_ITEMS_PER_PAGE') ? (int)getenv('DEFAULT_ITEMS_PER_PAGE') : 5);


define('SESSION_LIFETIME', getenv('SESSION_LIFETIME') ? (int)getenv('SESSION_LIFETIME') : 1800);


date_default_timezone_set('Europe/Paris');


// Stripe keys from .env (simple definitions)
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: ($_ENV['STRIPE_SECRET_KEY'] ?? null));
define('STRIPE_PUBLIC_KEY', getenv('STRIPE_PUBLIC_KEY') ?: ($_ENV['STRIPE_PUBLIC_KEY'] ?? null));
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: ($_ENV['STRIPE_WEBHOOK_SECRET'] ?? null));

// Initialize Stripe API key (unconditionally)
// Assumes STRIPE_SECRET_KEY is loaded from .env and Stripe SDK is available via Composer
if (class_exists('\Stripe\Stripe')) { // On garde la vérification de l'existence de la classe pour éviter une erreur fatale si le SDK est manquant
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY); 
}

define('STRIPE_JS_URL', 'https://js.stripe.com/v3/');


define('INVOICE_STATUS_PENDING', 'en_attente');
define('INVOICE_STATUS_PAID', 'payee');
define('INVOICE_STATUS_CANCELLED', 'annulee');
define('INVOICE_STATUS_LATE', 'retard');
define('INVOICE_STATUS_UNPAID', 'impayee');
define('INVOICE_STATUSES', [INVOICE_STATUS_PENDING, INVOICE_STATUS_PAID, INVOICE_STATUS_CANCELLED, INVOICE_STATUS_LATE, INVOICE_STATUS_UNPAID]);
define('INVOICE_PAYMENT_MODES', ['virement', 'carte', 'prelevement']);
define('INVOICE_PREFIX', 'F');


define('TVA_RATE', getenv('TVA_RATE') ? (float)getenv('TVA_RATE') : 0.20);



define('QUOTE_STATUS_PENDING', 'en_attente');
define('QUOTE_STATUS_ACCEPTED', 'accepte');
define('QUOTE_STATUS_REFUSED', 'refuse');
define('QUOTE_STATUS_EXPIRED', 'expire');
define('QUOTE_STATUS_CUSTOM_REQUEST', 'en_attente');
define('QUOTE_STATUSES', [QUOTE_STATUS_PENDING, QUOTE_STATUS_ACCEPTED, QUOTE_STATUS_REFUSED, QUOTE_STATUS_EXPIRED, QUOTE_STATUS_CUSTOM_REQUEST]);

if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
    define('K_TCPDF_EXTERNAL_CONFIG', true);
}

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


define('ENVIRONMENT', $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production');
define('DEVIS_STATUT_VALIDE', 'validé');
