<?php
require_once __DIR__ . '/../../../../includes/init.php'; 

/**
 * Récupère les statistiques principales pour le tableau de bord Salarié.
 *
 * @param int $salarie_id L'ID du salarié connecté.
 * @return array Tableau des statistiques ['prochains_rdv', 'notifications_non_lues'].
 */
function getSalarieDashboardStats(int $salarie_id): array
{
    if ($salarie_id <= 0) {
        return ['prochains_rdv' => 0, 'notifications_non_lues' => 0];
    }

    
    $sqlRdv = "SELECT COUNT(id) FROM " . TABLE_APPOINTMENTS . "
               WHERE personne_id = :salarie_id
               AND date_rdv > NOW()
               AND statut IN ('planifie', 'confirme')";
    $stmtRdv = executeQuery($sqlRdv, [':salarie_id' => $salarie_id]);
    $prochains_rdv = (int) $stmtRdv->fetchColumn();

    
    $notifications_non_lues = getUnreadNotificationCount($salarie_id);

    return [
        'prochains_rdv' => $prochains_rdv,
        'notifications_non_lues' => $notifications_non_lues,
    ];
}

/**
 * Récupère les prochains rendez-vous d'un salarié.
 *
 * @param int $salarie_id L'ID du salarié.
 * @param int $limit Nombre maximum de rendez-vous à retourner.
 * @return array Tableau des prochains rendez-vous.
 */
function getUpcomingAppointments(int $salarie_id, int $limit = 5): array
{
    if ($salarie_id <= 0) {
        return [];
    }

    
    $sql = "SELECT rdv.id, rdv.date_rdv, rdv.statut,
                   pres.nom as prestation_nom,
                   CONCAT(prat.prenom, ' ', prat.nom) as praticien_nom
            FROM " . TABLE_APPOINTMENTS . " rdv
            LEFT JOIN " . TABLE_PRESTATIONS . " pres ON rdv.prestation_id = pres.id
            LEFT JOIN " . TABLE_USERS . " prat ON rdv.praticien_id = prat.id AND prat.role_id = :role_prestataire
            WHERE rdv.personne_id = :salarie_id
            AND rdv.date_rdv > NOW()
            AND rdv.statut IN ('planifie', 'confirme')
            ORDER BY rdv.date_rdv ASC
            LIMIT :limit";

    $params = [
        ':salarie_id' => $salarie_id,
        ':limit' => $limit,
        ':role_prestataire' => ROLE_PRESTATAIRE 
    ];

    return executeQuery($sql, $params)->fetchAll();
}
