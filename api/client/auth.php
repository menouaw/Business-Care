<?php
// API pour l'authentification des clients

// determine l'action a effectuer en fonction de la methode HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // authentification
        $data = json_decode(file_get_contents('php://input'), true);
        
        // verifier si les identifiants sont fournis
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Email et mot de passe requis'
            ]);
            exit;
        }
        
        // recuperer l'utilisateur (client uniquement)
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT id, nom, prenom, email, mot_de_passe, role_id, photo_url, entreprise_id, preferences 
                               FROM personnes 
                               WHERE email = ? AND statut = 'actif' AND role_id = 2"); 
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($data['password'], $user['mot_de_passe'])) {
            $token = bin2hex(random_bytes(32));
            
            // mise a jour du temps de derniere connexion
            $stmt = $pdo->prepare("UPDATE personnes SET derniere_connexion = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Charger les préférences utilisateur
            $preferences = json_decode($user['preferences'] ?? '{}', true);
            
            // masquer le mot de passe
            unset($user['mot_de_passe']);
            
            // récupérer les informations de l'entreprise si client rattaché à une entreprise
            $companyData = null;
            if (!empty($user['entreprise_id'])) {
                $stmt = $pdo->prepare("SELECT id, nom, adresse, ville, code_postal FROM entreprises WHERE id = ?");
                $stmt->execute([$user['entreprise_id']]);
                $companyData = $stmt->fetch();
            }
            
            echo json_encode([
                'error' => false,
                'message' => 'Authentification réussie',
                'token' => $token,
                'user' => $user,
                'company' => $companyData,
                'preferences' => $preferences
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Email ou mot de passe invalide ou compte non client'
            ]);
        }
        break;
        
    case 'PUT':
        // verifier que l'utilisateur est authentifie
        if (!$isAuthenticated) {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Authentification requise'
            ]);
            exit;
        }
        
        // modification du mot de passe
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Mot de passe actuel et nouveau mot de passe requis'
            ]);
            exit;
        }
        
        // recuperer l'ID de l'utilisateur (normalement extrait du token)
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'ID utilisateur requis'
            ]);
            exit;
        }
        
        // vérifier que l'utilisateur est bien un client
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM personnes WHERE id = ? AND role_id = 2");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Client non trouvé'
            ]);
            exit;
        }
        
        // verifier le mot de passe actuel
        if (!password_verify($data['current_password'], $user['mot_de_passe'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Mot de passe actuel incorrect'
            ]);
            exit;
        }
        
        // mettre a jour le mot de passe
        $stmt = $pdo->prepare("UPDATE personnes SET mot_de_passe = ?, updated_at = NOW() WHERE id = ?");
        $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
        
        if ($stmt->execute([$hashedPassword, $userId])) {
            echo json_encode([
                'error' => false,
                'message' => 'Mot de passe mis à jour avec succès'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Erreur lors de la mise à jour du mot de passe'
            ]);
        }
        break;
        
    case 'DELETE':
        // deconnexion (a implementer avec une invalidation du token)
        echo json_encode([
            'error' => false,
            'message' => 'Déconnexion réussie'
        ]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'error' => true,
            'message' => 'Méthode non autorisée'
        ]);
        break;
} 