<?php


require_once __DIR__ . '/../../../includes/init.php';


function getProvidersList($category = null, $page = 1, $limit = 20, $search = '')
{
    // Sanitize input parameters - utiliser sanitizeInput déjà disponible
    $category = sanitizeInput($category);
    $page = (int)$page;
    $limit = (int)$limit;
    $search = sanitizeInput($search);

    // Calcul de l'offset pour la pagination
    $offset = ($page - 1) * $limit;

    $query = "SELECT DISTINCT p.id, p.nom, p.prenom, p.email, p.photo_url, p.statut
              FROM personnes p
              LEFT JOIN rendez_vous r ON p.id = r.personne_id
              LEFT JOIN prestations pr ON r.prestation_id = pr.id
              WHERE p.role_id = ?";

    $countQuery = "SELECT COUNT(DISTINCT p.id) as total 
                  FROM personnes p
                  LEFT JOIN rendez_vous r ON p.id = r.personne_id
                  LEFT JOIN prestations pr ON r.prestation_id = pr.id
                  WHERE p.role_id = ?";

    $params = [ROLE_PRESTATAIRE];

    if (!empty($category)) {
        $query .= " AND pr.categorie = ?";
        $countQuery .= " AND pr.categorie = ?";
        $params[] = $category;
    }

    if (!empty($search)) {
        $query .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR pr.nom LIKE ?)";
        $countQuery .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR pr.nom LIKE ?)";
        $searchTerm = "%" . $search . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $query .= " ORDER BY p.nom, p.prenom LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = executeQuery($query, $params);
    $providers = $stmt->fetchAll();

    foreach ($providers as &$provider) {
        $provider['statut_badge'] = getStatusBadge($provider['statut']);

        $servicesQuery = "SELECT DISTINCT pr.id, pr.nom, pr.type, pr.categorie
                         FROM prestations pr
                         JOIN rendez_vous r ON pr.id = r.prestation_id
                         WHERE r.personne_id = ?
                         LIMIT 5";
        $servicesStmt = executeQuery($servicesQuery, [$provider['id']]);
        $provider['services'] = $servicesStmt->fetchAll();

        // Récupération de la note moyenne
        $ratingQuery = "SELECT AVG(e.note) as note_moyenne, COUNT(e.id) as nombre_avis
                       FROM evaluations e
                       JOIN rendez_vous r ON e.prestation_id = r.prestation_id
                       WHERE r.personne_id = ?";
        $ratingStmt = executeQuery($ratingQuery, [$provider['id']]);
        $rating = $ratingStmt->fetch();

        $provider['note_moyenne'] = $rating['note_moyenne'] ? round($rating['note_moyenne'], 1) : 0;
        $provider['nombre_avis'] = $rating['nombre_avis'] ?? 0;
    }

    // Exécution de la requête de comptage
    $countStmt = executeQuery($countQuery, array_slice($params, 0, -2));
    $countResult = $countStmt->fetch();
    $total = $countResult['total'];

    // Calcul des informations de pagination
    $totalPages = ceil($total / $limit);

    // Préparer le résultat avec le HTML de pagination
    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

    // Construction de l'URL pour la pagination (considérer les filtres existants)
    $urlPattern = "?page={page}";
    if (!empty($category)) {
        $urlPattern .= "&category=" . urlencode($category);
    }
    if (!empty($search)) {
        $urlPattern .= "&search=" . urlencode($search);
    }

    return [
        'providers' => $providers,
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
 * récupère les détails d'un prestataire
 * 
 * @param int $provider_id identifiant du prestataire
 * @return array|false détails du prestataire ou false si non trouvé
 */
function getProviderDetails($provider_id)
{
    // Validation de l'ID
    $provider_id = (int)sanitizeInput($provider_id);
    if (!$provider_id) {
        flashMessage("ID de prestataire invalide", "danger");
        return false;
    }

    // Récupération des informations du prestataire (déjà utilise fetchOne, c'est bien)
    $provider = fetchOne('personnes', "id = $provider_id AND role_id = " . ROLE_PRESTATAIRE);

    if (!$provider) {
        flashMessage("Prestataire non trouvé", "warning");
        return false;
    }

    // Ajout du titre de page
    $provider['page_title'] = generatePageTitle("Prestataire: {$provider['prenom']} {$provider['nom']}");

    // Ajouter le badge de statut
    $provider['statut_badge'] = getStatusBadge($provider['statut']);

    // Récupération des services proposés
    $servicesQuery = "SELECT DISTINCT pr.id, pr.nom, pr.description, pr.type, 
                    pr.categorie, pr.prix, pr.duree
                    FROM prestations pr
                    JOIN rendez_vous r ON pr.id = r.prestation_id
                    WHERE r.personne_id = ?";
    $servicesStmt = executeQuery($servicesQuery, [$provider_id]);
    $provider['services'] = $servicesStmt->fetchAll();

    // Formater les prix des services
    foreach ($provider['services'] as &$service) {
        if (isset($service['prix'])) {
            $service['prix_formate'] = formatMoney($service['prix']);
        }
    }

    // Récupération des évaluations
    $ratingsQuery = "SELECT e.*, CONCAT(p.prenom, ' ', p.nom) as client_nom,
                   CONCAT(pr.type, ' - ', pr.nom) as prestation_nom
                   FROM evaluations e
                   JOIN rendez_vous r ON e.prestation_id = r.prestation_id
                   JOIN personnes p ON e.personne_id = p.id
                   JOIN prestations pr ON e.prestation_id = pr.id
                   WHERE r.personne_id = ?
                   ORDER BY e.date_evaluation DESC
                   LIMIT 5";
    $ratingsStmt = executeQuery($ratingsQuery, [$provider_id]);
    $provider['evaluations'] = $ratingsStmt->fetchAll();

    // Formater les dates des évaluations
    foreach ($provider['evaluations'] as &$evaluation) {
        if (isset($evaluation['date_evaluation'])) {
            $evaluation['date_evaluation_formatee'] = formatDate($evaluation['date_evaluation'], 'd/m/Y');
        }
    }

    // Calcul de la note moyenne
    $avgRatingQuery = "SELECT AVG(e.note) as note_moyenne, COUNT(e.id) as nombre_avis
                     FROM evaluations e
                     JOIN rendez_vous r ON e.prestation_id = r.prestation_id
                     WHERE r.personne_id = ?";
    $avgRatingStmt = executeQuery($avgRatingQuery, [$provider_id]);
    $avgRating = $avgRatingStmt->fetch();

    $provider['note_moyenne'] = $avgRating['note_moyenne'] ? round($avgRating['note_moyenne'], 1) : 0;
    $provider['nombre_avis'] = $avgRating['nombre_avis'] ?? 0;

    return $provider;
}

/**
 * recherche de prestataires selon critères avancés
 * 
 * @param array $criteria critères de recherche (categorie, type, disponibilite, note_min)
 * @return array liste des prestataires correspondant aux critères
 */
function searchProviders($criteria)
{
    // Sanitize input criteria - utiliser sanitizeInput uniquement
    $criteria = sanitizeInput($criteria);

    try {
        // Construction de la requête de base
        $query = "SELECT DISTINCT p.id, p.nom, p.prenom, p.email, p.photo_url, p.statut
                FROM personnes p
                LEFT JOIN rendez_vous r ON p.id = r.personne_id
                LEFT JOIN prestations pr ON r.prestation_id = pr.id
                WHERE p.role_id = ? AND p.statut = 'actif'";

        $params = [ROLE_PRESTATAIRE];

        // Ajout des critères s'ils sont spécifiés
        if (!empty($criteria['categorie'])) {
            $query .= " AND pr.categorie = ?";
            $params[] = $criteria['categorie'];
        }

        if (!empty($criteria['type'])) {
            $query .= " AND pr.type = ?";
            $params[] = $criteria['type'];
        }

        // Si critère de note minimum spécifié
        if (!empty($criteria['note_min'])) {
            $query .= " AND p.id IN (
                        SELECT r.personne_id
                        FROM rendez_vous r
                        JOIN evaluations e ON r.prestation_id = e.prestation_id
                        GROUP BY r.personne_id
                        HAVING AVG(e.note) >= ?
                        )";
            $params[] = $criteria['note_min'];
        }

        // Si critère de disponibilité spécifié
        if (!empty($criteria['disponibilite']) && !empty($criteria['date'])) {
            // Cette requête suppose qu'une absence de rendez-vous à la date spécifiée indique une disponibilité
            $query .= " AND p.id NOT IN (
                        SELECT DISTINCT r.personne_id
                        FROM rendez_vous r
                        WHERE DATE(r.date_rdv) = ? AND r.statut IN ('planifie', 'confirme')
                        )";
            $params[] = $criteria['date'];
        }

        $query .= " ORDER BY p.nom, p.prenom";

        $stmt = executeQuery($query, $params);
        $providers = $stmt->fetchAll();

        // Pour chaque prestataire, récupérer quelques informations complémentaires
        foreach ($providers as &$provider) {
            // Ajouter le badge de statut
            $provider['statut_badge'] = getStatusBadge($provider['statut']);

            // Récupération des services
            $servicesQuery = "SELECT DISTINCT pr.id, pr.nom, pr.type, pr.categorie
                           FROM prestations pr
                           JOIN rendez_vous r ON pr.id = r.prestation_id
                           WHERE r.personne_id = ?
                           LIMIT 3";
            $servicesStmt = executeQuery($servicesQuery, [$provider['id']]);
            $provider['services'] = $servicesStmt->fetchAll();

            // Récupération de la note moyenne
            $ratingQuery = "SELECT AVG(e.note) as note_moyenne, COUNT(e.id) as nombre_avis
                         FROM evaluations e
                         JOIN rendez_vous r ON e.prestation_id = r.prestation_id
                         WHERE r.personne_id = ?";
            $ratingStmt = executeQuery($ratingQuery, [$provider['id']]);
            $rating = $ratingStmt->fetch();

            $provider['note_moyenne'] = $rating['note_moyenne'] ? round($rating['note_moyenne'], 1) : 0;
            $provider['nombre_avis'] = $rating['nombre_avis'] ?? 0;
        }

        return $providers;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur recherche prestataires: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la recherche de prestataires", "danger");
        return [];
    }
}

