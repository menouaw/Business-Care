<?php

require_once __DIR__ . '/../../../init.php';


/**
 * Prépare les données nécessaires pour la page des Conseils Bien-être.
 * Récupère tous les conseils et les groupe par catégorie.
 *
 * @return array Données pour la vue (titre, conseils groupés).
 */
function setupCounselPage()
{
    requireRole(ROLE_SALARIE);

    $pageTitle = "Conseils Bien-être";
    $conseilsGrouped = [];

    try {
        
        $allConseils = fetchAll(
            'conseils',
            '',                      
            'categorie ASC, titre ASC' 
        );

        
        foreach ($allConseils as $conseil) {
            $category = $conseil['categorie'] ?? 'Autres';
            if (!isset($conseilsGrouped[$category])) {
                $conseilsGrouped[$category] = [];
            }
            $conseilsGrouped[$category][] = $conseil;
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des conseils: " . $e->getMessage());
        flashMessage("Impossible de charger les conseils bien-être.", "danger");
        $conseilsGrouped = []; 
    }

    return [
        'pageTitle' => $pageTitle,
        'conseilsGrouped' => $conseilsGrouped
    ];
}
