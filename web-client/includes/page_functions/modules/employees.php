<?php

/**
 * fonctions pour la gestion des salariés
 *
 * ce fichier contient les fonctions nécessaires pour gérer les salariés des entreprises clientes
 */

require_once __DIR__ . '/../../../includes/init.php';

/**
 * récupère la liste des salariés avec pagination et recherche
 * 
 * @param int|null $company_id identifiant de l'entreprise (null pour tous)
 * @param int $page numéro de page
 * @param int $limit nombre d'éléments par page
 * @param string $search terme de recherche
 * @return array liste des salariés et informations de pagination
 */
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
    $countParams = [':role_id' => ROLE_SALARIE]; // Params séparés pour le count

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

/**
 * récupère les détails d'un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @return array|false détails du salarié ou false si non trouvé
 */
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

/**
 * met à jour le profil d'un salarié
 * NOTE: Cette fonction met à jour les données. La validation et le traitement de la requête
 * devraient être dans une fonction handleUpdateEmployeeProfile().
 * 
 * @param int $employee_id identifiant du salarié
 * @param array $profile_data données du profil à mettre à jour
 * @return int|false nombre de lignes affectées ou false en cas d'erreur
 */
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
        // 'mot_de_passe' devrait être géré séparément via une fonction dédiée
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

/**
 * récupère les services disponibles pour un salarié
 * (vérifie si l'entreprise a un contrat actif)
 * 
 * @param int $employee_id identifiant du salarié
 * @return array liste des services disponibles
 */
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

/**
 * récupère les réservations d'un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @param string $status filtre par statut (ex: 'all', 'planifie', 'termine')
 * @return array liste des réservations
 */
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

/**
 * récupère les rendez-vous médicaux (type 'consultation') d'un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @param string $filter filtre par statut ('upcoming', 'past', 'all')
 * @return array liste des rendez-vous médicaux
 */
function getEmployeeAppointments($employee_id, $filter = 'upcoming')
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) return [];

    $filter = sanitizeInput($filter);

    $query = "SELECT r.id, r.date_rdv, r.duree, r.lieu, r.type_rdv, r.statut,
              p.nom as prestation_nom,
              prat.nom as praticien_nom, prat.prenom as praticien_prenom
              FROM " . TABLE_APPOINTMENTS . " r
              JOIN " . TABLE_PRESTATIONS . " p ON r.prestation_id = p.id
              LEFT JOIN " . TABLE_USERS . " prat ON r.praticien_id = prat.id
              WHERE r.personne_id = :employee_id AND p.type = :prestation_type";
    $params = [
        ':employee_id' => $employee_id,
        ':prestation_type' => 'consultation'
    ];

    $orderByDirection = 'DESC';
    if ($filter === 'upcoming') {
        $query .= " AND r.date_rdv >= CURDATE() AND r.statut NOT IN ('annule', 'termine')";
        $orderByDirection = 'ASC';
    } else if ($filter === 'past') {
        $query .= " AND (r.date_rdv < CURDATE() OR r.statut IN ('annule', 'termine', 'no_show'))";
    }

    $query .= " ORDER BY r.date_rdv $orderByDirection";

    $appointments = executeQuery($query, $params)->fetchAll();

    foreach ($appointments as &$appointment) {
        $appointment['date_rdv_formatee'] = isset($appointment['date_rdv']) ? formatDate($appointment['date_rdv'], 'd/m/Y H:i') : 'N/A';
        $appointment['statut_badge'] = isset($appointment['statut']) ? getStatusBadge($appointment['statut']) : '';
        $appointment['praticien_complet'] = isset($appointment['praticien_nom']) ? trim($appointment['praticien_prenom'] . ' ' . $appointment['praticien_nom']) : 'Non assigné';
    }

    return $appointments;
}

/**
 * récupère l'historique d'activités d'un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @param int $page numéro de page
 * @param int $limit nombre d'éléments par page
 * @return array historique d'activités et informations de pagination
 */
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

/**
 * Obtient une icône correspondant à un type d'action/log
 * 
 * @param string $action type d'action (peut inclure des préfixes comme [SECURITY]:login)
 * @return string classe CSS de l'icône Font Awesome
 */
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
    // Match key action if specific action not found
    if (isset($iconMap[$keyAction])) return $iconMap[$keyAction];
    // Match prefixes for broader categories
    if (strpos($action, 'reservation:') === 0) return 'fas fa-calendar-alt text-info';
    if (strpos($action, 'don:') === 0) return 'fas fa-gift text-info';
    if (strpos($action, 'paiement:') === 0) return 'fas fa-credit-card text-success';

    return $iconMap['default'];
}

/**
 * récupère les communautés accessibles à un salarié
 * 
 * @param int $employee_id identifiant du salarié (peut être utilisé pour vérifier les adhésions futures)
 * @return array liste des communautés
 */
function getEmployeeCommunities($employee_id)
{
    // Validation de l'ID (optionnel pour l'instant car on retourne toutes les communautés)
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    // if (!$employee_id) return []; // Décommenter si on filtre par salarié

    // Récupération des communautés
    try {
        $communities = fetchAll(TABLE_COMMUNAUTES, '1=1', 'type, nom'); // Ajouter `actif = 1` si applicable
        return $communities;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération communautés: " . $e->getMessage());
        flashMessage("Impossible de charger la liste des communautés.", "danger");
        return [];
    }
}

