<?php
// API pour l'authentification côté client
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
    $stmt = $pdo->prepare("SELECT id, nom, prenom, email, mot_de_passe, role_id, entreprise_id, photo_url 
                          FROM personnes WHERE email = ? AND statut = 'actif'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['mot_de_passe'])) {
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role_id'];
        $_SESSION['user_entreprise'] = $user['entreprise_id'];
        $_SESSION['user_photo'] = $user['photo_url'];
        $_SESSION['last_activity'] = time();
        
        // stocker les préférences utilisateur
        loadUserPreferences($user['id']);
        
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
 * Déconnecte l'utilisateur et réinitialise la session
 *
 * @return bool Indique si la déconnexion a été effectuée avec succès
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
 * Vérifie si l'utilisateur est authentifié
 *
 * @return bool Retourne true si l'utilisateur est authentifié, false sinon
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
 * Vérifie que l'utilisateur est authentifié et redirige si nécessaire
 *
 * @return void
 */
function requireAuthentication() {
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . WEBCLIENT_URL . '/connexion.php');
        exit;
    }
}

/**
 * Vérifie si l'utilisateur authentifié dispose du rôle spécifié
 *
 * @param int $requiredRole Identifiant du rôle requis
 * @return bool Retourne true si l'utilisateur est authentifié et a le rôle requis
 */
function hasRole($requiredRole) {
    if (!isAuthenticated()) {
        return false;
    }
    
    return $_SESSION['user_role'] == $requiredRole;
}

/**
 * Vérifie si l'utilisateur authentifié est un représentant d'entreprise
 *
 * @return bool Retourne true si l'utilisateur est un représentant d'entreprise
 */
function isEntrepriseUser() {
    return hasRole(ROLE_ENTREPRISE);
}

/**
 * Vérifie si l'utilisateur authentifié est un salarié
 *
 * @return bool Retourne true si l'utilisateur est un salarié
 */
function isSalarieUser() {
    return hasRole(ROLE_SALARIE);
}

/**
 * Vérifie si l'utilisateur authentifié est un prestataire
 *
 * @return bool Retourne true si l'utilisateur est un prestataire
 */
function isPrestataireUser() {
    return hasRole(ROLE_PRESTATAIRE);
}

/**
 * Vérifie l'accès au rôle spécifié et redirige si non autorisé
 *
 * @param int $requiredRole Identifiant du rôle requis
 * @return void
 */
function requireRole($requiredRole) {
    requireAuthentication();
    
    if (!hasRole($requiredRole)) {
        flashMessage('accès refusé: vous n\'avez pas les permissions nécessaires', 'danger');
        redirectTo(WEBCLIENT_URL . '/index.php');
    }
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
 * Initialise la procédure de réinitialisation de mot de passe pour un utilisateur
 *
 * @param string $email Email de l'utilisateur concerné
 * @return bool Retourne true si la procédure de réinitialisation a été initiée avec succès
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
    
    // TODO: envoyer un email de reinitialisation de mot de passe avec lien contenant le token
    
    return true;
}

/**
 * Charge les préférences utilisateur en session
 * 
 * @param int $userId ID de l'utilisateur
 * @return void
 */
function loadUserPreferences($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT langue FROM preferences_utilisateurs WHERE personne_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    if ($result) {
        $_SESSION['user_language'] = $result['langue'];
    }
}

/**
 * Génère et stocke un jeton de connexion automatique "Se souvenir de moi"
 *
 * @param int $userId Identifiant de l'utilisateur
 * @return string Le jeton généré
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
            $_SESSION['user_entreprise'] = $user['entreprise_id'];
            $_SESSION['user_photo'] = $user['photo_url'];
            $_SESSION['last_activity'] = time();
            
            // charger les préférences utilisateur
            loadUserPreferences($user['id']);
            
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