<?php
require_once __DIR__ . '/../../init.php';

/**
 * Recupere la liste des contrats avec pagination et filtrage
 * 
 * @param int $page Numero de la page
 * @param int $perPage Nombre d'elements par page
 * @param string $search Terme de recherche
 * @param string $statut Filtre par statut
 * @param int $entrepriseId Filtre par entreprise
 * @return array Donnees de pagination et liste des contrats
 */
function contractsGetList($page = 1, $perPage = 10, $search = '', $statut = '', $entrepriseId = 0) {
    $params = [];
    $conditions = [];

    if ($entrepriseId > 0) {
        $conditions[] = "c.entreprise_id = ?";
        $params[] = $entrepriseId;
    }

    if ($statut) {
        $conditions[] = "c.statut = ?";
        $params[] = $statut;
    }

    if ($search) {
        $conditions[] = "(e.nom LIKE ? OR c.type_contrat LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    $whereSql = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : '';

    $offset = ($page - 1) * $perPage;

    $pdo = getDbConnection();
    $countSql = "SELECT COUNT(c.id) FROM contrats c LEFT JOIN entreprises e ON c.entreprise_id = e.id $whereSql";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalContracts = $countStmt->fetchColumn();
    $totalPages = ceil($totalContracts / $perPage);
    $page = max(1, min($page, $totalPages)); 
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT c.*, e.nom as nom_entreprise 
            FROM contrats c 
            LEFT JOIN entreprises e ON c.entreprise_id = e.id
            {$whereSql}
            ORDER BY c.date_debut DESC LIMIT ?, ?";
    $paramsWithPagination = array_merge($params, [$offset, $perPage]);

    $contracts = executeQuery($sql, $paramsWithPagination)->fetchAll();

    return [
        'contracts' => $contracts,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalContracts,
        'perPage' => $perPage
    ];
}

/**
 * Recupere les details d'un contrat
 * 
 * @param int $id Identifiant du contrat
 * @return array|false Donnees du contrat ou false si non trouve
 */
function contractsGetDetails($id) {
    $sql = "SELECT c.*, e.nom as nom_entreprise 
            FROM contrats c 
            LEFT JOIN entreprises e ON c.entreprise_id = e.id 
            WHERE c.id = ? LIMIT 1";
    return executeQuery($sql, [$id])->fetch();
}

/**
 * Recupere la liste des entreprises pour le formulaire
 * 
 * @return array Liste des entreprises
 */
function contractsGetEntreprises() {
    return fetchAll('entreprises', '', 'nom ASC'); 
}

/**
 * Crée ou met à jour un contrat dans la base de données.
 *
 * Utilise insertRow ou updateRow de db.php.
 *
 * @param array $data Données du contrat.
 * @param int $id Identifiant du contrat (0 pour création).
 * @return array Résultat ['success' => bool, 'message' => string|null, 'errors' => array|null]
 */
function contractsSave($data, $id = 0) {
    $errors = [];
    
    if (empty($data['entreprise_id'])) {
        $errors[] = "L'entreprise est obligatoire";
    } else {
        $companyExists = fetchOne('entreprises', 'id = ?', '', [(int)$data['entreprise_id']]);
        if (!$companyExists) {
            $errors[] = "L'entreprise sélectionnée n'existe pas";
        }
    }
    
    if (empty($data['date_debut'])) {
        $errors[] = "La date de debut est obligatoire";
    }
    
    if (empty($data['type_contrat'])) {
        $errors[] = "Le type de contrat est obligatoire";
    }
    
    if (!empty($data['date_fin']) && strtotime($data['date_fin']) < strtotime($data['date_debut'])) {
        $errors[] = "La date de fin ne peut pas etre anterieure a la date de debut";
    }
    
    if (!empty($errors)) {
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
    
    $dbData = [
        'entreprise_id' => (int)$data['entreprise_id'],
        'date_debut' => $data['date_debut'],
        'date_fin' => !empty($data['date_fin']) ? $data['date_fin'] : null,
        'montant_mensuel' => !empty($data['montant_mensuel']) ? (float)$data['montant_mensuel'] : null,
        'nombre_salaries' => !empty($data['nombre_salaries']) ? (int)$data['nombre_salaries'] : null,
        'type_contrat' => $data['type_contrat'],
        'statut' => $data['statut'] ?? 'en_attente', // Default status
        'conditions_particulieres' => $data['conditions_particulieres'] ?? null
    ];

    try {
        if ($id > 0) {
            $affectedRows = updateRow('contrats', $dbData, "id = ?", [$id]);
            
            if ($affectedRows !== false) {
                logBusinessOperation($_SESSION['user_id'], 'contract_update', 
                    "[SUCCESS] Mise à jour contrat ID: $id, entreprise ID: {$dbData['entreprise_id']}, type: {$dbData['type_contrat']}");
                $message = "Le contrat a ete mis a jour avec succes";
                return ['success' => true, 'message' => $message];
            } else {
                throw new Exception("La mise à jour a échoué ou aucune ligne n'a été modifiée.");
            }
        } 
        else {
            $newId = insertRow('contrats', $dbData);
            
            if ($newId) {
                logBusinessOperation($_SESSION['user_id'], 'contract_create', 
                    "[SUCCESS] Création contrat ID: $newId, entreprise ID: {$dbData['entreprise_id']}, type: {$dbData['type_contrat']}");
                $message = "Le contrat a ete cree avec succes";
                return ['success' => true, 'message' => $message, 'newId' => $newId];
            } else {
                 throw new Exception("L'insertion a échoué.");
            }
        }
        
    } catch (Exception $e) {
        $errorMessage = "Erreur de base de données : " . $e->getMessage();
        $errors[] = $errorMessage;
        logSystemActivity('error', "[ERROR] Erreur BDD dans contractsSave: " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/**
 * Supprime un contrat de la base de données.
 * 
 * Utilise deleteRow de db.php après vérification.
 *
 * @param int $id Identifiant unique du contrat à supprimer.
 * @return array Résultat ['success' => bool, 'message' => string]
 */
function contractsDelete($id) {

    try {
        $deletedRows = deleteRow('contrats', "id = ?", [$id]);
        
        if ($deletedRows > 0) {
            logBusinessOperation($_SESSION['user_id'], 'contract_delete', 
                "Suppression contrat ID: $id");
            return [
                'success' => true,
                'message' => "Le contrat a ete supprime avec succes"
            ];
        } else {
            logBusinessOperation($_SESSION['user_id'], 'contract_delete_attempt', 
                "[ERROR] Tentative échouée de suppression contrat ID: $id - Contrat non trouvé?");
            return [
                'success' => false,
                'message' => "Impossible de supprimer le contrat (non trouvé ou déjà supprimé)"
            ];
        }
    } catch (Exception $e) {
         logSystemActivity('error', "[ERROR] Erreur BDD dans contractsDelete: " . $e->getMessage());
         return [
            'success' => false,
            'message' => "Erreur de base de données lors de la suppression."
         ];
    }
}

/**
 * Met à jour le statut d'un contrat.
 *
 * Utilise updateRow de db.php.
 *
 * @param int $id Identifiant du contrat.
 * @param string $status Nouveau statut du contrat.
 * @return bool True si le statut a été mis à jour avec succès, false sinon.
 */
function contractsUpdateStatus($id, $status) {
    $validStatuses = ['actif', 'inactif', 'en_attente', 'suspendu', 'expire', 'resilie'];
    
    if (!in_array($status, $validStatuses)) {
        logBusinessOperation($_SESSION['user_id'], 'contract_status_update_attempt', 
            "[ERROR] Tentative échouée de mise à jour de statut contrat ID: $id avec valeur invalide: $status");
        return false;
    }
    
    $affectedRows = updateRow('contrats', ['statut' => $status], "id = ?", [$id]);
    
    if ($affectedRows > 0) { 
        logBusinessOperation($_SESSION['user_id'], 'contract_status_update', 
            "[SUCCESS] Mise à jour statut contrat ID: $id - Nouveau statut: $status");
        return true; 
    } elseif ($affectedRows === 0) {
         logBusinessOperation($_SESSION['user_id'], 'contract_status_update_noop', 
            "[INFO] Mise à jour statut contrat ID: $id - Statut déjà {$status} ou contrat non trouvé?");
        return false; 
    } else { 
        logSystemActivity('error', "[ERROR] Erreur lors de la mise à jour du statut du contrat ID: $id");
        return false;
    }
} 