<?php

require_once __DIR__ . '/../../../../includes/init.php';

/**
 * Récupère toutes les disponibilités et indisponibilités enregistrées pour un prestataire donné.
 *
 * @param int $provider_id L'ID du prestataire (personnes.id).
 * @return array La liste des disponibilités/indisponibilités (peut être vide).
 */
function getProviderAvailabilities(int $provider_id): array
{
    if ($provider_id <= 0) {
        return [];
    }

    $orderBy = "FIELD(type, 'recurrente', 'specifique', 'indisponible'), jour_semaine ASC, date_debut ASC, heure_debut ASC";
    return fetchAll(
        'prestataires_disponibilites',
        'prestataire_id = :provider_id',
        $orderBy,
        0,
        0,
        [':provider_id' => $provider_id]
    );
}

/**
 * Formate une entrée de disponibilité pour l'affichage.
 *
 * @param array $availability L'entrée de disponibilité depuis la BDD.
 * @return string Une description textuelle de la disponibilité.
 */
function formatAvailabilityForDisplay(array $availability): string
{
    $dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    $output = '';

    switch ($availability['type']) {
        case 'recurrente':
            $day = $dayNames[$availability['jour_semaine'] ?? 0] ?? 'Jour inconnu';
            $startTime = date('H:i', strtotime($availability['heure_debut'] ?? ''));
            $endTime = date('H:i', strtotime($availability['heure_fin'] ?? ''));
            $endDate = $availability['recurrence_fin'] ? ' jusqu\'au ' . date('d/m/Y', strtotime($availability['recurrence_fin'])) : ' (indéfiniment)';
            $output = "Récurrent: Chaque <strong>{$day}</strong> de <strong>{$startTime}</strong> à <strong>{$endTime}</strong>{$endDate}";
            break;

        case 'specifique':
            $startDate = date('d/m/Y', strtotime($availability['date_debut'] ?? ''));
            $endDate = date('d/m/Y', strtotime($availability['date_fin'] ?? $availability['date_debut']));
            $startTime = date('H:i', strtotime($availability['heure_debut'] ?? ''));
            $endTime = date('H:i', strtotime($availability['heure_fin'] ?? ''));
            $dateRange = ($startDate === $endDate) ? "Le <strong>{$startDate}</strong>" : "Du <strong>{$startDate}</strong> au <strong>{$endDate}</strong>";
            $timeRange = ($startTime && $endTime && $startTime !== $endTime) ? " de <strong>{$startTime}</strong> à <strong>{$endTime}</strong>" : ($startTime ? " à partir de <strong>{$startTime}</strong>" : "");
            $output = "Spécifique: Disponible {$dateRange}{$timeRange}";
            break;

        case 'indisponible':
            $startDate = date('d/m/Y', strtotime($availability['date_debut'] ?? ''));
            $endDate = date('d/m/Y', strtotime($availability['date_fin'] ?? $availability['date_debut']));
            $startTime = date('H:i', strtotime($availability['heure_debut'] ?? ''));
            $endTime = date('H:i', strtotime($availability['heure_fin'] ?? ''));
            $dateRange = ($startDate === $endDate) ? "Le <strong>{$startDate}</strong>" : "Du <strong>{$startDate}</strong> au <strong>{$endDate}</strong>";
            $timeInfo = ($startTime && $endTime && $startTime !== $endTime) ? " de {$startTime} à {$endTime}" : ($startTime ? " à partir de {$startTime}" : "");
            $output = "Indisponible: {$dateRange}{$timeInfo}";
            break;

        default:
            $output = "Type inconnu";
    }

    if (!empty($availability['notes'])) {
        $output .= ' <em class="text-muted">- ' . htmlspecialchars($availability['notes']) . '</em>';
    }

    return $output;
}

/**
 * Ajoute une nouvelle disponibilité ou indisponibilité pour un prestataire.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param array $data Données du formulaire.
 * @return bool True si l'ajout réussit, false sinon.
 */
