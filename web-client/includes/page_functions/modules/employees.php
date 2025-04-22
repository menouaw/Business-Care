<?php


require_once __DIR__ . '/../../../includes/init.php';

function getEmployeesList($company_id = null, $page = 1, $limit = 5, $search = '')
{
    $company_id = sanitizeInput($company_id);
    if ($company_id !== null) {
        $company_id = filter_var($company_id, FILTER_VALIDATE_INT);
    }
    $page = max(1, (int)sanitizeInput($page));
    $limit = max(1, (int)sanitizeInput($limit));
    $search = sanitizeInput($search);

    $offset = ($page - 1) * $limit;

    $query = "SELECT p.id, p.nom, p.prenom, p.email, p.telephone, p.statut, p.photo_url,
              p.derniere_connexion, e.nom as entreprise_nom
              FROM personnes p
              LEFT JOIN entreprises e ON p.entreprise_id = e.id
              WHERE p.role_id = :role_id";
    $countQuery = "SELECT COUNT(p.id) as total FROM personnes p WHERE p.role_id = :role_id";
    $params = [':role_id' => ROLE_SALARIE];

    $countParams = [':role_id' => ROLE_SALARIE];

    if ($company_id) {
        $query .= " AND p.entreprise_id = :company_id";
        $countQuery .= " AND p.entreprise_id = :company_id";
        $params[':company_id'] = $company_id;
        $countParams[':company_id'] = $company_id;
    }

    if (!empty($search)) {
        $query .= " AND (p.nom LIKE :search OR p.prenom LIKE :search OR p.email LIKE :search)";
        $countQuery .= " AND (p.nom LIKE :search OR p.prenom LIKE :search OR p.email LIKE :search)";
        $searchTerm = '%' . $search . '%';
        $params[':search'] = $searchTerm;
        $countParams[':search'] = $searchTerm;
    }

    $query .= " ORDER BY p.nom, p.prenom ASC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    $stmt = executeQuery($query, $params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($employees as &$employee) {
        if (isset($employee['statut'])) {
            $employee['statut_badge'] = getStatusBadge($employee['statut']);
        }
        $employee['derniere_connexion_formatee'] = isset($employee['derniere_connexion']) ? formatDate($employee['derniere_connexion']) : 'Jamais';
    }


    $countStmt = executeQuery($countQuery, $countParams);
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $countResult['total'] ?? 0;

    $totalPages = ($limit > 0) ? ceil($total / $limit) : 0;

    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

    $urlParams = [];
    if ($company_id) {
        $urlParams['company_id'] = $company_id;
    }
    if (!empty($search)) {
        $urlParams['search'] = $search;
    }
    $urlPattern = "?" . http_build_query($urlParams) . "&page={page}";

    return [
        'employees' => $employees,
        'pagination' => [
            'current' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages
        ],
        'pagination_html' => renderPagination($paginationData, $urlPattern)
    ];
}

function getEmployeeDetails($employee_id)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        flashMessage("ID de salarié invalide", "danger");
        return false;
    }

    $employee = fetchOne(TABLE_USERS, "id = :id AND role_id = :role_id", [
        ':id' => $employee_id,
        ':role_id' => ROLE_SALARIE
    ]);

    if (!$employee) {
        flashMessage("Salarié non trouvé", "warning");
        return false;
    }

    if (isset($employee['statut'])) {
        $employee['statut_badge'] = getStatusBadge($employee['statut']);
    }

    $employee['date_naissance_formatee'] = isset($employee['date_naissance']) ? formatDate($employee['date_naissance'], 'd/m/Y') : 'N/A';
    $employee['derniere_connexion_formatee'] = isset($employee['derniere_connexion']) ? formatDate($employee['derniere_connexion']) : 'Jamais';


    if (!empty($employee['entreprise_id'])) {
        $entreprise = fetchOne(TABLE_COMPANIES, "id = :id", [':id' => $employee['entreprise_id']]);
        $employee['entreprise_nom'] = $entreprise ? $entreprise['nom'] : 'Inconnue';
    }

    $preferences = fetchOne(TABLE_USER_PREFERENCES, "personne_id = :id", [':id' => $employee_id]);
    $employee['preferences'] = $preferences ?: [];

    return $employee;
}


function updateEmployeeProfile($employee_id, $profile_data)
{

    $employee_id = filter_var($employee_id, FILTER_VALIDATE_INT);
    if (!$employee_id) {
        error_log("updateEmployeeProfile: ID salarié invalide.");
        return false;
    }

    $allowedFields = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'date_naissance',
        'genre',
        'photo_url'
    ];

    $filteredData = array_intersect_key($profile_data, array_flip($allowedFields));

    if (empty($filteredData)) {
        error_log("updateEmployeeProfile: Aucune donnée valide fournie pour la mise à jour.");

        return 0;
    }

    if (isset($filteredData['email'])) {
        $existingUser = fetchOne(TABLE_USERS, 'email = :email AND id != :id', [
            ':email' => $filteredData['email'],
            ':id' => $employee_id
        ]);
        if ($existingUser) {
            error_log("updateEmployeeProfile: Tentative de mise à jour avec un email déjà utilisé.");
            flashMessage("L'adresse email fournie est déjà utilisée par un autre compte.", "danger");
            return false;
        }
    }

    try {
        $filteredData['updated_at'] = date('Y-m-d H:i:s');

        $result = updateRow(
            TABLE_USERS,
            $filteredData,
            "id = :id AND role_id = :role_id",
            ['id' => $employee_id, 'role_id' => ROLE_SALARIE]
        );

        return $result;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur mise à jour profil salarié #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de la mise à jour du profil.", "danger");
        return false;
    }
}

function getEmployeeAvailableServices($employee_id)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) return [];

    $employee = fetchOne(TABLE_USERS, "id = :id AND role_id = :role_id", [
        ':id' => $employee_id,
        ':role_id' => ROLE_SALARIE
    ]);

    if (!$employee || empty($employee['entreprise_id'])) return [];

    $company_id = $employee['entreprise_id'];

    $activeContract = fetchOne(
        TABLE_CONTRACTS,
        "entreprise_id = :company_id AND statut = :status AND (date_fin IS NULL OR date_fin >= CURDATE())",
        [':company_id' => $company_id, ':status' => STATUS_ACTIVE]
    );
    if (!$activeContract) {
        return [];
    }

    $query = "SELECT id, nom, description, type, categorie,
              niveau_difficulte, duree, capacite_max, prix
              FROM " . TABLE_PRESTATIONS . "
              ORDER BY type, nom";

    $services = executeQuery($query)->fetchAll();

    foreach ($services as &$service) {
        if (isset($service['prix'])) {
            $service['prix_formate'] = formatMoney($service['prix']);
        }
    }

    return $services;
}

function getEmployeeReservations($employee_id, $status = 'all')
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) return [];

    $status = sanitizeInput($status);

    $query = "SELECT r.id, r.date_rdv, r.duree, r.lieu, r.type_rdv, r.statut, r.prestation_id,
              p.nom as prestation_nom, p.type as prestation_type,
              prat.nom as praticien_nom, prat.prenom as praticien_prenom
              FROM " . TABLE_APPOINTMENTS . " r
              JOIN " . TABLE_PRESTATIONS . " p ON r.prestation_id = p.id
              LEFT JOIN " . TABLE_USERS . " prat ON r.praticien_id = prat.id
              WHERE r.personne_id = :employee_id";
    $params = [':employee_id' => $employee_id];

    if ($status !== 'all' && in_array($status, APPOINTMENT_STATUSES)) {
        $query .= " AND r.statut = :status";
        $params[':status'] = $status;
    }

    $query .= " ORDER BY r.date_rdv DESC";

    $reservations = executeQuery($query, $params)->fetchAll();

    foreach ($reservations as &$reservation) {
        $reservation['date_rdv_formatee'] = isset($reservation['date_rdv']) ? formatDate($reservation['date_rdv'], 'd/m/Y H:i') : 'N/A';
        $reservation['statut_badge'] = isset($reservation['statut']) ? getStatusBadge($reservation['statut']) : '';
        $reservation['praticien_complet'] = isset($reservation['praticien_nom']) ? trim($reservation['praticien_prenom'] . ' ' . $reservation['praticien_nom']) : 'Non assigné';
    }

    return $reservations;
}


function getEmployeeAppointments($employee_id, $filter = 'upcoming', $page = 1, $limit = 10)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) return [
        'items' => [],
        'pagination_html' => '',
    ];

    $filter = sanitizeInput($filter);
    $page = max(1, (int)$page);
    $limit = max(5, (int)$limit);
    $offset = ($page - 1) * $limit;

    $baseQuery = "FROM " . TABLE_APPOINTMENTS . " r
                  JOIN " . TABLE_PRESTATIONS . " p ON r.prestation_id = p.id
                  LEFT JOIN " . TABLE_USERS . " prat ON r.praticien_id = prat.id
                  WHERE r.personne_id = :employee_id";

    $countQuery = "SELECT COUNT(r.id) " . $baseQuery;
    $dataQuery = "SELECT r.id, r.date_rdv, r.duree, r.lieu, r.type_rdv, r.statut,
                         p.nom as prestation_nom,
                         prat.nom as praticien_nom, prat.prenom as praticien_prenom " . $baseQuery;

    $params = [':employee_id' => $employee_id];
    $whereClause = '';

    $orderByDirection = 'DESC';
    if ($filter === 'upcoming') {
        $whereClause .= " AND r.date_rdv >= NOW() AND r.statut NOT IN ('annule', 'termine', 'no_show')";
        $orderByDirection = 'ASC';
    } else if ($filter === 'past') {
        $whereClause .= " AND (r.date_rdv < NOW() OR r.statut IN ('termine', 'no_show')) AND r.statut != 'annule'";
    } else if ($filter === 'annule') {
        $whereClause .= " AND r.statut = 'annule'";
    }

    $countQuery .= $whereClause;
    $dataQuery .= $whereClause;

    $dataQuery .= " ORDER BY r.date_rdv $orderByDirection LIMIT :limit OFFSET :offset";
    $dataParams = $params;
    $dataParams[':limit'] = $limit;
    $dataParams[':offset'] = $offset;

    try {
        $totalStmt = executeQuery($countQuery, $params);
        $totalItems = (int)$totalStmt->fetchColumn();

        $appointments = executeQuery($dataQuery, $dataParams)->fetchAll();

        foreach ($appointments as &$appointment) {
            $appointment['date_rdv_formatee'] = isset($appointment['date_rdv']) ? formatDate($appointment['date_rdv'], 'd/m/Y H:i') : 'N/A';
            $appointment['statut_badge'] = isset($appointment['statut']) ? getStatusBadge($appointment['statut']) : '';
            $appointment['praticien_complet'] = isset($appointment['praticien_nom']) ? trim($appointment['praticien_prenom'] . ' ' . $appointment['praticien_nom']) : 'Non assigné';
        }

        $totalPages = ($limit > 0 && $totalItems > 0) ? ceil($totalItems / $limit) : 0;
        $paginationData = [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'perPage' => $limit
        ];
        $urlPattern = "?filter=$filter&page={page}";

        return [
            'items' => $appointments,
            'pagination_html' => renderPagination($paginationData, $urlPattern),
            'pagination_data' => $paginationData
        ];
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur getEmployeeAppointments pour employee #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération de vos rendez-vous.", "danger");
        return [
            'items' => [],
            'pagination_html' => '',
            'pagination_data' => ['currentPage' => 1, 'totalPages' => 0, 'totalItems' => 0, 'perPage' => $limit]
        ];
    }
}

