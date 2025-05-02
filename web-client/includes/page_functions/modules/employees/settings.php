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
 * Récupère les préférences d'un employé.
 *
 * @param int $user_id L'ID de l'employé.
 * @return array Les préférences (peut être vide si aucune n'est définie).
 */
function getEmployeePreferences(int $user_id): array
{
    if ($user_id <= 0) {
        return [];
    }

    $prefs = fetchOne(TABLE_USER_PREFERENCES, 'personne_id = :id', [':id' => $user_id]);

    return [
        'langue' => $prefs['langue'] ?? 'fr',
        'notif_email' => $prefs['notif_email'] ?? true,
        'theme' => $prefs['theme'] ?? 'clair'
    ];
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

    try {

        $updated = updateRow(TABLE_USERS, $dataToUpdate, 'id = :id', [':id' => $user_id]);
        if ($updated > 0) {

            $_SESSION['user_name'] = $prenom . ' ' . $nom;
            return ['success' => true, 'message' => 'Profil mis à jour avec succès.'];
        } else {

            return ['success' => true, 'message' => 'Aucune modification détectée.'];
        }
    } catch (Exception $e) {
        error_log("Erreur MAJ profil employé ID {$user_id}: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur technique lors de la mise à jour.'];
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

    $langue = $formData['langue'] ?? 'fr';
    $notif_email = isset($formData['notif_email']) ? 1 : 0;
    $theme = $formData['theme'] ?? 'clair';


    if (!in_array($langue, ['fr', 'en'])) $langue = 'fr';
    if (!in_array($theme, ['clair', 'sombre'])) $theme = 'clair';

    $dataToUpdate = [
        'langue' => $langue,
        'notif_email' => $notif_email,
        'theme' => $theme
    ];

    try {

        $existingPrefs = fetchOne(TABLE_USER_PREFERENCES, 'personne_id = :id', [':id' => $user_id]);

        if ($existingPrefs) {

            $updated = updateRow(TABLE_USER_PREFERENCES, $dataToUpdate, 'personne_id = :id', [':id' => $user_id]);
        } else {

            $dataToUpdate['personne_id'] = $user_id;
            $updated = insertRow(TABLE_USER_PREFERENCES, $dataToUpdate);
        }


        $_SESSION['user_language'] = $langue;

        return ['success' => true, 'message' => 'Préférences mises à jour.'];
    } catch (Exception $e) {
        error_log("Erreur MAJ préférences employé ID {$user_id}: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur technique lors de la mise à jour des préférences.'];
    }
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
        logSecurityEvent($user_id, 'password_change', '[FAILURE] Tentative MAJ MDP (actuel incorrect)', true);
        return ['success' => false, 'message' => 'Le mot de passe actuel est incorrect.'];
    }

    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    if ($new_password_hash === false) {
        error_log("Echec hash MDP user ID {$user_id}");
        return ['success' => false, 'message' => 'Erreur technique lors de la sécurisation.'];
    }

    $updated = updateRow(TABLE_USERS, ['mot_de_passe' => $new_password_hash], 'id = :id', [":id" => $user_id]);
    if ($updated > 0) {
        logSecurityEvent($user_id, 'password_change', '[SUCCESS] MAJ MDP réussie.');
        return ['success' => true, 'message' => 'Mot de passe mis à jour.'];
    } else {
        error_log("Echec MAJ BDD MDP user ID {$user_id}");
        return ['success' => false, 'message' => 'Erreur technique lors de la mise à jour.'];
    }
}

/**
 * Met à jour la photo de profil de l'employé.
 *
 * @param int $user_id L'ID de l'employé.
 * @param array $fileData Données du fichier $_FILES.
 * @return array Résultat [success => bool, message => string, new_photo_url => ?string].
 */
function updateEmployeeProfilePhoto(int $user_id, array $fileData): array
{

    if (empty($fileData) || $fileData['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => "Aucun fichier reçu ou erreur upload.", 'new_photo_url' => null];
    }

    $imageInfo = getimagesize($fileData['tmp_name']);
    if ($imageInfo === false || !in_array($imageInfo['mime'] ?? '', ['image/jpeg', 'image/png', 'image/gif'])) {
        return ['success' => false, 'message' => "Fichier invalide (JPG, PNG, GIF uniquement).", 'new_photo_url' => null];
    }
    if ($fileData['size'] > (2 * 1024 * 1024)) {
        return ['success' => false, 'message' => 'Fichier trop volumineux (max 2 Mo).', 'new_photo_url' => null];
    }

    $uploadDir = realpath(__DIR__ . '/../../../../../uploads/photos');
    if (!$uploadDir || !is_dir($uploadDir)) {
        error_log("Dossier upload photos absent ou invalide: " . (__DIR__ . '/../../../../../uploads/photos'));
        return ['success' => false, 'message' => "Erreur config serveur upload.", 'new_photo_url' => null];
    }

    $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
    $newFileName = 'user_' . $user_id . '_' . time() . '.' . $fileExtension;
    $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;
    $relativePath = UPLOAD_URL . 'photos/' . $newFileName;

    $tmpFilePath = $fileData['tmp_name'] ?? '[tmp_name not set]';

    if (move_uploaded_file($tmpFilePath, $destinationPath)) {


        $currentUser = fetchOne(TABLE_USERS, 'id = :id', [':id' => $user_id], 'photo_url');
        if ($currentUser && !empty($currentUser['photo_url'])) {
            $oldRelativePath = $currentUser['photo_url'];
            $oldFileName = basename($oldRelativePath);
            $oldFullPath = $uploadDir . DIRECTORY_SEPARATOR . $oldFileName;
            if (file_exists($oldFullPath) && is_file($oldFullPath)) {
                @unlink($oldFullPath);
            }
        }

        $updated = updateRow(TABLE_USERS, ['photo_url' => $relativePath], 'id = :id', [':id' => $user_id]);
        if ($updated > 0) {
            $_SESSION['user_photo'] = $relativePath;
            logSecurityEvent($user_id, 'profile_photo_update', '[SUCCESS] Photo profil mise à jour.');
            return ['success' => true, 'message' => 'Photo de profil mise à jour.', 'new_photo_url' => $relativePath];
        } else {
            @unlink($destinationPath);
            error_log("Echec MAJ BDD photo user ID {$user_id}");
            return ['success' => false, 'message' => 'Erreur MAJ base de données.', 'new_photo_url' => null];
        }
    } else {
        error_log("Echec déplacement fichier uploadé vers {$destinationPath}");
        return ['success' => false, 'message' => 'Impossible de sauvegarder le fichier.', 'new_photo_url' => null];
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

    try {
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
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur MAJ intérêts employé ID {$user_id}: " . $e->getMessage());
        return ['success' => false, 'message' => 'Une erreur technique est survenue lors de la mise à jour de vos intérêts.'];
    }
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

        if (!validateToken($csrf_token)) {
            flashMessage("Jeton de sécurité invalide ou expiré. Veuillez réessayer.", "danger");
        } else {
            $formData = getFormData();
            $result = ['success' => false, 'message' => 'Action inconnue.'];

            switch ($action) {
                case 'update_profile':
                    $result = handleUpdateEmployeeProfile($user_id, $formData);
                    break;
                case 'update_password':
                    $result = updateEmployeePassword($user_id, $formData['current_password'] ?? '', $formData['new_password'] ?? '');
                    break;
                case 'update_photo':
                    $result = updateEmployeeProfilePhoto($user_id, $_FILES['profile_photo'] ?? []);

                    if ($result['success'] && isset($result['new_photo_url'])) {
                        $_SESSION['flash_new_photo_url'] = $result['new_photo_url'];
                    }
                    break;
                case 'update_preferences':
                    $result = handleUpdateEmployeePreferences($user_id, $formData);
                    break;
                case 'update_interests':
                    $result = handleUpdateEmployeeInterests($user_id, $formData);
                    break;
            }
            flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
        }

        redirectTo(WEBCLIENT_URL . '/modules/employees/settings.php');
        exit;
    }


    $employeeDetails = getEmployeeDetailsForSettings($user_id);
    $employeePreferences = getEmployeePreferences($user_id);


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


    return [
        'pageTitle' => "Mes Paramètres",
        'employee' => $employeeDetails,
        'preferences' => $employeePreferences,
        'allInterests' => $allInterests,
        'userInterestIds' => $userInterestIds,
        'csrf_token_profile' => generateToken(),
        'csrf_token_password' => generateToken(),
        'csrf_token_photo' => generateToken(),
        'csrf_token_preferences' => generateToken(),
        'csrf_token_interests' => generateToken(),
        'flash_new_photo_url' => $_SESSION['flash_new_photo_url'] ?? null
    ];
}


if (isset($_SESSION['flash_new_photo_url'])) {
    unset($_SESSION['flash_new_photo_url']);
}
