<?php

require_once __DIR__ . '/../../../init.php';

/**
 * Récupère la liste paginée des salariés pour une entreprise donnée.
 *
 * @param int $entreprise_id L'ID de l'entreprise.
 * @param int $current_page La page actuelle.
 * @param int $items_per_page Le nombre d'éléments par page.
 * @return array Un tableau contenant 'employees' (liste des salariés pour la page) et 'total_count' (nombre total de salariés).
 */
function getCompanyEmployees(int $entreprise_id, int $current_page = 1, int $items_per_page = 10): array
{
    if ($entreprise_id <= 0) {
        return ['employees' => [], 'total_count' => 0];
    }

    $baseSqlWhere = "FROM personnes p 
                     LEFT JOIN sites s ON p.site_id = s.id
                     WHERE p.entreprise_id = :entreprise_id 
                     AND p.role_id = :role_salarie";
    $params = [
        ':entreprise_id' => $entreprise_id,
        ':role_salarie' => ROLE_SALARIE
    ];


    $countSql = "SELECT COUNT(p.id) as total " . $baseSqlWhere;
    $countStmt = executeQuery($countSql, $params);
    $total_count = $countStmt->fetchColumn() ?: 0;


    $offset = max(0, ($current_page - 1) * $items_per_page);
    $limit = max(1, $items_per_page);

    $sql = "SELECT 
                p.id, 
                p.nom, 
                p.prenom, 
                p.email, 
                p.telephone, 
                p.statut, 
                p.derniere_connexion, 
                s.nom as site_nom 
            " . $baseSqlWhere . "
            ORDER BY 
                p.nom ASC, p.prenom ASC
            LIMIT :limit OFFSET :offset";


    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    $stmt = executeQuery($sql, $params);
    $employees = $stmt->fetchAll();


    return [
        'employees' => $employees,
        'total_count' => $total_count
    ];
}

/**
 * Fonction privée pour changer le statut d'un salarié et gérer les notifications.
 *
 * @param int    $employee_id          ID du salarié.
 * @param int    $company_id           ID de l'entreprise (pour vérification).
 * @param string $newStatus            Nouveau statut ('actif' ou 'inactif').
 * @param string $notificationTitle    Titre pour la notification.
 * @param string $notificationVerb     Verbe pour le message de notification (ex: "désactivé", "réactivé").
 * @param string $notificationType     Type de notification ('success', 'warning', 'info', 'danger').
 * @return bool                        True si le changement a réussi, false sinon.
 */
function _changeEmployeeStatus(int $employee_id, int $company_id, string $newStatus, string $notificationTitle, string $notificationVerb, string $notificationType): bool
{
    if ($employee_id <= 0 || $company_id <= 0 || !in_array($newStatus, ['actif', 'inactif'])) {
        return false;
    }

    
    $employee = fetchOne('personnes', 'id = :id AND entreprise_id = :company_id AND role_id = :role_salarie', [
        ':id' => $employee_id,
        ':company_id' => $company_id,
        ':role_salarie' => ROLE_SALARIE
    ]);

    if (!$employee) {
        
        return false;
    }

    
    $updatedRows = updateRow(
        'personnes',
        ['statut' => $newStatus],
        'id = :id',
        [':id' => $employee_id]
    );

    
    if ($updatedRows > 0) {
        $actor_id = $_SESSION['user_id'] ?? null;
        if ($actor_id && function_exists('createNotification')) {
            $employee_name = htmlspecialchars($employee['prenom'] . ' ' . $employee['nom']);
            
            $message = sprintf("Le salarié %s (ID: %d) a été %s.", $employee_name, $employee_id, $notificationVerb);
            $link = WEBCLIENT_URL . '/modules/companies/employees/index.php';
            
            createNotification($actor_id, $notificationTitle, $message, $notificationType, $link);
        }
        return true; 
    }

    
    return false;
}