function getEmployeeActivityHistory($employee_id, $page = 1, $limit = 10)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $page = max(1, (int)sanitizeInput($page));
    $limit = max(1, (int)sanitizeInput($limit));

    $defaultResult = [
        'activities' => [],
        'pagination' => [
            'current' => $page,
            'limit' => $limit,
            'total' => 0,
            'totalPages' => 0
        ],
        'pagination_html' => ''
    ];

    if (!$employee_id) {
        flashMessage("ID de salarié invalide", "danger");
        return $defaultResult;
    }

    try {
        $where = "personne_id = :employee_id";
        $params = [':employee_id' => $employee_id];
        $orderBy = "created_at DESC";

        $totalItems = countTableRows(TABLE_LOGS, $where, $params);
        $totalPages = ($limit > 0) ? ceil($totalItems / $limit) : 0;
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;

        $activities = fetchAll(TABLE_LOGS, $where, $orderBy, $limit, $offset, $params);

        foreach ($activities as &$activity) {
            $activity['created_at_formatted'] = isset($activity['created_at']) ? formatDate($activity['created_at']) : 'N/A';
            $actionParts = explode(':', $activity['action'], 2);
            $mainAction = count($actionParts) > 1 ? trim($actionParts[1]) : trim($actionParts[0]);
            $activity['icon'] = getActivityIcon($mainAction);
        }

        $paginationData = [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'perPage' => $limit
        ];

        $urlPattern = "?page={page}";

        return [
            'activities' => $activities,
            'pagination' => [
                'current' => $page,
                'limit' => $limit,
                'total' => $totalItems,
                'totalPages' => $totalPages
            ],
            'pagination_html' => renderPagination($paginationData, $urlPattern)
        ];
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération historique activités salarié #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération de l'historique d'activités", "danger");
        return $defaultResult;
    }
}

function getActivityIcon($action)
{
    $keyAction = strtolower(trim(substr($action, strrpos($action, ':') + 1)));

    $iconMap = [
        'login' => 'fas fa-sign-in-alt text-success',
        'logout' => 'fas fa-sign-out-alt text-warning',
        'auto_login' => 'fas fa-key text-info',
        'session_timeout' => 'fas fa-clock text-muted',
        'password_reset' => 'fas fa-unlock-alt text-info',
        'update_profile' => 'fas fa-user-edit text-primary',
        'update_preferences' => 'fas fa-cog text-secondary',
        'reservation:creation' => 'fas fa-calendar-plus text-success',
        'reservation:modification' => 'fas fa-calendar-day text-info',
        'reservation:annulation' => 'fas fa-calendar-times text-danger',
        'evaluation_creation' => 'fas fa-star text-warning',
        'don_creation' => 'fas fa-hand-holding-heart text-info',
        'community_post' => 'fas fa-comments text-primary',
        'anonymous_report' => 'fas fa-user-secret text-danger',
        'login_failure' => 'fas fa-exclamation-triangle text-danger',
        'csrf_failure' => 'fas fa-shield-alt text-danger',
        'permission_denied' => 'fas fa-ban text-danger',
        'remember_token' => 'fas fa-cookie-bite text-info',
        'default' => 'fas fa-history text-muted'
    ];

    if (isset($iconMap[$action])) return $iconMap[$action];
    if (isset($iconMap[$keyAction])) return $iconMap[$keyAction];
    if (strpos($action, 'reservation:') === 0) return 'fas fa-calendar-alt text-info';
    if (strpos($action, 'don:') === 0) return 'fas fa-gift text-info';
    if (strpos($action, 'paiement:') === 0) return 'fas fa-credit-card text-success';

    return $iconMap['default'];
}


function getEmployeeCommunities($employee_id)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);

    try {
        $communities = fetchAll(TABLE_COMMUNAUTES, '1=1', 'type, nom');
        return $communities;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération communautés: " . $e->getMessage());
        flashMessage("Impossible de charger la liste des communautés.", "danger");
        return [];
    }
}

function manageEmployeeDonations($employee_id, $donation_data)
{
    $employee_id = filter_var($employee_id, FILTER_VALIDATE_INT);
    if (!$employee_id) {
        error_log("manageEmployeeDonations: ID salarié invalide.");
        return false;
    }

    $association_id = isset($donation_data['association_id']) ? filter_var($donation_data['association_id'], FILTER_VALIDATE_INT) : null;
    if ($association_id === null) {
        flashMessage("Veuillez sélectionner une association.", "warning");
        return false;
    }

    if ($association_id) {
        $association_exists = fetchOne(TABLE_ASSOCIATIONS, "id = :id", [':id' => $association_id]);
        if (!$association_exists) {
            flashMessage("L'association sélectionnée est invalide.", "danger");
            return false;
        }
    }

    $donation_data['type'] = $donation_data['type'] ?? null;
    $donation_data['montant'] = $donation_data['montant'] ?? null;
    $donation_data['description'] = $donation_data['description'] ?? null;

    if (!in_array($donation_data['type'], DONATION_TYPES)) {
        flashMessage("Type de don invalide.", "warning");
        return false;
    }

    if ($donation_data['type'] == DONATION_TYPES[0]) {
        $montant = filter_var($donation_data['montant'], FILTER_VALIDATE_FLOAT);
        if ($montant === false || $montant <= 0) {
            flashMessage("Le montant du don financier doit être un nombre positif.", "warning");
            return false;
        }
        $donation_data['montant'] = $montant;
        $donation_data['description'] = null;
    } elseif ($donation_data['type'] == DONATION_TYPES[1]) {
        if (empty(trim($donation_data['description']))) {
            flashMessage("La description est obligatoire pour un don matériel.", "warning");
            return false;
        }
        $donation_data['montant'] = null;
    }

    try {
        $employee = fetchOne(TABLE_USERS, "id = :id AND role_id = :role_id AND statut = :status", [
            ':id' => $employee_id,
            ':role_id' => ROLE_SALARIE,
            ':status' => STATUS_ACTIVE
        ]);
        if (!$employee) {
            flashMessage("Impossible de traiter le don: Salarié non trouvé ou inactif.", "danger");
            return false;
        }

        $donData = [
            'personne_id' => $employee_id,
            'association_id' => $association_id,
            'montant' => $donation_data['type'] == DONATION_TYPES[0] ? $donation_data['montant'] : null,
            'type' => $donation_data['type'],
            'description' => $donation_data['type'] == DONATION_TYPES[1] ? trim($donation_data['description']) : null,
            'date_don' => date('Y-m-d'),
            'statut' => DEFAULT_DONATION_STATUS
        ];

        beginTransaction();

        $donationId = insertRow(TABLE_DONATIONS, $donData);

        if (!$donationId) {
            rollbackTransaction();
            logSystemActivity('error', "manageEmployeeDonations: Échec de l'insertion dans la table dons pour user #$employee_id");
            flashMessage("Une erreur technique est survenue lors de l'enregistrement de votre don (code 1).", "danger");
            return false;
        }

        commitTransaction();

        $assoc_name = 'Inconnue';
        if (isset($association_exists) && $association_exists) {
            $assoc_name = $association_exists['nom'];
        }
        logBusinessOperation($employee_id, 'don_creation', "Don #" . $donationId . " créé, type: " . $donation_data['type'] . " pour association: " . $assoc_name);


        return $donationId;
    } catch (Exception $e) {
        if (getDbConnection()->inTransaction()) {
            rollbackTransaction();
        }
        logSystemActivity('error', "Erreur création don pour user #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de l'enregistrement de votre don (code 2).", "danger");
        return false;
    }
}

function getEmployeeEvents($employee_id, $event_type = 'all', $page = 1, $limit = 9)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        flashMessage("ID employé invalide pour récupérer les événements.", "danger");
        return [
            'items' => [],
            'pagination_html' => '',
        ];
    }

    $employeeData = fetchOne(TABLE_USERS, 'id = :id', [':id' => $employee_id], ['site_id']);
    $employee_site_id = $employeeData ? $employeeData['site_id'] : null;

    $event_type = sanitizeInput($event_type);
    $validEventTypes = EVENT_TYPES;

    $query = "SELECT e.id, e.titre, e.description, e.date_debut, e.date_fin, e.lieu, e.type,
                     e.capacite_max, e.niveau_difficulte, e.site_id
              FROM evenements e
              WHERE e.date_debut >= CURDATE()";
    $params = [];
    $whereClause = '';

    if ($employee_site_id !== null) {
        $whereClause .= " AND (e.site_id IS NULL OR e.site_id = :employee_site_id)";
        $params[':employee_site_id'] = $employee_site_id;
    } else {
        $whereClause .= " AND e.site_id IS NULL";
    }

    if ($event_type !== 'all' && in_array($event_type, $validEventTypes)) {
        $whereClause .= " AND e.type = :event_type";
        $params[':event_type'] = $event_type;
    }

    $query .= $whereClause;
    $query .= " ORDER BY e.date_debut ASC, e.titre ASC";

    try {
        $prefQuery = "SELECT categorie_conseil FROM utilisateur_interets_conseils WHERE personne_id = :pid";
        $userPreferences = executeQuery($prefQuery, ['pid' => $employee_id])->fetchAll(PDO::FETCH_COLUMN);
        $userPreferencesLower = array_map('strtolower', array_map('trim', $userPreferences));

        $preferenceToEventTypeMap = [
            'stress' => ['conference', 'webinar', 'atelier'],
            'sommeil' => ['conference', 'webinar', 'atelier'],
            'nutrition' => ['conference', 'webinar', 'atelier'],
            'activité physique' => ['defi_sportif', 'atelier', 'conference'],
            'sport' => ['defi_sportif', 'atelier', 'conference'],
            'bien_etre' => ['conference', 'webinar', 'atelier', 'defi_sportif'],
            'sante' => ['conference', 'webinar', 'atelier']
        ];

        $recommendedEventTypes = [];
        foreach ($userPreferencesLower as $pref) {
            if (isset($preferenceToEventTypeMap[$pref])) {
                $recommendedEventTypes = array_merge($recommendedEventTypes, $preferenceToEventTypeMap[$pref]);
            }
        }
        $recommendedEventTypes = array_unique($recommendedEventTypes);

        error_log("[DEBUG] getEmployeeEvents - Query: " . $query);
        error_log("[DEBUG] getEmployeeEvents - Params: " . json_encode($params));

        $events = executeQuery($query, $params)->fetchAll();

        error_log("[DEBUG] getEmployeeEvents - Fetched events count: " . count($events));

        $eventIds = array_map(fn($e) => $e['id'], $events);
        $inscriptionCounts = [];
        $userRegistrations = [];

        if (!empty($eventIds)) {
            $placeholders = implode(',', array_fill(0, count($eventIds), '?'));

            $inscriptionCounts = [];
            $sqlCounts = "SELECT evenement_id, COUNT(id) as count
                          FROM evenement_inscriptions
                          WHERE evenement_id IN ($placeholders) AND statut = 'inscrit'
                          GROUP BY evenement_id";
            $stmtCounts = executeQuery($sqlCounts, $eventIds);
            while ($row = $stmtCounts->fetch()) {
                $inscriptionCounts[$row['evenement_id']] = $row['count'];
            }

            $userRegistrations = [];
            $sqlUserReg = "SELECT evenement_id
                           FROM evenement_inscriptions
                           WHERE personne_id = ? AND statut = 'inscrit' AND evenement_id IN ($placeholders)";
            $paramsUserReg = array_merge([$employee_id], $eventIds);
            $stmtUserReg = executeQuery($sqlUserReg, $paramsUserReg);
            while ($row = $stmtUserReg->fetch()) {
                $userRegistrations[$row['evenement_id']] = true;
            }
        }

        foreach ($events as &$event) {
            $event['date_debut_formatee'] = isset($event['date_debut']) ? formatDate($event['date_debut'], 'd/m/Y H:i') : 'N/A';
            $event['date_fin_formatee'] = isset($event['date_fin']) ? formatDate($event['date_fin'], 'd/m/Y H:i') : 'N/A';
            $registeredCount = $inscriptionCounts[$event['id']] ?? 0;
            if (isset($event['capacite_max']) && $event['capacite_max'] !== null) {
                $event['places_restantes'] = max(0, $event['capacite_max'] - $registeredCount);
                $event['est_complet'] = ($event['places_restantes'] <= 0);
            } else {
                $event['places_restantes'] = null;
                $event['est_complet'] = false;
            }
            $event['est_inscrit'] = isset($userRegistrations[$event['id']]);

            $eventTypeLower = isset($event['type']) ? strtolower(trim($event['type'])) : null;
            $event['is_recommended'] = $eventTypeLower && in_array($eventTypeLower, $recommendedEventTypes);
        }

        return $events;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération événements pour employé #$employee_id: " . $e->getMessage());
        flashMessage("Impossible de charger la liste des événements.", "danger");
        return [
            'items' => [],
            'pagination_html' => '',
        ];
    }
}

