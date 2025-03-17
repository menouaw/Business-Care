<?php
// module de gestion des contrats

// traitement de la requete selon la methode
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($id)) {
            // recuperer un contrat specifique
            getContract($id);
        } else {
            // recuperer tous les contrats
            getContracts();
        }
        break;
    case 'POST':
        // creer un nouveau contrat
        createContract();
        break;
    case 'PUT':
        // mettre a jour un contrat existant
        if (isset($id)) {
            updateContract($id);
        } else {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'id du contrat requis pour la mise a jour'
            ]);
        }
        break;
    case 'DELETE':
        // supprimer un contrat
        if (isset($id)) {
            deleteContract($id);
        } else {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'id du contrat requis pour la suppression'
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

// fonction pour recuperer tous les contrats
function getContracts() {
    global $db;
    
    try {
        // recuperer les parametres de filtrage optionnels
        $statut = isset($_GET['statut']) ? $_GET['statut'] : null;
        $entreprise_id = isset($_GET['entreprise_id']) ? $_GET['entreprise_id'] : null;
        
        $query = "SELECT c.*, e.nom as nom_entreprise 
                 FROM contrats c 
                 LEFT JOIN entreprises e ON c.entreprise_id = e.id";
        
        $params = [];
        $conditions = [];
        
        if ($statut) {
            $conditions[] = "c.statut = :statut";
            $params[':statut'] = $statut;
        }
        
        if ($entreprise_id) {
            $conditions[] = "c.entreprise_id = :entreprise_id";
            $params[':entreprise_id'] = $entreprise_id;
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $query .= " ORDER BY c.date_debut DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'error' => false,
            'contracts' => $contracts
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la recuperation des contrats: ' . $e->getMessage()
        ]);
    }
}

// fonction pour recuperer un contrat specifique
function getContract($id) {
    global $db;
    
    try {
        $query = "SELECT c.*, e.nom as nom_entreprise 
                 FROM contrats c 
                 LEFT JOIN entreprises e ON c.entreprise_id = e.id 
                 WHERE c.id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $contract = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($contract) {
            http_response_code(200);
            echo json_encode([
                'error' => false,
                'contract' => $contract
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'contrat non trouve'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la recuperation du contrat: ' . $e->getMessage()
        ]);
    }
}

// fonction pour creer un nouveau contrat
function createContract() {
    global $db;
    
    // recuperer les donnees du corps de la requete
    $data = json_decode(file_get_contents('php://input'), true);
    
    // validation des champs requis
    if (!isset($data['entreprise_id']) || !is_numeric($data['entreprise_id'])) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'l\'id de l\'entreprise est requis et doit etre un nombre'
        ]);
        return;
    }
    
    if (!isset($data['date_debut']) || empty(trim($data['date_debut']))) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'la date de debut du contrat est requise'
        ]);
        return;
    }
    
    if (!isset($data['type_contrat']) || empty(trim($data['type_contrat']))) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'le type de contrat est requis'
        ]);
        return;
    }
    
    try {
        // verifier si l'entreprise existe
        $query = "SELECT id FROM entreprises WHERE id = :entreprise_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':entreprise_id', $data['entreprise_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'entreprise non trouvee'
            ]);
            return;
        }
        
        // inserer le nouveau contrat
        $query = "INSERT INTO contrats (entreprise_id, date_debut, date_fin, montant_mensuel, 
                 nombre_salaries, type_contrat, statut, conditions_particulieres) 
                 VALUES (:entreprise_id, :date_debut, :date_fin, :montant_mensuel, 
                 :nombre_salaries, :type_contrat, :statut, :conditions_particulieres)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':entreprise_id', $data['entreprise_id'], PDO::PARAM_INT);
        $stmt->bindParam(':date_debut', $data['date_debut'], PDO::PARAM_STR);
        $stmt->bindParam(':date_fin', $data['date_fin'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':montant_mensuel', $data['montant_mensuel'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':nombre_salaries', $data['nombre_salaries'] ?? null, PDO::PARAM_INT);
        $stmt->bindParam(':type_contrat', $data['type_contrat'], PDO::PARAM_STR);
        $stmt->bindParam(':statut', $data['statut'] ?? 'actif', PDO::PARAM_STR);
        $stmt->bindParam(':conditions_particulieres', $data['conditions_particulieres'] ?? null, PDO::PARAM_STR);
        
        $stmt->execute();
        $contractId = $db->lastInsertId();
        
        http_response_code(201);
        echo json_encode([
            'error' => false,
            'message' => 'contrat cree avec succes',
            'contract_id' => $contractId
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la creation du contrat: ' . $e->getMessage()
        ]);
    }
}

// fonction pour mettre a jour un contrat existant
function updateContract($id) {
    global $db;
    
    // recuperer les donnees du corps de la requete
    $data = json_decode(file_get_contents('php://input'), true);
    
    // verifier si le contrat existe
    try {
        $query = "SELECT id FROM contrats WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'contrat non trouve'
            ]);
            return;
        }
        
        // construire la requete de mise a jour
        $updateFields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'entreprise_id', 'date_debut', 'date_fin', 'montant_mensuel', 
            'nombre_salaries', 'type_contrat', 'statut', 'conditions_particulieres'
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
        
        // si entreprise_id est fourni, verifier si l'entreprise existe
        if (isset($data['entreprise_id'])) {
            $query = "SELECT id FROM entreprises WHERE id = :entreprise_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':entreprise_id', $data['entreprise_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode([
                    'error' => true,
                    'message' => 'entreprise non trouvee'
                ]);
                return;
            }
        }
        
        $query = "UPDATE contrats SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        http_response_code(200);
        echo json_encode([
            'error' => false,
            'message' => 'contrat mis a jour avec succes'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la mise a jour du contrat: ' . $e->getMessage()
        ]);
    }
}

// fonction pour supprimer un contrat
function deleteContract($id) {
    global $db;
    
    try {
        // verifier d'abord si le contrat existe
        $query = "SELECT id FROM contrats WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'contrat non trouve'
            ]);
            return;
        }
        
        // supprimer le contrat
        $query = "DELETE FROM contrats WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        http_response_code(200);
        echo json_encode([
            'error' => false,
            'message' => 'contrat supprime avec succes'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la suppression du contrat: ' . $e->getMessage()
        ]);
    }
} 