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

function getEmployeeAvailableServices($employee_id)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        return [];
    }

    $employee = fetchOne('personnes', "id = $employee_id AND role_id = " . ROLE_SALARIE);

    if (!$employee || empty($employee['entreprise_id'])) {
        return [];
    }

    $company_id = $employee['entreprise_id'];

    $contractsCount = executeQuery(
        "SELECT COUNT(*) as count FROM contrats 
         WHERE entreprise_id = ? AND statut = 'actif'
         AND (date_fin IS NULL OR date_fin >= CURDATE())",
        [$company_id]
    )->fetch()['count'];

    if ($contractsCount == 0) {
        return [];
    }

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

    foreach ($services as &$service) {
        if (isset($service['prix'])) {
            $service['prix_formate'] = formatMoney($service['prix']);
        }
    }

    return $services;
}

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

    if ($status !== 'all') {
        $query .= " AND r.statut = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY r.date_rdv DESC";

    $reservations = executeQuery($query, $params)->fetchAll();

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
 * @internal Construit les parties dynamiques des requêtes SQL pour getEmployeeAppointments.
 *
 * @param string $status Le statut des rendez-vous ('upcoming', 'past', 'canceled').
 * @param array &$params Tableau de paramètres pour la requête préparée (modifié par référence).
 * @param string $now Timestamp actuel formaté.
 * @return array Tableau contenant 'whereClause', 'countWhereClause', 'orderByClause'.
 */
function _buildEmployeeAppointmentsQueryParts(string $status, array &$params, string $now): array
{
    $whereClause = '';
    $countWhereClause = '';
    $orderByClause = '';

    switch ($status) {
        case 'upcoming':
            $whereClause = " AND rv.date_rdv > :now AND rv.statut NOT IN ('annule', 'manque')";
            $countWhereClause = " AND rv.date_rdv > :now AND rv.statut NOT IN ('annule', 'manque')";
            $params[':now'] = $now;
            $orderByClause = " ORDER BY rv.date_rdv ASC";
            break;
        case 'past':
            $whereClause = " AND (rv.date_rdv < :now AND rv.statut NOT IN ('annule', 'manque'))";
            $countWhereClause = " AND (rv.date_rdv < :now AND rv.statut NOT IN ('annule', 'manque'))";
            $params[':now'] = $now;
            $orderByClause = " ORDER BY rv.date_rdv DESC";
            break;
        case 'canceled':
            $whereClause = " AND rv.statut IN ('annule', 'manque')";
            $countWhereClause = " AND rv.statut IN ('annule', 'manque')";
            $orderByClause = " ORDER BY rv.date_rdv DESC";
            break;
        default:
            // Pas de condition de statut spécifique, tri par défaut
            $orderByClause = " ORDER BY rv.date_rdv DESC";
            break;
    }

    return [
        'whereClause' => $whereClause,
        'countWhereClause' => $countWhereClause,
        'orderByClause' => $orderByClause
    ];
}

/**
 * @internal Formate un tableau de rendez-vous pour l'affichage.
 *
 * @param array $appointments Tableau de rendez-vous bruts de la BDD.
 * @return array Tableau de rendez-vous formatés.
 */
function _formatEmployeeAppointments(array $appointments): array
{
    foreach ($appointments as &$appointment) { // Utilisation de &$ pour modifier directement
        if (isset($appointment['date_rdv'])) {
            try {
                $date = new DateTime($appointment['date_rdv']);
                $appointment['date_rdv_formatee'] = $date->format('d/m/Y H:i');
            } catch (Exception $e) {
                $appointment['date_rdv_formatee'] = 'Date invalide';
                // Log l'erreur si nécessaire
                // error_log("Erreur formatage date RDV #{$appointment['rdv_id']}: " . $e->getMessage());
            }
        }

        if (isset($appointment['statut'])) {
            $appointment['statut_badge'] = getStatusBadge($appointment['statut']);
        }
    }
    return $appointments;
}

/**
 * @internal Construit le modèle d'URL pour la pagination des rendez-vous.
 *
 * @param string $status Le statut actuel des rendez-vous.
 * @return string Le modèle d'URL pour renderPagination.
 */
function _buildAppointmentsPaginationUrlPattern(string $status): string
{
    $pageParamsMap = [
        'upcoming' => 'upcoming_page',
        'past'     => 'past_page',
        'canceled' => 'canceled_page',
    ];

    // Récupérer les valeurs actuelles des paramètres de page (ou 1 par défaut)
    $currentPages = [];
    foreach ($pageParamsMap as $s => $paramName) {
        $currentPages[$paramName] = sanitizeInput($_GET[$paramName] ?? 1);
    }

    // Déterminer le nom du paramètre pour la page {page}
    $currentPageParam = $pageParamsMap[$status] ?? 'page'; // 'page' si statut inconnu

    // Commencer l'URL avec le paramètre de la page actuelle
    $urlPattern = "?{$currentPageParam}={page}";

    // Ajouter les paramètres des autres statuts
    foreach ($pageParamsMap as $s => $paramName) {
        // N'ajouter que si ce n'est PAS le statut actuel ET si la valeur n'est pas 1 (pour alléger l'URL)
        if ($paramName !== $currentPageParam && $currentPages[$paramName] != 1) {
            $urlPattern .= "&{$paramName}=" . $currentPages[$paramName];
        }
    }

    // Gérer le cas d'un statut non reconnu (ajouter le paramètre 'status')
    if (!isset($pageParamsMap[$status])) {
        $urlPattern .= "&status=" . urlencode($status);
        // Si statut inconnu, on ajoute explicitement les autres pages si elles ne sont pas à 1
        // (redondant avec la boucle précédente mais clarifie l'intention pour le cas défaut)
        if ($currentPages['upcoming_page'] != 1) $urlPattern .= "&upcoming_page=" . $currentPages['upcoming_page'];
        if ($currentPages['past_page'] != 1) $urlPattern .= "&past_page=" . $currentPages['past_page'];
        if ($currentPages['canceled_page'] != 1) $urlPattern .= "&canceled_page=" . $currentPages['canceled_page'];
    }

    return $urlPattern;
}

/**
 * Récupère les rendez-vous d'un employé avec pagination.
 *
 * @param int $employee_id ID de l'employé.
 * @param string $status Statut des rendez-vous ('upcoming', 'past', 'canceled').
 * @param int $page Page actuelle.
 * @param int $limit Nombre d'éléments par page.
 * @return array Tableau contenant les rendez-vous et les informations de pagination.
 */
function getEmployeeAppointments($employee_id, $status = 'upcoming', $page = 1, $limit = 5)
{
    // 1. Validation et sanitization
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $status = sanitizeInput($status);
    $page = max(1, (int)sanitizeInput($page)); // Assurer page >= 1
    $limit = max(1, (int)sanitizeInput($limit)); // Assurer limit >= 1

    if (!$employee_id) {
        error_log("ID d'employé invalide dans getEmployeeAppointments");
        return [
            'appointments' => [],
            'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
            'pagination_html' => ''
        ];
    }

    // 2. Initialisation
    $offset = ($page - 1) * $limit;
    $now = date('Y-m-d H:i:s');
    $baseQuery = "SELECT rv.id as rdv_id, rv.date_rdv, rv.duree, rv.type_rdv, rv.statut, rv.lieu, rv.notes,
                  p.id as prestation_id, p.nom as prestation_nom, p.description as prestation_description,
                  pp.id as praticien_id, CONCAT(pp.prenom, ' ', pp.nom) as praticien_nom,
                  e.id as evenement_id, e.titre as evenement_titre, e.lieu as evenement_lieu
                  FROM rendez_vous rv
                  LEFT JOIN prestations p ON rv.prestation_id = p.id
                  LEFT JOIN personnes pp ON rv.praticien_id = pp.id
                  LEFT JOIN evenements e ON rv.evenement_id = e.id
                  WHERE rv.personne_id = :employee_id";
    $baseCountQuery = "SELECT COUNT(*) as total FROM rendez_vous rv WHERE rv.personne_id = :employee_id";
    $params = [':employee_id' => $employee_id];
    $countParams = [':employee_id' => $employee_id]; // Copie pour la requête de comptage

    // 3. Construire les parties dynamiques des requêtes
    $queryParts = _buildEmployeeAppointmentsQueryParts($status, $params, $now);
    $countQueryParts = _buildEmployeeAppointmentsQueryParts($status, $countParams, $now); // Appeler aussi pour countParams

    // 4. Finaliser les requêtes
    $query = $baseQuery . $queryParts['whereClause'] . $queryParts['orderByClause']
        . " LIMIT " . $limit . " OFFSET " . $offset;
    $countQuery = $baseCountQuery . $countQueryParts['countWhereClause']; // Utiliser countWhereClause ici

    // 5. Exécuter les requêtes
    try {
        $stmt = executeQuery($query, $params);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = executeQuery($countQuery, $countParams);
        $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        $total = $countResult['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Erreur DB dans getEmployeeAppointments: " . $e->getMessage());
        return [ // Retourner une structure vide en cas d'erreur DB
            'appointments' => [],
            'pagination' => ['current' => $page, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
            'pagination_html' => '<div class="alert alert-danger">Erreur lors de la récupération des rendez-vous.</div>'
        ];
    }

    // 6. Formater les résultats
    $formattedAppointments = _formatEmployeeAppointments($appointments);

    // 7. Calculer la pagination
    $totalPages = $limit > 0 ? ceil($total / $limit) : 1;
    $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1)); // Réajuster $page si nécessaire après comptage

    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

    // 8. Construire l'URL de pagination
    $urlPattern = _buildAppointmentsPaginationUrlPattern($status);

    // 9. Retourner les données
    return [
        'appointments' => $formattedAppointments,
        'pagination' => [
            'current' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages
        ],
        'pagination_html' => renderPagination($paginationData, $urlPattern)
    ];
}


function generatePaginationHtml($paginationData, $urlPattern, $ariaLabel = 'Pagination')
{
    if ($paginationData['totalPages'] <= 1) {
        return '';
    }

    $html = '<nav aria-label="' . htmlspecialchars($ariaLabel) . '">';
    $html .= '<ul class="pagination pagination-sm">'; // Added pagination-sm

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

    for ($i = max(1, $paginationData['currentPage'] - 2); $i <= min($paginationData['totalPages'], $paginationData['currentPage'] + 2); $i++) {
        $pageUrl = str_replace('{page}', $i, $urlPattern);
        $activeClass = ($i == $paginationData['currentPage']) ? ' active' : '';

        $html .= '<li class="page-item' . $activeClass . '">';
        $html .= '<a class="page-link" href="' . $pageUrl . '">' . $i . '</a>';
        $html .= '</li>';
    }

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


function getEmployeeActivityHistory($employee_id, $page = 1, $limit = 5)
{
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
        $where = "personne_id = " . $employee_id;
        $orderBy = "created_at DESC";

        $pagination = paginateResults('logs', $page, $limit, $where, $orderBy);

        foreach ($pagination['items'] as &$activity) {
            $activity['created_at_formatted'] = formatDate($activity['created_at']);
            $activity['icon'] = getActivityIcon($activity['action']);
        }

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

function getEmployeeCommunities($employee_id)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        return [];
    }

    $communities = fetchAll('communautes', '1=1', 'type, nom');

    return $communities;
}

function manageEmployeeDonations($employee_id, $donation_data)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $donation_data = sanitizeInput($donation_data);

    if (!$employee_id || empty($donation_data)) {
        flashMessage("Paramètres invalides pour le don", "danger");
        return false;
    }

    if (
        empty($donation_data['type']) ||
        ($donation_data['type'] == 'financier' && empty($donation_data['montant'])) ||
        ($donation_data['type'] == 'materiel' && empty($donation_data['description']))
    ) {
        flashMessage("Veuillez remplir tous les champs obligatoires", "warning");
        return false;
    }

    if (!in_array($donation_data['type'], ['financier', 'materiel'])) {
        flashMessage("Type de don invalide", "danger");
        return false;
    }

    if ($donation_data['type'] == 'financier') {
        $montant = filter_var($donation_data['montant'], FILTER_VALIDATE_FLOAT);
        if ($montant === false || $montant <= 0) {
            flashMessage("Le montant du don doit être un nombre positif", "warning");
            return false;
        }
        $donation_data['montant'] = $montant;
    }

    try {
        $employee = fetchOne('personnes', "id = $employee_id AND role_id = " . ROLE_SALARIE . " AND statut = 'actif'");
        if (!$employee) {
            flashMessage("Le salarié n'existe pas ou n'est pas actif", "danger");
            return false;
        }

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

        if (!empty($donation_data['association_id'])) {
            $association_id = filter_var($donation_data['association_id'], FILTER_VALIDATE_INT);
            if ($association_id) {
                $association = fetchOne('associations', "id = $association_id AND actif = 1");
                if ($association) {
                    $donData['association_id'] = $association_id;
                }
            }
        }

        $donationId = insertRow('dons', $donData);

        if (!$donationId) {
            rollbackTransaction();
            flashMessage("Impossible d'enregistrer votre don", "danger");
            return false;
        }

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

        commitTransaction();

        logBusinessOperation($employee_id, 'don_creation', "Don #{$donationId} créé, type: {$donation_data['type']}");

        if ($donation_data['type'] == 'financier') {
            $montantFormatted = formatMoney($donation_data['montant']);
            flashMessage("Votre don financier de {$montantFormatted} a été enregistré et est en attente de traitement", "success");
        } else {
            flashMessage("Votre don matériel a été enregistré. Nous vous contacterons pour organiser la collecte", "success");
        }

        return $donationId;
    } catch (Exception $e) {
        if (isset($transactionStarted) && $transactionStarted) {
            rollbackTransaction();
        }
        logSystemActivity('error', "Erreur création don: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de l'enregistrement de votre don", "danger");
        return false;
    }
}