function updateEmployeeSettings($employee_id, $settings)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        error_log("updateEmployeeSettings: ID salarié invalide.");
        return false;
    }

    $allowedFields = [
        'langue' => ['fr', 'en'],
        'notif_email' => [0, 1],
        'theme' => ['clair', 'sombre']
    ];

    $filteredSettings = [];
    foreach ($settings as $key => $value) {
        if (array_key_exists($key, $allowedFields)) {
            if ($key === 'notif_email') {
                $filteredSettings[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            } elseif (in_array($value, $allowedFields[$key])) {
                $filteredSettings[$key] = $value;
            }
        }
    }

    if (empty($filteredSettings)) {
        flashMessage("Aucun paramètre valide fourni pour la mise à jour.", "warning");
        return false;
    }

    try {
        $exists = fetchOne(TABLE_USER_PREFERENCES, "personne_id = :id", [':id' => $employee_id]);

        $success = false;

        if ($exists) {
            $affectedRows = updateRow(
                TABLE_USER_PREFERENCES,
                $filteredSettings,
                'personne_id = :personne_id',
                ['personne_id' => $employee_id]
            );
            $success = ($affectedRows !== false);
        } else {
            $filteredSettings['personne_id'] = $employee_id;
            $insertId = insertRow(TABLE_USER_PREFERENCES, $filteredSettings);
            $success = ($insertId !== false);
        }

        if ($success) {
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $employee_id) {
                if (isset($filteredSettings['langue'])) {
                    $_SESSION['user_language'] = $filteredSettings['langue'];
                }
            }
        }

        return $success;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur mise à jour préférences salarié #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de la mise à jour des préférences.", "danger");
        return false;
    }
}


function bookEmployeeAppointment($employee_id, $appointment_data)
{
    $employee_id = filter_var($employee_id, FILTER_VALIDATE_INT);
    if (!$employee_id) {
        error_log("bookEmployeeAppointment: ID salarié invalide.");
        return false;
    }

    $requiredFields = ['prestation_id', 'date_rdv', 'duree', 'type_rdv'];
    foreach ($requiredFields as $field) {
        if (empty($appointment_data[$field])) {
            flashMessage("Le champ '$field' est obligatoire pour la réservation.", "danger");
            return false;
        }
    }

    $prestation_id = filter_var($appointment_data['prestation_id'], FILTER_VALIDATE_INT);
    $duree = filter_var($appointment_data['duree'], FILTER_VALIDATE_INT);
    $type_rdv = $appointment_data['type_rdv'];
    $praticien_id = isset($appointment_data['praticien_id']) ? filter_var($appointment_data['praticien_id'], FILTER_VALIDATE_INT) : null;

    $dateHeure = date('Y-m-d H:i:s', strtotime($appointment_data['date_rdv']));
    if (!$prestation_id || !$duree || $duree <= 0 || !$dateHeure || !in_array($type_rdv, APPOINTMENT_TYPES)) {
        flashMessage("Données de rendez-vous invalides (format, type ou durée).", "danger");
        return false;
    }
    if ($dateHeure < date('Y-m-d H:i:s')) {
        flashMessage("Vous ne pouvez pas réserver un rendez-vous dans le passé.", "warning");
        return false;
    }

    $dateHeure = date('Y-m-d H:i:s', strtotime($appointment_data['date_rdv']));
    if (!$prestation_id || !$duree || $duree <= 0 || !$dateHeure || !in_array($type_rdv, APPOINTMENT_TYPES)) {
        flashMessage("Données de rendez-vous invalides (format, type ou durée).", "danger");
        return false;
    }
    if ($dateHeure < date('Y-m-d H:i:s')) {
        flashMessage("Vous ne pouvez pas réserver un rendez-vous dans le passé.", "warning");
        return false;
    }

    if (!isTimeSlotAvailable($dateHeure, $duree, $prestation_id)) {
        flashMessage("Ce créneau horaire n'est pas disponible pour cette prestation.", "warning");
        return false;
    }

    try {
        $employee = fetchOne(TABLE_USERS, "id = :id AND role_id = :role_id AND statut = :status", [
            ':id' => $employee_id,
            ':role_id' => ROLE_SALARIE,
            ':status' => STATUS_ACTIVE
        ]);
        $prestation = fetchOne(TABLE_PRESTATIONS, "id = :id", [':id' => $prestation_id]);

        if (!$employee) {
            flashMessage("Salarié non trouvé ou inactif.", "danger");
            return false;
        }
        if (!$prestation) {
            flashMessage("La prestation demandée n'existe pas.", "danger");
            return false;
        }

        beginTransaction();

        $rdvData = [
            'personne_id' => $employee_id,
            'prestation_id' => $prestation_id,
            'praticien_id' => $praticien_id,
            'date_rdv' => $dateHeure,
            'duree' => $duree,
            'lieu' => $appointment_data['lieu'] ?? null,
            'type_rdv' => $type_rdv,
            'statut' => 'planifie',
            'notes' => $appointment_data['notes'] ?? null
        ];

        $appointmentId = insertRow(TABLE_APPOINTMENTS, $rdvData);

        if (!$appointmentId) {
            rollbackTransaction();
            logSystemActivity('error', "bookEmployeeAppointment: Échec insertion RDV pour user #$employee_id, prestation #$prestation_id");
            flashMessage("Une erreur technique est survenue lors de la création du rendez-vous (code 1).", "danger");
            return false;
        }

        $notifData = [
            'personne_id' => $employee_id,
            'titre' => 'Nouveau rendez-vous planifié',
            'message' => 'Votre rendez-vous pour \'' . sanitizeInput($prestation['nom']) . '\' le ' . formatDate($dateHeure) . ' a été planifié.',
            'type' => 'info',
            'lien' => WEBCLIENT_URL . '/mon-planning?rdv=' . $appointmentId
        ];
        insertRow(TABLE_NOTIFICATIONS, $notifData);

        commitTransaction();

        logReservationActivity($employee_id, $prestation_id, 'creation', "RDV #$appointmentId créé");

        return $appointmentId;
    } catch (Exception $e) {
        if (getDbConnection()->inTransaction()) {
            rollbackTransaction();
        }
        logSystemActivity('error', "Erreur création rendez-vous pour user #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de la création du rendez-vous (code 2).", "danger");
        return false;
    }
}

function displayEmployeeDashboard()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $data['user'] = getUserInfo($employee_id);
    if (!$data['user']) {
        flashMessage("Impossible de récupérer les informations utilisateur.", "danger");
        redirectTo(WEBCLIENT_URL . '/connexion.php');
    }

    $appointmentsResult = getEmployeeAppointments($employee_id, 'upcoming', 1, 3);
    $data['upcoming_appointments'] = $appointmentsResult;

    $allUpcomingEvents = getEmployeeEvents($employee_id, 'all', 1, 999);

    $prefQuery = "SELECT categorie_conseil FROM utilisateur_interets_conseils WHERE personne_id = :pid";
    $userPreferences = executeQuery($prefQuery, ['pid' => $employee_id])->fetchAll(PDO::FETCH_COLUMN);
    $userPreferencesLower = array_map('strtolower', array_map('trim', $userPreferences));

    $preferenceToEventTypeMap = [
        'stress' => ['conference', 'webinar', 'atelier'],
        'sommeil' => ['conference', 'webinar', 'atelier'],
        'nutrition' => ['conference', 'webinar', 'atelier'],
        'activité physique' => ['defi_sportif', 'atelier', 'conference'],
        'sport' => ['defi_sportif', 'atelier', 'conference'],
        'bien_etre' => ['conference', 'webinar', 'atelier', 'defi_sportif'],
        'sante' => ['conference', 'webinar', 'atelier']
    ];

    $recommendedEventTypes = [];
    foreach ($userPreferencesLower as $pref) {
        if (isset($preferenceToEventTypeMap[$pref])) {
            $recommendedEventTypes = array_merge($recommendedEventTypes, $preferenceToEventTypeMap[$pref]);
        }
    }
    $recommendedEventTypes = array_unique($recommendedEventTypes);

    $recommendedEvents = [];
    $otherEvents = [];
    if (is_array($allUpcomingEvents)) {
        foreach ($allUpcomingEvents as $event) {
            $eventTypeLower = isset($event['type']) ? strtolower(trim($event['type'])) : null;
            if ($eventTypeLower && in_array($eventTypeLower, $recommendedEventTypes)) {
                $recommendedEvents[] = $event;
            } else {
                $otherEvents[] = $event;
            }
        }
    }

    $dashboardEvents = array_slice($recommendedEvents, 0, 3);
    $remainingSlots = 3 - count($dashboardEvents);
    if ($remainingSlots > 0) {
        $dashboardEvents = array_merge($dashboardEvents, array_slice($otherEvents, 0, $remainingSlots));
    }

    $data['upcoming_events'] = $dashboardEvents;
    $data['total_upcoming_events_count'] = is_array($allUpcomingEvents) ? count($allUpcomingEvents) : 0;

    $data['unread_notifications'] = fetchAll(TABLE_NOTIFICATIONS, 'personne_id = :id AND lu = 0', 'created_at DESC', 5, 0, ['id' => $employee_id]);

    $data['recommended_events'] = array_slice($recommendedEvents, 0, 3);

    $activityResult = getEmployeeActivityHistory($employee_id, 1, 5);
    $data['recent_activity'] = $activityResult;

    error_log("Debug Dashboard - Events Result: " . print_r($dashboardEvents, true));

    return $data;
}


function displayEmployeeAppointmentsPage()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $filter = $_GET['filter'] ?? 'upcoming';
    $validFilters = ['upcoming', 'past', 'all'];
    if (!in_array($filter, $validFilters)) {
        $filter = 'upcoming';
    }

    $data['appointments'] = getEmployeeAppointments($employee_id, $filter);
    $data['currentFilter'] = $filter;
    $data['csrf_token'] = $_SESSION['csrf_token'];

    return $data;
}

