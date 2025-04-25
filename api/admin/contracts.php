<?php

require_once __DIR__ . '/init.php'; 

if (!$isAuthenticated) {
    logSecurityEvent(null, 'api_access_denied', '[FAILURE] Tentative d\'accès à /api/admin/contracts sans authentification');
    sendJsonResponse(['error' => true, 'message' => 'Authentification requise'], 401);
}


if ($currentUserRole !== ROLE_ADMIN) { 
    logSecurityEvent($currentUserId, 'api_access_denied', '[FAILURE] Utilisateur ID ' . $currentUserId . ' a tenté d\'accéder à /api/admin/contracts sans le rôle admin');
    sendJsonResponse(['error' => true, 'message' => 'Accès interdit'], 403);
}


$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    
    $contractId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
    $companyIdFilter = isset($_GET['company_id']) ? filter_var($_GET['company_id'], FILTER_VALIDATE_INT) : null;

    if ($contractId !== null && $contractId > 0) {
        
        getContractDetails($contractId);
    } elseif ($contractId === null && !isset($_GET['id'])) {
        
        getContractList($companyIdFilter);
    } else {
        
        logActivity($currentUserId, 'api_contract_request', '[FAILURE] Format d\'ID de contrat/entreprise invalide demandé : ' . ($_GET['id'] ?? $_GET['company_id'] ?? 'null'));
        sendJsonResponse(['error' => true, 'message' => 'ID de contrat ou d\'entreprise invalide ou non fourni correctement.'], 400);
    }
} else {
    
    logSecurityEvent($currentUserId, 'api_method_not_allowed', '[FAILURE] Méthode ' . $method . ' tentée sur /api/admin/contracts par l\'Utilisateur ID ' . $currentUserId);
    sendJsonResponse(['error' => true, 'message' => 'Méthode non autorisée pour ce point de terminaison'], 405);
}

/**
 * Récupère et renvoie la liste des contrats, potentiellement filtrée par entreprise.
 * @param int|null $companyId ID de l'entreprise pour filtrer (optionnel).
 */
function getContractList($companyId = null) {
    global $currentUserId;
    try {
        $whereClause = '';
        $params = [];
        if ($companyId !== null && $companyId > 0) {
            $whereClause = 'c.entreprise_id = ?';
            $params[] = $companyId;
        }

        
        $sql = "SELECT c.*, e.nom as nom_entreprise, s.type as nom_service
                FROM " . TABLE_CONTRACTS . " c
                LEFT JOIN " . TABLE_COMPANIES . " e ON c.entreprise_id = e.id
                LEFT JOIN " . TABLE_SERVICES . " s ON c.service_id = s.id"; 

        if ($whereClause) {
            $sql .= " WHERE " . $whereClause;
        }
        $sql .= " ORDER BY c.date_debut DESC";

        
        $stmt = executeQuery($sql, $params); 
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC); 

        logActivity($currentUserId, 'api_contract_list', '[SUCCESS] Liste des contrats récupérée avec succès (' . count($contracts) . ' contrats)' . ($companyId ? ' pour l\'entreprise ID ' . $companyId : ''));
        sendJsonResponse(['error' => false, 'data' => $contracts], 200);

    } catch (Exception $e) {
        logSystemActivity('api_contract_list', '[ERROR] Échec de la récupération de la liste des contrats : ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la récupération de la liste des contrats.'], 500);
    }
}

/**
 * Récupère et renvoie les détails d'un contrat spécifique, y compris les détails du service lié.
 * @param int $id ID du contrat.
 */
function getContractDetails($id) {
    global $currentUserId;
    try {
        
        $sql = "SELECT c.*, e.nom as nom_entreprise, s.type as nom_service, s.* as service_all_details
                FROM " . TABLE_CONTRACTS . " c
                LEFT JOIN " . TABLE_COMPANIES . " e ON c.entreprise_id = e.id
                LEFT JOIN " . TABLE_SERVICES . " s ON c.service_id = s.id
                WHERE c.id = ?";

        $contract = fetchOne(null, null, null, [$id], $sql); 

        if ($contract) {
            
            $serviceDetails = [];
            $prefix = 'service_all_details_'; 
            foreach ($contract as $key => $value) {
                 
                 if (in_array($key, ['service_id', 'type', 'description', 'actif', 'ordre', 'max_effectif_inferieur_egal', 'activites_incluses', 'rdv_medicaux_inclus', 'chatbot_questions_limite', 'conseils_hebdo_personnalises', 'tarif_annuel_par_salarie']) && isset($contract['service_id']) && $key !== 'service_id') {
                     $serviceDetails[$key] = $value;
                 }
                 
                 if (strpos($key, $prefix) === 0) {
                    $originalKey = substr($key, strlen($prefix));
                    if ($value !== null) $serviceDetails[$originalKey] = $value;
                    unset($contract[$key]);
                 }
            }
             
            if (isset($contract['service_id'])) {
                 $serviceDetails['id'] = $contract['service_id'];
            }

            $contract['service_details'] = !empty($serviceDetails) ? $serviceDetails : null;
             
            unset($contract['service_all_details_id']); 

            logActivity($currentUserId, 'api_contract_detail', '[SUCCESS] Détails récupérés avec succès pour le contrat ID : ' . $id);
            sendJsonResponse(['error' => false, 'data' => $contract], 200);

        } else {
            logActivity($currentUserId, 'api_contract_detail', '[FAILURE] Contrat non trouvé pour l\'ID : ' . $id);
            sendJsonResponse(['error' => true, 'message' => 'Contrat non trouvé'], 404);
        }
    } catch (Exception $e) {
        logSystemActivity('api_contract_detail', '[ERROR] Échec de la récupération des détails pour le contrat ID ' . $id . ' : ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la récupération des détails du contrat.'], 500);
    }
} 