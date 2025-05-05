<?php
require_once __DIR__ . '/../../../../includes/init.php'; // Inclut config, db, functions, auth

/**
 * Récupère les statistiques pour le tableau de bord du prestataire.
 *
 * @param int $provider_id L'ID de l'utilisateur prestataire (personnes.id).
 * @return array Un tableau contenant les statistiques clés.
 */
function getProviderDashboardStats(int $provider_id): array
{
    $stats = [
        'profile_status' => 'inconnu',
        'upcoming_appointments' => 0,
        'pending_habilitations' => 0,
    ];

    if ($provider_id <= 0) {
        return $stats;
    }

    try {
        // 1. Statut du profil
        $providerInfo = fetchOne(TABLE_USERS, 'id = :id', [':id' => $provider_id], 'statut');
        if ($providerInfo) {
            $stats['profile_status'] = $providerInfo['statut'];
        }

        // 2. Nombre de RDV à venir confirmés
        // (On considère "à venir" comme aujourd'hui ou plus tard)
        $sql_appointments = "SELECT COUNT(*) 
                             FROM rendez_vous 
                             WHERE praticien_id = :provider_id 
                             AND date_rdv >= CURDATE()
                             AND statut = 'confirme'"; // Ou 'planifie' si vous les incluez aussi
        $stmt_appointments = executeQuery($sql_appointments, [':provider_id' => $provider_id]);
        $stats['upcoming_appointments'] = (int)$stmt_appointments->fetchColumn();

        // 3. Nombre d'habilitations en attente de validation
        $sql_habilitations = "SELECT COUNT(*) 
                              FROM habilitations 
                              WHERE prestataire_id = :provider_id 
                              AND statut = 'en_attente_validation'";
        $stmt_habilitations = executeQuery($sql_habilitations, [':provider_id' => $provider_id]);
        $stats['pending_habilitations'] = (int)$stmt_habilitations->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur PDO dans getProviderDashboardStats pour prestataire ID {$provider_id}: " . $e->getMessage());
        // Retourner les stats par défaut en cas d'erreur
    }

    return $stats;
}