function getEmployeeEvents($employee_id, $event_type = 'all')
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        return [];
    }

    // Récupérer les colonnes existantes dans la table simplifiée
    $query = "SELECT id, titre, description, date_debut, date_fin, lieu, type, 
              capacite_max, niveau_difficulte 
              FROM evenements 
              WHERE date_debut >= CURDATE()";
    $params = [];

    if ($event_type !== 'all') {
        $query .= " AND type = :event_type"; // Marqueur nommé
        $params[':event_type'] = $event_type; // Utiliser le marqueur nommé
    }

    $query .= " ORDER BY date_debut, titre";

    $events = executeQuery($query, $params)->fetchAll();

    foreach ($events as &$event) {
        // Formatage Date Début (avec heure)
        if (isset($event['date_debut'])) {
            $event['date_debut_formatted'] = formatDate($event['date_debut'], 'd/m/Y H:i');
        }
        // Formatage Date Fin (avec heure)
        if (isset($event['date_fin'])) {
            $event['date_fin_formatted'] = formatDate($event['date_fin'], 'd/m/Y H:i');
        }
        // Formatage Niveau
        if (!empty($event['niveau_difficulte'])) {
            $event['niveau_formatted'] = ucfirst(sanitizeInput($event['niveau_difficulte']));
        } else {
            $event['niveau_formatted'] = 'N/A';
        }
        // Formatage Capacité
        if (!empty($event['capacite_max'])) {
            $event['capacite_formatted'] = sanitizeInput($event['capacite_max']) . ' pers.';
        } else {
            $event['capacite_formatted'] = 'N/A';
        }
        // PAS DE FORMATAGE DE COÛT ICI
    }

    return $events;
}

