<?php
require_once '../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ADMIN);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$csrf_token = $_GET['csrf_token'] ?? '';

if (!validateToken($csrf_token)) {
    handleCsrfFailureRedirect($id, 'companies', 'suppression entreprise');
}

if ($id <= 0) {
    flashMessage("Identifiant d'entreprise invalide.", "danger");
    redirectTo(WEBADMIN_URL . '/modules/companies/');
}

$company = companiesGetDetails($id);
if (!$company) {
    flashMessage('Entreprise non trouvée.', 'warning');
    redirectTo(WEBADMIN_URL . '/modules/companies/index.php');
}

$result = companiesDelete($id);

if ($result['success']) {
    flashMessage($result['message'], 'success');
    redirectTo(WEBADMIN_URL . '/modules/companies/index.php');
} else {
    flashMessage($result['message'], 'danger');
    redirectBasedOnReferer($id, 'companies'); 
}
