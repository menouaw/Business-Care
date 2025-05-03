<?php
require_once __DIR__ . '/../../init.php';

/**
 * Récupère la liste des factures clients avec pagination et filtrage
 * 
 * @param int $page Numéro de la page
 * @param int $perPage Nombre d'éléments par page
 * @param string $search Terme de recherche (numéro facture, nom entreprise)
 * @param string $status Filtre par statut
 * @param string $date_from Filtre par date d'émission (début)
 * @param string $date_to Filtre par date d'émission (fin)
 * @return array Données de pagination et liste des factures clients
 */
function billingGetClientInvoicesList($page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE, $search = '', $status = '', $date_from = '', $date_to = '') {
    $params = [];
    $conditions = [];

    if ($status) {
        $conditions[] = "f.statut = ?";
        $params[] = $status;
    }

    if ($date_from) {
        $conditions[] = "f.date_emission >= ?";
        $params[] = $date_from;
    }
    if ($date_to) {
        $conditions[] = "f.date_emission <= ?";
        $params[] = $date_to;
    }

    if ($search) {
        $conditions[] = "(f.numero_facture LIKE ? OR e.nom LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    $whereSql = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : '';

    $pdo = getDbConnection();
    $countSql = "SELECT COUNT(f.id) 
                 FROM " . TABLE_INVOICES . " f 
                 LEFT JOIN " . TABLE_COMPANIES . " e ON f.entreprise_id = e.id 
                 {$whereSql}";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $perPage);
    $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT f.*, e.nom as nom_entreprise
            FROM " . TABLE_INVOICES . " f 
            LEFT JOIN " . TABLE_COMPANIES . " e ON f.entreprise_id = e.id
            {$whereSql}
            ORDER BY f.date_emission DESC, f.id DESC LIMIT ?, ?";
            
    $paramsWithPagination = array_merge($params, [$offset, $perPage]);

    $items = executeQuery($sql, $paramsWithPagination)->fetchAll();

    return [
        'items' => $items,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'perPage' => $perPage
    ];
}

/**
 * Récupère les détails d'une facture client
 * 
 * @param int $id Identifiant de la facture
 * @return array|false Données de la facture ou false si non trouvée
 */
function billingGetClientInvoiceDetails($id) {
    $sql = "SELECT f.*, e.nom as nom_entreprise, d.id as devis_associe_id
            FROM " . TABLE_INVOICES . " f 
            LEFT JOIN " . TABLE_COMPANIES . " e ON f.entreprise_id = e.id 
            LEFT JOIN " . TABLE_QUOTES . " d ON f.devis_id = d.id
            WHERE f.id = ? LIMIT 1";
    return executeQuery($sql, [$id])->fetch();
}

/**
 * Génère le prochain numéro de facture pour un type donné
 * 
 * @param string $prefix Préfixe ('F' pour client, 'FP' pour prestataire)
 * @return string Le nouveau numéro de facture
 */
function billingGetNextInvoiceNumber($prefix) {
    $pdo = getDbConnection();
    $table = ($prefix === INVOICE_PREFIX) ? TABLE_INVOICES : TABLE_PRACTITIONER_INVOICES;
    $column = 'numero_facture';
    
    $sql = "SELECT MAX($column) FROM $table WHERE $column LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$prefix . '%']);
    $maxNum = $stmt->fetchColumn();

    if ($maxNum) {
        $numberPart = (int)substr($maxNum, strlen($prefix));
        $nextNumber = $numberPart + 1;
    } else {
        $nextNumber = 1;
    }
    
    return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT); 
}

/**
 * Met à jour le statut d'une facture client
 * 
 * @param int $invoiceId ID de la facture
 * @param string $status Nouveau statut
 * @param string|null $paymentMode Mode de paiement si applicable
 * @return array Résultat ['success' => bool, 'message' => string]
 */
function billingUpdateClientInvoiceStatus($invoiceId, $status, $paymentMode = null) {
    if (!in_array($status, INVOICE_STATUSES)) {
        return ['success' => false, 'message' => "Statut de facture invalide."];
    }
    if ($paymentMode && !in_array($paymentMode, INVOICE_PAYMENT_MODES)) {
         return ['success' => false, 'message' => "Mode de paiement invalide."];
    }

    $invoice = fetchOne(TABLE_INVOICES, 'id = ?', '', [$invoiceId]);
    if (!$invoice) {
        return ['success' => false, 'message' => "Facture non trouvée."];
    }

    $updateData = ['statut' => $status];
    if ($status === INVOICE_STATUS_PAID) {
        $updateData['date_paiement'] = date('Y-m-d H:i:s'); 
        if ($paymentMode) {
            $updateData['mode_paiement'] = $paymentMode;
        } elseif (!$invoice['mode_paiement']) {
             
             
        }
    } else {
        
        
        
    }

    try {
        $affectedRows = updateRow(TABLE_INVOICES, $updateData, 'id = :where_id', [':where_id' => $invoiceId]);
        if ($affectedRows >= 0) { 
             
             logBusinessOperation($_SESSION['user_id'], 'client_invoice_status_update', "[SUCCESS] Statut facture client ID: $invoiceId mis à jour vers: $status");
             return ['success' => true, 'message' => "Statut de la facture mis à jour."];
        } else {
            throw new Exception("La mise à jour en base de données a échoué.");
        }
    } catch (Exception $e) {
         logSystemActivity('error', "[ERROR] Échec mise à jour statut facture client ID: $invoiceId - " . $e->getMessage());
        return ['success' => false, 'message' => "Erreur lors de la mise à jour du statut: " . $e->getMessage()];
    }
}

