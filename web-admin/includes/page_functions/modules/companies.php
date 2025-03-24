<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../functions.php';

/**
 * Récupère la liste paginée des entreprises avec filtrage optionnel.
 *
 * Cette fonction renvoie un tableau associatif contenant à la fois la liste des entreprises et les informations de pagination. Un terme de recherche peut être fourni pour filtrer les entreprises par nom, siret ou ville.
 *
 * @param int $page Page à récupérer (défaut : 1).
 * @param int $perPage Nombre d'éléments par page (défaut : 10).
 * @param string $search Terme de recherche pour filtrer les entreprises.
 * @return array Tableau associatif comportant :
 *               - 'companies'  => liste des entreprises,
 *               - 'currentPage' => page actuelle,
 *               - 'totalPages'  => nombre total de pages,
 *               - 'totalItems'  => nombre total d'entreprises correspondant au filtre,
 *               - 'perPage'     => nombre d'éléments par page.
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
 * Récupère les détails d'une entreprise, incluant ses contrats et ses utilisateurs associés.
 *
 * Cette fonction interroge la base de données pour obtenir les informations complètes d'une entreprise identifiée par son identifiant.
 * Si l'entreprise est trouvée, le résultat intègre également une liste de contrats triés par date de début décroissante et une liste d'utilisateurs
 * (avec le nom de leur rôle) triée par nom puis prénom.
 *
 * @param int $id Identifiant unique de l'entreprise.
 * @return array|false Un tableau associatif contenant les informations de l'entreprise et ses relations, ou false si l'entreprise n'est pas trouvée.
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
 * Crée ou met à jour une entreprise dans la base de données.
 *
 * Valide que le nom de l'entreprise est renseigné. En cas de validation échouée, retourne immédiatement un tableau d'erreurs.
 * Si la validation réussit, la fonction met à jour l'entreprise lorsque l'identifiant est supérieur à zéro, ou crée
 * une nouvelle entreprise sinon. En cas d'erreur lors de l'opération sur la base de données, la fonction renvoie
 * un tableau contenant la liste des erreurs.
 *
 * @param array $data Données de l'entreprise à sauvegarder (ex. : 'nom', 'siret', 'adresse', 'code_postal', 'ville', 'telephone', 'email', 'site_web', 'taille_entreprise', 'secteur_activite', 'date_creation').
 * @param int $id Identifiant de l'entreprise (0 pour création, >0 pour mise à jour).
 * @return array Tableau associatif indiquant le résultat de l'opération, contenant soit une clé 'message' en cas de succès,
 *               soit une clé 'errors' en cas d'erreurs.
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
            
            $message = "L'entreprise a ete creee avec succes";
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
    } catch (PDOException $e) {
        $errors[] = "Erreur de base de données : " . $e->getMessage();
        
        // log l'erreur pour l'administrateur
        error_log("Erreur DB dans companies/index.php : " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/**
 * Supprime une entreprise si aucune association n'existe.
 *
 * Cette fonction tente de supprimer une entreprise identifiée par son ID. Elle vérifie d'abord qu'aucune personne ni aucun contrat n'est lié(e) à l'entreprise.
 * Si des associations sont détectées, la suppression est annulée et un message d'erreur approprié est retourné.
 * En l'absence d'associations, l'entreprise est supprimée et un message de confirmation est renvoyé.
 *
 * @param int $id Identifiant unique de l'entreprise à supprimer.
 * @return array Tableau associatif contenant le statut de l'opération sous la clé 'success' et un message descriptif sous la clé 'message'.
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
        return [
            'success' => false,
            'message' => "Impossible de supprimer cette entreprise car elle a des utilisateurs associes"
        ];
    } else if ($contractCount > 0) {
        return [
            'success' => false,
            'message' => "Impossible de supprimer cette entreprise car elle a des contrats associes"
        ];
    } else {
        $stmt = $pdo->prepare("DELETE FROM entreprises WHERE id = ?");
        $stmt->execute([$id]);
        return [
            'success' => true,
            'message' => "L'entreprise a ete supprimee avec succes"
        ];
    }
} 