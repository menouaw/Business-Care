<?php
require_once __DIR__ . '/../../../includes/init.php';

/**
 * Récupère les statistiques principales pour le tableau de bord de l'entreprise.
 *
 * @param int $entreprise_id L'ID de l'entreprise connectée.
 * @return array Tableau contenant les statistiques (ex: nb_salaries, nb_rdv_recents).
 */
function getCompanyDashboardStats(int $entreprise_id): array
{


    $activeEmployeesCount = 0;
    if (defined('TABLE_USERS') && defined('STATUS_ACTIVE')) {
        $activeEmployeesCount = countTableRows(TABLE_USERS, 'entreprise_id = ? AND statut = ?', [$entreprise_id, STATUS_ACTIVE]);
    }



    $recentAppointmentsCount = 0;

    return [
        'active_employees' => $activeEmployeesCount,
        'recent_appointments' => $recentAppointmentsCount,
    ];
}


function getCompanyRecentActivities(int $entreprise_id, int $limit = 10): array
{


    if (!defined('TABLE_LOGS') || !defined('TABLE_USERS')) {
        return [];
    }

    
    $allowed_actions = [
        'login',
        'rdv_creation',
        'rdv_modification',
        'rdv_annulation',
        'profile_update'
        
    ];
    $action_placeholders = implode(', ', array_fill(0, count($allowed_actions), '?'));

    $sql = "SELECT l.created_at, l.action, l.details, p.prenom, p.nom 
            FROM " . TABLE_LOGS . " l
            LEFT JOIN " . TABLE_USERS . " p ON l.personne_id = p.id
            WHERE p.entreprise_id = ? AND l.action IN ($action_placeholders)
            ORDER BY l.created_at DESC
            LIMIT ?";

    $params = array_merge([$entreprise_id], $allowed_actions, [$limit]);

    try {
        
        
        
        $action_list = implode(', ', array_map(function ($action) {
            return getDbConnection()->quote($action); 
        }, $allowed_actions));

        $sql_safe = "SELECT l.created_at, l.action, l.details, p.prenom, p.nom 
                     FROM " . TABLE_LOGS . " l
                     LEFT JOIN " . TABLE_USERS . " p ON l.personne_id = p.id
                     WHERE p.entreprise_id = :entreprise_id AND l.action IN ($action_list)
                     ORDER BY l.created_at DESC
                     LIMIT :limit";

        $params_safe = [
            ':entreprise_id' => $entreprise_id,
            ':limit' => $limit
        ];

        $stmt = executeQuery($sql_safe, $params_safe);
        return $stmt->fetchAll();
    } catch (Exception $e) {

        error_log("Erreur getCompanyRecentActivities: " . $e->getMessage());
        return [];
    }
}
