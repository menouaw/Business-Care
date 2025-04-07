<?php
session_start();

// inclure les fichiers partagés
require_once __DIR__ . '/../../shared/web-client/config.php';
require_once __DIR__ . '/../../shared/web-client/db.php';
require_once __DIR__ . '/../../shared/web-client/functions.php';
require_once __DIR__ . '/../../shared/web-client/auth.php';

/**
 * Génère et stocke un jeton CSRF dans la session si nécessaire.
 *
 * @return string Le jeton CSRF.
 */
function generateCsrfToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    // echo "Generating/Returning Token: " . $_SESSION['csrf_token'] . "<br>"; 
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le jeton CSRF pour les requêtes POST.
 * En cas d'échec, affiche un message flash et redirige.
 */
function verifyCsrfToken()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals(trim($_SESSION['csrf_token']), trim($_POST['csrf_token']))) {
            error_log("CSRF token validation failed. SESSION: " . ($_SESSION['csrf_token'] ?? 'Not Set') . " POST: " . ($_POST['csrf_token'] ?? 'Not Set'));


            flashMessage("Erreur de sécurité (jeton invalide). Veuillez réessayer.", "danger");
            redirectTo($_SERVER['HTTP_REFERER'] ?? WEBCLIENT_URL . '/index.php');
            exit;
        }
    }
}
