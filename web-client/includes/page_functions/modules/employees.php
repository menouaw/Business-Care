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

    if ($activeContract === false) {
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
                  LEFT JOIN " . TABLE_USERS . " prat ON r.praticien_id = prat.id -- Restore LEFT JOIN
                  WHERE r.personne_id = :employee_id";

    $countQuery = "SELECT COUNT(r.id) " . $baseQuery;
    $dataQuery = "SELECT r.id, r.date_rdv, r.duree, r.lieu, r.type_rdv, r.statut,
                         p.nom as prestation_nom,
                         prat.nom as praticien_nom, prat.prenom as praticien_prenom -- Restore practitioner fields
                         " . $baseQuery;

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
        logSystemActivity('error', "Erreur getEmployeeAppointments pour employé #$employee_id: " . $e->getMessage());
        flashMessage("Erreur lors du chargement des rendez-vous.", "danger");
        return [
            'items' => [],
            'pagination_html' => '',
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
        if (!defined('TABLE_ASSOCIATIONS')) define('TABLE_ASSOCIATIONS', 'associations');
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
        logBusinessOperation($employee_id, 'don_creation', "Don #{$donationId} créé, type: {$donation_data['type']} pour association: {$assoc_name}");

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

function getEmployeeEvents($employee_id, $event_type = 'all')
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        flashMessage("ID employé invalide pour récupérer les événements.", "danger");
        return [];
    }

    $event_type = sanitizeInput($event_type);
    $validEventTypes = EVENT_TYPES;

    $query = "SELECT e.id, e.titre, e.description, e.date_debut, e.date_fin, e.lieu, e.type, 
                     e.capacite_max, e.niveau_difficulte
              FROM evenements e
              WHERE e.date_debut >= CURDATE()";
    $params = [];

    if ($event_type !== 'all' && in_array($event_type, $validEventTypes)) {
        $query .= " AND e.type = :event_type";
        $params[':event_type'] = $event_type;
    }

    $query .= " ORDER BY e.date_debut ASC, e.titre ASC";

    try {
        error_log("[DEBUG] getEmployeeEvents - Query: " . $query);
        error_log("[DEBUG] getEmployeeEvents - Params: " . json_encode($params));

        $events = executeQuery($query, $params)->fetchAll();

        error_log("[DEBUG] getEmployeeEvents - Fetched events count: " . count($events));

        $eventIds = array_map(function ($e) {
            return $e['id'];
        }, $events);

        if (!empty($eventIds)) {
            $inscriptionCounts = [];
            $sqlCounts = "SELECT evenement_id, COUNT(id) as count 
                          FROM evenement_inscriptions 
                          WHERE evenement_id IN (" . implode(',', array_fill(0, count($eventIds), '?')) . ") 
                          AND statut = 'inscrit' 
                          GROUP BY evenement_id";
            $stmtCounts = executeQuery($sqlCounts, $eventIds);
            while ($row = $stmtCounts->fetch()) {
                $inscriptionCounts[$row['evenement_id']] = $row['count'];
            }

            $userRegistrations = [];
            $sqlUserReg = "SELECT evenement_id 
                           FROM evenement_inscriptions 
                           WHERE personne_id = ? AND statut = 'inscrit' 
                           AND evenement_id IN (" . implode(',', array_fill(0, count($eventIds), '?')) . ")";
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
        }

        return $events;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération événements pour employé #$employee_id: " . $e->getMessage());
        flashMessage("Impossible de charger la liste des événements.", "danger");
        return [];
    }
}

