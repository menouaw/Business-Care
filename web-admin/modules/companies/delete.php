<?php
require_once '../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ADMIN);

$requestMethod = $_SERVER['REQUEST_METHOD'];
$params = [];

if ($requestMethod === 'GET') {
    $params = $_GET;
} elseif ($requestMethod === 'POST') {
    $params = $_POST;
} else {
    flashMessage('Méthode non autorisée.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/companies/index.php');
}

if (!isset($params['id']) || !isset($params['csrf_token'])) {
    flashMessage('Données manquantes pour la suppression.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/companies/index.php');
}

$companyId = (int)$params['id'];
$token = $params['csrf_token'];

if (!validateToken($token)) {
    flashMessage('Jeton de sécurité invalide ou expiré. Veuillez réessayer.', 'danger');
    logSecurityEvent($_SESSION['user_id'] ?? 0, 'csrf_failure', "[SECURITY FAILURE] Tentative de suppression entreprise ID: $companyId avec jeton invalide via $requestMethod");
    if ($companyId > 0) {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, 'view.php?id=' . $companyId) !== false) {
             redirectTo(WEBADMIN_URL . "/modules/companies/view.php?id={$companyId}");
        } else {
             redirectTo(WEBADMIN_URL . '/modules/companies/index.php');
        }
    } else {
        redirectTo(WEBADMIN_URL . '/modules/companies/index.php');
    }
}

if ($companyId <= 0) {
    flashMessage('Identifiant entreprise invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/companies/index.php');
}

// Verifie si l'entreprise existe avant de tenter de supprimer
$company = companiesGetDetails($companyId);
if (!$company) {
    flashMessage('Entreprise non trouvée.', 'warning');
    redirectTo(WEBADMIN_URL . '/modules/companies/index.php');
}

$result = companiesDelete($companyId);

if ($result['success']) {
    flashMessage($result['message'], 'success');
    redirectTo(WEBADMIN_URL . '/modules/companies/index.php');
} else {
    flashMessage($result['message'], 'danger');
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    // Si l'erreur vient de la page view, redirige vers elle pour voir l'erreur
    if (strpos($referer, 'view.php?id=' . $companyId) !== false) {
         redirectTo(WEBADMIN_URL . "/modules/companies/view.php?id={$companyId}");
    } else {
         // Sinon, redirige vers la liste
         redirectTo(WEBADMIN_URL . '/modules/companies/index.php'); 
    }
}
