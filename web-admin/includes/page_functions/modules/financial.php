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
 * Génère les données pour un rapport de revenus détaillé (pour affichage UI).
 *
 * @param string $startDate Date de début (Y-m-d) de paiement.
 * @param string $endDate Date de fin (Y-m-d) de paiement.
 * @param array $filters Filtres optionnels (ex: ['company_id' => 1, 'service_type' => 'Premium Pack']). Non implémenté ici.
 * @return array Liste des factures payées sur la période.
 */
function financialGetDetailedRevenueReport($startDate, $endDate, $filters = []) {
    
    $params = [INVOICE_STATUS_PAID, $startDate, $endDate];
    $whereClauses = ["f.statut = ?", "DATE(f.date_paiement) BETWEEN ? AND ?"];

    
    
    
    
    

    $sql = "SELECT f.*, e.nom as nom_entreprise
            FROM " . TABLE_INVOICES . " f
            LEFT JOIN " . TABLE_COMPANIES . " e ON f.entreprise_id = e.id
            WHERE " . implode(' AND ', $whereClauses) .
           " ORDER BY f.date_paiement DESC";

    return executeQuery($sql, $params)->fetchAll();
}

/**
 * Calcule le résumé de la TVA collectée sur une période.
 *
 * @param string $startDate Date de début (Y-m-d) de paiement.
 * @param string $endDate Date de fin (Y-m-d) de paiement.
 * @return array Tableau contenant 'total_ht', 'total_tva', 'total_ttc'.
 */
function financialGetTaxSummaryReport($startDate, $endDate) {
    $sql = "SELECT SUM(montant_ht) as total_ht, SUM(montant_total - montant_ht) as total_tva, SUM(montant_total) as total_ttc
            FROM " . TABLE_INVOICES . "
            WHERE statut = ? AND DATE(date_paiement) BETWEEN ? AND ?";

    $summary = executeQuery($sql, [INVOICE_STATUS_PAID, $startDate, $endDate])->fetch();

    return [
        'total_ht' => (float)($summary['total_ht'] ?? 0),
        'total_tva' => (float)($summary['total_tva'] ?? 0),
        'total_ttc' => (float)($summary['total_ttc'] ?? 0)
    ];
}

/**
 * Retourne le symbole monétaire par défaut.
 *
 * @return string Symbole monétaire.
 */
function financialGetCurrencySymbol() {
    return DEFAULT_CURRENCY;
}

?>
