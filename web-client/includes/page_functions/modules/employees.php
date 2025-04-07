<?php


require_once __DIR__ . '/../../../includes/init.php';

function getEmployeesList($company_id = null, $page = 1, $limit = 5, $search = '')
{
    $company_id = sanitizeInput($company_id);
    if ($company_id !== null) {
        $company_id = filter_var($company_id, FILTER_VALIDATE_INT);
    }
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);
    $search = sanitizeInput($search);

    $offset = ($page - 1) * $limit;

    $query = "SELECT p.id, p.nom, p.prenom, p.email, p.telephone, p.statut, p.photo_url, 
              p.derniere_connexion, e.nom as entreprise_nom 
              FROM personnes p
              LEFT JOIN entreprises e ON p.entreprise_id = e.id
              WHERE p.role_id = ?";
    $countQuery = "SELECT COUNT(id) as total FROM personnes WHERE role_id = ?";
    $params = [ROLE_SALARIE];

    if ($company_id) {
        $query .= " AND p.entreprise_id = ?";
        $countQuery .= " AND entreprise_id = ?";
        $params[] = $company_id;
    }

    if (!empty($search)) {
        $query .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR p.email LIKE ?)";
        $countQuery .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }

    $query .= " ORDER BY p.nom, p.prenom ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = executeQuery($query, $params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($employees as &$employee) {
        if (isset($employee['statut'])) {
            $employee['statut_badge'] = getStatusBadge($employee['statut']);
        }
        if (isset($employee['derniere_connexion'])) {
            $employee['derniere_connexion_formatee'] = formatDate($employee['derniere_connexion']);
        }
    }

    $countStmt = executeQuery($countQuery, array_slice($params, 0, -2));
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $countResult['total'];

    $totalPages = ceil($total / $limit);

    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

    $urlPattern = "?page={page}";
    if ($company_id) {
        $urlPattern .= "&company_id=" . urlencode($company_id);
    }
    if (!empty($search)) {
        $urlPattern .= "&search=" . urlencode($search);
    }

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
    // Validation de l'ID
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        flashMessage("ID de salarié invalide", "danger");
        return false;
    }

    $employee = fetchOne('personnes', "id = $employee_id AND role_id = " . ROLE_SALARIE);

    if (!$employee) {
        flashMessage("Salarié non trouvé", "warning");
        return false;
    }

    if (isset($employee['statut'])) {
        $employee['statut_badge'] = getStatusBadge($employee['statut']);
    }

    if (isset($employee['date_naissance'])) {
        $employee['date_naissance_formatee'] = formatDate($employee['date_naissance'], 'd/m/Y');
    }
    if (isset($employee['derniere_connexion'])) {
        $employee['derniere_connexion_formatee'] = formatDate($employee['derniere_connexion']);
    }

    if (!empty($employee['entreprise_id'])) {
        $entreprise = fetchOne('entreprises', "id = " . $employee['entreprise_id']);
        if ($entreprise) {
            $employee['entreprise_nom'] = $entreprise['nom'];
        }
    }

    $preferences = fetchAll('preferences_utilisateurs', "personne_id = " . $employee_id);
    if (!empty($preferences)) {
        $employee['preferences'] = $preferences[0];
    }

    return $employee;
}


function updateEmployeeProfile($employee_id, $profile_data)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $profile_data = sanitizeInput($profile_data);

    if (!$employee_id || empty($profile_data)) {
        flashMessage("ID de salarié invalide ou données manquantes", "danger");
        return false;
    }

    $validation_errors = [];

    if (isset($profile_data['email']) && !filter_var($profile_data['email'], FILTER_VALIDATE_EMAIL)) {
        $validation_errors[] = "Format d'email invalide";
    }

    if (isset($profile_data['telephone'])) {
        $phone_to_check = $profile_data['telephone'];
        $is_match = preg_match('/^(0|\+33)[1-9]([-. ]?[0-9]{2}){4}$/', $phone_to_check);

        error_log("Vérification Tel: '$phone_to_check' - Match: " . ($is_match ? 'Oui' : 'Non'));

        if (!$is_match) {
            $validation_errors[] = "Format de téléphone invalide";
        }
    }

    if (isset($profile_data['genre']) && !in_array($profile_data['genre'], ['F', 'M'])) {
        $validation_errors[] = "La valeur pour le genre doit être 'F' ou 'M'";
    }

    error_log("Validation errors after checks: " . print_r($validation_errors, true));

    if (!empty($validation_errors)) {
        error_log("Validation errors detected. Stopping update."); // Log avant arrêt
        flashMessage("Erreurs de validation: " . implode(", ", $validation_errors), "danger");
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
        flashMessage("Aucun champ valide trouvé pour la mise à jour", "warning");
        return false;
    }

    try {
        // Utilisation de updateRow pour la mise à jour
        $result = updateRow(
            'personnes',
            array_merge($filteredData, ['updated_at' => date('Y-m-d H:i:s')]),
            "id = :id AND role_id = :role_id",
            ['id' => $employee_id, 'role_id' => ROLE_SALARIE]
        );

        if ($result) {
            logBusinessOperation($_SESSION['user_id'] ?? null, 'update_employee', "Mise à jour profil salarié #$employee_id");
            flashMessage("Le profil a été mis à jour avec succès", "success");
        } else {
            flashMessage("Aucune mise à jour n'a été effectuée", "info");
        }

        return $result;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur mise à jour profil: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la mise à jour du profil", "danger");
        return false;
    }
}

/**
 * récupère les services disponibles pour un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @return array liste des services disponibles
 */
function getEmployeeAvailableServices($employee_id)
{
    // Validation de l'ID
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        return [];
    }

    // Récupération de l'entreprise du salarié
    $employee = fetchOne('personnes', "id = $employee_id AND role_id = " . ROLE_SALARIE);

    if (!$employee || empty($employee['entreprise_id'])) {
        return [];
    }

    $company_id = $employee['entreprise_id'];

    // Vérification des contrats actifs de l'entreprise
    $contractsCount = executeQuery(
        "SELECT COUNT(*) as count FROM contrats 
         WHERE entreprise_id = ? AND statut = 'actif'
         AND (date_fin IS NULL OR date_fin >= CURDATE())",
        [$company_id]
    )->fetch()['count'];

    if ($contractsCount == 0) {
        return [];
    }

    // Récupération des services disponibles
    $query = "SELECT id, nom, description, type, categorie, 
              niveau_difficulte, duree, capacite_max 
              FROM prestations 
              WHERE id IN (
                  SELECT DISTINCT prestation_id 
                  FROM rendez_vous 
                  WHERE personne_id IN (
                      SELECT id FROM personnes 
                      WHERE entreprise_id = ? AND role_id = ?
                  )
              ) OR type IN ('conference', 'webinar', 'atelier', 'evenement')
              ORDER BY type, nom";

    $services = executeQuery($query, [$company_id, ROLE_SALARIE])->fetchAll();

    // Formater les prix des services si présents
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
 * @param string $status filtre par statut (all, planifie, confirme, annule, termine)
 * @return array liste des réservations
 */