/**
 * gère les dons d'un salarié (fonction de traitement des données)
 * La validation et la gestion du formulaire devraient être dans handleDonationRequest()
 * 
 * @param int $employee_id identifiant du salarié
 * @param array $donation_data données du don ('type', 'montant', 'description')
 * @return int|false ID du don créé ou false en cas d'erreur
 */
function manageEmployeeDonations($employee_id, $donation_data)
{
    // Validation ID
    $employee_id = filter_var($employee_id, FILTER_VALIDATE_INT);
    if (!$employee_id) {
        error_log("manageEmployeeDonations: ID salarié invalide.");
        return false;
    }

    // Assurer que les clés existent pour éviter les erreurs undefined index
    $donation_data['type'] = $donation_data['type'] ?? null;
    $donation_data['montant'] = $donation_data['montant'] ?? null;
    $donation_data['description'] = $donation_data['description'] ?? null;

    // Validation du type de don
    if (!in_array($donation_data['type'], ['financier', 'materiel'])) {
        flashMessage("Type de don invalide.", "warning");
        return false;
    }

    // Validation basée sur le type
    if ($donation_data['type'] == 'financier') {
        $montant = filter_var($donation_data['montant'], FILTER_VALIDATE_FLOAT);
        if ($montant === false || $montant <= 0) {
            flashMessage("Le montant du don financier doit être un nombre positif.", "warning");
            return false;
        }
        $donation_data['montant'] = $montant; // Utiliser le montant validé
        if (!empty($donation_data['description'])) {
            flashMessage("La description n'est pas nécessaire pour un don financier.", "info");
            $donation_data['description'] = null; // Optionnel: ignorer la description
        }
    } elseif ($donation_data['type'] == 'materiel') {
        if (empty(trim($donation_data['description']))) {
            flashMessage("La description est obligatoire pour un don matériel.", "warning");
            return false;
        }
        if (!empty($donation_data['montant'])) {
            flashMessage("Le montant n'est pas applicable pour un don matériel.", "info");
            $donation_data['montant'] = null; // Optionnel: ignorer le montant
        }
    }

    try {
        // Vérifier que le salarié existe et est actif
        $employee = fetchOne(TABLE_USERS, "id = :id AND role_id = :role_id AND statut = :status", [
            ':id' => $employee_id,
            ':role_id' => ROLE_SALARIE,
            ':status' => STATUS_ACTIVE
        ]);
        if (!$employee) {
            flashMessage("Impossible de traiter le don: Salarié non trouvé ou inactif.", "danger");
            return false;
        }

        // Préparation des données pour insertion dans la table DONS
        $donData = [
            'personne_id' => $employee_id,
            'montant' => $donation_data['type'] == 'financier' ? $donation_data['montant'] : null,
            'type' => $donation_data['type'],
            'description' => $donation_data['type'] == 'materiel' ? trim($donation_data['description']) : null,
            'date_don' => date('Y-m-d'), // Utiliser la date actuelle
            'statut' => 'en_attente' // Statut initial
        ];

        // Début de transaction (important si on modifie plusieurs tables)
        beginTransaction();

        // Insertion du don
        $donationId = insertRow(TABLE_DONATIONS, $donData);

        if (!$donationId) {
            rollbackTransaction();
            logSystemActivity('error', "manageEmployeeDonations: Échec de l'insertion dans la table dons pour user #$employee_id");
            flashMessage("Une erreur technique est survenue lors de l'enregistrement de votre don (code 1).", "danger");
            return false;
        }

        // Si c'est un don financier, on pourrait déclencher un processus de paiement ici ou créer une facture/transaction
        // Pour l'instant, on ne crée pas d'entrée dans une table 'transactions' inexistante

        // Validation de la transaction
        commitTransaction();

        // Journalisation
        logBusinessOperation($employee_id, 'don_creation', "Don #{$donationId} créé, type: {$donation_data['type']}");

        // Retourner l'ID du don créé
        return $donationId;
    } catch (Exception $e) {
        // Assurer l'annulation de la transaction en cas d'erreur
        if (getDbConnection()->inTransaction()) {
            rollbackTransaction();
        }
        logSystemActivity('error', "Erreur création don pour user #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de l'enregistrement de votre don (code 2).", "danger");
        return false;
    }
}

/**
 * récupère les événements et défis disponibles pour un salarié
 * 
 * @param int $employee_id identifiant du salarié (peut être utilisé pour filtrage futur)
 * @param string $event_type filtre par type d'événement (ex: 'all', 'conference', 'defi_sportif')
 * @return array liste des événements
 */
