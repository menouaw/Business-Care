<?php

require_once __DIR__ . '/../../../includes/init.php';

function getProvidersList($category = null, $page = 1, $limit = 20, $search = '')
{
    $category = sanitizeInput($category);
    $page = (int)$page;
    $limit = (int)$limit;
    $search = sanitizeInput($search);

    $offset = ($page - 1) * $limit;

    $query = "SELECT DISTINCT p.id, p.nom, p.prenom, p.email, p.photo_url, p.statut
              FROM " . TABLE_USERS . " p
              LEFT JOIN " . TABLE_APPOINTMENTS . " r ON p.id = r.praticien_id
              LEFT JOIN " . TABLE_PRESTATIONS . " pr ON r.prestation_id = pr.id
              WHERE p.role_id = ?";

    $countQuery = "SELECT COUNT(DISTINCT p.id) as total 
                  FROM " . TABLE_USERS . " p
                  LEFT JOIN " . TABLE_APPOINTMENTS . " r ON p.id = r.praticien_id
                  LEFT JOIN " . TABLE_PRESTATIONS . " pr ON r.prestation_id = pr.id
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
                         FROM " . TABLE_PRESTATIONS . " pr
                         JOIN " . TABLE_APPOINTMENTS . " r ON pr.id = r.prestation_id
                         WHERE r.praticien_id = ?
                         LIMIT 5";
        $servicesStmt = executeQuery($servicesQuery, [$provider['id']]);
        $provider['services'] = $servicesStmt->fetchAll();

        $ratingQuery = "SELECT AVG(e.note) as note_moyenne, COUNT(e.id) as nombre_avis
                       FROM " . TABLE_EVALUATIONS . " e
                       JOIN " . TABLE_APPOINTMENTS . " r ON e.prestation_id = r.prestation_id AND e.personne_id = r.personne_id
                       WHERE r.praticien_id = ?";
        $ratingStmt = executeQuery($ratingQuery, [$provider['id']]);
        $rating = $ratingStmt->fetch();

        $provider['note_moyenne'] = $rating['note_moyenne'] ? round($rating['note_moyenne'], 1) : 0;
        $provider['nombre_avis'] = $rating['nombre_avis'] ?? 0;
    }

    $countStmt = executeQuery($countQuery, array_slice($params, 0, -2));
    $countResult = $countStmt->fetch();
    $total = $countResult['total'];

    $totalPages = ceil($total / $limit);

    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

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

