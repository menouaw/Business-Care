<?php

/**
 * script de déconnexion
 *
 * ce script déconnecte l'utilisateur et le redirige vers la page d'accueil
 */

require_once __DIR__ . '/includes/init.php';



logSecurityEvent($_SESSION['user_id'] ?? null, 'logout', '[INFO] Déconnexion utilisateur ID: ' . ($_SESSION['user_id'] ?? 'Inconnu'));


$_SESSION = array();




if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


session_destroy();

logout();

redirectTo(WEBCLIENT_URL . '/auth/login.php');
exit;
