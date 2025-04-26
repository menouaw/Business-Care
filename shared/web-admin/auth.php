<?php

require_once 'logging.php';
require_once 'config.php';
require_once 'db.php';

/**
 * Authentifie un utilisateur en vérifiant son email et son mot de passe puis initialise la session.
 *
 * La fonction recherche un utilisateur actif par son adresse email et valide le mot de passe fourni. Si l'authentification est réussie, elle
 * initialise les variables de session avec les informations de l'utilisateur et crée, si demandé, un cookie de connexion persistante ("remember me").
 * Un événement de sécurité est enregistré pour consigner le résultat de l'opération.
 *
 * @param string $email Email de l'utilisateur.
 * @param string $password Mot de passe de l'utilisateur.
 * @param bool $rememberMe Indique si un token "remember me" doit être créé.
 *
 * @return bool Retourne true si l'authentification réussit, sinon false.
 */
function login($email, $password, $rememberMe = false)
{
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
function logout()
{
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
function isAuthenticated()
{
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
function requireAuthentication()
{
    if (!isAuthenticated()) {
        logSystemActivity('[SECURITY]:auth_required', '[FAILURE] Redirection vers la page de connexion - Accès à une page protégée: ' . $_SERVER['REQUEST_URI']);
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirectTo(WEBADMIN_URL . '/login.php');
    }
}

/**
 * Vérifie si l'utilisateur authentifié dispose du rôle spécifié.
 *
 * @param int $requiredRole Identifiant du rôle requis.
 * @return bool Retourne true si l'utilisateur est authentifié et a le rôle requis, sinon false.
 */
function hasRole($requiredRole)
{
    if (!isAuthenticated()) {
        logSecurityEvent(null, 'role_check', '[FAILURE] Vérification de rôle échouée - Utilisateur non authentifié', true);
        return false;
    }

    $hasRequiredRole = isset($_SESSION['user_role']) && $_SESSION['user_role'] == $requiredRole;

    if (!$hasRequiredRole) {
        logSecurityEvent($_SESSION['user_id'], 'role_check', "[FAILURE] Rôle insuffisant - Requis: $requiredRole, Actuel: {$_SESSION['user_role']}", true);
    } else {
        logSecurityEvent($_SESSION['user_id'], 'role_check', "[SUCCESS] Rôle suffisant - Requis: $requiredRole, Actuel: {$_SESSION['user_role']}");
    }

    return $hasRequiredRole;
}

/**
 * Vérifie que l'utilisateur est authentifié et possède le rôle requis, redirige sinon.
 *
 * Appelle requireAuthentication pour s'assurer que l'utilisateur est connecté.
 * Si l'utilisateur n'a pas le rôle requis, enregistre l'événement et redirige
 * vers la page de connexion administrateur avec une erreur.
 *
 * @param int $requiredRole Identifiant du rôle requis.
 * @return void
 */
function requireRole($requiredRole)
{
    requireAuthentication();

    if (!hasRole($requiredRole)) {
        logSecurityEvent(
            $_SESSION['user_id'] ?? null,
            'permission_denied',
            '[FAILURE] Accès refusé à ' . $_SERVER['REQUEST_URI'] . " - Rôle requis: $requiredRole",
            true
        );

        redirectTo(WEBADMIN_URL . '/login.php?error=permission_denied');
    }
}

/**
 * Récupère les informations d'un utilisateur ou de l'utilisateur connecté.
 *
 * Si aucun identifiant n'est fourni, la fonction utilise l'utilisateur actuellement authentifié.
 * Elle retourne les informations de l'utilisateur sous forme de tableau associatif, ou false si l'utilisateur n'existe pas ou n'est pas authentifié.
 *
 * @param int|null $userId Identifiant de l'utilisateur à récupérer. Utilise l'utilisateur connecté si null.
 * @return array|false Tableau associatif des informations utilisateur, ou false en cas d'absence d'utilisateur valide.
 */
function getUserInfo($userId = null)
{
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
 * elle consigne un événement de sécurité et retourne false. Sinon, elle génère un token unique et définit
 * une date d'expiration d'une heure pour le lien de réinitialisation, met à jour l'enregistrement de l'utilisateur,
 * et consigne l'initiation de la demande de réinitialisation.
 *
 * @param string $email Email de l'utilisateur concerné.
 * @return bool Retourne true si la procédure de réinitialisation a été initiée avec succès, false sinon.
 */
function resetPassword($email)
{
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

    

    return true;
}

/**
 * Génère et stocke un token de connexion automatique "Se souvenir de moi" pour un utilisateur.
 *
 * Le token, composé d'une chaîne hexadécimale de 64 caractères, est enregistré dans la base
 * de données avec une date d'expiration fixée à 30 jours. Un événement de sécurité est loggé
 * lors de sa création.
 *
 * @param int $userId Identifiant de l'utilisateur pour lequel le token est généré.
 * @return string Le token généré.
 */
function createRememberMeToken($userId)
{
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

    insertRow('remember_me_tokens', [
        'user_id' => $userId,
        'token' => $token,
        'expires_at' => $expires
    ]);

    logSecurityEvent($userId, 'remember_token', '[SUCCESS] Création de token "Se souvenir de moi"');

    return $token;
}

/**
 * Valide un token "Se souvenir de moi" et ré-authentifie l'utilisateur associé.
 *
 * Cette fonction vérifie que le token fourni existe et n'est pas expiré dans la base de données. 
 * Si le token est valide, elle récupère les informations de l'utilisateur correspondant, initialise 
 * les variables de session et enregistre un événement de connexion automatique. En cas d'invalidité 
 * du token ou si l'utilisateur est introuvable, un événement d'échec est consigné.
 *
 * @param string $token Le token à valider.
 * @return bool Renvoie true si la ré-authentification a réussi, sinon false.
 */
function validateRememberMeToken($token)
{
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

            logSecurityEvent($user['id'], 'auto_login', 'Connexion automatique via token "Se souvenir de moi"');
            return true;
        } else {
            logSecurityEvent($result['user_id'], 'auto_login', '[FAILURE] Échec de connexion automatique - Utilisateur introuvable', true);
        }
    } else {
        logSecurityEvent(null, 'auto_login', '[FAILURE] Échec de connexion automatique - Token invalide ou expiré', true);
    }
    return false;
}

/**
 * Supprime un token "Se souvenir de moi" de la base de données et journalise l'opération.
 *
 * Cette fonction recherche le token dans la table dédiée et tente de le supprimer. Elle enregistre ensuite
 * un événement de sécurité indiquant si l'opération a réussi ou échoué.
 *
 * @param string $token Token d'authentification à supprimer.
 * @return bool Retourne true si la suppression est effectuée avec succès, sinon false.
 */
function deleteRememberMeToken($token)
{
    $tokenInfo = fetchOne('remember_me_tokens', "token = '$token'");
    $userId = $tokenInfo ? $tokenInfo['user_id'] : null;

    $rowsAffected = deleteRow('remember_me_tokens', "token = '$token'");

    if ($rowsAffected > 0) {
        logSecurityEvent($userId, 'remember_token', '[SUCCESS] Suppression du token "Se souvenir de moi"');
        return true;
    } else {
        logSecurityEvent($userId, 'remember_token', '[FAILURE] Échec de suppression du token "Se souvenir de moi"', true);
        return false;
    }
}
