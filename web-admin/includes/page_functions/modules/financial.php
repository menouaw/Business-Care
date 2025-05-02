<?php
require_once __DIR__ . '/../../init.php';

/**
 * Récupère les métriques financières clés pour le tableau de bord.
 *
 * @return array Tableau contenant les métriques : total_pending_revenue, recent_payments_amount, overdue_amount, pending_payouts_amount.
 */
function financialGetDashboardSummary() {
    $pdo = getDbConnection();
    $summary = [
        'total_pending_revenue' => 0,
        'recent_payments_amount' => 0,
        'overdue_amount' => 0,
        'pending_payouts_amount' => 0
    ];

    
    $sqlPending = "SELECT SUM(montant_total) FROM " . TABLE_INVOICES . " WHERE statut IN (?, ?, ?)";
    $pendingRevenue = executeQuery($sqlPending, [INVOICE_STATUS_PENDING, INVOICE_STATUS_LATE, INVOICE_STATUS_UNPAID])->fetchColumn();
    $summary['total_pending_revenue'] = $pendingRevenue ?: 0;

    
    $recentDateThreshold = date('Y-m-d H:i:s', strtotime('-' . FINANCIAL_RECENT_PAYMENT_DAYS . ' days'));
    $sqlRecent = "SELECT SUM(montant_total) FROM " . TABLE_INVOICES . " WHERE statut = ? AND date_paiement >= ?";
    $recentPayments = executeQuery($sqlRecent, [INVOICE_STATUS_PAID, $recentDateThreshold])->fetchColumn();
    $summary['recent_payments_amount'] = $recentPayments ?: 0;

    
    $sqlOverdue = "SELECT SUM(montant_total) FROM " . TABLE_INVOICES . " WHERE statut IN (?, ?) AND date_echeance < CURDATE()";
    $overdueAmount = executeQuery($sqlOverdue, [INVOICE_STATUS_LATE, INVOICE_STATUS_UNPAID])->fetchColumn();
    $summary['overdue_amount'] = $overdueAmount ?: 0;

    
    $sqlProviderPending = "SELECT SUM(montant_total) FROM " . TABLE_PRACTITIONER_INVOICES . " WHERE statut = ?";
    $pendingPayouts = executeQuery($sqlProviderPending, [PRACTITIONER_INVOICE_STATUS_UNPAID])->fetchColumn();
    $summary['pending_payouts_amount'] = $pendingPayouts ?: 0;

    return $summary;
}


/**
 * Génère les données pour un rapport de revenus détaillé avec filtres et tri.
 *
 * @param string $startDate Date de début (Y-m-d) de paiement.
 * @param string $endDate Date de fin (Y-m-d) de paiement.
 * @param array $filters Filtres optionnels (ex: ['company_id' => 1, 'service_id' => 2]).
 * @param string $sortBy Champ pour le tri (ex: 'f.date_paiement').
 * @param string $sortOrder Ordre de tri ('ASC' ou 'DESC').
 * @return array Liste des factures payées sur la période.
 */