function getProviderDetails($provider_id)
{
    $provider_id = (int)sanitizeInput($provider_id);
    if (!$provider_id) {
        flashMessage("ID de prestataire invalide", "danger");
        return false;
    }

    $provider = fetchOne(TABLE_USERS, "id = :id AND role_id = :role", [':id' => $provider_id, ':role' => ROLE_PRESTATAIRE]);

    if (!$provider) {
        flashMessage("Prestataire non trouvé", "warning");
        return false;
    }

    $provider['page_title'] = generatePageTitle("Prestataire: {$provider['prenom']} {$provider['nom']}");
    $provider['statut_badge'] = getStatusBadge($provider['statut']);

    $servicesQuery = "SELECT DISTINCT pr.id, pr.nom, pr.description, pr.type, 
                    pr.categorie, pr.prix, pr.duree
                    FROM " . TABLE_PRESTATIONS . " pr
                    JOIN " . TABLE_APPOINTMENTS . " r ON pr.id = r.prestation_id
                    WHERE r.praticien_id = ?";
    $servicesStmt = executeQuery($servicesQuery, [$provider_id]);
    $provider['services'] = $servicesStmt->fetchAll();

    foreach ($provider['services'] as &$service) {
        if (isset($service['prix'])) {
            $service['prix_formate'] = formatMoney($service['prix']);
        }
    }

    $ratingsQuery = "SELECT e.*, CONCAT(p.prenom, ' ', p.nom) as client_nom,
                   CONCAT(pr.type, ' - ', pr.nom) as prestation_nom
                   FROM " . TABLE_EVALUATIONS . " e
                   JOIN " . TABLE_APPOINTMENTS . " r ON e.prestation_id = r.prestation_id AND e.personne_id = r.personne_id
                   JOIN " . TABLE_USERS . " p ON e.personne_id = p.id
                   JOIN " . TABLE_PRESTATIONS . " pr ON e.prestation_id = pr.id
                   WHERE r.praticien_id = ?
                   ORDER BY e.date_evaluation DESC
                   LIMIT 5";
    $ratingsStmt = executeQuery($ratingsQuery, [$provider_id]);
    $provider['evaluations'] = $ratingsStmt->fetchAll();

    foreach ($provider['evaluations'] as &$evaluation) {
        if (isset($evaluation['date_evaluation'])) {
            $evaluation['date_evaluation_formatee'] = formatDate($evaluation['date_evaluation'], 'd/m/Y');
        }
    }

    $avgRatingQuery = "SELECT AVG(e.note) as note_moyenne, COUNT(e.id) as nombre_avis
                     FROM " . TABLE_EVALUATIONS . " e
                     JOIN " . TABLE_APPOINTMENTS . " r ON e.prestation_id = r.prestation_id AND e.personne_id = r.personne_id
                     WHERE r.praticien_id = ?";
    $avgRatingStmt = executeQuery($avgRatingQuery, [$provider_id]);
    $avgRating = $avgRatingStmt->fetch();

    $provider['note_moyenne'] = $avgRating['note_moyenne'] ? round($avgRating['note_moyenne'], 1) : 0;
    $provider['nombre_avis'] = $avgRating['nombre_avis'] ?? 0;

    return $provider;
}

function searchProviders($criteria)
{
    $criteria = sanitizeInput($criteria);

    try {
        $query = "SELECT DISTINCT p.id, p.nom, p.prenom, p.email, p.photo_url, p.statut
                FROM " . TABLE_USERS . " p
                LEFT JOIN " . TABLE_APPOINTMENTS . " r ON p.id = r.praticien_id
                LEFT JOIN " . TABLE_PRESTATIONS . " pr ON r.prestation_id = pr.id
                WHERE p.role_id = ? AND p.statut = '" . STATUS_ACTIVE . "'";

        $params = [ROLE_PRESTATAIRE];

        if (!empty($criteria['categorie'])) {
            $query .= " AND pr.categorie = ?";
            $params[] = $criteria['categorie'];
        }

        if (!empty($criteria['type'])) {
            $query .= " AND pr.type = ?";
            $params[] = $criteria['type'];
        }

        if (!empty($criteria['note_min'])) {
            $query .= " AND p.id IN (
                         SELECT r_inner.praticien_id
                         FROM " . TABLE_APPOINTMENTS . " r_inner
                         JOIN " . TABLE_EVALUATIONS . " e_inner ON r_inner.prestation_id = e_inner.prestation_id AND r_inner.personne_id = e_inner.personne_id
                         WHERE r_inner.praticien_id IS NOT NULL
                         GROUP BY r_inner.praticien_id
                         HAVING AVG(e_inner.note) >= ?
                        )";
            $params[] = $criteria['note_min'];
        }

        if (!empty($criteria['disponibilite']) && !empty($criteria['date'])) {
            $query .= " AND p.id NOT IN (
                        SELECT DISTINCT r_avail.praticien_id
                        FROM " . TABLE_APPOINTMENTS . " r_avail
                        WHERE DATE(r_avail.date_rdv) = ? AND r_avail.statut IN ('planifie', 'confirme')
                        )";
            $params[] = $criteria['date'];
        }

        $query .= " ORDER BY p.nom, p.prenom";

        $stmt = executeQuery($query, $params);
        $providers = $stmt->fetchAll();

        foreach ($providers as &$provider) {
            $provider['statut_badge'] = getStatusBadge($provider['statut']);

            $servicesQuery = "SELECT DISTINCT pr.id, pr.nom, pr.type, pr.categorie
                           FROM " . TABLE_PRESTATIONS . " pr
                           JOIN " . TABLE_APPOINTMENTS . " r ON pr.id = r.prestation_id
                           WHERE r.praticien_id = ?
                           LIMIT 3";
            $servicesStmt = executeQuery($servicesQuery, [$provider['id']]);
            $provider['services'] = $servicesStmt->fetchAll();

            $ratingQuery = "SELECT AVG(e.note) as note_moyenne, COUNT(e.id) as nombre_avis
                         FROM " . TABLE_EVALUATIONS . " e
                         JOIN " . TABLE_APPOINTMENTS . " r ON e.prestation_id = r.prestation_id AND e.personne_id = r.personne_id
                         WHERE r.praticien_id = ?";
            $ratingStmt = executeQuery($ratingQuery, [$provider['id']]);
            $rating = $ratingStmt->fetch();

            $provider['note_moyenne'] = $rating['note_moyenne'] ? round($rating['note_moyenne'], 1) : 0;
            $provider['nombre_avis'] = $rating['nombre_avis'] ?? 0;
        }

        return $providers;
    } catch (Exception $e) {
        flashMessage("Une erreur est survenue lors de la recherche de prestataires", "danger");
        return [];
    }
}

