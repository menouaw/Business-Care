<?php
header('Content-Type: application/json');


require_once __DIR__ . '/../../vendor/autoload.php'; 

require_once __DIR__ . '/init.php';

require_once __DIR__ . '/../../shared/web-admin/auth_firebase.php';

$firebaseUserPayload = requireFirebaseAuthentication();
$firebaseUid = $firebaseUserPayload->sub;

$localAdminUser = null;
$localAdminUserId = null;
try {
    
    $localAdminUser = fetchOne(TABLE_USERS, 'firebase_uid = :firebase_uid', '', [':firebase_uid' => $firebaseUid]);

    if (!$localAdminUser) {
        
        logSecurityEvent(null, '[SECURITY]:api_access_denied', '[FAILURE] Accès refusé: Firebase UID ' . $firebaseUid . ' non trouvé dans la base de données locale.', true);
        http_response_code(403); 
        echo json_encode(['error' => true, 'message' => 'Accès refusé. Enregistrement d\'utilisateur non trouvé.']);
        exit;
    }

    
    if ((int)$localAdminUser['role_id'] !== ROLE_ADMIN) {
        logSecurityEvent($localAdminUser['id'], '[SECURITY]:api_access_denied', '[FAILURE] Accès refusé: L\'utilisateur ID ' . $localAdminUser['id'] . ' n\'a pas le rôle ADMIN (Firebase UID: ' . $firebaseUid . ').', true);
        http_response_code(403); 
        echo json_encode(['error' => true, 'message' => 'Accès refusé. Permissions insuffisantes.']);
        exit;
    }

    $localAdminUserId = (int)$localAdminUser['id'];
    
    logSecurityEvent($localAdminUserId, '[SECURITY]:api_access_granted', '[SUCCESS] Accès accordé: Firebase UID ' . $firebaseUid);

} catch (PDOException $e) {
    logSystemActivity('[SECURITY]:api_error', '[FAILURE] Erreur de base de données lors de la recherche/vérification du rôle pour Firebase UID: ' . $firebaseUid . ' - Erreur: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Erreur interne du serveur lors de l\'authentification.']);
    exit;
} catch (\Exception $e) {
    
    logSystemActivity('[SECURITY]:api_error', '[FAILURE] Erreur générale lors de l\'authentification pour Firebase UID: ' . $firebaseUid . ' - Erreur: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Erreur interne du serveur lors de l\'authentification.']);
    exit;
}




$method = $_SERVER['REQUEST_METHOD'];
$userId = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : null; 

