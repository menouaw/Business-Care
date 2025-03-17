<?php
// module de gestion des prestations

// traitement de la requete selon la methode
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($id)) {
            // recuperer une prestation specifique
            getService($id);
        } else {
            // recuperer toutes les prestations
            getServices();
        }
        break;
    case 'POST':
        // creer une nouvelle prestation
        createService();
        break;
    case 'PUT':
        // mettre a jour une prestation existante
        if (isset($id)) {
            updateService($id);
        } else {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'id de la prestation requis pour la mise a jour'
            ]);
        }
        break;
    case 'DELETE':
        // supprimer une prestation
        if (isset($id)) {
            deleteService($id);
        } else {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'id de la prestation requis pour la suppression'
            ]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode([
            'error' => true,
            'message' => 'methode non autorisee'
        ]);
        break;
}

// fonction pour recuperer toutes les prestations
function getServices() {
    global $db;
    
    try {
        $query = "SELECT * FROM prestations ORDER BY nom ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'error' => false,
            'services' => $services
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la recuperation des prestations: ' . $e->getMessage()
        ]);
    }
}

// fonction pour recuperer une prestation specifique
function getService($id) {
    global $db;
    
    try {
        $query = "SELECT * FROM prestations WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($service) {
            http_response_code(200);
            echo json_encode([
                'error' => false,
                'service' => $service
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'prestation non trouvee'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la recuperation de la prestation: ' . $e->getMessage()
        ]);
    }
}

// fonction pour creer une nouvelle prestation
function createService() {
    global $db;
    
    // recuperer les donnees du corps de la requete
    $data = json_decode(file_get_contents('php://input'), true);
    
    // validation des champs requis
    if (!isset($data['nom']) || empty(trim($data['nom']))) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'le nom de la prestation est requis'
        ]);
        return;
    }
    
    if (!isset($data['prix']) || !is_numeric($data['prix'])) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'le prix de la prestation est requis et doit etre un nombre'
        ]);
        return;
    }
    
    if (!isset($data['type']) || empty(trim($data['type']))) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'le type de la prestation est requis'
        ]);
        return;
    }
    
    try {
        $query = "INSERT INTO prestations (nom, description, prix, duree, type, categorie, 
                niveau_difficulte, capacite_max, materiel_necessaire, prerequis) 
                VALUES (:nom, :description, :prix, :duree, :type, :categorie, 
                :niveau_difficulte, :capacite_max, :materiel_necessaire, :prerequis)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nom', $data['nom'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':prix', $data['prix'], PDO::PARAM_STR);
        $stmt->bindParam(':duree', $data['duree'] ?? null, PDO::PARAM_INT);
        $stmt->bindParam(':type', $data['type'], PDO::PARAM_STR);
        $stmt->bindParam(':categorie', $data['categorie'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':niveau_difficulte', $data['niveau_difficulte'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':capacite_max', $data['capacite_max'] ?? null, PDO::PARAM_INT);
        $stmt->bindParam(':materiel_necessaire', $data['materiel_necessaire'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':prerequis', $data['prerequis'] ?? null, PDO::PARAM_STR);
        
        $stmt->execute();
        $serviceId = $db->lastInsertId();
        
        http_response_code(201);
        echo json_encode([
            'error' => false,
            'message' => 'prestation creee avec succes',
            'service_id' => $serviceId
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la creation de la prestation: ' . $e->getMessage()
        ]);
    }
}

// fonction pour mettre a jour une prestation existante
function updateService($id) {
    global $db;
    
    // recuperer les donnees du corps de la requete
    $data = json_decode(file_get_contents('php://input'), true);
    
    // verifier si la prestation existe
    try {
        $query = "SELECT id FROM prestations WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'prestation non trouvee'
            ]);
            return;
        }
        
        // construire la requete de mise a jour
        $updateFields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'nom', 'description', 'prix', 'duree', 'type', 'categorie', 
            'niveau_difficulte', 'capacite_max', 'materiel_necessaire', 'prerequis'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'aucun champ fourni pour la mise a jour'
            ]);
            return;
        }
        
        $query = "UPDATE prestations SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        http_response_code(200);
        echo json_encode([
            'error' => false,
            'message' => 'prestation mise a jour avec succes'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la mise a jour de la prestation: ' . $e->getMessage()
        ]);
    }
}

// fonction pour supprimer une prestation
function deleteService($id) {
    global $db;
    
    try {
        // verifier si la prestation a des rendez-vous associes
        $query = "SELECT COUNT(*) as count FROM rendez_vous WHERE prestation_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'impossible de supprimer la prestation car elle a des rendez-vous associes'
            ]);
            return;
        }
        
        // verifier si la prestation a des evaluations associees
        $query = "SELECT COUNT(*) as count FROM evaluations WHERE prestation_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'impossible de supprimer la prestation car elle a des evaluations associees'
            ]);
            return;
        }
        
        // supprimer la prestation
        $query = "DELETE FROM prestations WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode([
                'error' => false,
                'message' => 'prestation supprimee avec succes'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'prestation non trouvee'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la suppression de la prestation: ' . $e->getMessage()
        ]);
    }
} 