<?php

require_once __DIR__ . '/../../../../includes/init.php';

function getSalarieAppointments(int $salarie_id, string $orderBy = 'rdv.date_rdv DESC'): array
{
    if ($salarie_id <= 0) return [];

    $sql = "SELECT rdv.id, rdv.date_rdv, rdv.statut, rdv.type_rdv, rdv.lieu,
                   pres.nom as prestation_nom,
                   CONCAT(prat.prenom, ' ', prat.nom) as praticien_nom
            FROM " . TABLE_APPOINTMENTS . " rdv
            LEFT JOIN " . TABLE_PRESTATIONS . " pres ON rdv.prestation_id = pres.id
            LEFT JOIN " . TABLE_USERS . " prat ON rdv.praticien_id = prat.id AND prat.role_id = :role_prestataire
            WHERE rdv.personne_id = :salarie_id
            ORDER BY " . $orderBy;

    return executeQuery($sql, [
        ':salarie_id' => $salarie_id,
        ':role_prestataire' => ROLE_PRESTATAIRE
    ])->fetchAll();
}

function getAvailableServicesForBooking(int $page = 1, int $perPage = 3): array
{
    return paginateResults(
        TABLE_PRESTATIONS,
        $page,
        $perPage,
        "type IN ('consultation', 'atelier')",
        'nom ASC'
    );
}

function getPrestationDetails(int $prestation_id): array|false
{
    return $prestation_id > 0 ? fetchOne(TABLE_PRESTATIONS, 'id = :id', [':id' => $prestation_id]) : false;
}

function getAvailableSlotsForService(int $service_id, string $startDate, string $endDate): array
{
    if ($service_id <= 0) return [];

    $sql = "SELECT
                cc.id, cc.start_time, cc.end_time, cc.praticien_id,
                CONCAT(p.prenom, ' ', p.nom) as praticien_nom
            FROM consultation_creneaux cc
            LEFT JOIN " . TABLE_USERS . " p ON cc.praticien_id = p.id AND p.role_id = :role_prestataire
            WHERE cc.prestation_id = :service_id
              AND cc.is_booked = 0
              AND cc.start_time >= :start_date
              AND cc.start_time <= :end_date
            ORDER BY cc.start_time ASC";

    return executeQuery($sql, [
        ':service_id' => $service_id,
        ':start_date' => date('Y-m-d 00:00:00', strtotime($startDate)),
        ':end_date' => date('Y-m-d 23:59:59', strtotime($endDate)),
        ':role_prestataire' => ROLE_PRESTATAIRE
    ])->fetchAll();
}

