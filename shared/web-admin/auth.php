<?php
// API pour l'authentification
require_once 'logging.php';

/**
 * Authentifie un utilisateur à l'aide de son email et de son mot de passe.
 *
 * Cette fonction vérifie les informations d'identification en consultant la base de données.
 * En cas de succès, elle initialise la session avec les données de l'utilisateur et, si l'option "se souvenir de moi" est activée,
 * crée un token de reconnexion stocké dans un cookie sécurisé. Des événements de sécurité sont enregistrés pour indiquer
 * la réussite ou l'échec de la tentative de connexion.
 *
 * @param string $email Adresse email de l'utilisateur.
 * @param string $password Mot de passe saisi par l'utilisateur.
 * @param bool $rememberMe Indique si l'utilisateur doit être reconnu lors de visites ultérieures.
 * @return bool Renvoie true en cas d'authentification réussie, false sinon.
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
 * Déconnecte l'utilisateur actuellement authentifié et réinitialise la session.
 *
 * Cette fonction effectue les opérations suivantes :
 * - Enregistre un événement de sécurité de déconnexion si un identifiant d'utilisateur est présent en session.
 * - Supprime le jeton "remember me" du stockage et expire le cookie correspondant, le cas échéant.
 * - Efface toutes les données de session, détruit la session en cours puis démarre une nouvelle session.
 *
 * @return bool Retourne toujours true pour indiquer que la déconnexion a bien été effectuée.
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
 * Vérifie si l'utilisateur est authentifié.
 *
 * Cette fonction vérifie si une session utilisateur active existe et si celle-ci n'a pas dépassé
 * la durée d'inactivité définie par SESSION_LIFETIME. En cas d'expiration, elle enregistre l'événement
 * de timeout, termine la session via logout() et retourne false. Si aucune session valide n'est détectée
 * mais qu'un cookie "remember_me" est présent, la fonction tente de ré-authentifier l'utilisateur en validant ce cookie.
 *
 * @return bool True si l'utilisateur est authentifié, false sinon.
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
 * Vérifie si l'utilisateur est authentifié et, sinon, le redirige vers la page de connexion.
 *
 * Si l'utilisateur n'est pas authentifié, la fonction enregistre l'URL actuelle dans la session sous la clé 
 * 'redirect_after_login' pour permettre une redirection post-authentification, puis effectue une redirection 
 * vers la page de connexion.
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
 * Vérifie si l'utilisateur authentifié possède le rôle requis.
 *
 * Cette fonction renvoie false si l'utilisateur n'est pas authentifié.
 * Actuellement, le paramètre $requiredRole n'est pas exploité et la vérification
 * se limite à déterminer si l'utilisateur est administrateur (role_id = 3).
 *
 * @param int $requiredRole Identifiant du rôle requis (non utilisé dans la version actuelle).
 * @return bool Retourne true si l'utilisateur est administrateur, sinon false.
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
 * Démarre la procédure de réinitialisation de mot de passe pour l'utilisateur associé à l'email fourni.
 *
 * Cette fonction vérifie si l'email existe dans la base de données. En cas d'absence, elle enregistre un
 * événement de sécurité pour une tentative invalide et retourne false. Si l'utilisateur est trouvé, elle
 * génère un token de réinitialisation avec une date d'expiration d'une heure, met à jour les informations
 * correspondantes dans la base, et consigne l'initiation de la demande. Un email de réinitialisation doit ensuite
 * être envoyé (à implémenter).
 *
 * @param string $email Email de l'utilisateur pour lequel la réinitialisation est demandée.
 * @return bool Vrai si la demande a été traitée avec succès, faux sinon.
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
 * Génère et stocke un jeton sécurisé pour la connexion automatique "Se souvenir de moi".
 *
 * La fonction crée un jeton aléatoire de 64 caractères hexadécimaux, le stocke dans la base de données avec une date d'expiration fixée à 30 jours,
 * et consigne l'événement de sécurité associé.
 *
 * @param int $userId ID de l'utilisateur pour lequel le jeton est généré.
 * @return string Le jeton généré.
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
 * Valide le jeton "Se souvenir de moi" et réauthentifie automatiquement l'utilisateur.
 *
 * Cette fonction vérifie que le jeton existe dans la base de données et n'est pas expiré. Si le jeton est valide,
 * elle récupère les informations associées à l'utilisateur, initialise les variables de session nécessaires et
 * enregistre l'événement de connexion automatique.
 *
 * @param string $token Jeton à valider.
 * @return bool Vrai si le jeton est valide et l'utilisateur a été réauthentifié, faux sinon.
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