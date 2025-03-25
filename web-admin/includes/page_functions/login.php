<?php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../../../shared/web-admin/logging.php';

/**
 * Traite la soumission du formulaire de connexion.
 *
 * Cette fonction gère le traitement des informations d'identification envoyées via une requête POST, en vérifiant que l'email et le mot de passe sont renseignés. 
 * En cas d'informations manquantes, un message d'erreur est généré et un événement de tentative échouée est logué. 
 * Si les informations sont complètes, la fonction tente de connecter l'utilisateur via la fonction login(). 
 * Un échec de connexion en raison d'informations incorrectes entraîne également un message d'erreur.
 * La fonction vérifie aussi la présence d'un indicateur de timeout dans une requête GET pour signaler une expiration de session, 
 * mettant à jour le message d'erreur et loguant cet événement.
 *
 * @return array Retourne un tableau associatif contenant :
 *               - 'success' (bool) : true si la connexion a réussi, false sinon.
 *               - 'error' (string) : message d'erreur en cas d'échec (chaîne vide en cas de succès).
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