function getAppointmentDetailsForEmployee(int $salarie_id, int $rdv_id): array|false
{
    if ($salarie_id <= 0 || $rdv_id <= 0) {
        return false;
    }

    $sql = "SELECT rdv.*,
                   pres.nom as prestation_nom, pres.description as prestation_description,
                   CONCAT(prat.prenom, ' ', prat.nom) as praticien_nom, prat.email as praticien_email, prat.telephone as praticien_tel,
                   s.nom as site_nom, CONCAT(s.adresse, ', ', s.code_postal, ' ', s.ville) as site_adresse
            FROM " . TABLE_APPOINTMENTS . " rdv
            LEFT JOIN " . TABLE_PRESTATIONS . " pres ON rdv.prestation_id = pres.id
            LEFT JOIN " . TABLE_USERS . " prat ON rdv.praticien_id = prat.id AND prat.role_id = :role_prestataire
            LEFT JOIN consultation_creneaux cc ON rdv.consultation_creneau_id = cc.id
            LEFT JOIN sites s ON cc.site_id = s.id
            WHERE rdv.id = :rdv_id AND rdv.personne_id = :salarie_id
            LIMIT 1";

    $params = [
        ':rdv_id' => $rdv_id,
        ':salarie_id' => $salarie_id,
        ':role_prestataire' => ROLE_PRESTATAIRE
    ];

    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

function bookAppointmentSlot(int $salarie_id, int $slot_id, int $service_id_confirm): bool
{
    if ($salarie_id <= 0 || $slot_id <= 0 || $service_id_confirm <= 0) {
        error_log("[Booking Error] Invalid IDs provided: Salarie: $salarie_id, Slot: $slot_id, Service: $service_id_confirm");
        return false;
    }

    $pdo = getDbConnection();

    $pdo->beginTransaction();

    $sqlCheckSlot = "SELECT * FROM consultation_creneaux WHERE id = :slot_id AND prestation_id = :service_id FOR UPDATE";
    $slot = executeQuery($sqlCheckSlot, [':slot_id' => $slot_id, ':service_id' => $service_id_confirm])->fetch();

    if (!$slot) {
        flashMessage("Le créneau sélectionné n'existe pas ou ne correspond pas à la prestation choisie.", "warning");
        $pdo->rollBack();
        return false;
    }

    if ($slot['is_booked']) {
        flashMessage("Ce créneau vient d'être réservé par quelqu'un d'autre.", "warning");
        $pdo->rollBack();
        return false;
    }

    $prestation = getPrestationDetails($service_id_confirm);
    if (!$prestation) {
        flashMessage("Impossible de récupérer les détails de la prestation.", "danger");
        $pdo->rollBack();
        return false;
    }

    $duree = $prestation['duree'] ?? 60;
    $type_rdv = isset($prestation['type']) && in_array($prestation['type'], APPOINTMENT_TYPES) ? $prestation['type'] : 'consultation';

    $updatedRows = updateRow(
        'consultation_creneaux',
        ['is_booked' => 1],
        'id = :slot_id AND is_booked = 0',
        [':slot_id' => $slot_id]
    );

    if ($updatedRows === 0) {
        flashMessage("Ce créneau vient d'être réservé à l'instant par quelqu'un d'autre.", "warning");
        $pdo->rollBack();
        return false;
    }

    $rdvData = [
        'personne_id' => $salarie_id,
        'prestation_id' => $slot['prestation_id'],
        'praticien_id' => $slot['praticien_id'],
        'date_rdv' => $slot['start_time'],
        'duree' => $duree,
        'lieu' => $slot['site_id'] ? 'Site ID: ' . $slot['site_id'] : null,
        'type_rdv' => $type_rdv,
        'statut' => 'confirme',
        'notes' => 'Réservé via plateforme web.',
        'consultation_creneau_id' => $slot_id
    ];

    if (!insertRow(TABLE_APPOINTMENTS, $rdvData)) {
        flashMessage("Une erreur est survenue lors de la finalisation de la réservation.", "danger");
        $pdo->rollBack();
        return false;
    }

    $pdo->commit();

    createNotification(
        $salarie_id,
        "Confirmation de rendez-vous",
        "Votre RDV pour '" . htmlspecialchars($prestation['nom']) . "' le " . htmlspecialchars(formatDate($slot['start_time'], 'd/m/Y H:i')) . " est confirmé.",
        "success",
        WEBCLIENT_URL . '/modules/employees/appointments.php'
    );

    return true;
}

function cancelEmployeeAppointment(int $salarie_id, int $rdv_id): bool
{
    if ($salarie_id <= 0 || $rdv_id <= 0) {
        error_log("Cancel failed at: invalid IDs");
        return false;
    }

    $pdo = getDbConnection();

    $pdo->beginTransaction();

    $sqlGetRdv = "SELECT * FROM " . TABLE_APPOINTMENTS . " WHERE id = :rdv_id AND personne_id = :salarie_id FOR UPDATE";
    $stmt = executeQuery($sqlGetRdv, [':rdv_id' => $rdv_id, ':salarie_id' => $salarie_id]);
    $rdv = $stmt->fetch();

    if (!$rdv) {
        error_log("Cancel failed at: rdv not found or no permission for rdv_id: " . $rdv_id . " salarie_id: " . $salarie_id);
        flashMessage("Rendez-vous non trouvé ou vous n'avez pas la permission de l'annuler.", "warning");
        $pdo->rollBack();
        return false;
    }

    if (!in_array($rdv['statut'], ['planifie', 'confirme'])) {
        error_log("Cancel failed at: bad status ('" . $rdv['statut'] . "') for rdv_id: " . $rdv_id);
        flashMessage("Ce rendez-vous ne peut plus être annulé (statut: " . htmlspecialchars($rdv['statut']) . ").", "warning");
        $pdo->rollBack();
        return false;
    }

    if (strtotime($rdv['date_rdv']) <= time()) {
        error_log("Cancel failed at: date past ('" . $rdv['date_rdv'] . "') for rdv_id: " . $rdv_id);
        flashMessage("Impossible d'annuler un rendez-vous déjà passé.", "warning");
        $pdo->rollBack();
        return false;
    }

    if (updateRow(TABLE_APPOINTMENTS, ['statut' => 'annule'], 'id = :rdv_id', [':rdv_id' => $rdv_id]) === 0) {
        error_log("Cancel failed at: updateRow for appointment status returned 0 for rdv_id: " . $rdv_id);
        flashMessage("Erreur lors de la mise à jour du statut du rendez-vous.", "danger");
        $pdo->rollBack();
        return false;
    }

    if (!empty($rdv['consultation_creneau_id'])) {
        error_log("Attempting to free consultation slot ID: " . $rdv['consultation_creneau_id'] . " for cancelled RDV ID: " . $rdv_id);
        $slotUpdateResult = updateRow(
            'consultation_creneaux',
            ['is_booked' => 0],
            'id = :creneau_id',
            [':creneau_id' => $rdv['consultation_creneau_id']]
        );

        if ($slotUpdateResult === 0) {
            error_log("Warning: updateRow for consultation_creneaux returned 0 for creneau_id: " . $rdv['consultation_creneau_id']);
        }
    }

    error_log("Attempting to commit transaction for cancelling RDV ID: " . $rdv_id);
    $pdo->commit();
    error_log("Transaction committed for cancelling RDV ID: " . $rdv_id);

    $prestation = getPrestationDetails($rdv['prestation_id']);
    $prestationNom = $prestation ? $prestation['nom'] : 'inconnue';
    error_log("Attempting to create notification for cancelled RDV ID: " . $rdv_id);
    createNotification(
        $salarie_id,
        "Annulation de rendez-vous",
        "Votre RDV pour '" . htmlspecialchars($prestationNom) . "' le " . htmlspecialchars(formatDate($rdv['date_rdv'], 'd/m/Y H:i')) . " a été annulé.",
        "info"
    );
    error_log("Notification created successfully for cancelled RDV ID: " . $rdv_id);

    return true;
}

/**
 * Gère la logique POST pour la réservation d'un créneau.
 *
 * @param int $salarie_id ID de l'employé.
 * @param array $postData Données du formulaire POST.
 */
function _handleBookingPostAction(int $salarie_id, array $postData): void
{
    $csrf_token_booking = $postData['csrf_token'] ?? '';
    if (!validateToken($csrf_token_booking)) {
        flashMessage("Jeton de sécurité invalide ou expiré pour la réservation. Veuillez réessayer.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/employees/appointments.php');
        exit;
    }

    $slot_id = filter_var($postData['slot_id'] ?? null, FILTER_VALIDATE_INT);
    $service_id_confirm = filter_var($postData['service_id'] ?? null, FILTER_VALIDATE_INT);

    if ($slot_id && $service_id_confirm) {
        $bookingSuccess = bookAppointmentSlot($salarie_id, $slot_id, $service_id_confirm);
        if ($bookingSuccess) {
            flashMessage("Votre rendez-vous a bien été réservé !", "success");
        } elseif (empty($_SESSION['flash_messages'])) {
            flashMessage("Erreur lors de la réservation. Le créneau n'est peut-être plus disponible ou une erreur technique est survenue.", "danger");
        }
    } else {
        flashMessage("Données de réservation invalides.", "warning");
    }

    redirectTo(WEBCLIENT_URL . '/modules/employees/appointments.php');
    exit;
}

/**
 * Gère la logique POST pour l'annulation d'un rendez-vous.
 *
 * @param int $salarie_id ID de l'employé.
 * @param array $postData Données du formulaire POST.
 */
function _handleCancelPostAction(int $salarie_id, array $postData): void
{
    $csrf_token_cancel = $postData['csrf_token'] ?? '';
    if (!validateToken($csrf_token_cancel)) {
        flashMessage("Erreur de sécurité (jeton invalide). Veuillez réessayer.", "danger");

        $redirectUrl = WEBCLIENT_URL . '/modules/employees/appointments.php';
        $current_filter_on_fail = $postData['filter'] ?? $_GET['filter'] ?? null;
        if ($current_filter_on_fail && in_array($current_filter_on_fail, ['all', 'upcoming', 'past', 'cancelled'])) {
            $redirectUrl .= '?filter=' . urlencode($current_filter_on_fail);
        }
        redirectTo($redirectUrl);
        exit;
    }

    $rdv_id_to_cancel = filter_var($postData['id'] ?? null, FILTER_VALIDATE_INT);
    $current_filter = filter_var($postData['filter'] ?? null, FILTER_SANITIZE_SPECIAL_CHARS);

    if ($rdv_id_to_cancel) {
        $cancelSuccess = cancelEmployeeAppointment($salarie_id, $rdv_id_to_cancel);
        if ($cancelSuccess) {
            flashMessage("Votre rendez-vous a bien été annulé.", "success");
        } elseif (empty($_SESSION['flash_messages'])) {
            flashMessage("L'annulation du rendez-vous a échoué pour une raison inconnue.", "danger");
        }
    } else {
        flashMessage("ID de rendez-vous invalide pour l'annulation.", "warning");
    }


    $redirectUrl = WEBCLIENT_URL . '/modules/employees/appointments.php';
    if (!empty($current_filter) && in_array($current_filter, ['all', 'upcoming', 'past', 'cancelled'])) {
        $redirectUrl .= '?filter=' . urlencode($current_filter);
    }
    redirectTo($redirectUrl);
    exit;
}

/**
 * Gère les actions POST et GET pour la page des rendez-vous.
 * Refactorisé pour utiliser des fonctions d'aide pour les actions POST.
 *
 * @param int $salarie_id ID de l'employé.
 */
function handleAppointmentPostAndGetActions(int $salarie_id)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postData = $_POST;


        if (isset($postData['slot_id'])) {
            _handleBookingPostAction($salarie_id, $postData);
        } elseif (isset($postData['action']) && $postData['action'] === 'cancel') {
            _handleCancelPostAction($salarie_id, $postData);
        } else {
            flashMessage("Action POST non reconnue.", "warning");
            redirectTo(WEBCLIENT_URL . '/modules/employees/appointments.php');
            exit;
        }
    }
}