function getEmployeeEvents($employee_id, $event_type = 'all')
{
    // Validation de l'ID (optionnel pour l'instant)
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    // if (!$employee_id) return [];

    $event_type = sanitizeInput($event_type);

    // Récupérer les types d'événements valides depuis la définition de la table si possible
    // Pour l'instant, on se base sur ceux du sujet
    $validEventTypes = ['conference', 'webinar', 'atelier', 'defi_sportif', 'autre'];

    // Construction de la requête
    $query = "SELECT id, titre, description, date_debut, date_fin, lieu, type, 
              capacite_max, niveau_difficulte 
              FROM evenements 
              WHERE date_debut >= CURDATE()"; // Afficher uniquement les événements futurs
    $params = [];

    // Filtre par type si différent de 'all' et valide
    if ($event_type !== 'all' && in_array($event_type, $validEventTypes)) {
        $query .= " AND type = :event_type";
        $params[':event_type'] = $event_type;
    }

    $query .= " ORDER BY date_debut ASC, titre ASC"; // Tri par date puis par titre

    try {
        $events = executeQuery($query, $params)->fetchAll();

        // Formater les dates
        foreach ($events as &$event) {
            $event['date_debut_formatee'] = isset($event['date_debut']) ? formatDate($event['date_debut'], 'd/m/Y H:i') : 'N/A';
            $event['date_fin_formatee'] = isset($event['date_fin']) ? formatDate($event['date_fin'], 'd/m/Y H:i') : 'N/A';
            // Ajouter d'autres formatages si nécessaire (ex: difficulté)
        }
        return $events;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération événements: " . $e->getMessage());
        flashMessage("Impossible de charger la liste des événements.", "danger");
        return [];
    }
}

/**
 * met à jour les préférences d'un salarié (fonction de traitement)
 * La validation et la gestion du formulaire devraient être dans handleUpdateEmployeeSettings()
 * 
 * @param int $employee_id identifiant du salarié
 * @param array $settings paramètres à mettre à jour ('langue', 'notif_email', 'theme')
 * @return bool résultat de la mise à jour (true si succès, false si échec)
 */
function updateEmployeeSettings($employee_id, $settings)
{
    // Validation de l'ID
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        error_log("updateEmployeeSettings: ID salarié invalide.");
        return false;
    }

    // Liste des champs autorisés et leurs validations
    $allowedFields = [
        'langue' => ['fr', 'en'], // Doit correspondre à l'ENUM
        'notif_email' => [0, 1], // BOOLEAN représenté par 0 ou 1
        'theme' => ['clair', 'sombre'] // Doit correspondre à l'ENUM
    ];

    // Filtrage et validation des paramètres
    $filteredSettings = [];
    foreach ($settings as $key => $value) {
        if (array_key_exists($key, $allowedFields)) {
            if ($key === 'notif_email') {
                // Convertir en 0 ou 1
                $filteredSettings[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            } elseif (in_array($value, $allowedFields[$key])) {
                $filteredSettings[$key] = $value; // La valeur est valide
            } else {
                flashMessage("Valeur invalide pour le paramètre '$key'.", "warning");
                // Optionnel: on pourrait retourner false ici pour arrêter
            }
        }
    }

    if (empty($filteredSettings)) {
        flashMessage("Aucun paramètre valide fourni pour la mise à jour.", "warning");
        return false; // Aucune donnée valide à mettre à jour
    }

    try {
        // Vérification de l'existence des préférences
        $exists = fetchOne(TABLE_USER_PREFERENCES, "personne_id = :id", [':id' => $employee_id]);

        $success = false;

        if ($exists) {
            // Mise à jour
            $affectedRows = updateRow(
                TABLE_USER_PREFERENCES,
                $filteredSettings,
                'personne_id = :personne_id',
                ['personne_id' => $employee_id]
            );
            // updateRow retourne le nombre de lignes affectées, peut être 0 si pas de changement
            // On considère succès si >= 0 (pas d'erreur SQL)
            $success = ($affectedRows !== false);
        } else {
            // Insertion
            $filteredSettings['personne_id'] = $employee_id;
            $insertId = insertRow(TABLE_USER_PREFERENCES, $filteredSettings);
            $success = ($insertId !== false);
        }

        if ($success) {
            // Mise à jour de la session si nécessaire et si les données ont changé
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $employee_id) {
                if (isset($filteredSettings['langue'])) {
                    $_SESSION['user_language'] = $filteredSettings['langue'];
                }
                // Mettre à jour d'autres préférences en session si stockées
            }
        }

        return $success;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur mise à jour préférences salarié #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de la mise à jour des préférences.", "danger");
        return false;
    }
}

/**
 * réserve un rendez-vous pour un salarié (fonction de traitement)
 * La validation et la gestion du formulaire devraient être dans handleReservationRequest()
 * 
 * @param int $employee_id identifiant du salarié
 * @param array $appointment_data données du rendez-vous ('prestation_id', 'date_rdv', 'duree', 'type_rdv', 'lieu', 'notes', 'praticien_id')
 * @return int|false ID du rendez-vous créé ou false en cas d'erreur
 */
