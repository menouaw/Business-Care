<?php
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../../../shared/web-admin/logging.php';

/**
 * Recupere la liste des services avec pagination et filtrage
 * 
 * @param int $page Numero de la page
 * @param int $perPage Nombre d'elements par page
 * @param string $search Terme de recherche
 * @param string $type Filtre par type
 * @return array Donnees de pagination et liste des services
 */
function servicesGetList($page = 1, $perPage = 10, $search = '', $type = '') {
    $where = '';
    $params = [];
    $conditions = [];

    if ($search) {
        $conditions[] = "(nom LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($type) {
        $conditions[] = "type = ?";
        $params[] = $type;
    }
    
    if (!empty($conditions)) {
        $where = "WHERE " . implode(' AND ', $conditions);
    }

    // recupere les services pagines
    $offset = ($page - 1) * $perPage;

    $pdo = getDbConnection();
    $countSql = "SELECT COUNT(id) FROM prestations $where";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalServices = $countStmt->fetchColumn();
    $totalPages = ceil($totalServices / $perPage);
    $page = max(1, min($page, $totalPages));

    $sql = "SELECT * FROM prestations $where ORDER BY nom ASC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $perPage;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'services' => $services,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalServices,
        'perPage' => $perPage
    ];
}

/**
 * Recupere les details d'un service
 * 
 * @param int $id Identifiant du service
 * @return array|false Donnees du service ou false si non trouve
 */
function servicesGetDetails($id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM prestations WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        return false;
    }
    
    return $service;
}

/**
 * Recupere la liste des types de services
 * 
 * @return array Liste des types
 */
function servicesGetTypes() {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT DISTINCT type FROM prestations ORDER BY type");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Cree ou met a jour un service
 * 
 * @param array $data Donnees du service
 * @param int $id Identifiant du service (0 pour creation)
 * @return array Resultat de l'operation avec status et message
 */
function servicesSave($data, $id = 0) {
    $errors = [];
    
    if (empty($data['nom'])) {
        $errors[] = "Le nom du service est obligatoire";
    }
    
    if (empty($data['prix']) || !is_numeric($data['prix'])) {
        $errors[] = "Le prix du service est obligatoire et doit etre un nombre";
    }
    
    if (empty($data['type'])) {
        $errors[] = "Le type de service est obligatoire";
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
            $sql = "UPDATE prestations SET 
                    nom = ?, description = ?, prix = ?, duree = ?, 
                    type = ?, categorie = ?, niveau_difficulte = ?, 
                    capacite_max = ?, materiel_necessaire = ?, prerequis = ? 
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nom'], 
                $data['description'], 
                $data['prix'],
                $data['duree'],
                $data['type'],
                $data['categorie'],
                $data['niveau_difficulte'],
                $data['capacite_max'],
                $data['materiel_necessaire'],
                $data['prerequis'],
                $id
            ]);
            
            logBusinessOperation($_SESSION['user_id'], 'service_update', 
                "Mise à jour service: {$data['nom']} (ID: $id), type: {$data['type']}, prix: {$data['prix']}€");
            
            $message = "Le service a ete mis a jour avec succes";
        } 
        // cas de creation
        else {
            $sql = "INSERT INTO prestations (nom, description, prix, duree, type, 
                   categorie, niveau_difficulte, capacite_max, materiel_necessaire, prerequis) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nom'], 
                $data['description'], 
                $data['prix'],
                $data['duree'],
                $data['type'],
                $data['categorie'],
                $data['niveau_difficulte'],
                $data['capacite_max'],
                $data['materiel_necessaire'],
                $data['prerequis']
            ]);
            
            $newId = $pdo->lastInsertId();
            logBusinessOperation($_SESSION['user_id'], 'service_create', 
                "Création service: {$data['nom']} (ID: $newId), type: {$data['type']}, prix: {$data['prix']}€");
            
            $message = "Le service a ete cree avec succes";
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
    } catch (PDOException $e) {
        $errors[] = "Erreur de base de données : " . $e->getMessage();
        
        // log l'erreur pour l'administrateur
        logSystemActivity('error', "Erreur BDD dans services/index.php : " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/**
 * Supprime un service si possible
 * 
 * @param int $id Identifiant du service
 * @return array Resultat de l'operation avec status et message
 */
function servicesDelete($id) {
    $pdo = getDbConnection();
    
    // verifie si le service a des rendez-vous associes
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM rendez_vous WHERE prestation_id = ?");
    $stmt->execute([$id]);
    $appointmentCount = $stmt->fetchColumn();
    
    // verifie si le service a des evaluations associees
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM evaluations WHERE prestation_id = ?");
    $stmt->execute([$id]);
    $evaluationCount = $stmt->fetchColumn();
    
    if ($appointmentCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'service_delete_attempt', 
            "Tentative échouée de suppression service ID: $id - Rendez-vous associés existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer ce service car il a des rendez-vous associes"
        ];
    } else if ($evaluationCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'service_delete_attempt', 
            "Tentative échouée de suppression service ID: $id - Évaluations associées existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer ce service car il a des evaluations associees"
        ];
    } else {
        $stmt = $pdo->prepare("DELETE FROM prestations WHERE id = ?");
        $stmt->execute([$id]);
        
        logBusinessOperation($_SESSION['user_id'], 'service_delete', 
            "Suppression service ID: $id");
            
        return [
            'success' => true,
            'message' => "Le service a ete supprime avec succes"
        ];
    }
} 