function updateEmployeeSettings($employee_id, $settings)
{
    // Validation de l'ID
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $settings = sanitizeInput($settings);

    if (!$employee_id || empty($settings)) {
        flashMessage("ID de salarié invalide ou paramètres manquants", "danger");
        return false;
    }

    $allowedFields = ['langue', 'notif_email', 'theme'];

    $filteredSettings = array_intersect_key($settings, array_flip($allowedFields));

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

function isPrestationAvailable($prestation_id, $date_rdv)
{
    // On retourne toujours true pour permettre les inscriptions multiples
    return true;
}


function getAvailablePrestationsForEmployee($employee_id, $page = 1, $limit = 20)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $page = max(1, (int)sanitizeInput($page));
    $limit = max(1, (int)sanitizeInput($limit));

    if (!$employee_id) {
        error_log("Tentative d'accès aux prestations avec ID employé invalide: $employee_id");

        return [
            'prestations' => [],
            'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
            'pagination_html' => ''
        ];
    }

    // Utilise la fonction paginateResults pour récupérer et paginer les résultats.
    // Définit les champs à sélectionner.
    $selectFields = "p.id, p.nom, p.description, p.prix, p.duree, p.type, p.date_heure_disponible, 
                     p.capacite_max, p.niveau_difficulte, p.lieu, p.est_disponible, p.categorie, 
                     pp.id as praticien_id, CONCAT(pp.prenom, ' ', pp.nom) as praticien_nom";
    // Définit la clause FROM avec une jointure pour obtenir le nom du praticien.
    $fromClause = "prestations p LEFT JOIN personnes pp ON p.praticien_id = pp.id";
    // Définit la clause WHERE pour ne prendre que les prestations disponibles.
    $whereClause = "p.est_disponible = TRUE";
    // Définit la clause ORDER BY pour trier par nom.
    $orderByClause = "p.nom ASC";

    // Appelle paginateResults pour exécuter la requête et récupérer les données paginées.
    // Note : Le dernier argument spécifie les champs SELECT (redondant avec $selectFields mais nécessaire pour la compatibilité de paginateResults telle qu'elle est utilisée ici)
    // Le 6ème argument spécifie les jointures.
    $paginationResult = paginateResults(
        'prestations p', // Table principale (avec alias)
        $page,            // Page actuelle
        $limit,           // Limite par page
        $whereClause,     // Clause WHERE
        $orderByClause,   // Clause ORDER BY
        "p.id, p.nom, p.description, p.prix, p.duree, p.type, p.date_heure_disponible, 
                                     p.capacite_max, p.niveau_difficulte, p.lieu, p.est_disponible, p.categorie, 
                                     pp.id as praticien_id, CONCAT(pp.prenom, ' ', pp.nom) as praticien_nom", // Champs à sélectionner
        "LEFT JOIN personnes pp ON p.praticien_id = pp.id" // Jointures
    );

    /*
     * Voilà un chargement de prestations disponible : plus fourni qu'un train de ravitaillement !
     * Mais surveille-moi ce tomorrow qui surgit soudainement, caporal.
     * T'es sûr de vouloir imposer 9:00 à tout le monde ?
     * (Commentaire original de Coderabbitai - Note: l'assignation 9:00 a été retirée)
     */
    // Boucle sur les prestations récupérées pour les formater.
    foreach ($paginationResult['items'] as &$prestation) {
        // Formatage du prix : affiche "Gratuit" si 0 ou non défini, sinon formate le nombre.
        if (isset($prestation['prix'])) {
            $prix = floatval($prestation['prix']);
            $prestation['prix_formate'] = ($prix > 0) ? number_format($prix, 2, ',', ' ') . ' €' : 'Gratuit';
        } else {
            // Si le prix est NULL ou non défini dans le SELECT
            $prestation['prix_formate'] = 'Prix non spécifié'; // Ou 'Gratuit' selon la règle métier
        }

        // Formatage de la date de disponibilité : tente de la parser et la formater.
        if (!empty($prestation['date_heure_disponible'])) {
            try {
                $date = new DateTime($prestation['date_heure_disponible']);
                $prestation['date_disponible_formatee'] = $date->format('d/m/Y à H:i');
            } catch (Exception $e) {
                // En cas d'erreur de formatage, affiche un message et log l'erreur.
                $prestation['date_disponible_formatee'] = 'Date invalide';
                error_log("Erreur formatage date_heure_disponible pour prestation #{$prestation['id']}: " . $e->getMessage());
            }
        } else {
            // Si aucune date n'est spécifiée.
            $prestation['date_disponible_formatee'] = 'Non précisée';
        }
        // La colonne est_disponible n'a plus besoin d'être vérifiée/modifiée ici car filtrée par la requête SQL.
    }

    // Construit le modèle d'URL pour les liens de pagination.
    $urlPattern = "?prestation_page={page}";
    // Ajouter d'autres paramètres si nécessaire (ex: filtres)

    // Retourne la structure de données finale avec les prestations formatées et les informations de pagination.
    return [
        'prestations' => $paginationResult['items'], // Les prestations formatées
        'pagination' => [                            // Les détails de la pagination
            'current' => $paginationResult['currentPage'],
            'limit' => $paginationResult['perPage'],
            'total' => $paginationResult['totalItems'],
            'totalPages' => $paginationResult['totalPages']
        ],
        'pagination_html' => renderPagination($paginationResult, $urlPattern) // Le HTML de la pagination
    ];
}

