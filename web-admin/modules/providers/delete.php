<?php
require_once '../../includes/page_functions/modules/providers.php';

// requireRole(ROLE_ADMIN)


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $token = $_GET['csrf_token'] ?? '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $token = $_POST['csrf_token'] ?? '';
} else {
    flashMessage('Méthode non autorisée.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/providers/index.php');
}


if (!validateToken($token)) {
    flashMessage('Erreur de sécurité ou jeton expiré. Veuillez réessayer.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/providers/index.php');
}


if ($id <= 0) {
    flashMessage('Identifiant de prestataire invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/providers/index.php');
}


$result = deleteProvider($id); 
flashMessage($result['message'], $result['success'] ? 'success' : 'danger');


redirectBasedOnReferer($id, 'providers', true);
?>
