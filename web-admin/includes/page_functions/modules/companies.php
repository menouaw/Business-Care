<?php
require_once __DIR__ . '/../../init.php';

/**
 * Recupere la liste des entreprises avec pagination et filtrage
 * 
 * @param int $page Numero de la page
 * @param int $perPage Nombre d'elements par page
 * @param string $search Terme de recherche
 * @param string $city Filtre par ville
 * @param string $size Filtre par taille
 * @return array Donnees de pagination et liste des entreprises
 */
function companiesGetList($page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE, $search = '', $city = '', $size = '') {
    $whereClauses = [];
    $params = [];

    if ($search) {
        $whereClauses[] = "(nom LIKE ? OR siret LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    if ($city) {
        $whereClauses[] = "ville = ?";
        $params[] = $city;
    }
    
    if ($size) {
        $whereClauses[] = "taille_entreprise = ?";
        $params[] = $size;
    }
    
    $whereSql = !empty($whereClauses) ? implode(' AND ', $whereClauses) : '1';

    $countSql = "SELECT COUNT(id) FROM " . TABLE_COMPANIES . " WHERE {$whereSql}";
    $totalCompanies = executeQuery($countSql, $params)->fetchColumn();
    
    $totalPages = ceil($totalCompanies / $perPage);
    $page = max(1, min($page, $totalPages)); 
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT * FROM " . TABLE_COMPANIES . " WHERE {$whereSql} ORDER BY nom ASC LIMIT ?, ?";
    $paramsForLimit = array_merge($params, [(int)$offset, (int)$perPage]);

    $companies = executeQuery($sql, $paramsForLimit)->fetchAll();

    return [
        'companies' => $companies,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalCompanies,
        'perPage' => $perPage
    ];
}

/**
 * Recupere les details d'une entreprise avec ses contrats et utilisateurs associes
 * 
 * @param int $id Identifiant de l'entreprise
 * @return array|false Donnees de l'entreprise ou false si non trouvee
 */
function companiesGetDetails($id) {
    $company = fetchOne(TABLE_COMPANIES, "id = ?", '', [$id]);
    
    if (!$company) {
        return false;
    }
    
    $sqlContracts = "SELECT c.*, s.type as type_service 
                     FROM " . TABLE_CONTRACTS . " c 
                     LEFT JOIN " . TABLE_SERVICES . " s ON c.service_id = s.id
                     WHERE c.entreprise_id = ? ORDER BY c.date_debut DESC";
    $company['contracts'] = executeQuery($sqlContracts, [$id])->fetchAll();
    
    $sqlUsers = "SELECT p.*, r.nom as role_name FROM " . TABLE_USERS . " p LEFT JOIN " . TABLE_ROLES . " r ON p.role_id = r.id WHERE p.entreprise_id = ? ORDER BY p.nom, p.prenom";
    $company['users'] = executeQuery($sqlUsers, [$id])->fetchAll();
    
    return $company;
}

/**
 * Crée une nouvelle entreprise ou met à jour une entreprise existante avec les données fournies.
 *
 * Valide les champs obligatoires et la taille d'entreprise, puis effectue l'opération en base de données dans une transaction. Retourne le statut de l'opération, un message de succès ou d'erreur, et l'identifiant créé en cas de création.
 *
 * @param array $data Données de l'entreprise à enregistrer.
 * @param int $id Identifiant de l'entreprise à mettre à jour (0 pour une création).
 * @return array Résultat de l'opération : ['success' => bool, 'message' => string|null, 'errors' => array|null, 'newId' => int|null]
 */