function prepareAppointmentViewData(int $salarie_id): array
{
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
    $filter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_SPECIAL_CHARS);
    $rdv_id_view = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $service_id_book = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);
    $currentPage_book = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);

    $viewData = [
        'pageTitle' => "Mes Rendez-vous",
        'action' => 'list',
        'filter' => in_array($filter, ['all', 'upcoming', 'past']) ? $filter : 'upcoming',
        'upcomingAppointments' => [],
        'pastOrCancelledAppointments' => [],
        'appointmentDetails' => null,
        'bookingStep' => 'show_services',
        'availableServices' => [],
        'servicePagination' => [
            'items' => [],
            'currentPage' => 1,
            'totalPages' => 0,
            'totalItems' => 0,
            'perPage' => 3
        ],
        'selectedService' => null,
        'availableSlots' => [],
        'service_id' => null,
    ];

    if ($action === 'view' && $rdv_id_view > 0) {
        $viewData['action'] = 'view';
        $viewData['appointmentDetails'] = getAppointmentDetailsForEmployee($salarie_id, $rdv_id_view);
        if ($viewData['appointmentDetails']) {
            $viewData['pageTitle'] = "Détails du Rendez-vous #" . htmlspecialchars($viewData['appointmentDetails']['id']);
        } else {
            flashMessage("Impossible de voir les détails de ce rendez-vous.", "warning");
            $viewData['action'] = 'list';
        }
        return $viewData;
    }

    $allAppointments = getSalarieAppointments($salarie_id);
    foreach ($allAppointments as $rdv) {
        $isUpcoming = strtotime($rdv['date_rdv']) > time();

        if ($isUpcoming && $rdv['statut'] !== 'annule') {
            $viewData['upcomingAppointments'][] = $rdv;
        } else {
            $viewData['pastOrCancelledAppointments'][] = $rdv;
        }
    }

    usort($viewData['pastOrCancelledAppointments'], fn($a, $b) => strtotime($b['date_rdv']) <=> strtotime($a['date_rdv']));
    usort($viewData['upcomingAppointments'], fn($a, $b) => strtotime($a['date_rdv']) <=> strtotime($b['date_rdv']));

    if ($action === 'select_slot' && $service_id_book > 0) {
        $viewData['selectedService'] = getPrestationDetails($service_id_book);
        if ($viewData['selectedService']) {
            $viewData['bookingStep'] = 'show_slots';
            $viewData['pageTitle'] = "Choisir un Créneau pour " . htmlspecialchars($viewData['selectedService']['nom']);
            $viewData['availableSlots'] = getAvailableSlotsForService(
                $service_id_book,
                date('Y-m-d'),
                date('Y-m-d', strtotime('+4 weeks'))
            );
            $viewData['service_id'] = $service_id_book;
        } else {
            flashMessage("Prestation sélectionnée invalide.", "warning");
            $viewData['bookingStep'] = 'show_services';
        }
    }

    if ($viewData['bookingStep'] === 'show_services') {
        $viewData['pageTitle'] = "Prendre un rendez-vous";
        $viewData['servicePagination'] = getAvailableServicesForBooking($currentPage_book, 6);
        $viewData['availableServices'] = is_array($viewData['servicePagination']) && isset($viewData['servicePagination']['items']) && is_array($viewData['servicePagination']['items'])
            ? $viewData['servicePagination']['items']
            : [];
    }

    return $viewData;
}

