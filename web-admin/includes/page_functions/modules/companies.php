<?php
require_once __DIR__ . '/../../init.php';

/**
 * Recupere la liste des entreprises avec pagination et filtrage
 * 
 * @param int $page Numero de la page
 * @param int $perPage Nombre d'elements par page
 * @param string $search Terme de recherche
 * @return array Donnees de pagination et liste des entreprises
 */
function companiesGetList($page = 1, $perPage = 10, $search = '') {
    $whereClauses = [];
    $params = [];

    if ($search) {
        $whereClauses[] = "(nom LIKE ? OR siret LIKE ? OR ville LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    $whereSql = !empty($whereClauses) ? implode(' AND ', $whereClauses) : '1';


    $countSql = "SELECT COUNT(id) FROM entreprises WHERE {$whereSql}";
    $totalCompanies = executeQuery($countSql, $params)->fetchColumn();
    
    $totalPages = ceil($totalCompanies / $perPage);
    $page = max(1, min($page, $totalPages)); 
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT * FROM entreprises WHERE {$whereSql} ORDER BY nom ASC LIMIT ?, ?";
    $paramsWithPagination = array_merge($params, [$offset, $perPage]);

    $companies = executeQuery($sql, $paramsWithPagination)->fetchAll();

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
    $company = executeQuery("SELECT * FROM entreprises WHERE id = ? LIMIT 1", [$id])->fetch();
    
    if (!$company) {
        return false;
    }
    
    // recupere les contrats associes
    $sqlContracts = "SELECT c.* FROM contrats c WHERE c.entreprise_id = ? ORDER BY c.date_debut DESC";
    $company['contracts'] = executeQuery($sqlContracts, [$id])->fetchAll();
    
    // recupere les utilisateurs associes
    $sqlUsers = "SELECT p.*, r.nom as role_name FROM personnes p LEFT JOIN roles r ON p.role_id = r.id WHERE p.entreprise_id = ? ORDER BY p.nom, p.prenom";
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

    // Prepare data for DB insertion/update
    $dbData = [
        'nom' => $data['nom'], 
        'siret' => $data['siret'] ?? null, 
        'adresse' => $data['adresse'] ?? null, 
        'code_postal' => $data['code_postal'] ?? null,
        'ville' => $data['ville'] ?? null, 
        'telephone' => $data['telephone'] ?? null, 
        'email' => $data['email'] ?? null, 
        'site_web' => $data['site_web'] ?? null,
        'taille_entreprise' => $data['taille_entreprise'] ?? null, 
        'secteur_activite' => $data['secteur_activite'] ?? null, 
        'date_creation' => !empty($data['date_creation']) ? $data['date_creation'] : null // Ensure null if empty
    ];
    
    try {
        // Cas de mise a jour
        if ($id > 0) {
            $affectedRows = updateRow('entreprises', $dbData, "id = ?", [$id]);
            
            if ($affectedRows !== false) {
                logBusinessOperation($_SESSION['user_id'], 'company_update', 
                    "[SUCCESS] Mise à jour entreprise: {$dbData['nom']} (ID: $id)");
                $message = "L'entreprise a ete mise a jour avec succes";
                return ['success' => true, 'message' => $message];
            } else {
                throw new Exception("La mise à jour a échoué ou aucune ligne n'a été modifiée.");
            }
        } 
        // Cas de creation
        else {
            $newId = insertRow('entreprises', $dbData);
            
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
    
    // Verifie les personnes associees
    $personCount = executeQuery("SELECT COUNT(id) FROM personnes WHERE entreprise_id = ?", [$id])->fetchColumn();
    
    // Verifie les contrats associes
    $contractCount = executeQuery("SELECT COUNT(id) FROM contrats WHERE entreprise_id = ?", [$id])->fetchColumn();
    
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
    
    // Suppression
    try {
        $deletedRows = deleteRow('entreprises', "id = ?", [$id]);
        
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