function financialGetDetailedRevenueReport($startDate, $endDate, $filters = [], $sortBy = 'f.date_paiement', $sortOrder = 'DESC') {
    $params = [INVOICE_STATUS_PAID];
    $whereClauses = ["f.statut = ?"];
    $joins = " LEFT JOIN " . TABLE_COMPANIES . " e ON f.entreprise_id = e.id";
    $joins .= " LEFT JOIN " . TABLE_QUOTES . " d ON f.devis_id = d.id";
    $joins .= " LEFT JOIN " . TABLE_SERVICES . " s ON d.service_id = s.id";
    
    $selectFields = "f.*, e.nom as nom_entreprise, s.type as nom_service"; 

    
    if ($startDate && $endDate) {
        $whereClauses[] = "DATE(f.date_paiement) BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    }

    if (!empty($filters['company_id']) && is_numeric($filters['company_id'])) {
        $whereClauses[] = "f.entreprise_id = ?";
        $params[] = $filters['company_id'];
    }

    
    
    if (!empty($filters['service_id']) && is_numeric($filters['service_id']) && strpos($joins, TABLE_QUOTES) === false) {
        $joins .= " LEFT JOIN " . TABLE_QUOTES . " d ON f.devis_id = d.id";
        $whereClauses[] = "d.service_id = ?";
        $params[] = $filters['service_id'];
    }

    
    $allowedSortBy = ['f.numero_facture', 'e.nom', 's.type', 'f.date_paiement', 'f.montant_ht', 'f.montant_total'];
    if (!in_array($sortBy, $allowedSortBy)) {
        $sortBy = 'f.date_paiement'; 
    }
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

    $sql = "SELECT $selectFields
            FROM " . TABLE_INVOICES . " f
            $joins
            WHERE " . implode(' AND ', $whereClauses) .
           " ORDER BY $sortBy $sortOrder";

    return executeQuery($sql, $params)->fetchAll();
}

/**
 * Calcule le résumé de la TVA collectée sur une période, avec filtres optionnels.
 *
 * @param string $startDate Date de début (Y-m-d) de paiement.
 * @param string $endDate Date de fin (Y-m-d) de paiement.
 * @param array $filters Filtres optionnels (ex: ['company_id' => 1]).
 * @return array Tableau contenant 'total_ht', 'total_tva', 'total_ttc'.
 */
function financialGetTaxSummaryReport($startDate, $endDate, $filters = []) {
    $params = [INVOICE_STATUS_PAID];
    $whereClauses = ["f.statut = ?"];
    $joins = "";

    
    if ($startDate && $endDate) {
        $whereClauses[] = "DATE(f.date_paiement) BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    }

    if (!empty($filters['company_id']) && is_numeric($filters['company_id'])) {
        $whereClauses[] = "f.entreprise_id = ?";
        $params[] = $filters['company_id'];
    }
    
    if (!empty($filters['service_id']) && is_numeric($filters['service_id']) && strpos($joins, TABLE_QUOTES) === false) {
        $joins .= " LEFT JOIN " . TABLE_QUOTES . " d ON f.devis_id = d.id";
        $whereClauses[] = "d.service_id = ?";
        $params[] = $filters['service_id'];
    }

    $sql = "SELECT SUM(f.montant_ht) as total_ht, SUM(f.montant_total - f.montant_ht) as total_tva, SUM(f.montant_total) as total_ttc
            FROM " . TABLE_INVOICES . " f
            $joins
            WHERE " . implode(' AND ', $whereClauses);

    $summary = executeQuery($sql, $params)->fetch();

    return [
        'total_ht' => (float)($summary['total_ht'] ?? 0),
        'total_tva' => (float)($summary['total_tva'] ?? 0),
        'total_ttc' => (float)($summary['total_ttc'] ?? 0)
    ];
}

/**
 * Génère un contenu CSV pour le rapport de revenus détaillé.
 *
 * @param string $startDate Date de début.
 * @param string $endDate Date de fin.
 * @param array $filters Filtres.
 * @return string Contenu CSV.
 */
function financialGenerateRevenueReportCSV($startDate, $endDate, $filters = []) {
    $data = financialGetDetailedRevenueReport($startDate, $endDate, $filters);

    if (empty($data)) {
        return '';
    }

    ob_start();
    $df = fopen("php://output", 'w');
    fputcsv($df, ['Num Facture', 'Entreprise', 'Date Paiement', 'Montant HT', 'Montant TVA', 'Montant TTC'], ';');

    foreach ($data as $row) {
        fputcsv($df, [
            $row['numero_facture'],
            $row['nom_entreprise'] ?? 'N/A',
            formatDate($row['date_paiement'], 'd/m/Y H:i'),
            number_format($row['montant_ht'], 2, '.', ''), 
            number_format($row['montant_total'] - $row['montant_ht'], 2, '.', ''),
            number_format($row['montant_total'], 2, '.', '')
        ], ';');
    }
    fclose($df);
    $csvContent = ob_get_clean();
    return $csvContent;
}


/**
 * Récupère la liste détaillée des factures prestataires avec filtres et pagination.
 * Le tri est fixé par date de facture décroissante.
 *
 * @param string|null $startDate Date de début (Y-m-d) de facture prestataire (optionnel).
 * @param string|null $endDate Date de fin (Y-m-d) de facture prestataire (optionnel).
 * @param array $filters Filtres optionnels (ex: ['prestataire_id' => 1, 'statut' => 'impayee']).
 * @param int $page Numéro de la page courante.
 * @param int $perPage Nombre d'éléments par page.
 * @return array Tableau contenant les données paginées et les informations de pagination.
 */
function financialGetDetailedProviderPayouts($startDate = null, $endDate = null, $filters = [], $page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE) {
    $params = [];
    $whereClauses = ["1=1"]; 
    $joins = " LEFT JOIN " . TABLE_USERS . " p ON fp.prestataire_id = p.id";
    $selectFields = "fp.*, CONCAT(p.prenom, ' ', p.nom) as nom_prestataire";

    if ($startDate && $endDate) {
        $whereClauses[] = "DATE(fp.date_facture) BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    } elseif ($startDate) {
        $whereClauses[] = "DATE(fp.date_facture) >= ?";
        $params[] = $startDate;
    } elseif ($endDate) {
        $whereClauses[] = "DATE(fp.date_facture) <= ?";
        $params[] = $endDate;
    }

    if (!empty($filters['prestataire_id']) && is_numeric($filters['prestataire_id'])) {
        $whereClauses[] = "fp.prestataire_id = ?";
        $params[] = (int)$filters['prestataire_id'];
    }
    if (!empty($filters['statut']) && in_array($filters['statut'], PRACTITIONER_INVOICE_STATUSES)) {
        $whereClauses[] = "fp.statut = ?";
        $params[] = $filters['statut'];
    }

    $whereSql = implode(' AND ', $whereClauses);

    
    $countSql = "SELECT COUNT(fp.id) 
                 FROM " . TABLE_PRACTITIONER_INVOICES . " fp
                 $joins
                 WHERE " . $whereSql;
    $totalItems = executeQuery($countSql, $params)->fetchColumn();

    
    $limitClause = "";
    $limitParams = [];
    $finalPerPage = $perPage; 

    if (isset($perPage) && is_numeric($perPage) && $perPage > 0) {
        $perPage = (int)$perPage; 
        $totalPages = $totalItems > 0 ? ceil($totalItems / $perPage) : 1;
        $page = max(1, min((int)$page, $totalPages)); 
        $offset = ($page - 1) * $perPage;
        $limitClause = " LIMIT ? OFFSET ?";
        $limitParams[] = $perPage;
        $limitParams[] = $offset;
    } else {
        
        $page = 1;
        $totalPages = 1;
        $offset = 0; 
        
    }

    
    $defaultSortBy = 'fp.date_facture';
    $defaultSortOrder = 'DESC';

    
    $sql = "SELECT $selectFields
            FROM " . TABLE_PRACTITIONER_INVOICES . " fp
            $joins
            WHERE " . $whereSql .
           " ORDER BY $defaultSortBy $defaultSortOrder" . $limitClause; 

    
    $finalParams = array_merge($params, $limitParams); 

    $items = executeQuery($sql, $finalParams)->fetchAll();

    return [
        'items' => $items,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'perPage' => ($finalPerPage > 0) ? (int)$finalPerPage : null 
    ];
}

/**
 * Génère un contenu CSV pour le rapport des paiements prestataires.
 *
 * @param string $startDate Date de début.
 * @param string $endDate Date de fin.
 * @param array $filters Filtres.
 * @return string Contenu CSV.
 */
function financialGenerateProviderPayoutsCSV($startDate, $endDate, $filters = []) {
    
    $data = financialGetDetailedProviderPayouts($startDate, $endDate, $filters, 1, -1); 
    if ($data['totalItems'] > 0) {
         $data = financialGetDetailedProviderPayouts($startDate, $endDate, $filters, 1, $data['totalItems']); 
    } else {
         $data['items'] = []; 
    }
    

    if (empty($data['items'])) {
        return '';
    }

    ob_start();
    $df = fopen("php://output", 'w');
    fputcsv($df, ['Num Facture', 'Prestataire', 'Date Facture', 'Période Début', 'Période Fin', 'Montant Total', 'Statut', 'Date Paiement'], ';');

    foreach ($data['items'] as $row) {
        fputcsv($df, [
            $row['numero_facture'],
            $row['nom_prestataire'] ?? 'N/A',
            formatDate($row['date_facture'], 'd/m/Y'),
            formatDate($row['periode_debut'], 'd/m/Y') ?: '-',
            formatDate($row['periode_fin'], 'd/m/Y') ?: '-',
            number_format($row['montant_total'], 2, '.', ''),
            ucfirst($row['statut']),
            formatDate($row['date_paiement'], 'd/m/Y H:i') ?: '-'
        ], ';');
    }
    fclose($df);
    $csvContent = ob_get_clean();
    return $csvContent;
}

/**
 * Calcule le résumé de la TVA collectée par mois sur une période.
 *
 * @param string $startDate Date de début (Y-m-d) de paiement.
 * @param string $endDate Date de fin (Y-m-d) de paiement.
 * @param array $filters Filtres optionnels.
 * @return array Tableau des résumés mensuels ['mois', 'total_ht', 'total_tva', 'total_ttc'].
 */
function financialGetTaxSummaryByMonth($startDate, $endDate, $filters = []) {
    $params = [INVOICE_STATUS_PAID, $startDate, $endDate];
    $whereClauses = ["f.statut = ?", "DATE(f.date_paiement) BETWEEN ? AND ?"];
    $joins = "";

    
    if (!empty($filters['company_id']) && is_numeric($filters['company_id'])) {
        $whereClauses[] = "f.entreprise_id = ?";
        $params[] = $filters['company_id'];
    }
    

    $sql = "SELECT 
                DATE_FORMAT(f.date_paiement, '%Y-%m') as mois,
                SUM(f.montant_ht) as total_ht, 
                SUM(f.montant_total - f.montant_ht) as total_tva, 
                SUM(f.montant_total) as total_ttc
            FROM " . TABLE_INVOICES . " f
            $joins
            WHERE " . implode(' AND ', $whereClauses) . 
           " GROUP BY mois
            ORDER BY mois ASC";

    return executeQuery($sql, $params)->fetchAll();
}

?>