function addProviderAvailability(int $provider_id, array $data): bool
{
    if ($provider_id <= 0 || empty($data['type'])) {
        flashMessage("Le type de disponibilité est obligatoire.", "danger");
        return false;
    }

    $dataToInsert = [
        'prestataire_id' => $provider_id,
        'type' => $data['type'],
        'notes' => trim($data['notes'] ?? '')
    ];

    switch ($data['type']) {
        case 'recurrente':
            if (!isset($data['jour_semaine'], $data['heure_debut'], $data['heure_fin'])) {
                flashMessage("Pour une récurrence, le jour et les heures de début/fin sont requis.", "danger");
                return false;
            }
            $dataToInsert['jour_semaine'] = (int)$data['jour_semaine'];
            $dataToInsert['heure_debut'] = date('H:i:s', strtotime($data['heure_debut']));
            $dataToInsert['heure_fin'] = date('H:i:s', strtotime($data['heure_fin']));
            $dataToInsert['recurrence_fin'] = !empty($data['recurrence_fin']) ? date('Y-m-d', strtotime($data['recurrence_fin'])) : null;
            break;

        case 'specifique':
        case 'indisponible':
            if (empty($data['date_debut'])) {
                flashMessage("La date de début est requise pour ce type.", "danger");
                return false;
            }
            $dataToInsert['date_debut'] = date('Y-m-d H:i:s', strtotime($data['date_debut'] . ' ' . ($data['heure_debut_specifique'] ?? '00:00:00')));
            $dataToInsert['date_fin'] = !empty($data['date_fin']) ? date('Y-m-d H:i:s', strtotime($data['date_fin'] . ' ' . ($data['heure_fin_specifique'] ?? '23:59:59'))) : $dataToInsert['date_debut'];
            break;

        default:
            flashMessage("Type de disponibilité inconnu.", "danger");
            return false;
    }

    $success = insertRow('prestataires_disponibilites', $dataToInsert);
    flashMessage($success ? "Disponibilité/Indisponibilité ajoutée avec succès." : "Erreur lors de l'enregistrement.", $success ? "success" : "danger");
    return $success;
}

/**
 * Gère la soumission du formulaire d'ajout de disponibilité/indisponibilité.
 *
 * @param int $provider_id L'ID du prestataire.
 * @return void Termine le script par une redirection.
 */
function handleAvailabilityAddRequest(int $provider_id): void
{
    verifyPostedCsrfToken();

    $data = $_POST;

    addProviderAvailability($provider_id, $data);

    redirectTo(WEBCLIENT_URL . '/modules/providers/availabilities.php');
    exit;
}

/**
 * Supprime une disponibilité ou indisponibilité spécifique pour un prestataire.
 *
 * @param int $availability_id L'ID de l'entrée à supprimer.
 * @param int $provider_id L'ID du prestataire propriétaire.
 * @return bool True si la suppression réussit, false sinon.
 */
function deleteProviderAvailability(int $availability_id, int $provider_id): bool
{
    if ($availability_id <= 0 || $provider_id <= 0) {
        flashMessage("ID invalide pour la suppression.", "danger");
        return false;
    }

    $availability = fetchOne('prestataires_disponibilites', 'id = :id AND prestataire_id = :provider_id', [':id' => $availability_id, ':provider_id' => $provider_id]);
    if (!$availability) {
        flashMessage("Entrée non trouvée ou accès refusé.", "warning");
        return false;
    }

    $rowsAffected = deleteRow('prestataires_disponibilites', 'id = :id', [':id' => $availability_id]);
    flashMessage($rowsAffected > 0 ? "Entrée supprimée avec succès." : "Erreur lors de la suppression.", $rowsAffected > 0 ? "success" : "danger");
    return $rowsAffected > 0;
}

/**
 * Récupère une disponibilité spécifique par son ID, en vérifiant l'appartenance au prestataire.
 *
 * @param int $availability_id L'ID de la disponibilité.
 * @param int $provider_id L'ID du prestataire.
 * @return array|null Les données de la disponibilité ou null si non trouvée ou accès refusé.
 */
function getProviderAvailabilityById(int $availability_id, int $provider_id): ?array
{
    if ($availability_id <= 0 || $provider_id <= 0) {
        return null;
    }
    return fetchOne('prestataires_disponibilites', 'id = :id AND prestataire_id = :provider_id', [
        ':id' => $availability_id,
        ':provider_id' => $provider_id
    ]);
}

/**
 * Met à jour une disponibilité ou indisponibilité existante pour un prestataire.
 *
 * @param int $availability_id L'ID de l'entrée à mettre à jour.
 * @param int $provider_id L'ID du prestataire.
 * @param array $data Données du formulaire.
 * @return bool True si la mise à jour réussit, false sinon.
 */
