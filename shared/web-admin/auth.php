<?php
// API pour l'authentification
require_once 'logging.php';

/**
 * Authentifie un utilisateur avec son email et mot de passe
 * 
 * @param string $email Email de l'utilisateur
 * @param string $password Mot de passe de l'utilisateur
 * @param bool $rememberMe Option pour se souvenir de l'utilisateur
 * @return bool Indique si l'authentification a réussi
 */
function login($email, $password, $rememberMe = false) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, nom, prenom, email, mot_de_passe, role_id, photo_url 
                           FROM personnes WHERE email = ? AND statut = 'actif'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role_id'];
        $_SESSION['user_photo'] = $user['photo_url'];
        $_SESSION['last_activity'] = time();
        
        if ($rememberMe) {
            $token = createRememberMeToken($user['id']);
            setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
        }
        
        logSecurityEvent($user['id'], 'login', 'Connexion réussie');
        
        return true;
    } else {
        if ($user) {
            logSecurityEvent($user['id'], 'login', 'Échec de connexion: mot de passe incorrect', true);
        } else {
            logSecurityEvent(null, 'login', "Tentative de connexion avec email inexistant: $email", true);
        }
        return false;
    }
}

/**
 * Déconnecte l'utilisateur actuellement authentifié
 * 
 * @return bool Indique si la déconnexion a réussi
 */
function logout() {
    if (isset($_SESSION['user_id'])) {
        logSecurityEvent($_SESSION['user_id'], 'logout', 'Utilisateur déconnecté');
    }
    
    if (isset($_COOKIE['remember_me'])) {
        deleteRememberMeToken($_COOKIE['remember_me']);
        setcookie('remember_me', '', time() - 3600, '/', '', true, true);
    }
    
    session_unset();
    session_destroy();
    session_start();
    
    return true;
}

/**
 * Vérifie si l'utilisateur est actuellement authentifié
 * 
 * @return bool État d'authentification de l'utilisateur
 */
function isAuthenticated() {
    if (isset($_SESSION['user_id'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
            logSystemActivity('session_timeout', "Session expirée pour l'utilisateur ID: " . $_SESSION['user_id']);
            logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    if (isset($_COOKIE['remember_me'])) {
        return validateRememberMeToken($_COOKIE['remember_me']);
    }
    
    return false;
}

/**
 * Force l'authentification de l'utilisateur ou redirige vers la page de connexion
 * 
 * @return void
 */
function requireAuthentication() {
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . WEBADMIN_URL . '/login.php');
        exit;
    }
}

/**
 * Vérifie si l'utilisateur authentifié a le rôle requis
 * 
 * @param int $requiredRole Identifiant du rôle requis
 * @return bool Indique si l'utilisateur a la permission
 */
function hasPermission($requiredRole) {
    if (!isAuthenticated()) {
        return false;
    }
    
    // TODO: implementer un systeme de permission propre
    // pour l'instant, verifie si le rôle ID est admin (role_id = 3)
    return $_SESSION['user_role'] == 3;
}

/**
 * Récupère les informations d'un utilisateur
 * 
 * @param int|null $userId ID de l'utilisateur (utilise l'utilisateur courant si null)
 * @return array|false Informations de l'utilisateur ou false si non trouvé/authentifié
 */
function getUserInfo($userId = null) {
    if ($userId === null) {
        if (!isAuthenticated()) {
            return false;
        }
        $userId = $_SESSION['user_id'];
    }
    
    return fetchOne('personnes', "id = $userId");
}

/**
 * Initialise la procédure de réinitialisation de mot de passe
 * 
 * @param string $email Email de l'utilisateur
 * @return bool Indique si la demande a été traitée avec succès
 */
function resetPassword($email) {
    $user = fetchOne('personnes', "email = '$email'");
    
    if (!$user) {
        logSecurityEvent(null, 'password_reset', "Tentative de réinitialisation pour email inexistant: $email", true);
        return false;
    }
    
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    updateRow('personnes', [
        'token' => $token,
        'expires' => $expires
    ], "id = {$user['id']}");
    
    logSecurityEvent($user['id'], 'password_reset', 'Demande de réinitialisation de mot de passe initiée');
    
    // TODO: envoyer un email de reinitialisation de mot de passe
    
    return true;
}

/**
 * Crée un jeton de connexion automatique "Se souvenir de moi"
 * 
 * @param int $userId ID de l'utilisateur
 * @return string Jeton généré
 */
function createRememberMeToken($userId) {
    $pdo = getDbConnection();
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $pdo->prepare("INSERT INTO remember_me_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $token, $expires]);
    
    logSecurityEvent($userId, 'remember_token', 'Création de jeton "Se souvenir de moi"');
    
    return $token;
}

/**
 * Valide un jeton "Se souvenir de moi" et réauthentifie l'utilisateur
 * 
 * @param string $token Jeton à valider
 * @return bool Indique si le jeton est valide et l'authentification réussie
 */
function validateRememberMeToken($token) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT user_id FROM remember_me_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $result = $stmt->fetch();
    
    if ($result) {
        $user = getUserInfo($result['user_id']);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role_id'];
            $_SESSION['user_photo'] = $user['photo_url'];
            $_SESSION['last_activity'] = time();
            
            logSecurityEvent($user['id'], 'auto_login', 'Connexion automatique via jeton "Se souvenir de moi"');
            return true;
        }
    }
    return false;
}

/**
 * Supprime un jeton "Se souvenir de moi"
 * 
 * @param string $token Jeton à supprimer
 * @return bool Indique si la suppression a réussi
 */
function deleteRememberMeToken($token) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM remember_me_tokens WHERE token = ?");
    return $stmt->execute([$token]);
} 