<?php
session_start();

// inclure les fichiers partagés
require_once __DIR__ . '/../../shared/web-client/config.php';
require_once __DIR__ . '/../../shared/web-client/db.php';
require_once __DIR__ . '/../../shared/web-client/functions.php';
require_once __DIR__ . '/../../shared/web-client/auth.php';

/**
 * Génère et stocke un jeton CSRF dans la session.
 *
 * @return string Le jeton CSRF généré.
 */
function generateCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un jeton CSRF soumis par rapport à celui en session.
 *
 * @param string|null $submittedToken Le jeton soumis (généralement depuis $_POST ou $_GET). Si null, essaie de le récupérer depuis $_POST['csrf_token'].
 * @return bool True si le jeton est valide, False sinon.
 */
function verifyCsrfToken($submittedToken = null)
{
    if ($submittedToken === null && isset($_POST['csrf_token'])) {
        $submittedToken = $_POST['csrf_token'];
    }

    if (empty($submittedToken) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    $result = hash_equals($_SESSION['csrf_token'], $submittedToken);

    // Optionnel: Invalider le token après usage pour une sécurité accrue (single-use tokens)
    unset($_SESSION['csrf_token']);

    return $result;
}