function updateProviderAvailability(int $availability_id, int $provider_id, array $data): bool
{
    if ($availability_id <= 0 || $provider_id <= 0 || empty($data['type'])) {
        flashMessage("Données invalides pour la mise à jour.", "danger");
        return false;
    }

    $existing = getProviderAvailabilityById($availability_id, $provider_id);
    if (!$existing) {
        flashMessage("Entrée non trouvée ou accès refusé pour la mise à jour.", "warning");
        return false;
    }

    $dataToUpdate = [
        'type' => $data['type'],
        'notes' => trim($data['notes'] ?? '')
    ];

    switch ($data['type']) {
        case 'recurrente':
            if (!isset($data['jour_semaine'], $data['heure_debut'], $data['heure_fin'])) {
                flashMessage("Pour une récurrence, le jour et les heures de début/fin sont requis.", "danger");
                return false;
            }
            $dataToUpdate['jour_semaine'] = (int)$data['jour_semaine'];
            $dataToUpdate['heure_debut'] = date('H:i:s', strtotime($data['heure_debut']));
            $dataToUpdate['heure_fin'] = date('H:i:s', strtotime($data['heure_fin']));
            $dataToUpdate['recurrence_fin'] = !empty($data['recurrence_fin']) ? date('Y-m-d', strtotime($data['recurrence_fin'])) : null;
            break;

        case 'specifique':
        case 'indisponible':
            if (empty($data['date_debut'])) {
                flashMessage("La date de début est requise pour ce type.", "danger");
                return false;
            }
            $dataToUpdate['date_debut'] = date('Y-m-d H:i:s', strtotime($data['date_debut'] . ' ' . ($data['heure_debut_specifique'] ?? '00:00:00')));
            $dataToUpdate['date_fin'] = !empty($data['date_fin']) ? date('Y-m-d H:i:s', strtotime($data['date_fin'] . ' ' . ($data['heure_fin_specifique'] ?? '23:59:59'))) : $dataToUpdate['date_debut'];
            break;

        default:
            flashMessage("Type de disponibilité inconnu.", "danger");
            return false;
    }

    $rowsAffected = updateRow('prestataires_disponibilites', $dataToUpdate, 'id = :id AND prestataire_id = :provider_id', [
        ':id' => $availability_id,
        ':provider_id' => $provider_id
    ]);

    flashMessage($rowsAffected !== false ? "Disponibilité mise à jour avec succès." : "Erreur lors de la mise à jour.", $rowsAffected !== false ? "success" : "danger");
    return $rowsAffected !== false;
}

/**
 * Gère la soumission du formulaire de mise à jour de disponibilité/indisponibilité.
 *
 * @param int $provider_id L'ID du prestataire.
 * @return void Termine le script par une redirection.
 */
