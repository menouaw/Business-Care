<?php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../../../shared/web-admin/logging.php';

/**
 * Traite la soumission du formulaire de connexion
 * @return array Tableau contenant le statut et un message d'erreur eventuel
 */
function processLoginForm() {
    $error = '';
    $success = false;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        if (empty($email) || empty($password)) {
            $error = 'Veuillez entrer un email et un mot de passe.';
            logSystemActivity('login_attempt', "Échec de connexion: informations d'identification manquantes");
        } else if (login($email, $password, $rememberMe)) {
            $success = true;
        } else {
            $error = 'Email ou mot de passe invalide.';
        }
    }
    
    // Verifie si l'utilisateur a ete deconnecte pour inactivite
    if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
        $error = 'Votre session a expire. Veuillez vous reconnecter.';
        logSystemActivity('session_timeout', "Redirection vers la page de connexion suite à l'expiration de session");
    }
    
    return [
        'success' => $success,
        'error' => $error
    ];
} 