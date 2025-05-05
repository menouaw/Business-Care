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

/**
 * Calcule les statistiques d'utilisation agrégées et anonymisées pour un contrat.
 *
 * @param int $contract_id L'ID du contrat.
 * @param int $company_id L'ID de l'entreprise pour vérification.
 * @return array Statistiques d'utilisation (ex: ['medical_consultations_count' => 5, 'other_prestations_count' => 10]).
 */
function getContractUsageStats(int $contract_id, int $company_id): array
{
    $stats = [
        'medical_consultations_count' => 0,
        'other_prestations_count' => 0,
        // Ajoutez d'autres stats agrégées si nécessaire
    ];

    if ($contract_id <= 0 || $company_id <= 0) {
        return $stats;
    }

    // Vérifier que le contrat appartient bien à l'entreprise (sécurité)
    $contract_check = fetchOne('contrats', 'id = :contract_id AND entreprise_id = :company_id', [
        ':contract_id' => $contract_id,
        ':company_id' => $company_id
    ]);
    if (!$contract_check) {
        //error_log("Tentative d'accès non autorisé aux stats du contrat ID {$contract_id} par entreprise ID {$company_id}");
        return $stats; // Contrat non trouvé ou n'appartient pas à l'entreprise
    }

    // Définir les catégories médicales sensibles
    $medical_categories = ['Sante mentale', 'Nutrition', 'Sante', 'Bien-etre mental'];
    $placeholders_cat = implode(',', array_fill(0, count($medical_categories), '?'));

    // Récupérer les IDs des salariés liés à cette entreprise (directement ou via site)
    // Note: Cette requête peut être optimisée si les salariés sont toujours directement liés à entreprise_id
    $sql_employees = "SELECT p.id 
                      FROM personnes p
                      LEFT JOIN sites s ON p.site_id = s.id
                      WHERE (p.entreprise_id = ? OR s.entreprise_id = ?) 
                      AND p.role_id = ?";
    $stmt_employees = executeQuery($sql_employees, [$company_id, $company_id, ROLE_SALARIE]);
    $employee_ids = $stmt_employees ? $stmt_employees->fetchAll(PDO::FETCH_COLUMN) : [];

    if (empty($employee_ids)) {
        return $stats; // Aucun salarié trouvé pour cette entreprise
    }
    $placeholders_emp = implode(',', array_fill(0, count($employee_ids), '?'));

    // Compter les RDV pour ce contrat, pris par les salariés de l'entreprise
    $sql_usage = "SELECT 
                        SUM(CASE WHEN pr.type = 'consultation' AND pr.categorie IN ({$placeholders_cat}) THEN 1 ELSE 0 END) as medical_count,
                        SUM(CASE WHEN pr.type != 'consultation' OR pr.categorie NOT IN ({$placeholders_cat}) THEN 1 ELSE 0 END) as other_count
                    FROM rendez_vous rv
                    JOIN prestations pr ON rv.prestation_id = pr.id
                    WHERE rv.personne_id IN ({$placeholders_emp})
                      -- Optionnel: Filtrer par date si le contrat a une période définie
                      -- AND rv.date_rdv >= :contract_start_date 
                      -- AND rv.date_rdv <= :contract_end_date
                      AND rv.statut IN ('termine', 'confirme', 'planifie')";
    // Ajoutez d'autres statuts si nécessaire

    // Préparer les paramètres
    $params = array_merge($medical_categories, $employee_ids);
    // Si vous ajoutez le filtre par date:
    // $params[':contract_start_date'] = $contract_check['date_debut'];
    // $params[':contract_end_date'] = $contract_check['date_fin'] ?? date('Y-m-d H:i:s'); // Ou une date future si pas de date de fin

    try {
        $stmt_usage = executeQuery($sql_usage, $params);
        $usage_counts = $stmt_usage ? $stmt_usage->fetch(PDO::FETCH_ASSOC) : null;

        if ($usage_counts) {
            $stats['medical_consultations_count'] = (int)$usage_counts['medical_count'];
            $stats['other_prestations_count'] = (int)$usage_counts['other_count'];
        }
    } catch (PDOException $e) {
        // error_log("Erreur PDO dans getContractUsageStats pour contrat ID {$contract_id}: " . $e->getMessage());
        // Gérer l'erreur comme nécessaire, potentiellement retourner des stats vides ou logger
    }

    return $stats;
}
