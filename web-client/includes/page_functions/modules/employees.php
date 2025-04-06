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
    // Sanitize input parameters
    $company_id = sanitizeInput($company_id);
    if ($company_id !== null) {
        $company_id = filter_var($company_id, FILTER_VALIDATE_INT);
    }
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);
    $search = sanitizeInput($search);

    // Calcul de l'offset pour la pagination
    $offset = ($page - 1) * $limit;

    // Construction de la requête de base
    $query = "SELECT p.id, p.nom, p.prenom, p.email, p.telephone, p.statut, p.photo_url, 
              p.derniere_connexion, e.nom as entreprise_nom 
              FROM personnes p
              LEFT JOIN entreprises e ON p.entreprise_id = e.id
              WHERE p.role_id = ?";
    $countQuery = "SELECT COUNT(id) as total FROM personnes WHERE role_id = ?";
    $params = [ROLE_SALARIE];

    // Filtre par entreprise si spécifié
    if ($company_id) {
        $query .= " AND p.entreprise_id = ?";
        $countQuery .= " AND entreprise_id = ?";
        $params[] = $company_id;
    }

    // Ajout de la condition de recherche si nécessaire
    if (!empty($search)) {
        $query .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR p.email LIKE ?)";
        $countQuery .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }

    // Ajout de l'ordre et de la limite
    $query .= " ORDER BY p.nom, p.prenom ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    // Exécution de la requête principale
    $stmt = executeQuery($query, $params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque employé, ajouter le badge de statut
    foreach ($employees as &$employee) {
        if (isset($employee['statut'])) {
            $employee['statut_badge'] = getStatusBadge($employee['statut']);
        }
        if (isset($employee['derniere_connexion'])) {
            $employee['derniere_connexion_formatee'] = formatDate($employee['derniere_connexion']);
        }
    }

    // Calcul du nombre total pour la pagination
    $countStmt = executeQuery($countQuery, array_slice($params, 0, -2));
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
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

    // Construction de l'URL pour la pagination (considérer les filtres existants)
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

/**
 * récupère les détails d'un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @return array|false détails du salarié ou false si non trouvé
 */
function getEmployeeDetails($employee_id)
{
    // Validation de l'ID
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        flashMessage("ID de salarié invalide", "danger");
        return false;
    }

    // Récupération des informations du salarié avec fetchOne
    $employee = fetchOne('personnes', "id = $employee_id AND role_id = " . ROLE_SALARIE);

    if (!$employee) {
        flashMessage("Salarié non trouvé", "warning");
        return false;
    }

    // Ajouter le badge de statut
    if (isset($employee['statut'])) {
        $employee['statut_badge'] = getStatusBadge($employee['statut']);
    }

    // Formater les dates
    if (isset($employee['date_naissance'])) {
        $employee['date_naissance_formatee'] = formatDate($employee['date_naissance'], 'd/m/Y');
    }
    if (isset($employee['derniere_connexion'])) {
        $employee['derniere_connexion_formatee'] = formatDate($employee['derniere_connexion']);
    }

    // Récupération de l'entreprise associée
    if (!empty($employee['entreprise_id'])) {
        $entreprise = fetchOne('entreprises', "id = " . $employee['entreprise_id']);
        if ($entreprise) {
            $employee['entreprise_nom'] = $entreprise['nom'];
        }
    }

    // Récupération des préférences utilisateur
    $preferences = fetchAll('preferences_utilisateurs', "personne_id = " . $employee_id);
    if (!empty($preferences)) {
        $employee['preferences'] = $preferences[0];
    }

    return $employee;
}

/**
 * met à jour le profil d'un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @param array $profile_data données du profil à mettre à jour
 * @return bool résultat de la mise à jour
 */
function updateEmployeeProfile($employee_id, $profile_data)
{
    // Validation and sanitization
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

    // Liste des champs autorisés
    $allowedFields = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'date_naissance',
        'genre',
        'photo_url'
    ];

    // Filtrage des paramètres
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
 * récupère les rendez-vous médicaux d'un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @param string $status filtre par statut (upcoming, past, all)
 * @return array liste des rendez-vous médicaux
 */