/**
 * Prépare les données pour la vue liste des rendez-vous.
 */
function _getAppointmentListData(int $salarie_id, string $filter): array
{
    $orderBy = 'rdv.date_rdv ASC';
    if (in_array($filter, ['past', 'cancelled', 'all'])) {
        $orderBy = 'rdv.date_rdv DESC';
    }
    $allAppointments = getSalarieAppointments($salarie_id, $orderBy);

    $upcoming = [];
    $cancelled = [];
    $past_completed = [];
    foreach ($allAppointments as $rdv) {
        if ($rdv['statut'] === 'annule') {
            $cancelled[] = $rdv;
        } elseif (strtotime($rdv['date_rdv']) >= time() && !in_array($rdv['statut'], ['annule', 'termine', 'no_show'])) {
            $upcoming[] = $rdv;
        } else {
            $past_completed[] = $rdv;
        }
    }
    usort($upcoming, fn($a, $b) => strtotime($a['date_rdv']) <=> strtotime($b['date_rdv']));

    
    return [
        'pageTitle' => "Mes Rendez-vous",
        'action' => 'list',
        'filter' => $filter,
        'upcomingAppointments' => $upcoming,
        'cancelledAppointments' => $cancelled,
        'pastCompletedAppointments' => $past_completed,
        'bookingStep' => null,
        'availableServices' => [],
        'servicePagination' => null,
        'selectedService' => null,
        'availableSlots' => [],
        'service_id' => null,
        'appointmentDetails' => null,
    ];
}

