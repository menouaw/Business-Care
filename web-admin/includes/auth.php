<?php
require_once 'config.php';
require_once 'db.php';

function login($email, $password) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, nom, prenom, email, mot_de_passe, role_id, photo_url 
                           FROM personnes WHERE email = ? AND statut = 'actif'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['mot_de_passe'])) {
        // definit les variables de session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role_id'];
        $_SESSION['user_photo'] = $user['photo_url'];
        $_SESSION['last_activity'] = time();
        
        // met à jour le temps de derniere connexion
        $stmt = $pdo->prepare("UPDATE personnes SET derniere_connexion = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // enregistre l'activite de connexion
        logActivity($user['id'], 'login', 'Utilisateur connecte');
        
        return true;
    }
    return false;
}

function logout() {
    // enregistre l'activite de deconnexion si l'utilisateur est connecte
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout', 'Utilisateur deconnecte');
    }
    
    // detruit la session
    session_unset();
    session_destroy();
    
    // demarre une nouvelle session
    session_start();
    
    return true;
}

function isAuthenticated() {
    // verifie si l'utilisateur est connecte
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // verifie le delai de session
    if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
        logout();
        return false;
    }
    
    // met à jour le temps d'activite
    $_SESSION['last_activity'] = time();
    
    return true;
}

function requireAuthentication() {
    if (!isAuthenticated()) {
        // enregistre l'URL actuelle pour la redirection apres connexion
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function hasPermission($requiredRole) {
    if (!isAuthenticated()) {
        return false;
    }
    
    // TODO: implementer un systeme de permission propre
    // Pour l'instant, vérifie si le rôle de l'utilisateur correspond au rôle requis
    // ou si l'utilisateur est admin (role_id = 1)
    return $_SESSION['user_role'] == $requiredRole || $_SESSION['user_role'] == 1;
}

function getUserInfo($userId = null) {
    if ($userId === null) {
        if (!isAuthenticated()) {
            return false;
        }
        $userId = $_SESSION['user_id'];
    }
    
    return fetchOne('personnes', "id = $userId");
}

function logActivity($userId, $action, $details = '') {
    $data = [
        'personne_id' => $userId,
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return insertRow('logs', $data);
}

function resetPassword($email) {
    $user = fetchOne('personnes', "email = '$email'");
    
    if (!$user) {
        return false;
    }
    
    // genere un jeton de reinitialisation
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // enregistre le jeton de reinitialisation
    $data = [
        'token' => $token,
        'expires' => $expires
    ];
    
    updateRow('personnes', $data, "id = {$user['id']}");
    
    // TODO: envoyer un email de reinitialisation de mot de passe
    
    return true;
} 