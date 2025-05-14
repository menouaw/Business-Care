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
    $userEntrepriseId = $_SESSION['user_entreprise'] ?? null;

    $pageTitle = "Conseils Bien-être";
    $categoriesPerPage = 2;
    $preferredCounselsGroupedForPage = [];
    $otherCounselsGroupedForPage = [];
    $userServiceType = null;
    $allConseils = [];

    $pagination = [
        'currentPage' => 1,
        'totalPages' => 1,
        'itemsPerPage' => $categoriesPerPage
    ];

    if ($userEntrepriseId) {
        $db = getDbConnection();
        $tableContracts = defined('TABLE_CONTRACTS') ? TABLE_CONTRACTS : 'contrats';
        $tableServices = defined('TABLE_SERVICES') ? TABLE_SERVICES : 'services';

        $sqlService = "SELECT s.type 
                       FROM {$tableContracts} c 
                       JOIN {$tableServices} s ON c.service_id = s.id 
                       WHERE c.entreprise_id = :entreprise_id 
                         AND c.statut = 'actif' 
                       ORDER BY c.date_debut DESC
                       LIMIT 1";
        try {
            $stmtService = $db->prepare($sqlService);
            $stmtService->execute([':entreprise_id' => $userEntrepriseId]);
            $serviceInfo = $stmtService->fetch(PDO::FETCH_ASSOC);
            if ($serviceInfo) {
                $userServiceType = $serviceInfo['type'];
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du type de service de l'utilisateur: " . $e->getMessage());
        }
    }

    try {
        $tableConseils = defined('TABLE_CONSEILS') ? TABLE_CONSEILS : 'conseils';
        if ($userServiceType !== 'Starter Pack') {
            $allConseils = fetchAll(
                $tableConseils,
                '',
                'categorie ASC, titre ASC'
            );
        }
        
        $userInterests = [];
        if ($userId > 0 && $userServiceType === 'Premium Pack' && !empty($allConseils)) {
            $tablePersonneInterets = defined('TABLE_PERSONNE_INTERETS') ? TABLE_PERSONNE_INTERETS : 'personne_interets';
            $tableInteretsUtilisateurs = defined('TABLE_INTERETS_UTILISATEURS') ? TABLE_INTERETS_UTILISATEURS : 'interets_utilisateurs';

            $userInterestsRaw = fetchAll($tablePersonneInterets, 'personne_id = :user_id', '', null, null, [':user_id' => $userId]);
            $userInterestIds = array_column($userInterestsRaw, 'interet_id');
            if (!empty($userInterestIds)) {
                $placeholders = implode(',', array_fill(0, count($userInterestIds), '?'));
                $sqlInterests = "SELECT nom FROM {$tableInteretsUtilisateurs} WHERE id IN ($placeholders)";
                $userInterestsData = executeQuery($sqlInterests, $userInterestIds)->fetchAll(PDO::FETCH_COLUMN);
                $userInterests = $userInterestsData ?: [];
            }
        }
        
        $allPreferredCounselsGrouped = [];
        $allOtherCounselsGrouped = [];
        foreach ($allConseils as $conseil) {
            $category = $conseil['categorie'] ?? 'Autres';
            
            if ($userServiceType === 'Premium Pack' && in_array($category, $userInterests)) {
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
        'pagination' => $pagination,
        'userServiceType' => $userServiceType
    ];
}
