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
 * Désactive (marque comme 'inactif') un salarié appartenant à une entreprise spécifique.
 *
 * @param int $employee_id L'ID du salarié à désactiver.
 * @param int $company_id L'ID de l'entreprise propriétaire du salarié (pour vérification).
 * @return bool Retourne true si la désactivation a réussi, false sinon.
 */
function deactivateEmployee(int $employee_id, int $company_id): bool
{
    if ($employee_id <= 0 || $company_id <= 0) {
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
        ['statut' => 'inactif'],
        'id = :id',
        [':id' => $employee_id]
    );


    if ($updatedRows > 0) {
        $actor_id = $_SESSION['user_id'] ?? null;
        if ($actor_id && function_exists('createNotification')) {
            $employee_name = htmlspecialchars($employee['prenom'] . ' ' . $employee['nom']);
            $title = 'Salarié désactivé';
            $message = "Le salarié {$employee_name} (ID: {$employee_id}) a été désactivé.";
            $link = WEBCLIENT_URL . '/modules/companies/employees/index.php';
            createNotification($actor_id, $title, $message, 'warning', $link);
        }
        return true;
    }


    return false;
}

/**
 * Réactive (marque comme 'actif') un salarié appartenant à une entreprise spécifique.
 *
 * @param int $employee_id L'ID du salarié à réactiver.
 * @param int $company_id L'ID de l'entreprise propriétaire du salarié (pour vérification).
 * @return bool Retourne true si la réactivation a réussi, false sinon.
 */
function reactivateEmployee(int $employee_id, int $company_id): bool
{
    if ($employee_id <= 0 || $company_id <= 0) {
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
        ['statut' => 'actif'],
        'id = :id',
        [':id' => $employee_id]
    );


    if ($updatedRows > 0) {
        $actor_id = $_SESSION['user_id'] ?? null;
        if ($actor_id && function_exists('createNotification')) {
            $employee_name = htmlspecialchars($employee['prenom'] . ' ' . $employee['nom']);
            $title = 'Salarié réactivé';
            $message = "Le salarié {$employee_name} (ID: {$employee_id}) a été réactivé.";
            $link = WEBCLIENT_URL . '/modules/companies/employees/index.php';
            createNotification($actor_id, $title, $message, 'success', $link);
        }
        return true;
    }


    return false;
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
 * Met à jour les détails d'un salarié.
 *
 * @param int $employee_id L'ID du salarié à mettre à jour.
 * @param int $company_id L'ID de l'entreprise (pour vérification).
 * @param array $data Les données à mettre à jour.
 * @return bool True si la mise à jour réussit, false sinon.
 */
function updateEmployeeDetails(int $employee_id, int $company_id, array $data): bool
{
    if ($employee_id <= 0 || $company_id <= 0 || empty($data)) {
        return false;
    }


    $employee = getEmployeeDetails($employee_id, $company_id);
    if (!$employee) {
        return false;
    }



    $allowed_data = $data;
    unset($allowed_data['statut'], $allowed_data['role_id'], $allowed_data['entreprise_id'], $allowed_data['mot_de_passe']);

    if (empty($allowed_data)) {
        flashMessage("Aucune donnée modifiable fournie.", "info");
        return false;
    }


    $updatedRows = updateRow(
        'personnes',
        $allowed_data,
        'id = :id',
        [':id' => $employee_id]
    );





    if ($updatedRows !== false) {
        $actor_id = $_SESSION['user_id'] ?? null;
        if ($actor_id && function_exists('createNotification')) {

            $employee_name = htmlspecialchars($employee['prenom'] . ' ' . $employee['nom']);
            $title = 'Salarié modifié';
            $message = "Les informations du salarié {$employee_name} (ID: {$employee_id}) ont été mises à jour.";

            $link = WEBCLIENT_URL . '/modules/companies/employees/edit.php?id=' . $employee_id;
            createNotification($actor_id, $title, $message, 'info', $link);
        }

        return true;
    }



    flashMessage("Erreur lors de la mise à jour du salarié.", "danger");
    return false;
}

/**
 * Ajoute un nouveau salarié à une entreprise.
 *
 * @param int $entreprise_id ID de l'entreprise.
 * @param array $employeeData Données du salarié (nom, prenom, email, telephone, site_id).
 * @return int|false L'ID du nouveau salarié en cas de succès, false sinon.
 */
function addEmployee($entreprise_id, $employeeData)
{
    if (empty($entreprise_id) || empty($employeeData['nom']) || empty($employeeData['prenom']) || empty($employeeData['email'])) {
        flashMessage("Les champs Nom, Prénom et Email sont obligatoires.", "danger");
        return false;
    }

    if (!filter_var($employeeData['email'], FILTER_VALIDATE_EMAIL)) {
        flashMessage("Le format de l'adresse email est invalide.", "danger");
        return false;
    }

    $existingUser = fetchOne('personnes', 'email = :email', [':email' => $employeeData['email']]);
    if ($existingUser) {
        flashMessage("L'adresse email " . htmlspecialchars($employeeData['email']) . " existe déjà.", "warning");
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

    $newEmployeeId = insertRow('personnes', $dataToInsert);

    if ($newEmployeeId) {
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

        return (int)$newEmployeeId;
    } else {
        logSecurityEvent($_SESSION['user_id'] ?? null, 'employee_add', '[FAILURE] Échec ajout salarié pour entreprise ID: ' . $entreprise_id . ' Data: ' . json_encode($employeeData), true);
        flashMessage("Une erreur technique est survenue lors de l'ajout du salarié.", "danger");
        return false;
    }
}
