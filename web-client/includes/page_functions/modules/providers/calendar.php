<?php

require_once __DIR__ . '/../../../init.php';

/**
 * Récupère les événements (rendez-vous et interventions) pour un prestataire
 * pour un mois et une année spécifiques.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param int $year L'année désirée.
 * @param int $month Le mois désiré (1-12).
 * @return array Un tableau associatif où les clés sont les jours du mois (1-31)
 *               et les valeurs sont des tableaux contenant les événements de ce jour.
 *               Exemple: [ 15 => [event1, event2], 22 => [event3] ]
 */
function getProviderCalendarEventsForMonth(int $provider_id, int $year, int $month): array
{
    $events_by_day = [];
    if ($provider_id <= 0) {
        return $events_by_day;
    }


    $start_date_str = sprintf("%d-%02d-01 00:00:00", $year, $month);

    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $end_date_str = sprintf("%d-%02d-%02d 23:59:59", $year, $month, $days_in_month);

    $params_rdv = [
        ':provider_id' => $provider_id,
        ':start_date' => $start_date_str,
        ':end_date' => $end_date_str
    ];
    $params_creneaux = [
        ':provider_id' => $provider_id,
        ':start_date' => $start_date_str,
        ':end_date' => $end_date_str
    ];


    $sql_rdv = "SELECT 
                    rv.id, 
                    rv.date_rdv, 
                    rv.duree, 
                    rv.statut, 
                    p.nom AS prestation_nom,
                    per.prenom AS salarie_prenom, 
                    per.nom AS salarie_nom
                FROM rendez_vous rv
                JOIN prestations p ON rv.prestation_id = p.id
                JOIN personnes per ON rv.personne_id = per.id
                WHERE rv.praticien_id = :provider_id
                AND rv.statut IN ('confirme', 'planifie')
                AND rv.date_rdv BETWEEN :start_date AND :end_date
                ORDER BY rv.date_rdv ASC";

    $stmt_rdv = executeQuery($sql_rdv, $params_rdv);
    $appointments = $stmt_rdv ? $stmt_rdv->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($appointments as $rdv) {
        try {
            $start_dt = new DateTime($rdv['date_rdv']);
            $day = (int)$start_dt->format('j');
            $end_dt = clone $start_dt;
            if (!empty($rdv['duree']) && is_numeric($rdv['duree'])) {
                $end_dt->add(new DateInterval('PT' . intval($rdv['duree']) . 'M'));
            } else {
                $end_dt->add(new DateInterval('PT60M'));
            }

            $start_time = date('H:i', strtotime($rdv['heure_debut']));
            $end_time = date('H:i', strtotime($rdv['heure_fin']));
            $title = 'RDV: ' . ($rdv['prestation_nom'] . ' - ' . ($rdv['salarie_prenom'] ?? '') . ' ' . ($rdv['salarie_nom'] ?? ''));
            $color_class = ($rdv['statut'] == 'confirme') ? 'event-confirmed' : 'event-planned';

            if (!isset($events_by_day[$day])) {
                $events_by_day[$day] = [];
            }
            $events_by_day[$day][] = [
                'type' => 'rdv',
                'id' => 'rdv-' . $rdv['id'],
                'start_time' => $start_time,
                'end_time' => $end_time,
                'title' => $title,
                'class' => $color_class
            ];
        } catch (Exception $e) {
            error_log("Erreur de traitement de date pour RDV ID {" . $rdv['id'] . "}: " . $e->getMessage());
        }
    }
    $sql_creneaux = "SELECT 
                        cc.id, 
                        cc.start_time, 
                        cc.end_time, 
                        cc.is_booked,
                        p.nom AS prestation_nom,
                        p.type AS prestation_type,
                        s.nom AS site_nom
                     FROM consultation_creneaux cc
                     JOIN prestations p ON cc.prestation_id = p.id
                     LEFT JOIN sites s ON cc.site_id = s.id
                     WHERE cc.praticien_id = :provider_id
                     AND cc.start_time BETWEEN :start_date AND :end_date
                     ORDER BY cc.start_time ASC";

    $stmt_creneaux = executeQuery($sql_creneaux, $params_creneaux);
    $interventions = $stmt_creneaux ? $stmt_creneaux->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($interventions as $int) {
        try {
            $start_dt = new DateTime($int['start_time']);
            $day = (int)$start_dt->format('j');
            $end_dt = new DateTime($int['end_time']);

            $start_time = date('H:i', strtotime($int['heure_debut']));
            $end_time = date('H:i', strtotime($int['heure_fin']));
            $title = 'Interv: ' . $int['prestation_nom'];
            $title .= ($int['site_nom'] ? ' @ ' . $int['site_nom'] : '');
            $color_class = $int['is_booked'] ? 'event-booked' : 'event-free';

            if (!isset($events_by_day[$day])) {
                $events_by_day[$day] = [];
            }
            $events_by_day[$day][] = [
                'type' => 'intervention',
                'id' => 'intervention-' . $int['id'],
                'start_time' => $start_time,
                'end_time' => $end_time,
                'title' => $title,
                'class' => $color_class . ($int['is_booked'] ? ' event-intervention-booked' : ' event-intervention-free')
            ];
        } catch (Exception $e) {
            error_log("Erreur de traitement de date pour Intervention ID {" . $int['id'] . "}: " . $e->getMessage());
        }
    }


    foreach ($events_by_day as $day => &$day_events) {
        usort($day_events, function ($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });
    }
    unset($day_events);

    return $events_by_day;
}

