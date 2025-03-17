<?php
// API pour l'authentification

// determine l'action à effectuer en fonction de la methode HTTP
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
        
        // recuperer l'utilisateur
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT id, nom, prenom, email, mot_de_passe, role_id, photo_url 
                               FROM personnes WHERE email = ? AND statut = 'actif'");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($data['password'], $user['mot_de_passe'])) {
            // generer un token (à remplacer par une implementation JWT correcte)
            $token = bin2hex(random_bytes(32));
            
            // mise à jour du temps de derniere connexion
            $stmt = $pdo->prepare("UPDATE personnes SET derniere_connexion = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // masquer le mot de passe
            unset($user['mot_de_passe']);
            
            echo json_encode([
                'error' => false,
                'message' => 'Authentification reussie',
                'token' => $token,
                'user' => $user
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Email ou mot de passe invalide'
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
        
        // recuperer l'utilisateur
-        $user = fetchOne('personnes', "id = $userId");
+        $user = fetchOne('personnes', "id = ?", '', [$userId]);
        if (!$user) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Utilisateur non trouve'
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
        
        // mettre à jour le mot de passe
        $updatedData = [
            'mot_de_passe' => password_hash($data['new_password'], PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $updated = updateRow('personnes', $updatedData, "id = ?", [$userId]);
        if ($updated) {
            echo json_encode([
                'error' => false,
                'message' => 'Mot de passe mis à jour avec succes'
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
        // deconnexion (à implementer avec une invalidation du token)
        echo json_encode([
            'error' => false,
            'message' => 'Deconnexion reussie'
        ]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'error' => true,
            'message' => 'Methode non autorisee'
        ]);
        break;
} 