function bookEmployeeAppointment($employee_id, $appointment_data)
{
    // Validation ID
    $employee_id = filter_var($employee_id, FILTER_VALIDATE_INT);
    if (!$employee_id) {
        error_log("bookEmployeeAppointment: ID salarié invalide.");
        return false;
    }

    // Vérification des données requises
    $requiredFields = ['prestation_id', 'date_rdv', 'duree', 'type_rdv'];
    foreach ($requiredFields as $field) {
        if (empty($appointment_data[$field])) {
            flashMessage("Le champ '$field' est obligatoire pour la réservation.", "danger");
            return false;
        }
    }

    // Validation des types et valeurs
    $prestation_id = filter_var($appointment_data['prestation_id'], FILTER_VALIDATE_INT);
    $duree = filter_var($appointment_data['duree'], FILTER_VALIDATE_INT);
    $type_rdv = $appointment_data['type_rdv'];
    $praticien_id = isset($appointment_data['praticien_id']) ? filter_var($appointment_data['praticien_id'], FILTER_VALIDATE_INT) : null;

    // Validation format date/heure (exemple simple, utiliser DateTime pour plus de robustesse)
    $dateHeure = date('Y-m-d H:i:s', strtotime($appointment_data['date_rdv']));
    if (!$prestation_id || !$duree || $duree <= 0 || !$dateHeure || !in_array($type_rdv, APPOINTMENT_TYPES)) {
        flashMessage("Données de rendez-vous invalides (format, type ou durée).", "danger");
        return false;
    }
    if ($dateHeure < date('Y-m-d H:i:s')) {
        flashMessage("Vous ne pouvez pas réserver un rendez-vous dans le passé.", "warning");
        return false;
    }

    // Vérification de la disponibilité du créneau
    // Note: isTimeSlotAvailable devrait idéalement aussi vérifier la disponibilité du praticien si applicable
    if (!isTimeSlotAvailable($dateHeure, $duree, $prestation_id)) {
        flashMessage("Ce créneau horaire n'est pas disponible pour cette prestation.", "warning");
        return false;
    }

    try {
        // Vérifier que le salarié et la prestation existent
        $employee = fetchOne(TABLE_USERS, "id = :id AND role_id = :role_id AND statut = :status", [
            ':id' => $employee_id,
            ':role_id' => ROLE_SALARIE,
            ':status' => STATUS_ACTIVE
        ]);
        $prestation = fetchOne(TABLE_PRESTATIONS, "id = :id", [':id' => $prestation_id]); // Ajouter `actif=1` si besoin

        if (!$employee) {
            flashMessage("Salarié non trouvé ou inactif.", "danger");
            return false;
        }
        if (!$prestation) {
            flashMessage("La prestation demandée n'existe pas.", "danger");
            return false;
        }

        // Début de transaction
        beginTransaction();

        // Préparation des données pour insertion
        $rdvData = [
            'personne_id' => $employee_id,
            'prestation_id' => $prestation_id,
            'praticien_id' => $praticien_id, // Peut être NULL
            'date_rdv' => $dateHeure,
            'duree' => $duree,
            'lieu' => $appointment_data['lieu'] ?? null,
            'type_rdv' => $type_rdv,
            'statut' => 'planifie', // Statut initial
            'notes' => $appointment_data['notes'] ?? null
        ];

        // Insertion du rendez-vous
        $appointmentId = insertRow(TABLE_APPOINTMENTS, $rdvData);

        if (!$appointmentId) {
            rollbackTransaction();
            logSystemActivity('error', "bookEmployeeAppointment: Échec insertion RDV pour user #$employee_id, prestation #$prestation_id");
            flashMessage("Une erreur technique est survenue lors de la création du rendez-vous (code 1).", "danger");
            return false;
        }

        // Ajout d'une notification (optionnel, pourrait être géré par un autre système)
        $notifData = [
            'personne_id' => $employee_id,
            'titre' => 'Nouveau rendez-vous planifié',
            'message' => 'Votre rendez-vous pour \'' . sanitizeInput($prestation['nom']) . '\' le ' . formatDate($dateHeure) . ' a été planifié.',
            'type' => 'info',
            'lien' => WEBCLIENT_URL . '/mon-planning?rdv=' . $appointmentId // Example link
        ];
        insertRow(TABLE_NOTIFICATIONS, $notifData);
        // Une erreur ici ne devrait pas annuler la transaction principale

        // Validation de la transaction
        commitTransaction();

        // Journalisation
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

// ============================================================
// == NOUVELLES FONCTIONS (Handlers & Display Logic Stubs) ==
// ============================================================

/**
 * Affiche le tableau de bord de l'employé.
 * Prépare les données nécessaires pour la vue du tableau de bord.
 *
 * @return array Données pour la vue (ex: user info, upcoming events, notifications)
 */
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

    // Récupérer les prochains rendez-vous (ex: 3 prochains)
    $data['upcoming_appointments'] = getEmployeeAppointments($employee_id, 'upcoming'); // Limiter peut-être

    // Récupérer les prochains événements
    $data['upcoming_events'] = getEmployeeEvents($employee_id); // Limiter peut-être

    // Récupérer les notifications non lues
    $data['unread_notifications'] = fetchAll(TABLE_NOTIFICATIONS, 'personne_id = :id AND lu = 0', 'created_at DESC', 5, 0, [':id' => $employee_id]);

    // Ajouter d'autres données nécessaires (ex: stats rapides, conseils récents...)

    return $data; // Ces données seraient utilisées par le fichier PHP qui affiche la page
}

/**
 * Affiche la page des rendez-vous de l'employé.
 * Prépare les données nécessaires pour la vue de la page des rendez-vous.
 *
 * @return array Données pour la vue (ex: appointments, current filter)
 */
function displayEmployeeAppointmentsPage()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $filter = $_GET['filter'] ?? 'upcoming'; // Default filter
    $validFilters = ['upcoming', 'past', 'all'];
    if (!in_array($filter, $validFilters)) {
        $filter = 'upcoming'; // Reset to default if invalid
    }

    $data['appointments'] = getEmployeeAppointments($employee_id, $filter);
    $data['currentFilter'] = $filter;
    $data['csrf_token'] = $_SESSION['csrf_token']; // For cancellation links/forms

    return $data;
}

/**
 * Affiche le profil de l'employé.
 * Prépare les données pour la vue du profil.
 *
 * @return array Données pour la vue (détails de l'employé)
 */
