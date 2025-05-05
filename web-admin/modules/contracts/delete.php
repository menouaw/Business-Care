<?php
require_once '../../includes/page_functions/modules/contracts.php';

// requireRole(ROLE_ADMIN)

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$csrf_token = $_GET['csrf_token'] ?? '';

if (!validateToken($csrf_token)) {
    handleCsrfFailureRedirect($id, 'contracts', 'suppression contrat');
}

if ($id <= 0) {
    flashMessage("Identifiant de contrat invalide.", "danger");
    redirectTo(WEBADMIN_URL . '/modules/contracts/index.php');
}

$contract = contractsGetDetails($id);
if (!$contract) {
    flashMessage('Contrat non trouvé.', 'warning');
    redirectTo(WEBADMIN_URL . '/modules/contracts/index.php');
}


$result = contractsDelete($id);

if ($result['success']) {
    flashMessage($result['message'] ?? 'Contrat supprimé avec succès.', 'success');
    redirectTo(WEBADMIN_URL . '/modules/contracts/index.php');
} else {
    $errorMessage = $result['message'] ?? 'Impossible de supprimer le contrat.';
    logSystemActivity('contract_delete_failure', "[ERROR] Échec suppression contrat ID: {$id} - Raison: {$errorMessage}");
    flashMessage($errorMessage, 'danger');
    redirectBasedOnReferer($id, 'contracts'); 
}
