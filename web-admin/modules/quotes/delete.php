<?php
require_once '../../includes/page_functions/modules/quotes.php'; 

requireRole(ROLE_ADMIN);

 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flashMessage('Méthode non autorisée.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/quotes/index.php');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$token = $_POST['csrf_token'] ?? '';

 
if (!validateToken($token)) {
    flashMessage('Erreur de sécurité ou jeton expiré. Veuillez réessayer.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/quotes/index.php');
}

 
if ($id <= 0) {
    flashMessage('Identifiant de devis invalide.', 'danger');
     redirectTo(WEBADMIN_URL . '/modules/quotes/index.php');
}

 
$result = quotesDelete($id); 
flashMessage($result['message'], $result['success'] ? 'success' : 'danger');

 
redirectTo(WEBADMIN_URL . '/modules/quotes/index.php'); 
?>