function companiesSave($data, $id = 0) {
    $errors = [];
    
    if (empty($data['nom'])) {
        $errors[] = "Le nom de l'entreprise est obligatoire";
    }
    
    if (!empty($data['taille_entreprise']) && !in_array($data['taille_entreprise'], COMPANY_SIZES)) {
        $errors[] = "La taille d'entreprise sélectionnée n'est pas valide.";
    }
    
    if (!empty($errors)) {
        return [
            'success' => false,
            'errors' => $errors
        ];
    }

    $dbData = [
        'nom' => $data['nom'], 
        'siret' => $data['siret'] ?? null, 
        'adresse' => $data['adresse'] ?? null, 
        'code_postal' => $data['code_postal'] ?? null,
        'ville' => $data['ville'] ?? null, 
        'telephone' => $data['telephone'] ?? null, 
        'email' => $data['email'] ?? null, 
        'site_web' => $data['site_web'] ?? null,
        'taille_entreprise' => ($data['taille_entreprise'] ?? '') === '' ? null : $data['taille_entreprise'], 
        'secteur_activite' => $data['secteur_activite'] ?? null, 
        'date_creation' => !empty($data['date_creation']) ? $data['date_creation'] : null
    ];
    
    try {
        beginTransaction();

        if ($id > 0) {
            $affectedRows = updateRow(TABLE_COMPANIES, $dbData, "id = :where_id", ['where_id' => $id]);
            
            if ($affectedRows !== false) {
                logBusinessOperation($_SESSION['user_id'], 'company_update', 
                    "[SUCCESS] Mise à jour entreprise: {$dbData['nom']} (ID: $id)");
                $message = "L'entreprise a ete mise a jour avec succes";
                commitTransaction();
                return ['success' => true, 'message' => $message];
            } else {
                throw new Exception("La mise à jour a échoué ou aucune ligne n'a été modifiée.");
            }
        } 
        else {
            $newId = insertRow(TABLE_COMPANIES, $dbData);
            
            if ($newId) {
                logBusinessOperation($_SESSION['user_id'], 'company_create', 
                    "[SUCCESS] Création entreprise: {$dbData['nom']} (ID: $newId)");
                $message = "L'entreprise a ete creee avec succes";
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
        logSystemActivity('error', "[ERROR] Erreur BDD dans companiesSave: " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/****
 * Supprime une entreprise ainsi que toutes ses données associées (contrats, devis, factures, sites) de manière atomique.
 *
 * Effectue la suppression dans une transaction pour garantir la cohérence des données. Les utilisateurs liés à l'entreprise voient leur champ `entreprise_id` mis à NULL via la contrainte de clé étrangère. Retourne un tableau indiquant le succès ou l'échec de l'opération ainsi qu'un message explicatif.
 *
 * @param int $id Identifiant de l'entreprise à supprimer.
 * @return array Résultat de la suppression avec les clés 'success' (booléen) et 'message' (string).
 */
function companiesDelete($id) {
    
    $companyToDelete = fetchOne(TABLE_COMPANIES, 'id = ?', '', [$id]);
    if (!$companyToDelete) {
        return ['success' => false, 'message' => "Entreprise non trouvée."]; 
    }
    $companyName = $companyToDelete['nom']; 

    try {
        beginTransaction();

        
        
        deleteRow(TABLE_INVOICES, "entreprise_id = ?", [$id]);

        
        
        
        deleteRow(TABLE_QUOTES, "entreprise_id = ?", [$id]);

        
        
        
        deleteRow(TABLE_CONTRACTS, "entreprise_id = ?", [$id]);

        
        
        
        deleteRow('sites', "entreprise_id = ?", [$id]);

        
        
        

        
        
        $deletedRows = deleteRow(TABLE_COMPANIES, "id = ?", [$id]);

        if ($deletedRows > 0) {
            commitTransaction();
            logBusinessOperation($_SESSION['user_id'] ?? 0, 'company_delete',
                "[SUCCESS] Suppression entreprise ID: $id (Nom: $companyName)");
            return [
                'success' => true,
                'message' => "L'entreprise et ses données associées ont été supprimées avec succès"
            ];
        } else {
            
            rollbackTransaction();
            logBusinessOperation($_SESSION['user_id'] ?? 0, 'company_delete_attempt',
                "[ERROR] Tentative échouée de suppression entreprise ID: $id - Entreprise non trouvée lors de la suppression finale.");
            return [
                'success' => false,
                'message' => "Impossible de supprimer l'entreprise (non trouvée ou déjà supprimée lors de l'étape finale)"
            ];
        }
    } catch (PDOException $e) { 
        rollbackTransaction();
        logSystemActivity('error', "[ERROR] Erreur SQL dans companiesDelete (ID: $id, Transaction annulée): " . $e->getMessage() . " | SQL State: " . $e->getCode());
        return [
            'success' => false,
            'message' => "Erreur de base de données lors de la suppression (Transaction annulée): " . $e->getMessage()
        ];
    } catch (Exception $e) { 
        rollbackTransaction();
        logSystemActivity('error', "[ERROR] Erreur générale dans companiesDelete (ID: $id, Transaction annulée): " . $e->getMessage());
        return [
            'success' => false,
            'message' => "Erreur inattendue lors de la suppression (Transaction annulée)."
        ];
    }
}

/**
 * Recupere la liste des villes distinctes des entreprises
 *
 * @return array Liste des villes
 */
function companiesGetCities() {
    $sql = "SELECT DISTINCT ville FROM " . TABLE_COMPANIES . " WHERE ville IS NOT NULL AND ville != '' ORDER BY ville ASC";
    return executeQuery($sql)->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Recupere la liste des tailles d'entreprise possibles
 *
 * @return array Liste des tailles
 */
function companiesGetSizes() {
    return COMPANY_SIZES;
} 