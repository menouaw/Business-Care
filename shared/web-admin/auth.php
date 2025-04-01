<?php
// API pour l'authentification
require_once 'logging.php';
require_once 'config.php';
require_once 'db.php';

/**
 * Authentifie un utilisateur avec son email et mot de passe
 * 
 * @param string $email Email de l'utilisateur
 * @param string $password Mot de passe de l'utilisateur
 * @param bool $rememberMe Option pour se souvenir de l'utilisateur
 * @return bool Indique si l'authentification a réussi
 */
function login($email, $password, $rememberMe = false) {
    $user = fetchOne('personnes', "email = '$email' AND statut = 'actif'");
    
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
        
        logSecurityEvent($user['id'], 'login', '[SUCCESS] Connexion réussie');
        
        return true;
    } else {
        if ($user) {
            logSecurityEvent($user['id'], 'login', '[FAILURE] Mot de passe incorrect', true);
        } else {
            logSecurityEvent(null, 'login', '[FAILURE] Email inexistant: $email', true);
        }
        return false;
    }
}

/**
 * Déconnecte l'utilisateur authentifié et réinitialise la session.
 *
 * Cette fonction marque l'événement de déconnexion en journalisant l'action pour un utilisateur identifié,
 * supprime le token "remember_me" ainsi que son cookie associé si présent,
 * puis détruit la session PHP en cours et en démarre une nouvelle afin d'assurer une déconnexion complète.
 *
 * @return bool Indique si la déconnexion a été effectuée avec succès.
 */
