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
    $where = '';
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
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($conditions)) {
        $where = "WHERE " . implode(' AND ', $conditions);
    }

    // recupere les contrats pagines
    $offset = ($page - 1) * $perPage;

    $pdo = getDbConnection();
    $countSql = "SELECT COUNT(c.id) FROM contrats c LEFT JOIN entreprises e ON c.entreprise_id = e.id $where";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalContracts = $countStmt->fetchColumn();
    $totalPages = ceil($totalContracts / $perPage);
    $page = max(1, min($page, $totalPages));

    $sql = "SELECT c.*, e.nom as nom_entreprise 
            FROM contrats c 
            LEFT JOIN entreprises e ON c.entreprise_id = e.id
            $where
            ORDER BY c.date_debut DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $perPage;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT c.*, e.nom as nom_entreprise 
                          FROM contrats c 
                          LEFT JOIN entreprises e ON c.entreprise_id = e.id 
                          WHERE c.id = ?");
    $stmt->execute([$id]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contract) {
        return false;
    }
    
    return $contract;
}

/**
 * Recupere la liste des entreprises pour le formulaire
 * 
 * @return array Liste des entreprises
 */
function contractsGetEntreprises() {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, nom FROM entreprises ORDER BY nom");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Cree ou met a jour un contrat
 * 
 * @param array $data Donnees du contrat
 * @param int $id Identifiant du contrat (0 pour creation)
 * @return array Resultat de l'operation avec status et message
 */
function contractsSave($data, $id = 0) {
    $errors = [];
    
    if (empty($data['entreprise_id'])) {
        $errors[] = "L'entreprise est obligatoire";
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
    
    $pdo = getDbConnection();
    
    // verification que l'entreprise existe
    $stmt = $pdo->prepare("SELECT id FROM entreprises WHERE id = ?");
    $stmt->execute([$data['entreprise_id']]);
    if ($stmt->rowCount() === 0) {
        return [
            'success' => false,
            'errors' => ["L'entreprise selectionnee n'existe pas"]
        ];
    }
    
    try {
        // cas de mise a jour
        if ($id > 0) {
            $sql = "UPDATE contrats SET 
                    entreprise_id = ?, date_debut = ?, date_fin = ?, montant_mensuel = ?, 
                    nombre_salaries = ?, type_contrat = ?, statut = ?, conditions_particulieres = ? 
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['entreprise_id'],
                $data['date_debut'],
                $data['date_fin'],
                $data['montant_mensuel'],
                $data['nombre_salaries'],
                $data['type_contrat'],
                $data['statut'],
                $data['conditions_particulieres'],
                $id
            ]);
            
            $message = "Le contrat a ete mis a jour avec succes";
        } 
        // cas de creation
        else {
            $sql = "INSERT INTO contrats (entreprise_id, date_debut, date_fin, montant_mensuel, 
                    nombre_salaries, type_contrat, statut, conditions_particulieres) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['entreprise_id'],
                $data['date_debut'],
                $data['date_fin'],
                $data['montant_mensuel'],
                $data['nombre_salaries'],
                $data['type_contrat'],
                $data['statut'],
                $data['conditions_particulieres']
            ]);
            
            $message = "Le contrat a ete cree avec succes";
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
    } catch (PDOException $e) {
        $errors[] = "Erreur de base de données : " . $e->getMessage();
        
        // log l'erreur pour l'administrateur
        error_log("Erreur DB dans contracts/index.php : " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/**
 * Supprime un contrat
 * 
 * @param int $id Identifiant du contrat
 * @return array Resultat de l'operation avec status et message
 */
function contractsDelete($id) {
    $pdo = getDbConnection();
    
    // verification que le contrat existe
    $stmt = $pdo->prepare("SELECT id FROM contrats WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        return [
            'success' => false,
            'message' => "Contrat non trouve"
        ];
    }
    
    // suppression du contrat
    $stmt = $pdo->prepare("DELETE FROM contrats WHERE id = ?");
    $stmt->execute([$id]);
    
    return [
        'success' => true,
        'message' => "Le contrat a ete supprime avec succes"
    ];
}

/**
 * Met à jour le statut d'un contrat
 * 
 * @param int $id Identifiant du contrat
 * @param string $status Nouveau statut
 * @return bool Succès de l'opération
 */
function contractsUpdateStatus($id, $status) {
    $validStatuses = ['actif', 'inactif', 'en_attente', 'suspendu', 'expire', 'resilie'];
    
    if (!in_array($status, $validStatuses)) {
        return false;
    }
    
    $result = updateRow('contrats', ['statut' => $status], "id = $id");
    
    if ($result) {
        logActivity($_SESSION['user_id'], 'update_contract_status', "Statut du contrat #$id mis à jour: $status");
    }
    
    return $result;
} 