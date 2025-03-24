<?php
// API pour l'authentification

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

function createRememberMeToken($userId) {
    $pdo = getDbConnection();
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $pdo->prepare("INSERT INTO remember_me_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $token, $expires]);
    
    return $token;
}

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

function deleteRememberMeToken($token) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM remember_me_tokens WHERE token = ?");
    return $stmt->execute([$token]);
} 