/**
 * récupère le calendrier de disponibilité d'un prestataire
 * 
 * @param int $provider_id identifiant du prestataire
 * @param string $start_date date de début (format Y-m-d)
 * @param string $end_date date de fin (format Y-m-d)
 * @return array calendrier de disponibilité
 */
function getProviderCalendar($provider_id, $start_date, $end_date)
{
    // Validation de l'ID et des dates
    $provider_id = (int)sanitizeInput($provider_id);
    $start_date = sanitizeInput($start_date);
    $end_date = sanitizeInput($end_date);

    if (!$provider_id || empty($start_date) || empty($end_date)) {
        return [];
    }

    // Récupération des rendez-vous dans la période donnée
    $query = "SELECT r.id, r.date_rdv, r.duree, r.lieu, r.type_rdv, r.statut,
              pr.nom as prestation_nom, pr.type as prestation_type
              FROM rendez_vous r
              JOIN prestations pr ON r.prestation_id = pr.id
              WHERE r.personne_id = ? 
              AND DATE(r.date_rdv) BETWEEN ? AND ?
              AND r.statut IN ('planifie', 'confirme')
              ORDER BY r.date_rdv";

    $stmt = executeQuery($query, [$provider_id, $start_date, $end_date]);
    $appointments = $stmt->fetchAll();

    // Formater les dates et ajouter les badges de statut
    foreach ($appointments as &$appointment) {
        $appointment['date_rdv_formatee'] = formatDate($appointment['date_rdv'], 'd/m/Y H:i');
        $appointment['statut_badge'] = getStatusBadge($appointment['statut']);
    }

    // Création d'un tableau de jours entre start_date et end_date
    $period = new DatePeriod(
        new DateTime($start_date),
        new DateInterval('P1D'),
        (new DateTime($end_date))->modify('+1 day')
    );

    $calendar = [];

    foreach ($period as $date) {
        $dateStr = $date->format('Y-m-d');
        $dayAppointments = array_filter($appointments, function ($app) use ($dateStr) {
            return substr($app['date_rdv'], 0, 10) === $dateStr;
        });

        $calendar[$dateStr] = [
            'date' => $dateStr,
            'date_formatee' => formatDate($dateStr, 'd/m/Y'),
            'day_name' => $date->format('l'),
            'appointments' => array_values($dayAppointments),
            'is_available' => count($dayAppointments) < 8, // hypothèse : max 8 RDV par jour
            'slots_taken' => count($dayAppointments),
            'slots_available' => 8 - count($dayAppointments)
        ];
    }

    return $calendar;
}

