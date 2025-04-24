<?php

require_once __DIR__ . '/../../../../shared/web-client/db.php';

/**
 * Récupère la liste des contrats pour une entreprise donnée.
 *
 * @param int $entreprise_id L'ID de l'entreprise.
 * @return array La liste des contrats (ou un tableau vide si aucun).
 */
function getCompanyContracts(int $entreprise_id): array
{
    if ($entreprise_id <= 0) {
        return [];
    }

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
                c.date_debut DESC";

    $stmt = executeQuery($sql, [
        ':entreprise_id' => $entreprise_id
    ]);

    return $stmt->fetchAll();
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
 * Ajoute un nouveau contrat (Placeholder - Fonctionnalité non active pour les clients).
 *
 * @param int $entreprise_id ID de l'entreprise.
 * @param array $contractData Données du contrat.
 * @return int|false Retourne toujours false pour les clients.
 */
function addContract(int $entreprise_id, array $contractData): int|false
{
    logSecurityEvent($_SESSION['user_id'] ?? null, 'contract_add_attempt', "[INFO] Tentative non autorisée d'ajout de contrat par client entreprise ID: " . $entreprise_id);
    flashMessage("L'ajout de contrat se fait via le processus de devis.", "info");
    return false;
}

/**
 * Met à jour un contrat (Placeholder - Fonctionnalité non active pour les clients).
 *
 * @param int $contract_id L'ID du contrat.
 * @param int $company_id L'ID de l'entreprise (pour vérification).
 * @param array $data Les données à mettre à jour.
 * @return bool Retourne toujours false pour les clients.
 */
function updateContract(int $contract_id, int $company_id, array $data): bool
{
    logSecurityEvent($_SESSION['user_id'] ?? null, 'contract_update_attempt', '[INFO] Tentative non autorisée de modification contrat ID: ' . $contract_id . ' par client entreprise ID: ' . $company_id);
    flashMessage("La modification des contrats n'est pas permise depuis cet espace.", "info");
    return false;
}

/**
 * Marque un contrat comme 'resilie' (Placeholder - Fonctionnalité non active pour les clients).
 *
 * @param int $contract_id L'ID du contrat à résilier.
 * @param int $company_id L'ID de l'entreprise (pour vérification).
 * @return bool Retourne toujours false pour les clients.
 */
function cancelContract(int $contract_id, int $company_id): bool
{
    logSecurityEvent($_SESSION['user_id'] ?? null, 'contract_cancel_attempt', '[INFO] Tentative non autorisée de résiliation contrat ID: ' . $contract_id . ' par client entreprise ID: ' . $company_id);
    flashMessage("La résiliation de contrat doit être demandée via le support.", "info");
    return false;
    /* Note : L'implémentation fonctionnelle pour la résiliation existe 
       mais est désactivée ici car on a décidé de ne pas donner cette 
       possibilité au client pour l'instant.
    */
}
