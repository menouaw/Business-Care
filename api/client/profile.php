<?php
// API pour la gestion du profil client

// vérification de l'authentification
if (!$isAuthenticated) {
    http_response_code(401);
    echo json_encode([
        'error' => true,
        'message' => 'Authentification requise'
    ]);
    exit;
}

// récupérer l'id du client (extrait du jeton JWT en production)
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
if (!$clientId) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => 'ID client manquant'
    ]);
    exit;
}

// determine l'action a effectuer en fonction de la methode HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // récupérer le profil du client
        getClientProfile($clientId);
        break;
        
    case 'PUT':
        // mettre à jour le profil du client
        updateClientProfile($clientId);
        break;
        
    case 'OPTIONS':
        // répondre aux requêtes OPTIONS (pre-flight CORS)
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

/**
 * Récupère le profil complet d'un client
 * 
 * Cette fonction renvoie toutes les informations du profil du client, 
 * y compris ses préférences et les informations de son entreprise si applicable.
 * 
 * @param int $clientId L'ID du client dont on récupère le profil
 * @return void Envoie une réponse JSON avec les données du profil
 */
function getClientProfile($clientId) {
    $pdo = getDbConnection();
    
    try {
        // Vérifier que la personne est bien un client
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
        
        // Masquer le mot de passe
        unset($client['mot_de_passe']);
        
        // Récupérer les informations de l'entreprise si rattaché
        $company = null;
        if ($client['entreprise_id']) {
            $stmt = $pdo->prepare("SELECT * FROM entreprises WHERE id = ?");
            $stmt->execute([$client['entreprise_id']]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Récupérer les préférences utilisateur
        $preferences = json_decode($client['preferences'] ?? '{}', true);
        
        // Construire la réponse
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

/**
 * Met à jour le profil d'un client
 * 
 * Cette fonction permet au client de mettre à jour ses informations personnelles
 * et ses préférences (mais pas son rôle ou son statut).
 * 
 * @param int $clientId L'ID du client dont le profil est mis à jour
 * @return void Envoie une réponse JSON indiquant le succès ou l'échec
 */
function updateClientProfile($clientId) {
    $pdo = getDbConnection();
    
    try {
        // vérifier que la personne est bien un client
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
        
        // récupérer les données envoyées
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Aucune donnée fournie'
            ]);
            return;
        }
        
        // champs autorisés à être modifiés par le client
        $allowedFields = [
            'nom', 'prenom', 'email', 'telephone', 'adresse', 
            'code_postal', 'ville', 'photo_url'
        ];
        
        // construire la requête de mise à jour
        $updateFields = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        // vérifier si une nouvelle adresse email est déjà utilisée
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
        
        // mettre à jour les préférences si elles sont fournies
        if (isset($data['preferences']) && is_array($data['preferences'])) {
            $updateFields[] = "preferences = ?";
            $params[] = json_encode($data['preferences']);
        }
        
        // ajouter le timestamp de mise à jour
        $updateFields[] = "updated_at = NOW()";
        
        // s'il n'y a rien à mettre à jour
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Aucun champ valide à mettre à jour'
            ]);
            return;
        }
        
        // exécuter la mise à jour
        $params[] = $clientId; // pour la clause WHERE
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