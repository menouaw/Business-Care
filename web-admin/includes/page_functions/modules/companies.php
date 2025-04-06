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
function companiesGetList($page = 1, $perPage = 10, $search = '', $city = '', $size = '') {
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
    
    $sqlContracts = "SELECT c.* FROM " . TABLE_CONTRACTS . " c WHERE c.entreprise_id = ? ORDER BY c.date_debut DESC";
    $company['contracts'] = executeQuery($sqlContracts, [$id])->fetchAll();
    
    $sqlUsers = "SELECT p.*, r.nom as role_name FROM " . TABLE_USERS . " p LEFT JOIN " . TABLE_ROLES . " r ON p.role_id = r.id WHERE p.entreprise_id = ? ORDER BY p.nom, p.prenom";
    $company['users'] = executeQuery($sqlUsers, [$id])->fetchAll();
    
    return $company;
}

/**
 * Crée ou met à jour une entreprise.
 *
 * Utilise insertRow ou updateRow de db.php.
 *
 * @param array $data Tableau associatif des informations de l'entreprise.
 * @param int $id Identifiant de l'entreprise (0 pour création).
 * @return array Résultat ['success' => bool, 'message' => string|null, 'errors' => array|null]
 */
function companiesSave($data, $id = 0) {
    $errors = [];
    
    if (empty($data['nom'])) {
        $errors[] = "Le nom de l'entreprise est obligatoire";
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
        if ($id > 0) {
            $affectedRows = updateRow(TABLE_COMPANIES, $dbData, "id = :where_id", ['where_id' => $id]);
            
            if ($affectedRows !== false) {
                logBusinessOperation($_SESSION['user_id'], 'company_update', 
                    "[SUCCESS] Mise à jour entreprise: {$dbData['nom']} (ID: $id)");
                $message = "L'entreprise a ete mise a jour avec succes";
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
                return ['success' => true, 'message' => $message, 'newId' => $newId];
            } else {
                 throw new Exception("L'insertion a échoué.");
            }
        }
        
    } catch (Exception $e) {
        $errorMessage = "Erreur de base de données : " . $e->getMessage();
        $errors[] = $errorMessage;
        logSystemActivity('error', "[ERROR] Erreur BDD dans companiesSave: " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/**
 * Supprime une entreprise en vérifiant l'absence d'associations.
 *
 * Utilise executeQuery pour les vérifications et deleteRow pour la suppression.
 *
 * @param int $id L'identifiant de l'entreprise.
 * @return array Résultat ['success' => bool, 'message' => string]
 */
function companiesDelete($id) {
    
    $personCount = executeQuery("SELECT COUNT(id) FROM " . TABLE_USERS . " WHERE entreprise_id = ?", [$id])->fetchColumn();
    
    $contractCount = executeQuery("SELECT COUNT(id) FROM " . TABLE_CONTRACTS . " WHERE entreprise_id = ?", [$id])->fetchColumn();
    
    if ($personCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'company_delete_attempt', 
            "[ERROR] Tentative échouée de suppression d'entreprise ID: $id - Utilisateurs associés existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer cette entreprise car elle a des utilisateurs associes"
        ];
    } 
    
    if ($contractCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'company_delete_attempt', 
            "[ERROR] Tentative échouée de suppression d'entreprise ID: $id - Contrats associés existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer cette entreprise car elle a des contrats associes"
        ];
    }
    
    try {
        $deletedRows = deleteRow(TABLE_COMPANIES, "id = ?", [$id]);
        
        if ($deletedRows > 0) {
            logBusinessOperation($_SESSION['user_id'], 'company_delete', 
                "[SUCCESS] Suppression entreprise ID: $id");
            return [
                'success' => true,
                'message' => "L'entreprise a ete supprimee avec succes"
            ];
        } else {
            logBusinessOperation($_SESSION['user_id'], 'company_delete_attempt', 
                "[ERROR] Tentative échouée de suppression entreprise ID: $id - Entreprise non trouvée?");
            return [
                'success' => false,
                'message' => "Impossible de supprimer l'entreprise (non trouvée ou déjà supprimée)"
            ];
        }
    } catch (Exception $e) {
         logSystemActivity('error', "[ERROR] Erreur BDD dans companiesDelete: " . $e->getMessage());
         return [
            'success' => false,
            'message' => "Erreur de base de données lors de la suppression."
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