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
 * pour les salariés, séparés en "préférés" (basés sur les intérêts) et "autres".
 *
 * @return array Données pour la vue (titre, preferredEvents, otherEvents).
 */
function setupEventsPage()
{
    handleEventActions();
    requireRole(ROLE_SALARIE);

    $pageTitle = "Événements & Activités";
    $userId = $_SESSION['user_id'] ?? 0;

    if ($userId <= 0) {
        flashMessage("Session invalide.", "danger");
        redirectTo(WEBCLIENT_URL . '/auth/login.php');
        exit;
    }

    $preferredEvents = [];
    $otherEvents = [];

    try {
        
        $userInterestLinks = fetchAll(
            'personne_interets',
            'personne_id = :user_id',
            '',
            0,
            0,
            [':user_id' => $userId]
        );
        $userInterestIds = !empty($userInterestLinks) ? array_column($userInterestLinks, 'interet_id') : [];

        
        $userInterestNames = [];
        if (!empty($userInterestIds)) {
            $placeholders = implode(',', array_fill(0, count($userInterestIds), '?'));
            $interestsData = fetchAll(
                'interets_utilisateurs',
                "id IN ($placeholders)",
                '',
                0,
                0,
                $userInterestIds
            );
            $userInterestNames = !empty($interestsData) ? array_column($interestsData, 'nom') : [];
        }
        $userInterestNamesLower = array_map('strtolower', $userInterestNames);

        
        $allUpcomingEvents = fetchAll(
            'evenements',
            "date_debut >= NOW()",
            'date_debut ASC'
        );

        
        foreach ($allUpcomingEvents as $event) {
            $isPreferred = false;
            if (!empty($userInterestNamesLower)) {
                $eventTextLower = strtolower(($event['titre'] ?? '') . ' ' . ($event['description'] ?? ''));
                foreach ($userInterestNamesLower as $interestName) {
                    if (!empty($interestName) && str_contains($eventTextLower, $interestName)) {
                        $isPreferred = true;
                        break;
                    }
                }
            }
            if ($isPreferred) {
                $preferredEvents[] = $event;
            } else {
                $otherEvents[] = $event;
            }
        }

        
        $eventIdsToEnrich = array_column(array_merge($preferredEvents, $otherEvents), 'id');

        if (!empty($eventIdsToEnrich)) {
            $placeholders = implode(',', array_fill(0, count($eventIdsToEnrich), '?'));

            
            $userRegistrations = fetchAll(
                'evenement_inscriptions',
                "personne_id = ? AND evenement_id IN ($placeholders)",
                '',   
                0,    
                0,    
                array_merge([$userId], $eventIdsToEnrich)
            );
            $registeredEventIds = array_column($userRegistrations, 'evenement_id');
            $registeredEventIds = array_flip($registeredEventIds);

            
            $registrationCounts = [];
            $sqlCounts = "SELECT evenement_id, COUNT(*) as count FROM evenement_inscriptions WHERE evenement_id IN ($placeholders) GROUP BY evenement_id";
            $stmtCounts = executeQuery($sqlCounts, $eventIdsToEnrich);
            while ($row = $stmtCounts->fetch(PDO::FETCH_ASSOC)) {
                $registrationCounts[$row['evenement_id']] = (int)$row['count'];
            }

            
            $enrichEvent = function ($event) use ($registeredEventIds, $registrationCounts) {
                $eventId = $event['id'];
                $event['is_registered'] = isset($registeredEventIds[$eventId]);
                $event['remaining_spots'] = null;
                $currentRegCount = $registrationCounts[$eventId] ?? 0;
                $event['current_registrations'] = $currentRegCount;

                if (isset($event['capacite_max']) && $event['capacite_max'] !== null && $event['capacite_max'] > 0) {
                    $event['remaining_spots'] = max(0, $event['capacite_max'] - $currentRegCount);
                }
                return $event;
            };

            
            $preferredEvents = array_map($enrichEvent, $preferredEvents);
            $otherEvents = array_map($enrichEvent, $otherEvents);
        }
    } catch (Exception $e) {
        error_log("ERREUR dans setupEventsPage pour user $userId: " . $e->getMessage());
        flashMessage("Impossible de charger la liste des événements.", "danger");
        
    }

    return [
        'pageTitle' => $pageTitle,
        'preferredEvents' => $preferredEvents,
        'otherEvents' => $otherEvents
        
    ];
}
