<?php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../../../shared/web-admin/logging.php';

/**
 * Traite la soumission du formulaire de connexion et gère l'authentification de l'utilisateur.
 *
 * Cette fonction vérifie si la requête est envoyée en méthode POST et récupère les informations de connexion (email, mot de passe et option "se souvenir de moi").
 * Si l'email ou le mot de passe est absent, elle enregistre une tentative de connexion échouée et renvoie un message d'erreur.
 * En présence des deux informations, elle appelle la fonction login() pour tenter l'authentification et met à jour le statut en fonction du résultat.
 * En outre, si un paramètre GET indique une expiration de session, elle consigne cet événement et renvoie un message d'erreur correspondant.
 *
 * @return array Tableau associatif contenant le statut de la connexion ('success' => bool) et, le cas échéant, un message d'erreur ('error' => string).
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