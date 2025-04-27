<?php

require_once __DIR__ . '/init.php';


$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    
    handleAdminLogin();
} elseif ($method === 'DELETE') {
    
    
    if (!$isAuthenticated) { 
        sendJsonResponse(['error' => true, 'message' => 'Authentification requise pour la déconnexion'], 401);
    }
    handleAdminLogout($token); 
} else {
    sendJsonResponse(['error' => true, 'message' => 'Méthode non autorisée'], 405);
}

/**
 * Gère la tentative de connexion d'un administrateur.
 */
function handleAdminLogin() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['email']) || empty($input['password'])) {
        logSecurityEvent(null, 'api_admin_login', '[FAILURE] Email ou mot de passe manquant dans la requête de login API', true);
        sendJsonResponse(['error' => true, 'message' => 'Email et mot de passe requis'], 400);
        return;
    }

    $email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
    $password = $input['password'];

    try {
        $pdo = getDbConnection();
        
        $sqlUser = "SELECT id, nom, prenom, email, mot_de_passe, role_id, statut FROM personnes WHERE email = :email LIMIT 1";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([':email' => $email]);
        $user = $stmtUser->fetch();

        
        if ($user && password_verify($password, $user['mot_de_passe']) && $user['statut'] === 'actif' && (int)$user['role_id'] === ROLE_ADMIN) {
            
            
            
            $apiToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+8 hours')); 

            
            $sqlInsertToken = "INSERT INTO api_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
            $stmtInsertToken = $pdo->prepare($sqlInsertToken);
            $stmtInsertToken->execute([
                ':user_id' => $user['id'],
                ':token' => $apiToken,
                ':expires_at' => $expiresAt
            ]);

            logSecurityEvent($user['id'], 'api_admin_login', '[SUCCESS] Connexion API réussie');

            
            $userDataResponse = [
                'id' => (int)$user['id'],
                'nom' => $user['nom'],
                'prenom' => $user['prenom'],
                'email' => $user['email'],
                'role_id' => (int)$user['role_id']
            ];

            sendJsonResponse(['error' => false, 'message' => 'Authentification réussie', 'token' => $apiToken, 'user' => $userDataResponse], 200);

        } else {
            
            $reason = 'inconnu';
            if (!$user) {
                $reason = 'Email non trouvé';
            } elseif (!password_verify($password, $user['mot_de_passe'])) {
                $reason = 'Mot de passe incorrect';
            } elseif ($user['statut'] !== 'actif') {
                $reason = 'Compte inactif';
            } elseif ((int)$user['role_id'] !== ROLE_ADMIN) {
                 $reason = 'Rôle non administrateur';
            }
             logSecurityEvent(($user ? $user['id'] : null), 'api_admin_login', '[FAILURE] Échec de connexion API. Raison: ' . $reason, true);
            sendJsonResponse(['error' => true, 'message' => 'Identifiants invalides ou accès non autorisé.'], 401);
        }

    } catch (PDOException $e) {
        logSystemActivity('api_admin_login', '[ERROR] PDOException lors de la connexion API: ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur serveur lors de la tentative de connexion.'], 500);
    }
}

/**
 * Gère la déconnexion d'un administrateur via API (invalidation du token).
 * @param string|null $token Le token à invalider (extrait de l'en-tête)
 */
function handleAdminLogout($token) {
     global $adminUserId; 

    if (empty($token)) {
         sendJsonResponse(['error' => true, 'message' => 'Token non fourni pour la déconnexion'], 400);
         return;
    }

    try {
        $pdo = getDbConnection();
        
        $sqlDelete = "DELETE FROM api_tokens WHERE token = :token";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->execute([':token' => $token]);

        if ($stmtDelete->rowCount() > 0) {
             logSecurityEvent($adminUserId, 'api_admin_logout', '[SUCCESS] Déconnexion API réussie (token invalidé)');
            sendJsonResponse(['error' => false, 'message' => 'Déconnexion réussie'], 200);
        } else {
            
            logSecurityEvent($adminUserId, 'api_admin_logout', '[FAILURE] Tentative de déconnexion API avec un token déjà invalide ou inconnu');
            sendJsonResponse(['error' => true, 'message' => 'Échec de la déconnexion, token invalide ou déjà supprimé.'], 400);
        }

    } catch (PDOException $e) {
        logSystemActivity('api_admin_logout', '[ERROR] PDOException lors de la déconnexion API: ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur serveur lors de la tentative de déconnexion.'], 500);
    }
}

?> 