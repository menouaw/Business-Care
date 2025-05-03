<?php

require_once __DIR__ . '/../../../init.php';


/**
 * Gère les actions d'inscription/désinscription aux événements.
 */
function handleEventActions()
{
    if (!isset($_GET['action'], $_GET['id'])) {
        return;
    }

    requireRole(ROLE_SALARIE);
    $eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $action = $_GET['action'];
    $userId = $_SESSION['user_id'] ?? 0;
    $currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $redirectUrl = WEBCLIENT_URL . '/modules/employees/events.php?page=' . $currentPage;

    if (!$eventId || !$userId) {
        flashMessage("Action impossible : ID d'événement ou utilisateur invalide.", "danger");
        redirectTo($redirectUrl);
        return;
    }


    if ($action === 'register') {
        try {
            $pdo = getDbConnection();


            $event = fetchOne('evenements', 'id = ? AND date_debut >= NOW()', [$eventId]);
            if (!$event) {
                throw new Exception("Événement non trouvé, expiré ou invalide.");
            }


            $existingRegistration = fetchOne('evenement_inscriptions', 'evenement_id = ? AND personne_id = ?', [$eventId, $userId]);
            if ($existingRegistration) {
                throw new Exception("Vous êtes déjà inscrit(e) à cet événement.");
            }


            if (isset($event['capacite_max']) && $event['capacite_max'] !== null && $event['capacite_max'] > 0) {
                $currentRegistrations = countTableRows('evenement_inscriptions', 'evenement_id = ?', [$eventId]);
                if ($currentRegistrations >= $event['capacite_max']) {
                    throw new Exception("Cet événement est complet.");
                }
            }


            $success = insertRow('evenement_inscriptions', [
                'evenement_id' => $eventId,
                'personne_id' => $userId

            ]);

            if ($success) {
                flashMessage("Inscription à l'événement réussie !", "success");

                createNotification(
                    $userId,
                    "Inscription confirmée",
                    "Votre inscription à l'événement \"" . htmlspecialchars($event['titre']) . "\" a été enregistrée.",
                    'success',
                    WEBCLIENT_URL . '/modules/employees/my_planning.php'
                );
            } else {
                throw new Exception("Erreur technique lors de l'inscription.");
            }
        } catch (Exception $e) {
            flashMessage("Erreur d'inscription : " . $e->getMessage(), "danger");
        }

        redirectTo($redirectUrl);
    } elseif ($action === 'unregister') {
        try {

            $event = fetchOne('evenements', 'id = ?', [$eventId]);
            if (!$event) {
                throw new Exception("Événement non trouvé.");
            }


            $deletedRows = deleteRow(
                'evenement_inscriptions',
                'evenement_id = :event_id AND personne_id = :user_id',
                [':event_id' => $eventId, ':user_id' => $userId]
            );

            if ($deletedRows > 0) {
                flashMessage("Désinscription de l'événement réussie.", "success");

                createNotification(
                    $userId,
                    "Désinscription confirmée",
                    "Votre désinscription de l'événement \"" . htmlspecialchars($event['titre']) . "\" a été enregistrée.",
                    'info',
                    WEBCLIENT_URL . '/modules/employees/my_planning.php'
                );
            } else {


                flashMessage("Vous n'étiez pas inscrit(e) à cet événement ou une erreur est survenue.", "warning");
            }
        } catch (Exception $e) {
            flashMessage("Erreur de désinscription : " . $e->getMessage(), "danger");
        }

        redirectTo($redirectUrl);
    }
}

/**
 * Prépare les données nécessaires pour la page listant les événements disponibles
 * pour les salariés, avec pagination.
 *
 * @return array Données pour la vue (titre, événements, pagination).
 */
function setupEventsPage()
{

    handleEventActions();


    requireRole(ROLE_SALARIE);

    $pageTitle = "Événements & Activités";
    $itemsPerPage = 10;

    try {

        $currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
            'options' => ['default' => 1, 'min_range' => 1]
        ]);


        $whereClause = "date_debut >= NOW()";


        $paginationData = paginateResults(
            'evenements',
            $currentPage,
            $itemsPerPage,
            $whereClause,
            'date_debut ASC',
            []
        );


        if (empty($paginationData['items']) && $paginationData['currentPage'] > 1) {




            if (!headers_sent()) {
                redirectTo(WEBCLIENT_URL . '/modules/employees/events.php?page=1');
            }
        } elseif (empty($paginationData['items']) && $paginationData['currentPage'] == 1) {
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des événements paginés: " . $e->getMessage());
        flashMessage("Impossible de charger la liste des événements.", "danger");

        $paginationData = [
            'items' => [],
            'currentPage' => 1,
            'totalPages' => 1,
            'totalItems' => 0,
            'perPage' => $itemsPerPage
        ];
    }


    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId > 0 && !empty($paginationData['items'])) {
        $eventIds = array_column($paginationData['items'], 'id');
        if (!empty($eventIds)) {
            $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
            $userRegistrations = fetchAll(
                'evenement_inscriptions',
                "personne_id = ? AND evenement_id IN ($placeholders)",
                '',
                0,
                0,
                array_merge([$userId], $eventIds)
            );

            $registeredEventIds = array_column($userRegistrations, 'evenement_id');
            $registeredEventIds = array_flip($registeredEventIds);


            foreach ($paginationData['items'] as &$event) {
                $event['is_registered'] = isset($registeredEventIds[$event['id']]);

                
                $event['remaining_spots'] = null; 
                $event['current_registrations'] = 0;

                if (isset($event['capacite_max']) && $event['capacite_max'] !== null && $event['capacite_max'] > 0) {
                    try {
                        $currentRegistrations = countTableRows('evenement_inscriptions', 'evenement_id = ?', [$event['id']]);
                        $event['current_registrations'] = $currentRegistrations;
                        $event['remaining_spots'] = max(0, $event['capacite_max'] - $currentRegistrations);
                    } catch (Exception $e) {
                        error_log("Erreur comptage inscriptions pour event ID " . $event['id'] . ": " . $e->getMessage());
                        
                    }
                }
            }
            unset($event);
        }
    }


    return [
        'pageTitle' => $pageTitle,
        'events' => $paginationData['items'],
        'pagination' => $paginationData
    ];
}
