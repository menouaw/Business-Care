<?php
require_once __DIR__ . '/../../init.php';

/**
 * Récupère une liste paginée d'utilisateurs avec possibilité de filtrer par recherche, rôle, entreprise et statut.
 *
 * La fonction construit dynamiquement une clause SQL en fonction des filtres fournis pour retourner non seulement
 * la liste des utilisateurs, mais également les informations de pagination telles que le numéro de page actuel,
 * le nombre total de pages, le nombre total d'utilisateurs correspondants aux critères, et le nombre d'éléments par page.
 *
 * @param int $page Numéro de la page (défaut : 1).
 * @param int $perPage Nombre d'éléments par page (défaut : 10).
 * @param string $search Terme de recherche utilisé pour filtrer les utilisateurs par nom, prénom ou email.
 * @param int $roleId Identifiant du rôle pour filtrer les résultats (0 pour ignorer ce filtre).
 * @param int $entrepriseId Identifiant de l'entreprise pour filtrer les résultats (0 pour ignorer ce filtre).
 * @param string $statut Statut de l'utilisateur pour filtrer les résultats (e.g., 'actif', 'inactif', 'en_attente', 'suspendu').
 * @return array Tableau associatif contenant :
 *               - 'users': Liste des utilisateurs avec leur rôle et le nom de leur entreprise.
 *               - 'currentPage': Numéro de la page courante.
 *               - 'totalPages': Nombre total de pages calculé.
 *               - 'totalItems': Nombre total d'utilisateurs répondant aux critères de filtrage.
 *               - 'perPage': Nombre d'utilisateurs affichés par page.
 */
