<?php

require_once __DIR__ . '/../../../../includes/init.php';
require_once __DIR__ . '/appointments.php';

/**
 * Récupère les données de calendrier (rendez-vous) pour un salarié pour un mois et une année spécifiques.
 *
 * @param int $salarie_id L'ID du salarié.
 * @param int $year L'année désirée.
 * @param int $month Le mois désiré (1-12).
 * @return array Un tableau associatif où les clés sont les jours du mois (1-31)
 *               et les valeurs sont des tableaux contenant les événements de ce jour.
 */
function getSalarieCalendarData(int $salarie_id, int $year, int $month): array
{
    $calendar_data_by_day = [];
    if ($salarie_id <= 0) {
        return $calendar_data_by_day;
    }


    $start_date_month_str = sprintf("%d-%02d-01 00:00:00", $year, $month);
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $end_date_month_str = sprintf("%d-%02d-%02d 23:59:59", $year, $month, $days_in_month);





    $allAppointments = getSalarieAppointments($salarie_id, 'rdv.date_rdv ASC');

    foreach ($allAppointments as $rdv) {
        if (empty($rdv['date_rdv'])) {
            continue;
        }

        $rdv_timestamp = strtotime($rdv['date_rdv']);
        if ($rdv_timestamp === false) {
            error_log("Date invalide pour RDV ID {$rdv['id']}: {$rdv['date_rdv']}");
            continue;
        }


        if ($rdv_timestamp >= strtotime($start_date_month_str) && $rdv_timestamp <= strtotime($end_date_month_str)) {
            $day = (int)date('j', $rdv_timestamp);

            if (!isset($calendar_data_by_day[$day])) {
                $calendar_data_by_day[$day] = [];
            }

            $calendar_data_by_day[$day][] = [
                'id' => $rdv['id'],
                'start_time' => date('H:i', $rdv_timestamp),

                'title' => htmlspecialchars($rdv['prestation_nom'] ?? 'Rendez-vous'),
                'status' => $rdv['statut'] ?? 'inconnu',
                'type' => 'appointment',
                'view_url' => WEBCLIENT_URL . '/modules/employees/appointments.php?action=view&id=' . $rdv['id']

            ];
        }
    }








    return $calendar_data_by_day;
}

/**
 * Génère le HTML pour un calendrier mensuel affichant les rendez-vous d'un salarié.
 *
 * @param int $year L'année à afficher.
 * @param int $month Le mois à afficher (1-12).
 * @param array $calendar_data Données des rendez-vous groupées par jour (résultat de getSalarieCalendarData).
 * @param string $base_calendar_url L'URL de base pour la navigation du calendrier (ex: /modules/employees/calendar.php).
 * @return string Le code HTML du calendrier.
 */