/**
 * Récupère les statuts valides pour les factures client
 * 
 * @return array Liste des statuts valides
 */
function billingGetClientInvoiceStatuses() {
    return INVOICE_STATUSES;
}

/**
 * Récupère les modes de paiement valides pour les factures client
 * 
 * @return array Liste des modes de paiement valides
 */
function billingGetClientInvoicePaymentModes() {
    return INVOICE_PAYMENT_MODES;
}

/**
 * Récupère la liste des factures prestataires avec pagination et filtrage
 * 
 * @param int $page Numéro de la page
 * @param int $perPage Nombre d'éléments par page
 * @param string $search Terme de recherche (numéro facture, nom prestataire)
 * @param string $status Filtre par statut
 * @param int $providerId Filtre par ID prestataire
 * @param string $date_from Filtre par date d'émission (début)
 * @param string $date_to Filtre par date d'émission (fin)
 * @return array Données de pagination et liste des factures prestataires
 */
function billingGetProviderInvoicesList($page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE, $search = '', $status = '', $providerId = 0, $date_from = '', $date_to = '') {
    $params = [];
    $conditions = [];

    if ($status) {
        $conditions[] = "fp.statut = ?";
        $params[] = $status;
    }
    if ($providerId > 0) {
        $conditions[] = "fp.prestataire_id = ?";
        $params[] = (int)$providerId;
    }

    if ($date_from) {
        $conditions[] = "fp.date_facture >= ?";
        $params[] = $date_from;
    }
    if ($date_to) {
        $conditions[] = "fp.date_facture <= ?";
        $params[] = $date_to;
    }

    if ($search) {
        $conditions[] = "(fp.numero_facture LIKE ? OR CONCAT(p.prenom, ' ', p.nom) LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    $whereSql = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : '';

    $pdo = getDbConnection();
    $countSql = "SELECT COUNT(fp.id) 
                 FROM " . TABLE_PRACTITIONER_INVOICES . " fp 
                 LEFT JOIN " . TABLE_USERS . " p ON fp.prestataire_id = p.id 
                 {$whereSql}";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $perPage);
    $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1)); 
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT fp.*, CONCAT(p.prenom, ' ', p.nom) as nom_prestataire
            FROM " . TABLE_PRACTITIONER_INVOICES . " fp 
            LEFT JOIN " . TABLE_USERS . " p ON fp.prestataire_id = p.id
            {$whereSql}
            ORDER BY fp.date_facture DESC, fp.id DESC LIMIT ?, ?";
            
    $paramsWithPagination = array_merge($params, [$offset, $perPage]);

    $items = executeQuery($sql, $paramsWithPagination)->fetchAll();

    return [
        'items' => $items,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'perPage' => $perPage
    ];
}

/**
 * Récupère les détails d'une facture prestataire
 * 
 * @param int $id Identifiant de la facture
 * @return array|false Données de la facture ou false si non trouvée
 */
function billingGetProviderInvoiceDetails($id) {
     $sql = "SELECT * FROM v_details_facture_prestataire WHERE facture_id = ?";
     $lines = executeQuery($sql, [$id])->fetchAll();
     
     if (empty($lines)) {
         return false;
     }

     
     $invoiceDetails = [
         'id' => $lines[0]['facture_id'],
         'numero_facture' => $lines[0]['numero_facture'],
         'date_facture' => $lines[0]['date_facture'],
         'periode_debut' => $lines[0]['periode_debut'],
         'periode_fin' => $lines[0]['periode_fin'],
         'montant_total' => $lines[0]['facture_montant_total'],
         'statut' => $lines[0]['facture_statut'],
         'date_paiement' => $lines[0]['date_paiement'],
         'prestataire_id' => $lines[0]['prestataire_id'],
         'nom_prestataire' => $lines[0]['nom_prestataire'],
         'email_prestataire' => $lines[0]['email_prestataire'],
         'lines' => []
     ];

     foreach($lines as $line) {
         if ($line['ligne_id']) { 
             $invoiceDetails['lines'][] = [
                 'ligne_id' => $line['ligne_id'],
                 'description' => $line['ligne_description'],
                 'montant' => $line['ligne_montant'],
                 'rendez_vous_id' => $line['rendez_vous_id'],
                 'date_rdv' => $line['date_rdv'],
                 'nom_prestation' => $line['nom_prestation']
             ];
         }
     }
     
     return $invoiceDetails;
}

/**
 * Recherche les rendez-vous non facturés sur une période donnée
 * 
 * @param string $startDate Date de début (Y-m-d)
 * @param string $endDate Date de fin (Y-m-d)
 * @return array Liste des rendez-vous non facturés
 */
