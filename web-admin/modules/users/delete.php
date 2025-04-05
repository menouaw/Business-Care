<?php
require_once '../../includes/page_functions/modules/users.php';

requireRole(ROLE_ADMIN);

$requestMethod = $_SERVER['REQUEST_METHOD'];
$params = [];

if ($requestMethod === 'GET') {
    $params = $_GET;
} elseif ($requestMethod === 'POST') {
    $params = $_POST;
} else {
    flashMessage('Méthode non autorisée.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/users/index.php');
}

if (!isset($params['id']) || !isset($params['csrf_token'])) {
    flashMessage('Données manquantes pour la suppression.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/users/index.php');
}

$userId = (int)$params['id'];
$token = $params['csrf_token'];

if (!validateToken($token)) {
    flashMessage('Jeton de sécurité invalide ou expiré. Veuillez réessayer.', 'danger');
    logSecurityEvent($_SESSION['user_id'] ?? 0, 'csrf_failure', "[SECURITY FAILURE] Tentative de suppression utilisateur ID: $userId avec jeton invalide via $requestMethod");
    if ($userId > 0) {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, 'view.php?id=' . $userId) !== false) {
             redirectTo(WEBADMIN_URL . "/modules/users/view.php?id={$userId}");
        } else {
             redirectTo(WEBADMIN_URL . '/modules/users/index.php');
        }
    } else {
        redirectTo(WEBADMIN_URL . '/modules/users/index.php');
    }
}

if ($userId <= 0) {
    flashMessage('Identifiant utilisateur invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/users/index.php');
}

$user = usersGetUserById($userId);
if (!$user) {
    flashMessage('Utilisateur non trouvé.', 'warning');
    redirectTo(WEBADMIN_URL . '/modules/users/index.php');
}

$result = usersDelete($userId);

if ($result['success']) {
    flashMessage($result['message'], 'success');
    redirectTo(WEBADMIN_URL . '/modules/users/index.php');
} else {
    flashMessage($result['message'], 'danger');
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, 'view.php?id=' . $userId) !== false) {
         redirectTo(WEBADMIN_URL . "/modules/users/view.php?id={$userId}");
    } else {
         redirectTo(WEBADMIN_URL . '/modules/users/index.php'); 
    }
}
