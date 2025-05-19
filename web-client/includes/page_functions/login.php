<?php

/**
 * fonctions pour la gestion de l'authentification
 *
 * ce fichier contient les fonctions spécifiques à la page de connexion
 */

require_once __DIR__ . '/../../includes/init.php';

/**
 * traite la soumission du formulaire de connexion
 * 
 * @param array $formData données du formulaire
 * @return array résultat du traitement (success, message)
 */
function processLoginForm($formData = null)
{
    $result = [
        'success' => false,
        'message' => '',
        'redirect' => ''
    ];


    if ($formData === null) {
        $formData = getFormData();
    }


    if (empty($formData['email']) || empty($formData['password'])) {
        $result['message'] = 'Veuillez remplir tous les champs obligatoires';
        return $result;
    }


    $email = $formData['email'];
    $password = $formData['password'];
    $rememberMe = isset($formData['remember_me']) && $formData['remember_me'] === 'on';


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result['message'] = 'Adresse email invalide';
        return $result;
    }


    if (login($email, $password, $rememberMe)) {
        $result['success'] = true;
        $result['message'] = 'Connexion réussie';


        if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
            $result['redirect'] = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
        } else {

            if (isEntrepriseUser()) {
                $result['redirect'] = WEBCLIENT_URL . '/modules/companies/dashboard.php';
            } elseif (isSalarieUser()) {
                $result['redirect'] = WEBCLIENT_URL . '/modules/employees/dashboard.php';
            } elseif (isPrestataireUser()) {
                $result['redirect'] = WEBCLIENT_URL . '/modules/providers/dashboard.php';
            } else {
                $result['redirect'] = WEBCLIENT_URL . '/dashboard.php';
            }
        }
    } else {
        $result['message'] = 'Identifiants incorrects. Veuillez réessayer.';
    }

    return $result;
}

/**
 * traite la soumission du formulaire de récupération de mot de passe
 * 
 * @param array $formData données du formulaire
 * @return array résultat du traitement (success, message)
 */
function processPasswordResetForm($formData = null)
{
    $result = [
        'success' => false,
        'message' => ''
    ];


    if ($formData === null) {
        $formData = getFormData();
    }


    if (empty($formData['email'])) {
        $result['message'] = 'Veuillez saisir votre adresse email';
        return $result;
    }


    $email = $formData['email'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result['message'] = 'Adresse email invalide';
        return $result;
    }


    if (resetPassword($email)) {
        $result['success'] = true;
        $result['message'] = 'Si cette adresse email est associée à un compte.';
        logSystemActivity('password_reset_requested', "Demande de réinitialisation pour: $email");
    } else {

        $result['success'] = true;
        $result['message'] = 'Si cette adresse email est associée à un compte, vous recevrez un email avec les instructions pour réinitialiser votre mot de passe.';
    }

    return $result;
}

/**
 * vérifie si un token de réinitialisation de mot de passe est valide
 * 
 * @param string $token token de réinitialisation
 * @return bool|array false si invalide, données utilisateur si valide
 */
function validateResetToken($token)
{
    if (empty($token)) {
        return false;
    }


    $user = fetchOne('personnes', "token = '$token' AND expires > NOW()");
    return $user ?: false;
}

/**
 * traite la soumission du formulaire de définition du nouveau mot de passe
 * 
 * @param array $formData données du formulaire
 * @return array résultat du traitement (success, message)
 */
function processNewPasswordForm($formData = null)
{
    $result = [
        'success' => false,
        'message' => ''
    ];


    if ($formData === null) {
        $formData = getFormData();
    }


    if (empty($formData['token']) || empty($formData['password']) || empty($formData['confirm_password'])) {
        $result['message'] = 'Tous les champs sont obligatoires';
        return $result;
    }


    if ($formData['password'] !== $formData['confirm_password']) {
        $result['message'] = 'Les mots de passe ne correspondent pas';
        return $result;
    }


    $user = validateResetToken($formData['token']);
    if (!$user) {
        $result['message'] = 'Le lien de réinitialisation est invalide ou a expiré';
        return $result;
    }


    if (strlen($formData['password']) < 8) {
        $result['message'] = 'Le mot de passe doit contenir au moins 8 caractères';
        return $result;
    }

    $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
    $updated = updateRow(
        'personnes',
        [
            'mot_de_passe' => $hashedPassword,
            'token' => null,
            'expires' => null
        ],
        "id = {$user['id']}"
    );

    if ($updated) {
        logSecurityEvent($user['id'], 'password_changed', 'Mot de passe modifié après réinitialisation');
        $result['success'] = true;
        $result['message'] = 'Votre mot de passe a été mis à jour avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.';
    } else {
        $result['message'] = 'Une erreur est survenue lors de la mise à jour du mot de passe';
    }

    return $result;
}