function cancelEmployeeAppointment($employee_id, $rdv_id)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $rdv_id = filter_var(sanitizeInput($rdv_id), FILTER_VALIDATE_INT);

    // Vérification initiale des IDs
    if (!$employee_id || !$rdv_id) {
        flashMessage("Données d'annulation invalides.", "danger");
        return false;
    }

    try {
        // Récupérer le RDV et vérifier qu'il appartient bien à l'employé et n'est pas déjà annulé
        $query = "SELECT id, prestation_id, statut FROM rendez_vous WHERE id = :rdv_id AND personne_id = :employee_id";
        $rdv = executeQuery($query, [':rdv_id' => $rdv_id, ':employee_id' => $employee_id])->fetch();

        if (!$rdv) {
            flashMessage("Rendez-vous non trouvé ou ne vous appartient pas.", "warning");
            return false;
        }

        if ($rdv['statut'] === 'annule') {
            flashMessage("Ce rendez-vous est déjà annulé.", "info");
            return false; // Ou true si on considère que l'état désiré est atteint
        }

        // Mettre à jour le statut du RDV
        $updateQuery = "UPDATE rendez_vous SET statut = 'annule' WHERE id = :rdv_id";
        $affectedRows = executeQuery($updateQuery, [':rdv_id' => $rdv_id])->rowCount();

        if ($affectedRows > 0) {
            // Si c'était une prestation individuelle, la rendre à nouveau disponible
            // (Vérifier si la prestation existe et a une capacité <= 1)
            if (!empty($rdv['prestation_id'])) {
                $prestationQuery = "SELECT id, capacite_max FROM prestations WHERE id = :presta_id";
                $prestation = executeQuery($prestationQuery, [':presta_id' => $rdv['prestation_id']])->fetch();

                if ($prestation && (empty($prestation['capacite_max']) || $prestation['capacite_max'] <= 1)) {
                    $updatePrestationQuery = "UPDATE prestations SET est_disponible = TRUE WHERE id = :presta_id";
                    executeQuery($updatePrestationQuery, [':presta_id' => $rdv['prestation_id']]);
                    // Log ou notification optionnelle sur la remise en disponibilité
                }
            }
            // Log l'opération métier
            logBusinessOperation($employee_id, 'cancel_appointment', "Annulation du RDV #$rdv_id");
            // Le message de succès sera géré par le script appelant
            return true;
        } else {
            // Échec de la mise à jour (devrait être rare si le RDV existe et n'est pas déjà annulé)
            logSystemActivity('warning', "cancelEmployeeAppointment: échec de la mise à jour du statut pour RDV #$rdv_id.");
            flashMessage("La mise à jour du statut du rendez-vous a échoué.", "danger");
            return false;
        }
    } catch (PDOException $e) {
        logSystemActivity('error', "Erreur BDD dans cancelEmployeeAppointment pour RDV #$rdv_id: " . $e->getMessage());
        flashMessage("Une erreur de base de données est survenue lors de l'annulation.", "danger");
        return false;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur inattendue dans cancelEmployeeAppointment pour RDV #$rdv_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de l'annulation.", "danger");
        return false;
    }
}