function getProviderCalendar($provider_id, $start_date, $end_date)
{
    $provider_id = (int)sanitizeInput($provider_id);
    $start_date = sanitizeInput($start_date);
    $end_date = sanitizeInput($end_date);

    if (!$provider_id || empty($start_date) || empty($end_date)) {
        return [];
    }

    $query = "SELECT r.id, r.date_rdv, r.duree, r.lieu, r.type_rdv, r.statut,
              pr.nom as prestation_nom, pr.type as prestation_type
              FROM " . TABLE_APPOINTMENTS . " r
              JOIN " . TABLE_PRESTATIONS . " pr ON r.prestation_id = pr.id
              WHERE r.praticien_id = ?
              AND DATE(r.date_rdv) BETWEEN ? AND ?
              AND r.statut IN ('planifie', 'confirme')
              ORDER BY r.date_rdv";

    $stmt = executeQuery($query, [$provider_id, $start_date, $end_date]);
    $appointments = $stmt->fetchAll();

    foreach ($appointments as &$appointment) {
        $appointment['date_rdv_formatee'] = formatDate($appointment['date_rdv'], 'd/m/Y H:i');
        $appointment['statut_badge'] = getStatusBadge($appointment['statut']);
    }

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
            'is_available' => count($dayAppointments) < 8,
            'slots_taken' => count($dayAppointments),
            'slots_available' => 8 - count($dayAppointments)
        ];
    }

    return $calendar;
}

