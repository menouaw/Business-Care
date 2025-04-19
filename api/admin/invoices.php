<?php



require_once __DIR__ . '/../init.php'; 

if (!$isAuthenticated) {
    logSecurityEvent(null, 'api_access_denied', '[FAILURE] Tentative d\'accès à /api/admin/invoices sans authentification');
    sendJsonResponse(['error' => true, 'message' => 'Authentification requise'], 401);
}

if ($currentUserRole !== ROLE_ADMIN) {
    logSecurityEvent($currentUserId, 'api_access_denied', '[FAILURE] Utilisateur ID ' . $currentUserId . ' a tenté d\'accéder à /api/admin/invoices sans le rôle admin');
    sendJsonResponse(['error' => true, 'message' => 'Accès interdit'], 403);
}


$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $invoiceId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
    $companyIdFilter = isset($_GET['company_id']) ? filter_var($_GET['company_id'], FILTER_VALIDATE_INT) : null;

    if ($invoiceId !== null && $invoiceId > 0) {
        
        getInvoiceDetails($invoiceId);
    } elseif ($invoiceId === null && !isset($_GET['id'])) {
        
        getInvoiceList($companyIdFilter);
    } else {
        
        logActivity($currentUserId, 'api_invoice_request', '[FAILURE] Format d\'ID de facture/entreprise invalide demandé : ' . ($_GET['id'] ?? $_GET['company_id'] ?? 'null'));
        sendJsonResponse(['error' => true, 'message' => 'ID de facture ou d\'entreprise invalide ou non fourni correctement.'], 400);
    }
} else {
    
    logSecurityEvent($currentUserId, 'api_method_not_allowed', '[FAILURE] Méthode ' . $method . ' tentée sur /api/admin/invoices par l\'Utilisateur ID ' . $currentUserId);
    sendJsonResponse(['error' => true, 'message' => 'Méthode non autorisée pour ce point de terminaison'], 405);
}

/**
 * Récupère et renvoie la liste des factures, potentiellement filtrée par entreprise.
 * @param int|null $companyId ID de l'entreprise pour filtrer (optionnel).
 */
function getInvoiceList($companyId = null) {
    global $currentUserId;
    try {
        $whereClause = '';
        $params = [];
        if ($companyId !== null && $companyId > 0) {
            $whereClause = 'entreprise_id = ?';
            $params[] = $companyId;
        }

        
        $invoices = fetchAll(TABLE_INVOICES, $whereClause, 'date_emission DESC', 0, 0, $params);

        logActivity($currentUserId, 'api_invoice_list', '[SUCCESS] Liste des factures récupérée avec succès (' . count($invoices) . ' factures)' . ($companyId ? ' pour l\'entreprise ID ' . $companyId : ''));
        sendJsonResponse(['error' => false, 'data' => $invoices], 200);

    } catch (Exception $e) {
        logSystemActivity('api_invoice_list', '[ERROR] Échec de la récupération de la liste des factures : ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la récupération de la liste des factures.'], 500);
    }
}

/**
 * Récupère et renvoie les détails d'une facture spécifique, y compris les lignes de prestation du devis lié.
 * @param int $id ID de la facture.
 */
function getInvoiceDetails($id) {
    global $currentUserId;
    try {
        
        $invoice = fetchOne(TABLE_INVOICES, 'id = ?', '', [$id]);

        if ($invoice) {
            $lineItems = [];
            
            if (!empty($invoice['devis_id'])) {
                
                
                $lineItems = fetchAll(TABLE_QUOTE_PRESTATIONS, 'devis_id = ?', '', 0, 0, [$invoice['devis_id']]);
                 
            }

            
            $invoice['line_items'] = $lineItems;

            logActivity($currentUserId, 'api_invoice_detail', '[SUCCESS] Détails récupérés avec succès pour la facture ID : ' . $id);
            sendJsonResponse(['error' => false, 'data' => $invoice], 200);

        } else {
            logActivity($currentUserId, 'api_invoice_detail', '[FAILURE] Facture non trouvée pour l\'ID : ' . $id);
            sendJsonResponse(['error' => true, 'message' => 'Facture non trouvée'], 404);
        }
    } catch (Exception $e) {
        logSystemActivity('api_invoice_detail', '[ERROR] Échec de la récupération des détails pour la facture ID ' . $id . ' : ' . $e->getMessage());
        sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la récupération des détails de la facture.'], 500);
    }
}
?>
