<?php
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../../../shared/web-admin/logging.php';

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
 * Crée ou met à jour un contrat en fonction des données fournies.
 *
 * Cette fonction valide les champs obligatoires (entreprise_id, date_debut, type_contrat) et s'assure, le cas échéant, que la date de fin n'est pas antérieure à la date de début. Elle vérifie également l'existence de l'entreprise concernée avant d'effectuer une mise à jour (si l'identifiant du contrat est supérieur à 0) ou une création (si l'identifiant est 0). En cas de succès, la fonction retourne un tableau avec 'success' à true et un message associé. Sinon, elle retourne 'success' à false avec une liste d'erreurs, notamment pour les problèmes de validation ou les exceptions en base de données.
 *
 * @param array $data Données du contrat, incluant au minimum l'identifiant de l'entreprise, la date de début, le type de contrat, et éventuellement d'autres informations (date de fin, montant mensuel, nombre de salariés, statut, conditions particulières).
 * @param int $id Identifiant du contrat à mettre à jour ou 0 pour créer un nouveau contrat.
 * @return array Tableau associatif contenant une clé 'success' (bool) et, selon le cas, une clé 'message' (en cas de succès) ou 'errors' (en cas d'erreur).
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
            
            logBusinessOperation($_SESSION['user_id'], 'contract_update', 
                "Mise à jour contrat ID: $id, entreprise ID: {$data['entreprise_id']}, type: {$data['type_contrat']}");
            
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
            
            $newId = $pdo->lastInsertId();
            logBusinessOperation($_SESSION['user_id'], 'contract_create', 
                "Création contrat ID: $newId, entreprise ID: {$data['entreprise_id']}, type: {$data['type_contrat']}");
            
            $message = "Le contrat a ete cree avec succes";
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
    } catch (PDOException $e) {
        $errors[] = "Erreur de base de données : " . $e->getMessage();
        
        // log l'erreur pour l'administrateur
        logSystemActivity('error', "Erreur BDD dans contracts/index.php : " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/**
 * Supprime un contrat de la base de données.
 *
 * Cette fonction vérifie si le contrat correspondant à l'identifiant fourni existe. 
 * Si le contrat n'est pas trouvé, l'opération est loguée en tant qu'échec et un message d'erreur est retourné. 
 * Sinon, le contrat est supprimé, l'opération est loguée et un message de confirmation est renvoyé.
 *
 * @param int $id Identifiant du contrat à supprimer
 * @return array Tableau associatif contenant un booléen 'success' et un message descriptif de l'opération.
 */
function contractsDelete($id) {
    $pdo = getDbConnection();
    
    // verification que le contrat existe
    $stmt = $pdo->prepare("SELECT id FROM contrats WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        logBusinessOperation($_SESSION['user_id'], 'contract_delete_attempt', 
            "Tentative échouée de suppression de contrat inexistant ID: $id");
        return [
            'success' => false,
            'message' => "Contrat non trouve"
        ];
    }
    
    // suppression du contrat
    $stmt = $pdo->prepare("DELETE FROM contrats WHERE id = ?");
    $stmt->execute([$id]);
    
    logBusinessOperation($_SESSION['user_id'], 'contract_delete', 
        "Suppression contrat ID: $id");
    
    return [
        'success' => true,
        'message' => "Le contrat a ete supprime avec succes"
    ];
}

/**
 * Met à jour le statut d'un contrat.
 *
 * Vérifie que le nouveau statut est valide parmi les valeurs autorisées ("actif", "inactif", "en_attente", "suspendu", "expire", "resilie"). Si le statut n'est pas valide, l'opération est annulée et la fonction retourne false. En cas de succès, le contrat est mis à jour en base et l'opération est consignée.
 *
 * @param int $id Identifiant du contrat.
 * @param string $status Nouveau statut du contrat.
 * @return bool True si la mise à jour a été effectuée avec succès, false sinon.
 */
function contractsUpdateStatus($id, $status) {
    $validStatuses = ['actif', 'inactif', 'en_attente', 'suspendu', 'expire', 'resilie'];
    
    if (!in_array($status, $validStatuses)) {
        logBusinessOperation($_SESSION['user_id'], 'contract_status_update_attempt', 
            "Tentative échouée de mise à jour de statut contrat ID: $id avec valeur invalide: $status");
        return false;
    }
    
    $result = updateRow('contrats', ['statut' => $status], "id = $id");
    
    if ($result) {
        logBusinessOperation($_SESSION['user_id'], 'contract_status_update', 
            "Mise à jour statut contrat ID: $id - Nouveau statut: $status");
    }
    
    return $result;
} 