function logout() {
    if (isset($_SESSION['user_id'])) {
        logSecurityEvent($_SESSION['user_id'], 'logout', '[SUCCESS] Utilisateur déconnecté');
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
 * Vérifie si l'utilisateur est authentifié.
 *
 * Cette fonction détermine d'abord si une session active existe en vérifiant la présence d'un identifiant utilisateur.
 * Si la session existe, elle compare l'heure de la dernière activité avec le délai autorisé (SESSION_LIFETIME).
 * En cas d'expiration de la session, l'événement est consigné, la fonction procède à une déconnexion automatique et retourne false.
 *
 * Si aucune session n'est active, la fonction tente une authentification via le cookie "remember_me" en validant son token.
 *
 * @return bool Retourne true si l'utilisateur est authentifié, false sinon.
 */
function isAuthenticated() {
    if (isset($_SESSION['user_id'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
            logSecurityEvent($_SESSION['user_id'], 'session_timeout', '[FAILURE] Session expirée pour l\'utilisateur ID: ' . $_SESSION['user_id']);
            logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    if (isset($_COOKIE['remember_me'])) {
        $result = validateRememberMeToken($_COOKIE['remember_me']);
        if (!$result) {
            logSecurityEvent(null, 'remember_token', '[FAILURE] Échec de validation du jeton "Se souvenir de moi"', true);
        }
        return $result;
    }
    
    return false;
}

/**
 * Vérifie que l'utilisateur est authentifié.
 *
 * Si l'utilisateur n'est pas authentifié, enregistre l'URL de la requête actuelle dans la session
 * pour permettre une redirection après connexion, puis redirige vers la page de connexion.
 * La fonction termine immédiatement l'exécution du script après la redirection.
 *
 * @return void
 */
function requireAuthentication() {
    if (!isAuthenticated()) {
        logSystemActivity('auth_required', '[FAILURE] Redirection vers la page de connexion - Accès à une page protégée: ' . $_SERVER['REQUEST_URI']);
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirectTo(WEBADMIN_URL . '/login.php');
    }
}

/**
 * Vérifie si l'utilisateur authentifié dispose de la permission nécessaire.
 *
 * Actuellement, la vérification ignore le paramètre $requiredRole et se contente de
 * confirmer que l'utilisateur possède le rôle administrateur (role_id = 3) dans la session.
 *
 * @param int $requiredRole Identifiant du rôle requis (non utilisé dans l'implémentation actuelle)
 * @return bool Retourne true si l'utilisateur est authentifié et est administrateur, sinon false.
 */
function hasPermission($requiredRole) {
    if (!isAuthenticated()) {
        logSecurityEvent(null, 'permission_check', '[FAILURE] Vérification des permissions échouée - Utilisateur non authentifié', true);
        return false;
    }
    
    $hasPermission = $_SESSION['user_role'] == ROLE_ADMIN;
    
    if (!$hasPermission) {
        logSecurityEvent($_SESSION['user_id'], 'permission_check', "[FAILURE] Accès refusé - Rôle requis: $requiredRole, Rôle actuel: {$_SESSION['user_role']}", true);
    } else {
        logSecurityEvent($_SESSION['user_id'], 'permission_check', "[SUCCESS] Accès autorisé - Rôle requis: $requiredRole, Rôle actuel: {$_SESSION['user_role']}");
    }
    
    return $hasPermission;
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
            logSystemActivity('user_info', '[FAILURE] Tentative d\'accès aux informations utilisateur ');
            return false;
        }
        $userId = $_SESSION['user_id'];
    }
    
    $userInfo = fetchOne('personnes', "id = $userId");
    
    if ($userInfo) {
        logActivity($userId, 'user_info', "[SUCCESS] Récupération des informations utilisateur ID: $userId");
    } else {
        logSystemActivity('user_info', "[FAILURE] Échec de récupération des informations pour l'utilisateur ID: $userId");
    }
    
    return $userInfo;
}

/**
 * Initialise la procédure de réinitialisation de mot de passe pour un utilisateur.
 *
 * Cette fonction vérifie si l'email fourni correspond à un utilisateur existant. Si ce n'est pas le cas,
 * elle consigne un événement de sécurité et retourne false. Sinon, elle génère un jeton unique et définit
 * une date d'expiration d'une heure pour le lien de réinitialisation, met à jour l'enregistrement de l'utilisateur,
 * et consigne l'initiation de la demande de réinitialisation.
 *
 * @param string $email Email de l'utilisateur concerné.
 * @return bool Retourne true si la procédure de réinitialisation a été initiée avec succès, false sinon.
 */
function resetPassword($email) {
    $user = fetchOne('personnes', "email = '$email'");
    
    if (!$user) {
        logSecurityEvent(null, 'password_reset', "[FAILURE] Tentative de réinitialisation pour email inexistant: $email", true);
        return false;
    }
    
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    updateRow('personnes', [
        'token' => $token,
        'expires' => $expires
    ], "id = {$user['id']}");
    
    logSecurityEvent($user['id'], 'password_reset', '[SUCCESS] Demande de réinitialisation de mot de passe initiée');
    
    // TODO: envoyer un email de reinitialisation de mot de passe
    
    return true;
}

/**
 * Génère et stocke un jeton de connexion automatique "Se souvenir de moi" pour un utilisateur.
 *
 * Le jeton, composé d'une chaîne hexadécimale de 64 caractères, est enregistré dans la base
 * de données avec une date d'expiration fixée à 30 jours. Un événement de sécurité est loggué
 * lors de sa création.
 *
 * @param int $userId Identifiant de l'utilisateur pour lequel le jeton est généré.
 * @return string Le jeton généré.
 */
function createRememberMeToken($userId) {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    insertRow('remember_me_tokens', [
        'user_id' => $userId,
        'token' => $token,
        'expires_at' => $expires
    ]);
    
    logSecurityEvent($userId, 'remember_token', '[SUCCESS] Création de jeton "Se souvenir de moi"');
    
    return $token;
}

/**
 * Valide un jeton "Se souvenir de moi" et réauthentifie l'utilisateur
 * 
 * @param string $token Jeton à valider
 * @return bool Indique si le jeton est valide et l'authentification réussie
 */
function validateRememberMeToken($token) {
    $result = fetchOne('remember_me_tokens', "token = '$token' AND expires_at > NOW()");
    
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
        } else {
            logSecurityEvent($result['user_id'], 'auto_login', '[FAILURE] Échec de connexion automatique - Utilisateur introuvable', true);
        }
    } else {
        logSecurityEvent(null, 'auto_login', '[FAILURE] Échec de connexion automatique - Jeton invalide ou expiré', true);
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
    $tokenInfo = fetchOne('remember_me_tokens', "token = '$token'");
    $userId = $tokenInfo ? $tokenInfo['user_id'] : null;
    
    $rowsAffected = deleteRow('remember_me_tokens', "token = '$token'");
    
    if ($rowsAffected > 0) {
        logSecurityEvent($userId, 'remember_token', '[SUCCESS] Suppression du jeton "Se souvenir de moi"');
        return true;
    } else {
        logSecurityEvent($userId, 'remember_token', '[FAILURE] Échec de suppression du jeton "Se souvenir de moi"', true);
        return false;
    }
} 