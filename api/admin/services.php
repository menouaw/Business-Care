<?php
require_once __DIR__ . '/init.php'; 


if (!$isAuthenticated) {
    logSecurityEvent(null, 'api_access_denied', '[FAILURE] Tentative d\'accès à /api/admin/services sans authentification');
    sendJsonResponse(['error' => true, 'message' => 'Authentification requise'], 401);
}


if ($currentUserRole !== ROLE_ADMIN) { 
    logSecurityEvent($currentUserId, 'api_access_denied', '[FAILURE] Utilisateur ID ' . $currentUserId . ' a tenté d\'accéder à /api/admin/services sans le rôle admin');
    sendJsonResponse(['error' => true, 'message' => 'Accès interdit'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $prestationId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

    if ($prestationId !== null && $prestationId > 0) {
        
        getPrestationDetails($prestationId);
    } elseif ($prestationId === null && !isset($_GET['id'])) {
        
        getAllPrestations();
    } else {
        
        logActivity($currentUserId, 'api_service_request', '[FAILURE] Format d\'ID de prestation invalide demandé : ' . ($_GET['id'] ?? 'null'));
        sendJsonResponse(['error' => true, 'message' => 'ID de prestation invalide ou non fourni correctement.'], 400);
    }
} else {
    
    logSecurityEvent($currentUserId, 'api_method_not_allowed', '[FAILURE] Méthode ' . $method . ' tentée sur /api/admin/services par l\'Utilisateur ID ' . $currentUserId);
    sendJsonResponse(['error' => true, 'message' => 'Méthode non autorisée pour ce point de terminaison'], 405);
}

/**
 * Récupère et renvoie la liste de toutes les prestations.
 */
function getAllPrestations() {
    global $currentUserId;
    try {
        
        
        $prestations = fetchAll(TABLE_PRESTATIONS, '', 'nom ASC');

        
        

        logActivity($currentUserId, 'api_service_list', '[SUCCESS] Liste des prestations récupérée avec succès (' . count($prestations) . ' prestations)');
        sendJsonResponse(['error' => false, 'data' => $prestations], 200); 

    } catch (Exception $e) {
        logSystemActivity('api_service_list', '[ERROR] Échec de la récupération de la liste des prestations : ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la récupération de la liste des prestations.'], 500);
    }
}

/**
 * Récupère et renvoie les détails d'une prestation spécifique, y compris les IDs d'évènements liés.
 * @param int $id ID de la prestation.
 */
function getPrestationDetails($id) {
    global $currentUserId;
    try {
        
        $prestation = fetchOne(TABLE_PRESTATIONS, 'id = ?', '', [$id]);

        if ($prestation) {
            
            
            
            
             
             $rendezVousEntries = fetchAll('rendez_vous', 'prestation_id = ?', '', 0, 0, [$id]);
             
             

             
             
             
             

             
             
             
             
            $associatedEventIds = []; 


            
            $prestation['associated_events'] = $associatedEventIds; 

            logActivity($currentUserId, 'api_service_detail', '[SUCCESS] Détails récupérés avec succès pour la prestation ID : ' . $id);
            sendJsonResponse(['error' => false, 'data' => $prestation], 200); 

        } else {
            logActivity($currentUserId, 'api_service_detail', '[FAILURE] Prestation non trouvée pour l\'ID : ' . $id);
            sendJsonResponse(['error' => true, 'message' => 'Prestation non trouvée'], 404);
        }
    } catch (Exception $e) {
        logSystemActivity('api_service_detail', '[ERROR] Échec de la récupération des détails pour la prestation ID ' . $id . ' : ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la récupération des détails de la prestation.'], 500);
    }
} 