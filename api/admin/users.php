<?php
// API pour la gestion des utilisateurs

// verifie si l'utilisateur a accès à cette API
if (!$isAuthenticated) {
    http_response_code(401);
    echo json_encode([
        'error' => true,
        'message' => 'Authentification requise'
    ]);
    exit;
}

// determine l'action à effectuer en fonction de la methode HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // recuperation d'utilisateurs
        if ($id) {
            // recupère un utilisateur specifique
            $user = fetchOne('personnes', "id = $id");
            
            if ($user) {
                // masque le mot de passe
                unset($user['mot_de_passe']);
                
                echo json_encode([
                    'error' => false,
                    'user' => $user
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'error' => true,
                    'message' => 'Utilisateur non trouve'
                ]);
            }
        } else {
            // recupère tous les utilisateurs avec pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $role = isset($_GET['role']) ? (int)$_GET['role'] : 0;
            
            // construit la clause WHERE pour le filtrage
            $where = '';
            $params = [];
            
            if ($search) {
                $where .= "(nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($role) {
                if ($where) {
                    $where .= " AND role_id = ?";
                } else {
                    $where .= "role_id = ?";
                }
                $params[] = $role;
            }
            
            // compte le nombre total d'utilisateurs
            $pdo = getDbConnection();
            $countSql = "SELECT COUNT(id) FROM personnes";
            if ($where) {
                $countSql .= " WHERE $where";
            }
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalUsers = $countStmt->fetchColumn();
            
            // calcule le nombre de pages
            $totalPages = ceil($totalUsers / $limit);
            $offset = ($page - 1) * $limit;
            
            // recupère les utilisateurs pour la page courante
            $sql = "SELECT p.*, r.nom as role_name 
                    FROM personnes p 
                    LEFT JOIN roles r ON p.role_id = r.id";
            if ($where) {
                $sql .= " WHERE $where";
            }
            $sql .= " ORDER BY p.nom, p.prenom LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            
            // masque les mots de passe
            foreach ($users as &$user) {
                unset($user['mot_de_passe']);
            }
            
            echo json_encode([
                'error' => false,
                'users' => $users,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $totalUsers,
                    'pages' => $totalPages
                ]
            ]);
        }
        break;
        
    case 'POST':
        // creation d'un nouvel utilisateur
        $data = json_decode(file_get_contents('php://input'), true);
        
        // validation des donnees
        if (!isset($data['email']) || !isset($data['mot_de_passe']) || !isset($data['nom']) || !isset($data['prenom']) || !isset($data['role_id'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Donnees incomplètes'
            ]);
            exit;
        }
        
        // verifie si l'email existe dejà
        $existingUser = fetchOne('personnes', "email = '{$data['email']}'");
        if ($existingUser) {
            http_response_code(409);
            echo json_encode([
                'error' => true,
                'message' => 'Un utilisateur avec cet email existe dejà'
            ]);
            exit;
        }
        
        // hachage du mot de passe
        $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        
        // ajoute la date de creation
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // statut par defaut
        if (!isset($data['statut'])) {
            $data['statut'] = 'actif';
        }
        
        // insertion de l'utilisateur
        $userId = insertRow('personnes', $data);
        
        if ($userId) {
            echo json_encode([
                'error' => false,
                'message' => 'Utilisateur cree avec succès',
                'user_id' => $userId
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Erreur lors de la creation de l\'utilisateur'
            ]);
        }
        break;
        
    case 'PUT':
        // mise à jour d'un utilisateur existant
        if (!$id) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'ID utilisateur requis'
            ]);
            exit;
        }
        
        // verifie si l'utilisateur existe
        $existingUser = fetchOne('personnes', "id = $id");
        if (!$existingUser) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Utilisateur non trouve'
            ]);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // si le mot de passe est fourni, le hacher
        if (isset($data['mot_de_passe']) && !empty($data['mot_de_passe'])) {
            $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        } else {
            unset($data['mot_de_passe']);
        }
        
        // ajoute la date de mise à jour
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // mise à jour de l'utilisateur
        $updated = updateRow('personnes', $data, "id = $id");
        
        if ($updated) {
            echo json_encode([
                'error' => false,
                'message' => 'Utilisateur mis à jour avec succès'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Erreur lors de la mise à jour de l\'utilisateur'
            ]);
        }
        break;
        
    case 'DELETE':
        // suppression d'un utilisateur
        if (!$id) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'ID utilisateur requis'
            ]);
            exit;
        }
        
        // verifie si l'utilisateur existe
        $existingUser = fetchOne('personnes', "id = $id");
        if (!$existingUser) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Utilisateur non trouve'
            ]);
            exit;
        }
        
        // au lieu de supprimer, marque l'utilisateur comme inactif
        $data = [
            'statut' => 'inactif',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $updated = updateRow('personnes', $data, "id = $id");
        
        if ($updated) {
            echo json_encode([
                'error' => false,
                'message' => 'Utilisateur desactive avec succès'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Erreur lors de la desactivation de l\'utilisateur'
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'error' => true,
            'message' => 'Methode non autorisee'
        ]);
        break;
} 