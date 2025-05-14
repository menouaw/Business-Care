<?php
require_once '../../includes/page_functions/modules/events.php';

requireRole(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleCsrfFailureRedirect(0, 'events', 'suppression evenement');
    }

    $eventId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($eventId === false || $eventId <= 0) {
        flashMessage("Identifiant d'evenement invalide.", 'danger');
        redirectTo(WEBADMIN_URL . '/modules/events/index.php');
    }

    $result = eventsDelete($eventId);

    if ($result['success']) {
        flashMessage($result['message'], 'success');
    } else {
        flashMessage($result['message'], 'danger');
    }

    redirectTo(WEBADMIN_URL . '/modules/events/index.php');

} else {
    flashMessage("Methode de requete invalide.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/events/index.php');
}

?>
