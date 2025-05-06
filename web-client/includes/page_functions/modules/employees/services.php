<?php

require_once __DIR__ . '/../../../../includes/init.php';

/**
 * Récupère une liste paginée de prestations avec filtres optionnels.
 * Fonction copiée ici (existe aussi dans prestations.php).
 *
 * @param string $type Filtre optionnel par type de prestation.
 * @param string $categorie Filtre optionnel par catégorie.
 * @param int $page Numéro de la page demandée.
 * @param int $perPage Nombre d'éléments par page.
 * @return array Tableau contenant les prestations et les informations de pagination.
 */
function getPrestations(string $type = '', string $categorie = '', int $page = 1, int $perPage = DEFAULT_ITEMS_PER_PAGE): array
{
    $where = [];
    $params = [];

    if (!empty($type)) {
        $where[] = "type = :type";
        $params['type'] = $type;
    }

    if (!empty($categorie)) {
        $where[] = "categorie = :categorie";
        $params['categorie'] = $categorie;
    }

    $whereClause = !empty($where) ? implode(' AND ', $where) : '';


    return paginateResults('prestations', $page, $perPage, $whereClause, 'nom ASC', $params);
}

/**
 * Vérifie si la catégorie correspond aux critères de préférence "Santé Mentale".
 *
 * @param string $categoryLower Catégorie de la prestation en minuscules.
 * @param array $userInterestsLower Intérêts de l'utilisateur en minuscules.
 * @return bool True si ça correspond.
 */
function _matchesMentalPreference(string $categoryLower, array $userInterestsLower): bool
{
    $mentalKeywords = ['sante mentale', 'bien-etre mental', 'sommeil'];
    $mentalCategories = ['mentale', 'stress', 'sophrologie', 'meditation', 'sommeil'];

    $hasMentalInterest = !empty(array_intersect($mentalKeywords, $userInterestsLower));
    if (!$hasMentalInterest) {
        return false; 
    }

    $isMentalCategory = false;
    foreach ($mentalCategories as $catKeyword) {
        if (str_contains($categoryLower, $catKeyword)) {
            $isMentalCategory = true;
            break;
        }
    }

    return $isMentalCategory; 
}

/**
 * Vérifie si la catégorie correspond aux critères de préférence "Activité Physique".
 *
 * @param string $categoryLower Catégorie de la prestation en minuscules.
 * @param array $userInterestsLower Intérêts de l'utilisateur en minuscules.
 * @return bool True si ça correspond.
 */
function _matchesPhysicalPreference(string $categoryLower, array $userInterestsLower): bool
{
    $physicalKeywords = ['activité physique', 'activite physique', 'bien-etre physique', 'sport'];
    $physicalCategories = ['physique', 'sport', 'yoga', 'massage'];

    $hasPhysicalInterest = !empty(array_intersect($physicalKeywords, $userInterestsLower));
    if (!$hasPhysicalInterest) {
        return false; 
    }

    $isPhysicalCategory = false;
    foreach ($physicalCategories as $catKeyword) {
        if (str_contains($categoryLower, $catKeyword)) {
            $isPhysicalCategory = true;
            break;
        }
    }

    return $isPhysicalCategory; 
}

/**
 * Détermine si une prestation doit être considérée comme "préférée" pour un utilisateur,
 * en fonction de ses intérêts enregistrés et de la catégorie/mots-clés de la prestation.
 * Refactorisé pour utiliser des fonctions d'aide pour chaque type de vérification de préférence.
 *
 * @param array $prestation Les détails de la prestation.
 * @param array $userInterestsLower Tableau des intérêts de l'utilisateur en minuscules.
 * @return bool True si la prestation est considérée comme préférée.
 */
function _isPrestationPreferredForUser(array $prestation, array $userInterestsLower): bool
{
    if (empty($userInterestsLower)) {
        return false;
    }

    $categoryLower = strtolower($prestation['categorie'] ?? 'autres');

    
    if (in_array($categoryLower, $userInterestsLower)) {
        return true;
    }

    
    if (_matchesMentalPreference($categoryLower, $userInterestsLower)) {
        return true;
    }

    
    if (_matchesPhysicalPreference($categoryLower, $userInterestsLower)) {
        return true;
    }

    return false;
}

/**
 * Trie une liste de prestations en "préférées" et "autres" en fonction des intérêts de l'utilisateur.
 *
 * @param array $allPrestations Liste de toutes les prestations.
 * @param array $userInterestsLower Tableau des intérêts de l'utilisateur en minuscules.
 * @return array Tableau avec les clés 'preferred' et 'other'.
 */
function _sortPrestationsByPreference(array $allPrestations, array $userInterestsLower): array
{
    $sortedPrestations = [
        'preferred' => [],
        'other' => []
    ];

    if (empty($allPrestations)) {
        return $sortedPrestations;
    }

    foreach ($allPrestations as $prestation) {
        if (_isPrestationPreferredForUser($prestation, $userInterestsLower)) {
            $sortedPrestations['preferred'][] = $prestation;
        } else {
            $sortedPrestations['other'][] = $prestation;
        }
    }

    return $sortedPrestations;
}

/**
 * Prépare les données nécessaires pour la page du catalogue des services pour les employés.
 * Refactorisé pour utiliser des fonctions d'aide pour déterminer les préférences et trier.
 *
 * @return array Données pour la vue (titre, prestations préférées, autres prestations).
 */
function setupEmployeeServicesPage()
{
    requireRole(ROLE_SALARIE);

    $salarie_id = $_SESSION['user_id'] ?? 0;

    $pageTitle = "Catalogue des Services";
    $userInterests = [];

    if ($salarie_id > 0) {
        $userInterests = getUserInterests($salarie_id);
    }
    $userInterestsLower = array_map('strtolower', $userInterests);


    $allPrestations = fetchAll('prestations', '', 'categorie ASC, nom ASC');


    $sorted = _sortPrestationsByPreference($allPrestations, $userInterestsLower);

    return [
        'pageTitle' => $pageTitle,
        'preferredPrestations' => $sorted['preferred'],
        'otherPrestations' => $sorted['other']
    ];
}