/**
 * récupère les évaluations d'un prestataire
 * 
 * @param int $provider_id identifiant du prestataire
 * @param int $page numéro de page
 * @param int $limit nombre d'éléments par page
 * @return array évaluations et infos de pagination
 */
function getProviderRatings($provider_id, $page = 1, $limit = 10)
{
    // Validation de l'ID
    $provider_id = (int)sanitizeInput($provider_id);
    if (!$provider_id) {
        return [
            'ratings' => [],
            'pagination' => [
                'current' => $page,
                'limit' => $limit,
                'total' => 0,
                'totalPages' => 0
            ]
        ];
    }

    // Calcul de l'offset pour la pagination
    $offset = ($page - 1) * $limit;

    // Récupération des évaluations
    $query = "SELECT e.*, CONCAT(p.prenom, ' ', p.nom) as client_nom,
              pr.nom as prestation_nom, pr.type as prestation_type
              FROM evaluations e
              JOIN rendez_vous r ON e.prestation_id = r.prestation_id
              JOIN personnes p ON e.personne_id = p.id
              JOIN prestations pr ON e.prestation_id = pr.id
              WHERE r.personne_id = ?
              ORDER BY e.date_evaluation DESC
              LIMIT ? OFFSET ?";

    $stmt = executeQuery($query, [$provider_id, $limit, $offset]);
    $ratings = $stmt->fetchAll();

    // Formater les dates des évaluations
    foreach ($ratings as &$rating) {
        if (isset($rating['date_evaluation'])) {
            $rating['date_evaluation_formatee'] = formatDate($rating['date_evaluation'], 'd/m/Y');
        }
    }

    // Calcul du nombre total pour la pagination
    $countQuery = "SELECT COUNT(*) as total
                  FROM evaluations e
                  JOIN rendez_vous r ON e.prestation_id = r.prestation_id
                  WHERE r.personne_id = ?";

    $countStmt = executeQuery($countQuery, [$provider_id]);
    $countResult = $countStmt->fetch();
    $total = $countResult['total'];

    // Calcul des informations de pagination
    $totalPages = ceil($total / $limit);

    // Préparer les données pour renderPagination
    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

    // Construire l'URL pattern pour la pagination
    $urlPattern = "?provider_id=$provider_id&page={page}";

    return [
        'ratings' => $ratings,
        'summary' => [
            'average' => getProviderAverageRating($provider_id),
            'count' => $total
        ],
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
 * récupère la note moyenne d'un prestataire
 * 
 * @param int $provider_id identifiant du prestataire
 * @return float note moyenne
 */
function getProviderAverageRating($provider_id)
{
    $provider_id = (int)sanitizeInput($provider_id);

    $query = "SELECT AVG(e.note) as moyenne
              FROM evaluations e
              JOIN rendez_vous r ON e.prestation_id = r.prestation_id
              WHERE r.personne_id = ?";

    $stmt = executeQuery($query, [$provider_id]);
    $result = $stmt->fetch();

    return $result['moyenne'] ? round($result['moyenne'], 1) : 0;
}

/**
 * récupère les catégories de prestataires
 * 
 * @return array liste des catégories
 */
function getProviderCategories()
{
    try {
        // Cette requête est trop spécifique pour utiliser fetchAll directement
        // car nous avons besoin d'une agrégation (COUNT) et d'un ORDER BY sur cette agrégation
        $query = "SELECT DISTINCT categorie, COUNT(DISTINCT id) as count
                FROM prestations
                WHERE categorie IS NOT NULL AND categorie != ''
                GROUP BY categorie
                ORDER BY count DESC";

        return executeQuery($query)->fetchAll();
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération catégories prestataires: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération des catégories", "danger");
        return [];
    }
}

/**
 * récupère les contrats d'un prestataire
 * 
 * @param int $provider_id identifiant du prestataire
 * @param string $status filtre par statut (active, expired, all)
 * @return array liste des contrats
 */
function getProviderContracts($provider_id, $status = 'active')
{
    // Validation de l'ID
    $provider_id = (int)sanitizeInput($provider_id);
    $status = sanitizeInput($status);

    if (!$provider_id) {
        return [];
    }

    // Cette requête est une approximation car il n'y a pas de table explicite de contrats prestataires
    // On considère ici qu'un prestataire a un contrat implicite avec chaque entreprise pour laquelle
    // il a effectué des prestations

    $query = "SELECT DISTINCT e.id as entreprise_id, e.nom as entreprise_nom,
              MIN(r.date_rdv) as date_debut, MAX(r.date_rdv) as date_derniere,
              COUNT(r.id) as nombre_prestations
              FROM rendez_vous r
              JOIN personnes s ON r.personne_id = s.id
              JOIN entreprises e ON s.entreprise_id = e.id
              WHERE r.praticien_id = ?
              AND s.entreprise_id IS NOT NULL";

    $params = [$provider_id];

    if ($status === 'active') {
        $query .= " GROUP BY e.id, e.nom
                    HAVING MAX(r.date_rdv) >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    ORDER BY date_derniere DESC";
    } else if ($status === 'expired') {
        $query .= " GROUP BY e.id, e.nom
                    HAVING MAX(r.date_rdv) < DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    ORDER BY date_derniere DESC";
    } else {
        $query .= " GROUP BY e.id, e.nom
                    ORDER BY date_derniere DESC";
    }

    $stmt = executeQuery($query, $params);
    $contracts = $stmt->fetchAll();

    // Formater les dates
    foreach ($contracts as &$contract) {
        if (isset($contract['date_debut'])) {
            $contract['date_debut_formatee'] = formatDate($contract['date_debut'], 'd/m/Y');
        }
        if (isset($contract['date_derniere'])) {
            $contract['date_derniere_formatee'] = formatDate($contract['date_derniere'], 'd/m/Y');
        }

        // Ajouter un badge de statut basé sur l'état des dates
        $contract['statut'] = ($status === 'active') ? 'actif' : 'expire';
        $contract['statut_badge'] = getStatusBadge($contract['statut']);
    }

    return $contracts;
}

/**
 * récupère les services proposés par un prestataire
 * 
 * @param int $provider_id identifiant du prestataire
 * @return array liste des services
 */
function getProviderServices($provider_id)
{
    // Validation de l'ID
    $provider_id = (int)sanitizeInput($provider_id);
    if (!$provider_id) {
        return [];
    }

    // Récupération des services
    $query = "SELECT DISTINCT pr.id, pr.nom, pr.description, pr.type, pr.categorie,
              pr.prix, pr.duree, pr.niveau_difficulte, COUNT(r.id) as nombre_prestations
              FROM prestations pr
              JOIN rendez_vous r ON pr.id = r.prestation_id
              WHERE r.praticien_id = ?
              GROUP BY pr.id, pr.nom, pr.description, pr.type, pr.categorie,
              pr.prix, pr.duree, pr.niveau_difficulte
              ORDER BY nombre_prestations DESC";

    $stmt = executeQuery($query, [$provider_id]);
    $services = $stmt->fetchAll();

    // Formater les prix
    foreach ($services as &$service) {
        if (isset($service['prix'])) {
            $service['prix_formate'] = formatMoney($service['prix']);
        }
    }

    return $services;
}

/**
 * récupère les factures d'un prestataire
 * 
 * @param int $provider_id identifiant du prestataire
 * @param string|null $start_date date de début (format Y-m-d)
 * @param string|null $end_date date de fin (format Y-m-d)
 * @return array liste des factures
 */
function getProviderInvoices($provider_id, $start_date = null, $end_date = null)
{
    // Validation de l'ID
    $provider_id = (int)sanitizeInput($provider_id);
    $start_date = sanitizeInput($start_date);
    $end_date = sanitizeInput($end_date);

    if (!$provider_id) {
        return [];
    }

    // Cette fonction est une approximation car il n'y a pas de table explicite de factures prestataires
    // On regroupe les prestations par mois pour simuler les factures mensuelles

    $query = "SELECT CONCAT(YEAR(r.date_rdv), '-', MONTH(r.date_rdv)) as facture_id,
              DATE_FORMAT(r.date_rdv, '%Y-%m') as periode,
              COUNT(r.id) as nombre_prestations,
              SUM(pr.prix) as montant_total,
              MIN(r.date_rdv) as premiere_prestation,
              MAX(r.date_rdv) as derniere_prestation,
              'payee' as statut
              FROM rendez_vous r
              JOIN prestations pr ON r.prestation_id = pr.id
              WHERE r.personne_id = ? AND r.statut = 'termine'";

    $params = [$provider_id];

    if ($start_date) {
        $query .= " AND r.date_rdv >= ?";
        $params[] = $start_date;
    }

    if ($end_date) {
        $query .= " AND r.date_rdv <= ?";
        $params[] = $end_date;
    }

    $query .= " GROUP BY facture_id, periode, statut
                ORDER BY periode DESC";

    $stmt = executeQuery($query, $params);
    $invoices = $stmt->fetchAll();

    // Formater les dates et montants
    foreach ($invoices as &$invoice) {
        if (isset($invoice['premiere_prestation'])) {
            $invoice['premiere_prestation_formatee'] = formatDate($invoice['premiere_prestation'], 'd/m/Y');
        }
        if (isset($invoice['derniere_prestation'])) {
            $invoice['derniere_prestation_formatee'] = formatDate($invoice['derniere_prestation'], 'd/m/Y');
        }
        if (isset($invoice['montant_total'])) {
            $invoice['montant_total_formate'] = formatMoney($invoice['montant_total']);
        }

        // Ajouter le badge de statut
        $invoice['statut_badge'] = getStatusBadge($invoice['statut']);
    }

    return $invoices;
}

/**
 * met à jour les paramètres d'un prestataire
 * 
 * @param int $provider_id identifiant du prestataire
 * @param array $settings paramètres à mettre à jour
 * @return bool résultat de la mise à jour
 */
function updateProviderSettings($provider_id, $settings)
{
    // Validation de l'ID
    $provider_id = (int)sanitizeInput($provider_id);
    $settings = sanitizeInput($settings);

    if (!$provider_id || empty($settings)) {
        flashMessage("ID de prestataire invalide ou paramètres manquants", "danger");
        return false;
    }

    // Liste des champs autorisés
    $allowedFields = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'photo_url'
    ];

    // Filtrage des paramètres
    $filteredSettings = array_intersect_key($settings, array_flip($allowedFields));

    if (empty($filteredSettings)) {
        flashMessage("Aucun paramètre valide à mettre à jour", "warning");
        return false;
    }

    try {
        // Utilisation de updateRow pour mettre à jour les paramètres
        $result = updateRow(
            'personnes',
            $filteredSettings,
            "id = $provider_id AND role_id = " . ROLE_PRESTATAIRE
        );

        if ($result) {
            logBusinessOperation($_SESSION['user_id'] ?? null, 'update_provider', "Mise à jour prestataire #$provider_id");
            flashMessage("Les paramètres du prestataire ont été mis à jour avec succès", "success");
            return true;
        }

        return false;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur mise à jour prestataire: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la mise à jour des paramètres", "danger");
        return false;
    }
}

/**
 * ajoute une évaluation pour un prestataire
 * 
 * @param int $user_id identifiant de l'utilisateur qui évalue
 * @param int $prestation_id identifiant de la prestation
 * @param int $note note (1-5)
 * @param string $commentaire commentaire
 * @return int|false ID de l'évaluation créée ou false en cas d'erreur
 */
function addProviderRating($user_id, $prestation_id, $note, $commentaire = '')
{
    // Validation des paramètres
    $user_id = (int)sanitizeInput($user_id);
    $prestation_id = (int)sanitizeInput($prestation_id);
    $note = (int)sanitizeInput($note);
    $commentaire = sanitizeInput($commentaire);

    if (!$user_id || !$prestation_id || !$note || $note < 1 || $note > 5) {
        flashMessage("Paramètres invalides pour l'évaluation", "danger");
        return false;
    }

    try {
        // Création des données pour insertion
        $ratingData = [
            'personne_id' => $user_id,
            'prestation_id' => $prestation_id,
            'note' => $note,
            'commentaire' => $commentaire,
            'date_evaluation' => date('Y-m-d')
        ];

        // Utilisation de insertRow pour créer l'évaluation
        $ratingId = insertRow('evaluations', $ratingData);

        if ($ratingId) {
            logBusinessOperation($user_id, 'evaluation', "Évaluation #$ratingId ajoutée pour prestation #$prestation_id");
            flashMessage("Merci pour votre évaluation !", "success");
            return $ratingId;
        }

        return false;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur ajout évaluation: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de l'ajout de l'évaluation", "danger");
        return false;
    }
}