function getProviderRatings($provider_id, $page = 1, $limit = 10)
{
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

    $offset = ($page - 1) * $limit;

    $query = "SELECT e.*, CONCAT(p.prenom, ' ', p.nom) as client_nom,
              pr.nom as prestation_nom, pr.type as prestation_type
              FROM " . TABLE_EVALUATIONS . " e
              JOIN " . TABLE_APPOINTMENTS . " r ON e.prestation_id = r.prestation_id AND e.personne_id = r.personne_id
              JOIN " . TABLE_USERS . " p ON e.personne_id = p.id
              JOIN " . TABLE_PRESTATIONS . " pr ON e.prestation_id = pr.id
              WHERE r.praticien_id = ?
              ORDER BY e.date_evaluation DESC
              LIMIT ? OFFSET ?";

    $stmt = executeQuery($query, [$provider_id, $limit, $offset]);
    $ratings = $stmt->fetchAll();

    foreach ($ratings as &$rating) {
        if (isset($rating['date_evaluation'])) {
            $rating['date_evaluation_formatee'] = formatDate($rating['date_evaluation'], 'd/m/Y');
        }
    }

    $countQuery = "SELECT COUNT(e.id) as total
                  FROM " . TABLE_EVALUATIONS . " e
                  JOIN " . TABLE_APPOINTMENTS . " r ON e.prestation_id = r.prestation_id AND e.personne_id = r.personne_id
                  WHERE r.praticien_id = ?";

    $countStmt = executeQuery($countQuery, [$provider_id]);
    $countResult = $countStmt->fetch();
    $total = $countResult['total'];

    $totalPages = ceil($total / $limit);

    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

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

function getProviderAverageRating($provider_id)
{
    $provider_id = (int)sanitizeInput($provider_id);

    $query = "SELECT AVG(e.note) as moyenne
              FROM " . TABLE_EVALUATIONS . " e
              JOIN " . TABLE_APPOINTMENTS . " r ON e.prestation_id = r.prestation_id AND e.personne_id = r.personne_id
              WHERE r.praticien_id = ?";

    $stmt = executeQuery($query, [$provider_id]);
    $result = $stmt->fetch();

    return $result['moyenne'] ? round($result['moyenne'], 1) : 0;
}

function getProviderCategories()
{
    try {
        $query = "SELECT DISTINCT categorie, COUNT(DISTINCT id) as count
                FROM " . TABLE_PRESTATIONS . "
                WHERE categorie IS NOT NULL AND categorie != ''
                GROUP BY categorie
                ORDER BY count DESC";

        return executeQuery($query)->fetchAll();
    } catch (Exception $e) {
        flashMessage("Une erreur est survenue lors de la récupération des catégories", "danger");
        return [];
    }
}

function getProviderContracts($provider_id, $status = 'active')
{
    $provider_id = (int)sanitizeInput($provider_id);
    $status = sanitizeInput($status);

    if (!$provider_id) {
        return [];
    }

    $query = "SELECT DISTINCT e.id as entreprise_id, e.nom as entreprise_nom,
              MIN(r.date_rdv) as date_debut, MAX(r.date_rdv) as date_derniere,
              COUNT(r.id) as nombre_prestations
              FROM " . TABLE_APPOINTMENTS . " r
              JOIN " . TABLE_USERS . " s ON r.personne_id = s.id
              JOIN " . TABLE_COMPANIES . " e ON s.entreprise_id = e.id
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

    foreach ($contracts as &$contract) {
        if (isset($contract['date_debut'])) {
            $contract['date_debut_formatee'] = formatDate($contract['date_debut'], 'd/m/Y');
        }
        if (isset($contract['date_derniere'])) {
            $contract['date_derniere_formatee'] = formatDate($contract['date_derniere'], 'd/m/Y');
        }

        $contract['statut'] = ($status === 'active') ? STATUS_ACTIVE : 'expire';
        $contract['statut_badge'] = getStatusBadge($contract['statut']);
    }

    return $contracts;
}

function getProviderServices($provider_id)
{
    $provider_id = (int)sanitizeInput($provider_id);
    if (!$provider_id) {
        return [];
    }

    $query = "SELECT DISTINCT pr.id, pr.nom, pr.description, pr.type, pr.categorie,
              pr.prix, pr.duree, pr.niveau_difficulte, COUNT(r.id) as nombre_prestations
              FROM " . TABLE_PRESTATIONS . " pr
              JOIN " . TABLE_APPOINTMENTS . " r ON pr.id = r.prestation_id
              WHERE r.praticien_id = ?
              GROUP BY pr.id, pr.nom, pr.description, pr.type, pr.categorie, 
              pr.prix, pr.duree, pr.niveau_difficulte
              ORDER BY nombre_prestations DESC";

    $stmt = executeQuery($query, [$provider_id]);
    $services = $stmt->fetchAll();

    foreach ($services as &$service) {
        if (isset($service['prix'])) {
            $service['prix_formate'] = formatMoney($service['prix']);
        }
    }

    return $services;
}

function getProviderInvoices($provider_id, $start_date = null, $end_date = null)
{
    $provider_id = (int)sanitizeInput($provider_id);
    $start_date = sanitizeInput($start_date);
    $end_date = sanitizeInput($end_date);

    if (!$provider_id) {
        return [];
    }

    $query = "SELECT CONCAT(YEAR(r.date_rdv), '-', MONTH(r.date_rdv)) as facture_id,
              DATE_FORMAT(r.date_rdv, '%Y-%m') as periode,
              COUNT(r.id) as nombre_prestations,
              SUM(pr.prix) as montant_total,
              MIN(r.date_rdv) as premiere_prestation,
              MAX(r.date_rdv) as derniere_prestation,
              'payee' as statut
              FROM " . TABLE_APPOINTMENTS . " r
              JOIN " . TABLE_PRESTATIONS . " pr ON r.prestation_id = pr.id
              WHERE r.praticien_id = ? AND r.statut = 'termine'";

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

        $invoice['statut_badge'] = getStatusBadge($invoice['statut']);
    }

    return $invoices;
}

