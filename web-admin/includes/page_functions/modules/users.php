<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../functions.php';

/**
 * Récupère la liste paginée des utilisateurs en appliquant des filtres optionnels.
 *
 * Cette fonction construit dynamiquement une requête SQL en intégrant des conditions de filtrage
 * basées sur un terme de recherche (nom, prénom ou email), un identifiant de rôle et/ou un identifiant d'entreprise.
 * Elle exécute d'abord une requête pour déterminer le nombre total d'utilisateurs correspondant aux critères,
 * ajuste la pagination en conséquence, puis récupère la liste des utilisateurs avec leurs rôles et le nom de leur entreprise.
 *
 * @param int $page Le numéro de la page à afficher.
 * @param int $perPage Le nombre d'utilisateurs à afficher par page.
 * @param string $search Terme de recherche appliqué sur le nom, prénom ou email.
 * @param int $roleId Identifiant du rôle pour filtrer les utilisateurs.
 * @param int $entrepriseId Identifiant de l'entreprise pour filtrer les utilisateurs.
 * @return array Un tableau associatif contenant :
 *   - 'users': le tableau des utilisateurs récupérés,
 *   - 'currentPage': le numéro de la page courante après ajustement,
 *   - 'totalPages': le nombre total de pages disponibles,
 *   - 'totalItems': le nombre total d'utilisateurs correspondant aux critères,
 *   - 'perPage': le nombre d'utilisateurs affichés par page.
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
 * Récupère les détails complets d'un utilisateur.
 *
 * Cette fonction retourne les informations de l'utilisateur, y compris le nom associé à son rôle et à son entreprise.
 * Elle complète ces informations avec les 10 derniers événements de connexion (login et logout).
 * De plus, pour un praticien (role_id == 2), elle ajoute la liste des 10 dernières prestations,
 * et pour un salarié (role_id == 1), elle fournit les 10 dernières réservations.
 *
 * @param int $id Identifiant unique de l'utilisateur.
 * @return array|false Tableau associatif contenant les informations de l'utilisateur avec son historique et ses activités,
 *                     ou false si aucun utilisateur n'est trouvé.
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
 * Récupère la liste des rôles.
 *
 * Retourne l'ensemble des rôles présents dans la base de données, triés par ordre alphabétique croissant selon leur nom.
 *
 * @return array La liste des rôles, chaque rôle étant représenté par un tableau associatif.
 */
function usersGetRoles() {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM roles ORDER BY nom ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère la liste des entreprises triées par ordre alphabétique.
 *
 * Cette fonction se connecte à la base de données et exécute une requête préparée pour obtenir
 * l'identifiant et le nom de chaque entreprise à partir de la table "entreprises". Le résultat,
 * organisé par nom en ordre ascendant, est destiné à être utilisé dans les formulaires de l'application.
 *
 * @return array Liste des entreprises, chaque entrée étant un tableau associatif avec les clés "id" et "nom".
 */
function usersGetEntreprises() {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, nom FROM entreprises ORDER BY nom ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Crée ou met à jour un utilisateur.
 *
 * Cette fonction effectue la validation des champs obligatoires du formulaire utilisateur,
 * vérifie l'unicité de l'email et procède à l'insertion ou à la mise à jour des données en base.
 * Lors de la création, le mot de passe est nécessaire et sera hashé avant stockage.
 * En cas d'erreur de validation ou de problème lors de l'opération en base, la fonction renvoie
 * un tableau contenant les messages d'erreur.
 *
 * @param array $data Données de l'utilisateur, incluant 'nom', 'prenom', 'email', 'telephone',
 *                    'adresse', 'code_postal', 'ville', 'role_id', 'entreprise_id', 'mot_de_passe' et 'statut'.
 * @param int $id Identifiant de l'utilisateur (0 pour création).
 * @return array Tableau indiquant le succès de l'opération, avec une clé 'success' et, selon le cas,
 *               un message de confirmation dans 'message' ou une liste d'erreurs dans 'errors'.
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
            if (!empty($data['mot_de_passe'])) {
                $sql .= ", mot_de_passe = ?";
                $params[] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
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
            
            $message = "L'utilisateur a ete cree avec succes";
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
    } catch (PDOException $e) {
        $errors[] = "Erreur de base de données : " . $e->getMessage();
        
        // log l'erreur pour l'administrateur
        error_log("Erreur DB dans users/index.php : " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/**
 * Supprime l'utilisateur identifié par son ID si aucune association critique n'existe.
 *
 * La fonction vérifie d'abord que l'utilisateur n'est pas associé à des prestations (pour les praticiens)
 * ni à des réservations (pour les salariés). Si une de ces associations est détectée, la suppression est annulée
 * et un tableau avec un message explicatif est renvoyé. Sinon, les logs liés à l'utilisateur sont supprimés
 * ainsi que l'enregistrement correspondant dans la table des personnes.
 *
 * @param int $id Identifiant de l'utilisateur.
 * @return array Tableau indiquant le succès de l'opération et contenant un message informatif.
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
        return [
            'success' => false,
            'message' => "Impossible de supprimer cet utilisateur car il a des prestations associees"
        ];
    } else if ($reservationsCount > 0) {
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
        
        return [
            'success' => true,
            'message' => "L'utilisateur a ete supprime avec succes"
        ];
    }
} 