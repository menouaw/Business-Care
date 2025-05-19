<?php

require_once __DIR__ . '/../../../init.php';


require_once __DIR__ . '/../../../../../shared/web-client/db.php';

/**
 * Prépare les données nécessaires pour la page des notifications de l'employé.
 * Récupère les notifications pour l'employé connecté et les marque comme lues.
 *
 * @return array Données pour la vue (titre, notifications, pagination).
 */
function setupEmployeeNotificationsPage()
{
    requireRole(ROLE_SALARIE);

    $salarie_id = $_SESSION['user_id'] ?? 0;
    if ($salarie_id <= 0) {
        flashMessage("Impossible d'identifier votre compte.", "danger");
        redirectTo(WEBCLIENT_URL . '/auth/login.php');
        exit;
    }

    $pageTitle = "Mes Notifications";

    updateRow(
        'notifications',
        ['lu' => 1],
        "personne_id = :user_id AND lu = 0",
        [':user_id' => $salarie_id]
    );

    $itemsPerPage = 10;
    $currentPage = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($currentPage - 1) * $itemsPerPage;

    $totalItems = countTableRows('notifications', "personne_id = :user_id", [':user_id' => $salarie_id]);
    $notifications = $totalItems > 0 ? fetchAll(
        'notifications',
        "personne_id = :user_id",
        'created_at DESC',
        $itemsPerPage,
        $offset,
        [':user_id' => $salarie_id]
    ) : [];

    $totalPages = ceil($totalItems / $itemsPerPage);
    $pagination = [
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'itemsPerPage' => $itemsPerPage
    ];

    return [
        'pageTitle' => $pageTitle,
        'notifications' => $notifications,
        'pagination' => $pagination
    ];
}
