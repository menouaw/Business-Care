<?php

require_once __DIR__ . '/../../../init.php';

/**
 * Récupère les détails d'un employé pour l'affichage dans les paramètres.
 *
 * @param int $user_id L'ID de l'employé.
 * @return array|false Les détails ou false si non trouvé.
 */
function getEmployeeDetailsForSettings(int $user_id): array|false
{
    if ($user_id <= 0) {
        return false;
    }

    return fetchOne(TABLE_USERS, 'id = :id', [":id" => $user_id], 'id, nom, prenom, email, telephone, date_naissance, genre, photo_url');
}


/**
 * Met à jour le profil de l'employé (informations de base).
 *
 * @param int $user_id L'ID de l'employé.
 * @param array $formData Données du formulaire POST.
 * @return array Résultat [success => bool, message => string].
 */
function handleUpdateEmployeeProfile(int $user_id, array $formData): array
{
    if ($user_id <= 0) return ['success' => false, 'message' => 'Utilisateur invalide.'];

    $nom = trim($formData['nom'] ?? '');
    $prenom = trim($formData['prenom'] ?? '');
    $telephone = trim($formData['telephone'] ?? '');
    $date_naissance = trim($formData['date_naissance'] ?? '');
    $genre = $formData['genre'] ?? null;


    if (empty($nom) || empty($prenom)) {
        return ['success' => false, 'message' => 'Le nom et le prénom sont obligatoires.'];
    }
    if (!empty($date_naissance) && !strtotime($date_naissance)) {
        return ['success' => false, 'message' => 'Format de date de naissance invalide.'];
    }
    if ($genre !== null && !in_array($genre, ['M', 'F', 'Autre'])) {
        return ['success' => false, 'message' => 'Genre invalide.'];
    }

    $dataToUpdate = [
        'nom' => $nom,
        'prenom' => $prenom,
        'telephone' => $telephone ?: null,
        'date_naissance' => !empty($date_naissance) ? date('Y-m-d', strtotime($date_naissance)) : null,
        'genre' => $genre
    ];

    $updated = updateRow(TABLE_USERS, $dataToUpdate, 'id = :id', [':id' => $user_id]);
    if ($updated > 0) {
        $_SESSION['user_name'] = $prenom . ' ' . $nom;
        return ['success' => true, 'message' => 'Profil mis à jour avec succès.'];
    } else {
        return ['success' => true, 'message' => 'Aucune modification détectée.'];
    }
}

/**
 * Met à jour les préférences de l'employé.
 *
 * @param int $user_id L'ID de l'employé.
 * @param array $formData Données du formulaire POST.
 * @return array Résultat [success => bool, message => string].
 */
function handleUpdateEmployeePreferences(int $user_id, array $formData): array
{
    if ($user_id <= 0) return ['success' => false, 'message' => 'Utilisateur invalide.'];

    return ['success' => true, 'message' => 'Préférences mises à jour.'];
}

/**
 * Met à jour le mot de passe de l'employé.
 *
 * @param int $user_id L'ID de l'employé.
 * @param string $current_password Mot de passe actuel.
 * @param string $new_password Nouveau mot de passe.
 * @return array Résultat [success => bool, message => string].
 */
function updateEmployeePassword(int $user_id, string $current_password, string $new_password): array
{

    if (empty($current_password) || empty($new_password)) {
        return ['success' => false, 'message' => 'Les mots de passe ne peuvent pas être vides.'];
    }
    if ($current_password === $new_password) {
        return ["success" => false, "message" => "Le nouveau mot de passe doit être différent."];
    }
    if (strlen($new_password) < MIN_PASSWORD_LENGTH) {
        return ['success' => false, 'message' => 'Nouveau mot de passe trop court (min ' . MIN_PASSWORD_LENGTH . ' caractères).'];
    }

    $user = fetchOne(TABLE_USERS, 'id = :id', [":id" => $user_id], 'id, mot_de_passe');
    if (!$user) {
        return ['success' => false, 'message' => 'Utilisateur non trouvé.'];
    }

    $storedHash = $user['mot_de_passe'] ?? '';

    if (!password_verify($current_password, $storedHash)) {
        return ['success' => false, 'message' => 'Le mot de passe actuel est incorrect.'];
    }

    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    if ($new_password_hash === false) {
        return ['success' => false, 'message' => 'Erreur technique lors de la sécurisation.'];
    }

    $updated = updateRow(TABLE_USERS, ['mot_de_passe' => $new_password_hash], 'id = :id', [":id" => $user_id]);
    if ($updated > 0) {
        return ['success' => true, 'message' => 'Mot de passe mis à jour.'];
    } else {
        return ['success' => false, 'message' => 'Erreur technique lors de la mise à jour.'];
    }
}