function displayEmployeeCommunitiesPage()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [
        'recommendedCommunities' => [],
        'otherCommunities' => [],
        'memberCommunityIds' => [],
        'csrf_token' => $_SESSION['csrf_token'] ?? generateToken(),
        'dbError' => null
    ];

    try {
        $allCommunities = fetchAll(TABLE_COMMUNAUTES, '1=1', 'type, nom');
        if ($allCommunities === false) {
            throw new Exception("Impossible de récupérer la liste des communautés.");
        }

        $memberQuery = "SELECT communaute_id FROM communaute_membres WHERE personne_id = :pid";
        $memberStmt = executeQuery($memberQuery, [':pid' => $employee_id]);
        $data['memberCommunityIds'] = $memberStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        if ($data['memberCommunityIds'] === false) {
            error_log("Avertissement: Impossible de récupérer les adhésions aux communautés pour l'employé #$employee_id.");
            $data['memberCommunityIds'] = [];
        }

        $prefQuery = "SELECT categorie_conseil FROM utilisateur_interets_conseils WHERE personne_id = :pid";
        $userPreferencesStmt = executeQuery($prefQuery, [':pid' => $employee_id]);
        $userCounselingPreferences = $userPreferencesStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        if ($userCounselingPreferences === false) {
            error_log("Avertissement: Impossible de récupérer les préférences de conseils pour l'employé #$employee_id.");
            $userCounselingPreferences = [];
        }
        $userCounselingPreferencesLower = array_map('strtolower', array_map('trim', $userCounselingPreferences));

        $preferenceToCommunityTypeMap = [
            'stress' => ['bien_etre', 'sante'],
            'sommeil' => ['bien_etre', 'sante'],
            'nutrition' => ['sante', 'bien_etre'],
            'activité physique' => ['sport'],
            'sport' => ['sport'],
            'bien_etre' => ['bien_etre'],
            'sante' => ['sante']
        ];

        $recommendedCommunityTypes = [];
        foreach ($userCounselingPreferencesLower as $pref) {
            if (isset($preferenceToCommunityTypeMap[$pref])) {
                $recommendedCommunityTypes = array_merge($recommendedCommunityTypes, $preferenceToCommunityTypeMap[$pref]);
            }
        }
        $recommendedCommunityTypes = array_unique($recommendedCommunityTypes);

        foreach ($allCommunities as $community) {
            $communityTypeLower = isset($community['type']) ? strtolower(trim($community['type'])) : null;

            if ($communityTypeLower && in_array($communityTypeLower, $recommendedCommunityTypes)) {
                $data['recommendedCommunities'][] = $community;
            } else {
                $data['otherCommunities'][] = $community;
            }
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur chargement données page communautés pour user #$employee_id: " . $e->getMessage());
        $data['dbError'] = "Une erreur est survenue lors du chargement des communautés.";
        $data['recommendedCommunities'] = [];
        $data['otherCommunities'] = [];
        $data['memberCommunityIds'] = [];
    }

    return $data;
}

function getActiveAssociations()
{
    try {
        return fetchAll(TABLE_ASSOCIATIONS, '1=1', 'nom ASC', 0, 0, []);
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération des associations: " . $e->getMessage());
        flashMessage("Impossible de charger la liste des associations.", "danger");
        return [];
    }
}

function displayEmployeeDonationsPage()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $query = "SELECT d.*, a.nom as association_nom
              FROM " . TABLE_DONATIONS . " d
              LEFT JOIN associations a ON d.association_id = a.id
              WHERE d.personne_id = :employee_id
              ORDER BY d.date_don DESC, d.created_at DESC";

    $data['donations'] = executeQuery($query, [':employee_id' => $employee_id])->fetchAll();

    foreach ($data['donations'] as &$don) {
        $don['date_don_formatee'] = isset($don['date_don']) ? formatDate($don['date_don'], 'd/m/Y') : 'N/A';
        $don['statut_badge'] = isset($don['statut']) ? getStatusBadge($don['statut']) : '';
        if ($don['type'] === DONATION_TYPES[0] && isset($don['montant'])) {
            $don['montant_formate'] = formatMoney($don['montant']);
        }
        $don['association_nom'] = $don['association_nom'] ?? 'Non spécifiée';
    }

    $data['associations'] = getActiveAssociations();

    $data['csrf_token'] = $_SESSION['csrf_token'];

    return $data;
}

function displayEmployeeEventsPage()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $filters = getQueryData();
    $typeFilter = $filters['type'] ?? 'all';
    $page = isset($filters['page']) ? (int)$filters['page'] : 1;

    $data = [];
    $data['events'] = getEmployeeEvents($employee_id, $typeFilter);
    $data['currentTypeFilter'] = $typeFilter;
    $data['eventTypes'] = EVENT_TYPES;
    $data['csrf_token'] = $_SESSION['csrf_token'];

    return $data;
}




function displayEmployeeProfile()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $data['employee'] = getEmployeeDetails($employee_id);
    if (!$data['employee']) {
        flashMessage("Impossible d'afficher le profil.", "danger");
        redirectTo(WEBCLIENT_URL . '/dashboard.php');
    }
    $data['csrf_token'] = $_SESSION['csrf_token'];

    return $data;
}

function handleUpdateEmployeeProfile()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken();

        $formData = getFormData();

        $validation_errors = [];
        if (isset($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = "Format d'email invalide";
        }
        if (isset($formData['telephone']) && !empty($formData['telephone']) && !preg_match('/^(0|\+33)[1-9]([-. ]?[0-9]{2}){4}$/', $formData['telephone'])) {
            $validation_errors[] = "Format de téléphone invalide (ex: 0123456789 ou +33123456789)";
        }
        if (isset($formData['genre']) && !empty($formData['genre']) && !in_array($formData['genre'], ['M', 'F', 'Autre'])) {
            $validation_errors[] = "Genre invalide.";
        }

        if (!empty($validation_errors)) {
            flashMessage("Erreurs de validation: " . implode(", ", $validation_errors), "danger");
        } else {
            $result = updateEmployeeProfile($employee_id, $formData);

            if ($result !== false) {
                if ($result > 0) {
                    logBusinessOperation($employee_id, 'update_profile', "Profil mis à jour par l'employé");
                    flashMessage("Votre profil a été mis à jour avec succès.", "success");
                } else {
                    flashMessage("Aucune modification détectée sur votre profil.", "info");
                }
            }
        }
        redirectTo(WEBCLIENT_URL . '/mon-profil.php');
    } else {
        redirectTo(WEBCLIENT_URL . '/mon-profil.php');
    }
}
function displayEmployeeSettings()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $data['employee'] = getEmployeeDetails($employee_id);
    if (!$data['employee']) {
    }

    $data['settings'] = fetchOne(TABLE_USER_PREFERENCES, "personne_id = :id", [':id' => $employee_id]);
    if (!$data['settings']) {
        $data['settings'] = [
            'langue' => 'fr',
            'notif_email' => 1
        ];
    }
    $data['csrf_token'] = $_SESSION['csrf_token'];

    return $data;
}

function handleUpdateEmployeeSettings()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken();
        $formData = getFormData();

        $profileData = [
            'nom' => $formData['nom'] ?? null,
            'prenom' => $formData['prenom'] ?? null,
            'telephone' => $formData['telephone'] ?? null
        ];

        $settingsData = [
            'langue' => $formData['langue'] ?? null,
            'notif_email' => isset($formData['notif_email']) ? 1 : 0
        ];

        $profileUpdateResult = updateEmployeeProfile($employee_id, $profileData);

        $settingsUpdateResult = updateEmployeeSettings($employee_id, $settingsData);

        if ($profileUpdateResult !== false && $settingsUpdateResult !== false) {
            if (($profileUpdateResult !== false && $profileUpdateResult > 0) || $settingsUpdateResult === true) {
                logBusinessOperation($employee_id, 'update_preferences_profile', "Profil/Préférences mis à jour par l'employé via settings.php");
                flashMessage("Vos informations ont été enregistrées avec succès.", "success");
            } else if ($profileUpdateResult === 0 && $settingsUpdateResult === true) {
            } else if ($profileUpdateResult > 0 && $settingsUpdateResult === false && !fetchOne(TABLE_USER_PREFERENCES, "personne_id = :id", [':id' => $employee_id])) {
            } else if ($profileUpdateResult === 0 && $settingsUpdateResult === false && fetchOne(TABLE_USER_PREFERENCES, "personne_id = :id", [':id' => $employee_id])) {
                flashMessage("Aucune modification détectée.", "info");
            }
        }

        redirectTo(WEBCLIENT_URL . '/modules/employees/settings.php');
    } else {
        redirectTo(WEBCLIENT_URL . '/modules/employees/settings.php');
    }
}


function displayServiceCatalog()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $employee = fetchOne(TABLE_USERS, "id = :id", [':id' => $employee_id]);
    if (!$employee || !$employee['entreprise_id']) {
        flashMessage("Accès refusé: informations utilisateur incomplètes.", "danger");
        redirectTo(WEBCLIENT_URL . '/dashboard.php');
    }

    $activeContract = fetchOne(
        TABLE_CONTRACTS,
        "entreprise_id = :company_id AND statut = :status AND (date_fin IS NULL OR date_fin >= CURDATE())",
        [':company_id' => $employee['entreprise_id'], ':status' => STATUS_ACTIVE]
    );

    if ($activeContract === false) {
        $data['services'] = [];
        $data['pagination_html'] = '';
        $data['hasActiveContract'] = false;
        flashMessage("Votre entreprise n'a pas de contrat actif pour accéder aux services.", "warning");
    } else {
        $data['hasActiveContract'] = true;

        $filters = getQueryData();
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $typeFilter = $filters['type'] ?? '';
        $categoryFilter = $filters['categorie'] ?? '';

        $paginationResult = getPrestations($typeFilter, $categoryFilter, $page, 3);

        $data['services'] = $paginationResult['items'];
        $data['pagination_html'] = renderPagination($paginationResult, "?type=$typeFilter&categorie=$categoryFilter&page={page}");
        $data['types'] = PRESTATION_TYPES;
    }

    return $data;
}

function displayServiceDetails($service_id)
{
    requireRole(ROLE_SALARIE);

    $service_id = filter_var(sanitizeInput($service_id), FILTER_VALIDATE_INT);
    if (!$service_id) {
        flashMessage("ID de service invalide.", "danger");
        redirectTo(WEBCLIENT_URL . '/catalogue.php');
    }

    $data = [];
    $data['service'] = fetchOne(TABLE_PRESTATIONS, "id = :id", [':id' => $service_id]);

    if (!$data['service']) {
        flashMessage("Service non trouvé.", "warning");
        redirectTo(WEBCLIENT_URL . '/catalogue.php');
    }

    if (isset($data['service']['prix'])) {
        $data['service']['prix_formate'] = formatMoney($data['service']['prix']);
    }
    $data['csrf_token'] = $_SESSION['csrf_token'];

    return $data;
}

function displayEmployeeSchedule()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $filter = $_GET['filter'] ?? 'upcoming';

    $data['reservations'] = getEmployeeReservations($employee_id, $filter);

    $data['event_registrations'] = [];

    $data['filter'] = $filter;

    return $data;
}
function handleReservationRequest()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken();
        $formData = getFormData();

        $appointmentData = [
            'prestation_id' => $formData['prestation_id'] ?? null,
            'date_rdv' => $formData['date_rdv'] ?? null,
            'duree' => $formData['duree'] ?? null,
            'type_rdv' => $formData['type_rdv'] ?? null,
            'lieu' => $formData['lieu'] ?? null,
            'notes' => $formData['notes'] ?? null,
            'praticien_id' => $formData['praticien_id'] ?? null
        ];

        $appointmentId = bookEmployeeAppointment($employee_id, $appointmentData);

        if ($appointmentId) {
            flashMessage("Votre réservation a été enregistrée avec succès (ID: $appointmentId).", "success");
            redirectTo(WEBCLIENT_URL . '/mon-planning.php');
        } else {
            flashMessage("Une erreur est survenue lors de la réservation.", "danger");
            redirectTo($_SERVER['HTTP_REFERER'] ?? WEBCLIENT_URL . '/catalogue.php');
        }
    } else {
        redirectTo(WEBCLIENT_URL . '/catalogue.php');
    }
}


