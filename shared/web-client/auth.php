<?php
// API pour l'authentification côté client
require_once 'logging.php';
require_once 'db.php';
require_once 'functions.php';

/**
 * Authentifie un utilisateur via son adresse email et son mot de passe.
 *
 * Cette méthode vérifie si un utilisateur actif correspondant à l'email fourni existe et valide le mot de passe en le comparant au hash stocké. 
 * En cas de succès, elle initialise la session avec les informations de l'utilisateur et charge ses préférences. 
 * Si l'option de mémorisation est activée, un token "remember me" est généré et stocké dans un cookie sécurisé afin de faciliter une reconnexion ultérieure.
 * Des événements de sécurité sont également enregistrés pour indiquer le résultat de l'opération (succès ou échec).
 *
 * @param string $email Adresse email de l'utilisateur.
 * @param string $password Mot de passe fourni pour l'authentification.
 * @param bool $rememberMe Indique si l'utilisateur doit être mémorisé pour une reconnexion automatique. Par défaut à false.
 * @return bool Retourne true si l'authentification réussit, sinon false.
 */
function login($email, $password, $rememberMe = false) {
    $user = fetchOne('personnes', "email = '$email' AND statut = 'actif'");
    
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
        
        logSecurityEvent($user['id'], 'login', '[SUCCESS] Connexion réussie');
        
        return true;
    } else {
        if ($user) {
            logSecurityEvent($user['id'], 'login', '[FAILURE] Mot de passe incorrect', true);
        } else {
            logSecurityEvent(null, 'login', "[FAILURE] Email inexistant: $email", true);
        }
        return false;
    }
}

/**
 * Déconnecte l'utilisateur en réinitialisant la session et en supprimant le cookie "remember_me".
 *
 * Si l'utilisateur est authentifié, la fonction enregistre un événement de sécurité correspondant à la déconnexion.
 * Elle vérifie également la présence d'un cookie "remember_me" pour supprimer le jeton associé et invalider le cookie.
 * La session est ensuite vidée, détruite et une nouvelle session est démarrée pour assurer une réinitialisation complète.
 *
 * @return bool Vrai si la déconnexion a été effectuée avec succès.
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
 * Vérifie si l'utilisateur est authentifié via sa session active ou un token de connexion persistante.
 *
 * La fonction examine d'abord l'existence d'une session utilisateur. Si une session est présente, elle vérifie que le temps écoulé depuis
 * la dernière activité ne dépasse pas la durée définie par SESSION_LIFETIME. En cas d'expiration, la session est invalidée avec une déconnexion,
 * et un événement de session expirée est loggé. Si la session est toujours valide, l'heure de la dernière activité est mise à jour.
 * En l'absence de session active, la fonction tente de valider un token 'remember_me' stocké dans un cookie pour réauthentifier l'utilisateur.
 *
 * @return bool Retourne true si l'utilisateur est authentifié, false sinon.
 */
function isAuthenticated() {
    if (isset($_SESSION['user_id'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
            logSystemActivity('session_timeout', "[FAILURE] Session expirée pour l'utilisateur ID: " . $_SESSION['user_id']);
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
        redirectTo(WEBCLIENT_URL . '/connexion.php');
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
 * Vérifie si l'utilisateur authentifié possède le rôle requis et redirige en cas d'accès refusé.
 *
 * La fonction commence par vérifier que l'utilisateur est authentifié. Si l'utilisateur ne dispose pas
 * du rôle spécifié par l'identifiant passé en paramètre, un message d'accès refusé est affiché et il est
 * redirigé vers la page d'accueil.
 *
 * @param int $requiredRole Identifiant numérique du rôle requis.
 *
 * @return void
 */
function requireRole($requiredRole) {
    requireAuthentication();
    
    if (!hasRole($requiredRole)) {
        flashMessage('[ACCESS DENIED] Vous n\'avez pas les permissions nécessaires', 'danger');
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
 * Initialise la procédure de réinitialisation de mot de passe pour un utilisateur.
 *
 * Cette fonction vérifie l'existence d'un utilisateur correspondant à l'email fourni.
 * Si l'utilisateur est trouvé, elle génère un token de réinitialisation ainsi qu'une date d'expiration,
 * met à jour les informations de l'utilisateur dans la base de données, et consigne l'événement de la demande.
 * Si aucun utilisateur n'est associé à l'email, l'événement est consigné comme un échec.
 *
 * @param string $email L'adresse email de l'utilisateur concerné.
 * @return bool Retourne true si la procédure a été initiée avec succès, false sinon.
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
    
    // TODO: envoyer un email de reinitialisation de mot de passe avec lien contenant le token
    
    return true;
}

/**
 * Charge et initialise les préférences de l'utilisateur dans la session.
 *
 * La fonction récupère les préférences depuis la table "preferences_utilisateurs" via fetchOne. 
 * Si des préférences sont trouvées, la langue définie dans le champ "langue" est enregistrée dans la session sous "user_language".
 *
 * @param int $userId L'identifiant unique de l'utilisateur.
 *
 * @return void
 */
function loadUserPreferences($userId) {
    $result = fetchOne('preferences_utilisateurs', "personne_id = $userId");
    
    if ($result) {
        $_SESSION['user_language'] = $result['langue'];
    }
}

/**
 * Crée et stocke un jeton "Se souvenir de moi" unique pour l'utilisateur.
 *
 * Ce jeton, valable pendant 30 jours, permet une reconnexion automatique en l'absence de session active.
 * Le jeton est enregistré dans la base de données et un événement de sécurité est consigné.
 *
 * @param int $userId L'identifiant de l'utilisateur pour lequel générer le jeton.
 * @return string Le jeton généré.
 */
function createRememberMeToken($userId) {
    $pdo = getDbConnection();
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
            $_SESSION['user_entreprise'] = $user['entreprise_id'];
            $_SESSION['user_photo'] = $user['photo_url'];
            $_SESSION['last_activity'] = time();
            
            // charger les préférences utilisateur
            loadUserPreferences($user['id']);
            
            logSecurityEvent($user['id'], 'auto_login', '[SUCCESS] Connexion automatique via jeton "Se souvenir de moi"');
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
    $result = deleteRow('remember_me_tokens', "token = '$token'");
    return $result > 0;
} 