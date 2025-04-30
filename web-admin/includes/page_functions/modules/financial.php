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
 * Calcule le revenu total payé sur une période donnée.
 *
 * @param string $startDate Date de début (Y-m-d).
 * @param string $endDate Date de fin (Y-m-d).
 * @return float Montant total des revenus payés.
 */
function financialGetRevenueSummaryByPeriod($startDate, $endDate) {
    $sql = "SELECT SUM(montant_total) FROM " . TABLE_INVOICES . " WHERE statut = ? AND DATE(date_paiement) BETWEEN ? AND ?";
    $totalRevenue = executeQuery($sql, [INVOICE_STATUS_PAID, $startDate, $endDate])->fetchColumn();
    return (float)($totalRevenue ?: 0);
}

/**
 * Récupère le nombre et le montant total des factures clients en retard.
 *
 * @return array Tableau contenant 'count' et 'total_amount'.
 */
function financialGetOverdueInvoicesSummary() {
    $sql = "SELECT COUNT(id) as count, SUM(montant_total) as total_amount FROM " . TABLE_INVOICES . " WHERE statut IN (?, ?) AND date_echeance < CURDATE()";
    $summary = executeQuery($sql, [INVOICE_STATUS_LATE, INVOICE_STATUS_UNPAID])->fetch();
    return [
        'count' => (int)($summary['count'] ?? 0),
        'total_amount' => (float)($summary['total_amount'] ?? 0)
    ];
}

/**
 * Calcule le montant total dû aux prestataires (factures impayées).
 *
 * @return float Montant total dû.
 */
function financialGetPendingProviderPayoutsSummary() {
    $sql = "SELECT SUM(montant_total) FROM " . TABLE_PRACTITIONER_INVOICES . " WHERE statut = ?";
    $totalPending = executeQuery($sql, [PRACTITIONER_INVOICE_STATUS_UNPAID])->fetchColumn();
    return (float)($totalPending ?: 0);
}

/**
 * Calcule les statistiques de conversion des devis sur une période.
 *
 * @param string $startDate Date de début (Y-m-d) de création du devis.
 * @param string $endDate Date de fin (Y-m-d) de création du devis.
 * @return array Tableau contenant 'total_quotes', 'accepted_count', 'refused_expired_count', 'conversion_rate'.
 */
