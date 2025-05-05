<?php
require_once '../../includes/page_functions/modules/services.php';

// requireRole(ROLE_ADMIN)

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$csrf_token = $_GET['csrf_token'] ?? '';

if (!validateToken($csrf_token)) {
    handleCsrfFailureRedirect($id, 'services', 'suppression service');
}

if ($id <= 0) {
    flashMessage("Identifiant de service invalide.", "danger");
    redirectTo(WEBADMIN_URL . '/modules/services/index.php');
}

$result = servicesDelete($id);

if ($result['success']) {
    flashMessage($result['message'] ?? 'Service supprimé avec succès.', 'success');
    redirectTo(WEBADMIN_URL . '/modules/services/index.php');
} else {
    $errorMessage = $result['message'] ?? 'Impossible de supprimer le service.';
    logSystemActivity('service_delete_failure', "[ERROR] Échec suppression service ID: {$id} - Raison: {$errorMessage}");
    flashMessage($errorMessage, 'danger');
    redirectBasedOnReferer($id, 'services'); 
}