/**
 * Désactive (marque comme 'inactif') un salarié appartenant à une entreprise spécifique.
 * Appelle la fonction privée _changeEmployeeStatus.
 *
 * @param int $employee_id L'ID du salarié à désactiver.
 * @param int $company_id L'ID de l'entreprise propriétaire du salarié (pour vérification).
 * @return bool Retourne true si la désactivation a réussi, false sinon.
 */
function deactivateEmployee(int $employee_id, int $company_id): bool
{
    return _changeEmployeeStatus(
        $employee_id,
        $company_id,
        'inactif',
        'Salarié désactivé',
        'désactivé',
        'warning'
    );
}

/**
 * Réactive (marque comme 'actif') un salarié appartenant à une entreprise spécifique.
 * Appelle la fonction privée _changeEmployeeStatus.
 *
 * @param int $employee_id L'ID du salarié à réactiver.
 * @param int $company_id L'ID de l'entreprise propriétaire du salarié (pour vérification).
 * @return bool Retourne true si la réactivation a réussi, false sinon.
 */
function reactivateEmployee(int $employee_id, int $company_id): bool
{
    return _changeEmployeeStatus(
        $employee_id,
        $company_id,
        'actif',
        'Salarié réactivé',
        'réactivé',
        'success'
    );
}

/**
 * Récupère les détails complets d'un salarié spécifique appartenant à une entreprise.
 *
 * @param int $employee_id L'ID du salarié.
 * @param int $company_id L'ID de l'entreprise pour vérification.
 * @return array|false Les détails du salarié ou false si non trouvé ou n'appartient pas à l'entreprise.
 */
function getEmployeeDetails(int $employee_id, int $company_id): array|false
{
    if ($employee_id <= 0 || $company_id <= 0) {
        return false;
    }

    return fetchOne(
        'personnes',
        'id = :id AND entreprise_id = :company_id AND role_id = :role_salarie',
        [
            ':id' => $employee_id,
            ':company_id' => $company_id,
            ':role_salarie' => ROLE_SALARIE
        ]
    );
}

/**
 * Récupère la liste des sites pour une entreprise donnée.
 *
 * @param int $company_id L'ID de l'entreprise.
 * @return array La liste des sites (id, nom).
 */
function getCompanySites(int $company_id): array
{
    if ($company_id <= 0) {
        return [];
    }

    $sql = "SELECT id, nom FROM sites WHERE entreprise_id = :company_id ORDER BY nom ASC";
    $stmt = executeQuery($sql, [':company_id' => $company_id]);
    return $stmt->fetchAll();
}

/**
 * Valide les données d'entrée pour la mise à jour d'un salarié.
 *
 * @param int $employee_id ID du salarié.
 * @param int $company_id ID de l'entreprise.
 * @param array $data Données de mise à jour.
 * @return bool True si les données de base sont valides, false sinon.
 */
function _validateUpdateEmployeeInput(int $employee_id, int $company_id, array $data): bool
{
    if ($employee_id <= 0 || $company_id <= 0 || empty($data)) {
        
        return false;
    }
    return true;
}

/**
 * Filtre les données de mise à jour du salarié pour n'autoriser que certains champs.
 *
 * @param array $data Données brutes de mise à jour.
 * @return array Données filtrées et autorisées pour la mise à jour.
 */
function _filterUpdateData(array $data): array
{
    $allowed_data = $data;
    
    unset(
        $allowed_data['id'],
        $allowed_data['statut'],
        $allowed_data['role_id'],
        $allowed_data['entreprise_id'],
        $allowed_data['mot_de_passe'],
        $allowed_data['email'] 
    );
    return $allowed_data;
}

/**
 * Gère les actions post-succès de la mise à jour d'un salarié (log, flash, notification).
 *
 * @param array $employee Données actuelles du salarié (avant mise à jour).
 * @param int $updatedRows Nombre de lignes affectées par la mise à jour.
 * @param int $employee_id ID du salarié mis à jour.
 * @param array $allowed_data Données qui ont été tentées pour la mise à jour.
 */