function getEmployeeReservations($employee_id, $status = 'all')
{
    // Validation de l'ID
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        return [];
    }

    // Construction de la requête
    $query = "SELECT r.*, p.nom as prestation_nom, p.type as prestation_type, 
              CONCAT(pers.prenom, ' ', pers.nom) as prestataire_nom
              FROM rendez_vous r
              JOIN prestations p ON r.prestation_id = p.id
              JOIN personnes pers ON r.personne_id = pers.id
              WHERE r.personne_id = ?";
    $params = [$employee_id];

    // Filtre par statut si différent de 'all'
    if ($status !== 'all') {
        $query .= " AND r.statut = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY r.date_rdv DESC";

    $reservations = executeQuery($query, $params)->fetchAll();

    // Formater les dates et ajouter les badges de statut
    foreach ($reservations as &$reservation) {
        if (isset($reservation['date_rdv'])) {
            $reservation['date_rdv_formatee'] = formatDate($reservation['date_rdv'], 'd/m/Y H:i');
        }
        if (isset($reservation['statut'])) {
            $reservation['statut_badge'] = getStatusBadge($reservation['statut']);
        }
    }

    return $reservations;
}

/**
 * récupère les rendez-vous d'un salarié avec pagination
 * 
 * @param int $employee_id identifiant du salarié
 * @param string $status statut des rendez-vous à récupérer (upcoming, past, canceled)
 * @param int $page numéro de page
 * @param int $limit nombre d'éléments par page
 * @return array rendez-vous et informations de pagination
 */
