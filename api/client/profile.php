<?php

if (!$isAuthenticated) {
    http_response_code(401);
    echo json_encode([
        'error' => true,
        'message' => 'Authentification requise'
    ]);
    exit;
}

$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
if (!$clientId) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => 'ID client manquant'
    ]);
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        getClientProfile($clientId);
        break;
        
    case 'PUT':
        updateClientProfile($clientId);
        break;
        
    case 'OPTIONS':
        http_response_code(200);
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'error' => true,
            'message' => 'Méthode non autorisée'
        ]);
        break;
}

function getClientProfile($clientId) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare("SELECT p.*, r.nom as role_nom
                              FROM personnes p 
                              JOIN roles r ON p.role_id = r.id
                              WHERE p.id = ? AND p.role_id = 3");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Client non trouvé'
            ]);
            return;
        }
        
        unset($client['mot_de_passe']);
        
        $company = null;
        if ($client['entreprise_id']) {
            $stmt = $pdo->prepare("SELECT * FROM entreprises WHERE id = ?");
            $stmt->execute([$client['entreprise_id']]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        $preferences = json_decode($client['preferences'] ?? '{}', true);
        
        echo json_encode([
            'error' => false,
            'client' => $client,
            'company' => $company,
            'preferences' => $preferences
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Erreur lors de la récupération du profil: ' . $e->getMessage()
        ]);
    }
}

function updateClientProfile($clientId) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM personnes WHERE id = ? AND role_id = 3");
        $stmt->execute([$clientId]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Client non trouvé'
            ]);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Aucune donnée fournie'
            ]);
            return;
        }
        
        $allowedFields = [
            'nom', 'prenom', 'email', 'telephone', 'adresse', 
            'code_postal', 'ville', 'photo_url'
        ];
        
        $updateFields = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (isset($data['email'])) {
            $stmt = $pdo->prepare("SELECT id FROM personnes WHERE email = ? AND id != ?");
            $stmt->execute([$data['email'], $clientId]);
            
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode([
                    'error' => true,
                    'message' => 'Cette adresse email est déjà utilisée'
                ]);
                return;
            }
        }
        
        if (isset($data['preferences']) && is_array($data['preferences'])) {
            $updateFields[] = "preferences = ?";
            $params[] = json_encode($data['preferences']);
        }
        
        $updateFields[] = "updated_at = NOW()";
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Aucun champ valide à mettre à jour'
            ]);
            return;
        }
        
        $params[] = $clientId;
        $sql = "UPDATE personnes SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
            echo json_encode([
                'error' => false,
                'message' => 'Profil mis à jour avec succès'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Erreur lors de la mise à jour du profil'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Erreur lors de la mise à jour du profil: ' . $e->getMessage()
        ]);
    }
} 