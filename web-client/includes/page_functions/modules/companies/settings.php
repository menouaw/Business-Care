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
