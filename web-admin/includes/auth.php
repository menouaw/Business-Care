<?php
/**
 * Authentifie un utilisateur et initialise sa session.
 *
 * Vérifie qu'un utilisateur actif correspondant à l'email fourni existe et que le mot de passe correspond.
 * En cas d'authentification réussie, les informations de l'utilisateur sont enregistrées dans la session
 * et l'activité de connexion est loguée. Si l'option "se souvenir de moi" est activée, un token est généré
 * et un cookie est défini pour maintenir la connexion pendant 30 jours.
 *
 * @param string $email Adresse email de l'utilisateur.
 * @param string $password Mot de passe de l'utilisateur.
 * @param bool $rememberMe Indique si l'option "se souvenir de moi" doit être activée.
 * @return bool Retourne true en cas d'authentification réussie, sinon false.
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
        
        logActivity($user['id'], 'login', 'Utilisateur connecte');
        
        return true;
    }
    return false;
}

/**
 * Déconnecte l'utilisateur en enregistrant l'activité de déconnexion, en supprimant le cookie "remember_me" s'il existe, et en réinitialisant la session.
 *
 * Si une session utilisateur est active, l'activité de déconnexion est consignée. Si un cookie "remember_me" est présent, il est supprimé de la base de données et effacé du navigateur. La session est alors nettoyée, détruite puis redémarrée.
 *
 * @return bool Retourne toujours true.
 */
function logout() {
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout', 'Utilisateur deconnecte');
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
 * Cette fonction contrôle la présence d'une session active en vérifiant si un identifiant utilisateur est défini.
 * Si la session existe, elle vérifie que la durée d'inactivité n'a pas dépassé SESSION_LIFETIME, et met à jour le marqueur de dernière activité.
 * En cas d'expiration de la session, l'utilisateur est déconnecté et la fonction retourne false.
 * Si aucune session n'est active mais qu'un cookie "remember_me" est présent, elle tente de valider le token associé.
 *
 * @return bool Vrai si l'utilisateur est authentifié, sinon faux.
 */
function isAuthenticated() {
    if (isset($_SESSION['user_id'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
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
 * Vérifie si l'utilisateur est authentifié.
 *
 * Si l'utilisateur n'est pas authentifié, cette fonction sauvegarde l'URI actuelle dans la session afin
 * de permettre une redirection après connexion, puis redirige l'utilisateur vers la page de connexion et interrompt
 * l'exécution du script.
 */
function requireAuthentication() {
    if (!isAuthenticated()) {
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
    // pour l'instant, verifie si le rôle ID est admin (role_id = 3)
    return $_SESSION['user_role'] == 3;
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

/**
 * Prépare la réinitialisation du mot de passe pour un utilisateur.
 *
 * Cette fonction vérifie si un utilisateur existe pour l'adresse email donnée. Si c'est le cas, elle génère
 * un token sécurisé et définit une date d'expiration d'une heure pour la réinitialisation, en mettant à jour
 * les informations correspondantes dans la base de données. L'envoi du courriel de réinitialisation reste à implémenter.
 *
 * @param string $email Adresse e-mail de l'utilisateur.
 * @return bool Retourne true si le token a été généré et stocké, sinon false.
 */
function resetPassword($email) {
    $user = fetchOne('personnes', "email = '$email'");
    
    if (!$user) {
        return false;
    }
    
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    updateRow('personnes', [
        'token' => $token,
        'expires' => $expires
    ], "id = {$user['id']}");
    
    // TODO: envoyer un email de reinitialisation de mot de passe
    
    return true;
}

/**
 * Génère et enregistre un token "remember me" pour un utilisateur.
 *
 * La fonction crée un token cryptographiquement sécurisé, l'enregistre dans la table 
 * "remember_me_tokens" avec une date d'expiration fixée à 30 jours, et retourne le token généré.
 *
 * @param int $userId Identifiant de l'utilisateur pour lequel le token est généré.
 *
 * @return string Le token "remember me" généré.
 */
function createRememberMeToken($userId) {
    $pdo = getDbConnection();
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $pdo->prepare("INSERT INTO remember_me_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $token, $expires]);
    
    return $token;
}

/**
 * Valide un token "remember me" et met à jour la session utilisateur si le token est valide.
 *
 * La fonction vérifie que le token existe dans la base de données et qu'il n'est pas expiré. En cas de validation,
 * elle récupère les informations de l'utilisateur correspondant et initialise la session avec ces données,
 * incluant l'identifiant, le nom complet, l'email, le rôle, la photo, ainsi que la mise à jour du timestamp de dernière activité.
 *
 * @param string $token Le token "remember me" à valider.
 * @return bool Retourne true si le token est valide et la session a été mise à jour, sinon false.
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
            return true;
        }
    }
    return false;
}

/**
 * Supprime un token "remember me" de la base de données.
 *
 * Cette fonction efface l'enregistrement correspondant au token fourni dans la table des tokens "remember me".
 *
 * @param string $token Le token à supprimer.
 * @return bool Renvoie true si la suppression a réussi, false sinon.
 */
function deleteRememberMeToken($token) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM remember_me_tokens WHERE token = ?");
    return $stmt->execute([$token]);
} 