function handleCancelReservation($reservation_id)
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $reservation_id = filter_var(sanitizeInput($reservation_id), FILTER_VALIDATE_INT);
    if (!$reservation_id) {
        flashMessage("ID de réservation invalide.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/employees/appointments.php');
        exit;
    }

    error_log("[DEBUG] handleCancelReservation: Tentative d'annulation pour employee_id = $employee_id, reservation_id = $reservation_id");

    try {
        error_log("[DEBUG] handleCancelReservation: Appel de fetchOne avec id=$reservation_id, personne_id=$employee_id, statut IN ('planifie', 'confirme')");
        $reservation = fetchOne(
            TABLE_APPOINTMENTS,
            "id = :id AND personne_id = :employee_id AND statut IN ('planifie', 'confirme')",
            [':id' => $reservation_id, ':employee_id' => $employee_id]
        );
        error_log("[DEBUG] handleCancelReservation: Résultat de fetchOne: " . print_r($reservation, true));

        if (!$reservation) {
            flashMessage("Réservation non trouvée ou non annulable.", "warning");
            redirectTo(WEBCLIENT_URL . '/modules/employees/appointments.php');
            exit;
        }

        $now = new DateTime();
        $rdvTime = new DateTime($reservation['date_rdv']);
        $interval = $now->diff($rdvTime);
        if ($now >= $rdvTime || ($interval->days == 0 && $interval->h < 24)) {
        }

        $creneau_to_release = null;
        try {
            $creneau_to_release = fetchOne(
                'consultation_creneaux',
                'prestation_id = :prestation_id AND praticien_id <=> :praticien_id AND start_time = :start_time AND is_booked = TRUE',
                [
                    ':prestation_id' => $reservation['prestation_id'],
                    ':praticien_id' => $reservation['praticien_id'],
                    ':start_time' => $reservation['date_rdv']
                ]
            );
            error_log("[DEBUG] handleCancelReservation: Recherche créneau pour RDV #{$reservation_id}. Trouvé: " . ($creneau_to_release ? "ID {$creneau_to_release['id']}" : "Non"));
        } catch (Exception $fetchEx) {
            error_log("Erreur recherche créneau pour RDV #{$reservation_id}: " . $fetchEx->getMessage());
        }

        $updated = updateRow(
            TABLE_APPOINTMENTS,
            ['statut' => 'annule'],
            "id = :id",
            [':id' => $reservation_id]
        );

        if ($updated) {
            logReservationActivity($employee_id, $reservation['prestation_id'], 'annulation', "RDV #$reservation_id annulé par l'employé");
            flashMessage("Votre réservation a été annulée.", "success");

            if ($creneau_to_release) {
                try {
                    $slot_updated = updateRow(
                        'consultation_creneaux',
                        ['is_booked' => 0],
                        'id = :id',
                        [':id' => $creneau_to_release['id']]
                    );
                    if ($slot_updated) {
                        error_log("[INFO] handleCancelReservation: Créneau #{$creneau_to_release['id']} libéré suite à annulation RDV #{$reservation_id}.");
                    } else {
                        error_log("[WARNING] handleCancelReservation: Échec MAJ is_booked=0 pour créneau #{$creneau_to_release['id']}.");
                    }
                } catch (Exception $slotEx) {
                    error_log("Erreur libération créneau #{$creneau_to_release['id']} suite à annulation RDV #{$reservation_id}: " . $slotEx->getMessage());
                }
            }

            $prestation = fetchOne(TABLE_PRESTATIONS, 'id = :id', [':id' => $reservation['prestation_id']]);
            $prestationNom = $prestation ? $prestation['nom'] : 'Inconnue';
            $notifData = [
                'personne_id' => $employee_id,
                'titre' => 'Réservation annulée',
                'message' => 'Votre réservation pour \'' . htmlspecialchars($prestationNom) . '\' prévue le ' . formatDate($reservation['date_rdv']) . ' a été annulée.',
                'type' => 'warning',
                'lien' => WEBCLIENT_URL . '/modules/employees/appointments.php'
            ];
            try {
                insertRow(TABLE_NOTIFICATIONS, $notifData);
            } catch (Exception $notifEx) {
                logSystemActivity('error', "Erreur création notif annulation RDV #$reservation_id: " . $notifEx->getMessage());
            }
        } else {
            flashMessage("Impossible d'annuler la réservation.", "danger");
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur annulation RDV #$reservation_id pour user #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de l'annulation.", "danger");
    }
}


function handleAnonymousReport($sujet, $description)
{

    $sujet = sanitizeInput(trim($sujet));
    $description = sanitizeInput(trim($description));

    if (empty($description)) {
        flashMessage("La description détaillée est obligatoire.", "danger");
        return false;
    }
    if (mb_strlen($sujet) > 255) {
        flashMessage("Le sujet est trop long (max 255 caractères).", "danger");
        return false;
    }
    if (!defined('MAX_REPORT_LENGTH')) {
        define('MAX_REPORT_LENGTH', 5000);
    }
    if (mb_strlen($description) > MAX_REPORT_LENGTH) {
        flashMessage("La description est trop longue (max " . MAX_REPORT_LENGTH . " caractères).", "danger");
        return false;
    }
    $reportData = [
        'sujet' => !empty($sujet) ? $sujet : 'Signalement Anonyme',
        'description' => $description,
        'statut' => 'nouveau',
        'created_at' => date('Y-m-d H:i:s')
    ];

    try {

        $insertedId = insertRow(TABLE_SIGNALEMENTS, $reportData);

        if ($insertedId) {
            $details_success = "Nouveau signalement anonyme soumis (ID: $insertedId) | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A');
            logSystemActivity('anonymous_report_submitted', $details_success);
            return true;
        } else {
            $details_fail = "Échec insertion signalement anonyme DB | Sujet: " . $sujet . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A');
            logSystemActivity('error', $details_fail);
            flashMessage("Erreur technique lors de la transmission de votre signalement (code 1).", "danger");
            return false;
        }
    } catch (Exception $e) {
        $details_exception = "Exception lors traitement signalement anonyme: " . $e->getMessage() . " | Sujet: " . $sujet . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A');
        logSystemActivity('error', $details_exception);
        flashMessage("Erreur technique lors de la transmission de votre signalement (code 2).", "danger");
        return false;
    }
}

function handleChangePassword($employee_id, $current_password, $new_password, $confirm_password)
{
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $employee_id) {
        logSecurityEvent($employee_id, 'invalid_action', 'Tentative changement MDP pour autre user (' . ($_SESSION['user_id'] ?? 'non connecté') . ')', true);
        flashMessage("Action non autorisée.", "danger");
        return false;
    }

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        flashMessage("Tous les champs de mot de passe sont requis.", "danger");
        return false;
    }

    if ($new_password !== $confirm_password) {
        flashMessage("Le nouveau mot de passe et sa confirmation ne correspondent pas.", "danger");
        return false;
    }

    if (!defined('MIN_PASSWORD_LENGTH')) {
        define('MIN_PASSWORD_LENGTH', 8);
    }
    if (strlen($new_password) < MIN_PASSWORD_LENGTH) {
        flashMessage("Le nouveau mot de passe doit comporter au moins " . MIN_PASSWORD_LENGTH . " caractères.", "danger");
        return false;
    }

    if ($current_password === $new_password) {
        flashMessage("Le nouveau mot de passe doit être différent de l'ancien.", "warning");
        return false;
    }

    try {
        $employee = fetchOne(TABLE_USERS, 'id = :id', [':id' => $employee_id], 'mot_de_passe');
        if (!$employee || !isset($employee['mot_de_passe'])) {
            flashMessage("Utilisateur non trouvé.", "danger");
            return false;
        }

        if (!password_verify($current_password, $employee['mot_de_passe'])) {
            flashMessage("Le mot de passe actuel fourni est incorrect.", "danger");
            logSecurityEvent($employee_id, 'password_change_fail', 'Échec changement MDP: mot de passe actuel incorrect');
            return false;
        }

        $newPasswordHash = password_hash($new_password, PASSWORD_DEFAULT);
        if (!$newPasswordHash) {
            logSystemActivity('error', "Erreur hachage nouveau mot de passe pour employé #$employee_id");
            flashMessage('Une erreur technique est survenue lors de la préparation du nouveau mot de passe.', 'danger');
            return false;
        }

        $updateData = ['mot_de_passe' => $newPasswordHash, 'updated_at' => date('Y-m-d H:i:s')];
        $affectedRows = updateRow(TABLE_USERS, $updateData, 'id = :id', [':id' => $employee_id]);

        if ($affectedRows > 0) {
            logSecurityEvent($employee_id, 'password_change_success', "Mot de passe modifié avec succès par l'employé");
            flashMessage("Votre mot de passe a été modifié avec succès.", "success");
            return true;
        } else {
            flashMessage("Une erreur est survenue lors de la mise à jour du mot de passe.", "warning");
            logSystemActivity('warning', "handleChangePassword: updateRow a retourné 0 pour employé #$employee_id");
            return false;
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur handleChangePassword pour employé #$employee_id: " . $e->getMessage());
        flashMessage('Une erreur technique est survenue lors du changement de mot de passe.', 'danger');
        return false;
    }
}

