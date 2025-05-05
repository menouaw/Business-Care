<?php

require_once __DIR__ . '/../../../init.php';

/**
 * Récupère les rendez-vous pour un prestataire donné, avec options de filtrage et pagination.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param string $filter_status Filtre par statut ('upcoming', 'past', 'all').
 * @param int $limit Nombre de rendez-vous par page.
 * @param int $offset Décalage pour la pagination.
 * @return array Contient ['appointments' => array, 'total' => int].
 */
function getProviderAppointments(int $provider_id, string $filter_status = 'upcoming', int $limit = 15, int $offset = 0): array
{
    $result = ['appointments' => [], 'total' => 0];
    if ($provider_id <= 0) {
        return $result;
    }

    $base_sql = "FROM rendez_vous rv 
                 JOIN personnes p ON rv.personne_id = p.id 
                 JOIN prestations pr ON rv.prestation_id = pr.id 
                 WHERE rv.praticien_id = :provider_id";

    $params = [':provider_id' => $provider_id];
    $where_status = '';


    switch ($filter_status) {
        case 'upcoming':
            $where_status = " AND rv.statut IN ('planifie', 'confirme') AND rv.date_rdv >= CURDATE()";
            break;
        case 'past':

            $where_status = " AND (rv.statut = 'termine' OR rv.date_rdv < CURDATE())";
            break;
        case 'canceled':
            $where_status = " AND rv.statut = 'annule'";
            break;
    }


    $sql_count = "SELECT COUNT(*) " . $base_sql . $where_status;
    $stmt_count = executeQuery($sql_count, $params);
    $total_appointments = $stmt_count ? (int)$stmt_count->fetchColumn() : 0;
    $result['total'] = $total_appointments;

    if ($total_appointments === 0) {
        return $result;
    }


    $sql = "SELECT 
                rv.id, 
                rv.date_rdv, 
                rv.duree, 
                rv.lieu, 
                rv.type_rdv, 
                rv.statut, 
                rv.notes AS notes_rdv, 
                p.nom AS salarie_nom, 
                p.prenom AS salarie_prenom, 
                p.email AS salarie_email,
                p.telephone AS salarie_telephone,
                pr.nom AS prestation_nom
            " . $base_sql . $where_status .
        " ORDER BY rv.date_rdv DESC 
             LIMIT :limit OFFSET :offset";


    $params[':limit'] = (int)$limit;
    $params[':offset'] = (int)$offset;

    $stmt = executeQuery($sql, $params);
    $result['appointments'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    return $result;
}

/**
 * Retourne la classe CSS Bootstrap pour le badge de statut de rendez-vous.
 *
 * @param string|null $status Le statut du RDV.
 * @return string La classe CSS du badge.
 */
function getAppointmentStatusBadgeClass(?string $status): string
{
    switch (strtolower($status ?? '')) {
        case 'confirme':
            return 'success';
        case 'planifie':
            return 'info';
        case 'termine':
            return 'secondary';
        case 'annule':
            return 'danger';
        default:
            return 'light';
    }
}

/**
 * Formate le statut du rendez-vous pour l'affichage.
 *
 * @param string|null $status Le statut brut.
 * @return string Le statut formaté.
 */
function formatAppointmentStatus(?string $status): string
{
    
    return match (strtolower($status ?? '')) {
        'confirme' => 'Confirmé',
        'planifie' => 'Planifié',
        'termine' => 'Terminé',
        'annule' => 'Annulé',
        
        default => 'Inconnu' 
    };
}

/**
 * Prépare les données nécessaires pour l'affichage de la page des rendez-vous du prestataire.
 * Gère la récupération du filtre, la pagination et l'appel à getProviderAppointments.
 *
 * @param int $provider_id L'ID du prestataire connecté.
 * @return array Un tableau contenant toutes les variables nécessaires pour la vue.
 */
function setupProviderAppointmentsPageData(int $provider_id): array
{
    $pageTitle = "Mes Rendez-vous";


    $allowed_filters = ['upcoming', 'past', 'canceled', 'all'];
    $filter_status = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!$filter_status || !in_array($filter_status, $allowed_filters)) {
        $filter_status = 'upcoming';
    }


    $items_per_page = 15;
    $current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $offset = ($current_page - 1) * $items_per_page;


    $appointments_data = [];
    $total_appointments = 0;
    if ($provider_id > 0) {
        $appointments_data = getProviderAppointments($provider_id, $filter_status, $items_per_page, $offset);
        $total_appointments = $appointments_data['total'] ?? 0;
    }
    $appointments = $appointments_data['appointments'] ?? [];
    $total_pages = ceil($total_appointments / $items_per_page);


    return [
        'pageTitle' => $pageTitle,
        'filter_status' => $filter_status,
        'appointments' => $appointments,
        'total_appointments' => $total_appointments,
        'current_page' => $current_page,
        'total_pages' => $total_pages,


    ];
}