/**
 * Génère le HTML pour un calendrier mensuel simple affichant les événements.
 *
 * @param int $year L'année à afficher.
 * @param int $month Le mois à afficher (1-12).
 * @param array $events_by_day Données d'événements groupées par jour (résultat de getProviderCalendarEventsForMonth).
 * @return string Le code HTML du calendrier.
 */
function generateEventCalendarHTML(int $year, int $month, array $events_by_day): string
{
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $first_day_of_month_weekday = date('w', mktime(0, 0, 0, $month, 1, $year));

    $first_day_of_month_weekday = ($first_day_of_month_weekday == 0) ? 6 : $first_day_of_month_weekday - 1;

    $calendar_html = '<table class="table table-bordered calendar-table">';
    $calendar_html .= '<thead><tr>';
    $days_of_week = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    foreach ($days_of_week as $day) {
        $calendar_html .= '<th class="text-center">' . $day . '</th>';
    }
    $calendar_html .= '</tr></thead><tbody><tr>';


    $calendar_html .= str_repeat('<td class="calendar-day-empty"></td>', $first_day_of_month_weekday);

    $current_day = 1;
    $current_weekday = $first_day_of_month_weekday;

    while ($current_day <= $days_in_month) {

        $today_class = (date('Y-m-d') == sprintf("%d-%02d-%02d", $year, $month, $current_day)) ? ' calendar-day-today' : '';

        $calendar_html .= '<td class="calendar-day' . $today_class . '">';
        $calendar_html .= '<div class="day-number">' . $current_day . '</div>';
        $calendar_html .= '<div class="day-events">';


        if (isset($events_by_day[$current_day])) {
            foreach ($events_by_day[$current_day] as $event) {
                $calendar_html .= '<div class="calendar-event ' . htmlspecialchars($event['class'] ?? '') . '" title="' . htmlspecialchars($event['title']) . '">';
                $calendar_html .= '<span class="event-time">' . htmlspecialchars($event['start_time']) . ':</span> ';
                $calendar_html .= htmlspecialchars($event['title']);

                $calendar_html .= '</div>';
            }
        }

        $calendar_html .= '</div></td>';


        if ($current_weekday == 6) {
            $calendar_html .= '</tr><tr>';
            $current_weekday = -1;
        }

        $current_day++;
        $current_weekday++;
    }


    if ($current_weekday != 0) {
        $calendar_html .= str_repeat('<td class="calendar-day-empty"></td>', 7 - $current_weekday);
    }

    $calendar_html .= '</tr></tbody></table>';

    return $calendar_html;
}
