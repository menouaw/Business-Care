<?php
// configuration de la base de donnees
define('DB_HOST', 'localhost');
define('DB_NAME', 'business_care');
define('DB_USER', 'business_care_user');
define('DB_PASS', 'business_care_password');
define('DB_CHARSET', 'utf8mb4');

// parametres de l'application
define('APP_NAME', 'Business Care');
define('APP_VERSION', '0.4.0');

// url de l'application
define('WEBCLIENT_URL', 'http://localhost/Business-Care/web-client');
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

// duree de vie de la session en secondes (30 minutes)
define('SESSION_LIFETIME', 1800);

// definit le fuseau horaire
date_default_timezone_set('Europe/Paris');

// constantes specifiques au web-client
define('STRIPE_PUBLIC_KEY', '');
define('STRIPE_JS_URL', 'https://js.stripe.com/v3/');
