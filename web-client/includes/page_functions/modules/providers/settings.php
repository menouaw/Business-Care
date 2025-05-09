<?php

require_once __DIR__ . '/../../../init.php';



/**
 * Récupère les détails d'un prestataire pour l'affichage dans les paramètres.
 *
 * @param int $provider_id L'ID du prestataire.
 * @return array|false Les détails ou false si non trouvé.
 */
function getProviderDetailsForSettings(int $provider_id): array|false
{
    if ($provider_id <= 0) {
        return false;
    }

    return fetchOne(TABLE_USERS, 'id = :id AND role_id = :role_id', [":id" => $provider_id, ":role_id" => ROLE_PRESTATAIRE], 'id, nom, prenom, email, telephone, date_naissance, genre, photo_url');
}

/**
 * Récupère les préférences d'un prestataire.
 *
 * @param int $provider_id L'ID du prestataire.
 * @return array Les préférences (peut être vide si aucune n'est définie).
 */
function getProviderPreferences(int $provider_id): array
{
    if ($provider_id <= 0) {
        return [];
    }

    $prefs = fetchOne(TABLE_USER_PREFERENCES, 'personne_id = :id', [':id' => $provider_id]);

    return [
        'langue' => $prefs['langue'] ?? 'fr',
        'notif_email' => $prefs['notif_email'] ?? true,
    ];
}

/**
 * Met à jour le profil du prestataire (informations de base).
 *
 * @param int $provider_id L'ID du prestataire.
 * @param array $formData Données du formulaire POST.
 * @return array Résultat [success => bool, message => string].
 */
function handleUpdateProviderProfile(int $provider_id, array $formData): array
{
    if ($provider_id <= 0) return ['success' => false, 'message' => 'Utilisateur invalide.'];

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

    $updated = updateRow(TABLE_USERS, $dataToUpdate, 'id = :id AND role_id = :role_id', [':id' => $provider_id, ':role_id' => ROLE_PRESTATAIRE]);
    if ($updated > 0) {
        $_SESSION['user_name'] = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');
        return ['success' => true, 'message' => 'Profil mis à jour avec succès.'];
    } else {
        return ['success' => true, 'message' => 'Aucune modification détectée ou erreur lors de la mise à jour.'];
    }
}

/**
 * Met à jour les préférences du prestataire.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param array $formData Données du formulaire POST.
 * @return array Résultat [success => bool, message => string].
 */
function handleUpdateProviderPreferences(int $provider_id, array $formData): array
{
    if ($provider_id <= 0) return ['success' => false, 'message' => 'Utilisateur invalide.'];

    $langue = $formData['langue'] ?? 'fr';
    $notif_email = isset($formData['notif_email']) ? 1 : 0;

    if (!in_array($langue, ['fr', 'en'])) $langue = 'fr';

    $dataToUpdate = [
        'langue' => $langue,
        'notif_email' => $notif_email,
    ];

    $existingPrefs = fetchOne(TABLE_USER_PREFERENCES, 'personne_id = :id', [':id' => $provider_id]);

    if ($existingPrefs) {
        updateRow(TABLE_USER_PREFERENCES, $dataToUpdate, 'personne_id = :id', [':id' => $provider_id]);
    } else {
        $dataToUpdate['personne_id'] = $provider_id;
        insertRow(TABLE_USER_PREFERENCES, $dataToUpdate);
    }
    $_SESSION['user_language'] = $langue;
    return ['success' => true, 'message' => 'Préférences mises à jour.'];
}

/**
 * Met à jour le mot de passe du prestataire.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param string $current_password Mot de passe actuel.
 * @param string $new_password Nouveau mot de passe.
 * @return array Résultat [success => bool, message => string].
 */