/**
 * Prépare les données pour la vue détail d'un rendez-vous.
 */
function _getAppointmentDetailData(int $salarie_id, int $rdv_id): ?array
{
    $details = getAppointmentDetailsForEmployee($salarie_id, $rdv_id);
    if (!$details) {
        flashMessage("Impossible de voir les détails de ce rendez-vous.", "warning");
        return null;
    }
    
    return [
        'pageTitle' => "Détails du Rendez-vous #" . htmlspecialchars($details['id']),
        'action' => 'view',
        'appointmentDetails' => $details,
        'bookingStep' => null,
        'filter' => 'upcoming', 
        'upcomingAppointments' => [],
        'cancelledAppointments' => [],
        'pastCompletedAppointments' => [],
        'availableServices' => [],
        'servicePagination' => null,
        'selectedService' => null,
        'availableSlots' => [],
        'service_id' => null,
    ];
}

/**
 * Prépare les données pour la vue de sélection des services disponibles.
 */
function _getShowServicesData(int $page): array
{
    $servicesData = getAvailableServicesForBooking($page, 6);
    
    return [
        'pageTitle' => "Prendre un rendez-vous : Choisir une prestation",
        'action' => 'list',
        'bookingStep' => 'show_services',
        'availableServices' => $servicesData['items'] ?? [],
        'servicePagination' => $servicesData,
        'filter' => 'upcoming',
        'upcomingAppointments' => [],
        'cancelledAppointments' => [],
        'pastCompletedAppointments' => [],
        'appointmentDetails' => null,
        'selectedService' => null,
        'availableSlots' => [],
        'service_id' => null,
    ];
}

/**
 * Prépare les données pour la vue de sélection des créneaux pour un service.
 */
function _getShowSlotsData(int $service_id): ?array
{
    $selectedService = getPrestationDetails($service_id);
    if (!$selectedService) {
        flashMessage("Prestation non trouvée pour la sélection de créneau.", "warning");
        return null;
    }

    $pageTitle = "Choisir un créneau pour : " . htmlspecialchars($selectedService['nom']);
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+4 weeks'));
    $availableSlots = getAvailableSlotsForService($service_id, $startDate, $endDate);

    
    return [
        'pageTitle' => $pageTitle,
        'action' => 'select_slot',
        'bookingStep' => 'show_slots',
        'selectedService' => $selectedService,
        'availableSlots' => $availableSlots,
        'service_id' => $service_id,
        'filter' => 'upcoming',
        'upcomingAppointments' => [],
        'cancelledAppointments' => [],
        'pastCompletedAppointments' => [],
        'appointmentDetails' => null,
        'availableServices' => [],
        'servicePagination' => null,
    ];
}

