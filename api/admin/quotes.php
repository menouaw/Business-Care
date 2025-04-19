<?php



require_once __DIR__ . '/init.php'; 

if (!$isAuthenticated) {
    logSecurityEvent(null, 'api_access_denied', '[FAILURE] Tentative d\'accès à /api/admin/quotes sans authentification');
    sendJsonResponse(['error' => true, 'message' => 'Authentification requise'], 401);
}

if ($currentUserRole !== ROLE_ADMIN) {
    logSecurityEvent($currentUserId, 'api_access_denied', '[FAILURE] Utilisateur ID ' . $currentUserId . ' a tenté d\'accéder à /api/admin/quotes sans le rôle admin');
    sendJsonResponse(['error' => true, 'message' => 'Accès interdit'], 403);
}


$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $quoteId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
    $companyIdFilter = isset($_GET['company_id']) ? filter_var($_GET['company_id'], FILTER_VALIDATE_INT) : null;

    if ($quoteId !== null && $quoteId > 0) {
        
        getQuoteDetails($quoteId);
    } elseif ($quoteId === null && !isset($_GET['id'])) {
        
        getQuoteList($companyIdFilter);
    } else {
        
        logActivity($currentUserId, 'api_quote_request', '[FAILURE] Format d\'ID de devis/entreprise invalide demandé : ' . ($_GET['id'] ?? $_GET['company_id'] ?? 'null'));
        sendJsonResponse(['error' => true, 'message' => 'ID de devis ou d\'entreprise invalide ou non fourni correctement.'], 400);
    }
} else {
    
    logSecurityEvent($currentUserId, 'api_method_not_allowed', '[FAILURE] Méthode ' . $method . ' tentée sur /api/admin/quotes par l\'Utilisateur ID ' . $currentUserId);
    sendJsonResponse(['error' => true, 'message' => 'Méthode non autorisée pour ce point de terminaison'], 405);
}

/**
 * Récupère et renvoie la liste des devis, potentiellement filtrée par entreprise.
 * @param int|null $companyId ID de l'entreprise pour filtrer (optionnel).
 */
function getQuoteList($companyId = null) {
    global $currentUserId;
    try {
        $whereClause = '';
        $params = [];
        if ($companyId !== null && $companyId > 0) {
            $whereClause = 'entreprise_id = ?';
            $params[] = $companyId;
        }

        
        $quotes = fetchAll(TABLE_QUOTES, $whereClause, 'date_creation DESC', 0, 0, $params);

        logActivity($currentUserId, 'api_quote_list', '[SUCCESS] Liste des devis récupérée avec succès (' . count($quotes) . ' devis)' . ($companyId ? ' pour l\'entreprise ID ' . $companyId : ''));
        sendJsonResponse(['error' => false, 'data' => $quotes], 200);

    } catch (Exception $e) {
        logSystemActivity('api_quote_list', '[ERROR] Échec de la récupération de la liste des devis : ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la récupération de la liste des devis.'], 500);
    }
}

/**
 * Récupère et renvoie les détails d'un devis spécifique, y compris les lignes de prestation.
 * @param int $id ID du devis.
 */
function getQuoteDetails($id) {
    global $currentUserId;
    try {
        
        $quote = fetchOne(TABLE_QUOTES, 'id = ?', '', [$id]);

        if ($quote) {
            
            
             
            $prestations = fetchAll(TABLE_QUOTE_PRESTATIONS, 'devis_id = ?', '', 0, 0, [$id]);
            

            
            $quote['prestations'] = $prestations;

            logActivity($currentUserId, 'api_quote_detail', '[SUCCESS] Détails récupérés avec succès pour le devis ID : ' . $id);
            sendJsonResponse(['error' => false, 'data' => $quote], 200);

        } else {
            logActivity($currentUserId, 'api_quote_detail', '[FAILURE] Devis non trouvé pour l\'ID : ' . $id);
            sendJsonResponse(['error' => true, 'message' => 'Devis non trouvé'], 404);
        }
    } catch (Exception $e) {
        logSystemActivity('api_quote_detail', '[ERROR] Échec de la récupération des détails pour le devis ID ' . $id . ' : ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la récupération des détails du devis.'], 500);
    }
}
?>
