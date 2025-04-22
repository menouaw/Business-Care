<?php
require_once __DIR__ . '/../init.php';

/**
 * Récupère les statistiques pour le tableau de bord
 * 
 * @return array Tableau contenant les différentes statistiques
 */
function getDashboardStats() {
    return [
        'total_users' => countTableRows('personnes'),
        'total_companies' => countTableRows('entreprises'),
        'total_contracts' => countTableRows('contrats'),
        'active_contracts' => countTableRows('contrats', "statut = '" . STATUS_ACTIVE . "'")
    ];
}

/**
 * Récupère les activités récentes pour le tableau de bord
 * 
 * @param int $limit Nombre d'activités à récupérer
 * @return array Tableau d'activités récentes
 */
function getDashboardRecentActivities($limit = 20) {
    return getRecentActivities($limit);
} 