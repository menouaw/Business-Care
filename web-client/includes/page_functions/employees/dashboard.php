<?php
require_once __DIR__ . '/../../../includes/init.php'; // Assure-toi que init.php existe

/**
 * Récupère les statistiques pour le tableau de bord Salarié.
 * (Ex: prochains RDV, dernières notifications, etc.)
 *
 * @param int $salarie_id L'ID du salarié connecté.
 * @return array Tableau des statistiques.
 */
function getSalarieDashboardStats(int $salarie_id): array
{
    // TODO: Implémenter la logique (ex: compter les RDV à venir)
    return [
        'prochains_rdv' => 0, // Placeholder
        'notifications_non_lues' => 0, // Placeholder
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
    // TODO: Implémenter la logique (ex: derniers RDV pris, messages communauté)
    return []; // Placeholder
}

// Ajouter d'autres fonctions si nécessaire 