/**
 * Met à jour les intérêts bien-être sélectionnés par l'employé.
 *
 * @param int $user_id L'ID de l'employé.
 * @param array $formData Données du formulaire POST (contenant le tableau 'interests').
 * @return array Résultat [success => bool, message => string].
 */
function handleUpdateEmployeeInterests(int $user_id, array $formData): array
{
    if ($user_id <= 0) {
        return ['success' => false, 'message' => 'Utilisateur invalide.'];
    }


    $selectedInterestIds = $formData['interests'] ?? [];


    $selectedInterestIds = array_map('intval', $selectedInterestIds);
    $selectedInterestIds = array_filter($selectedInterestIds, function ($id) {
        return $id > 0;
    });

    $pdo = getDbConnection();

    $pdo->beginTransaction();
    deleteRow('personne_interets', 'personne_id = :user_id', [':user_id' => $user_id], $pdo);


    if (!empty($selectedInterestIds)) {
        $insertSql = "INSERT INTO personne_interets (personne_id, interet_id) VALUES (:user_id, :interet_id)";
        $stmt = $pdo->prepare($insertSql);

        foreach ($selectedInterestIds as $interet_id) {



            $stmt->execute([':user_id' => $user_id, ':interet_id' => $interet_id]);
        }
    }

    $pdo->commit();
    return ['success' => true, 'message' => 'Vos intérêts ont été mis à jour.'];
}

/**
 * Fonction principale pour gérer les actions POST et préparer les données de la vue.
 *
 * @return array Données pour la vue.
 */
function setupEmployeeSettingsPage(): array
{
    requireRole(ROLE_SALARIE);
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id <= 0) {
        flashMessage("Session invalide.", "danger");
        redirectTo(WEBCLIENT_URL . '/auth/login.php');
        exit;
    }


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $csrf_token = $_POST['csrf_token'] ?? '';


            $formData = getFormData();
            $result = ['success' => false, 'message' => 'Action inconnue.'];

            switch ($action) {
                case 'update_profile':
                    $result = handleUpdateEmployeeProfile($user_id, $formData);
                    break;
                case 'update_password':
                    $result = updateEmployeePassword($user_id, $formData['current_password'] ?? '', $formData['new_password'] ?? '');
                    break;

                case 'update_preferences':
                    $result = handleUpdateEmployeePreferences($user_id, $formData);
                    break;
                case 'update_interests':
                    $result = handleUpdateEmployeeInterests($user_id, $formData);
                    break;
            }
            flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
        

        redirectTo(WEBCLIENT_URL . '/modules/employees/settings.php');
        exit;
    }


    $employeeDetails = getEmployeeDetailsForSettings($user_id);


    $allInterests = fetchAll('interets_utilisateurs', '', 'nom ASC');
    $userInterestsRaw = fetchAll('personne_interets', 'personne_id = :user_id', '', null, null, [':user_id' => $user_id]);

    $userInterestIds = array_column($userInterestsRaw, 'interet_id');



    if (!$employeeDetails) {

        flashMessage("Erreur lors de la récupération de vos informations.", "danger");



        if (isset($_COOKIE['remember_me_token'])) {

            deleteRow(TABLE_REMEMBER_ME, 'token = ?', [$_COOKIE['remember_me_token']]);
            setcookie('remember_me_token', '', time() - 3600, "/", "", false, true);
        }
        session_unset();
        session_destroy();

        redirectTo(WEBCLIENT_URL . '/auth/login.php');
        exit;
    }


    $csrfToken = $_SESSION['csrf_token'] ?? '';

    return [
        'pageTitle' => "Mes Paramètres",
        'employee' => $employeeDetails,
        'allInterests' => $allInterests,
        'userInterestIds' => $userInterestIds,
        'csrf_token_profile' => $csrfToken,
        'csrf_token_password' => $csrfToken,
        'csrf_token_photo' => $csrfToken,
        'csrf_token_preferences' => $csrfToken,
        'csrf_token_interests' => $csrfToken,
        'flash_new_photo_url' => $_SESSION['flash_new_photo_url'] ?? null
    ];
}


if (isset($_SESSION['flash_new_photo_url'])) {
    unset($_SESSION['flash_new_photo_url']);
}
