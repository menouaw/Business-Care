<?php
// module de gestion des entreprises

// traitement de la requete selon la methode
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($id)) {
            // recuperer une entreprise specifique
            getCompany($id);
        } else {
            // recuperer toutes les entreprises
            getCompanies();
        }
        break;
    case 'POST':
        // creer une nouvelle entreprise
        createCompany();
        break;
    case 'PUT':
        // mettre a jour une entreprise existante
        if (isset($id)) {
            updateCompany($id);
        } else {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'id de l\'entreprise requis pour la mise a jour'
            ]);
        }
        break;
    case 'DELETE':
        // supprimer une entreprise
        if (isset($id)) {
            deleteCompany($id);
        } else {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'id de l\'entreprise requis pour la suppression'
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

// fonction pour recuperer toutes les entreprises
function getCompanies() {
    global $db;
    
    try {
        $query = "SELECT * FROM entreprises ORDER BY nom ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'error' => false,
            'companies' => $companies
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la recuperation des entreprises: ' . $e->getMessage()
        ]);
    }
}

// fonction pour recuperer une entreprise specifique
function getCompany($id) {
    global $db;
    
    try {
        $query = "SELECT * FROM entreprises WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($company) {
            http_response_code(200);
            echo json_encode([
                'error' => false,
                'company' => $company
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'entreprise non trouvee'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la recuperation de l\'entreprise: ' . $e->getMessage()
        ]);
    }
}

// fonction pour creer une nouvelle entreprise
function createCompany() {
    global $db;
    
    // recuperer les donnees du corps de la requete
    $data = json_decode(file_get_contents('php://input'), true);
    
    // validation des champs requis
    if (!isset($data['nom']) || empty(trim($data['nom']))) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'le nom de l\'entreprise est requis'
        ]);
        return;
    }
    
    try {
        $query = "INSERT INTO entreprises (nom, siret, adresse, code_postal, ville, telephone, 
                 email, site_web, logo_url, taille_entreprise, secteur_activite, date_creation) 
                 VALUES (:nom, :siret, :adresse, :code_postal, :ville, :telephone, 
                 :email, :site_web, :logo_url, :taille_entreprise, :secteur_activite, :date_creation)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nom', $data['nom'], PDO::PARAM_STR);
        $stmt->bindParam(':siret', $data['siret'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':adresse', $data['adresse'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':code_postal', $data['code_postal'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':ville', $data['ville'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':telephone', $data['telephone'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':email', $data['email'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':site_web', $data['site_web'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':logo_url', $data['logo_url'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':taille_entreprise', $data['taille_entreprise'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':secteur_activite', $data['secteur_activite'] ?? null, PDO::PARAM_STR);
        $stmt->bindParam(':date_creation', $data['date_creation'] ?? null, PDO::PARAM_STR);
        
        $stmt->execute();
        $companyId = $db->lastInsertId();
        
        http_response_code(201);
        echo json_encode([
            'error' => false,
            'message' => 'entreprise creee avec succes',
            'company_id' => $companyId
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la creation de l\'entreprise: ' . $e->getMessage()
        ]);
    }
}

// fonction pour mettre a jour une entreprise existante
function updateCompany($id) {
    global $db;
    
    // recuperer les donnees du corps de la requete
    $data = json_decode(file_get_contents('php://input'), true);
    
    // verifier si l'entreprise existe
    try {
        $query = "SELECT id FROM entreprises WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'entreprise non trouvee'
            ]);
            return;
        }
        
        // construire la requete de mise a jour
        $updateFields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'nom', 'siret', 'adresse', 'code_postal', 'ville', 'telephone', 
            'email', 'site_web', 'logo_url', 'taille_entreprise', 'secteur_activite', 'date_creation'
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
        
        $query = "UPDATE entreprises SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        http_response_code(200);
        echo json_encode([
            'error' => false,
            'message' => 'entreprise mise a jour avec succes'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la mise a jour de l\'entreprise: ' . $e->getMessage()
        ]);
    }
}

// fonction pour supprimer une entreprise
function deleteCompany($id) {
    global $db;
    
    try {
        // verifier si l'entreprise a des personnes associees
        $query = "SELECT COUNT(*) as count FROM personnes WHERE entreprise_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'impossible de supprimer l\'entreprise car elle a des personnes associees'
            ]);
            return;
        }
        
        // verifier si l'entreprise a des contrats associes
        $query = "SELECT COUNT(*) as count FROM contrats WHERE entreprise_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'impossible de supprimer l\'entreprise car elle a des contrats associes'
            ]);
            return;
        }
        
        // supprimer l'entreprise
        $query = "DELETE FROM entreprises WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode([
                'error' => false,
                'message' => 'entreprise supprimee avec succes'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'entreprise non trouvee'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'erreur lors de la suppression de l\'entreprise: ' . $e->getMessage()
        ]);
    }
} 