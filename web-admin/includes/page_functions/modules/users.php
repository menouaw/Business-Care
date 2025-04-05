<?php
require_once __DIR__ . '/../../init.php';

/**
 * Recupere la liste des utilisateurs avec pagination et filtrage
 * 
 * @param int $page Numero de la page
 * @param int $perPage Nombre d'elements par page
 * @param string $search Terme de recherche
 * @param int $roleId Filtre par role
 * @param int $entrepriseId Filtre par entreprise
 * @return array Donnees de pagination et liste des utilisateurs
 */
function usersGetList($page = 1, $perPage = 10, $search = '', $roleId = 0, $entrepriseId = 0) {
        $params = [];
    $conditions = [];

    if ($search) {
        $conditions[] = "(p.nom LIKE ? OR p.prenom LIKE ? OR p.email LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    if ($roleId > 0) {
        $conditions[] = "p.role_id = ?";
        $params[] = (int)$roleId;
    }
    
    if ($entrepriseId > 0) {
        $conditions[] = "p.entreprise_id = ?";
        $params[] = (int)$entrepriseId;
    }
    
    $whereSql = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : '';

    $countSql = "SELECT COUNT(p.id) FROM personnes p {$whereSql}";
    $totalUsers = executeQuery($countSql, $params)->fetchColumn();
    
    $totalPages = ceil($totalUsers / $perPage);
    $page = max(1, min($page, $totalPages)); 
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT p.*, r.nom as role_name, e.nom as entreprise_nom
            FROM personnes p
            LEFT JOIN roles r ON p.role_id = r.id
            LEFT JOIN entreprises e ON p.entreprise_id = e.id
            {$whereSql}
            ORDER BY p.nom, p.prenom ASC LIMIT ?, ?";
    $paramsWithPagination = array_merge($params, [$offset, $perPage]);

    $users = executeQuery($sql, $paramsWithPagination)->fetchAll();

    return [
        'users' => $users,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalUsers,
        'perPage' => $perPage
    ];
}

/**
 * Recupere les details d'un utilisateur
 * 
 * @param int $id Identifiant de l'utilisateur
 * @return array|false Donnees de l'utilisateur ou false si non trouve
 */
function usersGetDetails($id) {
    $sqlUser = "SELECT p.*, r.nom as role_name, e.nom as entreprise_nom
                  FROM personnes p
                  LEFT JOIN roles r ON p.role_id = r.id
                  LEFT JOIN entreprises e ON p.entreprise_id = e.id
                  WHERE p.id = ? LIMIT 1";
    $user = executeQuery($sqlUser, [$id])->fetch();
    
    if (!$user) {
        return false;
    }
    
    $sqlHistory = "SELECT * FROM logs 
                   WHERE personne_id = ? AND action LIKE '%login%' 
                   ORDER BY created_at DESC LIMIT 10"; // Adjusted action check
    $user['login_history'] = executeQuery($sqlHistory, [$id])->fetchAll();
    
    if (isset($user['role_id']) && $user['role_id'] == ROLE_PRESTATAIRE) { 
        $sqlPrestations = "SELECT pr.* 
                           FROM prestations pr
                           WHERE pr.praticien_id = ?
                           ORDER BY pr.created_at DESC LIMIT 10"; // Assuming prestation has praticien_id
                           // Original query had `services s` and `p.*` - might need adjustment based on actual `prestations` table
        $user['prestations'] = executeQuery($sqlPrestations, [$id])->fetchAll();
    }
    
    if (isset($user['role_id']) && $user['role_id'] == ROLE_SALARIE) { 
        $sqlReservations = "SELECT rv.*, pr.nom as prestation_nom, p.prenom as praticien_prenom, p.nom as praticien_nom
                            FROM rendez_vous rv
                            LEFT JOIN prestations pr ON rv.prestation_id = pr.id
                            LEFT JOIN personnes p ON pr.praticien_id = p.id
                            WHERE rv.personne_id = ?
                            ORDER BY rv.date_rdv DESC LIMIT 10";
        $user['reservations'] = executeQuery($sqlReservations, [$id])->fetchAll();
    }
    
    return $user;
}

/**
 * Recupere la liste des roles
 * 
 * @return array Liste des roles
 */
function usersGetRoles() {
    return fetchAll('roles', '', 'nom ASC');
}

/**
 * Recupere la liste des entreprises pour le formulaire
 * 
 * @return array Liste des entreprises
 */
function usersGetEntreprises() {
    return fetchAll('entreprises', '', 'nom ASC');
}

/**
 * Crée ou met à jour un utilisateur dans la base de données.
 *
 * Utilise insertRow ou updateRow de db.php.
 *
 * @param array $data Tableau associatif des informations de l'utilisateur.
 * @param int $id Identifiant de l'utilisateur (0 pour création).
 * @return array Résultat ['success' => bool, 'message' => string|null, 'errors' => array|null]
 */
