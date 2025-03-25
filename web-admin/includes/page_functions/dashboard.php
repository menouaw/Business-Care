<?php
require_once __DIR__ . '/../init.php';

/**
 * Recupere les statistiques pour le tableau de bord
 * @return array Tableau contenant les differentes statistiques
 */
function getDashboardStats() {
    return [
        'total_users' => countTableRows('personnes'),
        'total_companies' => countTableRows('entreprises'),
        'total_contracts' => countTableRows('contrats'),
        'active_contracts' => countTableRows('contrats', "statut = 'actif'")
    ];
}

/**
 * Recupere les activites recentes pour le tableau de bord
 * @param int $limit Nombre d'activites a recuperer
 * @return array Tableau d'activites recentes
 */
function getDashboardRecentActivities($limit = 10) {
    return getRecentActivities($limit);
} 