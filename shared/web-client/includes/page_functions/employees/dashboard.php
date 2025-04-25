<?php
require_once __DIR__ . '/../../../includes/init.php'; 

/**
 * Récupère les statistiques pour le tableau de bord Salarié.
 * (Ex: prochains RDV, dernières notifications, etc.)
 *
 * @param int $salarie_id L'ID du salarié connecté.
 * @return array Tableau des statistiques.
 */
function getSalarieDashboardStats(int $salarie_id): array
{
    
    return [
        'prochains_rdv' => 0, 
        'notifications_non_lues' => 0, 
    ];
}

/**
 * Récupère les activités récentes pour le tableau de bord Salarié.
 *
 * @param int $salarie_id L'ID du salarié connecté.
 * @param int $limit Nombre maximum d'activités.
 * @return array Tableau des activités.
 */
function getSalarieRecentActivities(int $salarie_id, int $limit = 5): array
{
    
    return []; 
}