function updateEmployeeSettings($employee_id, $settings)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $settings = sanitizeInput($settings);

    if (!$employee_id || empty($settings)) {
        flashMessage("ID de salarié invalide ou paramètres manquants", "danger");
        return false;
    }

    $allowedFields = [
        'langue' => ['fr', 'en'],
        'notif_email' => [0, 1]
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
        flashMessage("Aucun paramètre valide à mettre à jour", "warning");
        return false;
    }

    try {
        $exists = fetchOne('preferences_utilisateurs', "personne_id = $employee_id");

        $result = false;

        if ($exists) {
            $result = updateRow(
                'preferences_utilisateurs',
                $filteredSettings,
                'personne_id = :personne_id',
                ['personne_id' => $employee_id]
            );
        } else {
            $filteredSettings['personne_id'] = $employee_id;
            $result = insertRow('preferences_utilisateurs', $filteredSettings) ? true : false;
        }

        if ($result) {
            logBusinessOperation($employee_id, 'update_preferences', "Mise à jour des préférences utilisateur");
            flashMessage("Vos préférences ont été mises à jour", "success");

            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $employee_id && isset($filteredSettings['langue'])) {
                $_SESSION['user_language'] = $filteredSettings['langue'];
            }
        }

        return $result;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur mise à jour préférences: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la mise à jour des préférences", "danger");
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

    $eventsResult = getEmployeeEvents($employee_id, 'all', 1, 3);
    $data['upcoming_events'] = $eventsResult['items'] ?? [];

    $data['unread_notifications'] = fetchAll(TABLE_NOTIFICATIONS, 'personne_id = :id AND lu = 0', 'created_at DESC', 5, 0, ['id' => $employee_id]);

    $activityResult = getEmployeeActivityHistory($employee_id, 1, 5);
    $data['recent_activity'] = $activityResult;


    return $data;
}


function displayEmployeeAppointmentsPage()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $filters = getQueryData();
    $filter = $filters['filter'] ?? 'upcoming';
    $page = isset($filters['page']) ? (int)$filters['page'] : 1;

    $validFilters = ['upcoming', 'past', 'all', 'annule'];
    if (!in_array($filter, $validFilters)) {
        $filter = 'upcoming';
    }

    $paginationResult = getEmployeeAppointments($employee_id, $filter, $page, 10);

    $data['appointments'] = $paginationResult['items'];
    $data['pagination_html'] = $paginationResult['pagination_html'];
    $data['currentFilter'] = $filter;
    $data['csrf_token'] = $_SESSION['csrf_token'];

    return $data;
}

function displayEmployeeCommunitiesPage()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $data['communities'] = getEmployeeCommunities($employee_id);

    return $data;
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
        $profileData = array_filter($profileData, function ($value) {
            return $value !== null;
        });

        $settingsData = [
            'langue' => $formData['langue'] ?? null,
            'notif_email' => isset($formData['notif_email']) ? 1 : 0
        ];
        $settingsData = array_filter($settingsData, function ($value) {
            return $value !== null;
        });

        $profileUpdateResult = false;
        $settingsUpdateResult = false;
        $profileChanged = false;
        $settingsChanged = false;

        if (!empty($profileData)) {
            $profileUpdateResult = updateEmployeeProfile($employee_id, $profileData);
            $profileChanged = ($profileUpdateResult !== false && $profileUpdateResult > 0);
        }

        if (!empty($settingsData)) {
            $settingsUpdateResult = updateEmployeeSettings($employee_id, $settingsData);
            $settingsChanged = ($settingsUpdateResult === true);
        }

        if ($profileUpdateResult !== false && $settingsUpdateResult !== false) {
            if ($profileChanged || $settingsChanged) {
                logBusinessOperation($employee_id, 'update_preferences_profile', "Profil/Préférences mis à jour par l'employé via settings.php");
                flashMessage("Vos informations ont été enregistrées avec succès.", "success");
            } else if ($profileUpdateResult === 0 && ($settingsUpdateResult === false || $settingsUpdateResult === true)) {
                $existingSettings = fetchOne(TABLE_USER_PREFERENCES, "personne_id = :id", [':id' => $employee_id]);
                if ($settingsUpdateResult === true || $existingSettings) {
                    flashMessage("Aucune modification détectée.", "info");
                }
            } else {
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

        $paginationResult = getPrestations($typeFilter, $categoryFilter, $page, 12);

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

        if (empty($appointmentData['prestation_id']) || empty($appointmentData['date_rdv']) || empty($appointmentData['duree'])) {
            flashMessage("Informations de réservation incomplètes.", "warning");
            redirectTo($_SERVER['HTTP_REFERER'] ?? WEBCLIENT_URL . '/catalogue.php');
            return;
        }

        $appointmentId = bookEmployeeAppointment($employee_id, $appointmentData);

        if ($appointmentId) {
            flashMessage("Votre réservation a été enregistrée avec succès (ID: $appointmentId).", "success");
            redirectTo(WEBCLIENT_URL . '/mon-planning.php');
        } else {
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
        redirectTo(WEBCLIENT_URL . '/mon-planning.php');
    }

    try {
        $reservation = fetchOne(
            TABLE_APPOINTMENTS,
            "id = :id AND personne_id = :employee_id AND statut IN ('planifie', 'confirme')",
            [':id' => $reservation_id, ':employee_id' => $employee_id]
        );

        if (!$reservation) {
            flashMessage("Réservation non trouvée ou non annulable.", "warning");
            redirectTo(WEBCLIENT_URL . '/mon-planning.php');
        }

        $now = new DateTime();
        $rdvTime = new DateTime($reservation['date_rdv']);
        $interval = $now->diff($rdvTime);
        if ($now >= $rdvTime || ($interval->days == 0 && $interval->h < 24)) {

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
        } else {
            flashMessage("Impossible d'annuler la réservation.", "danger");
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur annulation RDV #$reservation_id pour user #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de l'annulation.", "danger");
    }
}


function displayNotifications()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;

    $where = "personne_id = :id";
    $params = [':id' => $employee_id];
    $orderBy = "created_at DESC";

    $paginationResult = paginateResults(TABLE_NOTIFICATIONS, $page, $limit, $where, $orderBy, $params);

    $data['notifications'] = $paginationResult['items'];
    $data['pagination_html'] = renderPagination($paginationResult, "?page={page}");

    return $data;
}

function handleMarkNotificationRead($notification_id)
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $notification_id = filter_var(sanitizeInput($notification_id), FILTER_VALIDATE_INT);
    if (!$notification_id) return false;

    $notif = fetchOne(
        TABLE_NOTIFICATIONS,
        "id = :id AND personne_id = :employee_id",
        [':id' => $notification_id, ':employee_id' => $employee_id]
    );

    if ($notif && $notif['lu'] == 0) {
        $updated = updateRow(
            TABLE_NOTIFICATIONS,
            ['lu' => 1, 'date_lecture' => date('Y-m-d H:i:s')],
            "id = :id",
            [':id' => $notification_id]
        );
        return $updated > 0;
    }
    return false; 
}


