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
            ORDER BY {$orderBy}";
    
    return executeQuery($sql, [
        ':salarie_id' => $salarie_id, 
        ':role_prestataire' => ROLE_PRESTATAIRE
    ])->fetchAll();
}

function getAppointmentDetailsForEmployee(int $salarie_id, int $rdv_id): array|false
{
    if ($salarie_id <= 0 || $rdv_id <= 0) return false;
    
    $sql = "SELECT rdv.id, rdv.date_rdv, rdv.statut, rdv.type_rdv, rdv.lieu,
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
    
    return executeQuery($sql, [
        ':rdv_id' => $rdv_id, 
        ':salarie_id' => $salarie_id, 
        ':role_prestataire' => ROLE_PRESTATAIRE
    ])->fetch();
}

function bookAppointmentSlot(int $salarie_id, int $slot_id, int $service_id_confirm): bool
{
    if ($salarie_id <= 0 || $slot_id <= 0 || $service_id_confirm <= 0) return false;
    
    beginTransaction();
    $slot = executeQuery("SELECT id, prestation_id, praticien_id, start_time, site_id, is_booked FROM consultation_creneaux WHERE id = :slot_id AND prestation_id = :service_id FOR UPDATE", [
        ':slot_id' => $slot_id, 
        ':service_id' => $service_id_confirm
    ])->fetch();
    
    if (!$slot || $slot['is_booked']) {
        rollbackTransaction();
        return false;
    }
    
    $prestation = fetchOne(TABLE_PRESTATIONS, 'id = :id', [':id' => $service_id_confirm]);
    if (!$prestation) return false;
    
    updateRow('consultation_creneaux', ['is_booked' => 1], 'id = :slot_id AND is_booked = 0', [':slot_id' => $slot_id]);
    
    $rdvData = [
        'personne_id' => $salarie_id,
        'prestation_id' => $slot['prestation_id'],
        'praticien_id' => $slot['praticien_id'],
        'date_rdv' => $slot['start_time'],
        'duree' => $prestation['duree'] ?? 60,
        'lieu' => $slot['site_id'] ? 'Site ID: ' . $slot['site_id'] : null,
        'type_rdv' => $prestation['type'] ?? 'consultation',
        'statut' => 'confirme',
        'notes' => 'Réservé via plateforme web.',
        'consultation_creneau_id' => $slot_id
    ];
    
    $insertResult = insertRow(TABLE_APPOINTMENTS, $rdvData);
    if ($insertResult === false) {
        rollbackTransaction();
        return false;
    }
    
    commitTransaction();
    return (bool)$insertResult;
}

function cancelEmployeeAppointment(int $salarie_id, int $rdv_id): bool
{
    if ($salarie_id <= 0 || $rdv_id <= 0) return false;
    
    beginTransaction();
    $rdv = executeQuery("SELECT id, statut, consultation_creneau_id FROM " . TABLE_APPOINTMENTS . " WHERE id = :rdv_id AND personne_id = :salarie_id FOR UPDATE", [
        ':rdv_id' => $rdv_id, 
        ':salarie_id' => $salarie_id
    ])->fetch();
    
    if (!$rdv || !in_array($rdv['statut'], ['planifie', 'confirme'])) return false;
    
    updateRow(TABLE_APPOINTMENTS, ['statut' => 'annule'], 'id = :rdv_id', [':rdv_id' => $rdv_id]);
    
    if (!empty($rdv['consultation_creneau_id'])) {
        updateRow('consultation_creneaux', ['is_booked' => 0], 'id = :creneau_id', [':creneau_id' => $rdv['consultation_creneau_id']]);
    }
    
    commitTransaction();
    return true;
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

    $sql = "SELECT cc.id, cc.start_time, cc.end_time, cc.praticien_id,
                   CONCAT(p.prenom, ' ', p.nom) as praticien_nom
            FROM consultation_creneaux cc
            LEFT JOIN " . TABLE_USERS . " p ON cc.praticien_id = p.id AND p.role_id = :role_prestataire
            WHERE cc.prestation_id = :service_id
              AND cc.is_booked = 0
              AND cc.start_time BETWEEN :start_date AND :end_date
            ORDER BY cc.start_time ASC";

    return executeQuery($sql, [
        ':service_id' => $service_id,
        ':start_date' => date('Y-m-d 00:00:00', strtotime($startDate)),
        ':end_date' => date('Y-m-d 23:59:59', strtotime($endDate)),
        ':role_prestataire' => ROLE_PRESTATAIRE
    ])->fetchAll();
}

