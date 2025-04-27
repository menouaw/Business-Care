<?php

require_once __DIR__ . '/../../../init.php';

/**
 * Récupère les détails d'une entreprise pour l'affichage dans les paramètres.
 *
 * @param int $entreprise_id L'ID de l'entreprise.
 * @return array|false Les détails de l'entreprise ou false si non trouvée.
 */
function getCompanyDetailsForSettings(int $entreprise_id): array|false
{
    if ($entreprise_id <= 0) {
        return false;
    }

    return fetchOne(TABLE_COMPANIES, 'id = :id', [":id" => $entreprise_id], 'id, nom, siret, adresse, code_postal, ville, telephone, email');
}

/**
 * Met à jour le mot de passe du représentant de l'entreprise.
 *
 * @param int $user_id L'ID de l'utilisateur (représentant).
 * @param string $current_password Le mot de passe actuel fourni.
 * @param string $new_password Le nouveau mot de passe souhaité.
 * @return array ['success' => bool, 'message' => string]
 */
function updateCompanyRepresentativePassword(int $user_id, string $current_password, string $new_password): array
{
    if (empty($current_password) || empty($new_password)) {
        return ['success' => false, 'message' => 'Les mots de passe ne peuvent pas être vides.'];
    }

    if ($current_password === $new_password) {
        return ["success" => false, "message" => "Le nouveau mot de passe doit être différent de l'ancien mot de passe."];
    }

    if (strlen($new_password) < (defined('MIN_PASSWORD_LENGTH') ? MIN_PASSWORD_LENGTH : 8)) {
        return ['success' => false, 'message' => 'Le nouveau mot de passe est trop court. Minimum ' . (defined('MIN_PASSWORD_LENGTH') ? MIN_PASSWORD_LENGTH : 8) . ' caractères.'];
    }


    $user = fetchOne(TABLE_USERS, 'id = :id', [":id" => $user_id], 'id, mot_de_passe');
    if (!$user) {
        return ['success' => false, 'message' => 'Utilisateur non trouvé.'];
    }


    if (!password_verify($current_password, $user['mot_de_passe'])) {
        logSecurityEvent($user_id, 'password_change', '[FAILURE] Tentative de changement de mot de passe échouée (mot de passe actuel incorrect)', true);
        return ['success' => false, 'message' => 'Le mot de passe actuel est incorrect.'];
    }


    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    if ($new_password_hash === false) {
        error_log("[ERROR] Echec du hachage de mot de passe pour user ID: {$user_id}");
        return ['success' => false, 'message' => 'Erreur technique lors de la sécurisation du nouveau mot de passe.'];
    }


    $updated = updateRow(TABLE_USERS, ['mot_de_passe' => $new_password_hash], 'id = :id', [":id" => $user_id]);

    if ($updated > 0) {
        logSecurityEvent($user_id, 'password_change', '[SUCCESS] Mot de passe changé avec succès.');
        return ['success' => true, 'message' => 'Votre mot de passe a été mis à jour avec succès.'];
    } else {
        error_log("[ERROR] Echec de la mise à jour du mot de passe en BDD pour user ID: {$user_id}");
        return ['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour du mot de passe. Veuillez réessayer.'];
    }
}

/**
 * Met à jour la photo de profil de l'utilisateur.
 *
 * @param int $user_id L'ID de l'utilisateur.
 * @param array $fileData Les données du fichier uploadé depuis $_FILES.
 * @return array ['success' => bool, 'message' => string, 'new_photo_url' => string|null]
 */
function updateUserProfilePhoto(int $user_id, array $fileData): array
{
    if (empty($fileData) || $fileData['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => "Aucun fichier n'a été envoyé ou une erreur est survenue lors de l'upload.", 'new_photo_url' => null];
    }

    
    $imageInfo = getimagesize($fileData['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'message' => "Le fichier envoyé n'est pas une image valide.", 'new_photo_url' => null];
    }

    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $detectedMimeType = $imageInfo['mime'] ?? ''; 

    if (!in_array($detectedMimeType, $allowedMimeTypes)) {
        return ['success' => false, 'message' => "Type d'image non autorisé. Veuillez choisir une image JPG, PNG ou GIF.", 'new_photo_url' => null];
    }

    $maxSize = 2 * 1024 * 1024; 
    if ($fileData['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Le fichier est trop volumineux. La taille maximale est de 2 Mo.', 'new_photo_url' => null];
    }

    
    
    $uploadDir = realpath(__DIR__ . '/../../../../uploads/photos');
    if (!$uploadDir || !is_dir($uploadDir)) {
        error_log("[ERROR] Le dossier d'upload pour les photos n'existe pas ou n'est pas un dossier: {$uploadDir}");
        return ['success' => false, 'message' => "Erreur de configuration serveur pour l'upload.", 'new_photo_url' => null];
    }

    
    $fileExtension = match ($detectedMimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        default => pathinfo($fileData['name'], PATHINFO_EXTENSION) 
    };
    $newFileName = 'user_' . $user_id . '_' . time() . '.' . strtolower($fileExtension);
    $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

    
    $relativePath = (defined('UPLOAD_URL') ? rtrim(parse_url(UPLOAD_URL, PHP_URL_PATH), '/') : '/uploads') . '/photos/' . $newFileName;

    
    if (move_uploaded_file($fileData['tmp_name'], $destinationPath)) {
        
        $updated = updateRow(TABLE_USERS, ['photo_url' => $relativePath], 'id = :id', [':id' => $user_id]);
        if ($updated > 0) {
            logSecurityEvent($user_id, 'profile_photo_update', '[SUCCESS] Photo de profil mise à jour.');
            
            return ['success' => true, 'message' => 'Photo de profil mise à jour avec succès.', 'new_photo_url' => $relativePath];
        } else {
            error_log("[ERROR] Échec de la mise à jour de photo_url en BDD pour user ID: {$user_id}");
            
            if (file_exists($destinationPath)) unlink($destinationPath);
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour de la base de données.', 'new_photo_url' => null];
        }
    } else {
        error_log("[ERROR] Échec du déplacement du fichier uploadé vers {$destinationPath}");
        return ['success' => false, 'message' => 'Impossible de sauvegarder le fichier uploadé.', 'new_photo_url' => null];
    }
}