function getEmployeeAppointments($employee_id, $status = 'upcoming', $page = 1, $limit = 5)
{
    // Validation et sanitization
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $status = sanitizeInput($status);
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);

    // Vérification de l'ID employé
    if (!$employee_id) {
        error_log("ID d'employé invalide dans getEmployeeAppointments");
        return [
            'appointments' => [],
            'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
            'pagination_html' => ''
        ];
    }

    // Calcul de l'offset pour la pagination
    $offset = ($page - 1) * $limit;

    // Construction de la requête de base
    $query = "SELECT rv.id as rdv_id, rv.date_rdv, rv.duree, rv.type_rdv, rv.statut, rv.lieu, rv.notes,
              p.id as prestation_id, p.nom as prestation_nom, p.description as prestation_description, 
              pp.id as praticien_id, CONCAT(pp.prenom, ' ', pp.nom) as praticien_nom,
              e.id as evenement_id, e.titre as evenement_titre, e.lieu as evenement_lieu
              FROM rendez_vous rv
              LEFT JOIN prestations p ON rv.prestation_id = p.id
              LEFT JOIN personnes pp ON rv.praticien_id = pp.id
              LEFT JOIN evenements e ON rv.evenement_id = e.id
              WHERE rv.personne_id = ?";

    // Conditions selon le statut demandé
    $params = [$employee_id];

    $now = date('Y-m-d H:i:s');

    switch ($status) {
        case 'upcoming':
            $query .= " AND rv.date_rdv > ? AND rv.statut NOT IN ('annule', 'manque')";
            $params[] = $now;
            $query .= " ORDER BY rv.date_rdv ASC";
            break;

        case 'past':
            $query .= " AND (rv.date_rdv < ? AND rv.statut NOT IN ('annule', 'manque'))";
            $params[] = $now;
            $query .= " ORDER BY rv.date_rdv DESC";
            break;

        case 'canceled':
            $query .= " AND rv.statut IN ('annule', 'manque')";
            $query .= " ORDER BY rv.date_rdv DESC";
            break;

        default:
            $query .= " ORDER BY rv.date_rdv DESC";
    }

    // Ajout de la limitation et offset
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    // Comptage du nombre total d'éléments pour la pagination
    $countQuery = "SELECT COUNT(*) as total FROM rendez_vous rv WHERE rv.personne_id = ?";
    $countParams = [$employee_id];

    switch ($status) {
        case 'upcoming':
            $countQuery .= " AND rv.date_rdv > ? AND rv.statut NOT IN ('annule', 'manque')";
            $countParams[] = $now;
            break;

        case 'past':
            $countQuery .= " AND (rv.date_rdv < ? AND rv.statut NOT IN ('annule', 'manque'))";
            $countParams[] = $now;
            break;

        case 'canceled':
            $countQuery .= " AND rv.statut IN ('annule', 'manque')";
            break;
    }

    // Exécution de la requête principale
    $stmt = executeQuery($query, $params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque rendez-vous, formater les données
    foreach ($appointments as &$appointment) {
        // Formatage de la date
        if (isset($appointment['date_rdv'])) {
            $date = new DateTime($appointment['date_rdv']);
            $appointment['date_rdv_formatee'] = $date->format('d/m/Y H:i');
        }

        // Formatage du statut avec un badge
        if (isset($appointment['statut'])) {
            $appointment['statut_badge'] = getStatusBadge($appointment['statut']);
        }
    }

    // Comptage du nombre total pour la pagination
    $countStmt = executeQuery($countQuery, $countParams);
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $countResult['total'];

    // Calcul des informations de pagination
    $totalPages = ceil($total / $limit);

    // Préparer les données pour la pagination
    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

    // Construction de l'URL pour la pagination
    $urlPattern = "?";

    switch ($status) {
        case 'upcoming':
            $urlPattern .= "upcoming_page={page}&past_page=" . sanitizeInput($_GET['past_page'] ?? 1) . "&canceled_page=" . sanitizeInput($_GET['canceled_page'] ?? 1);
            break;

        case 'past':
            $urlPattern .= "upcoming_page=" . sanitizeInput($_GET['upcoming_page'] ?? 1) . "&past_page={page}&canceled_page=" . sanitizeInput($_GET['canceled_page'] ?? 1);
            break;

        case 'canceled':
            $urlPattern .= "upcoming_page=" . sanitizeInput($_GET['upcoming_page'] ?? 1) . "&past_page=" . sanitizeInput($_GET['past_page'] ?? 1) . "&canceled_page={page}";
            break;
    }

    return [
        'appointments' => $appointments,
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
 * Génère le HTML de pagination
 * 
 * @param array $paginationData Données de pagination (currentPage, totalPages, totalItems, perPage)
 * @param string $urlPattern Modèle d'URL pour les liens de pagination
 * @param string $ariaLabel Texte pour l'attribut aria-label
 * @return string HTML de pagination
 */
function generatePaginationHtml($paginationData, $urlPattern, $ariaLabel = 'Pagination')
{
    if ($paginationData['totalPages'] <= 1) {
        return '';
    }

    $html = '<nav aria-label="' . htmlspecialchars($ariaLabel) . '">';
    $html .= '<ul class="pagination">';

    // Premier et Précédent
    if ($paginationData['currentPage'] > 1) {
        $firstUrl = str_replace('{page}', '1', $urlPattern);
        $prevUrl = str_replace('{page}', $paginationData['currentPage'] - 1, $urlPattern);

        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $firstUrl . '" aria-label="Première">';
        $html .= '<span aria-hidden="true">&laquo;&laquo;</span>';
        $html .= '</a></li>';

        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $prevUrl . '" aria-label="Précédente">';
        $html .= '<span aria-hidden="true">&laquo;</span>';
        $html .= '</a></li>';
    }

    // Pages
    for ($i = max(1, $paginationData['currentPage'] - 2); $i <= min($paginationData['totalPages'], $paginationData['currentPage'] + 2); $i++) {
        $pageUrl = str_replace('{page}', $i, $urlPattern);
        $activeClass = ($i == $paginationData['currentPage']) ? ' active' : '';

        $html .= '<li class="page-item' . $activeClass . '">';
        $html .= '<a class="page-link" href="' . $pageUrl . '">' . $i . '</a>';
        $html .= '</li>';
    }

    // Suivant et Dernier
    if ($paginationData['currentPage'] < $paginationData['totalPages']) {
        $nextUrl = str_replace('{page}', $paginationData['currentPage'] + 1, $urlPattern);
        $lastUrl = str_replace('{page}', $paginationData['totalPages'], $urlPattern);

        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $nextUrl . '" aria-label="Suivante">';
        $html .= '<span aria-hidden="true">&raquo;</span>';
        $html .= '</a></li>';

        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $lastUrl . '" aria-label="Dernière">';
        $html .= '<span aria-hidden="true">&raquo;&raquo;</span>';
        $html .= '</a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

/**
 * récupère l'historique d'activités d'un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @param int $page numéro de page
 * @param int $limit nombre d'éléments par page
 * @return array historique d'activités et informations de pagination
 */
function getEmployeeActivityHistory($employee_id, $page = 1, $limit = 5)
{
    // Validation de l'ID
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);

    if (!$employee_id) {
        flashMessage("ID de salarié invalide", "danger");
        return [
            'activities' => [],
            'pagination' => [
                'current' => $page,
                'limit' => $limit,
                'total' => 0,
                'totalPages' => 0
            ]
        ];
    }

    try {
        // Utilisation de la structure de pagination commune
        $where = "personne_id = " . $employee_id;
        $orderBy = "created_at DESC";

        // Exécution de la requête principale avec pagination
        $pagination = paginateResults('logs', $page, $limit, $where, $orderBy);

        // Formatage spécifique pour l'historique d'activités
        foreach ($pagination['items'] as &$activity) {
            // Formatage de la date
            $activity['created_at_formatted'] = formatDate($activity['created_at']);
            // Ajout d'une icône en fonction de l'action
            $activity['icon'] = getActivityIcon($activity['action']);
        }

        // Préparer les données pour renderPagination
        $paginationData = [
            'currentPage' => $pagination['currentPage'],
            'totalPages' => $pagination['totalPages'],
            'totalItems' => $pagination['totalItems'],
            'perPage' => $pagination['perPage']
        ];

        // Construction de l'URL pattern pour la pagination
        $urlPattern = "?employee_id=$employee_id&page={page}";

        return [
            'activities' => $pagination['items'],
            'pagination' => [
                'current' => $pagination['currentPage'],
                'limit' => $pagination['perPage'],
                'total' => $pagination['totalItems'],
                'totalPages' => $pagination['totalPages']
            ],
            'pagination_html' => renderPagination($paginationData, $urlPattern)
        ];
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération historique activités: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération de l'historique d'activités", "danger");
        return [
            'activities' => [],
            'pagination' => [
                'current' => $page,
                'limit' => $limit,
                'total' => 0,
                'totalPages' => 0
            ]
        ];
    }
}

/**
 * Obtient une icône correspondant à un type d'activité
 * 
 * @param string $action type d'action
 * @return string classe CSS de l'icône
 */
function getActivityIcon($action)
{
    $iconMap = [
        'login' => 'fas fa-sign-in-alt',
        'logout' => 'fas fa-sign-out-alt',
        'update_profile' => 'fas fa-user-edit',
        'reservation' => 'fas fa-calendar-check',
        'evaluation' => 'fas fa-star',
        'don' => 'fas fa-hand-holding-heart',
        'inscription' => 'fas fa-user-plus',
        'paiement' => 'fas fa-credit-card'
    ];

    return $iconMap[$action] ?? 'fas fa-history';
}

/**
 * récupère les communautés accessibles à un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @return array liste des communautés
 */
function getEmployeeCommunities($employee_id)
{
    // Validation de l'ID
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        return [];
    }

    // Récupération des communautés - utiliser fetchAll au lieu de PDO directement
    $communities = fetchAll('communautes', '1=1', 'type, nom');

    return $communities;
}

/**
 * gère les dons d'un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @param array $donation_data données du don
 * @return int|false ID du don créé ou false en cas d'erreur
 */
function manageEmployeeDonations($employee_id, $donation_data)
{
    // Validation des paramètres
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $donation_data = sanitizeInput($donation_data);

    if (!$employee_id || empty($donation_data)) {
        flashMessage("Paramètres invalides pour le don", "danger");
        return false;
    }

    // Vérification des données requises
    if (
        empty($donation_data['type']) ||
        ($donation_data['type'] == 'financier' && empty($donation_data['montant'])) ||
        ($donation_data['type'] == 'materiel' && empty($donation_data['description']))
    ) {
        flashMessage("Veuillez remplir tous les champs obligatoires", "warning");
        return false;
    }

    // Vérifier que le type est bien une valeur acceptée par l'enum de la base de données
    if (!in_array($donation_data['type'], ['financier', 'materiel'])) {
        flashMessage("Type de don invalide", "danger");
        return false;
    }

    // Validation supplémentaire pour les montants financiers
    if ($donation_data['type'] == 'financier') {
        $montant = filter_var($donation_data['montant'], FILTER_VALIDATE_FLOAT);
        if ($montant === false || $montant <= 0) {
            flashMessage("Le montant du don doit être un nombre positif", "warning");
            return false;
        }
        $donation_data['montant'] = $montant;
    }

    try {
        // Vérifier que le salarié existe et est actif
        $employee = fetchOne('personnes', "id = $employee_id AND role_id = " . ROLE_SALARIE . " AND statut = 'actif'");
        if (!$employee) {
            flashMessage("Le salarié n'existe pas ou n'est pas actif", "danger");
            return false;
        }

        // Début de transaction
        beginTransaction();

        // Préparation des données pour insertion
        $donData = [
            'personne_id' => $employee_id,
            'montant' => $donation_data['type'] == 'financier' ? $donation_data['montant'] : null,
            'type' => $donation_data['type'],
            'description' => $donation_data['description'] ?? null,
            'date_don' => date('Y-m-d'),
            'statut' => 'en_attente'
        ];

        // Ajouter l'association_id si spécifié
        if (!empty($donation_data['association_id'])) {
            $association_id = filter_var($donation_data['association_id'], FILTER_VALIDATE_INT);
            if ($association_id) {
                // Vérifier que l'association existe
                $association = fetchOne('associations', "id = $association_id AND actif = 1");
                if ($association) {
                    $donData['association_id'] = $association_id;
                }
            }
        }

        // Insertion du don
        $donationId = insertRow('dons', $donData);

        if (!$donationId) {
            rollbackTransaction();
            flashMessage("Impossible d'enregistrer votre don", "danger");
            return false;
        }

        // Si c'est un don financier et qu'il y a un montant, créer une entrée dans les transactions
        if ($donation_data['type'] == 'financier' && !empty($donation_data['montant'])) {
            $transactionData = [
                'personne_id' => $employee_id,
                'montant' => $donation_data['montant'],
                'type' => 'don',
                'reference' => 'DON-' . $donationId,
                'date_transaction' => date('Y-m-d H:i:s'),
                'statut' => 'en_attente'
            ];

            $transactionId = insertRow('transactions', $transactionData);
            if (!$transactionId) {
                rollbackTransaction();
                flashMessage("Erreur lors de l'enregistrement de la transaction", "danger");
                return false;
            }
        }

        // Validation de la transaction
        commitTransaction();

        // Journalisation
        logBusinessOperation($employee_id, 'don_creation', "Don #{$donationId} créé, type: {$donation_data['type']}");

        // Notification utilisateur
        if ($donation_data['type'] == 'financier') {
            $montantFormatted = formatMoney($donation_data['montant']);
            flashMessage("Votre don financier de {$montantFormatted} a été enregistré et est en attente de traitement", "success");
        } else {
            flashMessage("Votre don matériel a été enregistré. Nous vous contacterons pour organiser la collecte", "success");
        }

        return $donationId;
    } catch (Exception $e) {
        // S'assurer que la transaction est annulée en cas d'erreur
        if (isset($transactionStarted) && $transactionStarted) {
            rollbackTransaction();
        }
        logSystemActivity('error', "Erreur création don: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de l'enregistrement de votre don", "danger");
        return false;
    }
}

/**
 * récupère les événements et défis disponibles pour un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @param string $event_type filtre par type d'événement (all, conference, webinar, atelier, defi_sportif)
 * @return array liste des événements
 */
function getEmployeeEvents($employee_id, $event_type = 'all')
{
    // Validation de l'ID
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        return [];
    }

    // Construction de la requête
    $query = "SELECT id, titre, description, date_debut, date_fin, lieu, type, 
              capacite_max, niveau_difficulte 
              FROM evenements 
              WHERE date_debut >= CURDATE()";
    $params = [];

    // Filtre par type si différent de 'all'
    if ($event_type !== 'all') {
        $query .= " AND type = ?";
        $params[] = $event_type;
    }

    $query .= " ORDER BY date_debut, titre";

    $events = executeQuery($query, $params)->fetchAll();

    // Formater les dates
    foreach ($events as &$event) {
        if (isset($event['date_debut'])) {
            $event['date_debut_formatted'] = formatDate($event['date_debut'], 'd/m/Y');
        }
        if (isset($event['date_fin'])) {
            $event['date_fin_formatee'] = formatDate($event['date_fin'], 'd/m/Y');
        }
    }

    return $events;
}

/**
 * met à jour les préférences d'un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @param array $settings paramètres à mettre à jour
 * @return bool résultat de la mise à jour
 */
function updateEmployeeSettings($employee_id, $settings)
{
    // Validation de l'ID
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $settings = sanitizeInput($settings);

    if (!$employee_id || empty($settings)) {
        flashMessage("ID de salarié invalide ou paramètres manquants", "danger");
        return false;
    }

    // Liste des champs autorisés
    $allowedFields = ['langue', 'notif_email', 'theme'];

    // Filtrage des paramètres
    $filteredSettings = array_intersect_key($settings, array_flip($allowedFields));

    if (empty($filteredSettings)) {
        flashMessage("Aucun paramètre valide à mettre à jour", "warning");
        return false;
    }

    try {
        // Vérification de l'existence des préférences
        $exists = fetchOne('preferences_utilisateurs', "personne_id = $employee_id");

        $result = false;

        if ($exists) {
            // Mise à jour
            $result = updateRow(
                'preferences_utilisateurs',
                $filteredSettings,
                'personne_id = :personne_id',
                ['personne_id' => $employee_id]
            );
        } else {
            // Insertion
            $filteredSettings['personne_id'] = $employee_id;
            $result = insertRow('preferences_utilisateurs', $filteredSettings) ? true : false;
        }

        if ($result) {
            logBusinessOperation($employee_id, 'update_preferences', "Mise à jour des préférences utilisateur");
            flashMessage("Vos préférences ont été mises à jour", "success");

            // Mise à jour de la session si c'est l'utilisateur courant
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

/**
 * Vérifie si une prestation est disponible à une date donnée
 * Cette fonction retourne toujours true pour permettre de s'inscrire 
 * plusieurs fois à la même prestation
 * 
 * @param int $prestation_id ID de la prestation
 * @param string $date_rdv Date du rendez-vous au format 'Y-m-d H:i:s'
 * @return bool True si disponible, false sinon
 */
function isPrestationAvailable($prestation_id, $date_rdv)
{
    // On retourne toujours true pour permettre les inscriptions multiples
    return true;
}

/**
 * récupère les prestations disponibles pour un salarié avec pagination
 * 
 * @param int $employee_id identifiant du salarié
 * @param int $page numéro de page
 * @param int $limit nombre d'éléments par page
 * @return array prestations et informations de pagination
 */
function getAvailablePrestationsForEmployee($employee_id, $page = 1, $limit = 20)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);

    if (!$employee_id) {
        return [
            'prestations' => [],
            'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
            'pagination_html' => ''
        ];
    }

    $offset = ($page - 1) * $limit;

    $tomorrow = date('Y-m-d', strtotime('+1 day')); // Correction: Réinsérer la définition de $tomorrow

    // Note: On récupère toutes les prestations disponibles, sans filtrer par contrat entreprise pour le moment
    // car la logique exacte dépend des règles métier (toutes prestations dispo? ou seulement celles du contrat?)
    $query = "SELECT p.id, p.nom, p.description, p.prix, p.duree, p.type, p.date_heure_disponible, 
              p.capacite_max, p.niveau_difficulte, p.lieu, p.est_disponible, p.categorie,
              pp.id as praticien_id, CONCAT(pp.prenom, ' ', pp.nom) as praticien_nom
              FROM prestations p
              LEFT JOIN personnes pp ON p.praticien_id = pp.id
              WHERE p.est_disponible = TRUE
              ORDER BY p.nom ASC
              LIMIT ? OFFSET ?";

    $params = [$limit, $offset];

    $stmt = executeQuery($query, $params);
    $prestations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($prestations as &$prestation) {
        if (isset($prestation['prix'])) {
            $prix = floatval($prestation['prix']);
            $prestation['prix_formate'] = ($prix > 0) ? number_format($prix, 2, ',', ' ') . ' €' : 'Gratuit';
        } else {
            $prestation['prix_formate'] = 'Gratuit';
        }

        if (!empty($prestation['date_heure_disponible'])) {
            $date = new DateTime($prestation['date_heure_disponible']);
            $prestation['date_disponible_formatee'] = $date->format('d/m/Y à H:i');
        } else {
            $prestation['date_heure_disponible'] = $tomorrow . ' 09:00:00';
            $prestation['date_disponible_formatee'] = 'Non précisée';
        }

        if (!isset($prestation['est_disponible'])) {
            $prestation['est_disponible'] = true;
        }
    }

    $countQuery = "SELECT COUNT(*) as total FROM prestations";
    $countStmt = executeQuery($countQuery, []);
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = $countResult['total'];

    $totalPages = ceil($total / $limit);

    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

    $urlPattern = "?prestation_page={page}";

    return [
        'prestations' => $prestations,
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
 * annule un rendez-vous
 * 
 * @param int $employee_id identifiant du salarié
 * @param int $rdv_id identifiant du rendez-vous
 * @return bool résultat de l'annulation
 */
function cancelEmployeeAppointment($employee_id, $rdv_id)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $rdv_id = filter_var(sanitizeInput($rdv_id), FILTER_VALIDATE_INT);

    if (!$employee_id || !$rdv_id) {
        return false;
    }

    $query = "SELECT prestation_id FROM rendez_vous WHERE id = ? AND personne_id = ?";
    $result = executeQuery($query, [$rdv_id, $employee_id])->fetch();

    if (!$result) {
        return false;
    }

    $updateQuery = "UPDATE rendez_vous SET statut = 'annule' WHERE id = ?";
    $success = executeQuery($updateQuery, [$rdv_id])->rowCount() > 0;

    if ($success && !empty($result['prestation_id'])) {
        $updatePrestationQuery = "UPDATE prestations SET est_disponible = TRUE WHERE id = ?";
        executeQuery($updatePrestationQuery, [$result['prestation_id']]);
    }

    return $success;
}

/**
 * réserve un rendez-vous pour un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @param array $appointment_data données du rendez-vous
 * @return bool|int résultat de la réservation (id du rendez-vous ou false)
 */
function bookEmployeeAppointment($employee_id, $appointment_data)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);

    if (!$employee_id || empty($appointment_data)) {
        return false;
    }

    $prestation_id = filter_var($appointment_data['prestation_id'] ?? 0, FILTER_VALIDATE_INT);
    $praticien_id = filter_var($appointment_data['praticien_id'] ?? 0, FILTER_VALIDATE_INT);
    $date_rdv = sanitizeInput($appointment_data['date_rdv'] ?? '');
    $duree = filter_var($appointment_data['duree'] ?? 0, FILTER_VALIDATE_INT);
    $type_rdv = sanitizeInput($appointment_data['type_rdv'] ?? '');
    $lieu = sanitizeInput($appointment_data['lieu'] ?? '');
    $notes = sanitizeInput($appointment_data['notes'] ?? '');

    if (!$prestation_id || empty($date_rdv) || $duree <= 0) {
        return false;
    }

    try {
        // Vérification de l'existence du praticien
        $praticienExists = false;
        if ($praticien_id) {
            $checkPraticien = "SELECT id FROM personnes WHERE id = ? AND role_id = " . ROLE_PRESTATAIRE;
            $praticienExists = executeQuery($checkPraticien, [$praticien_id])->fetch();

            if (!$praticienExists) {
                // Si le praticien n'existe pas, on met praticien_id à NULL
                $praticien_id = null;
            }
        } else {
            // Si praticien_id est 0, on le met à NULL
            $praticien_id = null;
        }

        $checkPrestation = "SELECT id, est_disponible FROM prestations WHERE id = ?";
        $prestation = executeQuery($checkPrestation, [$prestation_id])->fetch();

        if (!$prestation || !$prestation['est_disponible']) {
            return false;
        }

        if (empty($date_rdv)) {
            $date_rdv = date('Y-m-d H:i:s', strtotime('+1 day 9:00:00'));
        }

        // Vérifions s'il existe au moins un événement dans la base de données
        $eventQuery = "SELECT id FROM evenements ORDER BY id LIMIT 1";
        $eventResult = executeQuery($eventQuery)->fetch();

        // Si un événement existe, utilisons son ID, sinon nous devrons omettre ce champ
        if ($eventResult && isset($eventResult['id'])) {
            $insertQuery = "INSERT INTO rendez_vous (personne_id, prestation_id, praticien_id, date_rdv, duree, 
                            type_rdv, lieu, notes, statut, created_at, evenement_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirme', NOW(), ?)";

            $insertParams = [$employee_id, $prestation_id, $praticien_id, $date_rdv, $duree, $type_rdv, $lieu, $notes, $eventResult['id']];
        } else {
            // Omettre evenement_id de la requête si aucun événement n'existe
            $insertQuery = "INSERT INTO rendez_vous (personne_id, prestation_id, praticien_id, date_rdv, duree, 
                            type_rdv, lieu, notes, statut, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirme', NOW())";

            $insertParams = [$employee_id, $prestation_id, $praticien_id, $date_rdv, $duree, $type_rdv, $lieu, $notes];
        }

        $success = executeQuery($insertQuery, $insertParams)->rowCount() > 0;

        if ($success) {
            $rdvId = executeQuery("SELECT LAST_INSERT_ID()")->fetchColumn();

            $updatePrestation = "UPDATE prestations SET est_disponible = FALSE WHERE id = ?";
            executeQuery($updatePrestation, [$prestation_id]);

            return $rdvId;
        }

        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Récupère les horaires disponibles pour une prestation
 * 
 * @param int $prestation_id ID de la prestation
 * @return array Liste des horaires disponibles (date, heure, lieu)
 */
function getAvailableSchedulesForPrestation($prestation_id)
{
    $prestation_id = filter_var($prestation_id, FILTER_VALIDATE_INT);

    if (!$prestation_id) {
        return [];
    }

    $query = "SELECT p.id, p.nom, p.description, p.duree, p.type, p.niveau_difficulte, 
              p.praticien_id, p.date_heure_disponible, p.lieu, p.est_disponible,
              CONCAT(pers.prenom, ' ', pers.nom) as praticien_nom
              FROM prestations p
              LEFT JOIN personnes pers ON p.praticien_id = pers.id
              WHERE p.id = ?";

    $prestation = executeQuery($query, [$prestation_id])->fetch();

    if (!$prestation || empty($prestation['date_heure_disponible'])) {
        return [];
    }

    $schedules = [];
    $now = new DateTime();

    try {
        $dateHoraire = new DateTime($prestation['date_heure_disponible']);

        if ($dateHoraire > $now) {
            $schedules[] = [
                'id' => $prestation_id . '-0',
                'date_value' => $prestation['date_heure_disponible'],
                'date_debut_formattee' => formatDate($prestation['date_heure_disponible'], 'd/m/Y H:i'),
                'duree' => $prestation['duree'],
                'titre' => $prestation['nom'],
                'disponibilite' => '1 place(s) disponible(s)',
                'praticien_nom' => $prestation['praticien_nom'] ?? 'Non assigné',
                'lieu' => $prestation['lieu'] ?? 'À déterminer'
            ];
        }
    } catch (Exception $e) {
    }

    return $schedules;
}

/**
 * récupère les communautés dont un salarié est membre
 * 
 * @param int $employee_id identifiant du salarié
 * @return array liste des communautés
 */
function getEmployeeMemberships($employee_id)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        return [];
    }

    $query = "SELECT c.id, c.nom, c.description, c.type, c.niveau, c.capacite_max,
              cm.date_inscription, cm.role_communaute,
              (SELECT COUNT(*) FROM communautes_membres WHERE communaute_id = c.id) as nombre_membres
              FROM communautes c
              JOIN communautes_membres cm ON c.id = cm.communaute_id
              WHERE cm.personne_id = ?
              ORDER BY cm.date_inscription DESC";

    $memberships = executeQuery($query, [$employee_id])->fetchAll();

    foreach ($memberships as &$membership) {
        $membership['type_class'] = getCommunityTypeClass($membership['type']);

        if (isset($membership['date_inscription'])) {
            $membership['date_inscription_formatted'] = formatDate($membership['date_inscription']);
        }

        $membership['role_badge'] = getCommunityRoleBadge($membership['role_communaute']);
    }

    return $memberships;
}

/**
 * récupère les détails d'une communauté
 * 
 * @param int $community_id identifiant de la communauté
 * @param int $employee_id identifiant du salarié
 * @return array|false détails de la communauté ou false si non trouvée
 */
function getCommunityDetails($community_id, $employee_id = null)
{
    $community_id = filter_var(sanitizeInput($community_id), FILTER_VALIDATE_INT);
    if (!$community_id) {
        flashMessage("ID de communauté invalide", "danger");
        return false;
    }

    if ($employee_id !== null) {
        $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    }

    $community = fetchOne('communautes', "id = $community_id");

    if (!$community) {
        flashMessage("Communauté non trouvée", "warning");
        return false;
    }

    $community['type_class'] = getCommunityTypeClass($community['type']);

    if (isset($community['created_at'])) {
        $community['created_at_formatted'] = formatDate($community['created_at']);
    }
    if (isset($community['updated_at'])) {
        $community['updated_at_formatted'] = formatDate($community['updated_at']);
    }

    if ($employee_id) {
        $query = "SELECT COUNT(*) as count FROM communautes_membres 
                 WHERE communaute_id = ? AND personne_id = ?";
        $result = executeQuery($query, [$community_id, $employee_id])->fetch();
        $community['est_membre'] = (int)$result['count'] > 0;
    } else {
        $community['est_membre'] = false;
    }

    $query = "SELECT COUNT(*) as count FROM communautes_membres WHERE communaute_id = ?";
    $result = executeQuery($query, [$community_id])->fetch();
    $community['nombre_membres'] = (int)$result['count'];

    $community['derniers_messages'] = getCommunityLastMessages($community_id);

    $community['evenements'] = getCommunityEvents($community_id);

    return $community;
}

/**
 * récupère les membres d'une communauté
 * 
 * @param int $community_id identifiant de la communauté
 * @param int $page numéro de page
 * @param int $limit nombre d'éléments par page
 * @return array liste des membres et informations de pagination
 */
function getCommunityMembers($community_id, $page = 1, $limit = 20)
{
    $community_id = filter_var(sanitizeInput($community_id), FILTER_VALIDATE_INT);
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);

    if (!$community_id) {
        return [
            'members' => [],
            'pagination' => [
                'current' => $page,
                'limit' => $limit,
                'total' => 0,
                'totalPages' => 0
            ],
            'pagination_html' => ''
        ];
    }

    $offset = ($page - 1) * $limit;

    $query = "SELECT p.id, p.nom, p.prenom, p.email, p.photo_url, p.entreprise_id,
              cm.date_inscription, cm.role_communaute,
              e.nom as entreprise_nom
              FROM communautes_membres cm
              JOIN personnes p ON cm.personne_id = p.id
              LEFT JOIN entreprises e ON p.entreprise_id = e.id
              WHERE cm.communaute_id = ?
              ORDER BY cm.role_communaute DESC, p.nom, p.prenom
              LIMIT ? OFFSET ?";

    $members = executeQuery($query, [$community_id, $limit, $offset])->fetchAll();

    foreach ($members as &$member) {
        if (isset($member['date_inscription'])) {
            $member['date_inscription_formatted'] = formatDate($member['date_inscription']);
        }

        $member['role_badge'] = getCommunityRoleBadge($member['role_communaute']);
    }

    $countQuery = "SELECT COUNT(*) as total FROM communautes_membres WHERE communaute_id = ?";
    $total = executeQuery($countQuery, [$community_id])->fetch()['total'];

    $totalPages = ceil($total / $limit);

    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

    $urlPattern = "?id=$community_id&members_page={page}";

    return [
        'members' => $members,
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
 * récupère les messages d'une communauté avec pagination
 * 
 * @param int $community_id identifiant de la communauté
 * @param int $page numéro de page
 * @param int $limit nombre d'éléments par page
 * @return array messages et informations de pagination
 */
function getCommunityMessages($community_id, $page = 1, $limit = 10)
{
    $community_id = filter_var(sanitizeInput($community_id), FILTER_VALIDATE_INT);
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);

    if (!$community_id) {
        return [
            'messages' => [],
            'pagination' => [
                'current' => $page,
                'limit' => $limit,
                'total' => 0,
                'totalPages' => 0
            ],
            'pagination_html' => ''
        ];
    }

    $offset = ($page - 1) * $limit;

    $query = "SELECT m.id, m.contenu, m.date_creation, m.est_modere, m.raison_moderation,
              p.id as auteur_id, p.nom as auteur_nom, p.prenom as auteur_prenom, p.photo_url,
              (SELECT MAX(role_communaute) FROM communautes_membres WHERE personne_id = p.id AND communaute_id = ?) as role_membre
              FROM communautes_messages m
              JOIN personnes p ON m.personne_id = p.id
              WHERE m.communaute_id = ? AND m.est_modere = 0
              ORDER BY m.date_creation DESC
              LIMIT ? OFFSET ?";

    $messages = executeQuery($query, [$community_id, $community_id, $limit, $offset])->fetchAll();

    foreach ($messages as &$message) {
        if (isset($message['date_creation'])) {
            $message['date_creation_formatted'] = formatDate($message['date_creation']);
        }

        $message['role_badge'] = getCommunityRoleBadge($message['role_membre'] ?? 'membre');
    }

    $countQuery = "SELECT COUNT(*) as total FROM communautes_messages 
                  WHERE communaute_id = ? AND est_modere = 0";
    $total = executeQuery($countQuery, [$community_id])->fetch()['total'];

    $totalPages = ceil($total / $limit);

    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

    $urlPattern = "?id=$community_id&messages_page={page}";

    return [
        'messages' => $messages,
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
 * récupère les derniers messages d'une communauté
 * 
 * @param int $community_id identifiant de la communauté
 * @param int $limit nombre de messages à récupérer
 * @return array liste des derniers messages
 */
function getCommunityLastMessages($community_id, $limit = 5)
{
    $community_id = filter_var(sanitizeInput($community_id), FILTER_VALIDATE_INT);
    $limit = (int)sanitizeInput($limit);

    if (!$community_id) {
        return [];
    }

    $query = "SELECT m.id, m.contenu, m.date_creation, m.est_modere, m.raison_moderation,
              p.id as auteur_id, p.nom as auteur_nom, p.prenom as auteur_prenom, p.photo_url
              FROM communautes_messages m
              JOIN personnes p ON m.personne_id = p.id
              WHERE m.communaute_id = ? AND m.est_modere = 0
              ORDER BY m.date_creation DESC
              LIMIT ?";

    $messages = executeQuery($query, [$community_id, $limit])->fetchAll();

    foreach ($messages as &$message) {
        if (isset($message['date_creation'])) {
            $message['date_creation_formatted'] = formatDate($message['date_creation']);
        }
    }

    return $messages;
}

/**
 * récupère les événements d'une communauté
 * 
 * @param int $community_id identifiant de la communauté
 * @param int $limit nombre d'événements à récupérer
 * @return array liste des événements
 */
function getCommunityEvents($community_id, $limit = 5)
{
    $community_id = filter_var(sanitizeInput($community_id), FILTER_VALIDATE_INT);
    $limit = (int)sanitizeInput($limit);

    if (!$community_id) {
        return [];
    }

    $query = "SELECT e.id, e.titre, e.description, e.date_debut, e.date_fin, e.lieu, e.type,
              e.capacite_max, e.niveau_difficulte
              FROM evenements e
              JOIN communautes_evenements ce ON e.id = ce.evenement_id
              WHERE ce.communaute_id = ? AND e.date_debut >= CURDATE()
              ORDER BY e.date_debut ASC
              LIMIT ?";

    $events = executeQuery($query, [$community_id, $limit])->fetchAll();

    foreach ($events as &$event) {
        if (isset($event['date_debut'])) {
            $event['date_debut_formatted'] = formatDate($event['date_debut']);
        }
        if (isset($event['date_fin'])) {
            $event['date_fin_formatted'] = formatDate($event['date_fin']);
        }
    }

    return $events;
}

/**
 * ajoute un salarié à une communauté
 * 
 * @param int $employee_id identifiant du salarié
 * @param int $community_id identifiant de la communauté
 * @return bool résultat de l'ajout
 */
function joinCommunity($employee_id, $community_id)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $community_id = filter_var(sanitizeInput($community_id), FILTER_VALIDATE_INT);

    if (!$employee_id || !$community_id) {
        flashMessage("Paramètres invalides", "danger");
        return false;
    }

    $employee = fetchOne('personnes', "id = $employee_id AND role_id = " . ROLE_SALARIE . " AND statut = 'actif'");
    if (!$employee) {
        flashMessage("Le salarié n'existe pas ou n'est pas actif", "danger");
        return false;
    }

    $community = fetchOne('communautes', "id = $community_id");
    if (!$community) {
        flashMessage("La communauté n'existe pas", "danger");
        return false;
    }

    $query = "SELECT COUNT(*) as count FROM communautes_membres 
             WHERE communaute_id = ? AND personne_id = ?";
    $result = executeQuery($query, [$community_id, $employee_id])->fetch();

    if ((int)$result['count'] > 0) {
        flashMessage("Vous êtes déjà membre de cette communauté", "info");
        return false;
    }

    if (!empty($community['capacite_max'])) {
        $query = "SELECT COUNT(*) as count FROM communautes_membres WHERE communaute_id = ?";
        $result = executeQuery($query, [$community_id])->fetch();

        if ((int)$result['count'] >= $community['capacite_max']) {
            flashMessage("La capacité maximale de cette communauté est atteinte", "warning");
            return false;
        }
    }

    $data = [
        'communaute_id' => $community_id,
        'personne_id' => $employee_id,
        'date_inscription' => date('Y-m-d H:i:s'),
        'role_communaute' => 'membre'
    ];

    $result = insertRow('communautes_membres', $data);

    if ($result) {
        logBusinessOperation($employee_id, 'join_community', "Adhésion à la communauté #{$community_id} - {$community['nom']}");

        $notifData = [
            'personne_id' => $employee_id,
            'titre' => 'Nouvelle communauté',
            'message' => "Vous avez rejoint la communauté '{$community['nom']}'",
            'type' => 'info',
            'lien' => "/salarie/communities.php?id=$community_id"
        ];
        insertRow('notifications', $notifData);

        flashMessage("Vous avez rejoint la communauté avec succès", "success");
        return true;
    }

    flashMessage("Une erreur est survenue lors de l'adhésion à la communauté", "danger");
    return false;
}

/**
 * quitte une communauté
 * 
 * @param int $employee_id identifiant du salarié
 * @param int $community_id identifiant de la communauté
 * @return bool résultat de l'opération
 */
function leaveCommunity($employee_id, $community_id)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $community_id = filter_var(sanitizeInput($community_id), FILTER_VALIDATE_INT);

    if (!$employee_id || !$community_id) {
        flashMessage("Paramètres invalides", "danger");
        return false;
    }

    $query = "SELECT COUNT(*) as count FROM communautes_membres 
             WHERE communaute_id = ? AND personne_id = ?";
    $result = executeQuery($query, [$community_id, $employee_id])->fetch();

    if ((int)$result['count'] == 0) {
        flashMessage("Vous n'êtes pas membre de cette communauté", "info");
        return false;
    }

    $query = "DELETE FROM communautes_membres WHERE communaute_id = ? AND personne_id = ?";
    $result = executeQuery($query, [$community_id, $employee_id])->rowCount() > 0;

    if ($result) {
        $community = fetchOne('communautes', "id = $community_id");
        $communityName = $community ? $community['nom'] : "ID #$community_id";

        logBusinessOperation($employee_id, 'leave_community', "Départ de la communauté #{$community_id} - {$communityName}");

        flashMessage("Vous avez quitté la communauté avec succès", "success");
        return true;
    }

    flashMessage("Une erreur est survenue lors du départ de la communauté", "danger");
    return false;
}

/**
 * ajoute un message dans une communauté avec modération automatique
 * 
 * @param int $employee_id identifiant du salarié
 * @param int $community_id identifiant de la communauté
 * @param string $message contenu du message
 * @return int|false identifiant du message créé ou false en cas d'erreur
 */
function addCommunityMessage($employee_id, $community_id, $message)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $community_id = filter_var(sanitizeInput($community_id), FILTER_VALIDATE_INT);
    $message = sanitizeInput($message);

    if (!$employee_id || !$community_id || empty($message)) {
        flashMessage("Paramètres invalides", "danger");
        return false;
    }

    $query = "SELECT COUNT(*) as count FROM communautes_membres 
             WHERE communaute_id = ? AND personne_id = ?";
    $result = executeQuery($query, [$community_id, $employee_id])->fetch();

    if ((int)$result['count'] == 0) {
        flashMessage("Vous devez être membre de cette communauté pour publier un message", "warning");
        return false;
    }

    $moderationResult = automaticContentModeration($message);
    $est_modere = $moderationResult['moderated'];
    $raison_moderation = $moderationResult['reason'];

    $data = [
        'communaute_id' => $community_id,
        'personne_id' => $employee_id,
        'contenu' => $message,
        'date_creation' => date('Y-m-d H:i:s'),
        'est_modere' => $est_modere ? 1 : 0,
        'raison_moderation' => $raison_moderation
    ];

    $message_id = insertRow('communautes_messages', $data);

    if ($message_id) {
        logBusinessOperation($employee_id, 'add_community_message', "Message ajouté dans la communauté #{$community_id}");

        if ($est_modere) {
            flashMessage("Votre message a été soumis à modération pour la raison suivante : {$raison_moderation}", "warning");
        } else {
            flashMessage("Votre message a été publié avec succès", "success");
        }

        return $message_id;
    }

    flashMessage("Une erreur est survenue lors de la publication du message", "danger");
    return false;
}

