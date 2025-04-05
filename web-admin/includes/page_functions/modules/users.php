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
 * @param string $statut Filtre par statut ('actif', 'inactif', etc.)
 * @return array Donnees de pagination et liste des utilisateurs
 */
function usersGetList($page = 1, $perPage = 10, $search = '', $roleId = 0, $entrepriseId = 0, $statut = '') {
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
    
    if ($statut && in_array($statut, USER_STATUSES)) {
        $conditions[] = "p.statut = ?";
        $params[] = $statut;
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
                   ORDER BY created_at DESC LIMIT 10"; 
    $user['login_history'] = executeQuery($sqlHistory, [$id])->fetchAll();
    
    if (isset($user['role_id']) && $user['role_id'] == ROLE_PRESTATAIRE) { 
        $sqlAppointments = "SELECT rv.*, pr.nom as prestation_nom, cust.prenom as client_prenom, cust.nom as client_nom
                           FROM rendez_vous rv
                           LEFT JOIN prestations pr ON rv.prestation_id = pr.id
                           LEFT JOIN personnes cust ON rv.personne_id = cust.id
                           WHERE rv.praticien_id = ?
                           ORDER BY rv.date_rdv DESC LIMIT 10";
        $user['appointments_given'] = executeQuery($sqlAppointments, [$id])->fetchAll();
    }
    
    if (isset($user['role_id']) && $user['role_id'] == ROLE_SALARIE) { 
        $sqlReservations = "SELECT rv.*, pr.nom as prestation_nom, p.id as praticien_id, p.prenom as praticien_prenom, p.nom as praticien_nom
                            FROM rendez_vous rv
                            LEFT JOIN prestations pr ON rv.prestation_id = pr.id
                            LEFT JOIN personnes p ON rv.praticien_id = p.id
                            WHERE rv.personne_id = ?
                            ORDER BY rv.date_rdv DESC LIMIT 10";
        $user['reservations'] = executeQuery($sqlReservations, [$id])->fetchAll();

        $sqlEvaluations = "SELECT e.*, pr.nom as prestation_nom 
                           FROM evaluations e
                           JOIN prestations pr ON e.prestation_id = pr.id
                           WHERE e.personne_id = ? 
                           ORDER BY e.date_evaluation DESC 
                           LIMIT 10";
        $user['evaluations_submitted'] = executeQuery($sqlEvaluations, [$id])->fetchAll();

        $sqlDonations = "SELECT * FROM dons WHERE personne_id = ? ORDER BY date_don DESC LIMIT 10";
        $user['donations_made'] = executeQuery($sqlDonations, [$id])->fetchAll();
    }
    
    if (isset($user['role_id']) && $user['role_id'] == ROLE_ENTREPRISE && isset($user['entreprise_id'])) {
        $entrepriseId = $user['entreprise_id'];
        
        $sqlContracts = "SELECT * FROM contrats WHERE entreprise_id = ? ORDER BY date_debut DESC LIMIT 5";
        $user['company_contracts'] = executeQuery($sqlContracts, [$entrepriseId])->fetchAll();
        
        $sqlInvoices = "SELECT * FROM factures WHERE entreprise_id = ? ORDER BY date_emission DESC LIMIT 10";
        $user['company_invoices'] = executeQuery($sqlInvoices, [$entrepriseId])->fetchAll();
        
        $sqlEmployees = "SELECT id, nom, prenom, email, statut FROM personnes 
                         WHERE entreprise_id = ? AND role_id = ? 
                         ORDER BY nom, prenom LIMIT 10";
        $user['company_employees'] = executeQuery($sqlEmployees, [$entrepriseId, ROLE_SALARIE])->fetchAll();
        
        $sqlQuotes = "SELECT * FROM devis WHERE entreprise_id = ? ORDER BY date_creation DESC LIMIT 10";
        $user['company_quotes'] = executeQuery($sqlQuotes, [$entrepriseId])->fetchAll();

        $sqlCurrentContract = "SELECT id, statut, date_fin FROM contrats 
                               WHERE entreprise_id = ? AND statut = 'actif' 
                               ORDER BY date_debut DESC LIMIT 1";
        $user['current_contract_status'] = executeQuery($sqlCurrentContract, [$entrepriseId])->fetch();
        
        $sqlActiveEmployees = "SELECT COUNT(id) as count FROM personnes WHERE entreprise_id = ? AND role_id = ? AND statut = 'actif'";
        $user['stats_active_employees'] = executeQuery($sqlActiveEmployees, [$entrepriseId, ROLE_SALARIE])->fetchColumn();

        $sqlRecentReservations = "SELECT COUNT(rv.id) as count FROM rendez_vous rv 
                                  JOIN personnes p ON rv.personne_id = p.id 
                                  WHERE p.entreprise_id = ? AND rv.date_rdv >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $user['stats_recent_reservations'] = executeQuery($sqlRecentReservations, [$entrepriseId])->fetchColumn();
        
        $sqlTopPrestations = "SELECT pr.nom, COUNT(rv.id) as count 
                              FROM rendez_vous rv 
                              JOIN personnes p ON rv.personne_id = p.id
                              JOIN prestations pr ON rv.prestation_id = pr.id
                              WHERE p.entreprise_id = ?
                              GROUP BY pr.id, pr.nom
                              ORDER BY count DESC LIMIT 3";
        $user['stats_top_prestations'] = executeQuery($sqlTopPrestations, [$entrepriseId])->fetchAll();

        $sqlAvgSatisfaction = "SELECT AVG(e.note) as avg_score 
                               FROM evaluations e
                               JOIN personnes p ON e.personne_id = p.id
                               WHERE p.entreprise_id = ?";
        $avgScore = executeQuery($sqlAvgSatisfaction, [$entrepriseId])->fetchColumn();
        $user['stats_avg_satisfaction'] = ($avgScore !== null) ? round($avgScore, 2) : null;
    }
    
    if (isset($user['role_id']) && $user['role_id'] == ROLE_ADMIN) {
        $sqlAdminActions = "SELECT action, details, ip_address, created_at 
                            FROM logs 
                            WHERE personne_id = ? 
                            AND action NOT LIKE '%login%' 
                            AND action NOT LIKE '%logout%'
                            AND action NOT LIKE '%session_timeout%'
                            AND action NOT LIKE '%role_check%'      
                            AND action NOT LIKE '%user_info%'       
                            ORDER BY created_at DESC 
                            LIMIT 15";
        $user['admin_actions'] = executeQuery($sqlAdminActions, [$id])->fetchAll();

        $sqlSecurityActions = "SELECT action, details, ip_address, created_at 
                               FROM logs 
                               WHERE personne_id = ? 
                               AND (action LIKE '[SECURITY]%' OR action LIKE '[SECURITY FAILURE]%')
                               ORDER BY created_at DESC 
                               LIMIT 10";
        $user['security_actions'] = executeQuery($sqlSecurityActions, [$id])->fetchAll();
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
        'role_id' => (int)$data['role_id'],
        'entreprise_id' => !empty($data['entreprise_id']) ? (int)$data['entreprise_id'] : null, 
        'statut' => $data['statut'] ?? 'inactif'
    ];
            
    $passwordChanged = false;
    if (!empty($data['mot_de_passe'])) {
        $dbData['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        $passwordChanged = true;
    }

    try {
        if ($id > 0) {
            $affectedRows = updateRow('personnes', $dbData, "id = :where_id", ['where_id' => $id]);
            
            if ($affectedRows !== false) { 
                 logBusinessOperation($_SESSION['user_id'], ':user_update', 
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
                logBusinessOperation($_SESSION['user_id'], ':user_create', 
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

    $reservationsCount = executeQuery(
        "SELECT COUNT(id) FROM rendez_vous WHERE personne_id = ? OR praticien_id = ?",
        [$id, $id]
    )->fetchColumn();

    if ($reservationsCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'user_delete_attempt',
            "[ERROR] Tentative échouée de suppression utilisateur ID: $id - Rendez-vous associés existent (client ou praticien)");
        return [
            'success' => false,
            'message' => "Impossible de supprimer cet utilisateur car il a des rendez-vous associés (en tant que client ou praticien)"
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
                'message' => "L'utilisateur et ses données associées ont été supprimés avec succès"
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