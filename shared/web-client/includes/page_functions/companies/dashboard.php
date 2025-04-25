<?php
require_once __DIR__ . '/../../../includes/init.php';
require_once __DIR__ . '/../../../../shared/web-client/db.php';

/**
 * Récupère les statistiques pour le tableau de bord de l'entreprise.
 *
 * @param int $entreprise_id L'ID de l'entreprise.
 * @return array Un tableau contenant les statistiques.
 */
function getCompanyDashboardStats(int $entreprise_id): array
{
    $stats = [
        'active_employees' => 0,
        'active_contracts' => 0,
        'pending_quotes' => 0,
        'pending_invoices' => 0,
    ];

    if ($entreprise_id <= 0) {
        return $stats;
    }

    try {
        
        $sql_employees = "SELECT COUNT(DISTINCT p.id) 
                          FROM personnes p
                          LEFT JOIN sites s ON p.site_id = s.id
                          WHERE (p.entreprise_id = :id1 OR s.entreprise_id = :id2) 
                          AND p.statut = 'actif' 
                          AND p.role_id = :role_salarie";
        $stmt_employees = executeQuery($sql_employees, [
            ':id1' => $entreprise_id,
            ':id2' => $entreprise_id,
            ':role_salarie' => ROLE_SALARIE
        ]);
        $employee_count_raw = $stmt_employees->fetchColumn();
        $stats['active_employees'] = (int)$employee_count_raw;

        
        $sql_contracts = "SELECT COUNT(*) FROM contrats WHERE entreprise_id = :id AND statut = 'actif'";
        $stmt_contracts = executeQuery($sql_contracts, [':id' => $entreprise_id]);
        $stats['active_contracts'] = (int)$stmt_contracts->fetchColumn();

        
        $sql_quotes = "SELECT COUNT(*) FROM devis WHERE entreprise_id = :id AND statut IN (:status1, :status2)";
        $stmt_quotes = executeQuery($sql_quotes, [
            ':id' => $entreprise_id,
            ':status1' => QUOTE_STATUS_PENDING,       
            ':status2' => QUOTE_STATUS_CUSTOM_REQUEST 
        ]);
        $stats['pending_quotes'] = (int)$stmt_quotes->fetchColumn();

        
        $sql_invoices = "SELECT COUNT(*) FROM factures WHERE entreprise_id = :id AND statut IN (:status1, :status2, :status3)";
        $stmt_invoices = executeQuery($sql_invoices, [
            ':id' => $entreprise_id,
            ':status1' => INVOICE_STATUS_PENDING, 
            ':status2' => INVOICE_STATUS_LATE,    
            ':status3' => INVOICE_STATUS_UNPAID   
        ]);
        $stats['pending_invoices'] = (int)$stmt_invoices->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur PDO dans getCompanyDashboardStats pour entreprise ID {$entreprise_id}: " . $e->getMessage());
        
    }

    return $stats;
}