/**
 * modère automatiquement un contenu
 * 
 * @param string $content contenu à modérer
 * @return array résultat de la modération [moderated, reason]
 */
function automaticContentModeration($content)
{
    $forbidden_words = [
        'insulte',
        'grossier',
        'raciste',
        'discrimination',
        'haine',
        'pornographie',
        'violence',
        'drogue',
        'illégal',
        'fraude'
    ];

    // mb = multibyte = caractères spéciaux
    $content_lower = mb_strtolower($content);

    foreach ($forbidden_words as $word) {
        if (strpos($content_lower, $word) !== false) {
            return [
                'moderated' => true,
                'reason' => "Contenu contenant des termes inappropriés"
            ];
        }
    }

    if (strlen(trim($content)) < 2) {
        return [
            'moderated' => true,
            'reason' => "Contenu trop court"
        ];
    }

    if (
        strpos($content, 'http://') !== false ||
        strpos($content, 'https://') !== false ||
        strpos($content, 'www.') !== false
    ) {
        $allowed_domains = ['business-care.fr', 'businesscare.fr'];
        $is_allowed = false;

        foreach ($allowed_domains as $domain) {
            if (strpos($content, $domain) !== false) {
                $is_allowed = true;
                break;
            }
        }

        if (!$is_allowed) {
            return [
                'moderated' => true,
                'reason' => "Contenu contenant des liens externes non autorisés"
            ];
        }
    }

    return [
        'moderated' => false,
        'reason' => null
    ];
}

