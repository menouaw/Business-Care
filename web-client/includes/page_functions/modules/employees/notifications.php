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



    try {
        updateRow(
            'notifications',
            ['lu' => 1],
            "personne_id = :user_id AND lu = 0",
            [':user_id' => $salarie_id]
        );
    } catch (Exception $e) {
        error_log("Erreur lors du marquage des notifications comme lues pour user $salarie_id: " . $e->getMessage());
    }


    $itemsPerPage = 10;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($currentPage < 1) {
        $currentPage = 1;
    }
    $offset = ($currentPage - 1) * $itemsPerPage;

    $notifications = [];
    $pagination = [];
    $totalItems = 0;

    try {

        $totalItems = countTableRows('notifications', "personne_id = :user_id", [':user_id' => $salarie_id]);


        if ($totalItems > 0) {
            $notifications = fetchAll(
                'notifications',
                "personne_id = :user_id",
                'created_at DESC',
                $itemsPerPage,
                $offset,
                [':user_id' => $salarie_id]
            );
        }


        $totalPages = ceil($totalItems / $itemsPerPage);
        $pagination = [
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'itemsPerPage' => $itemsPerPage
        ];
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des notifications pour user $salarie_id: " . $e->getMessage());
        flashMessage("Impossible de charger les notifications.", "danger");
        $notifications = [];
        $pagination = [];
    }



    return [
        'pageTitle' => $pageTitle,
        'notifications' => $notifications,
        'pagination' => $pagination
    ];
}
