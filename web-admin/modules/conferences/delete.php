<?php
require_once '../../includes/page_functions/modules/conferences.php';

requireRole(ROLE_ADMIN);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$csrf_token = $_GET['csrf_token'] ?? '';

if ($id <= 0) {
    flashMessage('Identifiant de conférence invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/conferences/index.php');
}

if (!validateToken($csrf_token)) {
     handleCsrfFailureRedirect($id, 'conferences', 'suppression conférence', WEBADMIN_URL . '/modules/conferences/index.php');
}

$result = conferencesDelete($id);

if ($result['success']) {
    flashMessage($result['message'] ?? 'Conférence supprimée avec succès.', 'success');
} else {
    flashMessage($result['message'] ?? 'Erreur lors de la suppression de la conférence.', 'danger');
}

redirectTo(WEBADMIN_URL . '/modules/conferences/index.php');
?>