function updateProviderPassword(int $provider_id, string $current_password, string $new_password): array
{
    if (empty($current_password) || empty($new_password)) {
        return ['success' => false, 'message' => 'Les mots de passe ne peuvent pas être vides.'];
    }
    if ($current_password === $new_password) {
        return ["success" => false, "message" => "Le nouveau mot de passe doit être différent de l'ancien."];
    }
    if (strlen($new_password) < MIN_PASSWORD_LENGTH) {
        return ['success' => false, 'message' => 'Le nouveau mot de passe est trop court (minimum ' . MIN_PASSWORD_LENGTH . ' caractères).'];
    }

    $user = fetchOne(TABLE_USERS, 'id = :id AND role_id = :role_id', [":id" => $provider_id, ':role_id' => ROLE_PRESTATAIRE], 'id, mot_de_passe');
    if (!$user) {
        return ['success' => false, 'message' => 'Utilisateur prestataire non trouvé.'];
    }

    $storedHash = $user['mot_de_passe'] ?? '';
    if (!password_verify($current_password, $storedHash)) {
        return ['success' => false, 'message' => 'Le mot de passe actuel est incorrect.'];
    }

    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    if ($new_password_hash === false) {
        error_log("Erreur de hachage du mot de passe pour l'utilisateur ID: {$provider_id}");
        return ['success' => false, 'message' => 'Une erreur technique est survenue lors de la sécurisation du mot de passe.'];
    }

    $updated = updateRow(TABLE_USERS, ['mot_de_passe' => $new_password_hash], 'id = :id', [":id" => $provider_id]);
    if ($updated > 0) {
        return ['success' => true, 'message' => 'Mot de passe mis à jour avec succès.'];
    } else {
        error_log("Échec de la mise à jour du mot de passe en BDD pour l'utilisateur ID: {$provider_id}");
        return ['success' => false, 'message' => 'Une erreur technique est survenue lors de la mise à jour du mot de passe.'];
    }
}

/**
 * Met à jour la photo de profil du prestataire.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param array $fileData Données du fichier $_FILES.
 * @return array Résultat [success => bool, message => string, new_photo_url => ?string].
 */
function updateProviderProfilePhoto(int $provider_id, array $fileData): array
{
    if ($provider_id <= 0) return ['success' => false, 'message' => 'Utilisateur invalide.', 'new_photo_url' => null];

    if (empty($fileData) || $fileData['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => "Le fichier dépasse la taille maximale autorisée par le serveur.",
            UPLOAD_ERR_FORM_SIZE  => "Le fichier dépasse la taille maximale autorisée par le formulaire.",
            UPLOAD_ERR_PARTIAL    => "Le fichier n'a été que partiellement téléchargé.",
            UPLOAD_ERR_NO_FILE    => "Aucun fichier n'a été téléchargé.",
            UPLOAD_ERR_NO_TMP_DIR => "Erreur serveur : dossier temporaire manquant.",
            UPLOAD_ERR_CANT_WRITE => "Erreur serveur : impossible d'écrire sur le disque.",
            UPLOAD_ERR_EXTENSION  => "Erreur serveur : une extension PHP a arrêté l'envoi.",
        ];
        $message = $uploadErrors[$fileData['error']] ?? "Aucun fichier reçu ou erreur inconnue lors du téléchargement.";
        return ['success' => false, 'message' => $message, 'new_photo_url' => null];
    }


    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($fileData['tmp_name']);
    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

    if (!in_array($mime_type, $allowed_mime_types)) {
        return ['success' => false, 'message' => "Type de fichier invalide. Seuls JPG, PNG, GIF sont autorisés.", 'new_photo_url' => null];
    }

    if ($fileData['size'] > (2 * 1024 * 1024)) {
        return ['success' => false, 'message' => 'Fichier trop volumineux (maximum 2 Mo).', 'new_photo_url' => null];
    }


    $uploadDir = realpath(__DIR__ . '/../../../../../uploads/photos');
    if (!$uploadDir || !is_dir($uploadDir)) {
        error_log("Le dossier d'upload n'existe pas ou n'est pas un dossier : {$uploadDir}");
        return ['success' => false, 'message' => "Erreur de configuration serveur pour l'upload.", 'new_photo_url' => null];
    }


    $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
    $newFileName = 'provider_' . $provider_id . '_' . time() . '.' . $fileExtension;
    $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;
    $relativePath = UPLOAD_URL . 'photos/' . $newFileName;


    if (move_uploaded_file($fileData['tmp_name'], $destinationPath)) {

        $currentUser = fetchOne(TABLE_USERS, 'id = :id', [':id' => $provider_id], 'photo_url');
        if ($currentUser && !empty($currentUser['photo_url']) && strpos($currentUser['photo_url'], 'default-user.png') === false) {
            $oldRelativePath = $currentUser['photo_url'];

            $oldFileName = basename(parse_url($oldRelativePath, PHP_URL_PATH));
            $oldFullPath = $uploadDir . DIRECTORY_SEPARATOR . $oldFileName;
            if (file_exists($oldFullPath) && is_file($oldFullPath)) {
                @unlink($oldFullPath);
            }
        }


        $updated = updateRow(TABLE_USERS, ['photo_url' => $relativePath], 'id = :id', [":id" => $provider_id]);
        if ($updated > 0) {
            $_SESSION['user_photo'] = $relativePath;
            return ['success' => true, 'message' => 'Photo de profil mise à jour avec succès.', 'new_photo_url' => $relativePath];
        } else {
            @unlink($destinationPath);
            error_log("Échec de la mise à jour de photo_url en BDD pour l'utilisateur ID: {$provider_id}");
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour de la base de données.', 'new_photo_url' => null];
        }
    } else {
        error_log("Échec de move_uploaded_file pour l'utilisateur ID: {$provider_id}. Source: {$fileData['tmp_name']}, Destination: {$destinationPath}");
        return ['success' => false, 'message' => 'Impossible de sauvegarder le fichier téléchargé.', 'new_photo_url' => null];
    }
}


