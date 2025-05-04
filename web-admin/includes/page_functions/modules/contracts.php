<?php
require_once __DIR__ . '/../../init.php';

/**
 * Recupere la liste des contrats avec pagination et filtrage
 * 
 * @param int $page Numero de la page
 * @param int $perPage Nombre d'elements par page (defaut via constante)
 * @param string $search Terme de recherche
 * @param string $statut Filtre par statut
 * @param int $serviceId Filtre par service 
 * @return array Donnees de pagination et liste des contrats
 */
function contractsGetList($page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE, $search = '', $statut = '', $serviceId = 0) {
    $params = [];
    $conditions = [];

    if ($serviceId > 0) {
        $conditions[] = "c.service_id = ?";
        $params[] = (int)$serviceId;
    }

    if ($statut) {
        $conditions[] = "c.statut = ?";
        $params[] = $statut;
    }

    if ($search) {
        $conditions[] = "(e.nom LIKE ? OR s.type LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    $whereSql = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : '';

    $offset = ($page - 1) * $perPage;

    $pdo = getDbConnection();
    $countSql = "SELECT COUNT(c.id) 
                 FROM " . TABLE_CONTRACTS . " c 
                 LEFT JOIN " . TABLE_COMPANIES . " e ON c.entreprise_id = e.id 
                 LEFT JOIN " . TABLE_SERVICES . " s ON c.service_id = s.id 
                 $whereSql";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalContracts = $countStmt->fetchColumn();
    $totalPages = ceil($totalContracts / $perPage);
    $page = max(1, min($page, $totalPages)); 
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT c.*, e.nom as nom_entreprise, s.type as type_service 
            FROM " . TABLE_CONTRACTS . " c 
            LEFT JOIN " . TABLE_COMPANIES . " e ON c.entreprise_id = e.id
            LEFT JOIN " . TABLE_SERVICES . " s ON c.service_id = s.id
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
    $sql = "SELECT c.*, e.nom as nom_entreprise, s.type as type_service
            FROM " . TABLE_CONTRACTS . " c 
            LEFT JOIN " . TABLE_COMPANIES . " e ON c.entreprise_id = e.id 
            LEFT JOIN " . TABLE_SERVICES . " s ON c.service_id = s.id
            WHERE c.id = ? LIMIT 1";
    return executeQuery($sql, [$id])->fetch();
}

/**
 * Recupere la liste des entreprises pour le formulaire
 * 
 * @return array Liste des entreprises
 */
function contractsGetEntreprises() {
    return fetchAll(TABLE_COMPANIES, '', 'nom ASC'); 
}

/**
 * Recupere la liste des services pour le formulaire
 * 
 * @return array Liste des services (id, nom)
 */
function contractsGetServices() {
    return fetchAll(TABLE_SERVICES, 'actif = 1', 'ordre ASC'); 
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
        $companyExists = fetchOne(TABLE_COMPANIES, 'id = ?', '', [(int)$data['entreprise_id']]);
        if (!$companyExists) {
            $errors[] = "L'entreprise sélectionnée n'existe pas";
        }
    }

    if (empty($data['service_id'])) {
        $errors[] = "Le service (type de contrat) est obligatoire";
    } else {
        $serviceExists = fetchOne(TABLE_SERVICES, 'id = ? AND actif = 1', '', [(int)$data['service_id']]);
        if (!$serviceExists) {
            $errors[] = "Le service sélectionné n'existe pas ou n'est pas actif";
        }
    }
    
    if (empty($data['date_debut'])) {
        $errors[] = "La date de debut est obligatoire";
    }
    
    if (!empty($data['date_fin']) && $data['date_fin'] < $data['date_debut']) {
        $errors[] = "La date de fin ne peut pas être antérieure à la date de début";
    }
    
    $status = $data['statut'] ?? DEFAULT_CONTRACT_STATUS;
    if (!in_array($status, CONTRACT_STATUSES)) {
        $errors[] = "Le statut sélectionné n'est pas valide.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    $dbData = [
        'entreprise_id' => (int)$data['entreprise_id'],
        'service_id' => (int)$data['service_id'],
        'date_debut' => $data['date_debut'],
        'date_fin' => !empty($data['date_fin']) ? $data['date_fin'] : null,
        'nombre_salaries' => !empty($data['nombre_salaries']) ? (int)$data['nombre_salaries'] : null,
        'statut' => $status,
        'conditions_particulieres' => $data['conditions_particulieres'] ?? null
    ];

    try {
        beginTransaction();

        if ($id > 0) {
            $affectedRows = updateRow(TABLE_CONTRACTS, $dbData, "id = :where_id", [':where_id' => $id]);
            
            if ($affectedRows !== false) {
                logBusinessOperation($_SESSION['user_id'], 'contract_update', 
                    "[SUCCESS] Mise à jour contrat ID: $id, entreprise ID: {$dbData['entreprise_id']}, service ID: {$dbData['service_id']}");
                $message = "Le contrat a ete mis a jour avec succes";
                commitTransaction();
                return ['success' => true, 'message' => $message];
            } else {
                throw new Exception("La mise à jour a échoué ou aucune ligne n'a été modifiée.");
            }
        } 
        else {
            $newId = insertRow(TABLE_CONTRACTS, $dbData);
            
            if ($newId) {
                logBusinessOperation($_SESSION['user_id'], 'contract_create', 
                    "[SUCCESS] Création contrat ID: $newId, entreprise ID: {$dbData['entreprise_id']}, service ID: {$dbData['service_id']}");
                $message = "Le contrat a ete cree avec succes";
                commitTransaction();
                return ['success' => true, 'message' => $message, 'newId' => $newId];
            } else {
                 throw new Exception("L'insertion a échoué.");
            }
        }
        
    } catch (Exception $e) {
        rollbackTransaction();
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
        beginTransaction();
        $deletedRows = deleteRow(TABLE_CONTRACTS, "id = ?", [$id]);
        
        if ($deletedRows > 0) {
            commitTransaction();
            logBusinessOperation($_SESSION['user_id'], 'contract_delete', 
                "Suppression contrat ID: $id");
            return [
                'success' => true,
                'message' => "Le contrat a ete supprime avec succes"
            ];
        } else {
            rollbackTransaction();
            logBusinessOperation($_SESSION['user_id'], 'contract_delete_attempt', 
                "[ERROR] Tentative échouée de suppression contrat ID: $id - Contrat non trouvé?");
            return [
                'success' => false,
                'message' => "Impossible de supprimer le contrat (non trouvé ou déjà supprimé)"
            ];
        }
    } catch (Exception $e) {
         rollbackTransaction();
         logSystemActivity('error', "[ERROR] Erreur BDD dans contractsDelete: " . $e->getMessage());
         return [
            'success' => false,
            'message' => "Erreur de base de données lors de la suppression."
         ];
    }
}

/**
 * Compte le nombre d'utilisateurs actifs associés à une entreprise.
 *
 * @param int $entrepriseId L'ID de l'entreprise.
 * @return int Le nombre d'utilisateurs actifs.
 */
function contractsGetActiveUserCountForCompany(int $entrepriseId): int {
    if ($entrepriseId <= 0) {
        return 0;
    }
    $userTable = defined('TABLE_USERS') ? TABLE_USERS : 'personnes';
    $sql = "SELECT COUNT(id) FROM " . $userTable . " WHERE entreprise_id = ? AND statut = 'actif'";
    
    try {
        $count = executeQuery($sql, [$entrepriseId])->fetchColumn();
        return (int)$count;
    } catch (Exception $e) {
        logSystemActivity('error', '[ERROR] Erreur lors de la récupération du nombre d\'utilisateurs actifs pour l\'entreprise ID ' . $entrepriseId . ': ' . $e->getMessage());
        return 0; 
    }
}