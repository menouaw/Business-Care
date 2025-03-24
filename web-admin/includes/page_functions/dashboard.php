<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

/**
 * Récupère les statistiques du tableau de bord.
 *
 * Retourne un tableau associatif contenant les mesures essentielles pour l'affichage du tableau de bord :
 * - "total_users" : nombre total d'utilisateurs.
 * - "total_companies" : nombre total d'entreprises.
 * - "total_contracts" : nombre total de contrats.
 * - "active_contracts" : nombre de contrats actifs.
 *
 * @return array Tableau associatif des statistiques.
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