function _handleSuccessfulEmployeeUpdate(array $employee, int $updatedRows, int $employee_id, array $allowed_data): void
{
    $actor_id = $_SESSION['user_id'] ?? null;
    if ($actor_id && function_exists('createNotification')) {
        $employee_name = htmlspecialchars($employee['prenom'] . ' ' . $employee['nom']);
        $title = 'Salarié modifié';
        $message = "Les informations du salarié {$employee_name} (ID: {$employee_id}) ont été mises à jour.";
        if ($updatedRows === 0 && !empty($allowed_data)) {
            $message = "Tentative de mise à jour pour {$employee_name} (ID: {$employee_id}), mais aucune valeur n'a changé.";
        }
        $link = WEBCLIENT_URL . '/modules/companies/employees/edit.php?id=' . $employee_id;
        createNotification($actor_id, $title, $message, 'info', $link);
    }
    flashMessage("Mise à jour du salarié effectuée.", "success");
}

/**
 * Met à jour les détails d'un salarié.
 * Refactorisé pour utiliser des fonctions d'aide pour la validation, le filtrage des données et la gestion du succès.
 *
 * @param int $employee_id L'ID du salarié à mettre à jour.
 * @param int $company_id L'ID de l'entreprise (pour vérification).
 * @param array $data Les données à mettre à jour.
 * @return bool True si la mise à jour réussit, false sinon.
 */
function updateEmployeeDetails(int $employee_id, int $company_id, array $data): bool
{
    if (!_validateUpdateEmployeeInput($employee_id, $company_id, $data)) {
        flashMessage("Données d'entrée invalides pour la mise à jour du salarié.", "danger");
        return false;
    }

    $employee = getEmployeeDetails($employee_id, $company_id);
    if (!$employee) {
        flashMessage("Salarié non trouvé ou non modifiable.", "warning");
        return false;
    }

    $allowed_data = _filterUpdateData($data);

    if (empty($allowed_data)) {
        flashMessage("Aucune donnée modifiable fournie pour le salarié.", "info");
        return false; 
    }

    $updatedRows = updateRow(
        'personnes',
        $allowed_data,
        'id = :id AND entreprise_id = :company_id',
        [':id' => $employee_id, ':company_id' => $company_id]
    );

    if ($updatedRows !== false) { 
        _handleSuccessfulEmployeeUpdate($employee, (int)$updatedRows, $employee_id, $allowed_data);
        return true;
    }

    flashMessage("Erreur lors de la mise à jour du salarié.", "danger");
    return false;
}

/**
 * Valide les données d'entrée pour l'ajout d'un salarié.
 *
 * @param int $entreprise_id ID de l'entreprise.
 * @param array $employeeData Données du salarié.
 * @return bool True si les données sont valides, false sinon.
 */
function _validateAddEmployeeInput(int $entreprise_id, array $employeeData): bool
{
    if (empty($entreprise_id) || empty($employeeData['nom']) || empty($employeeData['prenom']) || empty($employeeData['email'])) {
        flashMessage("Les champs Nom, Prénom et Email sont obligatoires.", "danger");
        return false;
    }

    if (!filter_var($employeeData['email'], FILTER_VALIDATE_EMAIL)) {
        flashMessage("Le format de l'adresse email est invalide.", "danger");
        return false;
    }
    return true;
}

/**
 * Vérifie si un salarié existe déjà avec l'email donné.
 *
 * @param string $email Email à vérifier.
 * @return bool True si l'email existe, false sinon.
 */
function _checkExistingEmployeeByEmail(string $email): bool
{
    $existingUser = fetchOne('personnes', 'email = :email', [':email' => $email]);
    if ($existingUser) {
        flashMessage("L'adresse email " . htmlspecialchars($email) . " existe déjà.", "warning");
        return true; 
    }
    return false; 
}

/**
 * Gère les actions post-succès de l'ajout d'un salarié (log, flash, notification).
 *
 * @param array $dataToInsert Données du salarié inséré.
 * @param int $newEmployeeId ID du nouveau salarié.
 * @param int $entreprise_id ID de l'entreprise.
 */
