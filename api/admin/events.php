<?php



require_once __DIR__ . '/init.php'; 

if (!$isAuthenticated) {
    logSecurityEvent(null, 'api_access_denied', '[FAILURE] Tentative d\'accès à /api/admin/events sans authentification');
    sendJsonResponse(['error' => true, 'message' => 'Authentification requise'], 401);
}

if ($currentUserRole !== ROLE_ADMIN) {
    logSecurityEvent($currentUserId, 'api_access_denied', '[FAILURE] Utilisateur ID ' . $currentUserId . ' a tenté d\'accéder à /api/admin/events sans le rôle admin');
    sendJsonResponse(['error' => true, 'message' => 'Accès interdit'], 403);
}


$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $eventId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

    if ($eventId !== null && $eventId > 0) {
        
        getEventDetails($eventId);
    } elseif ($eventId === null && !isset($_GET['id'])) {
        
        getAllEvents();
    } else {
        
        logActivity($currentUserId, 'api_event_request', '[FAILURE] Format d\'ID d\'événement invalide demandé : ' . ($_GET['id'] ?? 'null'));
        sendJsonResponse(['error' => true, 'message' => 'ID d\'événement invalide ou non fourni correctement.'], 400);
    }
} else {
    
    logSecurityEvent($currentUserId, 'api_method_not_allowed', '[FAILURE] Méthode ' . $method . ' tentée sur /api/admin/events par l\'Utilisateur ID ' . $currentUserId);
    sendJsonResponse(['error' => true, 'message' => 'Méthode non autorisée pour ce point de terminaison'], 405);
}

/**
 * Récupère et renvoie la liste de tous les événements.
 */
function getAllEvents() {
    global $currentUserId;
    try {
        
        $events = fetchAll(TABLE_EVENEMENTS, '', 'date_debut DESC');

        
        
        
        

        logActivity($currentUserId, 'api_event_list', '[SUCCESS] Liste des événements récupérée avec succès (' . count($events) . ' événements)');
        sendJsonResponse(['error' => false, 'data' => $events], 200); 

    } catch (Exception $e) {
        logSystemActivity('api_event_list', '[ERROR] Échec de la récupération de la liste des événements : ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la récupération de la liste des événements.'], 500);
    }
}

/**
 * Récupère et renvoie les détails d'un événement spécifique, y compris les services associés et les inscriptions.
 * @param int $id ID de l'événement.
 */
function getEventDetails($id) {
    global $currentUserId;
    try {
        
        
        

        
        $event = fetchOne(TABLE_EVENEMENTS, 'id = ?', '', [$id]);

        if ($event) {
            
            
            
            
            
            $associatedServiceIds = []; 

            
            $inscriptions = fetchAll(TABLE_EVENEMENT_INSCRIPTIONS, 'evenement_id = ?', '', 0, 0, [$id]);
             
            $inscriptionDetails = [];
            foreach($inscriptions as $inscription) {
                 
                 $personInfo = fetchOne(TABLE_USERS, 'id = ?', '', [$inscription['personne_id']]);
                 $inscriptionDetails[] = [
                      'personne_id' => $inscription['personne_id'],
                      'statut' => $inscription['statut'],
                      'nom_personne' => $personInfo ? ($personInfo['prenom'] . ' ' . $personInfo['nom']) : 'Utilisateur inconnu', 
                      'email_personne' => $personInfo ? $personInfo['email'] : null 
                 ];
            }


            
            $event['associated_services'] = $associatedServiceIds; 
            $event['inscriptions'] = $inscriptionDetails; 

            logActivity($currentUserId, 'api_event_detail', '[SUCCESS] Détails récupérés avec succès pour l\'événement ID : ' . $id);
            sendJsonResponse(['error' => false, 'data' => $event], 200); 

        } else {
            logActivity($currentUserId, 'api_event_detail', '[FAILURE] Événement non trouvé pour l\'ID : ' . $id);
            sendJsonResponse(['error' => true, 'message' => 'Événement non trouvé'], 404);
        }
    } catch (Exception $e) {
        logSystemActivity('api_event_detail', '[ERROR] Échec de la récupération des détails pour l\'événement ID ' . $id . ' : ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la récupération des détails de l\'événement.'], 500);
    }
}
?>
