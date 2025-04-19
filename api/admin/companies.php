<?php



require_once __DIR__ . '/../init.php'; 

if (!$isAuthenticated) {
    logSecurityEvent(null, 'api_access_denied', '[FAILURE] Tentative d\'accès à /api/admin/companies sans authentification');
    sendJsonResponse(['error' => true, 'message' => 'Authentification requise'], 401);
}

if ($currentUserRole !== ROLE_ADMIN) { 
    logSecurityEvent($currentUserId, 'api_access_denied', '[FAILURE] Utilisateur ID ' . $currentUserId . ' a tenté d\'accéder à /api/admin/companies sans le rôle admin');
    sendJsonResponse(['error' => true, 'message' => 'Accès interdit'], 403);
}


$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    
    $companyId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

    if ($companyId !== null && $companyId > 0) {
        
        getCompanyDetails($companyId);
    } elseif ($companyId === null && !isset($_GET['id'])) { 
        
        getAllCompanies();
    } else {
        
         logActivity($currentUserId, 'api_company_request', '[FAILURE] Format d\'ID d\'entreprise invalide demandé : ' . ($_GET['id'] ?? 'null'));
        sendJsonResponse(['error' => true, 'message' => 'ID d\'entreprise invalide ou non fourni correctement.'], 400);
    }
} else {
    
    logSecurityEvent($currentUserId, 'api_method_not_allowed', '[FAILURE] Méthode ' . $method . ' tentée sur /api/admin/companies par l\'Utilisateur ID ' . $currentUserId);
    sendJsonResponse(['error' => true, 'message' => 'Méthode non autorisée pour ce point de terminaison'], 405);
}

/**
 * Récupère et renvoie la liste de toutes les entreprises.
 */
function getAllCompanies() {
    global $currentUserId; 
    try {
        $companies = fetchAll(TABLE_COMPANIES, '', 'nom ASC'); 

        logActivity($currentUserId, 'api_company_list', '[SUCCESS] Liste des entreprises récupérée avec succès (' . count($companies) . ' entreprises)');
        
        sendJsonResponse(['error' => false, 'data' => $companies], 200);

    } catch (Exception $e) {
        logSystemActivity('api_company_list', '[ERROR] Échec de la récupération de la liste des entreprises : ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la récupération de la liste des entreprises.'], 500);
    }
}

/**
 * Récupère et renvoie les détails d'une entreprise spécifique, y compris les IDs liés.
 * @param int $id ID de l'entreprise.
 */
function getCompanyDetails($id) {
    global $currentUserId; 
    try {
        
        $company = fetchOne(TABLE_COMPANIES, 'id = ?', '', [$id]);

        if ($company) {
            
            $contractIds = array_column(fetchAll(TABLE_CONTRACTS, 'entreprise_id = ?', 'id ASC', 0, 0, [$id]), 'id');
            $quoteIds = array_column(fetchAll(TABLE_QUOTES, 'entreprise_id = ?', 'id ASC', 0, 0, [$id]), 'id');
            $invoiceIds = array_column(fetchAll(TABLE_INVOICES, 'entreprise_id = ?', 'id ASC', 0, 0, [$id]), 'id');

            
            $company['contracts'] = $contractIds;
            $company['quotes'] = $quoteIds;
            $company['invoices'] = $invoiceIds;

            logActivity($currentUserId, 'api_company_detail', '[SUCCESS] Détails récupérés avec succès pour l\'entreprise ID : ' . $id);
            
            sendJsonResponse(['error' => false, 'data' => $company], 200);

        } else {
            logActivity($currentUserId, 'api_company_detail', '[FAILURE] Entreprise non trouvée pour l\'ID : ' . $id);
            sendJsonResponse(['error' => true, 'message' => 'Entreprise non trouvée'], 404);
        }
    } catch (Exception $e) {
        logSystemActivity('api_company_detail', '[ERROR] Échec de la récupération des détails pour l\'entreprise ID ' . $id . ' : ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la récupération des détails de l\'entreprise.'], 500);
    }
}

?> 