function _handleSuccessfulEmployeeAddition(array $dataToInsert, int $newEmployeeId, int $entreprise_id): void
{
    logSecurityEvent($_SESSION['user_id'] ?? null, 'employee_add', '[SUCCESS] Ajout salarié ID: ' . $newEmployeeId . ' pour entreprise ID: ' . $entreprise_id);
    flashMessage("Salarié " . htmlspecialchars($dataToInsert['prenom'] . ' ' . $dataToInsert['nom']) . " ajouté avec succès. Un email d'activation devrait être envoyé.", "success");

    $actor_id = $_SESSION['user_id'] ?? null;
    if ($actor_id && function_exists('createNotification')) {
        $employee_name = htmlspecialchars($dataToInsert['prenom'] . ' ' . $dataToInsert['nom']);
        $title = 'Nouveau salarié ajouté';
        $message = "Le salarié {$employee_name} a été ajouté avec succès.";
        $link = WEBCLIENT_URL . '/modules/companies/employees/index.php';
        createNotification($actor_id, $title, $message, 'success', $link);
    }
}

/**
 * Tente d'insérer un nouveau salarié et de retourner son ID.
 * Gère la transaction interne.
 *
 * @param array $dataToInsert Données à insérer.
 * @return int|false L'ID du nouveau salarié ou false en cas d'échec.
 */
function _attemptEmployeeInsertion(array $dataToInsert, int $entreprise_id, array $originalEmployeeData): int|false
{
    $pdo = getDbConnection();
    try {
        beginTransaction();
        if (insertRow('personnes', $dataToInsert)) {
            $newEmployeeId = (int)$pdo->lastInsertId();
            if ($newEmployeeId > 0) {
                commitTransaction();
                return $newEmployeeId;
            } else {
                
                rollbackTransaction();
                flashMessage("Une erreur est survenue lors de la récupération de l'ID du nouveau salarié.", "danger");
                return false;
            }
        } else {
            rollbackTransaction();
            logSecurityEvent($_SESSION['user_id'] ?? null, 'employee_add', '[FAILURE] Échec insertRow pour entreprise ID: ' . $entreprise_id . ' Data: ' . json_encode($originalEmployeeData), true);
            flashMessage("Une erreur technique est survenue lors de l'insertion du salarié.", "danger");
            return false;
        }
    } catch (Exception $e) {
        rollbackTransaction();
        
        flashMessage("Une erreur exceptionnelle est survenue lors de l'ajout du salarié.", "danger");
        return false;
    }
}

/**
 * Ajoute un nouveau salarié à une entreprise.
 * Refactorisé pour utiliser des fonctions d'aide pour la validation, l'insertion et la gestion du succès.
 *
 * @param int $entreprise_id ID de l'entreprise.
 * @param array $employeeData Données du salarié (nom, prenom, email, telephone, site_id).
 * @return int|false L'ID du nouveau salarié en cas de succès, false sinon.
 */
function addEmployee($entreprise_id, $employeeData)
{
    if (!_validateAddEmployeeInput($entreprise_id, $employeeData)) {
        return false;
    }

    if (_checkExistingEmployeeByEmail(trim($employeeData['email']))) {
        return false;
    }

    $temporaryPassword = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);

    $dataToInsert = [
        'nom' => trim($employeeData['nom']),
        'prenom' => trim($employeeData['prenom']),
        'email' => trim($employeeData['email']),
        'mot_de_passe' => $hashedPassword,
        'telephone' => !empty($employeeData['telephone']) ? trim($employeeData['telephone']) : null,
        'role_id' => ROLE_SALARIE,
        'entreprise_id' => $entreprise_id,
        'site_id' => !empty($employeeData['site_id']) ? (int)$employeeData['site_id'] : null,
        'statut' => 'actif'
    ];

    $newEmployeeId = _attemptEmployeeInsertion($dataToInsert, $entreprise_id, $employeeData);

    if ($newEmployeeId !== false && $newEmployeeId > 0) {
        _handleSuccessfulEmployeeAddition($dataToInsert, $newEmployeeId, $entreprise_id);
        return $newEmployeeId;
    }

    
    return false;
}