function financialGetQuoteConversionStats($startDate, $endDate) {
    $pdo = getDbConnection();
    $stats = [
        'total_quotes' => 0,
        'accepted_count' => 0,
        'refused_expired_count' => 0,
        'conversion_rate' => 0
    ];

    $sqlTotal = "SELECT COUNT(id) FROM " . TABLE_QUOTES . " WHERE DATE(date_creation) BETWEEN ? AND ?";
    $stats['total_quotes'] = executeQuery($sqlTotal, [$startDate, $endDate])->fetchColumn();

    if ($stats['total_quotes'] > 0) {
        $sqlAccepted = "SELECT COUNT(id) FROM " . TABLE_QUOTES . " WHERE statut = ? AND DATE(date_creation) BETWEEN ? AND ?";
        $stats['accepted_count'] = executeQuery($sqlAccepted, [QUOTE_STATUS_ACCEPTED, $startDate, $endDate])->fetchColumn();

        $sqlRefusedExpired = "SELECT COUNT(id) FROM " . TABLE_QUOTES . " WHERE statut IN (?, ?) AND DATE(date_creation) BETWEEN ? AND ?";
        $stats['refused_expired_count'] = executeQuery($sqlRefusedExpired, [QUOTE_STATUS_REFUSED, QUOTE_STATUS_EXPIRED, $startDate, $endDate])->fetchColumn();

        $stats['conversion_rate'] = round(($stats['accepted_count'] / $stats['total_quotes']) * 100, 2);
    }

    return $stats;
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

    
    
    if (!empty($filters['service_id']) && is_numeric($filters['service_id'])) {
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
    
    if (!empty($filters['service_id']) && is_numeric($filters['service_id'])) {
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
 * Récupère les revenus agrégés par un critère donné (compagnie, service, mois).
 *
 * @param string $startDate Date de début (Y-m-d) de paiement.
 * @param string $endDate Date de fin (Y-m-d) de paiement.
 * @param string $groupBy Critère de groupement ('company', 'service', 'month').
 * @param array $filters Filtres additionnels.
 * @return array Tableau des résultats agrégés.
 */
function financialGetRevenueGroupedBy($startDate, $endDate, $groupBy, $filters = []) {
    $params = [INVOICE_STATUS_PAID, $startDate, $endDate];
    $whereClauses = ["f.statut = ?", "DATE(f.date_paiement) BETWEEN ? AND ?"];
    $joins = " LEFT JOIN " . TABLE_COMPANIES . " e ON f.entreprise_id = e.id";
    $groupBySql = "";
    $selectFields = "SUM(f.montant_ht) as total_ht, SUM(f.montant_total - f.montant_ht) as total_tva, SUM(f.montant_total) as total_ttc";

    
    if (!empty($filters['company_id']) && is_numeric($filters['company_id'])) {
        $whereClauses[] = "f.entreprise_id = ?";
        $params[] = $filters['company_id'];
    }
    if (!empty($filters['service_id']) && is_numeric($filters['service_id'])) {
        $joins .= " LEFT JOIN " . TABLE_QUOTES . " d ON f.devis_id = d.id";
        $whereClauses[] = "d.service_id = ?";
        $params[] = $filters['service_id'];
    }

    
    switch ($groupBy) {
        case 'company':
            $selectFields .= ", f.entreprise_id, e.nom as group_name";
            $groupBySql = " GROUP BY f.entreprise_id, e.nom";
            break;
        case 'service':
            
            $joins .= " LEFT JOIN " . TABLE_QUOTES . " d ON f.devis_id = d.id";
            $joins .= " LEFT JOIN " . TABLE_SERVICES . " s ON d.service_id = s.id";
            $selectFields .= ", d.service_id, s.type as group_name"; 
            $groupBySql = " GROUP BY d.service_id, s.type";
            break;
        case 'month':
            $selectFields .= ", DATE_FORMAT(f.date_paiement, '%Y-%m') as group_name";
            $groupBySql = " GROUP BY DATE_FORMAT(f.date_paiement, '%Y-%m')";
            break;
        default:
            
            return [];
    }

    $sql = "SELECT $selectFields
            FROM " . TABLE_INVOICES . " f
            $joins
            WHERE " . implode(' AND ', $whereClauses) .
            $groupBySql . 
            " ORDER BY group_name ASC";

    return executeQuery($sql, $params)->fetchAll();
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
    return ob_get_clean();
}

/**
 * Récupère les résumés de revenus pour deux périodes afin de comparaison.
 *
 * @param string $currentStartDate Date début période actuelle.
 * @param string $currentEndDate Date fin période actuelle.
 * @param string $previousStartDate Date début période précédente.
 * @param string $previousEndDate Date fin période précédente.
 * @param array $filters Filtres optionnels.
 * @return array Tableau contenant les résumés ['current' => [...], 'previous' => [...]].
 */
function financialGetRevenueComparison($currentStartDate, $currentEndDate, $previousStartDate, $previousEndDate, $filters = []) {
    $currentSummary = financialGetTaxSummaryReport($currentStartDate, $currentEndDate, $filters);
    $previousSummary = financialGetTaxSummaryReport($previousStartDate, $previousEndDate, $filters);

    return [
        'current' => $currentSummary,
        'previous' => $previousSummary
    ];
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

    
    $totalPages = $totalItems > 0 ? ceil($totalItems / $perPage) : 1;
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    
    $defaultSortBy = 'fp.date_facture';
    $defaultSortOrder = 'DESC';

    
    $sql = "SELECT $selectFields
            FROM " . TABLE_PRACTITIONER_INVOICES . " fp
            $joins
            WHERE " . $whereSql .
           " ORDER BY $defaultSortBy $defaultSortOrder
            LIMIT ? OFFSET ?";

    
    $params[] = $perPage;
    $params[] = $offset;

    $items = executeQuery($sql, $params)->fetchAll();

    return [
        'items' => $items,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'perPage' => $perPage
    ];
}

/**
 * Calcule les montants dus agrégés par prestataire.
 *
 * @param string $startDate Date de début (Y-m-d) de facture prestataire.
 * @param string $endDate Date de fin (Y-m-d) de facture prestataire.
 * @param array $filters Filtres optionnels (ex: ['statut' => 'impayee']).
 * @return array Tableau des montants agrégés par prestataire.
 */
function financialGetPayoutsGroupedByProvider($startDate, $endDate, $filters = []) {
    $params = [$startDate, $endDate];
    $whereClauses = ["DATE(fp.date_facture) BETWEEN ? AND ?"];
    $joins = " LEFT JOIN " . TABLE_USERS . " p ON fp.prestataire_id = p.id";
    $selectFields = "SUM(fp.montant_total) as total_du, fp.prestataire_id, CONCAT(p.prenom, ' ', p.nom) as nom_prestataire";

    
    if (!empty($filters['statut']) && in_array($filters['statut'], PRACTITIONER_INVOICE_STATUSES)) {
        $whereClauses[] = "fp.statut = ?";
        $params[] = $filters['statut'];
    }

    $sql = "SELECT $selectFields
            FROM " . TABLE_PRACTITIONER_INVOICES . " fp
            $joins
            WHERE " . implode(' AND ', $whereClauses) .
            " GROUP BY fp.prestataire_id, nom_prestataire" . 
            " ORDER BY nom_prestataire ASC";

    return executeQuery($sql, $params)->fetchAll();
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
    return ob_get_clean();
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

/**
 * Génère un contenu CSV pour le résumé fiscal (global ou par mois).
 *
 * @param string $startDate Date de début.
 * @param string $endDate Date de fin.
 * @param array $filters Filtres.
 * @param bool $byMonth Si true, génère le rapport par mois.
 * @return string Contenu CSV.
 */
function financialGenerateTaxSummaryCSV($startDate, $endDate, $filters = [], $byMonth = false) {
    ob_start();
    $df = fopen("php://output", 'w');

    if ($byMonth) {
        $data = financialGetTaxSummaryByMonth($startDate, $endDate, $filters);
        fputcsv($df, ['Mois', 'Total HT', 'Total TVA', 'Total TTC'], ';');
        foreach ($data as $row) {
            fputcsv($df, [
                $row['mois'],
                number_format($row['total_ht'], 2, '.', ''),
                number_format($row['total_tva'], 2, '.', ''),
                number_format($row['total_ttc'], 2, '.', '')
            ], ';');
        }
    } else {
        $data = financialGetTaxSummaryReport($startDate, $endDate, $filters);
        fputcsv($df, ['Description', 'Montant'], ';');
        fputcsv($df, ['Total HT', number_format($data['total_ht'], 2, '.', '')], ';');
        fputcsv($df, ['Total TVA', number_format($data['total_tva'], 2, '.', '')], ';');
        fputcsv($df, ['Total TTC', number_format($data['total_ttc'], 2, '.', '')], ';');
    }

    fclose($df);
    return ob_get_clean();
}

/**
 * Retourne le symbole monétaire par défaut.
 *
 * @return string Symbole monétaire.
 */
function financialGetCurrencySymbol() {
    return DEFAULT_CURRENCY;
}

/**
 * Marque une facture prestataire comme payée.
 *
 * @param int $invoiceId ID de la facture prestataire à mettre à jour.
 * @param string|null $paymentDate Date de paiement (Y-m-d H:i:s). Si null, utilise la date/heure actuelle.
 * @return array Résultat ['success' => bool, 'message' => string].
 */
function financialMarkProviderInvoicePaid($invoiceId, $paymentDate = null) {
    $invoiceId = (int)$invoiceId;
    if ($invoiceId <= 0) {
        return ['success' => false, 'message' => 'ID de facture invalide.'];
    }

    
    if ($paymentDate === null) {
        $paymentDate = date('Y-m-d H:i:s');
    } else {
        
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $paymentDate);
        if (!$d || $d->format('Y-m-d H:i:s') !== $paymentDate) {
             return ['success' => false, 'message' => 'Format de date de paiement invalide. Utilisez YYYY-MM-DD HH:MM:SS.'];
        }
    }

    try {
        beginTransaction();

        
        $invoice = fetchOne(TABLE_PRACTITIONER_INVOICES, "id = ? AND statut = ?", [$invoiceId, PRACTITIONER_INVOICE_STATUS_UNPAID]);
        if (!$invoice) {
            rollbackTransaction();
            
            $existing = fetchOne(TABLE_PRACTITIONER_INVOICES, "id = ?", [$invoiceId]);
            if ($existing && $existing['statut'] === PRACTITIONER_INVOICE_STATUS_PAID) {
                 return ['success' => false, 'message' => 'La facture est déjà marquée comme payée.'];
            }
             return ['success' => false, 'message' => 'Facture non trouvée ou n\'est pas en statut impayé.'];
        }

        $dataToUpdate = [
            'statut' => PRACTITIONER_INVOICE_STATUS_PAID,
            'date_paiement' => $paymentDate,
            
        ];

        $affectedRows = updateRow(TABLE_PRACTITIONER_INVOICES, $dataToUpdate, "id = :where_id", [':where_id' => $invoiceId]);

        if ($affectedRows > 0) {
            commitTransaction();
            logBusinessOperation($_SESSION['user_id'] ?? 0, 'provider_invoice_paid', "Facture prestataire ID: {$invoiceId} marquée comme payée.");
            return ['success' => true, 'message' => 'Facture marquée comme payée avec succès.'];
        } else {
            
            rollbackTransaction();
            logSystemActivity('error', "Échec de la mise à jour du statut de paiement pour la facture prestataire ID: {$invoiceId} (affectedRows=0)");
            return ['success' => false, 'message' => 'La mise à jour du statut de la facture a échoué. Aucune modification détectée.'];
        }

    } catch (Exception $e) {
        rollbackTransaction();
        logSystemActivity('error', "Erreur BDD dans financialMarkProviderInvoicePaid: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur de base de données lors de la mise à jour de la facture: ' . $e->getMessage()];
    }
}

?>