function generateSalarieCalendarHTML(int $year, int $month, array $calendar_data, string $base_calendar_url): string
{
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $first_day_of_month_weekday = date('w', mktime(0, 0, 0, $month, 1, $year));

    $first_day_of_month_weekday = ($first_day_of_month_weekday == 0) ? 6 : $first_day_of_month_weekday - 1;

    $calendar_html = '<div class="calendar-navigation mb-3 d-flex justify-content-between align-items-center">';
    $prev_month_ts = mktime(0, 0, 0, $month - 1, 1, $year);
    $next_month_ts = mktime(0, 0, 0, $month + 1, 1, $year);
    $calendar_html .= '<a href="' . htmlspecialchars($base_calendar_url) . '?year=' . date('Y', $prev_month_ts) . '&month=' . date('n', $prev_month_ts) . '" class="btn btn-sm btn-outline-secondary">&lt; Mois Précédent</a>';

    $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, null, IntlDateFormatter::GREGORIAN, 'MMMM yyyy');
    $calendar_html .= '<h5 class="mb-0">' . htmlentities(ucfirst($formatter->format(mktime(0, 0, 0, $month, 1, $year)))) . '</h5>';

    $calendar_html .= '<a href="' . htmlspecialchars($base_calendar_url) . '?year=' . date('Y', $next_month_ts) . '&month=' . date('n', $next_month_ts) . '" class="btn btn-sm btn-outline-secondary">Mois Suivant &gt;</a>';
    $calendar_html .= '</div>';

    $calendar_html .= '<table class="table table-bordered calendar-table">';
    $calendar_html .= '<thead><tr>';
    $days_of_week = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    foreach ($days_of_week as $day_name) {
        $calendar_html .= '<th class="text-center calendar-header-cell">' . $day_name . '</th>';
    }
    $calendar_html .= '</tr></thead><tbody><tr>';


    $calendar_html .= str_repeat('<td class="calendar-day calendar-day-empty"></td>', $first_day_of_month_weekday);

    $current_day_in_week = $first_day_of_month_weekday;
    for ($day_num = 1; $day_num <= $days_in_month; $day_num++) {
        if ($current_day_in_week == 7) {
            $calendar_html .= '</tr><tr>';
            $current_day_in_week = 0;
        }

        $cell_classes = ['calendar-day'];
        if (date('Y-m-d') == sprintf("%d-%02d-%02d", $year, $month, $day_num)) {
            $cell_classes[] = 'calendar-day-today';
        }



        if (isset($calendar_data[$day_num]) && !empty($calendar_data[$day_num])) {
            $cell_classes[] = 'calendar-day-has-appointments';
        }

        $calendar_html .= '<td class="' . implode(' ', $cell_classes) . '">';
        $calendar_html .= '<div class="day-number">' . $day_num . '</div>';
        $calendar_html .= '<div class="day-events employee-day-events">';

        if (isset($calendar_data[$day_num])) {
            foreach ($calendar_data[$day_num] as $event) {

                $event_class = 'event-appointment-' . htmlspecialchars(strtolower($event['status'] ?? 'default'));
                $calendar_html .= '<div class="calendar-event ' . $event_class . '" title="' . htmlspecialchars($event['title']) . ' (' . htmlspecialchars($event['status']) . ')">';
                $calendar_html .= '<a href="' . htmlspecialchars($event['view_url']) . '" class="event-link">';
                $calendar_html .= '<span class="event-time">' . htmlspecialchars($event['start_time']) . ':</span> ';
                $calendar_html .= htmlspecialchars($event['title']);
                $calendar_html .= '</a></div>';
            }
        }
        $calendar_html .= '</div></td>';
        $current_day_in_week++;
    }


    if ($current_day_in_week != 0 && $current_day_in_week < 7) {
        $calendar_html .= str_repeat('<td class="calendar-day calendar-day-empty"></td>', 7 - $current_day_in_week);
    }

    $calendar_html .= '</tr></tbody></table>';

    return $calendar_html;
}

/**
 * Prépare toutes les données nécessaires pour la page du calendrier des salariés.
 *
 * @return array Un tableau contenant les données pour la vue (pageTitle, calendar_html, year, month).
 */
function setupEmployeeCalendarPageData(): array
{
    requireRole(ROLE_SALARIE);
    $salarie_id = $_SESSION['user_id'] ?? 0;

    if ($salarie_id <= 0) {

        flashMessage("Impossible d'identifier votre compte pour afficher le calendrier.", "danger");
        redirectTo(WEBCLIENT_URL . '/auth/login.php');
        exit;
    }


    $current_year = date('Y');
    $current_month = date('n');

    $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT, [
        'options' => ['default' => $current_year, 'min_range' => 2000, 'max_range' => 2100]
    ]);
    $month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT, [
        'options' => ['default' => $current_month, 'min_range' => 1, 'max_range' => 12]
    ]);

    $pageTitle = "Mon Planning";


    $calendar_events_data = getSalarieCalendarData($salarie_id, $year, $month);


    $base_calendar_url = WEBCLIENT_URL . '/modules/employees/calendar.php';
    $calendar_html = generateSalarieCalendarHTML($year, $month, $calendar_events_data, $base_calendar_url);

    return [
        'pageTitle' => $pageTitle,
        'calendar_html' => $calendar_html,
        'year' => $year,
        'month' => $month
    ];
}
