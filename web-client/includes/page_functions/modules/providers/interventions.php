<?php

require_once __DIR__ . '/../../../init.php';

/**
 * Récupère les interventions (créneaux planifiés) pour un prestataire donné.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param string $filter_status Filtre par statut ('upcoming', 'past', 'all').
 * @param int $limit Nombre d'interventions par page.
 * @param int $offset Décalage pour la pagination.
 * @return array Contient ['interventions' => array, 'total' => int].
 */
function getProviderInterventions(int $provider_id, string $filter_status = 'upcoming', int $limit = 15, int $offset = 0): array
{
    $result = ['interventions' => [], 'total' => 0];
    if ($provider_id <= 0) {
        return $result;
    }

    $base_sql = "FROM consultation_creneaux cc
                 JOIN prestations p ON cc.prestation_id = p.id
                 LEFT JOIN sites s ON cc.site_id = s.id
                 WHERE cc.praticien_id = :provider_id";

    $params = [':provider_id' => $provider_id];
    $where_status = '';

    
    switch ($filter_status) {
        case 'upcoming':
            
            $where_status = " AND cc.start_time >= NOW()";
            break;
        case 'past':
            
            $where_status = " AND cc.start_time < NOW()";
            break;
            
    }

    
    $sql_count = "SELECT COUNT(cc.id) " . $base_sql . $where_status;
    $stmt_count = executeQuery($sql_count, $params);
    $total_interventions = $stmt_count ? (int)$stmt_count->fetchColumn() : 0;
    $result['total'] = $total_interventions;

    if ($total_interventions === 0) {
        return $result; 
    }

    
    $sql = "SELECT
                cc.id,
                cc.start_time,
                cc.end_time,
                cc.is_booked, 
                p.nom AS prestation_nom,
                p.type AS prestation_type,
                s.nom AS site_nom,
                s.adresse AS site_adresse,
                s.code_postal AS site_cp,
                s.ville AS site_ville
            " . $base_sql . $where_status .
        " ORDER BY cc.start_time " . ($filter_status === 'past' ? 'DESC' : 'ASC') . 
        " LIMIT :limit OFFSET :offset";

    
    $params[':limit'] = (int)$limit;
    $params[':offset'] = (int)$offset;

    $stmt = executeQuery($sql, $params);
    $result['interventions'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    return $result;
}

/**
 * Détermine le statut d'une intervention (À venir, Passée) basé sur l'heure de début.
 *
 * @param string|null $start_time L'heure de début du créneau.
 * @return string Le statut formaté ('À venir', 'Passée', 'Inconnu').
 */
function formatInterventionStatus(?string $start_time): string
{
    if ($start_time === null) return 'Inconnu';

    try {
        $startTime = new DateTime($start_time);
        $now = new DateTime();
        return ($startTime >= $now) ? 'À venir' : 'Passée';
    } catch (Exception $e) {
        return 'Inconnu'; 
    }
}

/**
 * Retourne la classe CSS Bootstrap pour le badge de statut d'intervention.
 *
 * @param string|null $start_time L'heure de début du créneau.
 * @return string La classe CSS du badge.
 */
function getInterventionStatusBadgeClass(?string $start_time): string
{
    $status = formatInterventionStatus($start_time);
    switch ($status) {
        case 'À venir':
            return 'info';
        case 'Passée':
            return 'secondary';
        default:
            return 'light';
    }
}

/**
 * Calcule la durée d'une intervention en minutes.
 *
 * @param string|null $start_time
 * @param string|null $end_time
 * @return int|null Durée en minutes ou null si dates invalides.
 */
function calculateInterventionDuration(?string $start_time, ?string $end_time): ?int
{
    if ($start_time === null || $end_time === null) return null;

    try {
        $start = new DateTime($start_time);
        $end = new DateTime($end_time);
        $interval = $start->diff($end);
        return $interval->h * 60 + $interval->i;
    } catch (Exception $e) {
        return null;
    }
}
