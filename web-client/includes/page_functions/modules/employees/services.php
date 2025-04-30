<?php

require_once __DIR__ . '/../../../init.php';

/**
 * Prépare les données nécessaires pour la page du catalogue des services pour les employés.
 * Récupère la liste de toutes les prestations avec pagination.
 *
 * @return array Données pour la vue (titre, prestations, pagination).
 */
function setupEmployeeServicesPage()
{
    requireRole(ROLE_SALARIE);

    $salarie_id = $_SESSION['user_id'] ?? 0;

    $pageTitle = "Catalogue des Services";

    $itemsPerPage = 6;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($currentPage < 1) {
        $currentPage = 1;
    }
    $offset = ($currentPage - 1) * $itemsPerPage;

    $prestations = [];
    $pagination = [];
    $totalItems = 0;

    $totalItems = countTableRows('prestations');

    if ($totalItems > 0) {
        $prestations = fetchAll(
            'prestations',
            '',
            'categorie ASC, nom ASC',
            $itemsPerPage,
            $offset,
            []
        );
    } else {
        $prestations = [];
    }

    $totalPages = $itemsPerPage > 0 ? ceil($totalItems / $itemsPerPage) : 0;
    $pagination = [
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'itemsPerPage' => $itemsPerPage
    ];

    return [
        'pageTitle' => $pageTitle,
        'prestations' => $prestations,
        'pagination' => $pagination
    ];
}
