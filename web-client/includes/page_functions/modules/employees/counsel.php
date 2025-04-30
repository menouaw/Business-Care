<?php

require_once __DIR__ . '/../../../init.php';


/**
 * Prépare les données nécessaires pour la page des Conseils Bien-être,
 * paginée par catégorie (2 catégories par page).
 *
 * @return array Données pour la vue (titre, conseils groupés pour la page, pagination).
 */
function setupCounselPage()
{
    requireRole(ROLE_SALARIE);

    $pageTitle = "Conseils Bien-être";
    $categoriesPerPage = 2;
    $conseilsGroupedForPage = [];
    $pagination = [
        'currentPage' => 1,
        'totalPages' => 1,
        'itemsPerPage' => $categoriesPerPage // Ici, items = catégories
    ];

    try {
        // 1. Récupérer TOUS les conseils et les grouper par catégorie
        $allConseilsGrouped = [];
        $allConseils = fetchAll(
            'conseils',
            '',
            'categorie ASC, titre ASC' // Trier d'abord par catégorie
        );

        foreach ($allConseils as $conseil) {
            $category = $conseil['categorie'] ?? 'Autres';
            if (!isset($allConseilsGrouped[$category])) {
                $allConseilsGrouped[$category] = [];
            }
            $allConseilsGrouped[$category][] = $conseil;
        }

        // 2. Pagination basée sur les catégories
        $categoryNames = array_keys($allConseilsGrouped);
        $totalCategories = count($categoryNames);

        if ($totalCategories > 0) {
            $totalPages = ceil($totalCategories / $categoriesPerPage);
            $pagination['totalPages'] = $totalPages;

            // Récupérer la page actuelle
            $currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
                'options' => ['default' => 1, 'min_range' => 1]
            ]);
            $currentPage = min($currentPage, $totalPages); // S'assurer qu'on ne dépasse pas
            $pagination['currentPage'] = $currentPage;

            // Calculer l'offset pour les catégories
            $categoryOffset = ($currentPage - 1) * $categoriesPerPage;

            // Sélectionner les noms de catégories pour la page actuelle
            $categoriesToShow = array_slice($categoryNames, $categoryOffset, $categoriesPerPage);

            // 3. Filtrer les conseils groupés pour ne garder que ceux des catégories sélectionnées
            foreach ($categoriesToShow as $catName) {
                if (isset($allConseilsGrouped[$catName])) {
                    $conseilsGroupedForPage[$catName] = $allConseilsGrouped[$catName];
                }
            }

            // Gérer le cas où on arrive sur une page vide (si des cat. ont été supprimées)
            if (empty($conseilsGroupedForPage) && $currentPage > 1) {
                redirectTo(WEBCLIENT_URL . '/modules/employees/counsel.php?page=1');
            }
        } else {
            flashMessage("Aucun conseil bien-être disponible pour le moment.", "info");
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération ou pagination des conseils par catégorie: " . $e->getMessage());
        flashMessage("Impossible de charger les conseils bien-être.", "danger");
        $conseilsGroupedForPage = [];
        // Garder pagination par défaut
    }

    return [
        'pageTitle' => $pageTitle,
        'conseilsGrouped' => $conseilsGroupedForPage, // Conseils groupés POUR la page actuelle
        'pagination' => $pagination
    ];
}