switch ($method) {
    case 'GET':
        
        if ($userId) {
            
             $user = fetchOne(TABLE_USERS, 'id = :id', '', [':id' => $userId]);
             if ($user) {
                 
                 unset($user['mot_de_passe']); 
                 unset($user['firebase_uid']); 
                 echo json_encode($user);
             } else {
                 http_response_code(404);
                 echo json_encode(['error' => true, 'message' => 'Utilisateur non trouvé.']);
             }
        } else {
            
             $users = fetchAll(TABLE_USERS);
             
             foreach ($users as &$user) {
                 unset($user['mot_de_passe']); 
                 unset($user['firebase_uid']); 
             }
            echo json_encode($users);
        }
        break;

    case 'POST':
        
        $data = json_decode(file_get_contents('php://input'), true);

        
        if (!$data || empty($data['nom']) || empty($data['prenom']) || empty($data['email']) || empty($data['role_id'])) {
            http_response_code(400); 
            echo json_encode(['error' => true, 'message' => 'Champs requis manquants (nom, prenom, email, role_id).']);
            exit;
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
             http_response_code(400);
             echo json_encode(['error' => true, 'message' => 'Format d\'email invalide.']);
             exit;
        }
         
        $roleExists = fetchOne(TABLE_ROLES, 'id = :id', '', [':id' => (int)$data['role_id']]);
        if (!$roleExists) {
             http_response_code(400);
             echo json_encode(['error' => true, 'message' => 'Identifiant de rôle invalide.']);
             exit;
        }
        
        $existingUser = fetchOne(TABLE_USERS, 'email = :email', '', [':email' => $data['email']]);
        if ($existingUser) {
            http_response_code(409); 
            echo json_encode(['error' => true, 'message' => 'Adresse email déjà utilisée.']);
            exit;
        }
        

        
        
        
        
        
        try {
            $insertData = [
                'nom' => trim($data['nom']),
                'prenom' => trim($data['prenom']),
                'email' => trim($data['email']),
                'role_id' => (int)$data['role_id'],
                'entreprise_id' => isset($data['entreprise_id']) ? (int)$data['entreprise_id'] : null,
                'site_id' => isset($data['site_id']) ? (int)$data['site_id'] : null,
                'statut' => $data['statut'] ?? 'actif', 
                'firebase_uid' => null, 
                
                'telephone' => $data['telephone'] ?? null,
                'date_naissance' => $data['date_naissance'] ?? null,
                'genre' => $data['genre'] ?? null,
                'photo_url' => $data['photo_url'] ?? null,
            ];
            
            
            $newUserId = insertRow(TABLE_USERS, $insertData); 

            if ($newUserId) {
                 logActivity($localAdminUserId, '[SECURITY]:admin_create_user', 'Création de l\'utilisateur ID: ' . $newUserId . ' (Email: ' . $insertData['email'] . ')');
                 http_response_code(201); 
                 
                 $createdUser = fetchOne(TABLE_USERS, 'id = :id', '', [':id' => $newUserId]);
                 unset($createdUser['mot_de_passe']);
                 unset($createdUser['firebase_uid']);
                 echo json_encode($createdUser);
            } else {
                 logSystemActivity('[SECURITY]:api_error', '[FAILURE] Échec de l\'insertion d\'un nouvel utilisateur. Données d\'entrée: ' . json_encode($data));
                 http_response_code(500);
                 echo json_encode(['error' => true, 'message' => 'Échec de la création de l\'utilisateur.']);
            }
        } catch (PDOException $e) {
             logSystemActivity('[SECURITY]:api_error', '[FAILURE] Erreur de base de données lors de la création d\'un nouvel utilisateur. Données d\'entrée: ' . json_encode($data));
             http_response_code(500);
             echo json_encode(['error' => true, 'message' => 'Erreur de base de données lors de la création d\'un nouvel utilisateur.']);
        }
        break;

    case 'PUT':
        
        if (!$userId) {
             http_response_code(400);
             echo json_encode(['error' => true, 'message' => 'Identifiant de l\'utilisateur est requis pour la mise à jour.']);
             exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
             http_response_code(400);
             echo json_encode(['error' => true, 'message' => 'Données de mise à jour invalides fournies.']);
             exit;
        }

        
        $existingUser = fetchOne(TABLE_USERS, 'id = :id', '', [':id' => $userId]);
        if (!$existingUser) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Utilisateur non trouvé pour la mise à jour.']);
            exit;
        }

        
        
        
        if (isset($data['email']) && strtolower(trim($data['email'])) !== strtolower($existingUser['email'])) {
             
             $emailConflict = fetchOne(TABLE_USERS, 'email = :email AND id != :id', '', [':email' => trim($data['email']), ':id' => $userId]);
             if ($emailConflict) {
                http_response_code(409); 
                echo json_encode(['error' => true, 'message' => 'L\'adresse email nouvellement fournie est déjà utilisée par un autre utilisateur.']);
                exit;
             }
              
             
             
             logSecurityEvent($localAdminUserId, '[SECURITY]:admin_attempt_email_change', '[FAILURE] Tentative de modification de l\'email local pour l\'utilisateur ID: ' . $userId . '. Nécessite une mise à jour séparée de Firebase.', true);
        }
         if (isset($data['role_id'])) {
            $roleExists = fetchOne(TABLE_ROLES, 'id = :id', '', [':id' => (int)$data['role_id']]);
            if (!$roleExists) {
                 http_response_code(400);
                 echo json_encode(['error' => true, 'message' => 'Identifiant de rôle invalide pour la mise à jour.']);
                 exit;
            }
        }
        


        
        
        $updateData = [];
        $allowedFields = ['nom', 'prenom', 'email', 'telephone', 'date_naissance', 'genre', 'photo_url', 'role_id', 'entreprise_id', 'site_id', 'statut'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                
                if (in_array($field, ['role_id', 'entreprise_id', 'site_id'])) {
                    $updateData[$field] = $data[$field] !== null ? (int)$data[$field] : null;
                } else {
                    $updateData[$field] = $data[$field] !== null ? trim($data[$field]) : null;
                }
            }
        }

        
        unset($updateData['firebase_uid']);
        unset($updateData['id']); 

        if (empty($updateData)) {
             http_response_code(400);
             echo json_encode(['error' => true, 'message' => 'Aucun champ valide fourni pour la mise à jour.']);
             exit;
        }

        
        try {
            
            
            $success = updateRow(TABLE_USERS, $updateData, 'id = :id', [':id' => $userId]);

            if ($success) {
                logActivity($localAdminUserId, '[SECURITY]:admin_update_user', 'Mise à jour de l\'utilisateur ID: ' . $userId . '. Champs mis à jour: ' . implode(', ', array_keys($updateData)));
                
                $updatedUser = fetchOne(TABLE_USERS, 'id = :id', '', [':id' => $userId]);
                unset($updatedUser['mot_de_passe']);
                unset($updatedUser['firebase_uid']);
                echo json_encode($updatedUser);
            } else {
                
                
                 logSystemActivity('[SECURITY]:api_warning', '[FAILURE] Opération de mise à jour retournée false pour l\'utilisateur ID: ' . $userId . '. Données d\'entrée: ' . json_encode($data));
                 
                 $currentUser = fetchOne(TABLE_USERS, 'id = :id', '', [':id' => $userId]);
                 unset($currentUser['mot_de_passe']);
                 unset($currentUser['firebase_uid']);
                 echo json_encode($currentUser); 
            }
        } catch (PDOException $e) {
            logSystemActivity('[SECURITY]:api_error', '[FAILURE] Erreur de base de données lors de la mise à jour de l\'utilisateur ID: ' . $userId . ' - Erreur: ' . $e->getMessage() . '. Données d\'entrée: ' . json_encode($data));
            http_response_code(500);
            echo json_encode(['error' => true, 'message' => 'Erreur de base de données lors de la mise à jour de l\'utilisateur.']);
        }
        break;

    case 'DELETE':
        
        if (!$userId) {
             http_response_code(400);
             echo json_encode(['error' => true, 'message' => 'Identifiant de l\'utilisateur est requis pour la suppression.']);
             exit;
        }

        
        $userToDelete = fetchOne(TABLE_USERS, 'id = :id', '', [':id' => $userId]);
        if (!$userToDelete) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Utilisateur non trouvé pour la suppression.']);
            exit;
        }

        
        
        
        
        
        try {
            
            
            $success = deleteRow(TABLE_USERS, 'id = :id', [':id' => $userId]);

            if ($success) {
                logActivity($localAdminUserId, '[SECURITY]:admin_delete_user', 'Suppression de l\'enregistrement local de l\'utilisateur ID: ' . $userId . ' (Email: ' . $userToDelete['email'] . '). Compte Firebase non supprimé par cette action.');
                 
                 logSecurityEvent($localAdminUserId, 'orphaned_firebase_account_risk', '[SECURITY]:orphaned_firebase_account_risk', '[FAILURE] L\'enregistrement local de l\'utilisateur ID ' . $userId . ' a été supprimé. Le compte Firebase associé (UID: ' . ($userToDelete['firebase_uid'] ?? 'N/A') . ') peut être orphelin si non supprimé séparément.', true);
                http_response_code(200); 
                echo json_encode(['message' => 'Utilisateur supprimé avec succès de la base de données locale. Le compte Firebase peut nécessiter une suppression séparée.']);
            } else {
                 logSystemActivity('[SECURITY]:api_error', '[FAILURE] Échec de la suppression de l\'utilisateur ID: ' . $userId . '. Opération de suppression retournée false.');
                 http_response_code(500);
                 echo json_encode(['error' => true, 'message' => 'Échec de la suppression de l\'utilisateur.']);
            }
        } catch (PDOException $e) {
            
            logSystemActivity('[SECURITY]:api_error', '[FAILURE] Erreur de base de données lors de la suppression de l\'utilisateur ID: ' . $userId . ' - Erreur: ' . $e->getMessage());
            http_response_code(500);
             
             if (strpos($e->getMessage(), 'CONSTRAINT') !== false) {
                 echo json_encode(['error' => true, 'message' => 'Impossible de supprimer l\'utilisateur en raison de données liées (e.g., rendez-vous, logs). Veuillez réassigner ou supprimer les données liées d\'abord.']);
             } else {
                 echo json_encode(['error' => true, 'message' => 'Erreur de base de données lors de la suppression de l\'utilisateur.']);
             }
        }
        break;

    default:
        
        http_response_code(405); 
        header('Allow: GET, POST, PUT, DELETE'); 
        echo json_encode(['error' => true, 'message' => 'Méthode non autorisée.']);
        break;
}

?>