/**
 * Fonction principale pour gérer les actions POST et préparer les données pour la vue des paramètres prestataire.
 *
 * @return array Données pour la vue.
 */
function setupProviderSettingsPage(): array
{
    requireRole(ROLE_PRESTATAIRE);
    $provider_id = $_SESSION['user_id'] ?? 0;
    if ($provider_id <= 0) {
        flashMessage("Session invalide ou expirée.", "danger");
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
            $result = ['success' => false, 'message' => 'Action inconnue ou non spécifiée.'];

            switch ($action) {
                case 'update_profile':
                    $result = handleUpdateProviderProfile($provider_id, $formData);
                    break;
                case 'update_password':
                    $result = updateProviderPassword($provider_id, $formData['current_password'] ?? '', $formData['new_password'] ?? '');
                    break;
                case 'update_photo':
                    $result = updateProviderProfilePhoto($provider_id, $_FILES['profile_photo'] ?? []);

                    if ($result['success'] && isset($result['new_photo_url'])) {
                        $_SESSION['flash_new_photo_url'] = $result['new_photo_url'];
                    }
                    break;
                case 'update_preferences':
                    $result = handleUpdateProviderPreferences($provider_id, $formData);
                    break;
            }
            flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
        }


        redirectTo(WEBCLIENT_URL . '/modules/providers/settings.php');
        exit;
    }


    $providerDetails = getProviderDetailsForSettings($provider_id);
    $providerPreferences = getProviderPreferences($provider_id);

    if (!$providerDetails) {

        flashMessage("Erreur lors de la récupération de vos informations.", "danger");

        session_unset();
        session_destroy();
        redirectTo(WEBCLIENT_URL . '/auth/login.php');
        exit;
    }


    $csrfToken = generateToken();


    $flash_new_photo_url = $_SESSION['flash_new_photo_url'] ?? null;
    if (isset($_SESSION['flash_new_photo_url'])) {
        unset($_SESSION['flash_new_photo_url']);
    }

    return [
        'pageTitle' => "Mes Paramètres Prestataire",
        'provider' => $providerDetails,
        'preferences' => $providerPreferences,

        'csrf_token_profile' => $csrfToken,
        'csrf_token_password' => $csrfToken,
        'csrf_token_photo' => $csrfToken,
        'csrf_token_preferences' => $csrfToken,
        'flash_new_photo_url' => $flash_new_photo_url
    ];
}
