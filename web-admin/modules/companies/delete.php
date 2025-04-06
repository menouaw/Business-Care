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
    handleCsrfFailureRedirect($companyId, 'companies', 'suppression entreprise');
}

if ($companyId <= 0) {
    flashMessage('Identifiant entreprise invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/companies/index.php');
}

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
    redirectBasedOnReferer($companyId, 'companies'); 
}