function displayEmployeeProfile()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $data['employee'] = getEmployeeDetails($employee_id);
    if (!$data['employee']) {
        flashMessage("Impossible d'afficher le profil.", "danger");
        redirectTo(WEBCLIENT_URL . '/dashboard.php'); // Rediriger vers le tableau de bord
    }
    $data['csrf_token'] = $_SESSION['csrf_token']; // Pour le formulaire d'édition

    return $data;
}

/**
 * Traite la soumission du formulaire de mise à jour du profil employé.
 */
function handleUpdateEmployeeProfile()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken(); // Vérifie le token CSRF pour POST

        $formData = getFormData();

        // Validation spécifique des champs (email, tel, genre etc.)
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
        // Ajouter d'autres validations si nécessaire (date naissance...)

        if (!empty($validation_errors)) {
            flashMessage("Erreurs de validation: " . implode(", ", $validation_errors), "danger");
        } else {
            // Appel de la fonction de mise à jour
            $result = updateEmployeeProfile($employee_id, $formData);

            if ($result !== false) {
                if ($result > 0) {
                    logBusinessOperation($employee_id, 'update_profile', "Profil mis à jour par l'employé");
                    flashMessage("Votre profil a été mis à jour avec succès.", "success");
                } else {
                    flashMessage("Aucune modification détectée sur votre profil.", "info");
                }
            } // Si $result === false, un message d'erreur a déjà été défini par updateEmployeeProfile
        }
        // Rediriger vers la page de profil après traitement
        redirectTo(WEBCLIENT_URL . '/mon-profil.php');
    } else {
        // Rediriger si la méthode n'est pas POST
        redirectTo(WEBCLIENT_URL . '/mon-profil.php');
    }
}

/**
 * Affiche la page des paramètres de l'employé.
 *
 * @return array Données pour la vue (préférences actuelles)
 */
function displayEmployeeSettings()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    // Récupérer les préférences actuelles
    $data['settings'] = fetchOne(TABLE_USER_PREFERENCES, "personne_id = :id", [':id' => $employee_id]);
    // Fournir des valeurs par défaut si aucune préférence n'existe
    if (!$data['settings']) {
        $data['settings'] = [
            'langue' => 'fr', // Défaut
            'notif_email' => 1, // Défaut
            'theme' => 'clair' // Défaut
        ];
    }
    $data['csrf_token'] = $_SESSION['csrf_token']; // Pour le formulaire

    return $data;
}

/**
 * Traite la soumission du formulaire de mise à jour des paramètres de l'employé.
 */
function handleUpdateEmployeeSettings()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken();
        $formData = getFormData();

        // Préparer les données pour la fonction de mise à jour
        $settingsData = [
            'langue' => $formData['langue'] ?? null,
            'notif_email' => isset($formData['notif_email']) ? 1 : 0, // Checkbox
            'theme' => $formData['theme'] ?? null
        ];

        // Appel de la fonction de mise à jour
        $success = updateEmployeeSettings($employee_id, $settingsData);

        if ($success) {
            logBusinessOperation($employee_id, 'update_preferences', "Préférences mises à jour par l'employé");
            flashMessage("Vos préférences ont été enregistrées.", "success");
        } // Message d'erreur géré dans updateEmployeeSettings

        redirectTo(WEBCLIENT_URL . '/mes-parametres.php');
    } else {
        redirectTo(WEBCLIENT_URL . '/mes-parametres.php');
    }
}

/**
 * Affiche le catalogue des services/prestations.
 *
 * @return array Données pour la vue (liste des prestations, pagination)
 */
function displayServiceCatalog()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    // Vérifier si l'employé a accès (contrat entreprise actif)
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

    if (!$activeContract) {
        $data['services'] = [];
        $data['pagination_html'] = '';
        flashMessage("Votre entreprise n'a pas de contrat actif pour accéder aux services.", "warning");
    } else {
        // Récupérer les paramètres de filtre et pagination GET
        $filters = getQueryData();
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $typeFilter = $filters['type'] ?? '';
        $categoryFilter = $filters['categorie'] ?? '';

        // Appeler la fonction de récupération paginée des prestations
        // (Adapter getPrestations si nécessaire pour plus de filtres ou logique métier)
        $paginationResult = getPrestations($typeFilter, $categoryFilter, $page, 12); // 12 par page par ex.

        $data['services'] = $paginationResult['items'];
        $data['pagination_html'] = renderPagination($paginationResult, "?type=$typeFilter&categorie=$categoryFilter&page={page}");
        $data['types'] = PRESTATION_TYPES; // Pour les filtres
        // Ajouter $data['categories'] si pertinent
    }

    return $data;
}

/**
 * Affiche les détails d'un service/prestation.
 *
 * @param int $service_id
 * @return array Données pour la vue (détails du service)
 */
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
    // Ajouter la récupération des créneaux disponibles si c'est une consultation
    // Ajouter la récupération des praticiens si applicable
    $data['csrf_token'] = $_SESSION['csrf_token']; // Pour le formulaire de réservation

    return $data;
}

/**
 * Affiche le planning personnel de l'employé (réservations, événements).
 *
 * @return array Données pour la vue (liste des réservations/événements)
 */