/**
 * Récupère et valide un créneau pour la réservation. Vérifie l'existence, la correspondance avec le service et la disponibilité.
 * Utilise FOR UPDATE pour verrouiller la ligne pendant la transaction.
 *
 * @param int $slot_id ID du créneau.
 * @param int $service_id_confirm ID du service attendu pour ce créneau.
 * @return array|null Les détails du créneau si valide, null sinon.
 */
function _getAndValidateSlotForBooking(int $slot_id, int $service_id_confirm): ?array
{
    $slot = executeQuery(
        "SELECT * FROM consultation_creneaux WHERE id = :slot_id AND prestation_id = :service_id FOR UPDATE",
        [':slot_id' => $slot_id, ':service_id' => $service_id_confirm]
    )->fetch();

    if (!$slot) {
        flashMessage("Le créneau sélectionné n'existe pas ou ne correspond pas à la prestation choisie.", "warning");
        return null;
    }

    if ($slot['is_booked']) {
        flashMessage("Ce créneau vient d'être réservé par quelqu'un d'autre.", "warning");
        return null;
    }

    return $slot;
}

/**
 * Détermine le type de rendez-vous basé sur les détails de la prestation.
 *
 * @param array $prestation Les détails de la prestation.
 * @return string Le type de rendez-vous.
 */
function _determineAppointmentType(array $prestation): string
{
    return $prestation['type'] ?? 'consultation';
}

/**
 * Vérifie si les IDs fournis pour la réservation sont valides.
 *
 * @param int $salarie_id
 * @param int $slot_id
 * @param int $service_id_confirm
 * @return bool True si les entrées sont invalides, false sinon.
 */
function _areBookingInputsInvalid(int $salarie_id, int $slot_id, int $service_id_confirm): bool
{
    return $salarie_id <= 0 || $slot_id <= 0 || $service_id_confirm <= 0;
}

/**
 * Vérifie si un message flash de fallback est nécessaire pour une exception de réservation.
 *
 * @param Exception $e L'exception interceptée.
 * @return bool True si un message flash de fallback doit être défini, false sinon.
 */
function _needsBookingFallbackFlashMessage(Exception $e): bool
{
    return empty($_SESSION['flash_messages']) && $e->getMessage() !== "Validation du créneau échouée.";
}

/**
 * Vérifie si le message d'une exception de réservation est un cas déjà géré spécifiquement.
 *
 * @param Exception $e L'exception interceptée.
 * @return bool True si le message est un cas géré, false sinon.
 */
function _isBookingExceptionMessageAlreadyHandled(Exception $e): bool
{
    $handledMessages = [
        "Détails de la prestation introuvables.",
        "Échec de l'insertion du rendez-vous.",
        "Échec de la mise à jour du créneau (probablement réservé simultanément)."
    ];
    return in_array($e->getMessage(), $handledMessages, true);
}

/**
 * Gère la logique POST pour la réservation d'un créneau.
 * Refactorisée pour utiliser des guard clauses.
 *
 * @param int $salarie_id ID de l'employé.
 * @param array $postData Données du formulaire POST.
 */
