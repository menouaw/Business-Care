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
    $userId = $_SESSION['user_id'] ?? 0;

    $pageTitle = "Conseils Bien-être";
    $categoriesPerPage = 2;
    $preferredCounselsGroupedForPage = [];
    $otherCounselsGroupedForPage = [];
    $pagination = [
        'currentPage' => 1,
        'totalPages' => 1,
        'itemsPerPage' => $categoriesPerPage
    ];

    try {
        
        $userInterests = [];
        if ($userId > 0) {
            $userInterestsRaw = fetchAll('personne_interets', 'personne_id = :user_id', '', null, null, [':user_id' => $userId]);
            $userInterestIds = array_column($userInterestsRaw, 'interet_id');
            if (!empty($userInterestIds)) {
                $placeholders = implode(',', array_fill(0, count($userInterestIds), '?'));
                $sqlInterests = "SELECT nom FROM interets_utilisateurs WHERE id IN ($placeholders)";
                $userInterestsData = executeQuery($sqlInterests, $userInterestIds)->fetchAll(PDO::FETCH_COLUMN);
                $userInterests = $userInterestsData ?: [];
            }
        }
        

        
        $allConseils = fetchAll(
            'conseils',
            '',
            'categorie ASC, titre ASC'
        );

        
        $allPreferredCounselsGrouped = [];
        $allOtherCounselsGrouped = [];
        foreach ($allConseils as $conseil) {
            $category = $conseil['categorie'] ?? 'Autres';
            $isPreferred = in_array($category, $userInterests);

            if ($isPreferred) {
                if (!isset($allPreferredCounselsGrouped[$category])) {
                    $allPreferredCounselsGrouped[$category] = [];
                }
                $allPreferredCounselsGrouped[$category][] = $conseil;
            } else {
                if (!isset($allOtherCounselsGrouped[$category])) {
                    $allOtherCounselsGrouped[$category] = [];
                }
                $allOtherCounselsGrouped[$category][] = $conseil;
            }
        }
        

        
        
        $allCategories = array_unique(array_merge(array_keys($allPreferredCounselsGrouped), array_keys($allOtherCounselsGrouped)));
        sort($allCategories); 
        $totalCategories = count($allCategories);

        if ($totalCategories > 0) {
            $totalPages = ceil($totalCategories / $categoriesPerPage);
            $pagination['totalPages'] = $totalPages;

            $currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
                'options' => ['default' => 1, 'min_range' => 1]
            ]);
            $currentPage = min($currentPage, $totalPages);
            $pagination['currentPage'] = $currentPage;

            $categoryOffset = ($currentPage - 1) * $categoriesPerPage;
            $categoriesToShow = array_slice($allCategories, $categoryOffset, $categoriesPerPage);

            
            foreach ($categoriesToShow as $catName) {
                if (isset($allPreferredCounselsGrouped[$catName])) {
                    $preferredCounselsGroupedForPage[$catName] = $allPreferredCounselsGrouped[$catName];
                }
                if (isset($allOtherCounselsGrouped[$catName])) {
                    $otherCounselsGroupedForPage[$catName] = $allOtherCounselsGrouped[$catName];
                }
            }

            if (empty($preferredCounselsGroupedForPage) && empty($otherCounselsGroupedForPage) && $currentPage > 1) {
                redirectTo(WEBCLIENT_URL . '/modules/employees/counsel.php?page=1');
            }
        }
        

    } catch (Exception $e) {
        error_log("Erreur lors de la récupération ou pagination des conseils par catégorie: " . $e->getMessage());
        flashMessage("Impossible de charger les conseils bien-être.", "danger");
        $preferredCounselsGroupedForPage = [];
        $otherCounselsGroupedForPage = [];
    }

    return [
        'pageTitle' => $pageTitle,
        'preferredCounselsGrouped' => $preferredCounselsGroupedForPage, 
        'otherCounselsGrouped' => $otherCounselsGroupedForPage,       
        'pagination' => $pagination
    ];
}