function displayEmployeeSchedule()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $data = [];
    $filter = $_GET['filter'] ?? 'upcoming'; // upcoming, past, all

    // Récupérer les réservations (consultations, ateliers...)
    $data['reservations'] = getEmployeeReservations($employee_id, $filter);

    // Récupérer les inscriptions aux événements (nécessite une table de liaison)
    // Exemple: $data['event_registrations'] = getEmployeeEventRegistrations($employee_id, $filter);
    $data['event_registrations'] = []; // Placeholder

    $data['filter'] = $filter;

    return $data;
}

/**
 * Traite une demande de réservation depuis un formulaire.
 */
function handleReservationRequest()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken();
        $formData = getFormData();

        // Préparer les données pour bookEmployeeAppointment
        $appointmentData = [
            'prestation_id' => $formData['prestation_id'] ?? null,
            'date_rdv' => $formData['date_rdv'] ?? null,
            'duree' => $formData['duree'] ?? null,
            'type_rdv' => $formData['type_rdv'] ?? null,
            'lieu' => $formData['lieu'] ?? null,
            'notes' => $formData['notes'] ?? null,
            'praticien_id' => $formData['praticien_id'] ?? null
            // Ajouter d'autres champs si nécessaire
        ];

        $appointmentId = bookEmployeeAppointment($employee_id, $appointmentData);

        if ($appointmentId) {
            flashMessage("Votre réservation a été enregistrée avec succès (ID: $appointmentId).", "success");
            redirectTo(WEBCLIENT_URL . '/mon-planning.php'); // Rediriger vers le planning
        } else {
            // Message d'erreur défini dans bookEmployeeAppointment
            // Rediriger vers la page précédente (formulaire) pour correction
            redirectTo($_SERVER['HTTP_REFERER'] ?? WEBCLIENT_URL . '/catalogue.php');
        }
    } else {
        redirectTo(WEBCLIENT_URL . '/catalogue.php');
    }
}

/**
 * Traite une demande d'annulation de réservation par l'employé.
 *
 * @param int $reservation_id ID de la réservation à annuler.
 */
function handleCancelReservation($reservation_id)
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $reservation_id = filter_var(sanitizeInput($reservation_id), FILTER_VALIDATE_INT);
    if (!$reservation_id) {
        flashMessage("ID de réservation invalide.", "danger");
        redirectTo(WEBCLIENT_URL . '/mon-planning.php');
    }

    // Idéalement, vérifier aussi le token CSRF via GET si l'action est initiée par un lien
    // Exemple: if (!validateToken($_GET['token'] ?? '')) { handleClientCsrfFailureRedirect(...); }

    try {
        // Vérifier que la réservation appartient bien à l'employé et est annulable
        $reservation = fetchOne(
            TABLE_APPOINTMENTS,
            "id = :id AND personne_id = :employee_id AND statut IN ('planifie', 'confirme')",
            [':id' => $reservation_id, ':employee_id' => $employee_id]
        );

        if (!$reservation) {
            flashMessage("Réservation non trouvée ou non annulable.", "warning");
            redirectTo(WEBCLIENT_URL . '/mon-planning.php');
        }

        // Vérifier s'il est trop tard pour annuler (règle métier, ex: 24h avant)
        $now = new DateTime();
        $rdvTime = new DateTime($reservation['date_rdv']);
        $interval = $now->diff($rdvTime);
        if ($now >= $rdvTime || ($interval->days == 0 && $interval->h < 24)) { // Moins de 24h avant
            // flashMessage("Il est trop tard pour annuler cette réservation (moins de 24h).", "warning");
            // redirectTo(WEBCLIENT_URL . '/mon-planning.php');
            // Temporairement autorisé pour test
        }

        // Mettre à jour le statut
        $updated = updateRow(
            TABLE_APPOINTMENTS,
            ['statut' => 'annule'],
            "id = :id",
            [':id' => $reservation_id]
        );

        if ($updated) {
            logReservationActivity($employee_id, $reservation['prestation_id'], 'annulation', "RDV #$reservation_id annulé par l'employé");
            flashMessage("Votre réservation a été annulée.", "success");
            // Envoyer une notification au praticien si applicable
        } else {
            flashMessage("Impossible d'annuler la réservation.", "danger");
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur annulation RDV #$reservation_id pour user #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de l'annulation.", "danger");
    }

    redirectTo(WEBCLIENT_URL . '/mon-planning.php');
}

/**
 * Affiche la liste des notifications de l'employé.
 *
 * @return array Données pour la vue (liste des notifications, pagination)
 */
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

    // Marquer comme lues celles affichées ? Non, préférable de le faire explicitement.
    $data['notifications'] = $paginationResult['items'];
    $data['pagination_html'] = renderPagination($paginationResult, "?page={page}");

    return $data;
}

/**
 * Marque une notification spécifique comme lue.
 * Souvent utilisé via AJAX.
 *
 * @param int $notification_id
 * @return bool Succès ou échec
 */
function handleMarkNotificationRead($notification_id)
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $notification_id = filter_var(sanitizeInput($notification_id), FILTER_VALIDATE_INT);
    if (!$notification_id) return false;

    // Vérifier que la notif appartient à l'utilisateur
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
    return false; // Déjà lue ou n'appartient pas à l'user
}

/**
 * Marque toutes les notifications de l'employé comme lues.
 */
function handleMarkAllNotificationsRead()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    // Idéalement, vérifier token CSRF si action vient d'un formulaire/bouton

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
            flashMessage("Erreur lors de la mise à jour des notifications.", "danger");
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur markAllNotificationsRead pour user #$employee_id: " . $e->getMessage());
        flashMessage("Erreur technique lors de la mise à jour des notifications.", "danger");
    }
    redirectTo(WEBCLIENT_URL . '/notifications.php');
}

