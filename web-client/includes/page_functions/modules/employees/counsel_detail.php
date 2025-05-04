<?php

require_once __DIR__ . '/../../../init.php';
/**
 * Prépare les données nécessaires pour la page de détail d'un conseil.
 * Récupère le conseil spécifique basé sur l'ID de l'URL.
 *
 * @return array Données pour la vue (titre, conseil).
 */
function setupCounselDetailPage()
{
    requireRole(ROLE_SALARIE);

    $conseil_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $conseil = null;
    $pageTitle = "Détail du Conseil";

    if ($conseil_id <= 0) {
        flashMessage("Identifiant de conseil invalide.", "warning");
    } else {
        try {

            $conseil = fetchOne('conseils', "id = :id", [':id' => $conseil_id]);

            if ($conseil) {
                $pageTitle = htmlspecialchars($conseil['titre'] ?? 'Détail du Conseil');
            } else {
                flashMessage("Conseil non trouvé.", "warning");
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du détail du conseil ID $conseil_id: " . $e->getMessage());
            flashMessage("Impossible de charger le détail du conseil.", "danger");
            $conseil = null;
        }
    }

    return [
        'pageTitle' => $pageTitle,
        'conseil' => $conseil
    ];
}
