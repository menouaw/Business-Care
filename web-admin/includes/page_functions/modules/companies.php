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
    $where = '';
    $params = [];

    if ($search) {
        $where .= " (nom LIKE ? OR siret LIKE ? OR ville LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // recupere les entreprises paginees
    $offset = ($page - 1) * $perPage;

    $pdo = getDbConnection();
    $countSql = "SELECT COUNT(id) FROM entreprises";
    if ($where) {
        $countSql .= " WHERE $where";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCompanies = $countStmt->fetchColumn();
    $totalPages = ceil($totalCompanies / $perPage);
    $page = max(1, min($page, $totalPages));

    $sql = "SELECT * FROM entreprises";
    if ($where) {
        $sql .= " WHERE $where";
    }
    $sql .= " ORDER BY nom ASC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $perPage;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM entreprises WHERE id = ?");
    $stmt->execute([$id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        return false;
    }
    
    // recupere les contrats associes
    $stmt = $pdo->prepare("SELECT c.* FROM contrats c WHERE c.entreprise_id = ? ORDER BY c.date_debut DESC");
    $stmt->execute([$id]);
    $company['contracts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // recupere les utilisateurs associes
    $stmt = $pdo->prepare("SELECT p.*, r.nom as role_name FROM personnes p LEFT JOIN roles r ON p.role_id = r.id WHERE p.entreprise_id = ? ORDER BY p.nom, p.prenom");
    $stmt->execute([$id]);
    $company['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $company;
}

/**
 * Crée ou met à jour une entreprise.
 *
 * Insère une nouvelle entreprise ou modifie une entreprise existante dans la base de données en fonction de l'identifiant fourni.
 * Le tableau $data doit contenir les informations de l'entreprise, avec le champ 'nom' étant obligatoire. En cas d'erreur de validation
 * ou de problème de base de données, la fonction retourne un tableau indiquant l'échec de l'opération ainsi que les messages d'erreur.
 *
 * @param array $data Tableau associatif des informations de l'entreprise.
 * @param int $id Identifiant de l'entreprise (0 pour création, supérieur à 0 pour mise à jour).
 * @return array Tableau contenant le statut de l'opération et un message de confirmation ou une liste d'erreurs.
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
    
    $pdo = getDbConnection();
    
    try {
        // cas de mise a jour
        if ($id > 0) {
            $sql = "UPDATE entreprises SET 
                    nom = ?, siret = ?, adresse = ?, code_postal = ?, ville = ?, 
                    telephone = ?, email = ?, site_web = ?, taille_entreprise = ?, 
                    secteur_activite = ?, date_creation = ? 
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nom'], 
                $data['siret'], 
                $data['adresse'], 
                $data['code_postal'],
                $data['ville'], 
                $data['telephone'], 
                $data['email'], 
                $data['site_web'],
                $data['taille_entreprise'], 
                $data['secteur_activite'], 
                $data['date_creation'], 
                $id
            ]);
            
            logBusinessOperation($_SESSION['user_id'], 'company_update', 
                "Mise à jour entreprise: {$data['nom']} (ID: $id)");
            
            $message = "L'entreprise a ete mise a jour avec succes";
        } 
        // cas de creation
        else {
            $sql = "INSERT INTO entreprises (nom, siret, adresse, code_postal, ville, 
                    telephone, email, site_web, taille_entreprise, secteur_activite, date_creation) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nom'], 
                $data['siret'], 
                $data['adresse'], 
                $data['code_postal'],
                $data['ville'], 
                $data['telephone'], 
                $data['email'], 
                $data['site_web'],
                $data['taille_entreprise'], 
                $data['secteur_activite'], 
                $data['date_creation']
            ]);
            
            $newId = $pdo->lastInsertId();
            logBusinessOperation($_SESSION['user_id'], 'company_create', 
                "Création entreprise: {$data['nom']} (ID: $newId)");
            
            $message = "L'entreprise a ete creee avec succes";
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
    } catch (PDOException $e) {
        $errors[] = "Erreur de base de données : " . $e->getMessage();
        
        // log l'erreur pour l'administrateur
        logSystemActivity('error', "Erreur DB dans companies/index.php : " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/**
 * Supprime une entreprise en vérifiant l'absence d'associations.
 *
 * Cette fonction vérifie d'abord qu'aucun utilisateur ou contrat n'est associé à l'entreprise identifiée par son ID.
 * Si des associations existent, elle loggue une tentative échouée et renvoie un tableau indiquant l'échec de l'opération
 * avec un message explicatif. Sinon, l'entreprise est supprimée, une opération de suppression est logguée,
 * et la fonction renvoie un tableau confirmant la réussite de l'opération.
 *
 * @param int $id L'identifiant de l'entreprise.
 * @return array Un tableau associatif contenant la clé 'success' (booléen) indiquant le résultat de l'opération
 *               et la clé 'message' (string) avec des détails sur le résultat.
 */
function companiesDelete($id) {
    $pdo = getDbConnection();
    
    // verifie si l'entreprise a des personnes associees
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM personnes WHERE entreprise_id = ?");
    $stmt->execute([$id]);
    $personCount = $stmt->fetchColumn();
    
    // verifie si l'entreprise a des contrats associes
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM contrats WHERE entreprise_id = ?");
    $stmt->execute([$id]);
    $contractCount = $stmt->fetchColumn();
    
    if ($personCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'company_delete_attempt', 
            "Tentative échouée de suppression d'entreprise ID: $id - Utilisateurs associés existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer cette entreprise car elle a des utilisateurs associes"
        ];
    } else if ($contractCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'company_delete_attempt', 
            "Tentative échouée de suppression d'entreprise ID: $id - Contrats associés existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer cette entreprise car elle a des contrats associes"
        ];
    } else {
        $stmt = $pdo->prepare("DELETE FROM entreprises WHERE id = ?");
        $stmt->execute([$id]);
        
        logBusinessOperation($_SESSION['user_id'], 'company_delete', 
            "Suppression entreprise ID: $id");
            
        return [
            'success' => true,
            'message' => "L'entreprise a ete supprimee avec succes"
        ];
    }
} 