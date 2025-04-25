<?php
$autoload_path = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
} else {
    error_log("Erreur Critique: Autoloader Composer non trouvé depuis init.php à " . $autoload_path . ". Exécutez 'composer install'.");
    die("Erreur de configuration serveur. Veuillez contacter l'administrateur.");
}


if (class_exists('Dotenv\Dotenv')) {
    $dotenv_path = __DIR__ . '/../..';
    $env_file_path = $dotenv_path . DIRECTORY_SEPARATOR . '.env';

    if (file_exists($env_file_path)) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable($dotenv_path);
            $dotenv->load();
        } catch (\Exception $e) {
            error_log("Erreur Dotenv depuis init.php: " . $e->getMessage());
        }
    } else {
        error_log("Warning depuis init.php: .env file not found at expected location: " . $env_file_path);
    }
} else {
    error_log("Warning depuis init.php: Dotenv class not found after autoload. Check Composer setup.");
}



session_start();

require_once __DIR__ . '/../../shared/web-client/config.php';
require_once __DIR__ . '/../../shared/web-client/db.php';
require_once __DIR__ . '/../../shared/web-client/functions.php';
require_once __DIR__ . '/../../shared/web-client/auth.php';
require_once __DIR__ . '/../../shared/web-client/logging.php';


function generateCsrfToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            flashMessage("Erreur de sécurité (jeton invalide). Veuillez réessayer.", "danger");
            redirectTo($_SERVER['HTTP_REFERER'] ?? WEBCLIENT_URL . '/index.php');
            exit;
        }
    }
}

generateCsrfToken();



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
}
