<?php

require_once __DIR__ . '/../../../init.php';



/**
 * Prépare les données nécessaires pour la page du catalogue des services pour les employés.
 * Récupère la liste de toutes les prestations avec pagination.
 *
 * @return array Données pour la vue (titre, prestations préférées, autres prestations).
 */
function setupEmployeeServicesPage()
{
    requireRole(ROLE_SALARIE);

    $salarie_id = $_SESSION['user_id'] ?? 0;

    $pageTitle = "Catalogue des Services";
    $preferredPrestations = [];
    $otherPrestations = [];
    $userInterests = [];



    if ($salarie_id > 0) {
        $userInterests = getUserInterests($salarie_id);
    }
    $userInterestsLower = array_map('strtolower', $userInterests);



    $allPrestations = fetchAll('prestations', '', 'categorie ASC, nom ASC');


    if (!empty($allPrestations)) {
        foreach ($allPrestations as $prestation) {
            $categoryLower = strtolower($prestation['categorie'] ?? 'autres');
            $isPreferred = false;

            if (in_array($categoryLower, $userInterestsLower)) {
                $isPreferred = true;
            } else {

                if ((in_array('sante mentale', $userInterestsLower) || in_array('bien-etre mental', $userInterestsLower) || in_array('sommeil', $userInterestsLower)) &&
                    (str_contains($categoryLower, 'mentale') || str_contains($categoryLower, 'stress') || str_contains($categoryLower, 'sophrologie') || str_contains($categoryLower, 'meditation') || str_contains($categoryLower, 'sommeil'))
                ) {
                    $isPreferred = true;
                }

                if ((in_array('activité physique', $userInterestsLower) || in_array('bien-etre physique', $userInterestsLower)) &&
                    (str_contains($categoryLower, 'physique') || str_contains($categoryLower, 'sport') || str_contains($categoryLower, 'yoga') || str_contains($categoryLower, 'massage'))
                ) {
                    $isPreferred = true;
                }
            }

            if ($isPreferred) {
                $preferredPrestations[] = $prestation;
            } else {
                $otherPrestations[] = $prestation;
            }
        }
    }



    return [
        'pageTitle' => $pageTitle,
        'preferredPrestations' => $preferredPrestations,
        'otherPrestations' => $otherPrestations
    ];
}