/**
 * Affiche les détails d'une communauté.
 *
 * @param int $community_id
 * @return array Données pour la vue (infos communauté, posts, membres...)
 */
function displayCommunityDetails($community_id)
{
    requireRole(ROLE_SALARIE);

    $community_id = filter_var(sanitizeInput($community_id), FILTER_VALIDATE_INT);
    if (!$community_id) {
        flashMessage("ID de communauté invalide.", "danger");
        redirectTo(WEBCLIENT_URL . '/communautes.php');
    }

    $data = [];
    $data['community'] = fetchOne(TABLE_COMMUNAUTES, "id = :id", [':id' => $community_id]);

    if (!$data['community']) {
        flashMessage("Communauté non trouvée.", "warning");
        redirectTo(WEBCLIENT_URL . '/communautes.php');
    }

    // Récupérer les posts de la communauté (nécessite table community_posts)
    // Exemple: $data['posts'] = fetchAll('community_posts', 'community_id = :id', 'created_at DESC', 20, 0, [':id' => $community_id]);
    $data['posts'] = []; // Placeholder
    $data['csrf_token'] = $_SESSION['csrf_token']; // Pour le formulaire de post

    return $data;
}

/**
 * Traite la soumission d'un nouveau message dans une communauté.
 *
 * @param int $community_id
 */
function handleCommunityPost($community_id)
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    $community_id = filter_var(sanitizeInput($community_id), FILTER_VALIDATE_INT);
    if (!$community_id) {
        flashMessage("ID de communauté invalide.", "danger");
        redirectTo(WEBCLIENT_URL . '/communautes.php');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken();
        $formData = getFormData();
        $message = trim($formData['message'] ?? '');

        if (empty($message)) {
            flashMessage("Le message ne peut pas être vide.", "warning");
        } else {
            // Vérifier que la communauté existe
            $community = fetchOne(TABLE_COMMUNAUTES, "id = :id", [':id' => $community_id]);
            if (!$community) {
                flashMessage("Communauté non trouvée.", "danger");
            } else {
                // Insérer le post (nécessite table community_posts avec user_id, community_id, message, created_at)
                // Exemple:
                // $postData = [
                //     'community_id' => $community_id,
                //     'user_id' => $employee_id,
                //     'message' => $message, // Appliquer une modération/nettoyage plus poussé si nécessaire
                //     'created_at' => date('Y-m-d H:i:s')
                // ];
                // $postId = insertRow('community_posts', $postData);
                // if ($postId) {
                //     logActivity($employee_id, 'community_post', "Nouveau post dans communauté #$community_id");
                //     flashMessage("Message publié.", "success");
                // } else {
                //     flashMessage("Erreur lors de la publication du message.", "danger");
                // }
                flashMessage("Fonctionnalité de publication non implémentée.", "info"); // Placeholder
            }
        }
        // Rediriger vers la page de la communauté
        redirectTo(WEBCLIENT_URL . '/communaute.php?id=' . $community_id);
    } else {
        redirectTo(WEBCLIENT_URL . '/communaute.php?id=' . $community_id);
    }
}

/**
 * Traite la soumission du formulaire de signalement anonyme.
 */
function handleAnonymousReport()
{
    // Pas besoin de requireRole ici car c'est anonyme par définition
    // Mais on pourrait vouloir restreindre l'accès à la page du formulaire aux salariés connectés

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // IMPORTANT: Ne pas vérifier le token CSRF de session car l'utilisateur pourrait ne pas être loggué
        // Envisager un autre mécanisme anti-spam/bot si nécessaire (captcha?)
        $formData = getFormData(); // Utiliser sanitizeInput
        $report_content = trim($formData['report_content'] ?? '');
        $report_subject = trim($formData['report_subject'] ?? 'Signalement anonyme');

        if (empty($report_content)) {
            flashMessage("Le contenu du signalement ne peut pas être vide.", "warning");
        } else {
            try {
                // Insérer dans une table dédiée (ex: anonymous_reports)
                // NE PAS inclure d'ID utilisateur !
                $reportData = [
                    'subject' => $report_subject,
                    'content' => $report_content,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null, // Optionnel: stocker l'IP pour analyse d'abus
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null, // Optionnel
                    'created_at' => date('Y-m-d H:i:s')
                ];
                // $reportId = insertRow('anonymous_reports', $reportData);
                $reportId = 1; // Placeholder si la table n'existe pas encore

                if ($reportId) {
                    logSystemActivity('anonymous_report', "Nouveau signalement anonyme soumis (ID: $reportId)");
                    flashMessage("Votre signalement a été soumis anonymement. Merci.", "success");
                } else {
                    flashMessage("Erreur lors de la soumission de votre signalement.", "danger");
                }
            } catch (Exception $e) {
                logSystemActivity('error', "Erreur soumission signalement anonyme: " . $e->getMessage());
                flashMessage("Erreur technique lors de la soumission.", "danger");
            }
        }
        // Rediriger vers une page de confirmation ou la page précédente
        redirectTo($_SERVER['HTTP_REFERER'] ?? WEBCLIENT_URL . '/index.php');
    } else {
        redirectTo(WEBCLIENT_URL . '/index.php');
    }
}

