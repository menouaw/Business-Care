<?php
require_once __DIR__ . '/../../init.php';

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
 * Recupere les details d'un service, et optionnellement les rendez-vous et evaluations associes.
 * 
 * @param int $id Identifiant du service
 * @param bool $fetchRelated Indique s'il faut recuperer les donnees associees (rendez-vous, evaluations)
 * @return array|false Donnees du service (et donnees associees si demande) ou false si non trouve
 */
function servicesGetDetails($id, $fetchRelated = false) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM prestations WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        return false;
    }

    $result = ['service' => $service];

    if ($fetchRelated) {
        // Recupere les rendez-vous associes
        $stmt = $pdo->prepare("SELECT r.*, p.nom as nom_personne, p.prenom as prenom_personne 
                              FROM rendez_vous r 
                              LEFT JOIN personnes p ON r.personne_id = p.id 
                              WHERE r.prestation_id = ? 
                              ORDER BY r.date_rdv DESC");
        $stmt->execute([$id]);
        $result['appointments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT e.*, p.nom as nom_personne, p.prenom as prenom_personne 
                              FROM evaluations e 
                              LEFT JOIN personnes p ON e.personne_id = p.id 
                              WHERE e.prestation_id = ? 
                              ORDER BY e.date_evaluation DESC");
        $stmt->execute([$id]);
        $result['evaluations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $result;
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
 * Crée ou met à jour un service dans la base de données.
 *
 * Cette fonction vérifie que les données obligatoires (nom, prix et type) sont présentes et valides.
 * En cas d'erreur de validation, elle retourne un tableau contenant les messages d'erreur.
 * Si les validations réussissent, elle effectue une mise à jour si un identifiant supérieur à 0 est fourni,
 * ou crée un nouveau service sinon. En cas d'échec de l'opération en base de données, un tableau d'erreurs est retourné.
 *
 * @param array $data Les informations du service, incluant notamment 'nom', 'prix', 'type', et d'autres champs optionnels.
 * @param int $id L'identifiant du service à mettre à jour, ou 0 pour créer un nouveau service.
 * @return array Tableau associatif indiquant le succès de l'opération. En cas de succès, il contient une clé 'message';
 *               en cas d'erreur, une clé 'errors' avec la liste des messages d'erreur.
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
 * Gère la soumission du formulaire d'ajout/modification de service.
 *
 * Valide le token CSRF, prépare les données, et appelle servicesSave.
 * Retourne un tableau avec le résultat de l'opération (succès, message/erreurs).
 *
 * @param array $postData Données du formulaire ($_POST)
 * @param int $id ID du service (0 pour ajout)
 * @return array Résultat de l'opération ['success' => bool, 'message' => string|null, 'errors' => array|null]
 */
function servicesHandlePostRequest($postData, $id) {
    if (!validateToken($postData['csrf_token'] ?? '')) {
        return [
            'success' => false,
            'errors' => ["Erreur de sécurité, veuillez réessayer."]
        ];
    }
    
    $data = [
        'nom' => $postData['nom'] ?? '',
        'description' => $postData['description'] ?? '',
        'prix' => $postData['prix'] ?? '',
        'duree' => $postData['duree'] ?? null,
        'type' => $postData['type'] ?? '',
        'categorie' => $postData['categorie'] ?? null,
        'statut' => 'actif', // Statut par défaut
        'niveau_difficulte' => $postData['niveau_difficulte'] ?? null,
        'capacite_max' => $postData['capacite_max'] ?? null,
        'materiel_necessaire' => $postData['materiel_necessaire'] ?? null,
        'prerequis' => $postData['prerequis'] ?? null
    ];

    return servicesSave($data, $id);
}

/**
 * Supprime un service après vérification des dépendances associées.
 *
 * Cette fonction tente de supprimer un service identifié par son ID, après avoir vérifié
 * qu'il n'a aucun rendez-vous ou évaluation associé. Si des dépendances sont présentes,
 * la suppression est annulée et une opération d'échec est consignée. Sinon, le service
 * est supprimé et l'opération de suppression est enregistrée.
 *
 * @param int $id L'identifiant du service à supprimer.
 * @return array Un tableau associatif contenant un booléen sous la clé 'success' indiquant
 *               si l'opération a réussi et un message descriptif sous la clé 'message'.
 */
function servicesDelete($id) {
    $pdo = getDbConnection();
    
    // Verifie si le service a des rendez-vous associes
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM rendez_vous WHERE prestation_id = ?");
    $stmt->execute([$id]);
    $appointmentCount = $stmt->fetchColumn();
    
    // Verifie si le service a des evaluations associees
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
    } 
    
    if ($evaluationCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'service_delete_attempt', 
            "Tentative échouée de suppression service ID: $id - Évaluations associées existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer ce service car il a des evaluations associees"
        ];
    }
    
    // Si aucune dépendance, procéder à la suppression
    try {
        $stmt = $pdo->prepare("DELETE FROM prestations WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            logBusinessOperation($_SESSION['user_id'], 'service_delete', 
                "Suppression service ID: $id");
            return [
                'success' => true,
                'message' => "Le service a ete supprime avec succes"
            ];
        } else {
            // Cas où l'ID n'existe pas (ou autre erreur non PDOException)
             logBusinessOperation($_SESSION['user_id'], 'service_delete_attempt', 
                "Tentative échouée de suppression service ID: $id - Service non trouvé?");
            return [
                'success' => false,
                'message' => "Impossible de supprimer le service (peut-être déjà supprimé?)"
            ];
        }
    } catch (PDOException $e) {
         logSystemActivity('error', "Erreur BDD dans servicesDelete: " . $e->getMessage());
         return [
            'success' => false,
            'message' => "Erreur de base de données lors de la suppression."
         ];
    }
} 