function usersGetList($page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE, $search = '', $roleId = 0, $entrepriseId = 0, $statut = '') {
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
 * Récupère les détails complets d'un utilisateur et des informations complémentaires selon son rôle.
 *
 * Cette fonction renvoie les informations personnelles de l'utilisateur, incluant son rôle et le nom de l'entreprise associée le cas échéant,
 * ainsi que son historique de connexion. En fonction du rôle de l'utilisateur, des données supplémentaires sont ajoutées :
 * - Pour un prestataire : les rendez-vous donnés.
 * - Pour un salarié : les réservations effectuées, les évaluations soumises et les dons réalisés.
 * - Pour une entreprise : les contrats, factures, employés, devis, ainsi que diverses statistiques (contrat actuel, nombre d'employés actifs,
 *   réservations récentes, prestations les plus sollicitées et moyenne de satisfaction).
 * - Pour un administrateur : les actions administratives (hors connexions) et les actions liées à la sécurité.
 *
 * @param int $id L'identifiant de l'utilisateur.
 * @return array|false Les données détaillées de l'utilisateur ou false si l'utilisateur n'est pas trouvé.
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
 * Valide les informations fournies dans le tableau de données ($data) pour s'assurer que les champs obligatoires
 * (nom, prénom, email, et mot de passe lors de la création) ainsi que le rôle et, le cas échéant, l'entreprise sont corrects.
 * Vérifie également l'unicité de l'email avant de procéder à l'insertion ou à la mise à jour dans la table 'personnes'.
 * En cas de données invalides, retourne immédiatement un tableau d'erreurs sans modifier la base.
 * Si l'opération est réussie, consigne l'action dans les logs business et de sécurité et retourne un tableau indiquant le succès
 * de l'opération. Pour une création, le résultat inclut la clé 'newId' correspondant au nouvel identifiant de l'utilisateur.
 *
 * @param array $data Informations de l'utilisateur incluant au minimum 'nom', 'prenom', 'email', 'mot_de_passe' (lors de la création), 'role_id'
 *                    et éventuellement 'entreprise_id' et 'statut'.
 * @param int $id Identifiant de l'utilisateur à mettre à jour ou 0 pour créer un nouvel utilisateur.
 * @return array Tableau contenant 'success' (bool) et, selon le résultat, 'message' (string|null) ou 'errors' (array|null).
 */
function usersSave($data, $id = 0) {
    $errors = [];
    $isNewUser = ($id == 0);

    $data['nom'] = trim($data['nom'] ?? '');
    $data['prenom'] = trim($data['prenom'] ?? '');
    $data['email'] = trim($data['email'] ?? '');
    $data['telephone'] = trim($data['telephone'] ?? '');
    $data['role_id'] = isset($data['role_id']) ? (int)$data['role_id'] : 0;
    $data['entreprise_id'] = isset($data['entreprise_id']) && $data['entreprise_id'] !== '' ? (int)$data['entreprise_id'] : null;
    $data['statut'] = $data['statut'] ?? ($isNewUser ? 'actif' : null);
    $data['mot_de_passe'] = $data['mot_de_passe'] ?? '';
    $confirmPassword = $data['mot_de_passe_confirm'] ?? ''; 

    if (empty($data['nom'])) $errors['nom'] = "Le nom est obligatoire.";
    if (empty($data['prenom'])) $errors['prenom'] = "Le prénom est obligatoire.";

    if (empty($data['email'])) {
        $errors['email'] = "L'email est obligatoire.";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "L'email n'est pas valide.";
    } else {
        $existingUser = fetchOne('personnes', 'email = ? AND id != ?', '', [$data['email'], $id]);
        if ($existingUser) {
            $errors['email'] = "Cet email est déjà utilisé par un autre utilisateur.";
            logSystemActivity('user_duplicate_email', "[WARNING] Tentative d'utilisation d'email déjà existant: {$data['email']}");
        }
    }

    if ($isNewUser && empty($data['mot_de_passe'])) {
        $errors['mot_de_passe'] = "Le mot de passe est obligatoire lors de la création.";
    }
    if (!empty($data['mot_de_passe']) && $data['mot_de_passe'] !== $confirmPassword) {
        $errors['mot_de_passe_confirm'] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($data['role_id']) || !fetchOne('roles', 'id = ?', '', [$data['role_id']])) {
        $errors['role_id'] = "Le rôle sélectionné est invalide.";
    }

    if ($data['entreprise_id'] !== null && !fetchOne('entreprises', 'id = ?', '', [$data['entreprise_id']])) {
         $errors['entreprise_id'] = "L'entreprise sélectionnée est invalide.";
    }

    if (empty($data['statut']) || !in_array($data['statut'], USER_STATUSES)) {
        $errors['statut'] = "Le statut sélectionné est invalide.";
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    $dbData = [
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'email' => $data['email'],
        'telephone' => $data['telephone'] ?: null,
        'role_id' => $data['role_id'],
        'entreprise_id' => $data['entreprise_id'],
        'statut' => $data['statut'],
    ];
            
    $passwordChanged = false;
    if (!empty($data['mot_de_passe'])) {
        $dbData['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        $passwordChanged = true;
    }

    try {
        beginTransaction();

        if (!$isNewUser) {
            $affectedRows = updateRow('personnes', $dbData, "id = :where_id", ['where_id' => $id]);
            
            if ($affectedRows !== false) { 
                 logBusinessOperation($_SESSION['user_id'], ':user_update',
                    "[SUCCESS] Mise à jour utilisateur: {$dbData['prenom']} {$dbData['nom']} (ID: $id), role: {$dbData['role_id']}, statut: {$dbData['statut']}");
                if ($passwordChanged && $id != ($_SESSION['user_id'] ?? 0)) { 
                     logSecurityEvent($_SESSION['user_id'], 'password_change_admin',
                        "[SUCCESS] Modification mot de passe pour utilisateur ID: $id par admin ID: {$_SESSION['user_id']}");
                } elseif ($passwordChanged && $id == ($_SESSION['user_id'] ?? 0)) {
                    logSecurityEvent($_SESSION['user_id'], 'password_change_self',
                       "[SUCCESS] Modification de son propre mot de passe par utilisateur ID: $id");
                }
                $message = "L'utilisateur a été mis à jour avec succès.";
                 commitTransaction();
                 return ['success' => true, 'message' => $message, 'userId' => $id];
            } else {
                 throw new Exception("La mise à jour a échoué ou aucune donnée n'a été modifiée.");
            }
        }
        else { 
            $newId = insertRow('personnes', $dbData);
            
            if ($newId) {
                logBusinessOperation($_SESSION['user_id'], ':user_create',
                    "[SUCCESS] Création utilisateur: {$dbData['prenom']} {$dbData['nom']} (ID: $newId), role: {$dbData['role_id']}, statut: {$dbData['statut']}");
                logSecurityEvent($_SESSION['user_id'], 'account_creation',
                    "[SUCCESS] Création compte pour {$dbData['email']} (ID: $newId, role: {$dbData['role_id']}) par admin ID: {$_SESSION['user_id']}");
                $message = "L'utilisateur a été créé avec succès.";
                 commitTransaction();
                 return ['success' => true, 'message' => $message, 'newId' => $newId];
            } else {
                 throw new Exception("L'insertion a échoué.");
            }
        }
        
    } catch (Exception $e) {
        rollbackTransaction();
        $action = $isNewUser ? 'création' : 'mise à jour';
        $errorMessage = "Erreur lors de la {$action} de l'utilisateur : " . $e->getMessage();
        logSystemActivity('user_save_error', "[ERROR] Erreur BDD dans usersSave (Action: {$action}, ID: {$id}): " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => ['db_error' => $errorMessage]
        ];
    }
}

/**
 * Supprime un utilisateur en vérifiant l'absence de rendez-vous associés et en empêchant la suppression du compte connecté.
 *
 * La fonction empêche la suppression du compte actuellement connecté et vérifie qu'aucun rendez-vous (en tant que client ou praticien) n'est associé à l'utilisateur.
 * Si aucune association n'est détectée, elle supprime l'utilisateur ainsi que ses données liées (logs, tokens de connexion et préférences) à l'aide d'une transaction pour garantir la cohérence des opérations.
 *
 * @param int $id Identifiant de l'utilisateur à supprimer.
 * @return array Tableau contenant 'success' (booléen indiquant le résultat) et 'message' (description du résultat de l'opération).
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