function bookEmployeeAppointment($employee_id, $appointment_data)
{
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $appointment_data = sanitizeInput($appointment_data); // Sanitize the whole array first

    if (!$employee_id || empty($appointment_data)) {
        flashMessage("Données de réservation invalides.", "danger");
        return false;
    }

    $prestation_id = filter_var($appointment_data['prestation_id'] ?? 0, FILTER_VALIDATE_INT);
    $date_rdv = $appointment_data['date_rdv'] ?? '';
    $duree = filter_var($appointment_data['duree'] ?? 0, FILTER_VALIDATE_INT);

    if (!$prestation_id || empty($date_rdv) || $duree <= 0) {
        flashMessage("Informations de réservation incomplètes (prestation, date ou durée manquante).", "danger");
        return false;
    }

    $praticien_id = filter_var($appointment_data['praticien_id'] ?? null, FILTER_VALIDATE_INT);
    $type_rdv = $appointment_data['type_rdv'] ?? 'presentiel'; // Default value
    $lieu = $appointment_data['lieu'] ?? '';
    $notes = $appointment_data['notes'] ?? '';
    $evenement_id = filter_var($appointment_data['evenement_id'] ?? null, FILTER_VALIDATE_INT); // Récupérer depuis les données si fourni


    try {
        beginTransaction();

        if ($praticien_id) {
            $checkPraticien = "SELECT id FROM personnes WHERE id = :id AND role_id = :role_id";
            $praticienExists = executeQuery($checkPraticien, [':id' => $praticien_id, ':role_id' => ROLE_PRESTATAIRE])->fetch();
            if (!$praticienExists) {
                // Comportement actuel : ignorer l'ID invalide. Alternative : rejeter.
                flashMessage("Le praticien spécifié est invalide.", "warning");
                $praticien_id = null; // Ou rollbackTransaction(); return false;
            }
        }

        $checkPrestation = "SELECT id, nom, est_disponible, capacite_max FROM prestations WHERE id = :id";
        $prestation = executeQuery($checkPrestation, [':id' => $prestation_id])->fetch();

        if (!$prestation) {
            rollbackTransaction();
            flashMessage("La prestation demandée n'existe pas.", "danger");
            return false;
        }

        if (!$prestation['est_disponible']) {

            if (empty($prestation['capacite_max']) || $prestation['capacite_max'] <= 1) {
                rollbackTransaction();
                flashMessage("Cette prestation n'est plus disponible.", "warning");
                return false;
            }
        }

        if (!isTimeSlotAvailable($date_rdv, $duree, $prestation_id)) {
            rollbackTransaction();
            flashMessage("Le créneau horaire demandé n'est pas disponible.", "warning");
            return false;
        }

        $insertQuery = "INSERT INTO rendez_vous (personne_id, prestation_id, praticien_id, date_rdv, duree, 
                        type_rdv, lieu, notes, statut, created_at, evenement_id) 
                        VALUES (:pid, :prestaid, :pracid, :daterdv, :duree, :typerdv, :lieu, :notes, 'confirme', NOW(), :eventid)";

        $insertParams = [
            ':pid' => $employee_id,
            ':prestaid' => $prestation_id,
            ':pracid' => $praticien_id, // Peut être NULL
            ':daterdv' => $date_rdv,
            ':duree' => $duree,
            ':typerdv' => $type_rdv,
            ':lieu' => $lieu,
            ':notes' => $notes,
            ':eventid' => $evenement_id // Peut être NULL
        ];

        $stmt = executeQuery($insertQuery, $insertParams);
        $rowCount = $stmt->rowCount();
        $rdvId = getDbConnection()->lastInsertId();

        if ($rowCount > 0 && $rdvId) {
            if (empty($prestation['capacite_max']) || $prestation['capacite_max'] <= 1) {
                $updatePrestation = "UPDATE prestations SET est_disponible = FALSE WHERE id = :id";
                executeQuery($updatePrestation, [':id' => $prestation_id]);
            }

            commitTransaction();
            flashMessage("Votre rendez-vous pour '{$prestation['nom']}' a été réservé avec succès.", "success");
            // TODO: Ajouter notification utilisateur ?
            // TODO: Ajouter log business operation ?
            return $rdvId;
        } else {
            // L'insertion a échoué
            rollbackTransaction();
            logSystemActivity('error', "Échec insertion RDV pour user #$employee_id, presta #$prestation_id. rowCount=$rowCount");
            flashMessage("Une erreur est survenue lors de l'enregistrement du rendez-vous.", "danger");
            return false;
        }
    } catch (PDOException $e) {
        rollbackTransaction();
        logSystemActivity('error', "Erreur BDD bookEmployeeAppointment: " . $e->getMessage());
        flashMessage("Une erreur de base de données est survenue lors de la réservation.", "danger");
        return false;
    } catch (Exception $e) {
        rollbackTransaction();
        logSystemActivity('error', "Erreur inattendue bookEmployeeAppointment: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de la réservation.", "danger");
        return false;
    }
}


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
        error_log("Erreur lors de la conversion de date pour prestation #{$prestation_id}: " . $e->getMessage());
    }

    return $schedules;
}

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
        if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $content_lower)) {
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

function handlePostActions($employee_id)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (isset($_POST['join_community']) && !empty($_POST['community_id'])) {
        $join_community_id = filter_input(INPUT_POST, 'community_id', FILTER_VALIDATE_INT);
        if (joinCommunity($employee_id, $join_community_id)) {
            // Redirection pour éviter le problème de re-soumission du formulaire
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    if (isset($_POST['leave_community']) && !empty($_POST['community_id'])) {
        $leave_community_id = filter_input(INPUT_POST, 'community_id', FILTER_VALIDATE_INT);
        if (leaveCommunity($employee_id, $leave_community_id)) {
            // Redirection pour éviter le problème de re-soumission du formulaire
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    if (isset($_POST['add_message']) && !empty($_POST['message']) && !empty($_POST['community_id'])) {
        $msg_community_id = filter_input(INPUT_POST, 'community_id', FILTER_VALIDATE_INT);
        $message = htmlspecialchars(filter_input(INPUT_POST, 'message', FILTER_UNSAFE_RAW));
        if (addCommunityMessage($employee_id, $msg_community_id, $message)) {
            // Redirection pour éviter le problème de re-soumission du formulaire
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

function getConseils(int $page = 1, int $limit = 10, ?string $category = null, ?string $searchTerm = null): array
{
    $params = [];
    $whereClauses = ['c.est_publie = TRUE'];

    if ($category !== null && $category !== '') {
        $whereClauses[] = 'c.categorie = :category'; // Use named placeholders for clarity with paginateResults
        $params['category'] = $category;
    }

    if ($searchTerm !== null && $searchTerm !== '') {
        $whereClauses[] = '(c.titre LIKE :searchTerm OR c.contenu LIKE :searchTerm OR c.mots_cles LIKE :searchTerm)';
        $params['searchTerm'] = '%' . $searchTerm . '%';
    }

    $whereSql = !empty($whereClauses) ? implode(' AND ', $whereClauses) : '1=1'; // Use 1=1 if no clauses
    $orderBySql = 'c.date_publication DESC, c.ordre_affichage ASC, c.id DESC';

    $countQuery = "SELECT COUNT(c.id) as total 
                   FROM conseils c 
                   LEFT JOIN personnes p ON c.auteur_personne_id = p.id 
                   WHERE {$whereSql}";
    $totalResult = executeQuery($countQuery, $params)->fetch(PDO::FETCH_ASSOC);
    $totalConseils = $totalResult['total'] ?? 0;

    $totalPages = $limit > 0 ? ceil($totalConseils / $limit) : 1;
    $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
    $offset = ($page - 1) * $limit;

    $query = "SELECT c.*,
                     DATE_FORMAT(c.date_publication, '%d/%m/%Y') as date_publication_formatee,
                     p.nom as auteur_nom_personne, p.prenom as auteur_prenom_personne
              FROM conseils c
              LEFT JOIN personnes p ON c.auteur_personne_id = p.id
              WHERE {$whereSql}
              ORDER BY {$orderBySql}
              LIMIT {$limit} OFFSET {$offset}";

    $conseils = executeQuery($query, $params)->fetchAll(PDO::FETCH_ASSOC);

    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalConseils,
        'perPage' => $limit
    ];

    $urlParams = [];
    if ($category !== null && $category !== '') $urlParams['category'] = $category;
    if ($searchTerm !== null && $searchTerm !== '') $urlParams['search'] = $searchTerm;
    $urlPattern = $_SERVER['PHP_SELF'] . "?page={page}";
    if (!empty($urlParams)) {
        $urlPattern .= "&" . http_build_query($urlParams);
    }

    $paginationHtml = renderPagination($paginationData, $urlPattern);

    return [
        'conseils' => $conseils,
        'pagination' => $paginationData, // Renvoyer les données calculées
        'pagination_html' => $paginationHtml
    ];
}


function getConseilCategories(): array
{
    $query = "SELECT DISTINCT categorie FROM conseils WHERE est_publie = TRUE AND categorie IS NOT NULL AND categorie != '' ORDER BY categorie ASC";
    return executeQuery($query)->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Récupère les détails complets d'un conseil spécifique.
 *
 * @param int $conseil_id L'ID du conseil à récupérer.
 * @return array|false Les détails du conseil ou false si non trouvé/publié.
 */
function getConseilDetails(int $conseil_id): array|false
{
    if ($conseil_id <= 0) {
        return false;
    }

    $query = "SELECT c.*,
                     DATE_FORMAT(c.date_publication, '%d/%m/%Y') as date_publication_formatee,
                     p.nom as auteur_nom_personne, p.prenom as auteur_prenom_personne
              FROM conseils c
              LEFT JOIN personnes p ON c.auteur_personne_id = p.id
              WHERE c.id = :conseil_id AND c.est_publie = TRUE";

    $params = [':conseil_id' => $conseil_id];

    $conseil = executeQuery($query, $params)->fetch(PDO::FETCH_ASSOC);

    // Si l'auteur n'est pas une personne (table `personnes`), utiliser le champ `auteur_nom` de la table `conseils`
    if ($conseil && empty($conseil['auteur_nom_personne']) && !empty($conseil['auteur_nom'])) {
        $conseil['auteur_prenom_personne'] = null; // Pas de prénom si c'est juste un nom d'auteur
        $conseil['auteur_nom_personne'] = $conseil['auteur_nom']; // Utiliser auteur_nom comme nom complet
    }

    return $conseil; // Retourne le tableau du conseil ou false si fetch() n'a rien trouvé
}


function getPrestationDetails(int $prestation_id): array|false
{
    if ($prestation_id <= 0) {
        return false;
    }

    $query = "SELECT p.*, 
                     pp.id as praticien_id, CONCAT(pp.prenom, ' ', pp.nom) as praticien_nom
              FROM prestations p
              LEFT JOIN personnes pp ON p.praticien_id = pp.id
              WHERE p.id = :prestation_id";
    // Peut-être ajouter une condition WHERE p.est_disponible = TRUE ?
    // Dépend si on veut voir les détails d'une presta non active.

    $params = [':prestation_id' => $prestation_id];

    $prestation = executeQuery($query, $params)->fetch(PDO::FETCH_ASSOC);

    if ($prestation) {
        /*
         * Détails de prestation, soldat ? Garde un œil sur la variable prix_formate,
         * qu'elle n'envoie pas du flan au client !
         * (Commentaire original de Coderabbitai)
         */
        // Formater le prix
        if (isset($prestation['prix'])) {
            $prix = $prestation['prix']; // Garder la valeur originale pour vérifier NULL
            if ($prix === null) {
                $prestation['prix_formate'] = 'Prix non disponible';
            } else {
                $prixFloat = floatval($prix);
                if ($prixFloat > 0) {
                    $prestation['prix_formate'] = number_format($prixFloat, 2, ',', ' ') . ' €';
                } else {
                    // Gère 0, négatif, ou conversion de non-numérique en 0
                    $prestation['prix_formate'] = 'Gratuit';
                }
            }
        } else {
            // Fallback si la colonne prix n'existe pas dans le résultat
            $prestation['prix_formate'] = 'Prix non disponible';
        }

        // Formater la date de disponibilité si elle existe
        if (!empty($prestation['date_heure_disponible'])) {
            try {
                $date = new DateTime($prestation['date_heure_disponible']);
                $prestation['date_disponible_formatee'] = $date->format('d/m/Y à H:i');
            } catch (Exception $e) {
                $prestation['date_disponible_formatee'] = 'Date invalide';
            }
        } else {
            $prestation['date_disponible_formatee'] = null; // Pas de date spécifique définie
        }
    }

    return $prestation; // Retourne le tableau de la prestation ou false
}

/**
 * Inscrit un salarié à un événement.
 *
 * @param int $employee_id ID du salarié.
 * @param int $event_id ID de l'événement.
 * @return bool True si l'inscription réussit, False sinon.
 */
function registerEmployeeToEvent($employee_id, $event_id)
{
    $employee_id = filter_var($employee_id, FILTER_VALIDATE_INT);
    $event_id = filter_var($event_id, FILTER_VALIDATE_INT);

    if (!$employee_id || !$event_id) {
        flashMessage("Données d'inscription invalides.", "danger");
        return false;
    }

    try {
        // Vérifier si l'événement existe et récupérer sa capacité
        $event = fetchOne('evenements', 'id = :id', [':id' => $event_id]);
        if (!$event) {
            flashMessage("L'événement demandé n'existe pas.", "warning");
            return false;
        }

        // Vérifier si l'employé est déjà inscrit
        $existingRegistration = fetchOne('evenements_participants', 'evenement_id = :event_id AND personne_id = :employee_id', [
            ':event_id' => $event_id,
            ':employee_id' => $employee_id
        ]);

        if ($existingRegistration) {
            flashMessage("Vous êtes déjà inscrit à cet événement.", "info");
            return false; // Déjà inscrit, pas une erreur mais on ne réinscrit pas
        }

        // Vérifier la capacité maximale si elle est définie
        if (!empty($event['capacite_max'])) {
            $currentParticipants = countTableRows('evenements_participants', 'evenement_id = :event_id', [':event_id' => $event_id]);
            if ($currentParticipants >= $event['capacite_max']) {
                flashMessage("La capacité maximale pour cet événement est atteinte.", "warning");
                return false;
            }
        }

        // Inscrire l'employé
        $data = [
            'evenement_id' => $event_id,
            'personne_id' => $employee_id,
            'date_inscription' => date('Y-m-d H:i:s'),
            'statut_inscription' => 'inscrit' // Statut par défaut
        ];

        $inserted = insertRow('evenements_participants', $data);

        if ($inserted) {
            // Log l'activité (si la fonction existe)
            if (function_exists('logBusinessOperation')) {
                logBusinessOperation($employee_id, 'event_registration', "Inscription à l'événement #{$event_id} - {$event['titre']}");
            }
            // Créer une notification (si la table et la fonction existent)
            $notifData = [
                'personne_id' => $employee_id,
                'titre' => 'Inscription confirmée',
                'message' => "Vous êtes bien inscrit à l'événement : {$event['titre']}",
                'type' => 'success',
                'lien' => WEBCLIENT_URL . '/modules/employees/events.php' // Ou lien vers l'événement spécifique si page détail existe
            ];
            insertRow('notifications', $notifData);

            flashMessage("Inscription à l'événement '{$event['titre']}' réussie !", "success");
            return true;
        } else {
            flashMessage("Une erreur est survenue lors de l'inscription.", "danger");
            return false;
        }
    } catch (Exception $e) {
        // Log l'erreur système
        if (function_exists('logSystemActivity')) {
            logSystemActivity('error', "Erreur inscription événement #{$event_id} pour user #{$employee_id}: " . $e->getMessage());
        }
        flashMessage("Une erreur technique est survenue.", "danger");
        return false;
    }
}

/**
 * Désinscrit un salarié d'un événement.
 *
 * @param int $employee_id ID du salarié.
 * @param int $event_id ID de l'événement.
 * @return bool True si la désinscription réussit ou si l'utilisateur n'était pas inscrit, False en cas d'erreur.
 */
function unregisterEmployeeFromEvent($employee_id, $event_id)
{
    $employee_id = filter_var($employee_id, FILTER_VALIDATE_INT);
    $event_id = filter_var($event_id, FILTER_VALIDATE_INT);

    if (!$employee_id || !$event_id) {
        flashMessage("Données de désinscription invalides.", "danger");
        return false;
    }

    try {
        // Vérifier si l'événement existe (optionnel mais bonne pratique)
        $event = fetchOne('evenements', 'id = :id', [':id' => $event_id]);
        if (!$event) {
            flashMessage("L'événement spécifié n'existe pas.", "warning");
            return false;
        }

        // Vérifier si l'employé est réellement inscrit
        $whereClause = 'evenement_id = :event_id AND personne_id = :employee_id';
        $params = [
            ':event_id' => $event_id,
            ':employee_id' => $employee_id
        ];
        $existingRegistration = fetchOne('evenements_participants', $whereClause, $params);

        if (!$existingRegistration) {
            flashMessage("Vous n'êtes pas inscrit à cet événement.", "info");
            return true; // Pas une erreur, l'état désiré (non inscrit) est atteint
        }

        // Supprimer l'inscription
        $deleted = deleteRow('evenements_participants', $whereClause, $params);

        if ($deleted > 0) {
            // Log l'activité
            if (function_exists('logBusinessOperation')) {
                logBusinessOperation($employee_id, 'event_unregistration', "Désinscription de l'événement #{$event_id} - {$event['titre']}");
            }
            // Créer une notification (optionnel)
            $notifData = [
                'personne_id' => $employee_id,
                'titre' => 'Désinscription confirmée',
                'message' => "Vous avez été désinscrit de l'événement : {$event['titre']}",
                'type' => 'info',
                'lien' => WEBCLIENT_URL . '/modules/employees/events.php'
            ];
            insertRow('notifications', $notifData);

            flashMessage("Vous avez été désinscrit de l'événement '{$event['titre']}'.", "success");
            return true;
        } else {
            flashMessage("Une erreur est survenue lors de la désinscription.", "danger");
            return false;
        }
    } catch (Exception $e) {
        if (function_exists('logSystemActivity')) {
            logSystemActivity('error', "Erreur désinscription événement #{$event_id} pour user #{$employee_id}: " . $e->getMessage());
        }
        flashMessage("Une erreur technique est survenue lors de la désinscription.", "danger");
        return false;
    }
}


function getRegisteredEventIds($employee_id)
{
    $sql = "SELECT evenement_id 
            FROM evenements_participants 
            WHERE personne_id = :employee_id 
              AND statut_inscription = 'inscrit'";

    $stmt = executeQuery($sql, [':employee_id' => $employee_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
