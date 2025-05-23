<?php

require_once __DIR__ . '/logging.php';
require_once __DIR__ . '/functions.php';


function login($email, $password, $rememberMe = false)
{
    $user = fetchOne('personnes', "email = :email AND statut = 'actif'", [':email' => $email]);

    if ($user && password_verify($password, $user['mot_de_passe'])) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = (int)$user['role_id'];
        $_SESSION['user_entreprise'] = $user['entreprise_id'] ? (int)$user['entreprise_id'] : null;
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
            logSecurityEvent(null, 'login', "[FAILURE] Email inexistant: $email", true);
        }
        return false;
    }
}

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

function isAuthenticated()
{
    if (isset($_SESSION['user_id'])) {
        $currentTime = time();
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 1800;
        $timeDiff = $currentTime - $lastActivity;

        if ($timeDiff > $lifetime) {
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

function requireAuthentication()
{
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirectTo((defined('WEBCLIENT_URL') ? WEBCLIENT_URL : '') . '/login.php');
        exit;
    }
}

/**
 * Vérifie si l'utilisateur authentifié dispose du rôle spécifié
 *
 * @param int $requiredRole Identifiant du rôle requis
 * @return bool Retourne true si l'utilisateur est authentifié et a le rôle requis
 */
function hasRole($requiredRole)
{
    if (!isAuthenticated()) {
        return false;
    }

    return $_SESSION['user_role'] == $requiredRole;
}

function isEntrepriseUser()
{
    return hasRole(ROLE_ENTREPRISE);
}


function isSalarieUser()
{
    return hasRole(ROLE_SALARIE);
}


function isPrestataireUser()
{
    return hasRole(ROLE_PRESTATAIRE);
}


function requireRole($requiredRole)
{
    requireAuthentication();

    $userRoleInSession = $_SESSION['user_role'] ?? null;

    if ($userRoleInSession === null || (int)$userRoleInSession !== (int)$requiredRole) {
        flashMessage('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'danger');
        redirectTo((defined('WEBCLIENT_URL') ? WEBCLIENT_URL : '') . '/index.php');
        exit;
    }
}

/**
 * Récupère les informations d'un utilisateur
 * 
 * @param int|null $userId ID de l'utilisateur (utilise l'utilisateur courant si null)
 * @return array|false Informations de l'utilisateur ou false si non trouvé/authentifié
 */
function getUserInfo($userId = null)
{
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
 * met à jour les informations de l'utilisateur dans la base de données, et consigne l'évènement de la demande.
 * Si aucun utilisateur n'est associé à l'email, l'évènement est consigné comme un échec.
 *
 * @param string $email L'adresse email de l'utilisateur concerné.
 * @return bool Retourne true si la procédure a été initiée avec succès, false sinon.
 */
function resetPassword($email)
{
    $user = fetchOne('personnes', "email = :email", [':email' => $email]);

    if (!$user) {
        logSecurityEvent(null, 'password_reset', "[FAILURE] Tentative de réinitialisation pour email inexistant: $email", true);
        return false;
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $updated = updateRow('personnes', [
        'token' => $token,
        'expires' => $expires
    ], "id = :id", [':id' => $user['id']]);

    if ($updated > 0) {
        logSecurityEvent($user['id'], 'password_reset', '[SUCCESS] Demande de réinitialisation de mot de passe initiée');
        return true;
    } else {
        logSecurityEvent($user['id'], 'password_reset', '[FAILURE] Échec de la mise à jour du token de réinitialisation en BDD', true);
        return false;
    }
}



/**
 * Crée et stocke un jeton "Se souvenir de moi" unique pour l'utilisateur.
 *
 * Ce jeton, valable pendant 30 jours, permet une reconnexion automatique en l'absence de session active.
 * Le jeton est enregistré dans la base de données et un évènement de sécurité est consigné.
 *
 * @param int $userId L'identifiant de l'utilisateur pour lequel générer le jeton.
 * @return string Le jeton généré.
 */
function createRememberMeToken($userId)
{
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
            $_SESSION['user_entreprise'] = $user['entreprise_id'];
            $_SESSION['user_photo'] = $user['photo_url'];
            $_SESSION['last_activity'] = time();


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
function deleteRememberMeToken($token)
{
    $tokenInfo = fetchOne('remember_me_tokens', "token = :token", [':token' => $token]);
    $userId = $tokenInfo ? $tokenInfo['user_id'] : null;

    $rowsAffected = deleteRow('remember_me_tokens', "token = :token", [':token' => $token]);

    if ($rowsAffected > 0) {
        logSecurityEvent($userId, 'remember_token', '[SUCCESS] Suppression du jeton "Se souvenir de moi"');
        return true;
    } else {
        logSecurityEvent($userId, 'remember_token', '[FAILURE] Échec de suppression du jeton "Se souvenir de moi" (token introuvable ou erreur)', true);
        return false;
    }
}

function userHasPermission($permission)
{
    if (!isAuthenticated()) {
        return false; 
    }

    $role_id = $_SESSION['user_role'] ?? null;

    switch ($permission) {
        case 'employee_dashboard':
        case 'employee_services':
        case 'employee_appointments':
        case 'employee_profile':
        case 'employee_settings':
        case 'employee_history':
        case 'employee_events':
        case 'employee_communities':
        case 'employee_donations':
        case 'employee_signalement':
        case 'employee_counsel':
        case 'employee_chatbot':
            return ($role_id == ROLE_SALARIE);

        case 'company_dashboard':
        case 'company_employees':
        case 'company_contracts':
        case 'company_settings':
            return ($role_id == ROLE_ENTREPRISE);

        case 'provider_dashboard':
        case 'provider_appointments':
        case 'provider_services':
        case 'provider_settings':
            return ($role_id == ROLE_PRESTATAIRE);

        case 'view_notifications':
            return true;

        default:
            logSecurityEvent($_SESSION['user_id'] ?? null, 'permission_check_unknown', "[WARNING] Unknown permission checked: $permission");
            return false;
    }
}