function updateProviderSettings($provider_id, $settings)
{
    $provider_id = (int)sanitizeInput($provider_id);
    $settings = sanitizeInput($settings);

    if (!$provider_id || empty($settings)) {
        flashMessage("ID de prestataire invalide ou paramètres manquants", "danger");
        return false;
    }

    $allowedFields = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'photo_url'
    ];

    $filteredSettings = array_intersect_key($settings, array_flip($allowedFields));

    if (empty($filteredSettings)) {
        flashMessage("Aucun paramètre valide à mettre à jour", "warning");
        return false;
    }

    try {
        $result = updateRow(
            TABLE_USERS,
            $filteredSettings,
            "id = :id AND role_id = :role",
            [':id' => $provider_id, ':role' => ROLE_PRESTATAIRE]
        );

        if ($result) {
            flashMessage("Les paramètres du prestataire ont été mis à jour avec succès", "success");
            return true;
        }

        return false;
    } catch (Exception $e) {
        flashMessage("Une erreur est survenue lors de la mise à jour des paramètres", "danger");
        return false;
    }
}

function addProviderRating($user_id, $prestation_id, $note, $commentaire = '')
{
    $user_id = (int)sanitizeInput($user_id);
    $prestation_id = (int)sanitizeInput($prestation_id);
    $note = (int)sanitizeInput($note);
    $commentaire = sanitizeInput($commentaire);

    if (!$user_id || !$prestation_id || !$note || $note < 1 || $note > 5) {
        flashMessage("Paramètres invalides pour l'évaluation", "danger");
        return false;
    }

    try {
        $ratingData = [
            'personne_id' => $user_id,
            'prestation_id' => $prestation_id,
            'note' => $note,
            'commentaire' => $commentaire,
            'date_evaluation' => date('Y-m-d')
        ];

        $ratingId = insertRow(TABLE_EVALUATIONS, $ratingData);

        if ($ratingId) {
            flashMessage("Merci pour votre évaluation !", "success");
            return $ratingId;
        }

        return false;
    } catch (Exception $e) {
        flashMessage("Une erreur est survenue lors de l'ajout de l'évaluation", "danger");
        return false;
    }
}