function handleRegisterForEvent($employee_id, $event_id)
{
    error_log("[HRE_DEBUG] Début handleRegisterForEvent pour employee #$employee_id, event #$event_id");
    try {
        $event = fetchOne(TABLE_EVENTS, "id = :id", ['id' => $event_id]);
        if (!$event) {
            error_log("[HRE_ERROR] Événement #$event_id non trouvé.");
            flashMessage("Événement non trouvé.", "warning");
            return false;
        }
        error_log("[HRE_DEBUG] Événement trouvé: " . print_r($event, true));

        $existingRegistration = fetchOne(TABLE_EVENT_REGISTRATIONS, "personne_id = :pid AND evenement_id = :eid AND statut = 'inscrit'", [':pid' => $employee_id, ':eid' => $event_id]);
        if ($existingRegistration) {
            error_log("[HRE_INFO] Employé #$employee_id déjà inscrit à l'événement #$event_id.");
            flashMessage("Vous êtes déjà inscrit à cet événement.", "info");
            return false;
        }
        error_log("[HRE_DEBUG] Pas d'inscription active trouvée pour employee #$employee_id.");

        if (isset($event['capacite_max']) && $event['capacite_max'] !== null) {
            $currentRegistrations = countTableRows(TABLE_EVENT_REGISTRATIONS, "evenement_id = :eid AND statut = 'inscrit'", [':eid' => $event_id]);
            error_log("[HRE_DEBUG] Capacité max: {$event['capacite_max']}, Inscriptions actuelles: $currentRegistrations pour event #$event_id");
            if ($currentRegistrations >= $event['capacite_max']) {
                error_log("[HRE_WARN] Événement #$event_id est complet (Capacité: {$event['capacite_max']}, Inscrits: $currentRegistrations).");
                flashMessage("Cet événement est complet.", "warning");
                return false;
            }
        }

        $cancelledRegistration = fetchOne(TABLE_EVENT_REGISTRATIONS, "personne_id = :pid AND evenement_id = :eid AND statut = 'annule'", [':pid' => $employee_id, ':eid' => $event_id]);
        error_log("[HRE_DEBUG] Inscription annulée trouvée: " . ($cancelledRegistration ? 'Oui (ID: ' . $cancelledRegistration['id'] . ')' : 'Non'));

        error_log("[HRE_DEBUG] Début transaction.");
        beginTransaction();

        $success = false;
        $registrationId = null;

        if ($cancelledRegistration) {
            error_log("[HRE_DEBUG] Tentative de réactivation de l'inscription ID: {$cancelledRegistration['id']}");
            $updated = updateRow(TABLE_EVENT_REGISTRATIONS, ['statut' => 'inscrit', 'updated_at' => date('Y-m-d H:i:s')], "id = :id", [':id' => $cancelledRegistration['id']]);
            $success = (bool)$updated;
            $registrationId = $cancelledRegistration['id'];
            error_log("[HRE_DEBUG] Résultat updateRow pour réactivation: " . ($success ? 'Succès' : 'Échec'));
        } else {
            $registrationData = [
                'personne_id' => $employee_id,
                'evenement_id' => $event_id,
                'statut' => 'inscrit'
            ];
            error_log("[HRE_DEBUG] Tentative d'insertion nouvelle inscription. Données: " . print_r($registrationData, true));
            $insertedId = insertRow(TABLE_EVENT_REGISTRATIONS, $registrationData);
            $success = (bool)$insertedId;
            if ($success && is_numeric($insertedId)) {
                $registrationId = $insertedId;
            } elseif (!$success && $insertedId !== false) {
                $registrationId = 'N/A (Insert a retourné true)';
            } else {
                $registrationId = 'N/A (Insert a échoué)';
            }

            error_log("[HRE_DEBUG] Résultat insertRow pour nouvelle inscription: " . ($success ? "Succès (ID: $registrationId)" : 'Échec'));
        }

        if ($success) {
            commitTransaction();
            logBusinessOperation($employee_id, 'event_registration', "Inscription à l'événement #$event_id (Reg ID: $registrationId)");
            flashMessage("Inscription à l'événement réussie !", "success");

            $eventTitre = $event ? $event['titre'] : 'Inconnu';
            $notifData = [
                'personne_id' => $employee_id,
                'titre' => 'Inscription événement réussie',
                'message' => 'Vous êtes maintenant inscrit à l\'événement : \'' . htmlspecialchars($eventTitre) . '\'.',
                'type' => 'success',
                'lien' => WEBCLIENT_URL . '/modules/employees/events.php?id=' . $event_id
            ];
            try {
                insertRow(TABLE_NOTIFICATIONS, $notifData);
            } catch (Exception $notifEx) {
                logSystemActivity('error', "Erreur création notif inscription event #$event_id: " . $notifEx->getMessage());
            }

            return true;
        } else {
            rollbackTransaction();
            logSystemActivity('error', "Échec inscription événement #$event_id pour employé #$employee_id");
            flashMessage("Une erreur technique est survenue lors de l'inscription.", "danger");
            return false;
        }
    } catch (Exception $e) {
        if (getDbConnection()->inTransaction()) {
            rollbackTransaction();
        }
        logSystemActivity('error', "Exception inscription événement #$event_id pour employé #$employee_id: " . $e->getMessage());
        flashMessage("Erreur technique lors de l'inscription.", "danger");
        return false;
    }
}
function handleUnregisterFromEvent($employee_id, $event_id)
{
    try {
        $registration = fetchOne(TABLE_EVENT_REGISTRATIONS, "personne_id = :pid AND evenement_id = :eid AND statut = 'inscrit'", [':pid' => $employee_id, ':eid' => $event_id]);
        if (!$registration) {
            flashMessage("Vous n'êtes pas inscrit à cet événement ou l'inscription est déjà annulée.", "warning");
            return false;
        }


        $updated = updateRow(TABLE_EVENT_REGISTRATIONS, ['statut' => 'annule', 'updated_at' => date('Y-m-d H:i:s')], "id = :id", [':id' => $registration['id']]);

        if ($updated) {
            logBusinessOperation($employee_id, 'event_unregistration', "Désinscription de l'événement #$event_id (Reg ID: {$registration['id']})");
            flashMessage("Désinscription de l'événement réussie.", "success");

            $event = fetchOne(TABLE_EVENTS, "id = :id", ['id' => $event_id]);
            $eventTitre = $event ? $event['titre'] : 'Inconnu';
            $notifData = [
                'personne_id' => $employee_id,
                'titre' => 'Désinscription événement',
                'message' => 'Vous avez été désinscrit de l\'événement : \'' . htmlspecialchars($eventTitre) . '\'.',
                'type' => 'warning',
                'lien' => WEBCLIENT_URL . '/modules/employees/events.php?id=' . $event_id
            ];
            try {
                insertRow(TABLE_NOTIFICATIONS, $notifData);
            } catch (Exception $notifEx) {
                logSystemActivity('error', "Erreur création notif désinscription event #$event_id: " . $notifEx->getMessage());
            }

            return true;
        } else {
            logSystemActivity('error', "Échec désinscription événement #$event_id pour employé #$employee_id");
            flashMessage("Une erreur technique est survenue lors de la désinscription.", "danger");
            return false;
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Exception désinscription événement #$event_id pour employé #$employee_id: " . $e->getMessage());
        flashMessage("Erreur technique lors de la désinscription.", "danger");
        return false;
    }
}

function getEventIcon($eventType)
{
    $eventType = strtolower($eventType);
    $iconMap = [
        'atelier' => 'fas fa-tools',
        'formation' => 'fas fa-chalkboard-teacher',
        'conference' => 'fas fa-microphone-alt',
        'seminaire' => 'fas fa-project-diagram',
        'team building' => 'fas fa-users',
        'webinaire' => 'fas fa-laptop',
        'social' => 'fas fa-glass-cheers',
        'sportif' => 'fas fa-futbol',
        'culturel' => 'fas fa-palette',
        'autre' => 'fas fa-calendar-alt'
    ];

    return $iconMap[$eventType] ?? $iconMap['autre'];
}

function timeAgo($timestamp)
{
    $currentTime = time();
    $timeDiff = $currentTime - $timestamp;

    $intervals = [
        'année'   => 31536000,
        'mois'    => 2592000,
        'semaine' => 604800,
        'jour'    => 86400,
        'heure'   => 3600,
        'minute'  => 60,
        'seconde' => 1
    ];

    if ($timeDiff < 0) {
        return 'dans le futur';
    }

    if ($timeDiff < 10) {
        return 'à l\'instant';
    }

    foreach ($intervals as $label => $seconds) {
        $count = floor($timeDiff / $seconds);
        if ($count >= 1) {
            $plural = ($label !== 'mois' && $count > 1) ? 's' : '';
            return 'il y a ' . $count . ' ' . $label . $plural;
        }
    }

    return 'date inconnue';
}

function handleNewCommunityPost($community_id, $employee_id, $message_content)
{
    $message_content = sanitizeInput(trim($message_content ?? ''));

    if (empty($message_content)) {
        flashMessage("Le contenu du message ne peut pas être vide.", "danger");
        return false;
    }

    if (mb_strlen($message_content) > 2000) {
        flashMessage("Le message est trop long (max 2000 caractères).", "danger");
        return false;
    }

    try {
        $community = fetchOne(TABLE_COMMUNAUTES, 'id = :id', [':id' => $community_id]);
        if (!$community) {
            flashMessage("La communauté spécifiée n'existe pas.", "danger");
            return false;
        }


        $postData = [
            'communaute_id' => $community_id,
            'personne_id' => $employee_id,
            'message' => $message_content,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $postId = insertRow(TABLE_COMMUNITY_POSTS, $postData);

        if ($postId) {
            logBusinessOperation($employee_id, 'community_post', "Nouveau message (ID: $postId) posté dans la communauté #$community_id");
            flashMessage("Votre message a été envoyé avec succès.", "success");

            $communityNom = $community ? $community['nom'] : 'Inconnue';
            $notifData = [
                'personne_id' => $employee_id,
                'titre' => 'Nouveau message posté',
                'message' => 'Votre message a été ajouté à la communauté \'' . htmlspecialchars($communityNom) . '\'.',
                'type' => 'info',
                'lien' => WEBCLIENT_URL . '/modules/employees/communities.php?id=' . $community_id . '#post-' . $postId
            ];
            try {
                insertRow(TABLE_NOTIFICATIONS, $notifData);
            } catch (Exception $notifEx) {
                logSystemActivity('error', "Erreur création notif nouveau post communauté #$community_id: " . $notifEx->getMessage());
            }

            return true;
        } else {
            logSystemActivity('error', "Échec insertion message communauté #$community_id pour employé #$employee_id");
            flashMessage("Une erreur technique est survenue lors de l'envoi du message.", "danger");
            return false;
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Exception envoi message communauté #$community_id pour employé #$employee_id: " . $e->getMessage());
        flashMessage("Erreur technique lors de l'envoi du message.", "danger");
        return false;
    }
}


function displayCommunityDetailsPageData($community_id)
{
    $current_employee_id = $_SESSION['user_id'] ?? null;
    $data = ['community' => null, 'posts' => [], 'memberCommunityIds' => []];

    if (!$current_employee_id) {
        flashMessage("Erreur: Utilisateur non identifié.", "danger");
        return $data;
    }

    try {
        $community = fetchOne(TABLE_COMMUNAUTES, 'id = :id', [':id' => $community_id]);
        if (!$community) {
            return $data;
        }
        $data['community'] = $community;

        $queryPosts = "SELECT cp.*, p.nom as auteur_nom, p.prenom as auteur_prenom
                       FROM " . TABLE_COMMUNITY_POSTS . " cp
                       JOIN " . TABLE_USERS . " p ON cp.personne_id = p.id
                       WHERE cp.communaute_id = :community_id
                       ORDER BY cp.created_at DESC
                       LIMIT 50";
        $posts = executeQuery($queryPosts, [':community_id' => $community_id])->fetchAll();

        foreach ($posts as &$post) {
            $post['created_at_formatted'] = isset($post['created_at']) ? timeAgo(strtotime($post['created_at'])) : 'Date inconnue';
            $post['auteur_nom'] = trim(($post['auteur_prenom'] ?? '') . ' ' . ($post['auteur_nom'] ?? ''));
            if (empty($post['auteur_nom'])) {
                $post['auteur_nom'] = 'Auteur Anonyme';
            }
        }
        $data['posts'] = $posts;

        $memberQuery = "SELECT communaute_id FROM " . TABLE_COMMUNITY_MEMBERS . " WHERE personne_id = :pid";
        $memberStmt = executeQuery($memberQuery, [':pid' => $current_employee_id]);
        $data['memberCommunityIds'] = $memberStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        if ($data['memberCommunityIds'] === false) {
            error_log("Avertissement: Impossible de récupérer les adhésions aux communautés pour l'employé #$current_employee_id dans displayCommunityDetailsPageData.");
            $data['memberCommunityIds'] = [];
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur chargement détails communauté #$community_id: " . $e->getMessage());
        flashMessage("Impossible de charger les détails de la communauté.", "danger");
        $data['community'] = null;
        $data['posts'] = [];
        $data['memberCommunityIds'] = [];
    }

    return $data;
}

function getCommunityIcon($communityType)
{
    $communityType = strtolower($communityType ?? 'autre');
    $iconMap = [
        'sport' => 'fas fa-futbol',
        'bien_etre' => 'fas fa-spa',
        'sante' => 'fas fa-heartbeat',
        'loisir' => 'fas fa-gamepad',
        'professionnel' => 'fas fa-briefcase',
        'culture' => 'fas fa-palette',
        'technologie' => 'fas fa-laptop-code',
        'entraide' => 'fas fa-hands-helping',
        'autre' => 'fas fa-users'
    ];
    return $iconMap[$communityType] ?? $iconMap['autre'];
}


function handleCounselPreferencesUpdate(array $postData, int $employee_id): bool
{

    $selectedCategories = $postData['categories'] ?? [];
    if (!is_array($selectedCategories)) {
        $selectedCategories = [];
    }
    $sanitizedCategories = array_map('sanitizeInput', $selectedCategories);

    try {
        beginTransaction();
        deleteRow('utilisateur_interets_conseils', 'personne_id = :pid', [':pid' => $employee_id]);

        if (!empty($sanitizedCategories)) {
            $sqlInsert = "INSERT INTO utilisateur_interets_conseils (personne_id, categorie_conseil) VALUES (:pid, :cat)";
            $stmt = getDbConnection()->prepare($sqlInsert);
            foreach ($sanitizedCategories as $category) {
                $stmt->execute([':pid' => $employee_id, ':cat' => $category]);
            }
        }
        commitTransaction();
        logActivity($employee_id, 'update_counsel_preferences', 'Préférences de conseils mises à jour.');
        flashMessage("Vos préférences de conseils ont été mises à jour.", "success");
        return true;
    } catch (Exception $e) {
        if (getDbConnection()->inTransaction()) {
            rollbackTransaction();
        }
        logSystemActivity('error', "Erreur MAJ préférences conseils pour user #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de la mise à jour de vos préférences.", "danger");
        return false;
    }
}


function getCounselPageData(int $employee_id): array
{
    $data = [
        'personalizedTopics' => [],
        'generalTopics' => [],
        'availableCategories' => [],
        'userPreferences' => [],
        'dbError' => null
    ];

    try {
        $catQuery = "SELECT DISTINCT categorie FROM conseils WHERE categorie IS NOT NULL AND categorie != '' ORDER BY categorie ASC";
        $data['availableCategories'] = executeQuery($catQuery)->fetchAll(PDO::FETCH_COLUMN);

        $prefQuery = "SELECT categorie_conseil FROM utilisateur_interets_conseils WHERE personne_id = :pid";
        $data['userPreferences'] = executeQuery($prefQuery, [':pid' => $employee_id])->fetchAll(PDO::FETCH_COLUMN);

        $allTopics = fetchAll('conseils', '', 'categorie ASC, titre ASC');

        if (!empty($data['userPreferences'])) {
            foreach ($allTopics as $topic) {
                if (isset($topic['categorie']) && in_array($topic['categorie'], $data['userPreferences'])) {
                    $data['personalizedTopics'][] = $topic;
                } else {
                    $data['generalTopics'][] = $topic;
                }
            }
        } else {
            $data['generalTopics'] = $allTopics;
        }
    } catch (Exception $e) {
        error_log("Error fetching data for counsel page (user #$employee_id): " . $e->getMessage());
        $data['dbError'] = "Impossible de charger les données des conseils pour le moment. Veuillez réessayer plus tard.";
    }

    return $data;
}

function getAvailableSlotsForDate($prestation_id, $date)
{
    $prestation_id = filter_var(sanitizeInput($prestation_id), FILTER_VALIDATE_INT);
    $date = sanitizeInput($date);
    $employee_id = $_SESSION['user_id'] ?? null;

    if (!$prestation_id || !$date || !$employee_id) {
        error_log("getAvailableSlotsForDate: Paramètres manquants ou invalides.");
        return [];
    }

    $employeeData = fetchOne(TABLE_USERS, 'id = :id', [':id' => $employee_id], ['site_id']);
    $employee_site_id = $employeeData ? $employeeData['site_id'] : null;


    try {
        $sql = "SELECT cc.id, cc.heure_debut, cc.heure_fin, cc.reserve
                FROM " . TABLE_CONSULTATION_CRENEAUX . " cc
                WHERE cc.prestation_id = :prestation_id
                  AND DATE(cc.heure_debut) = :date
                  AND cc.reserve = FALSE";

        $params = [
            ':prestation_id' => $prestation_id,
            ':date' => $date
        ];

        if ($employee_site_id !== null) {
            $sql .= " AND (cc.site_id IS NULL OR cc.site_id = :employee_site_id)";
            $params[':employee_site_id'] = $employee_site_id;
        } else {
            $sql .= " AND cc.site_id IS NULL";
        }

        $sql .= " ORDER BY cc.heure_debut ASC";


        $stmt = executeQuery($sql, $params);
        $slots = $stmt->fetchAll();

        return $slots;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur getAvailableSlotsForDate pour prestation #$prestation_id, date $date: " . $e->getMessage());
        return [];
    }
}


function getAllAvailableSlotsGroupedByDate($prestation_id)
{
    $prestation_id = filter_var(sanitizeInput($prestation_id), FILTER_VALIDATE_INT);
    $employee_id = $_SESSION['user_id'] ?? null;


    if (!$prestation_id || !$employee_id) {
        error_log("getAllAvailableSlotsGroupedByDate: Paramètres manquants ou invalides.");
        return [];
    }

    $employeeData = fetchOne(TABLE_USERS, 'id = :id', [':id' => $employee_id], ['site_id']);
    $employee_site_id = $employeeData ? $employeeData['site_id'] : null;

    $groupedSlots = [];
    try {
        $sql = "SELECT DATE(cc.heure_debut) as date_slot, COUNT(cc.id) as available_slots
                FROM " . TABLE_CONSULTATION_CRENEAUX . " cc
                WHERE cc.prestation_id = :prestation_id
                  AND cc.reserve = FALSE
                  AND cc.heure_debut > NOW()";

        $params = [':prestation_id' => $prestation_id];

        if ($employee_site_id !== null) {
            $sql .= " AND (cc.site_id IS NULL OR cc.site_id = :employee_site_id)";
            $params[':employee_site_id'] = $employee_site_id;
        } else {
            $sql .= " AND cc.site_id IS NULL";
        }

        $sql .= " GROUP BY date_slot
                 HAVING available_slots > 0
                 ORDER BY date_slot ASC";

        $stmt = executeQuery($sql, $params);
        while ($row = $stmt->fetch()) {
            $groupedSlots[$row['date_slot']] = $row['available_slots'];
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur getAllAvailableSlotsGroupedByDate pour prestation #$prestation_id: " . $e->getMessage());
        return [];
    }
    return $groupedSlots;
}

function processSlotBooking($employee_id, $creneau_id)
{
    $employee_id = filter_var($employee_id, FILTER_VALIDATE_INT);
    $creneau_id = filter_var($creneau_id, FILTER_VALIDATE_INT);

    if (!$employee_id || !$creneau_id) {
        logSystemActivity('error', "processSlotBooking: ID Salarié ou Créneau invalide.", ['eid' => $employee_id, 'cid' => $creneau_id]);
        flashMessage("Données invalides pour la réservation.", "danger");
        return false;
    }

    try {
        beginTransaction();

        $slot = fetchOne('consultation_creneaux', "id = :id AND is_booked = FALSE", [':id' => $creneau_id]);

        if (!$slot) {
            rollbackTransaction();
            flashMessage("Ce créneau n'est plus disponible ou a déjà été réservé.", "warning");
            return false;
        }

        if (strtotime($slot['start_time']) <= time()) {
            rollbackTransaction();
            flashMessage("Impossible de réserver un créneau dans le passé.", "warning");
            return false;
        }

        $updated = updateRow('consultation_creneaux', ['is_booked' => TRUE], "id = :id", [':id' => $creneau_id]);

        if (!$updated) {
            rollbackTransaction();
            logSystemActivity('error', "processSlotBooking: Échec MAJ is_booked pour créneau #$creneau_id");
            flashMessage("Erreur technique lors de la réservation du créneau (code PB01).", "danger");
            return false;
        }

        $prestation = fetchOne('prestations', "id = :id", [':id' => $slot['prestation_id']]);
        if (!$prestation || !isset($prestation['duree'])) {
            rollbackTransaction();
            logSystemActivity('error', "processSlotBooking: Impossible de trouver la durée pour prestation #{$slot['prestation_id']}");
            flashMessage("Erreur technique lors de la récupération des détails de la prestation (code PB02).", "danger");
            return false;
        }

        $rdvData = [
            'personne_id' => $employee_id,
            'prestation_id' => $slot['prestation_id'],
            'praticien_id' => $slot['praticien_id'],
            'date_rdv' => $slot['start_time'],
            'duree' => $prestation['duree'],
            'lieu' => 'À définir',
            'type_rdv' => 'consultation',
            'statut' => 'planifie',
            'notes' => 'Réservé via catalogue de services'
        ];
        $appointmentId = insertRow('rendez_vous', $rdvData);

        if (!$appointmentId) {
            rollbackTransaction();
            logSystemActivity('error', "processSlotBooking: Échec insertion rendez_vous pour créneau #$creneau_id, user #$employee_id");
            flashMessage("Erreur technique lors de la création du rendez-vous (code PB03).", "danger");
            return false;
        }

        commitTransaction();

        logReservationActivity($employee_id, $slot['prestation_id'], 'creation_via_creneau', "RDV #$appointmentId créé depuis créneau #$creneau_id");
        flashMessage("Votre rendez-vous pour '" . htmlspecialchars($prestation['nom']) . "' le " . formatDate($slot['start_time']) . " a été réservé avec succès !", "success");
        return true;
    } catch (Exception $e) {
        if (getDbConnection()->inTransaction()) {
            rollbackTransaction();
        }
        error_log("Full exception details for PB04: " . (string)$e);
        logSystemActivity('error', "Exception processSlotBooking pour créneau #$creneau_id, user #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique majeure est survenue lors de la réservation (code PB04).", "danger");
        return false;
    }
}
function getServiceCatalogPageData($action, $selected_prestation_id, $selected_date)
{

    $data = [
        'action' => $action,
        'services' => [],
        'paginationHtml' => '',
        'types' => [],
        'categories' => [],
        'currentTypeFilter' => $_GET['type'] ?? '',
        'currentCategoryFilter' => $_GET['categorie'] ?? '',
        'hasActiveContract' => false,
        'selected_prestation_id' => $selected_prestation_id,
        'selected_date' => $selected_date,
        'prestation_details' => null,
        'available_slots' => [],
        'grouped_slots' => [],
        'pageTitle' => 'Catalogue des Services - Espace Salarié',
        'csrfToken' => $_SESSION['csrf_token'] ?? generateToken()
    ];

    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];
    $employee = fetchOne(TABLE_USERS, "id = :id", [':id' => $employee_id]);
    if (!$employee || !$employee['entreprise_id']) {
        flashMessage("Accès refusé: informations utilisateur incomplètes.", "danger");
        redirectTo(WEBCLIENT_URL . '/dashboard.php');
        exit;
    }
    $activeContract = fetchOne(
        TABLE_CONTRACTS,
        "entreprise_id = :company_id AND statut = :status AND (date_fin IS NULL OR date_fin >= CURDATE())",
        [':company_id' => $employee['entreprise_id'], ':status' => STATUS_ACTIVE]
    );
    $data['hasActiveContract'] = (bool)$activeContract;

    if ($action === 'list') {
        if (!$data['hasActiveContract']) {
            flashMessage("Votre entreprise n'a pas de contrat actif pour accéder aux services.", "warning");
            return $data;
        }
        $pageDataList = displayServiceCatalog();
        $data['services'] = $pageDataList['services'] ?? [];
        $data['paginationHtml'] = $pageDataList['pagination_html'] ?? '';
        $data['types'] = $pageDataList['types'] ?? [];
        $data['categories'] = $pageDataList['categories'] ?? [];
    } elseif (($action === 'select_date' || $action === 'select_slot' || $action === 'book_slot') && $selected_prestation_id) {
        $data['prestation_details'] = fetchOne('prestations', 'id = :id', [':id' => $selected_prestation_id]);
        if (!$data['prestation_details']) {
            flashMessage("Prestation non trouvée.", "danger");
            redirectTo(WEBCLIENT_URL . '/modules/employees/services.php');
            exit;
        }
        $prestationNom = isset($data['prestation_details']['nom']) ? htmlspecialchars($data['prestation_details']['nom']) : 'Prestation inconnue';
        $data['pageTitle'] = 'Réserver : ' . $prestationNom;

        if ($action === 'select_date') {
            $data['grouped_slots'] = getAllAvailableSlotsGroupedByDate($selected_prestation_id);
            $prestationNomDate = isset($data['prestation_details']['nom']) ? htmlspecialchars($data['prestation_details']['nom']) : 'Prestation inconnue';
            $data['pageTitle'] = 'Choisir un créneau pour : ' . $prestationNomDate;
        }
    }

    return $data;
}

function handleLeaveCommunity(int $community_id, int $employee_id): bool
{
    $tableName = TABLE_COMMUNITY_MEMBERS;
    try {
        $deleted = deleteRow(
            $tableName,
            'communaute_id = :cid AND personne_id = :pid',
            [':cid' => $community_id, ':pid' => $employee_id]
        );

        if ($deleted) {
            logActivity($employee_id, 'community_leave_success', "A quitté la communauté #$community_id");

            $community = fetchOne(TABLE_COMMUNAUTES, 'id = :id', [':id' => $community_id]);
            $communityNom = $community ? $community['nom'] : 'Inconnue';
            $notifData = [
                'personne_id' => $employee_id,
                'titre' => 'Départ de communauté',
                'message' => 'Vous avez quitté la communauté \'' . htmlspecialchars($communityNom) . '\'.',
                'type' => 'warning',
                'lien' => WEBCLIENT_URL . '/modules/employees/communities.php'
            ];
            try {
                insertRow(TABLE_NOTIFICATIONS, $notifData);
            } catch (Exception $notifEx) {
                logSystemActivity('error', "Erreur création notif départ communauté #$community_id: " . $notifEx->getMessage());
            }

            return true;
        } else {
            logActivity($employee_id, 'community_leave_noop', "Tentative de quitter la communauté #$community_id mais l'enregistrement n'a pas été trouvé ou une erreur s'est produite.");
            return false;
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Exception en quittant la communauté #$community_id pour employé #$employee_id: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        flashMessage("Erreur technique lors de la tentative de quitter la communauté.", "danger");
        return false;
    }
}


function handleDeleteCommunityPost(int $post_id, int $employee_id): bool
{
    try {
        $post = fetchOne(TABLE_COMMUNITY_POSTS, 'id = :post_id', [':post_id' => $post_id]);

        if (!$post) {
            flashMessage("Le message à supprimer n'existe pas.", "warning");
            return false;
        }

        if ($post['personne_id'] != $employee_id) {
            flashMessage("Vous n'êtes pas autorisé à supprimer ce message.", "danger");
            logSecurityEvent($employee_id, 'delete_community_post_unauthorized', "Tentative de suppression du post #$post_id par un utilisateur non autorisé.");
            return false;
        }

        $deleted = deleteRow(TABLE_COMMUNITY_POSTS, 'id = :post_id AND personne_id = :employee_id', [':post_id' => $post_id, ':employee_id' => $employee_id]);

        if ($deleted) {
            logBusinessOperation($employee_id, 'community_post_delete', "Message #$post_id supprimé par son auteur.");
            flashMessage("Le message a été supprimé avec succès.", "success");
            return true;
        } else {
            logSystemActivity('error', "Échec suppression message communauté #$post_id par employé #$employee_id (deleteRow a échoué).");
            flashMessage("Une erreur technique est survenue lors de la suppression du message.", "danger");
            return false;
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Exception suppression message communauté #$post_id pour employé #$employee_id: " . $e->getMessage());
        flashMessage("Erreur technique lors de la suppression du message.", "danger");
        return false;
    }
}

function handleCommunityPostRequest(array $postData, ?int $current_employee_id, string $sessionCsrfToken): void
{
    $action = $postData['action'] ?? null;
    $posted_community_id = isset($postData['community_id']) ? filter_var($postData['community_id'], FILTER_VALIDATE_INT) : null;
    $submitted_csrf_token = $postData['csrf_token'] ?? null;

    $redirectUrl = WEBCLIENT_URL . '/modules/employees/communities.php';
    if ($posted_community_id) {
        $redirectUrl = WEBCLIENT_URL . '/modules/employees/communities.php?id=' . $posted_community_id;
    }

    if (!validateToken($submitted_csrf_token)) {
        logSecurityEvent($current_employee_id, 'csrf_failure', '[SECURITY FAILURE] Tentative POST avec jeton invalide sur communities.php');
        flashMessage("Erreur de sécurité (jeton invalide).", "danger");
        redirectTo($redirectUrl);
        exit;
    }

    requireRole(ROLE_SALARIE);

    switch ($action) {
        case 'join_community':
            if ($posted_community_id && $current_employee_id) {
                handleJoinCommunity($posted_community_id, $current_employee_id);
            } else {
                if (!$posted_community_id) flashMessage("ID de communauté manquant pour rejoindre.", "danger");
                if (!$current_employee_id) flashMessage("ID employé manquant.", "danger");
            }
            break;

        case 'post_message':
            if ($posted_community_id) {
                $message_content = $postData['message'] ?? null;
                handleNewCommunityPost($posted_community_id, $current_employee_id, $message_content);
            } else {
                flashMessage("ID de communauté manquant pour poster un message.", "danger");
            }
            break;

        case 'leave_community':
            if ($posted_community_id) {
                $left = handleLeaveCommunity($posted_community_id, $current_employee_id);
                if ($left) {
                    $redirectUrl = WEBCLIENT_URL . '/modules/employees/communities.php';
                }
            } else {
                flashMessage("ID de communauté manquant pour quitter.", "danger");
            }
            break;

        case 'delete_community_post':
            $post_id_to_delete = isset($postData['post_id']) ? filter_var($postData['post_id'], FILTER_VALIDATE_INT) : null;
            if ($post_id_to_delete && $posted_community_id) {
                handleDeleteCommunityPost($post_id_to_delete, $current_employee_id);
            } else {
                if (!$post_id_to_delete) flashMessage("ID de message manquant pour la suppression.", "danger");
                if (!$posted_community_id) flashMessage("Contexte de communauté manquant pour la suppression.", "danger");
            }
            break;

        default:
            flashMessage("Action invalide ou données manquantes.", "danger");
            break;
    }

    redirectTo($redirectUrl);
    exit;
}

function handleJoinCommunity(int $community_id, int $employee_id): bool
{
    $tableName = TABLE_COMMUNITY_MEMBERS;
    try {
        $existingMembership = fetchOne($tableName, 'communaute_id = :cid AND personne_id = :pid', [':cid' => $community_id, ':pid' => $employee_id]);
        if ($existingMembership) {
            flashMessage("Vous êtes déjà membre de cette communauté.", "info");
            return true;
        }
        $community = fetchOne(TABLE_COMMUNAUTES, 'id = :id', [':id' => $community_id]);
        if (!$community) {
            flashMessage("La communauté que vous essayez de rejoindre n'existe pas.", "warning");
            return false;
        }

        $joinData = [
            'communaute_id' => $community_id,
            'personne_id' => $employee_id,
            'date_adhesion' => date('Y-m-d H:i:s')
        ];

        $inserted = insertRow($tableName, $joinData);

        if ($inserted) {
            try {
                logActivity($employee_id, 'community_join_success', "A rejoint la communauté #$community_id");
            } catch (Exception $logEx) {
                logSystemActivity('error', "Erreur dans logActivity après join community #$community_id: " . $logEx->getMessage());
            }
            return true;
        } else {
            $failMsg = "[DEBUG handleJoinCommunity] insertRow for table '$tableName' returned false. Data: " . json_encode($joinData);
            error_log($failMsg);

            logSystemActivity('error', "Échec insertion adhésion communauté #$community_id pour employé #$employee_id (insertRow returned false).");
            flashMessage("Une erreur technique est survenue lors de la tentative de rejoindre la communauté (Code JI1).", "danger");
            return false;
        }
    } catch (Exception $e) {
        error_log("[handleJoinCommunity EXCEPTION] Type: " . get_class($e) . ", Message: " . $e->getMessage() . " IN FILE " . $e->getFile() . " ON LINE " . $e->getLine());

        if (str_contains($e->getMessage(), 'Duplicate entry')) {
            flashMessage("Vous êtes déjà membre de cette communauté (erreur détectée).", "info");
            return true;
        }

        logSystemActivity('error', "Exception en rejoignant la communauté #$community_id pour employé #$employee_id: " . $e->getMessage());

        flashMessage("Erreur technique lors de la tentative de rejoindre la communauté (Code JE1).", "danger");
        return false;
    }
}

function displayEmployeeNotificationsPage(int $employee_id, int $page = 1, int $limit = 15): array
{
    $defaultResult = [
        'notifications' => [],
        'pagination_html' => '',
        'pagination_data' => ['currentPage' => $page, 'totalPages' => 0, 'totalItems' => 0, 'perPage' => $limit]
    ];

    if (!defined('TABLE_NOTIFICATIONS')) {
        error_log("TABLE_NOTIFICATIONS constant is not defined.");
        flashMessage("Configuration error: Table des notifications non définie.", "danger");
        return $defaultResult;
    }

    if (!function_exists('paginateResults') || !function_exists('renderPagination') || !function_exists('timeAgo') || !function_exists('updateRow') || !function_exists('logActivity')) {
        error_log("One or more required helper functions (paginateResults, renderPagination, timeAgo, updateRow, logActivity) are missing.");
        flashMessage("Erreur interne: Fonctions d'aide manquantes.", "danger");
        return $defaultResult;
    }

    try {
        $updateData = [
            'lu' => 1,
            'date_lecture' => date('Y-m-d H:i:s')
        ];
        $tableName = TABLE_NOTIFICATIONS;
        try {
            updateRow(
                $tableName,
                $updateData,
                'personne_id = :pid AND lu = 0',
                [':pid' => $employee_id]
            );
        } catch (Exception $updateEx) {
            error_log("Error marking notifications as read for user #$employee_id: " . $updateEx->getMessage());
        }

        logActivity($employee_id, 'notifications_read', 'Consultation de la page des notifications.');


        $where = "personne_id = :id";
        $params = [':id' => $employee_id];
        $orderBy = "created_at DESC";

        $paginationResult = paginateResults($tableName, $page, $limit, $where, $orderBy, $params);

        $notifications = $paginationResult['items'] ?? [];
        foreach ($notifications as &$notif) {
            $notif['created_at_formatted'] = isset($notif['created_at']) ? timeAgo(strtotime($notif['created_at'])) : ($notif['created_at'] ?? 'Date inconnue');
            $notif['icon_class'] = getNotificationIcon($notif['type'] ?? 'info');
            $notif['bg_class'] = getNotificationBgClass($notif['type'] ?? 'info');
            $notif['is_read'] = (bool)($notif['lu'] ?? 0);
        }

        $paginationHtml = renderPagination($paginationResult, "?page={page}");

        return [
            'notifications' => $notifications,
            'pagination_html' => $paginationHtml,
            'pagination_data' => $paginationResult['pagination'] ?? $defaultResult['pagination_data']
        ];
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur chargement notifications pour user #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors du chargement des notifications.", "danger");
        return $defaultResult;
    }
}
function getNotificationIcon(string $type): string
{
    switch (strtolower($type)) {
        case 'success':
            return 'fas fa-check-circle text-success';
        case 'warning':
            return 'fas fa-exclamation-triangle text-warning';
        case 'error':
            return 'fas fa-times-circle text-danger';
        case 'info':
        default:
            return 'fas fa-info-circle text-info';
    }
}

function getNotificationBgClass(string $type): string
{

    switch (strtolower($type)) {
        case 'success':
            return 'list-group-item-success-light';
        case 'warning':
            return 'list-group-item-warning-light';
        case 'error':
            return 'list-group-item-danger-light';
        case 'info':
        default:
            return 'list-group-item-info-light';
    }
}
