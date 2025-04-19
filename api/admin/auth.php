<?php



require_once __DIR__ . '/../init.php';


$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST': 
        
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            sendJsonResponse(['error' => true, 'message' => 'Email et mot de passe requis'], 400);
        }

        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $password = $data['password'];

        try {
            
            $user = fetchOne(TABLE_USERS, "email = ? AND statut = 'actif'", '', [$email]);

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                
                

                
                $apiToken = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); 

                
                insertRow(TABLE_REMEMBER_ME, [
                    'user_id' => $user['id'],
                    'token' => $apiToken,
                    'expires_at' => $expires,
                    'created_at' => date('Y-m-d H:i:s') 
                ]);

                
                updateRow(TABLE_USERS, ['derniere_connexion' => date('Y-m-d H:i:s')], "id = ?", [$user['id']]);

                
                logSecurityEvent($user['id'], 'api_login', "[SUCCESS] Utilisateur connecté avec succès via l'API (ID: " . $user['id'] . ")");

                
                unset($user['mot_de_passe']);

                sendJsonResponse([
                    'error' => false,
                    'message' => 'Authentification réussie',
                    'token' => $apiToken, 
                    'user' => $user
                ], 200);

            } else {
                $logUserId = $user ? $user['id'] : null;
                logSecurityEvent($logUserId, 'api_login', '[FAILURE] Email ou mot de passe invalide pour tentative de connexion API : ' . $email, true);
                sendJsonResponse(['error' => true, 'message' => 'Email ou mot de passe invalide'], 401);
            }
        } catch (Exception $e) {
             logSystemActivity('api_login', '[ERROR] Erreur lors de la connexion API : ' . $e->getMessage());
             
             sendJsonResponse(['error' => true, 'message' => 'Une erreur interne est survenue lors de la connexion.'], 500);
        }
        break;

    case 'DELETE': 
        
        if (!$isAuthenticated || !$bearerToken) {
             sendJsonResponse(['error' => true, 'message' => 'Authentification requise'], 401);
        }

        try {
            
            
            $deleted = deleteRow(TABLE_REMEMBER_ME, "token = ?", [$bearerToken]);

            if ($deleted > 0) {
                 
                 logSecurityEvent($currentUserId, 'api_logout', "[SUCCESS] Utilisateur déconnecté avec succès via l'API (ID: " . $currentUserId . ")");
                 sendJsonResponse(['error' => false, 'message' => 'Déconnexion réussie'], 200);
            } else {
                 
                 logSecurityEvent($currentUserId, 'api_logout', '[FAILURE] Échec de la suppression du jeton API lors de la déconnexion (ID: ' . $currentUserId . ', Jeton: ' . substr($bearerToken, 0, 10) . '...)', true);
                 sendJsonResponse(['error' => true, 'message' => 'Échec de la déconnexion, le jeton est peut-être invalide'], 400);
            }
        } catch (Exception $e) {
             logSystemActivity('api_logout', '[ERROR] Erreur lors de la déconnexion API : ' . $e->getMessage());
             
             sendJsonResponse(['error' => true, 'message' => 'Une erreur interne est survenue lors de la déconnexion.'], 500);
        }
        break;

    

    default:
        sendJsonResponse(['error' => true, 'message' => 'Méthode non autorisée'], 405);
        break;
} 