function billingFindUnbilledAppointments($startDate, $endDate) {
    $sql = "SELECT 
                rdv.id as rdv_id, 
                rdv.praticien_id, 
                rdv.date_rdv, 
                prest.nom as prestation_nom,
                prest.prix as prestation_prix 
            FROM " . TABLE_APPOINTMENTS . " rdv
            JOIN " . TABLE_PRESTATIONS . " prest ON rdv.prestation_id = prest.id
            LEFT JOIN " . TABLE_PRACTITIONER_INVOICE_LINES . " fpl ON rdv.id = fpl.rendez_vous_id
            WHERE rdv.statut = 'termine' 
              AND rdv.praticien_id IS NOT NULL
              AND fpl.id IS NULL 
              AND DATE(rdv.date_rdv) BETWEEN ? AND ? 
            ORDER BY rdv.praticien_id, rdv.date_rdv";
            
    $appointments = executeQuery($sql, [$startDate, $endDate])->fetchAll();
    
    $groupedAppointments = [];
    foreach ($appointments as $appt) {
        $providerId = $appt['praticien_id'];
        if (!isset($groupedAppointments[$providerId])) {
            $groupedAppointments[$providerId] = [];
        }
        $groupedAppointments[$providerId][] = $appt;
    }
    
    return $groupedAppointments;
}

/**
 * Met à jour le statut d'une facture prestataire
 * 
 * @param int $invoiceId ID de la facture
 * @param string $status Nouveau statut
 * @return array Résultat ['success' => bool, 'message' => string]
 */
function billingUpdateProviderInvoiceStatus($invoiceId, $status) {
     if (!in_array($status, PRACTITIONER_INVOICE_STATUSES)) {
        return ['success' => false, 'message' => "Statut de facture prestataire invalide."];
    }

    $invoice = fetchOne(TABLE_PRACTITIONER_INVOICES, 'id = ?', '', [$invoiceId]);
    if (!$invoice) {
        return ['success' => false, 'message' => "Facture prestataire non trouvée."];
    }

    $updateData = ['statut' => $status];
    if ($status === PRACTITIONER_INVOICE_STATUS_PAID) {
        $updateData['date_paiement'] = date('Y-m-d H:i:s'); 
    } else {
        $updateData['date_paiement'] = null; 
    }

    try {
        $affectedRows = updateRow(TABLE_PRACTITIONER_INVOICES, $updateData, 'id = :where_id', [':where_id' => $invoiceId]);
        if ($affectedRows >= 0) {
             
             logBusinessOperation($_SESSION['user_id'], 'provider_invoice_status_update', "[SUCCESS] Statut facture prestataire ID: $invoiceId mis à jour vers: $status");
             return ['success' => true, 'message' => "Statut de la facture prestataire mis à jour."];
        } else {
            throw new Exception("La mise à jour en base de données a échoué.");
        }
    } catch (Exception $e) {
         logSystemActivity('error', "[ERROR] Échec mise à jour statut facture prestataire ID: $invoiceId - " . $e->getMessage());
        return ['success' => false, 'message' => "Erreur lors de la mise à jour du statut: " . $e->getMessage()];
    }
}

/**
 * Récupère les statuts valides pour les factures prestataire
 * 
 * @return array Liste des statuts valides
 */
function billingGetProviderInvoiceStatuses() {
    return PRACTITIONER_INVOICE_STATUSES;
}

/**
 * Génère un badge HTML pour le statut d'une facture
 * 
 * @param string $status Statut de la facture
 * @param string $type Type de facture ('client' ou 'provider')
 * @return string HTML du badge
 */
function billingGetInvoiceStatusBadge($status, $type = 'client') 
{
    $badgeClass = 'bg-secondary'; 
    $statusText = ucfirst(str_replace('_', ' ', $status)); 

    if ($type === 'client') {
        switch ($status) {
            case INVOICE_STATUS_PAID:
                $badgeClass = 'bg-success';
                break;
            case INVOICE_STATUS_PENDING:
                $badgeClass = 'bg-warning text-dark';
                $statusText = 'En attente';
                break;
            case INVOICE_STATUS_LATE:
                $badgeClass = 'bg-danger';
                $statusText = 'En retard';
                break;
             case INVOICE_STATUS_UNPAID:
                $badgeClass = 'bg-danger';
                $statusText = 'Impayée';
                break;
            case INVOICE_STATUS_CANCELLED:
                $badgeClass = 'bg-dark';
                 $statusText = 'Annulée';
                break;
        }
    } elseif ($type === 'provider') {
         switch ($status) {
            case PRACTITIONER_INVOICE_STATUS_PAID:
                $badgeClass = 'bg-success';
                $statusText = 'Payée';
                break;
            case PRACTITIONER_INVOICE_STATUS_UNPAID:
                $badgeClass = 'bg-warning text-dark';
                 $statusText = 'Impayée';
                break;
            case PRACTITIONER_INVOICE_STATUS_PENDING_GENERATION:
                 $badgeClass = 'bg-info text-dark';
                 $statusText = 'Génération attendue';
                 break;
        }
    }

    return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($statusText) . '</span>';
}

?>