function _handleBookingPostAction(int $salarie_id, array $postData): void
{
    verifyPostedCsrfToken();

    $slot_id = filter_var($postData['slot_id'] ?? null, FILTER_VALIDATE_INT);
    $service_id_confirm = filter_var($postData['service_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$slot_id || !$service_id_confirm) {
        flashMessage("Données de réservation invalides.", "warning");
        redirectTo(WEBCLIENT_URL . '/modules/employees/appointments.php');
        exit;
    }

    $bookingSuccess = bookAppointmentSlot($salarie_id, $slot_id, $service_id_confirm);

    if ($bookingSuccess) {
        flashMessage("Votre rendez-vous a bien été réservé !", "success");
    } else {
        flashMessage("Erreur lors de la réservation. Le créneau n'est peut-être plus disponible ou une erreur technique est survenue.", "danger");
    }

    redirectTo(WEBCLIENT_URL . '/modules/employees/appointments.php');
    exit;
}

/**
 * Gère la logique POST pour l'annulation d'un rendez-vous.
 * Refactorisée pour utiliser des guard clauses.
 *
 * @param int $salarie_id ID de l'employé.
 * @param array $postData Données du formulaire POST.
 */
function _handleCancelPostAction(int $salarie_id, array $postData): void
{
    $current_filter = filter_var($postData['filter'] ?? $_GET['filter'] ?? null, FILTER_SANITIZE_SPECIAL_CHARS);
    $redirectUrl = WEBCLIENT_URL . '/modules/employees/appointments.php';
    if (!empty($current_filter) && in_array($current_filter, ['all', 'upcoming', 'past', 'cancelled'])) {
        $redirectUrl .= '?filter=' . urlencode($current_filter);
    }

    verifyPostedCsrfToken();

    $rdv_id_to_cancel = filter_var($postData['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$rdv_id_to_cancel) {
        flashMessage("ID de rendez-vous invalide pour l'annulation.", "warning");
        redirectTo($redirectUrl);
        exit;
    }

    $cancelSuccess = cancelEmployeeAppointment($salarie_id, $rdv_id_to_cancel);
    if ($cancelSuccess) {
        flashMessage("Votre rendez-vous a bien été annulé.", "success");
    } else {
        flashMessage("L'annulation du rendez-vous a échoué pour une raison inconnue.", "danger");
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

/**
 * Prépare les données pour la vue liste des rendez-vous.
 */
function _getAppointmentListData(int $salarie_id, string $filter): array
{
    $orderBy = in_array($filter, ['past', 'cancelled', 'all']) ? 'rdv.date_rdv DESC' : 'rdv.date_rdv ASC';
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
 * Récupère et valide les paramètres de la requête GET pour la page des rendez-vous.
 *
 * @return array Un tableau associatif des paramètres nettoyés et validés.
 */
function _getAppointmentRequestParams(): array
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
    $bookingStep = filter_input(INPUT_GET, 'bookingStep', FILTER_SANITIZE_SPECIAL_CHARS);
    $service_id = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);

    $requestedFilter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_SPECIAL_CHARS);
    $validFilters = ['upcoming', 'past', 'cancelled', 'all'];
    $filter = in_array($requestedFilter, $validFilters, true) ? $requestedFilter : 'upcoming';

    return compact('id', 'action', 'bookingStep', 'service_id', 'page', 'filter');
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

    $params = _getAppointmentRequestParams();
    $viewData = [];

    if ($params['action'] === 'view' && $params['id'] > 0) {
        $viewData = _getAppointmentDetailData($salarie_id, $params['id']);
        if ($viewData === null) {
            $viewData = _getAppointmentListData($salarie_id, $params['filter']);
        }
    } elseif ($params['action'] === 'select_slot' && $params['service_id'] > 0) {
        $viewData = _getShowSlotsData($params['service_id']);
        if ($viewData === null) {
            $viewData = _getShowServicesData($params['page']);
        }
    } elseif ($params['bookingStep'] === 'show_services') {
        $viewData = _getShowServicesData($params['page']);
    } else {
        $viewData = _getAppointmentListData($salarie_id, $params['filter']);
    }

    $viewData['filter'] = $params['filter'];
    $viewData['csrf_token'] = ensureCsrfToken();

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
