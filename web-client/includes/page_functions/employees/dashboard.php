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
   
    try {
       
        $stmtRdv = executeQuery(
            'SELECT COUNT(*) FROM rendez_vous WHERE salarie_id = :salarie_id AND date_heure > NOW()',
            [':salarie_id' => $salarie_id]
        );
        $prochainsRdv = $stmtRdv ? $stmtRdv->fetchColumn() : 0;

       
        $stmtNotif = executeQuery(
            'SELECT COUNT(*) FROM notifications WHERE destinataire_id = :salarie_id AND statut = :statut',
            [':salarie_id' => $salarie_id, ':statut' => 'non_lu']
        );
        $notifNonLues = $stmtNotif ? $stmtNotif->fetchColumn() : 0;

        return [
            'prochains_rdv'        => (int)$prochainsRdv,
            'notifications_non_lues' => (int)$notifNonLues,
        ];
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des stats dashboard salarié ID {$salarie_id}: " . $e->getMessage());
       
        return [
            'prochains_rdv' => 0,
            'notifications_non_lues' => 0,
        ];
    }
   
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
   
    $sql = "SELECT 
               n.id, 
               n.type, 
               n.message, 
               n.date_creation, 
               n.statut 
           FROM notifications n 
           WHERE n.destinataire_id = :salarie_id 
           ORDER BY n.date_creation DESC 
           LIMIT :limit";

    $params = [
        ':salarie_id' => $salarie_id,
        ':limit' => $limit
    ];

    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
       
        error_log("Error fetching employee recent activities for salarie_id $salarie_id: " . $e->getMessage());
       
        return [];
    }
}