/**
 * retourne la classe CSS pour un type de communauté
 * 
 * @param string $type type de communauté
 * @return string classe CSS
 */
function getCommunityTypeClass($type)
{
    $classMap = [
        'sport' => 'bg-primary',
        'bien_etre' => 'bg-success',
        'sante' => 'bg-info',
        'autre' => 'bg-secondary'
    ];

    return $classMap[$type] ?? 'bg-secondary';
}

/**
 * retourne un badge pour un rôle dans la communauté
 * 
 * @param string $role rôle dans la communauté
 * @return string HTML du badge
 */
function getCommunityRoleBadge($role)
{
    switch ($role) {
        case 'admin':
            return '<span class="badge bg-danger">Administrateur</span>';
        case 'animateur':
            return '<span class="badge bg-warning text-dark">Animateur</span>';
        case 'membre':
        default:
            return '<span class="badge bg-primary">Membre</span>';
    }
}

/**
 * récupère la liste des communautés avec pagination et recherche
 * 
 * @param int $employee_id identifiant du salarié
 * @param int $page numéro de page
 * @param int $limit nombre d'éléments par page
 * @param string $search terme de recherche
 * @param string $type_filter filtre par type de communauté
 * @return array liste des communautés et informations de pagination
 */
function getCommunities($employee_id = null, $page = 1, $limit = 10, $search = '', $type_filter = '')
{
    $basic_communities = getEmployeeCommunities($employee_id);

    $filtered_communities = [];

    $employee_id = sanitizeInput($employee_id);
    if ($employee_id !== null) {
        $employee_id = filter_var($employee_id, FILTER_VALIDATE_INT);
    }
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);
    $search = sanitizeInput($search);
    $type_filter = sanitizeInput($type_filter);

    foreach ($basic_communities as $community) {
        if (!empty($search)) {
            $search_term = strtolower($search);
            $community_name = strtolower($community['nom']);
            $community_desc = strtolower($community['description'] ?? '');

            if (
                strpos($community_name, $search_term) === false &&
                strpos($community_desc, $search_term) === false
            ) {
                continue;
            }
        }

        if (!empty($type_filter) && $community['type'] !== $type_filter) {
            continue;
        }

        $community['type_class'] = getCommunityTypeClass($community['type']);

        if (isset($community['created_at'])) {
            $community['created_at_formatted'] = formatDate($community['created_at']);
        }

        if ($employee_id) {
            $query = "SELECT COUNT(*) as count FROM communautes_membres 
                     WHERE communaute_id = ? AND personne_id = ?";
            $result = executeQuery($query, [$community['id'], $employee_id])->fetch();
            $community['est_membre'] = (int)$result['count'] > 0;
        } else {
            $community['est_membre'] = false;
        }

        $query = "SELECT COUNT(*) as count FROM communautes_membres WHERE communaute_id = ?";
        $result = executeQuery($query, [$community['id']])->fetch();
        $community['nombre_membres'] = (int)$result['count'];

        $filtered_communities[] = $community;
    }

    usort($filtered_communities, function ($a, $b) {
        return strcmp($a['nom'], $b['nom']);
    });

    $total = count($filtered_communities);
    $totalPages = ceil($total / $limit);
    $offset = ($page - 1) * $limit;

    $filtered_communities = array_slice($filtered_communities, $offset, $limit);

    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

    $urlPattern = "?page={page}";
    if (!empty($search)) {
        $urlPattern .= "&search=" . urlencode($search);
    }
    if (!empty($type_filter)) {
        $urlPattern .= "&type=" . urlencode($type_filter);
    }

    return [
        'communities' => $filtered_communities,
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
 * récupère l'historique des dons d'un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @return array liste des dons
 */
function getDonationHistory($employee_id)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);

    if (!$employee_id) {
        return [];
    }

    $query = "SELECT d.* 
              FROM dons d
              WHERE d.personne_id = ?
              ORDER BY d.date_don DESC";

    $dons = executeQuery($query, [$employee_id])->fetchAll();

    $associations = getAssociations();
    $associations_by_id = [];

    foreach ($associations as $association) {
        $associations_by_id[$association['id']] = $association['nom'];
    }

    foreach ($dons as &$don) {
        if (!empty($don['association_id']) && isset($associations_by_id[$don['association_id']])) {
            $don['association_nom'] = $associations_by_id[$don['association_id']];
        } else {
            $don['association_nom'] = 'Non spécifiée';
        }
    }

    return $dons;
}

/**
 * compte le nombre de dons d'un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @return int nombre de dons
 */
function getEmployeeDonationsCount($employee_id)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);

    if (!$employee_id) {
        return 0;
    }

    $query = "SELECT COUNT(*) as total FROM dons WHERE personne_id = ?";
    $result = executeQuery($query, [$employee_id])->fetch();

    return $result['total'] ?? 0;
}

function getAssociations()
{
    try {
        $query = "SELECT id, nom, domaine, description, logo_url, site_web 
                 FROM associations 
                 WHERE actif = 1 
                 ORDER BY nom ASC";

        $stmt = executeQuery($query);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération associations: " . $e->getMessage());
        return [];
    }
}