function handleMarkAllNotificationsRead()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    try {
        $updated = updateRow(
            TABLE_NOTIFICATIONS,
            ['lu' => 1, 'date_lecture' => date('Y-m-d H:i:s')],
            "personne_id = :employee_id AND lu = 0",
            [':employee_id' => $employee_id]
        );
        if ($updated !== false) {
            flashMessage("Toutes les notifications ont été marquées comme lues.", "success");
        } else {
            flashMessage("Aucune nouvelle notification à marquer comme lue.", "info");
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur markAllNotificationsRead pour user #$employee_id: " . $e->getMessage());
        flashMessage("Erreur technique lors de la mise à jour des notifications.", "danger");
    }
    redirectTo(WEBCLIENT_URL . '/notifications.php');
}


function displayCommunityDetailsPageData($community_id)
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [
        'community' => null,
        'posts' => [],
        'csrf_token' => $_SESSION['csrf_token'] ?? ''
    ];

    $community_id = filter_var($community_id, FILTER_VALIDATE_INT);
    if (!$community_id) {
        flashMessage("ID de communauté invalide.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/employees/communities.php');
        return $data; 
    }

    $data['community'] = fetchOne(TABLE_COMMUNAUTES, "id = :id", [':id' => $community_id], '');

    if (!$data['community']) {
        flashMessage("Communauté non trouvée.", "warning");
        redirectTo(WEBCLIENT_URL . '/modules/employees/communities.php');
        return $data; 
    }

    $query = "SELECT cm.*, p.prenom, p.nom 
              FROM communaute_messages cm
              JOIN personnes p ON cm.personne_id = p.id
              WHERE cm.communaute_id = :community_id
              ORDER BY cm.created_at ASC";
    try {
        $stmt = executeQuery($query, [':community_id' => $community_id]);
        $posts = $stmt->fetchAll();

        foreach ($posts as &$post) {
            $post['auteur_nom'] = trim(($post['prenom'] ?? '') . ' ' . ($post['nom'] ?? 'Inconnu'));
            if ($post['personne_id'] === $employee_id) {
                $post['auteur_nom'] = 'Vous';
            }
            $post['created_at_formatted'] = isset($post['created_at']) ? formatDate($post['created_at'], 'd/m/Y H:i') : 'Date inconnue';
        }
        $data['posts'] = $posts;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur lors de la récupération des messages pour la communauté ID: $community_id: " . $e->getMessage());
        flashMessage("Erreur lors du chargement des messages.", "danger");
    }

    return $data;
}