function handleUpdateProviderSettings()
{
    $provider_id = $_SESSION['user_id'] ?? null;
    if (!$provider_id) {
        flashMessage("Session invalide ou expirée.", "danger");
        return;
    }

    $profileData = [
        'nom' => $_POST['nom'] ?? null,
        'prenom' => $_POST['prenom'] ?? null,
        'telephone' => $_POST['telephone'] ?? null,
    ];
    $profileData = array_filter($profileData, function ($value) {
        return $value !== null;
    });

    $preferenceData = [
        'langue' => $_POST['langue'] ?? 'fr',
        'notif_email' => isset($_POST['notif_email']) ? 1 : 0,
    ];

    $profileUpdated = false;
    $prefsUpdated = false;

    if (!empty($profileData)) {
        try {
            $rowsAffected = updateRow(TABLE_USERS, $profileData, "id = :id", [':id' => $provider_id]);
            if ($rowsAffected > 0) {
                $profileUpdated = true;

                if (isset($profileData['prenom']) || isset($profileData['nom'])) {
                    $updatedUser = fetchOne(TABLE_USERS, "id = :id", [':id' => $provider_id]);
                    $_SESSION['user_name'] = ($updatedUser['prenom'] ?? '') . ' ' . ($updatedUser['nom'] ?? '');
                }
            }
        } catch (Exception $e) {
            flashMessage("Une erreur est survenue lors de la mise à jour du profil.", "danger");
            return;
        }
    }

    $existingPrefs = fetchOne(TABLE_USER_PREFERENCES, "personne_id = :id", [':id' => $provider_id]);

    try {
        if ($existingPrefs) {
            $rowsAffected = updateRow(TABLE_USER_PREFERENCES, $preferenceData, "personne_id = :id", [':id' => $provider_id]);
            if ($rowsAffected > 0) $prefsUpdated = true;
        } else {
            $preferenceData['personne_id'] = $provider_id;
            $newId = insertRow(TABLE_USER_PREFERENCES, $preferenceData);
            if ($newId) $prefsUpdated = true;
        }
    } catch (Exception $e) {
        flashMessage("Une erreur est survenue lors de la mise à jour des préférences.", "danger");
    }

    if ($profileUpdated || $prefsUpdated) {
        flashMessage("Profil et préférences mis à jour avec succès.", "success");

        if (isset($preferenceData['langue']) && ($_SESSION['user_language'] ?? 'fr') !== $preferenceData['langue']) {
            $_SESSION['user_language'] = $preferenceData['langue'];
        }
    } else {
        flashMessage("Aucune modification détectée.", "info");
    }
}

function displayProviderSettingsPageData()
{
    $provider_id = $_SESSION['user_id'] ?? null;
    if (!$provider_id) {
        flashMessage("Utilisateur non identifié.", "danger");
        redirectTo(WEBCLIENT_URL . '/login.php');
        exit;
    }

    $providerData = fetchOne(TABLE_USERS, "id = :id AND role_id = :role", [':id' => $provider_id, ':role' => ROLE_PRESTATAIRE]);
    $preferencesData = fetchOne(TABLE_USER_PREFERENCES, "personne_id = :id", [':id' => $provider_id]);

    if (!$providerData) {
        flashMessage("Impossible de charger les informations du compte prestataire.", "danger");
        return ['provider' => [], 'settings' => []];
    }

    if (!$preferencesData) {
        $preferencesData = ['langue' => 'fr', 'notif_email' => 1, 'theme' => 'clair'];
    }

    return [
        'provider' => $providerData,
        'settings' => $preferencesData
    ];
}

function handleChangePassword($provider_id, $current_password, $new_password, $confirm_password)
{
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        flashMessage("Tous les champs de mot de passe sont requis.", "danger");
        return;
    }

    if (strlen($new_password) < 8) {
        flashMessage("Le nouveau mot de passe doit comporter au moins 8 caractères.", "danger");
        return;
    }

    if ($new_password !== $confirm_password) {
        flashMessage("Le nouveau mot de passe et sa confirmation ne correspondent pas.", "danger");
        return;
    }

    $user = fetchOne(TABLE_USERS, "id = :id", [':id' => $provider_id]);
    if (!$user) {
        flashMessage("Utilisateur non trouvé.", "danger");
        return;
    }

    if (!password_verify($current_password, $user['mot_de_passe'])) {
        flashMessage("Le mot de passe actuel est incorrect.", "danger");
        return;
    }

    if (password_verify($new_password, $user['mot_de_passe'])) {
        flashMessage("Le nouveau mot de passe doit être différent de l'ancien.", "warning");
        return;
    }

    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    try {
        $rowsAffected = updateRow(TABLE_USERS, ['mot_de_passe' => $new_password_hash], "id = :id", [':id' => $provider_id]);
        if ($rowsAffected > 0) {
            flashMessage("Mot de passe modifié avec succès.", "success");
        } else {
            flashMessage("La mise à jour du mot de passe a échoué sans erreur explicite.", "warning");
        }
    } catch (Exception $e) {
        flashMessage("Une erreur serveur est survenue lors de la modification du mot de passe.", "danger");
    }
}