function handleAvailabilityUpdateRequest(int $provider_id): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['update_availability'])) {
        flashMessage("Requête de mise à jour invalide.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/providers/availabilities.php');
        exit;
    }

    if (!verifyPostedCsrfToken()) {
        flashMessage("Jeton de sécurité invalide ou expiré pour la mise à jour.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/providers/availabilities.php');
        exit;
    }

    $availability_id = filter_input(INPUT_POST, 'availability_id', FILTER_VALIDATE_INT);
    if (!$availability_id) {
        flashMessage("ID de disponibilité invalide pour la mise à jour.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/providers/availabilities.php');
        exit;
    }

    updateProviderAvailability($availability_id, $provider_id, $_POST);
    redirectTo(WEBCLIENT_URL . '/modules/providers/availabilities.php');
    exit;
}

/**
 * Génère le HTML pour un calendrier mensuel simple.
 *
 * @param int $year L'année.
 * @param int $month Le mois (1-12).
 * @param array $days_data Données pour chaque jour (ex: [1 => ['class' => 'available', 'title' => 'Recurrent...'], ...]).
 * @return string Le HTML du calendrier.
 */
function generateCalendarHTML(int $year, int $month, array $days_data = []): string
{
    $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $firstDayOfMonth = date('N', mktime(0, 0, 0, $month, 1, $year));

    $calendar = '<table class="table table-bordered calendar-table">';
    $calendar .= '<thead><tr><th>Lun</th><th>Mar</th><th>Mer</th><th>Jeu</th><th>Ven</th><th>Sam</th><th>Dim</th></tr></thead>';
    $calendar .= '<tbody><tr>';

    if ($firstDayOfMonth > 1) {
        $calendar .= str_repeat('<td class="calendar-day-empty"></td>', $firstDayOfMonth - 1);
    }

    $currentDay = 1;
    $dayOfWeek = $firstDayOfMonth;

    while ($currentDay <= $daysInMonth) {
        if ($dayOfWeek == 1 && $currentDay > 1) {
            $calendar .= '</tr><tr>';
        }

        $cell_class = 'calendar-day';
        $cell_title = '';
        if (isset($days_data[$currentDay])) {
            $cell_class .= ' ' . ($days_data[$currentDay]['class'] ?? '');
            $cell_title = ' title="' . htmlspecialchars($days_data[$currentDay]['title'] ?? '') . '"';
        }

        if ($year == date('Y') && $month == date('n') && $currentDay == date('j')) {
            $cell_class .= ' calendar-day-today';
        }

        $calendar .= '<td class="' . $cell_class . '"' . $cell_title . '>';
        $calendar .= '<div class="day-number">' . $currentDay . '</div>';
        $calendar .= '</td>';

        if ($dayOfWeek == 7) {
            $calendar .= '</tr>';
            $dayOfWeek = 1;
        } else {
            $dayOfWeek++;
        }
        $currentDay++;
    }

    if ($dayOfWeek > 1 && $dayOfWeek <= 7) {
        $calendar .= str_repeat('<td class="calendar-day-empty"></td>', 7 - $dayOfWeek + 1);
        $calendar .= '</tr>';
    }

    $calendar .= '</tbody></table>';
    return $calendar;
}

/**
 * Prépare les données des jours pour le calendrier pour un mois donné.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param int $year L'année.
 * @param int $month Le mois (1-12).
 * @return array Tableau associatif [jour => ['class' => '...', 'title' => '...']].
 */
function getCalendarDaysData(int $provider_id, int $year, int $month): array
{
    $availabilities = getProviderAvailabilities($provider_id);
    $days_data = [];
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $month_start_ts = mktime(0, 0, 0, $month, 1, $year);
    $month_end_ts = mktime(23, 59, 59, $month, $daysInMonth, $year);

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $current_day_ts_start = mktime(0, 0, 0, $month, $day, $year);
        $current_day_ts_end = mktime(23, 59, 59, $month, $day, $year);
        $dbDayOfWeek = date('N', $current_day_ts_start) % 7;

        $day_status = [];
        $day_titles = [];

        foreach ($availabilities as $av) {
            $applies = false;
            switch ($av['type']) {
                case 'recurrente':
                    if ($av['jour_semaine'] == $dbDayOfWeek) {
                        $recurrence_end_ts = $av['recurrence_fin'] ? strtotime($av['recurrence_fin'] . ' 23:59:59') : PHP_INT_MAX;
                        if ($current_day_ts_start <= $recurrence_end_ts) {
                            $applies = true;
                            $day_titles[] = formatAvailabilityForDisplay($av);
                        }
                    }
                    break;
                case 'specifique':
                case 'indisponible':
                    $av_start_ts = strtotime($av['date_debut']);
                    $av_end_ts = $av['date_fin'] ? strtotime($av['date_fin']) : $av_start_ts;
                    if (date('H:i:s', $av_end_ts) == '00:00:00') {
                        $av_end_ts = strtotime(date('Y-m-d', $av_end_ts) . ' 23:59:59');
                    }
                    if ($current_day_ts_start <= $av_end_ts && $current_day_ts_end >= $av_start_ts) {
                        $applies = true;
                        $day_titles[] = formatAvailabilityForDisplay($av);
                    }
                    break;
            }
            if ($applies && !in_array($av['type'], $day_status)) {
                $day_status[] = $av['type'];
            }
        }

        if (!empty($day_status)) {
            $class = 'calendar-day-mixed';
            if (count($day_status) === 1) {
                $class = $day_status[0] === 'indisponible' ? 'calendar-day-unavailable' : 'calendar-day-available';
            } elseif (in_array('indisponible', $day_status)) {
                $class = 'calendar-day-mixed-unavailable';
            }

            $days_data[$day] = [
                'class' => $class,
                'title' => implode('\n', $day_titles)
            ];
        }
    }

    return $days_data;
}
