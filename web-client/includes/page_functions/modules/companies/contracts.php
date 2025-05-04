<?php

require_once __DIR__ . '/../../../init.php';

/**
 * Récupère la liste paginée des contrats pour une entreprise donnée.
 *
 * @param int $entreprise_id L'ID de l'entreprise.
 * @param int $current_page La page actuelle.
 * @param int $items_per_page Le nombre d'éléments par page.
 * @return array Un tableau contenant 'contracts' (liste des contrats pour la page) et 'total_count' (nombre total de contrats).
 */
function getCompanyContracts(int $entreprise_id, int $current_page = 1, int $items_per_page = 10): array
{
    if ($entreprise_id <= 0) {
        return ['contracts' => [], 'total_count' => 0];
    }


    $countSql = "SELECT COUNT(*) as total 
                 FROM contrats 
                 WHERE entreprise_id = :entreprise_id";
    $countStmt = executeQuery($countSql, [':entreprise_id' => $entreprise_id]);
    $total_count = $countStmt->fetchColumn() ?: 0;


    $offset = max(0, ($current_page - 1) * $items_per_page);
    $limit = max(1, $items_per_page);

    $sql = "SELECT 
                c.id, 
                c.date_debut, 
                c.date_fin, 
                c.statut, 
                s.type as service_nom,  
                c.updated_at
            FROM 
                contrats c
            LEFT JOIN
                services s ON c.service_id = s.id
            WHERE 
                c.entreprise_id = :entreprise_id 
            ORDER BY 
                c.date_debut DESC
            LIMIT :limit OFFSET :offset";

    $stmt = executeQuery($sql, [
        ':entreprise_id' => $entreprise_id,
        ':limit' => $limit,
        ':offset' => $offset
    ]);

    $contracts = $stmt->fetchAll();


    return [
        'contracts' => $contracts,
        'total_count' => $total_count
    ];
}

/**
 * Récupère les détails complets d'un contrat spécifique appartenant à une entreprise.
 *
 * @param int $contract_id L'ID du contrat.
 * @param int $company_id L'ID de l'entreprise pour vérification.
 * @return array|false Les détails du contrat ou false si non trouvé ou n'appartient pas à l'entreprise.
 */
function getContractDetails(int $contract_id, int $company_id): array|false
{
    if ($contract_id <= 0 || $company_id <= 0) {
        return false;
    }

    $sql = "SELECT 
                c.*, 
                s.type as service_nom 
            FROM 
                contrats c
            LEFT JOIN
                services s ON c.service_id = s.id
            WHERE 
                c.id = :contract_id 
            AND 
                c.entreprise_id = :company_id";

    $stmt = executeQuery($sql, [
        ':contract_id' => $contract_id,
        ':company_id' => $company_id
    ]);

    return $stmt->fetch();
}

/**
 * Récupère la liste des services/packs disponibles pour les contrats.
 *
 * @return array La liste des services (id, type).
 */
function getContractServicesList(): array
{
    return fetchAll('services', 'actif = 1', [], 'id, type');
}