/**
 * Configure les données nécessaires pour la page des rendez-vous.
 * Refactorisé pour utiliser des fonctions d'aide pour chaque état de la vue.
 * IMPORTANT: Appeler handleAppointmentPostAndGetActions() AVANT cette fonction.
 *
 * @return array Un tableau contenant les données pour la vue.
 */
function setupAppointmentsPage(): array
{
    requireRole(ROLE_SALARIE);
    $salarie_id = $_SESSION['user_id'] ?? 0;
    if ($salarie_id <= 0) {
        flashMessage("Erreur critique: ID Salarié non trouvé en session.", "danger");
        redirectTo(WEBCLIENT_URL . '/auth/login.php');
        exit;
    }

    
    $getId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $getAction = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
    $getBookingStep = filter_input(INPUT_GET, 'bookingStep', FILTER_SANITIZE_SPECIAL_CHARS);
    $getServiceId = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);
    $getPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $requestedFilter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_SPECIAL_CHARS);
    $validFilters = ['upcoming', 'past', 'cancelled', 'all'];
    $filter = (in_array($requestedFilter, $validFilters, true)) ? $requestedFilter : 'upcoming';

    $viewData = [];
    $stateDetermined = false;

    
    if ($getAction === 'view' && $getId > 0) {
        $viewData = _getAppointmentDetailData($salarie_id, $getId);
        if ($viewData === null) {
            $viewData = _getAppointmentListData($salarie_id, $filter);
        } else {
            $viewData['filter'] = $filter;
        }
        $stateDetermined = true;
    } elseif ($getAction === 'select_slot' && $getServiceId > 0) {
        $viewData = _getShowSlotsData($getServiceId);
        if ($viewData === null) {
            $viewData = _getShowServicesData($getPage);
        }
        $stateDetermined = true;
    } elseif ($getBookingStep === 'show_services') {
        $viewData = _getShowServicesData($getPage);
        $stateDetermined = true;
    }

    
    if (!$stateDetermined) {
        $viewData = _getAppointmentListData($salarie_id, $filter);
    }

    
    $viewData['csrf_token'] = generateToken();

    return $viewData;
}

/**
 * Vérifie si un créneau horaire est disponible pour une prestation donnée.
 * Fonction déplacée depuis shared/functions.php
 *
 * @param string $dateHeure Date et heure du début souhaité (format Y-m-d H:i:s ou compatible strtotime).
 * @param int $duree Durée de la prestation en minutes.
 * @param int $prestationId ID de la prestation.
 * @return bool True si le créneau est disponible, false sinon.
 */
function isTimeSlotAvailable($dateHeure, $duree, $prestationId): bool
{

    if (empty($dateHeure) || !is_numeric($duree) || $duree <= 0 || !is_numeric($prestationId) || $prestationId <= 0) {

        return false;
    }


    $debutTimestamp = strtotime($dateHeure);
    if ($debutTimestamp === false) {

        return false;
    }
    $finTimestamp = $debutTimestamp + ($duree * 60);
    $finRdv = date('Y-m-d H:i:s', $finTimestamp);
    $debutRdv = date('Y-m-d H:i:s', $debutTimestamp);


    $sql = "SELECT COUNT(id) FROM rendez_vous 
                WHERE prestation_id = :prestation_id
                AND statut NOT IN ('annule', 'termine', 'refuse') 
                AND (
                    (:debut_rdv >= date_rdv AND :debut_rdv < DATE_ADD(date_rdv, INTERVAL duree MINUTE))
                    OR 
                    (:fin_rdv > date_rdv AND :fin_rdv <= DATE_ADD(date_rdv, INTERVAL duree MINUTE))
                    OR
                    (:debut_rdv <= date_rdv AND :fin_rdv >= DATE_ADD(date_rdv, INTERVAL duree MINUTE))
                )";

    $params = [
        ':prestation_id' => $prestationId,
        ':debut_rdv' => $debutRdv,
        ':fin_rdv' => $finRdv
    ];


    $stmt = executeQuery($sql, $params);
    $conflictCount = $stmt->fetchColumn();

    return $conflictCount == 0;
}