function getEmployeeAppointments($employee_id, $status = 'upcoming')
{
    // Validation de l'ID
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    if (!$employee_id) {
        return [];
    }

    // Construction de la requête
    $query = "SELECT r.id, r.date_rdv, r.duree, r.lieu, r.type_rdv, r.statut,
              p.nom as prestation_nom
              FROM rendez_vous r
              JOIN prestations p ON r.prestation_id = p.id
              WHERE r.personne_id = ? AND p.type = 'consultation'";
    $params = [$employee_id];

    // Filtre par statut
    if ($status === 'upcoming') {
        $query .= " AND r.date_rdv >= CURDATE() AND r.statut NOT IN ('annule', 'termine')";
    } else if ($status === 'past') {
        $query .= " AND (r.date_rdv < CURDATE() OR r.statut = 'termine')";
    }

    $query .= " ORDER BY r.date_rdv";
    if ($status === 'upcoming') {
        $query .= " ASC";
    } else {
        $query .= " DESC";
    }

    $appointments = executeQuery($query, $params)->fetchAll();

    // Formater les dates et ajouter les badges de statut
    foreach ($appointments as &$appointment) {
        if (isset($appointment['date_rdv'])) {
            $appointment['date_rdv_formatee'] = formatDate($appointment['date_rdv'], 'd/m/Y H:i');
        }
        if (isset($appointment['statut'])) {
            $appointment['statut_badge'] = getStatusBadge($appointment['statut']);
        }
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

            // Mettre à jour le numéro de référence avec l'ID de transaction
            updateRow(
                'dons',
                ['reference' => 'DON-' . $donationId . '-TR' . $transactionId],
                'id = :id',
                ['id' => $donationId]
            );
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
            $event['date_debut_formatee'] = formatDate($event['date_debut'], 'd/m/Y');
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
 * réserve un rendez-vous pour un salarié
 * 
 * @param int $employee_id identifiant du salarié
 * @param array $appointment_data données du rendez-vous
 * @return int|false ID du rendez-vous créé ou false en cas d'erreur
 */
function bookEmployeeAppointment($employee_id, $appointment_data)
{
    // Validation de l'ID et des données
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);
    $appointment_data = sanitizeInput($appointment_data);

    if (!$employee_id || empty($appointment_data)) {
        flashMessage("Données de rendez-vous invalides", "danger");
        return false;
    }

    // Vérification des données requises
    $requiredFields = ['prestation_id', 'date_rdv', 'duree', 'type_rdv'];
    foreach ($requiredFields as $field) {
        if (empty($appointment_data[$field])) {
            flashMessage("Des champs obligatoires sont manquants", "danger");
            return false;
        }
    }

    // Vérification de la disponibilité du créneau
    $prestation_id = filter_var($appointment_data['prestation_id'], FILTER_VALIDATE_INT);
    $dateHeure = $appointment_data['date_rdv'];
    $duree = filter_var($appointment_data['duree'], FILTER_VALIDATE_INT);

    if (!isTimeSlotAvailable($dateHeure, $duree, $prestation_id)) {
        flashMessage("Ce créneau horaire n'est pas disponible", "warning");
        return false;
    }

    try {
        // Début de transaction
        beginTransaction();

        // Préparation des données pour insertion
        $rdvData = [
            'personne_id' => $employee_id,
            'prestation_id' => $prestation_id,
            'date_rdv' => $dateHeure,
            'duree' => $duree,
            'lieu' => $appointment_data['lieu'] ?? null,
            'type_rdv' => $appointment_data['type_rdv'],
            'statut' => 'planifie',
            'notes' => $appointment_data['notes'] ?? null
        ];

        // Insertion du rendez-vous
        $appointmentId = insertRow('rendez_vous', $rdvData);

        if (!$appointmentId) {
            rollbackTransaction();
            flashMessage("Impossible de créer le rendez-vous", "danger");
            return false;
        }

        // Ajout d'une notification si nécessaire
        if (!empty($appointment_data['notifier']) && $appointment_data['notifier'] === true) {
            $notifData = [
                'personne_id' => $employee_id,
                'titre' => 'Nouveau rendez-vous confirmé',
                'message' => 'Votre rendez-vous du ' . formatDate($dateHeure) . ' a été confirmé.',
                'type' => 'info',
                'lien' => '/rendez-vous/' . $appointmentId
            ];

            try {
                insertRow('notifications', $notifData);
            } catch (Exception $notifError) {
                // Une erreur sur la notification ne doit pas annuler le rendez-vous
                logSystemActivity('warning', "Erreur création notification pour RDV #$appointmentId: " . $notifError->getMessage());
            }
        }

        // Validation de la transaction
        commitTransaction();

        // Journalisation
        logReservationActivity($employee_id, $prestation_id, 'creation', "RDV #$appointmentId créé");
        flashMessage("Votre rendez-vous a été réservé avec succès", "success");

        return $appointmentId;
    } catch (Exception $e) {
        rollbackTransaction();
        logSystemActivity('error', "Erreur création rendez-vous: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la création du rendez-vous", "danger");
        return false;
    }
}