function renderStars($rating)
{
    $rating = max(0, min(5, round((float)($rating ?? 0))));
    $output = '';
    for ($i = 1; $i <= 5; $i++) {
        $output .= '<i class="' . ($i <= $rating ? 'fas' : 'far') . ' fa-star text-warning"></i> ';
    }
    return trim($output);
}

function getProviderAppointments($provider_id, $status_filter = 'upcoming', $page = 1, $limit = 15)
{
    $provider_id = (int)sanitizeInput($provider_id);
    $status_filter = sanitizeInput($status_filter);
    $page = max(1, (int)$page);
    $limit = max(1, (int)$limit);
    $offset = ($page - 1) * $limit;

    if (!$provider_id) {
        return [
            'appointments' => [],
            'pagination_html' => '',
            'total' => 0,
            'current_filter' => $status_filter
        ];
    }

    $baseQuery = "FROM " . TABLE_APPOINTMENTS . " r
                  JOIN " . TABLE_PRESTATIONS . " pr ON r.prestation_id = pr.id
                  JOIN " . TABLE_USERS . " c ON r.personne_id = c.id
                  WHERE r.praticien_id = :provider_id";

    $countQuery = "SELECT COUNT(r.id) as total " . $baseQuery;
    $dataQuery = "SELECT r.id, r.date_rdv, r.duree, r.lieu, r.type_rdv, r.statut, r.notes,
                         pr.nom as prestation_nom, pr.type as prestation_type,
                         CONCAT(c.prenom, ' ', c.nom) as client_nom, c.email as client_email, c.telephone as client_telephone "
        . $baseQuery;

    $params = [':provider_id' => $provider_id];
    $whereStatus = '';
    $orderBy = 'ORDER BY r.date_rdv ASC';

    switch ($status_filter) {
        case 'upcoming':
            $whereStatus = " AND r.statut IN ('planifie', 'confirme') AND r.date_rdv >= NOW()";
            break;
        case 'past':
            $whereStatus = " AND r.statut IN ('termine', 'no_show')";
            $orderBy = 'ORDER BY r.date_rdv DESC';
            break;
        case 'cancelled':
            $whereStatus = " AND r.statut = 'annule'";
            $orderBy = 'ORDER BY r.date_rdv DESC';
            break;
        case 'all':
            $orderBy = 'ORDER BY r.date_rdv DESC';
            break;
        default:
            $whereStatus = " AND r.statut IN ('planifie', 'confirme') AND r.date_rdv >= NOW()";
            $status_filter = 'upcoming';
            break;
    }

    $countQuery .= $whereStatus;
    $dataQuery .= $whereStatus . " " . $orderBy . " LIMIT :limit OFFSET :offset";

    $countStmt = executeQuery($countQuery, $params);
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $limit);

    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    $stmt = executeQuery($dataQuery, $params);
    $appointments = $stmt->fetchAll();

    foreach ($appointments as &$appointment) {
        if (isset($appointment['date_rdv'])) {
            $appointment['date_rdv_formatee'] = formatDate($appointment['date_rdv'], 'd/m/Y H:i');
            $appointment['date_formatee_jour'] = formatDate($appointment['date_rdv'], 'd/m/Y');
            $appointment['date_formatee_heure'] = formatDate($appointment['date_rdv'], 'H:i');
        }
        $appointment['statut_badge'] = getStatusBadge($appointment['statut']);
        $appointment['type_rdv_icon'] = match ($appointment['type_rdv']) {
            'presentiel' => 'fa-map-marker-alt',
            'visio' => 'fa-video',
            'telephone' => 'fa-phone',
            default => 'fa-question-circle'
        };
        $appointment['type_rdv_text'] = ucfirst($appointment['type_rdv'] ?? '?');
    }

    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];
    $urlPattern = "?status=" . urlencode($status_filter) . "&page={page}";
    $paginationHtml = renderPagination($paginationData, $urlPattern);

    return [
        'appointments' => $appointments,
        'pagination_html' => $paginationHtml,
        'total' => $total,
        'current_filter' => $status_filter
    ];
}