function usersSave($data, $id = 0) {
    $errors = [];
    
    if (empty($data['nom'])) $errors[] = "Le nom est obligatoire";
    if (empty($data['prenom'])) $errors[] = "Le prenom est obligatoire";
    if (empty($data['email'])) {
        $errors[] = "L'email est obligatoire";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    } else {
        $existingUser = fetchOne('personnes', 'email = ? AND id != ?', '', [$data['email'], $id]);
        if ($existingUser) {
            $errors[] = "Cet email est deja utilise par un autre utilisateur";
            logSystemActivity('user_duplicate_email', "[ERROR] Tentative d'utilisation d'email déjà existant: {$data['email']}");
        }
    }
    
    if ($id == 0 && empty($data['mot_de_passe'])) {
        $errors[] = "Le mot de passe est obligatoire lors de la création";
    }
    if (empty($data['role_id']) || !fetchOne('roles', 'id = ?', '', [(int)$data['role_id']])) {
        $errors[] = "Le rôle sélectionné est invalide.";
    }
    if (!empty($data['entreprise_id']) && !fetchOne('entreprises', 'id = ?', '', [(int)$data['entreprise_id']])) {
         $errors[] = "L'entreprise sélectionnée est invalide.";
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    $dbData = [
        'nom' => $data['nom'], 
        'prenom' => $data['prenom'], 
        'email' => $data['email'], 
        'telephone' => $data['telephone'] ?? null,
        'adresse' => $data['adresse'] ?? null, 
        'code_postal' => $data['code_postal'] ?? null, 
        'ville' => $data['ville'] ?? null, 
        'role_id' => (int)$data['role_id'],
        'entreprise_id' => !empty($data['entreprise_id']) ? (int)$data['entreprise_id'] : null, 
        'statut' => $data['statut'] ?? 'inactif' // Default to inactive?
    ];
            
    $passwordChanged = false;
    if (!empty($data['mot_de_passe'])) {
        $dbData['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        $passwordChanged = true;
    }

    try {
        if ($id > 0) {
            $affectedRows = updateRow('personnes', $dbData, "id = ?", [$id]);
            
            if ($affectedRows !== false) { 
                 logBusinessOperation($_SESSION['user_id'], 'user_update', 
                    "[SUCCESS] Mise à jour utilisateur: {$dbData['prenom']} {$dbData['nom']} (ID: $id), role: {$dbData['role_id']}");
                if ($passwordChanged && $id != ($_SESSION['user_id'] ?? 0)) { 
                     logSecurityEvent($_SESSION['user_id'], 'password_change_admin', 
                        "[SUCCESS] Modification mot de passe pour utilisateur ID: $id par admin ID: {$_SESSION['user_id']}");
                }
                $message = "L'utilisateur a ete mis a jour avec succes";
                 return ['success' => true, 'message' => $message];
            } else {
                throw new Exception("La mise à jour a échoué ou aucune ligne n'a été modifiée.");
            }
        } 
        else {
            $newId = insertRow('personnes', $dbData);
            
            if ($newId) {
                logBusinessOperation($_SESSION['user_id'], 'user_create', 
                    "[SUCCESS] Création utilisateur: {$dbData['prenom']} {$dbData['nom']} (ID: $newId), role: {$dbData['role_id']}");
                logSecurityEvent($_SESSION['user_id'], 'account_creation', 
                    "[SUCCESS] Création compte pour {$dbData['email']} (ID: $newId, role: {$dbData['role_id']}) par admin ID: {$_SESSION['user_id']}");
                $message = "L'utilisateur a ete cree avec succes";
                 return ['success' => true, 'message' => $message, 'newId' => $newId];
            } else {
                 throw new Exception("L'insertion a échoué.");
            }
        }
        
    } catch (Exception $e) {
        $errorMessage = "Erreur de base de données : " . $e->getMessage();
        $errors[] = $errorMessage;
        logSystemActivity('error', "[ERROR] Erreur BDD dans usersSave: " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/**
 * Supprime un utilisateur après vérification de l'absence d'associations.
 *
 * Utilise executeQuery et deleteRow.
 *
 * @param int $id Identifiant de l'utilisateur à supprimer.
 * @return array Résultat ['success' => bool, 'message' => string]
 */
function usersDelete($id) {
    if ($id == ($_SESSION['user_id'] ?? 0)) {
        return ['success' => false, 'message' => "Vous ne pouvez pas supprimer votre propre compte."];
    }

    $prestationsCount = executeQuery("SELECT COUNT(id) FROM prestations WHERE praticien_id = ?", [$id])->fetchColumn();
    
    $reservationsCount = executeQuery("SELECT COUNT(id) FROM rendez_vous WHERE personne_id = ?", [$id])->fetchColumn();
    
    if ($prestationsCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'user_delete_attempt', 
            "[ERROR] Tentative échouée de suppression utilisateur ID: $id - Prestations associées existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer cet utilisateur car il a des prestations associees"
        ];
    } 
    
    if ($reservationsCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'user_delete_attempt', 
            "[ERROR] Tentative échouée de suppression utilisateur ID: $id - Rendez-vous associés existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer cet utilisateur car il a des rendez-vous associes"
        ];
    }
    
    try {
        beginTransaction(); 

        deleteRow('logs', "personne_id = ?", [$id]);
        
        deleteRow('remember_me_tokens', "user_id = ?", [$id]);
        
        deleteRow('preferences_utilisateurs', "personne_id = ?", [$id]);

        $deletedRows = deleteRow('personnes', "id = ?", [$id]);
        
        if ($deletedRows > 0) {
            commitTransaction(); 
            logSecurityEvent($_SESSION['user_id'], 'account_deletion', 
                "[SUCCESS] Suppression compte utilisateur ID: $id par admin ID: {$_SESSION['user_id']}");
            return [
                'success' => true,
                'message' => "L'utilisateur et ses données associées (logs, tokens, préférences) ont été supprimés avec succès"
            ];
        } else {
            rollbackTransaction(); 
            logBusinessOperation($_SESSION['user_id'], 'user_delete_attempt', 
                "[ERROR] Tentative échouée de suppression utilisateur ID: $id - Utilisateur non trouvé?");
            return [
                'success' => false,
                'message' => "Impossible de supprimer l'utilisateur (non trouvé ou déjà supprimé)"
            ];
        }
    } catch (Exception $e) {
        rollbackTransaction(); 
         logSystemActivity('error', "[ERROR] Erreur BDD dans usersDelete (Transaction annulée): " . $e->getMessage());
         return [
            'success' => false,
            'message' => "Erreur de base de données lors de la suppression (Transaction annulée)."
         ];
    }
} 