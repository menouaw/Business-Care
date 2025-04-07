<?php
require_once '../../includes/page_functions/modules/appointments.php';

requireRole(ROLE_ADMIN);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$csrf_token = $_GET['csrf_token'] ?? '';

if (!validateToken($csrf_token)) {
    handleCsrfFailureRedirect($id, 'appointments', 'suppression rendez-vous');
}

if ($id <= 0) {
    flashMessage("Identifiant de rendez-vous invalide.", "danger");
    redirectTo(WEBADMIN_URL . '/modules/appointments/index.php');
}

$appointment = appointmentsGetDetails($id);
if (!$appointment) {
    flashMessage('Rendez-vous non trouvé.', 'warning');
    redirectTo(WEBADMIN_URL . '/modules/appointments/index.php');
}

$result = appointmentsDelete($id);

if ($result['success']) {
    flashMessage($result['message'] ?? 'Rendez-vous supprimé avec succès.', 'success');
    redirectTo(WEBADMIN_URL . '/modules/appointments/index.php');
} else {
    $errorMessage = $result['message'] ?? 'Impossible de supprimer le rendez-vous.';
    logSystemActivity('appointment_delete_failure', "[ERROR] Échec suppression RDV ID: {$id} - Raison: {$errorMessage}");
    flashMessage($errorMessage, 'danger');
    redirectTo(WEBADMIN_URL . '/modules/appointments/index.php'); 
}
