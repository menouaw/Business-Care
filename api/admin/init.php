<?php
require_once __DIR__ . '/../../shared/web-admin/config.php';
require_once __DIR__ . '/../../shared/web-admin/db.php';
require_once __DIR__ . '/../../shared/web-admin/functions.php';
require_once __DIR__ . '/../../shared/web-admin/auth.php'; 
require_once __DIR__ . '/../../shared/web-admin/logging.php'; 

$isAuthenticated = false;
$currentUserId = null;
$currentUserRole = null;
$bearerToken = null;

/**
 * Envoie une réponse JSON avec le code de statut approprié et termine l'exécution.
 *
 * @param mixed $data Données à encoder en JSON.
 * @param int $statusCode Code de statut HTTP.
 * @return void
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}


$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

if ($authHeader && preg_match('/^Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $bearerToken = $matches[1];

    
    try {
        
        $tokenRecord = fetchOne(TABLE_REMEMBER_ME, "token = ? AND expires_at > NOW()", '', [$bearerToken]);

        if ($tokenRecord) {
            
            
            $user = fetchOne(TABLE_USERS, "id = ? AND statut = 'actif'", '', [$tokenRecord['user_id']]);

            if ($user) {
                $isAuthenticated = true;
                $currentUserId = $user['id'];
                $currentUserRole = $user['role_id'];
                
                
            } else {
                
                logSecurityEvent($tokenRecord['user_id'], 'api_token_validation', '[FAILURE] Jeton API valide mais utilisateur non trouvé ou inactif (ID: ' . $tokenRecord['user_id'] . ')', true);
            }
        } else {
            
             logSecurityEvent(null, 'api_token_validation', '[FAILURE] Jeton API invalide ou expiré fourni : ' . substr($bearerToken, 0, 10) . '...', true);
        }
    } catch (Exception $e) {
        logSystemActivity('api_token_validation', '[ERROR] Erreur durant la validation du jeton : ' . $e->getMessage());
        
    }
}
?>
