<?php
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../../../shared/web-admin/logging.php';

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
    $where = '';
    $params = [];
    $conditions = [];

    if ($search) {
        $conditions[] = "(p.nom LIKE ? OR p.prenom LIKE ? OR p.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($roleId > 0) {
        $conditions[] = "p.role_id = ?";
        $params[] = $roleId;
    }
    
    if ($entrepriseId > 0) {
        $conditions[] = "p.entreprise_id = ?";
        $params[] = $entrepriseId;
    }
    
    if (!empty($conditions)) {
        $where = "WHERE " . implode(' AND ', $conditions);
    }

    // recupere les utilisateurs pagines
    $offset = ($page - 1) * $perPage;

    $pdo = getDbConnection();
    $countSql = "SELECT COUNT(p.id) FROM personnes p $where";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);
    $page = max(1, min($page, $totalPages));

    $sql = "SELECT p.*, r.nom as role_name, e.nom as entreprise_nom
            FROM personnes p
            LEFT JOIN roles r ON p.role_id = r.id
            LEFT JOIN entreprises e ON p.entreprise_id = e.id
            $where
            ORDER BY p.nom, p.prenom ASC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $perPage;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT p.*, r.nom as role_name, e.nom as entreprise_nom
                          FROM personnes p
                          LEFT JOIN roles r ON p.role_id = r.id
                          LEFT JOIN entreprises e ON p.entreprise_id = e.id
                          WHERE p.id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return false;
    }
    
    // recupere l'historique de connexion
    $stmt = $pdo->prepare("SELECT * FROM logs 
                          WHERE personne_id = ? AND action IN ('login', 'logout') 
                          ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$id]);
    $user['login_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // recupere les prestations associees (pour les praticiens)
    if ($user['role_id'] == 2) { // role_id 2 = praticien
        $stmt = $pdo->prepare("SELECT p.*, s.nom as service_nom
                              FROM prestations p
                              LEFT JOIN services s ON p.service_id = s.id
                              WHERE p.praticien_id = ?
                              ORDER BY p.date_debut DESC LIMIT 10");
        $stmt->execute([$id]);
        $user['prestations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // recupere les reservations (pour les salaries)
    if ($user['role_id'] == 1) { // role_id 1 = salarie
        $stmt = $pdo->prepare("SELECT r.*, s.nom as service_nom, p.prenom as praticien_prenom, p.nom as praticien_nom
                              FROM reservations r
                              LEFT JOIN prestations pr ON r.prestation_id = pr.id
                              LEFT JOIN services s ON pr.service_id = s.id
                              LEFT JOIN personnes p ON pr.praticien_id = p.id
                              WHERE r.salarie_id = ?
                              ORDER BY r.date_reservation DESC LIMIT 10");
        $stmt->execute([$id]);
        $user['reservations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $user;
}

/**
 * Recupere la liste des roles
 * 
 * @return array Liste des roles
 */
function usersGetRoles() {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM roles ORDER BY nom ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Recupere la liste des entreprises pour le formulaire
 * 
 * @return array Liste des entreprises
 */
function usersGetEntreprises() {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, nom FROM entreprises ORDER BY nom ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Enregistre ou met à jour un utilisateur dans la base de données.
 *
 * Valide les données obligatoires (nom, prénom, email) et, pour la création, le mot de passe. Vérifie également
 * l'unicité de l'email avant d'insérer ou de mettre à jour l'utilisateur. Selon le contexte, la modification
 * du mot de passe est gérée et des événements de création, de mise à jour ou de modification de mot de passe sont logués.
 *
 * @param array $data Tableau associatif contenant les informations de l'utilisateur (par exemple, 'nom', 'prenom', 'email', etc.).
 * @param int $id Identifiant de l'utilisateur à mettre à jour (utiliser 0 pour créer un nouvel utilisateur).
 * @return array Tableau indiquant le succès de l'opération et retournant soit un message de succès, soit une liste d'erreurs en cas d'échec.
 */
function usersSave($data, $id = 0) {
    $errors = [];
    
    if (empty($data['nom'])) {
        $errors[] = "Le nom est obligatoire";
    }
    
    if (empty($data['prenom'])) {
        $errors[] = "Le prenom est obligatoire";
    }
    
    if (empty($data['email'])) {
        $errors[] = "L'email est obligatoire";
    } else if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }
    
    // verification de l'unicite de l'email
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id FROM personnes WHERE email = ? AND id != ?");
    $stmt->execute([$data['email'], $id]);
    if ($stmt->fetchColumn()) {
        $errors[] = "Cet email est deja utilise par un autre utilisateur";
        logSystemActivity('user_duplicate_email', "Tentative d'utilisation d'email déjà existant: {$data['email']}");
    }
    
    // verification du mot de passe lors de la creation
    if ($id == 0 && empty($data['mot_de_passe'])) {
        $errors[] = "Le mot de passe est obligatoire";
    }
    
    if (!empty($errors)) {
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
    
    try {
        // cas de mise a jour
        if ($id > 0) {
            $sql = "UPDATE personnes SET 
                    nom = ?, prenom = ?, email = ?, telephone = ?, 
                    adresse = ?, code_postal = ?, ville = ?, 
                    role_id = ?, entreprise_id = ?, statut = ?";
            
            $params = [
                $data['nom'], 
                $data['prenom'], 
                $data['email'], 
                $data['telephone'],
                $data['adresse'], 
                $data['code_postal'], 
                $data['ville'], 
                $data['role_id'],
                $data['entreprise_id'] ?: null, 
                $data['statut']
            ];
            
            // ajout du mot de passe s'il est fourni
            $passwordChanged = false;
            if (!empty($data['mot_de_passe'])) {
                $sql .= ", mot_de_passe = ?";
                $params[] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
                $passwordChanged = true;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            logBusinessOperation($_SESSION['user_id'], 'user_update', 
                "Mise à jour utilisateur: {$data['prenom']} {$data['nom']} (ID: $id), role: {$data['role_id']}");
                
            if ($passwordChanged) {
                logSecurityEvent($_SESSION['user_id'], 'password_change', 
                    "Modification mot de passe pour utilisateur ID: $id par administrateur");
            }
            
            $message = "L'utilisateur a ete mis a jour avec succes";
        } 
        // cas de creation
        else {
            $sql = "INSERT INTO personnes (nom, prenom, email, telephone, adresse, code_postal, ville, 
                    role_id, entreprise_id, mot_de_passe, statut) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nom'], 
                $data['prenom'], 
                $data['email'], 
                $data['telephone'],
                $data['adresse'], 
                $data['code_postal'], 
                $data['ville'], 
                $data['role_id'],
                $data['entreprise_id'] ?: null, 
                $hashedPassword,
                $data['statut']
            ]);
            
            $newId = $pdo->lastInsertId();
            logBusinessOperation($_SESSION['user_id'], 'user_create', 
                "Création utilisateur: {$data['prenom']} {$data['nom']} (ID: $newId), role: {$data['role_id']}");
                
            logSecurityEvent($_SESSION['user_id'], 'account_creation', 
                "Création compte pour {$data['email']} (ID: $newId, role: {$data['role_id']})");
            
            $message = "L'utilisateur a ete cree avec succes";
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
    } catch (PDOException $e) {
        $errors[] = "Erreur de base de données : " . $e->getMessage();
        
        // log l'erreur pour l'administrateur
        logSystemActivity('error', "Erreur BDD dans users/index.php : " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/**
 * Tente de supprimer un utilisateur après vérification des associations existantes.
 *
 * La fonction vérifie si l'utilisateur identifié par son ID possède des prestations (pour les praticiens) ou des réservations (pour les salariés). 
 * Si l'une de ces associations est présente, la suppression est annulée et un message d'erreur descriptif est retourné. 
 * Sinon, elle supprime d'abord les logs associés, puis l'utilisateur, et enregistre l'événement de suppression.
 *
 * @param int $id L'identifiant de l'utilisateur à supprimer.
 * @return array Un tableau indiquant le succès de l'opération et un message explicatif.
 */
function usersDelete($id) {
    $pdo = getDbConnection();
    
    // verifie si l'utilisateur a des prestations associees (praticien)
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM prestations WHERE praticien_id = ?");
    $stmt->execute([$id]);
    $prestationsCount = $stmt->fetchColumn();
    
    // verifie si l'utilisateur a des reservations associees (salarie)
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM reservations WHERE salarie_id = ?");
    $stmt->execute([$id]);
    $reservationsCount = $stmt->fetchColumn();
    
    if ($prestationsCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'user_delete_attempt', 
            "Tentative échouée de suppression utilisateur ID: $id - Prestations associées existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer cet utilisateur car il a des prestations associees"
        ];
    } else if ($reservationsCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'user_delete_attempt', 
            "Tentative échouée de suppression utilisateur ID: $id - Réservations associées existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer cet utilisateur car il a des reservations associees"
        ];
    } else {
        // supprimer les logs associes
        $stmt = $pdo->prepare("DELETE FROM logs WHERE personne_id = ?");
        $stmt->execute([$id]);
        
        // supprimer l'utilisateur
        $stmt = $pdo->prepare("DELETE FROM personnes WHERE id = ?");
        $stmt->execute([$id]);
        
        logSecurityEvent($_SESSION['user_id'], 'account_deletion', 
            "Suppression compte utilisateur ID: $id");
        
        return [
            'success' => true,
            'message' => "L'utilisateur a ete supprime avec succes"
        ];
    }
} 