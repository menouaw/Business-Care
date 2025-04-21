<?php
// configuration de la base de donnees
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'business_care');
define('DB_USER', getenv('MYSQL_USER') ?: 'business_care_user');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');
define('DB_CHARSET', 'utf8mb4');

// parametres de l'application
define('APP_NAME', 'Business Care');
define('APP_VERSION', '0.4.0');

// url de l'application
define('ROOT_URL', 'http://localhost');
define('WEBCLIENT_URL', ROOT_URL . '/client');
define('WEBADMIN_URL', ROOT_URL . '/web-admin');

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
define('DEFAULT_ITEMS_PER_PAGE', 10);

// duree de vie de la session en secondes (30 minutes)
define('SESSION_LIFETIME', 1800);

// definit le fuseau horaire
date_default_timezone_set('Europe/Paris');

// constantes specifiques au web-client
define('REMOVED', 'pk_test_51RG1rUCS7H8MIpLmIl0i9xgv1TEpg5hXe9fZZKZF2VboTBXVvJG02V4CoLdYnPGV6v0aK4RIekGvNuG3UiUADoLS00kQ2d2e07');
define('REMOVED', 'sk_test_51RG1rUCS7H8MIpLmrY7d2OezYTkw6JDofEvEM0t0FbLBBo1inAw25c40HW7OgO0NwJRjAnOSBFAEuRVu3umXRgw600QUrz9KEU');
define('STRIPE_JS_URL', 'https://js.stripe.com/v3/');