/**
 * Affiche la section "Conseils".
 *
 * @return array Données pour la vue (liste d'articles/conseils)
 */
function displayAdviceSection()
{
    requireRole(ROLE_SALARIE); // Ouvert à tous les salariés connectés

    $data = [];
    // Récupérer les conseils depuis une table dédiée (ex: articles, posts avec categorie='conseil')
    // $data['advices'] = fetchAll('articles', "categorie = 'conseil' AND actif = 1", 'date_publication DESC');
    $data['advices'] = []; // Placeholder

    return $data;
}

/**
 * Affiche les informations sur les associations partenaires.
 *
 * @return array Données pour la vue (liste des associations)
 */
function displayAssociationInfo()
{
    requireRole(ROLE_SALARIE);

    $data = [];
    // Récupérer les infos depuis une table `associations` ou contenu statique
    // $data['associations'] = fetchAll('associations', 'partenaire = 1', 'nom ASC');
    $data['associations'] = []; // Placeholder
    $data['csrf_token'] = $_SESSION['csrf_token']; // Pour le formulaire de don

    return $data;
}

/**
 * Traite une demande de don depuis un formulaire.
 */
function handleDonationRequest()
{
    requireRole(ROLE_SALARIE);
    $employee_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken();
        $formData = getFormData();

        $donationData = [
            'type' => $formData['type'] ?? null,
            'montant' => $formData['montant'] ?? null,
            'description' => $formData['description'] ?? null
        ];

        $donationId = manageEmployeeDonations($employee_id, $donationData);

        if ($donationId) {
            // Message flash géré dans manageEmployeeDonations
            // Si don financier, rediriger vers paiement ? Sinon vers confirmation/remerciement.
            if ($donationData['type'] == 'financier') {
                flashMessage("Votre don financier de " . formatMoney($donationData['montant']) . " a été enregistré (ID: $donationId) et est en attente de traitement.", "success");
                // redirectTo(WEBCLIENT_URL . '/paiement.php?don=' . $donationId); // Exemple redirection paiement
                redirectTo(WEBCLIENT_URL . '/associations.php'); // Retour à la page associations
            } else {
                flashMessage("Votre don matériel \"" . sanitizeInput($donationData['description']) . "\" (ID: $donationId) a été enregistré. Nous vous contacterons.", "success");
                redirectTo(WEBCLIENT_URL . '/associations.php');
            }
        } else {
            // Message flash géré dans manageEmployeeDonations
            redirectTo($_SERVER['HTTP_REFERER'] ?? WEBCLIENT_URL . '/associations.php');
        }
    } else {
        redirectTo(WEBCLIENT_URL . '/associations.php');
    }
}

/**
 * Vérifie si c'est la première connexion de l'utilisateur.
 * (Nécessite un flag dans la base de données, ex: colonne `first_login` dans `personnes` ou `preferences_utilisateurs`)
 *
 * @param int $employee_id
 * @return bool True si c'est la première connexion, false sinon.
 */
function checkFirstLogin($employee_id)
{
    // requireRole(ROLE_SALARIE); // Appelé après authentification

    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) return false;

    // Méthode 1: Utiliser derniere_connexion (si NULL = première fois)
    // $user = fetchOne(TABLE_USERS, "id = :id", [':id' => $employee_id]);
    // return ($user && $user['derniere_connexion'] === null);

    // Méthode 2: Utiliser un flag dédié (supposons `first_login_completed` dans `preferences_utilisateurs`)
    $prefs = fetchOne(TABLE_USER_PREFERENCES, "personne_id = :id", [':id' => $employee_id]);
    if ($prefs && isset($prefs['first_login_completed']) && $prefs['first_login_completed'] == 1) {
        return false; // Le tutoriel a déjà été vu
    }
    // Si pas de préférences ou flag non défini/à 0, c'est la première fois
    return true;
}

/**
 * Marque le tutoriel comme complété pour l'utilisateur.
 * (Met à jour le flag `first_login_completed`)
 *
 * @param int $employee_id
 * @return bool Succès ou échec.
 */
function markTutorialAsCompleted($employee_id)
{
    requireRole(ROLE_SALARIE);
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if ($_SESSION['user_id'] != $employee_id) {
        // Ne devrait pas arriver si appelé correctement
        logSecurityEvent($employee_id, 'invalid_action', 'Tentative de marquer le tutoriel pour un autre utilisateur', true);
        return false;
    }

    try {
        // Assurer que l'enregistrement de préférences existe
        $prefs = fetchOne(TABLE_USER_PREFERENCES, "personne_id = :id", [':id' => $employee_id]);
        $data = ['first_login_completed' => 1];

        if ($prefs) {
            $updated = updateRow(TABLE_USER_PREFERENCES, $data, 'personne_id = :id', [':id' => $employee_id]);
        } else {
            // Créer l'enregistrement si inexistant
            $data['personne_id'] = $employee_id;
            // Ajouter les valeurs par défaut pour les autres champs
            $data['langue'] = 'fr';
            $data['notif_email'] = 1;
            $data['theme'] = 'clair';
            $updated = insertRow(TABLE_USER_PREFERENCES, $data);
        }
        logActivity($employee_id, 'tutorial_completed', 'Tutoriel marqué comme complété');
        return $updated !== false;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur markTutorialAsCompleted pour user #$employee_id: " . $e->getMessage());
        return false;
    }
}

// --- FIN DES NOUVELLES FONCTIONS ---
