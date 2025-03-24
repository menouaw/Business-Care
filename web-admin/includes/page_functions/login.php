<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';

/**
 * Gère la soumission d'un formulaire de connexion.
 *
 * Cette fonction vérifie si la requête est de type POST et, dans ce cas, récupère l'adresse email,
 * le mot de passe, ainsi que l'option "se souvenir de moi" depuis les données du formulaire.
 * Elle contrôle la présence des champs requis et tente d'authentifier l'utilisateur via la fonction login().
 * En cas d'absence de données obligatoires, d'authentification échouée ou d'expiration de session (paramètre GET 'timeout'),
 * un message d'erreur approprié est défini.
 *
 * @return array Tableau associatif contenant le statut de la connexion (clé "success") et un message d'erreur éventuel (clé "error").
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
        } else if (login($email, $password, $rememberMe)) {
            $success = true;
        } else {
            $error = 'Email ou mot de passe invalide.';
        }
    }
    
    // Verifie si l'utilisateur a ete deconnecte pour inactivite
    if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
        $error = 'Votre session a expire. Veuillez vous reconnecter.';
    }
    
    return [
        'success' => $success,
        'error' => $error
    ];
} 