function handleNewCommunityPost($community_id, $employee_id, $message)
{
    if (empty(trim($message))) {
        flashMessage("Le message ne peut pas être vide.", "warning");
        return false;
    }
    if (!filter_var($community_id, FILTER_VALIDATE_INT) || !filter_var($employee_id, FILTER_VALIDATE_INT)) {
        flashMessage("ID invalide.", "danger");
        return false;
    }

    $dataToInsert = [
        'communaute_id' => $community_id,
        'personne_id' => $employee_id,
        'message' => $message,
    ];

    try {
        $insertedId = insertRow('communaute_messages', $dataToInsert);

        if ($insertedId) {
            logActivity($employee_id, 'community_post', "Nouveau message posté dans la communauté ID: $community_id (Msg ID: $insertedId)");
            flashMessage("Votre message a été ajouté.", "success");
            return true;
        } else {
            logSystemActivity('error', "Échec de l'insertion du message pour la communauté ID: $community_id par l'employé ID: $employee_id");
            flashMessage("Erreur technique lors de l'ajout de votre message.", "danger");
            return false;
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Exception lors de l'ajout du message pour la communauté ID: $community_id: " . $e->getMessage());
        flashMessage("Erreur technique lors de l'ajout de votre message.", "danger");
        return false;
    }
}


function getActiveAssociations()
{
    try {
        if (!defined('TABLE_ASSOCIATIONS')) {
            define('TABLE_ASSOCIATIONS', 'associations');
        }
        return fetchAll(TABLE_ASSOCIATIONS, 'statut = :status', 'nom ASC', [':status' => 'actif']);
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération des associations: " . $e->getMessage());
        return [];
    }
}

function handleRegisterForEvent($employee_id, $event_id)
{
    requireRole(ROLE_SALARIE);

    $employee_id = filter_var($employee_id, FILTER_VALIDATE_INT);
    $event_id = filter_var($event_id, FILTER_VALIDATE_INT);

    if (!$employee_id || !$event_id) {
        flashMessage("Informations invalides pour l'inscription.", "danger");
        return false;
    }

    $event_params = [$event_id];
    $event = fetchOne('evenements', 'id = ?', $event_params);

    if (!$event) {
        flashMessage("L'événement demandé n'existe pas.", "warning");
        return false;
    }

    $eventStartDate = new DateTime($event['date_debut']);
    $now = new DateTime();
    if ($now >= $eventStartDate) {
        flashMessage("Cet événement est déjà passé ou en cours, inscription impossible.", "warning");
        return false;
    }

    $params_exist = [':pid' => $employee_id, ':eid' => $event_id, ':statut' => 'inscrit'];
    $existingRegistration = fetchOne(
        'evenement_inscriptions',
        'personne_id = :pid AND evenement_id = :eid AND statut = :statut',
        $params_exist
    );
    if ($existingRegistration) {
        flashMessage("Vous êtes déjà inscrit à cet événement.", "info");
        return true; 
    }

    if (isset($event['capacite_max']) && $event['capacite_max'] !== null) {
        $params_count = [':eid' => $event_id, ':statut' => 'inscrit'];
        $registeredCount = countTableRows(
            'evenement_inscriptions',
            'evenement_id = :eid AND statut = :statut',
            $params_count
        );

        if ($registeredCount >= $event['capacite_max']) {
            flashMessage("Désolé, cet événement est complet.", "warning");
            return false;
        }
    }

    try {
        beginTransaction();

        $params_cancel = [':pid' => $employee_id, ':eid' => $event_id, ':statut' => 'annule'];
        $cancelledRegistration = fetchOne(
            'evenement_inscriptions',
            'personne_id = :pid AND evenement_id = :eid AND statut = :statut',
            $params_cancel
        );

        if ($cancelledRegistration) {
            $updated = updateRow(
                'evenement_inscriptions',
                ['statut' => 'inscrit', 'updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                [':id' => $cancelledRegistration['id']]
            );
            $success = ($updated > 0);
        } else {
            $dataToInsert = [
                'personne_id' => $employee_id,
                'evenement_id' => $event_id,
                'statut' => 'inscrit',
            ];
            $insertedId = insertRow('evenement_inscriptions', $dataToInsert);
            $success = ($insertedId !== false);
        }

        if ($success) {
            commitTransaction();
            logActivity($employee_id, 'event_registration', "Inscription à l'événement ID: $event_id réussie.");
            flashMessage("Inscription à l'événement réussie !", "success");
            return true;
        } else {
            rollbackTransaction();
            logSystemActivity('error', "Échec de l'inscription à l'événement ID: $event_id pour l'employé ID: $employee_id");
            flashMessage("Erreur technique lors de l'inscription.", "danger");
            return false;
        }
    } catch (Exception $e) {
        if (getDbConnection()->inTransaction()) {
            rollbackTransaction();
        }
        logSystemActivity('error', "Exception lors de l'inscription à l'événement ID: $event_id: " . $e->getMessage());
        flashMessage("Erreur technique lors de l'inscription.", "danger");
        return false;
    }
}

function handleUnregisterFromEvent($employee_id, $event_id)
{
    requireRole(ROLE_SALARIE);

    $employee_id = filter_var($employee_id, FILTER_VALIDATE_INT);
    $event_id = filter_var($event_id, FILTER_VALIDATE_INT);

    if (!$employee_id || !$event_id) {
        flashMessage("Informations invalides pour la désinscription.", "danger");
        return false;
    }

    $event = fetchOne('evenements', 'id = ?', [$event_id]);
    if (!$event) {
        flashMessage("L'événement demandé n'existe pas.", "warning");
        return false;
    }

    $eventStartDate = new DateTime($event['date_debut']);
    $now = new DateTime();
    if ($now >= $eventStartDate) {
        flashMessage("Cet événement est déjà passé ou en cours, désinscription impossible.", "warning");
        return false;
    }

    try {
        beginTransaction();

        $params_exist = [':pid' => $employee_id, ':eid' => $event_id, ':statut' => 'inscrit'];
        $existingRegistration = fetchOne(
            'evenement_inscriptions',
            'personne_id = :pid AND evenement_id = :eid AND statut = :statut',
            $params_exist
        );

        if (!$existingRegistration) {
            return true;
        }

        $updated = updateRow(
            'evenement_inscriptions',
            ['statut' => 'annule', 'updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            [':id' => $existingRegistration['id']]
        );

        if ($updated > 0) {
            commitTransaction();
            logActivity($employee_id, 'event_unregistration', "Désinscription de l'événement ID: $event_id réussie.");
            flashMessage("Désinscription de l'événement réussie.", "success");
            return true;
        } else {
            rollbackTransaction();
            logSystemActivity('error', "Échec de la désinscription de l'événement ID: $event_id pour l'employé ID: $employee_id");
            flashMessage("Erreur technique lors de la désinscription.", "danger");
            return false;
        }
    } catch (Exception $e) {
        if (getDbConnection()->inTransaction()) {
            rollbackTransaction();
        }
        logSystemActivity('error', "Exception lors de la désinscription de l'événement ID: $event_id: " . $e->getMessage());
        flashMessage("Erreur technique lors de la désinscription.", "danger");
        return false;
    }
}

function getEventIcon($eventType)
{
    $eventType = strtolower(trim($eventType));

    $iconMap = [
        'conference' => 'fas fa-chalkboard-teacher',
        'webinar' => 'fas fa-desktop',
        'atelier' => 'fas fa-tools',
        'defi_sportif' => 'fas fa-running',
        'consultation' => 'fas fa-user-md',
        'autre' => 'fas fa-calendar-day',
        'default' => 'fas fa-calendar-alt'
    ];

    return $iconMap[$eventType] ?? $iconMap['default'];
}

function handleChangePassword($employee_id, $current_password, $new_password, $confirm_password)
{
    if (!$employee_id) {
        flashMessage("Impossible d'identifier l'utilisateur. Veuillez vous reconnecter.", "danger");
        return false;
    }

    if (!defined('MIN_PASSWORD_LENGTH')) {
        define('MIN_PASSWORD_LENGTH', 8);
        error_log("Warning: MIN_PASSWORD_LENGTH was not defined. Using default value 8.");
    }


    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        flashMessage("Tous les champs de mot de passe sont obligatoires.", "warning");
        return false;
    }

    if ($new_password !== $confirm_password) {
        flashMessage("Le nouveau mot de passe et sa confirmation ne correspondent pas.", "warning");
        return false;
    }

    if (strlen($new_password) < MIN_PASSWORD_LENGTH) {
        flashMessage("Le nouveau mot de passe doit comporter au moins " . MIN_PASSWORD_LENGTH . " caractères.", "warning");
        return false;
    }

    try {
        requireRole(ROLE_SALARIE);
        if ($_SESSION['user_id'] != $employee_id) {
            logSecurityEvent($employee_id, 'password_change_forbidden', "[SECURITY] Tentative de changement de mot de passe pour un autre utilisateur (demandeur: {$_SESSION['user_id']})");
            flashMessage("Vous ne pouvez modifier que votre propre mot de passe.", "danger");
            return false;
        }

        $user = fetchOne(TABLE_USERS, 'id = :id', '', [':id' => $employee_id]);

        if (!$user) {
            flashMessage("Utilisateur non trouvé.", "danger");
            session_unset();
            session_destroy();
            return false;
        }

        if (!password_verify($current_password, $user['mot_de_passe'])) {
            logSecurityEvent($employee_id, 'password_change_fail', "[SECURITY] Tentative de changement de mot de passe échouée (mot de passe actuel incorrect)");
            flashMessage("Le mot de passe actuel est incorrect.", "danger");
            return false;
        }

        $newPasswordHash = password_hash($new_password, PASSWORD_DEFAULT);

        $updateData = ['mot_de_passe' => $newPasswordHash, 'updated_at' => date('Y-m-d H:i:s')];
        $whereClause = 'id = :id';
        $whereParams = [':id' => $employee_id];


        $updated = updateRow(
            TABLE_USERS,
            $updateData,
            $whereClause,
            $whereParams
        );

        if ($updated > 0) {
            logBusinessOperation($employee_id, 'password_change_success', "Mot de passe modifié avec succès par l'employé.");
            deleteRow(TABLE_REMEMBER_ME, 'user_id = :user_id', [':user_id' => $employee_id]);
            flashMessage("Votre mot de passe a été modifié avec succès.", "success");
            return true;
        } else {
            logSystemActivity('error', "Échec de la mise à jour du mot de passe pour l'employé ID: $employee_id (DB update returned 0 rows)");
            flashMessage("Erreur technique lors de la modification du mot de passe (code 1).", "danger");
            return false;
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Exception lors du changement de mot de passe pour l'employé ID: $employee_id: " . $e->getMessage());
        flashMessage("Erreur technique lors de la modification du mot de passe (code 2).", "danger");
        return false;
    }
}

function handleAnonymousReport($sujet, $description)
{
    $sujet = trim(sanitizeInput($sujet));
    $description = trim(sanitizeInput($description));

    if (mb_strlen($sujet) > 255) {
        error_log("[WARNING] handleAnonymousReport: Sujet trop long.");
        return false;
    }

    $dataToInsert = [
        'sujet' => !empty($sujet) ? $sujet : null,
        'description' => $description,
        'statut' => 'nouveau'

    ];

    if (!defined('TABLE_SIGNALEMENTS')) {
        define('TABLE_SIGNALEMENTS', 'signalements');
    }

    try {
        $insertedId = insertRow(TABLE_SIGNALEMENTS, $dataToInsert);

        if ($insertedId) {
            logSystemActivity('anonymous_report_success', "Signalement anonyme ID: $insertedId enregistré avec succès.");
            return true;
        } else {
            logSystemActivity('anonymous_report_failure', "Échec de l'insertion du signalement anonyme en BDD.");
            return false;
        }
    } catch (Exception $e) {
        logSystemActivity('anonymous_report_exception', "Exception lors de l'enregistrement du signalement anonyme: " . $e->getMessage());
        return false;
    }
}



function processAppointmentCancellationRequest($postData)
{
    $message = '';
    $messageType = 'danger';
    $userIdForLog = $_SESSION['user_id'] ?? null;

    if (!isset($postData['csrf_token']) || !validateToken($postData['csrf_token'])) {
        logSecurityEvent($userIdForLog, 'csrf_failure', "[SECURITY FAILURE] Tentative d'annulation via POST avec jeton invalide sur appointments.php");
        flashMessage('Erreur de sécurité. Impossible de traiter votre demande.', 'danger');
    } else {
        $reservation_id = isset($postData['reservation_id']) ? filter_var($postData['reservation_id'], FILTER_VALIDATE_INT) : false;
        if (!$reservation_id) {
            flashMessage("ID de réservation invalide pour l'annulation.", 'danger');
        } else {
            handleCancelReservation($reservation_id);
        }
    }
    $currentFilterForRedirect = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : 'upcoming';
    redirectTo(WEBCLIENT_URL . '/modules/employees/appointments.php?filter=' . $currentFilterForRedirect);
    exit;
}
