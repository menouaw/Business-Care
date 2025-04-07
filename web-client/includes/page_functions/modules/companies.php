<?php

function validateBirthDate($dateNaissanceInput, &$errors)
{
    if (!empty($dateNaissanceInput)) {
        $d = DateTime::createFromFormat('Y-m-d', $dateNaissanceInput);
        if (!$d || $d->format('Y-m-d') !== $dateNaissanceInput) {
            $errors[] = "La date de naissance fournie n'est pas valide.";
        } else {
            $today = new DateTime();
            if ($d > $today) {
                $errors[] = "La date de naissance ne peut pas être dans le futur.";
            } else {
                $age = $today->diff($d)->y;
                if ($age < 16) {
                    $errors[] = "L'employé doit avoir au moins 16 ans.";
                } elseif ($age > 100) {
                    $errors[] = "L'âge de l'employé semble irréaliste (plus de 100 ans).";
                }
            }
        }
    }
}


function getCompaniesList($page = 1, $limit = 5, $search = '')
{
    // Sanitize inputs
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);
    $search = sanitizeInput($search);

    try {
        $where = '1=1';
        $params = [];

        if (!empty($search)) {
            $searchTerm = "%" . $search . "%";
            $where .= " AND (e.nom LIKE :search OR e.email LIKE :search OR e.adresse LIKE :search OR e.ville LIKE :search)";
            $params['search'] = $searchTerm;
        }

        $offset = ($page - 1) * $limit;

        $query = "SELECT e.id, e.nom, e.siret, e.adresse, e.code_postal, e.ville, e.telephone, e.email, e.site_web, e.logo_url, e.taille_entreprise, e.secteur_activite, e.date_creation, e.created_at, e.updated_at,
                  COUNT(DISTINCT p.id) as nombre_employes,
                  COUNT(DISTINCT c.id) as nombre_contrats_actifs 
                  FROM entreprises e 
                  LEFT JOIN personnes p ON e.id = p.entreprise_id AND p.role_id = :role_id
                  LEFT JOIN contrats c ON e.id = c.entreprise_id AND (c.date_fin IS NULL OR c.date_fin >= CURDATE()) AND c.statut = 'actif'
                  WHERE " . $where;


        $queryParams = ['role_id' => ROLE_SALARIE];
        $queryParams = array_merge($queryParams, $params);

        $query .= " GROUP BY e.id, e.nom, e.siret, e.adresse, e.code_postal, e.ville, e.telephone, e.email, e.site_web, e.logo_url, e.taille_entreprise, e.secteur_activite, e.date_creation, e.created_at, e.updated_at
                    ORDER BY e.nom LIMIT :limit OFFSET :offset";
        $queryParams['limit'] = $limit;
        $queryParams['offset'] = $offset;

        $companies = executeQuery($query, $queryParams)->fetchAll();

        $countQuery = "SELECT COUNT(DISTINCT e.id) as total FROM entreprises e WHERE " . $where;
        $countResult = executeQuery($countQuery, $params)->fetch();
        $total = $countResult['total'];

        $totalPages = ceil($total / $limit);

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

        return [
            'companies' => $companies,
            'pagination' => [
                'current' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages
            ],
            'pagination_html' => renderPagination($paginationData, $urlPattern)
        ];
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur lors de la récupération des entreprises: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors du chargement des entreprises", "danger");
        return [
            'companies' => [],
            'pagination' => [
                'current' => 1,
                'limit' => $limit,
                'total' => 0,
                'totalPages' => 0
            ],
            'pagination_html' => ''
        ];
    }
}

/**
 * Récupère les détails d'une entreprise
 * 
 * @param int $company_id 
 * @return array|false 
 */
function getCompanyDetails($company_id)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide", "danger");
        return false;
    }

    try {
        $query = "SELECT e.*, 
                  COUNT(DISTINCT p.id) as nombre_employes,
                  COUNT(DISTINCT c.id) as nombre_contrats_actifs -- Renommé pour clarté
                  FROM entreprises e
                  LEFT JOIN personnes p ON e.id = p.entreprise_id AND p.role_id = :role_id
                  LEFT JOIN contrats c ON e.id = c.entreprise_id AND (c.date_fin IS NULL OR c.date_fin >= CURDATE()) AND c.statut = 'active'
                  WHERE e.id = :company_id
                  GROUP BY e.id, e.nom, e.siret, e.adresse, e.code_postal, e.ville, e.telephone, e.email, e.site_web, e.logo_url, e.taille_entreprise, e.secteur_activite, e.date_creation, e.created_at, e.updated_at";

        $company = executeQuery($query, [
            'role_id' => ROLE_SALARIE,
            'company_id' => $company_id
        ])->fetch();

        if (!$company) {
            flashMessage("Entreprise non trouvée", "danger");
            return false;
        }

        if (isset($company['date_creation'])) {
            $dateToFormat = $company['date_creation'] ?? $company['created_at'] ?? null;
            if ($dateToFormat) {
                $company['date_creation_formatee'] = formatDate($dateToFormat, 'd/m/Y');
            }
        }
        if (isset($company['created_at'])) {
            $company['created_at_formatee'] = formatDate($company['created_at']);
        }
        if (isset($company['updated_at'])) {
            $company['updated_at_formatee'] = formatDate($company['updated_at']);
        }

        $contractsQuery = "SELECT c.*
                         FROM contrats c
                         WHERE c.entreprise_id = :company_id AND (c.date_fin IS NULL OR c.date_fin >= CURDATE()) AND c.statut = 'actif'
                         ORDER BY c.date_debut DESC";
        $contracts = executeQuery($contractsQuery, ['company_id' => $company_id])->fetchAll();

        foreach ($contracts as &$contract) {
            if (isset($contract['date_debut'])) {
                $contract['date_debut_formatee'] = formatDate($contract['date_debut'], 'd/m/Y');
            }
            if (isset($contract['date_fin'])) {
                $contract['date_fin_formatee'] = formatDate($contract['date_fin'], 'd/m/Y');
            }
            if (isset($contract['statut'])) {
                $contract['statut_badge'] = getStatusBadge($contract['statut']);
            }
            $contract['reference'] = 'CT-' . str_pad($contract['id'], 6, '0', STR_PAD_LEFT);
        }
        $company['contrats_actifs'] = $contracts;

        $servicesQuery = "SELECT DISTINCT p.*
                        FROM prestations p
                        JOIN contrats_prestations cp ON p.id = cp.prestation_id
                        JOIN contrats c ON cp.contrat_id = c.id
                        WHERE c.entreprise_id = :company_id AND (c.date_fin IS NULL OR c.date_fin >= CURDATE()) AND c.statut = 'actif'
                        ORDER BY p.categorie, p.nom";
        $company['services_disponibles'] = executeQuery($servicesQuery, ['company_id' => $company_id])->fetchAll();

        foreach ($company['services_disponibles'] as &$service) {
            if (isset($service['prix'])) {
                $service['prix_formate'] = formatMoney($service['prix']);
            }
        }

        $statsQuery = "SELECT COUNT(r.id) as total_rdv,
                     SUM(CASE WHEN r.statut = 'termine' THEN 1 ELSE 0 END) as rdv_termines,
                     SUM(CASE WHEN r.date_rdv >= CURDATE() THEN 1 ELSE 0 END) as rdv_a_venir,
                     COUNT(DISTINCT p.id) as employes_actifs -- Renommé
                     FROM rendez_vous r
                     JOIN personnes p ON r.personne_id = p.id
                     WHERE p.entreprise_id = :company_id AND p.role_id = :role_id";
        $company['statistiques'] = executeQuery($statsQuery, [
            'company_id' => $company_id,
            'role_id' => ROLE_SALARIE
        ])->fetch();

        return $company;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération statistiques entreprise: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération des statistiques", "danger");
        return [];
    }
}

/**
 * Récupère les activités récentes d'une entreprise
 * 
 * @param int $company_id 
 * @param int $limit 
 * @return array 
 */
function getCompanyRecentActivity($company_id, $limit = 10)
{
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $limit = min(50, max(1, (int)$limit)); // Limiter entre 1 et 50 activités

    if (!$company_id) {

        return [];
    }

    try {
        $query = "SELECT l.id, l.action, l.details, l.created_at, 
                CONCAT(p.prenom, ' ', p.nom) as utilisateur
                FROM logs l
                JOIN personnes p ON l.personne_id = p.id
                WHERE p.entreprise_id = :company_id
                ORDER BY l.created_at DESC
                LIMIT :limit";

        $activities = executeQuery($query, [
            ':company_id' => $company_id,
            ':limit' => $limit
        ])->fetchAll();

        foreach ($activities as &$activity) {
            $activity['date_formatee'] = formatDate($activity['created_at']);

            $actionIcons = [
                'login' => 'fas fa-sign-in-alt',
                'logout' => 'fas fa-sign-out-alt',
                'reservation' => 'fas fa-calendar-check',
                'update_profile' => 'fas fa-user-edit',
                'payment' => 'fas fa-credit-card',
                'add_employee' => 'fas fa-user-plus',
                'update_company' => 'fas fa-building',
                'add_contract' => 'fas fa-file-signature',
                'demande_devis' => 'fas fa-file-invoice-dollar'
            ];

            $activity['icone'] = $actionIcons[$activity['action']] ?? 'fas fa-history';
        }

        return $activities;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération activités: " . $e->getMessage());
        // Ne pas afficher de message flash ici non plus, l'absence d'activité n'est pas une erreur critique
        // flashMessage("Une erreur est survenue lors de la récupération des activités récentes", "danger");
        return [];
    }
}

/**
 * Récupère les factures d'une entreprise avec pagination et filtres de date.
 * 
 * @param int $company_id 
 * @param int $page 
 * @param int $limit 
 * @param string|null $start_date 
 * @param string|null $end_date 
 * @param string|array|null $status 
 * @return array 
 */
function getCompanyInvoices($company_id, $page = 1, $limit = 5, $start_date = null, $end_date = null, $status = null)
{

    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $page = max(1, (int)sanitizeInput($page));
    $limit = max(1, (int)sanitizeInput($limit));
    $start_date = $start_date ? sanitizeInput($start_date) : null;
    $end_date = $end_date ? sanitizeInput($end_date) : null;
    $status = $status ? sanitizeInput($status) : null;

    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide", "danger");
        return [
            'invoices' => [],
            'pagination' => [
                'current' => 1,
                'limit' => $limit,
                'total' => 0,
                'totalPages' => 0
            ],
            'pagination_html' => ''
        ];
    }

    try {
        $where = "f.entreprise_id = :company_id";
        $params = ['company_id' => $company_id];
        $countParams = ['company_id' => $company_id];
        $urlParams = []; // Pour l'URL de pagination

        if ($start_date) {
            $where .= " AND f.date_emission >= :start_date";
            $startDateSql = date('Y-m-d', strtotime($start_date));
            $params['start_date'] = $startDateSql;
            $countParams['start_date'] = $startDateSql;
            $urlParams['start_date'] = $start_date;
        }

        if ($end_date) {
            $where .= " AND f.date_emission <= :end_date";
            $endDateSql = date('Y-m-d', strtotime($end_date));
            $params['end_date'] = $endDateSql;
            $countParams['end_date'] = $endDateSql;
            $urlParams['end_date'] = $end_date;
        }

        if ($status) {
            $allowed_statuses = ['en_attente', 'payee', 'annulee', 'retard', 'impayee'];

            if (is_array($status)) {
                $valid_statuses = array_intersect($status, $allowed_statuses);
                if (!empty($valid_statuses)) {
                    $placeholders = [];
                    $statusParams = [];
                    foreach ($valid_statuses as $index => $st) {
                        $key = "status_" . $index;
                        $placeholders[] = ":" . $key;
                        $statusParams[$key] = $st;
                    }
                    $where .= " AND f.statut IN (" . implode(', ', $placeholders) . ")";
                    $params = array_merge($params, $statusParams);
                    $countParams = array_merge($countParams, $statusParams);
                    $urlParams['status'] = $valid_statuses;
                }
            } elseif (is_string($status) && in_array($status, $allowed_statuses)) {
                $where .= " AND f.statut = :status";
                $params['status'] = $status;
                $countParams['status'] = $status;
                $urlParams['status'] = $status;
            }
        }

        // 1. Compter le total des factures
        $countQuery = "SELECT COUNT(f.id) as total FROM factures f WHERE " . $where;
        $totalResult = executeQuery($countQuery, $countParams)->fetch();
        $total = $totalResult['total'] ?? 0;
        $totalPages = ceil($total / $limit);

        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $limit;


        $query = "SELECT f.id, f.numero_facture, f.date_emission, f.date_echeance,
                f.montant_ht, f.montant_total, f.statut, f.devis_id
                FROM factures f
                WHERE " . $where . "
                ORDER BY f.date_emission DESC
                LIMIT :limit OFFSET :offset";

        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $invoices = executeQuery($query, $params)->fetchAll();

        foreach ($invoices as &$invoice) {
            if ($invoice['devis_id']) {
                $invoice['devis_reference'] = 'DV-' . str_pad($invoice['devis_id'], 6, '0', STR_PAD_LEFT);
            } else {
                $invoice['devis_reference'] = 'N/A';
            }
            if (isset($invoice['date_emission'])) {
                $invoice['date_emission_formatee'] = formatDate($invoice['date_emission'], 'd/m/Y');
            }
            if (isset($invoice['date_echeance'])) {
                $invoice['date_echeance_formatee'] = formatDate($invoice['date_echeance'], 'd/m/Y');
            }
            if (isset($invoice['montant_ht'])) {
                $invoice['montant_ht_formate'] = formatMoney($invoice['montant_ht']);
            }
            if (isset($invoice['montant_total'])) {
                $invoice['montant_total_formate'] = formatMoney($invoice['montant_total']);
            }
            if (isset($invoice['statut'])) {
                $invoice['statut_badge'] = getStatusBadge($invoice['statut']);
            }
        }
        unset($invoice); // Détacher la référence

        // 4. Préparer les données de pagination HTML
        $paginationData = [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $total,
            'perPage' => $limit
        ];
        $urlPattern = "?page={page}";
        if (!empty($urlParams)) {
            $urlPattern .= '&' . http_build_query($urlParams);
        }

        // 5. Retourner les résultats
        return [
            'invoices' => $invoices,
            'pagination' => [
                'current' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages
            ],
            'pagination_html' => renderPagination($paginationData, $urlPattern)
        ];
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération factures: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération des factures", "danger");
        return [
            'invoices' => [],
            'pagination' => [
                'current' => 1,
                'limit' => $limit,
                'total' => 0,
                'totalPages' => 0
            ],
            'pagination_html' => ''
        ];
    }
}

/**
 * Ajoute un nouveau contrat pour une entreprise
 * 
 * @param int $company_id identifiant de l'entreprise
 * @param array $contract_data données du contrat à créer
 * @return int|false identifiant du nouveau contrat ou false en cas d'erreur
 */
function addCompanyContract($company_id, $contract_data)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $contract_data = sanitizeInput($contract_data);

    if (!$company_id || empty($contract_data)) {
        flashMessage("Données de contrat invalides", "danger");
        return false;
    }

    // Validation des données requises
    $requiredFields = ['date_debut', 'type_contrat'];
    foreach ($requiredFields as $field) {
        if (empty($contract_data[$field])) {
            flashMessage("Des champs obligatoires sont manquants: " . $field, "danger");
            return false;
        }
    }

    $allowedFields = [
        'date_debut',
        'date_fin',
        'montant_mensuel',
        'nombre_salaries',
        'type_contrat',
        'statut',
        'conditions_particulieres'
    ];

    $filteredData = array_intersect_key($contract_data, array_flip($allowedFields));
    $filteredData['entreprise_id'] = $company_id;
    if (!isset($filteredData['statut'])) {
        $filteredData['statut'] = 'actif';
    }
    if (isset($filteredData['date_fin']) && empty($filteredData['date_fin'])) {
        $filteredData['date_fin'] = null;
    }
    if (isset($filteredData['montant_mensuel']) && $filteredData['montant_mensuel'] === '') {
        $filteredData['montant_mensuel'] = null;
    }
    if (isset($filteredData['nombre_salaries']) && $filteredData['nombre_salaries'] === '') {
        $filteredData['nombre_salaries'] = null;
    }


    try {
        beginTransaction();

        $contractId = insertRow('contrats', $filteredData);

        if (!$contractId) {
            // Si l'insertion échoue, annuler la transaction
            rollbackTransaction();
            flashMessage("Impossible de créer le contrat", "danger");
            return false;
        }

        if (!empty($contract_data['services']) && is_array($contract_data['services'])) {
            foreach ($contract_data['services'] as $prestationId) { // Utiliser prestationId comme nom de variable
                $prestationId = filter_var($prestationId, FILTER_VALIDATE_INT);
                if ($prestationId) {

                    $linkData = [
                        'contrat_id' => $contractId,
                        'prestation_id' => $prestationId
                        // 'date_ajout' // Colonne inexistante
                    ];

                    // Insertion dans la table de liaison
                    if (!insertRow('contrats_prestations', $linkData)) {
                        // Si une association échoue, annuler la transaction
                        rollbackTransaction();
                        flashMessage("Impossible d'associer le service ID $prestationId au contrat", "danger");
                        return false;
                    }
                }
            }
        }

        commitTransaction();

        $contractReference = 'CT-' . str_pad($contractId, 6, '0', STR_PAD_LEFT);
        $userIdForLog = (isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) && $_SESSION['user_id'] > 0)
            ? $_SESSION['user_id']
            : null;
        logBusinessOperation($userIdForLog, 'add_contract', "Création contrat #$contractReference pour entreprise #$company_id");
        flashMessage("Le contrat $contractReference a été créé avec succès", "success");

        return $contractId;
    } catch (Exception $e) {
        rollbackTransaction();
        logSystemActivity('error', "Erreur création contrat: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la création du contrat", "danger");
        return false;
    }
}

/**
 * Récupère les détails complets d'un contrat pour une entreprise donnée,
 * incluant les informations de l'entreprise et les services associés.
 *
 * @param int $company_id Identifiant de l'entreprise qui consulte (pour la vérification d'accès)
 * @param int $contract_id Identifiant du contrat à récupérer
 * @return array|false Détails du contrat ou false si non trouvé/accès non autorisé
 */
function getCompanyContractDetails($company_id, $contract_id)
{
    // Validation des IDs
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $contract_id = filter_var(sanitizeInput($contract_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$contract_id) {
        return false;
    }

    // Récupération du contrat ET des informations de l'entreprise associée
    $pdo = getDbConnection();
    $query = "SELECT c.*,
                   e.nom AS entreprise_nom, 
                   e.siret AS entreprise_siret,
                   e.adresse AS entreprise_adresse,
                   e.code_postal AS entreprise_code_postal,
                   e.ville AS entreprise_ville
              FROM contrats c
              JOIN entreprises e ON c.entreprise_id = e.id
              WHERE c.id = :contract_id AND c.entreprise_id = :company_id";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':contract_id' => $contract_id, ':company_id' => $company_id]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contract) {
        return false; // Contrat non trouvé ou n'appartient pas à cette entreprise
    }

    // Générer la référence
    $contract['reference'] = 'CT-' . str_pad($contract['id'], 6, '0', STR_PAD_LEFT);

    // Récupération des services inclus dans le contrat via la table de liaison
    $query_services = "SELECT p.id, p.nom, p.description, p.prix, p.duree, p.type, p.categorie, p.niveau_difficulte, p.capacite_max
                       FROM prestations p
                       JOIN contrats_prestations cp ON p.id = cp.prestation_id
                       WHERE cp.contrat_id = :contract_id
                       ORDER BY p.nom";

    $stmt_services = $pdo->prepare($query_services);
    $stmt_services->execute([':contract_id' => $contract_id]);
    $services = $stmt_services->fetchAll(PDO::FETCH_ASSOC);

    // Formater les prix des services
    foreach ($services as &$service) {
        if (isset($service['prix'])) {
            $service['prix_formate'] = formatMoney($service['prix']);
        }
    }

    // Ajouter les services au contrat
    $contract['services'] = $services;

    return $contract;
}

/**
 * Récupère les factures liées à un contrat (via l'entreprise, car pas de lien direct)
 *
 * @param int $contract_id Identifiant du contrat (utilisé pour trouver l'entreprise)
 * @return array Liste des factures
 */
function getContractInvoices($contract_id)
{
    // Validation de l'ID contrat
    $contract_id = filter_var(sanitizeInput($contract_id), FILTER_VALIDATE_INT);
    if (!$contract_id) {
        return [];
    }

    // Trouver l'entreprise liée à ce contrat
    $query = "SELECT entreprise_id FROM contrats WHERE id = :id LIMIT 1";
    $params = [':id' => $contract_id];
    $contractInfo = executeQuery($query, $params)->fetch(); // Utiliser executeQuery directement

    if (!$contractInfo || !$contractInfo['entreprise_id']) {
        return []; // Contrat non trouvé ou non lié à une entreprise
    }
    $company_id = $contractInfo['entreprise_id'];

    // 2. Récupérer les factures de cette entreprise (on réutilise getCompanyInvoices)
    return getCompanyInvoices($company_id);
}

/**
 * Récupère l'historique d'un contrat
 *
 * @param int $contract_id Identifiant du contrat
 * @return array Liste des événements de l'historique
 */
function getContractHistory($contract_id)
{
    // Validation de l'ID
    $contract_id = filter_var(sanitizeInput($contract_id), FILTER_VALIDATE_INT);
    if (!$contract_id) {
        return [];
    }

    try {
        // Récupérer l'historique depuis la nouvelle table (exemple)
        $query = 'SELECT * FROM historique_contrats WHERE contrat_id = :cid ORDER BY date_evenement DESC';
        $params = [':cid' => $contract_id];

        $history = fetchAll($query, $params); // Utiliser fetchAll avec query et params
        // Ajouter le formatage nécessaire...
        return $history;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération historique contrat #$contract_id: " . $e->getMessage());
        return [];
    }
}

/**
 * Enregistre une demande de renouvellement d'un contrat et notifie les admins.
 *
 * @param int $contract_id Identifiant du contrat à renouveler.
 * @param int $entreprise_id Identifiant de l'entreprise qui demande.
 * @param int|null $personne_id Identifiant de la personne qui fait la demande
 * @param array $data Données supplémentaires (ex: commentaire).
 * @return array Résultat de l'opération.
 */
function requestContractRenewal($contract_id, $entreprise_id, $personne_id = null, $data = [])
{
    // Validation des IDs
    $contract_id = filter_var(sanitizeInput($contract_id), FILTER_VALIDATE_INT);
    $entreprise_id = filter_var(sanitizeInput($entreprise_id), FILTER_VALIDATE_INT);
    $personne_id = filter_var(sanitizeInput($personne_id), FILTER_VALIDATE_INT);
    $commentaire = isset($data['commentaire_client']) ? sanitizeInput($data['commentaire_client']) : null;

    if (!$contract_id || !$entreprise_id) {
        return [
            'success' => false,
            'message' => "Informations invalides pour la demande de renouvellement."
        ];
    }

    // Vérifier que le contrat existe et appartient à l'entreprise
    $contract = fetchOne('contrats', 'id = :cid AND entreprise_id = :eid', [':cid' => $contract_id, ':eid' => $entreprise_id]);
    if (!$contract) {
        return [
            'success' => false,
            'message' => "Contrat non trouvé ou n'appartient pas à votre entreprise."
        ];
    }

    // Vérifier si une demande n'est pas déjà en cours pour ce contrat
    $existingRequest = fetchOne('demandes_renouvellement', 'contrat_id = :cid AND statut = \'en_attente\'', [':cid' => $contract_id]);
    if ($existingRequest) {
        return [
            'success' => false,
            'message' => "Une demande de renouvellement est déjà en cours pour ce contrat."
        ];
    }

    try {
        // Préparer les données pour l'insertion
        $insertData = [
            'contrat_id' => $contract_id,
            'entreprise_id' => $entreprise_id,
            'personne_id' => $personne_id, // Peut être null
            'statut' => 'en_attente',
            'commentaire_client' => $commentaire
        ];

        // Insérer la demande
        $demandeId = insertRow('demandes_renouvellement', $insertData);

        if ($demandeId) {
            // Notification aux administrateurs
            notifyAdminsNewRenewalRequest($demandeId, $contract_id, $entreprise_id, $personne_id, $commentaire);

            // Log
            $userIdForLog = $personne_id ?? null;
            logBusinessOperation($userIdForLog, 'request_contract_renewal', "Demande de renouvellement #$demandeId pour contrat #$contract_id (Entreprise #$entreprise_id)");

            return [
                'success' => true,
                'message' => "Votre demande de renouvellement (N° $demandeId) a été envoyée avec succès."
            ];
        } else {
            return [
                'success' => false,
                'message' => "Erreur lors de l'enregistrement de votre demande."
            ];
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur demande renouvellement contrat #$contract_id: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "Une erreur technique est survenue lors de l'envoi de votre demande."
        ];
    }
}

/**
 * Notifie les administrateurs d'une nouvelle demande de renouvellement.
 *
 * @param int $demandeId ID de la demande créée
 * @param int $contractId ID du contrat concerné
 * @param int $entrepriseId ID de l'entreprise
 * @param int|null $personneId ID de la personne ayant fait la demande
 * @param string|null $commentaire Commentaire éventuel du client
 * @return void
 */
function notifyAdminsNewRenewalRequest($demandeId, $contractId, $entrepriseId, $personneId = null, $commentaire = null)
{
    try {
        $adminRoleId = ROLE_ADMIN;
        $admins = fetchAll('personnes', 'role_id = :role_id', '', 0, 0, [':role_id' => $adminRoleId]);


        // Récupérer le nom de l'entreprise et la référence du contrat pour la notif
        $entreprise = fetchOne('entreprises', 'id = :id', [':id' => $entrepriseId]);
        $nomEntreprise = $entreprise ? $entreprise['nom'] : "Entreprise #$entrepriseId";
        $refContrat = 'CT-' . str_pad($contractId, 6, '0', STR_PAD_LEFT);

        $titre = "Demande de renouvellement contrat (#$refContrat)";
        $message = "Une demande de renouvellement (ID: $demandeId) pour le contrat $refContrat a été reçue de l'entreprise $nomEntreprise.";
        if ($personneId) {
            $personne = fetchOne('personnes', 'id = :id', [':id' => $personneId]);
            if ($personne) $message .= "\nDemandeur: {$personne['prenom']} {$personne['nom']} (ID: $personneId).";
        }
        if ($commentaire) {
            $message .= "\nCommentaire client: " . $commentaire;
        }
        $lien = '/admin/renewals/view/' . $demandeId; // Adapter le lien admin

        foreach ($admins as $admin) {
            insertRow('notifications', [
                'personne_id' => $admin['id'],
                'titre' => $titre,
                'message' => $message,
                'type' => 'info',
                'lien' => $lien
            ]);
        }
        logSystemActivity('info', "Notification envoyée aux admins pour demande renouvellement #$demandeId");
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur notification admin pour demande renouvellement #$demandeId: " . $e->getMessage());
    }
}

/**
 * Traite une demande de devis d'une entreprise
 *
 * @param array $data Données de la demande (incluant entreprise_id, service_souhaite, description_besoin, etc.)
 * @return array Résultat de l'opération (success, message)
 */
function requestCompanyQuote($data)
{
    // Validation des données de base
    $errors = [];
    if (empty($data['entreprise_id'])) {
        $errors[] = "L'identifiant de l'entreprise est manquant.";
    } else {
        $company_id = filter_var(sanitizeInput($data['entreprise_id']), FILTER_VALIDATE_INT);
        if (!$company_id) {
            $errors[] = "L'identifiant de l'entreprise est invalide.";
        } else {
            // Vérifier si l'entreprise existe réellement
            $companyExists = fetchOne('entreprises', 'id = :id', [':id' => $company_id]);
            if (!$companyExists) {
                $errors[] = "L'entreprise spécifiée n'existe pas.";
            }
        }
    }

    if (empty($data['description_besoin'])) {
        $errors[] = "La description de votre besoin est requise.";
    }

    if (empty($data['service_souhaite'])) {
        $errors[] = "Veuillez indiquer le service ou type de contrat souhaité.";
    }

    // Validation optionnelle du téléphone
    if (!empty($data['contact_telephone'])) {
        $phonePattern = '/^(0|\+33)[1-9]([-. ]?[0-9]{2}){4}$/';
        if (!preg_match($phonePattern, trim($data['contact_telephone']))) {
            $errors[] = "Le format du numéro de téléphone est invalide (attendu : 0XXXXXXXXX ou +33XXXXXXXXX).";
        }
    }

    // Si des erreurs sont trouvées, retourner un échec avec les messages
    if (!empty($errors)) {
        return [
            'success' => false,
            'message' => implode("<br>", $errors) // Concatène les erreurs pour l'affichage
        ];
    }

    // Sécurité: Valider le token CSRF si vous l'utilisez
    if (!isset($data['csrf_token']) || !validateToken($data['csrf_token'])) {
        return [
            'success' => false,
            'message' => "Erreur de sécurité. Veuillez réessayer."
        ];
    }

    try {
        // Préparer les données pour l'insertion dans la table 'devis'
        $devisData = [
            'entreprise_id' => $data['entreprise_id'],
            'date_creation' => date('Y-m-d'),
            'montant_total' => 0, // Montant à déterminer par l'admin
            'montant_ht' => 0,
            'tva' => 0, // Ou la TVA par défaut
            'statut' => 'en_attente' // Le devis est en attente de traitement
            // 'conditions_paiement' et 'delai_paiement' pourraient être ajoutés si souhaité
        ];

        // !! TODO: Ajouter ces colonnes à la table 'devis' si le stockage est nécessaire ?


        // Insérer le devis dans la base de données
        $devisId = insertRow('devis', $devisData);

        if (!$devisId) {
            return [
                'success' => false,
                'message' => "Erreur lors de l'enregistrement de votre demande de devis."
            ];
        }

        // Optionnel : Envoyer une notification aux administrateurs
        // Inclure les détails de la demande dans le message car ils ne sont pas en BDD
        $details_demande = "Service/Contrat: " . ($data['service_souhaite'] ?? 'N/A') . "\n";
        $details_demande .= "Nb Salariés: " . ($data['nombre_salaries'] ?? 'N/A') . "\n";
        $details_demande .= "Description: " . ($data['description_besoin'] ?? 'N/A') . "\n";
        $details_demande .= "Contact: " . ($data['contact_personne'] ?? 'N/A') . " (" . ($data['contact_email'] ?? 'N/A') . " / " . ($data['contact_telephone'] ?? 'N/A') . ")";

        notifyAdminsNewQuoteRequest($devisId, $data['entreprise_id'], $details_demande);

        // Journaliser l'activité
        // Vérification plus stricte de l'ID utilisateur pour le log
        $userIdForLog = (isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) && $_SESSION['user_id'] > 0)
            ? $_SESSION['user_id']
            : null;
        logBusinessOperation($userIdForLog, 'demande_devis', "Demande de devis #$devisId pour entreprise #{$data['entreprise_id']}");

        return [
            'success' => true,
            'message' => "Votre demande de devis a bien été envoyée (N° $devisId). Nous reviendrons vers vous rapidement."
        ];
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur demande devis: " . $e->getMessage());
        // Pour le débogage : echo $e->getMessage();
        return [
            'success' => false,
            'message' => "Une erreur technique est survenue lors de l'envoi de votre demande."
        ];
    }
}

/**
 * Notifie les administrateurs d'une nouvelle demande de devis
 * (Fonction placeholder à implémenter correctement) 
 *
 * @param int $devisId ID du devis créé
 * @param int $entrepriseId ID de l'entreprise qui a fait la demande
 * @param string $detailsMessage Détails de la demande
 * @return void
 */
function notifyAdminsNewQuoteRequest($devisId, $entrepriseId, $detailsMessage = '')
{
    // Logique pour trouver les admins et insérer une notification pour chacun
    try {
        $adminRoleId = ROLE_ADMIN;
        $admins = fetchAll('personnes', 'role_id = :role_id', '', 0, 0, [':role_id' => $adminRoleId]);
        $titre = "Nouvelle demande de devis (#$devisId)";
        // Utiliser les détails dans le message
        $message = "Demande reçue de l'entreprise ID #$entrepriseId.\n" . $detailsMessage;
        $lien = '/admin/quotes/view/' . $devisId; // Mettre le lien correct vers l'admin

        foreach ($admins as $admin) {
            insertRow('notifications', [
                'personne_id' => $admin['id'],
                'titre' => $titre,
                'message' => $message,
                'type' => 'info',
                'lien' => $lien
                // created_at est géré par la DB
            ]);
        }
        logSystemActivity('info', "Notification envoyée aux admins pour devis #$devisId");
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur notification admin pour devis #$devisId: " . $e->getMessage());
    }
}

/**
 * Récupère les détails d'une facture spécifique pour une entreprise donnée.
 * Vérifie que la facture appartient bien à l'entreprise.
 *
 * @param int $company_id ID de l'entreprise connectée.
 * @param int $invoice_id ID de la facture demandée.
 * @return array|false Détails de la facture ou false si non trouvée ou accès refusé.
 */
function getInvoiceDetailsForCompany($company_id, $invoice_id)
{
    // Validation des IDs
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $invoice_id = filter_var(sanitizeInput($invoice_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$invoice_id) {
        flashMessage("Identifiants invalides.", "danger");
        return false;
    }

    try {
        // Requête pour récupérer la facture et les informations de l'entreprise associée
        $query = "SELECT f.*, 
                       e.nom AS entreprise_nom, e.siret AS entreprise_siret, 
                       e.adresse AS entreprise_adresse, e.code_postal AS entreprise_code_postal, e.ville AS entreprise_ville
                FROM factures f
                JOIN entreprises e ON f.entreprise_id = e.id
                WHERE f.id = :invoice_id AND f.entreprise_id = :company_id";

        $invoice = executeQuery($query, [
            ':invoice_id' => $invoice_id,
            ':company_id' => $company_id
        ])->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            // Soit la facture n'existe pas, soit elle n'appartient pas à cette entreprise
            flashMessage("Facture non trouvée ou accès non autorisé.", "warning");
            return false;
        }

        // Formater les données pour l'affichage
        $invoice['numero_facture_complet'] = $invoice['numero_facture'] ?? ('INV-' . str_pad($invoice['id'], 6, '0', STR_PAD_LEFT));
        if (isset($invoice['date_emission'])) {
            $invoice['date_emission_formatee'] = formatDate($invoice['date_emission'], 'd/m/Y');
        }
        if (isset($invoice['date_echeance'])) {
            $invoice['date_echeance_formatee'] = formatDate($invoice['date_echeance'], 'd/m/Y');
        }
        if (isset($invoice['montant_ht'])) {
            $invoice['montant_ht_formate'] = formatMoney($invoice['montant_ht']);
        }
        if (isset($invoice['montant_total'])) {
            $invoice['montant_total_formate'] = formatMoney($invoice['montant_total']);
        }
        if (isset($invoice['statut'])) {
            $invoice['statut_badge'] = getStatusBadge($invoice['statut']);
        }
        if (isset($invoice['tva'])) {
            $invoice['tva_formatee'] = $invoice['tva'] . '%'; // Simple formatage du pourcentage
        }

        // TODO: Si la structure le permettait, on récupérerait les lignes de la facture ici.
        // $invoice['lignes'] = getInvoiceLines($invoice_id);
        $invoice['lignes'] = []; // Placeholder

        return $invoice;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération détail facture #$invoice_id pour entreprise #$company_id: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération des détails de la facture.", "danger");
        return false;
    }
}

/**
 * Récupère les détails d'un employé spécifique d'une entreprise.
 * Vérifie que l'employé appartient bien à l'entreprise et a le rôle salarié.
 *
 * @param int $company_id ID de l'entreprise.
 * @param int $employee_id ID de l'employé.
 * @return array|false Détails de l'employé ou false si non trouvé ou accès refusé.
 */
function getCompanyEmployeeDetails($company_id, $employee_id)
{
    // Validation des IDs
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$employee_id) {
        flashMessage("Identifiants invalides.", "danger");
        return false;
    }

    try {
        // Requête pour récupérer l'employé
        $query = "SELECT p.* 
                  FROM personnes p
                  WHERE p.id = :employee_id 
                    AND p.entreprise_id = :company_id 
                    AND p.role_id = :role_id";

        $employee = executeQuery($query, [
            ':employee_id' => $employee_id,
            ':company_id' => $company_id,
            ':role_id' => ROLE_SALARIE
        ])->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            // Soit l'employé n'existe pas, soit il n'appartient pas à cette entreprise/rôle
            flashMessage("Employé non trouvé ou accès non autorisé.", "warning");
            //redirectTo('employees.php'); // Redirection supprimée ici
            return false; // Retourne false au lieu de rediriger
        }

        // Formater les données pour l'affichage
        if (isset($employee['date_naissance']) && $employee['date_naissance']) {
            $employee['date_naissance_formatee'] = formatDate($employee['date_naissance'], 'd/m/Y');
        }
        if (isset($employee['derniere_connexion']) && $employee['derniere_connexion']) {
            $employee['derniere_connexion_formatee'] = formatDate($employee['derniere_connexion'], 'd/m/Y H:i');
        } else {
            $employee['derniere_connexion_formatee'] = 'Jamais';
        }
        if (isset($employee['statut'])) {
            $employee['statut_badge'] = getStatusBadge($employee['statut']);
        }
        // Formater le genre
        switch ($employee['genre']) {
            case 'M':
                $employee['genre_formate'] = 'Masculin';
                break;
            case 'F':
                $employee['genre_formate'] = 'Féminin';
                break;
            case 'Autre':
                $employee['genre_formate'] = 'Autre';
                break;
            default:
                $employee['genre_formate'] = 'Non spécifié';
        }


        return $employee;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération détail employé #$employee_id pour entreprise #$company_id: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération des détails de l'employé.", "danger");
        return false;
    }
}

/**
 * Met à jour les informations d'un employé spécifique d'une entreprise.
 *
 * @param int $company_id ID de l'entreprise.
 * @param int $employee_id ID de l'employé à mettre à jour.
 * @param array $employee_data Données à mettre à jour.
 * @return bool True si la mise à jour réussit, False sinon.
 */
function updateCompanyEmployee($company_id, $employee_id, $employee_data)
{
    // Validation des IDs
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$employee_id || empty($employee_data)) {
        flashMessage("Données invalides pour la mise à jour.", "danger");
        return false;
    }

    // Liste des champs modifiables par l'entreprise
    $allowedFields = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'date_naissance',
        'genre',
        'statut'
        // 'photo_url' // Pourrait être ajouté
    ];

    // Nettoyer et filtrer les données soumises
    $employee_data = sanitizeInput($employee_data);
    $filteredData = array_intersect_key($employee_data, array_flip($allowedFields));

    // Assurer que les champs vides sont null si applicable
    if (isset($filteredData['telephone']) && $filteredData['telephone'] === '') {
        $filteredData['telephone'] = null;
    }
    if (isset($filteredData['date_naissance']) && $filteredData['date_naissance'] === '') {
        $filteredData['date_naissance'] = null;
    }
    if (isset($filteredData['genre']) && $filteredData['genre'] === '') {
        $filteredData['genre'] = null;
    }

    if (empty($filteredData)) {
        flashMessage("Aucune donnée valide à mettre à jour.", "warning");
        return false;
    }

    try {
        // Vérifier si l'email est modifié et s'il est unique (sauf pour l'employé actuel)
        if (isset($filteredData['email'])) {
            $existingUser = fetchOne('personnes', "email = :email AND id != :id", [':email' => $filteredData['email'], ':id' => $employee_id]);
            if ($existingUser) {
                flashMessage("Cette adresse email est déjà utilisée par un autre utilisateur.", "danger");
                return false;
            }
        }

        // Condition WHERE pour s'assurer qu'on met à jour le bon employé DANS la bonne entreprise
        $whereCondition = 'id = :id AND entreprise_id = :entreprise_id AND role_id = :role_id';
        $whereParams = [
            'id' => $employee_id,
            'entreprise_id' => $company_id,
            'role_id' => ROLE_SALARIE
        ];

        // Utilisation de la fonction updateRow
        $affectedRows = updateRow('personnes', $filteredData, $whereCondition, $whereParams);

        if ($affectedRows === false) {
            // Erreur lors de l'exécution de la requête (déjà loguée par updateRow probablement)
            flashMessage("Erreur lors de la mise à jour de l'employé.", "danger");
            return false;
        } elseif ($affectedRows > 0) {
            // Vérification plus stricte de l'ID utilisateur pour le log
            $userIdForLog = (isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) && $_SESSION['user_id'] > 0)
                ? $_SESSION['user_id']
                : null;
            logBusinessOperation(
                $userIdForLog,
                'update_employee',
                "Mise à jour employé #{$employee_id} par entreprise #{$company_id}"
            );
            flashMessage("Les informations de l'employé ont été mises à jour avec succès.", "success");
            return true;
        } else {
            // Aucune ligne affectée - soit les données étaient identiques, soit l'employé n'existait pas/pas dans la bonne entreprise
            // On vérifie si l'employé existe toujours pour distinguer les cas
            $existsCheck = fetchOne('personnes', 'id = :id AND entreprise_id = :entreprise_id AND role_id = :role_id', $whereParams);
            if ($existsCheck) {
                flashMessage("Aucune modification n'a été détectée.", "info");
                return true; // Considéré comme un succès car aucune erreur
            } else {
                flashMessage("Employé non trouvé ou accès non autorisé.", "danger");
                return false;
            }
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur mise à jour employé #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de la mise à jour.", "danger");
        return false;
    }
}

/**
 * Supprime un employé spécifique d'une entreprise.
 *
 * @param int $company_id ID de l'entreprise.
 * @param int $employee_id ID de l'employé à supprimer.
 * @return bool True si la suppression réussit, False sinon.
 */
function deleteCompanyEmployee($company_id, $employee_id)
{
    // Validation des IDs
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$employee_id) {
        flashMessage("Identifiants invalides pour la suppression.", "danger");
        return false;
    }

    try {
        // Condition WHERE pour s'assurer qu'on supprime le bon employé DANS la bonne entreprise et avec le bon rôle
        $whereCondition = 'id = :id AND entreprise_id = :entreprise_id AND role_id = :role_id';
        $whereParams = [
            'id' => $employee_id,
            'entreprise_id' => $company_id,
            'role_id' => ROLE_SALARIE
        ];

        // Correction: Vérifier si l'employé existe en utilisant executeQuery directement
        $checkSql = "SELECT 1 FROM personnes WHERE " . $whereCondition . " LIMIT 1";
        $stmtCheck = executeQuery($checkSql, $whereParams);
        $employeeExists = $stmtCheck->fetch(); // fetch retourne false si aucune ligne
        // $employeeExists = fetchOne('personnes', $whereCondition, $whereParams); // Ancien appel incorrect

        if (!$employeeExists) {
            flashMessage("Employé non trouvé ou accès non autorisé pour la suppression.", "danger");
            return false;
        }

        // Récupérer les infos de l'employé AVANT suppression pour le log
        // (Optionnel, mais utile pour le message flash/log)
        // Si fetch() a réussi, on peut supposer que l'ID est valide pour une récupération simple
        // Ou on pourrait récupérer plus d'infos dans la requête de check ci-dessus
        $employeeInfoQuery = "SELECT nom, prenom FROM personnes WHERE id = :id LIMIT 1";
        $employeeInfoStmt = executeQuery($employeeInfoQuery, ['id' => $employee_id]);
        $employeeInfo = $employeeInfoStmt->fetch();
        $employeeFullName = $employeeInfo ? ($employeeInfo['prenom'] . ' ' . $employeeInfo['nom']) : "#{$employee_id}";

        // Modification: Utiliser updateRow pour passer le statut à 'supprime' (Soft Delete)
        $updateData = ['statut' => 'supprime'];
        $affectedRows = updateRow('personnes', $updateData, $whereCondition, $whereParams);
        // $affectedRows = deleteRow('personnes', $whereCondition, $whereParams); // Ancien appel à deleteRow

        if ($affectedRows === false) {
            // Erreur lors de l'exécution (déjà loguée par updateRow probablement)
            flashMessage("Erreur lors de la désactivation de l'employé.", "danger");
            return false;
        } elseif ($affectedRows > 0) {
            // Vérification plus stricte de l'ID utilisateur pour le log
            $userIdForLog = (isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) && $_SESSION['user_id'] > 0)
                ? $_SESSION['user_id']
                : null;
            logBusinessOperation(
                $userIdForLog,
                'soft_delete_employee', // Action modifiée pour refléter la suppression logique
                "Désactivation employé {$employeeFullName} (ID: {$employee_id}) par entreprise #{$company_id}"
            );
            // Message modifié
            flashMessage("L'employé '{$employeeFullName}' a été désactivé avec succès.", "success");
            return true;
        } else {
            // Aucune ligne affectée
            flashMessage("La désactivation n'a pas pu être effectuée (employé déjà désactivé ou non trouvé ?).", "warning");
            return false;
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur suppression employé #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de la suppression.", "danger");
        return false;
    }
}

/**
 * Réactive un employé spécifique d'une entreprise (annule la suppression logique).
 *
 * @param int $company_id ID de l'entreprise.
 * @param int $employee_id ID de l'employé à réactiver.
 * @return bool True si la réactivation réussit, False sinon.
 */
function reactivateCompanyEmployee($company_id, $employee_id)
{
    // Validation des IDs
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$employee_id) {
        flashMessage("Identifiants invalides pour la réactivation.", "danger");
        return false;
    }

    try {
        // Condition WHERE pour s'assurer qu'on réactive le bon employé DANS la bonne entreprise et qu'il est bien supprimé
        $whereCondition = 'id = :id AND entreprise_id = :entreprise_id AND role_id = :role_id AND statut = :statut_supprime';
        $whereParams = [
            'id' => $employee_id,
            'entreprise_id' => $company_id,
            'role_id' => ROLE_SALARIE,
            'statut_supprime' => 'supprime' // Vérifier qu'il est bien supprimé avant de réactiver
        ];

        // Vérifier si l'employé existe et correspond aux critères
        $employeeCheck = fetchOne('personnes', $whereCondition, $whereParams);
        if (!$employeeCheck) {
            flashMessage("Employé non trouvé, non supprimé, ou accès non autorisé pour la réactivation.", "warning");
            return false;
        }

        // Données à mettre à jour : passer le statut à 'actif'
        $updateData = ['statut' => 'actif'];

        // Condition WHERE pour la mise à jour (plus simple car on a déjà vérifié)
        $updateWhereCondition = 'id = :id AND entreprise_id = :entreprise_id';
        $updateWhereParams = [
            'id' => $employee_id,
            'entreprise_id' => $company_id
        ];

        $affectedRows = updateRow('personnes', $updateData, $updateWhereCondition, $updateWhereParams);

        if ($affectedRows === false) {
            flashMessage("Erreur lors de la réactivation de l'employé.", "danger");
            return false;
        } elseif ($affectedRows > 0) {
            $userIdForLog = (isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) && $_SESSION['user_id'] > 0)
                ? $_SESSION['user_id']
                : null;
            logBusinessOperation(
                $userIdForLog,
                'reactivate_employee',
                "Réactivation employé #{$employee_id} par entreprise #{$company_id}"
            );
            flashMessage("L'employé '{$employeeCheck['prenom']} {$employeeCheck['nom']}' a été réactivé avec succès.", "success");
            return true;
        } else {
            flashMessage("La réactivation n'a pas pu être effectuée (employé déjà actif ?).", "warning");
            return false;
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur réactivation employé #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de la réactivation.", "danger");
        return false;
    }
}

function addCompanyEmployee($company_id, $employee_data)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $employee_data = sanitizeInput($employee_data);

    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide.", "danger");
        return false;
    }
    if (empty($employee_data['nom']) || empty($employee_data['prenom']) || empty($employee_data['email'])) {
        flashMessage("Le nom, le prénom et l'email de l'employé sont obligatoires.", "danger");
        return false;
    }
    if (!filter_var($employee_data['email'], FILTER_VALIDATE_EMAIL)) {
        flashMessage("L'adresse email fournie n'est pas valide.", "danger");
        return false;
    }

    // Validation du format téléphone
    if (!empty($employee_data['telephone']) && !preg_match('/^(0|\+33)[1-9]([-. ]?[0-9]{2}){4}$/', $employee_data['telephone'])) {
        flashMessage("Le format du numéro de téléphone est invalide.", "danger");
        return false;
    }

    // Validation du genre (doit être F ou M si fourni)
    if (isset($employee_data['genre']) && $employee_data['genre'] !== '' && !in_array($employee_data['genre'], ['F', 'M'])) {
        flashMessage("La valeur pour le genre doit être 'F' ou 'M'.", "danger");
        return false;
    }

    try {
        $company = fetchOne('entreprises', "id = :id", [':id' => $company_id]);
        if (!$company) {
            flashMessage("Entreprise non trouvée.", "danger");
            return false;
        }

        // 3. Vérification que l'email n'est pas déjà utilisé
        $existingUser = fetchOne('personnes', "email = :email", [':email' => $employee_data['email']]);
        if ($existingUser) {
            // Message plus direct
            flashMessage("Erreur : L'adresse email '" . htmlspecialchars($employee_data['email']) . "' est déjà utilisée par un autre compte.", "danger");
            return false;
        }

        $plainPassword = null;
        if (empty($employee_data['mot_de_passe'])) {
            $plainPassword = bin2hex(random_bytes(6)); // Génère un mot de passe de 12 caractères hex
            $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        } else {
            $passwordHash = password_hash($employee_data['mot_de_passe'], PASSWORD_DEFAULT);
        }
        if (!$passwordHash) {
            throw new Exception("Erreur lors du hachage du mot de passe.");
        }

        $insertData = [
            'nom' => $employee_data['nom'],
            'prenom' => $employee_data['prenom'],
            'email' => $employee_data['email'],
            'mot_de_passe' => $passwordHash,
            'telephone' => $employee_data['telephone'] ?? null,
            'date_naissance' => !empty($employee_data['date_naissance']) ? $employee_data['date_naissance'] : null,
            'genre' => $employee_data['genre'] ?? null,
            'photo_url' => $employee_data['photo_url'] ?? null,
            'entreprise_id' => $company_id,
            'role_id' => ROLE_SALARIE, // Assigner le rôle Salarié
            'statut' => $employee_data['statut'] ?? 'actif' // Statut actif par défaut

        ];

        // 6. Insertion de l'employé via la fonction insertRow de db.php
        $employeeId = insertRow('personnes', $insertData);

        if ($employeeId) {
            // 7. Succès : Journalisation et message flash (incluant le mot de passe si généré)
            $userIdForLog = (isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT)) ? $_SESSION['user_id'] : null;
            logBusinessOperation(
                $userIdForLog,
                'add_employee',
                "Ajout employé #{$employeeId} ({$insertData['prenom']} {$insertData['nom']}) à l'entreprise #{$company_id}"
            );

            // Message de succès standardisé pour la production
            $successMessage = "L'employé {$insertData['prenom']} {$insertData['nom']} a été ajouté avec succès.";
            if ($plainPassword) {
                // En DEV, on peut laisser le message avec le mot de passe (à commenter en PROD)
                //$successMessage .= " Mot de passe temporaire : <strong>" . htmlspecialchars($plainPassword) . "</strong> (à communiquer à l'employé)";
                //logSystemActivity('info', "Mot de passe temporaire généré pour employé #$employeeId: $plainPassword"); // Garder le log

                // Message pour la production (supposer l'envoi par email ou autre moyen)
                $successMessage .= " Un email de bienvenue avec un mot de passe temporaire devrait lui être envoyé.";
                logSystemActivity('info', "Mot de passe temporaire généré pour employé #$employeeId: $plainPassword"); // Garder le log
            }

            flashMessage($successMessage, "success");

            // TODO: Implémenter l'envoi d'email de bienvenue avec identifiants si nécessaire.

            return $employeeId;
        } else {
            // 8. Échec de l'insertion
            flashMessage("Erreur interne : Impossible d'ajouter l'employé dans la base de données. Veuillez contacter le support.", "danger");
            return false;
        }
    } catch (Exception $e) {
        // 9. Gestion des erreurs générales (connexion DB, hachage, etc.)
        logSystemActivity('error', "Erreur technique dans addCompanyEmployee pour entreprise #$company_id: " . $e->getMessage());
        // Message générique pour l'utilisateur
        flashMessage("Une erreur technique imprévue est survenue lors de l'ajout de l'employé. Veuillez réessayer plus tard ou contacter le support.", "danger");
        return false;
    }
}

/**
 * Récupère les statistiques d'utilisation d'une entreprise
 * 
 * @param int $company_id Identifiant de l'entreprise
 * @return array|false Statistiques d'utilisation ou false en cas d'erreur
 */
function getCompanyUsageStatistics($company_id)
{
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id) {
        return false;
    }

    try {
        // Requête pour récupérer les statistiques d'utilisation
        $query = "SELECT COUNT(r.id) as total_rdv,
                     SUM(CASE WHEN r.statut = 'termine' THEN 1 ELSE 0 END) as rdv_termines,
                     SUM(CASE WHEN r.date_rdv >= CURDATE() THEN 1 ELSE 0 END) as rdv_a_venir,
                     COUNT(DISTINCT p.id) as employes_actifs -- Renommé
                     FROM rendez_vous r
                     JOIN personnes p ON r.personne_id = p.id
                     WHERE p.entreprise_id = :company_id AND p.role_id = :role_id";

        $stats = executeQuery($query, [
            ':company_id' => $company_id,
            ':role_id' => ROLE_SALARIE
        ])->fetch();

        if (!$stats) {
            flashMessage("Erreur lors de la récupération des statistiques d'utilisation.", "danger");
            return false;
        }

        return $stats;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération statistiques entreprise #$company_id: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération des statistiques d'utilisation.", "danger");
        return false;
    }
}

/**
 * Récupère les employés d'une entreprise avec pagination et filtres.
 *
 * @param int $company_id Identifiant de l'entreprise.
 * @param int $page Numéro de la page actuelle.
 * @param int $limit Nombre d'éléments par page.
 * @param string $search Terme de recherche (sur nom, prénom, email).
 * @param string $statusFilter Filtre par statut ('actif', 'inactif', 'suspendu', 'tous').
 * @return array Contenant ['employees', 'pagination', 'pagination_html']
 */
function getCompanyEmployees($company_id, $page = 1, $limit = 5, $search = '', $statusFilter = 'actif')
{
    // 1. Validation et Nettoyage des paramètres
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $page = max(1, (int)sanitizeInput($page));
    $limit = max(1, (int)sanitizeInput($limit));
    $search = (string) sanitizeInput(trim($search));
    $statusFilter = (string) sanitizeInput($statusFilter);

    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide.", "danger");
        return [
            'employees' => [],
            'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
            'pagination_html' => ''
        ];
    }

    // 2. Construction de la base de la requête et des paramètres
    $sqlBase = " FROM personnes p WHERE p.entreprise_id = :company_id AND p.role_id = :role_id";
    $params = [
        ':company_id' => $company_id,
        ':role_id' => ROLE_SALARIE
    ];
    // Copie des paramètres pour la requête de comptage (avant ajout de limit/offset)
    $countParams = $params;

    // 3. Ajout du filtre de statut
    $validStatusFilters = ['actif', 'inactif', 'suspendu', 'tous'];
    if (in_array($statusFilter, $validStatusFilters) && $statusFilter !== 'tous') {
        $sqlBase .= " AND p.statut = :status";
        $params[':status'] = $statusFilter;
        $countParams[':status'] = $statusFilter;
    }

    // 4. Ajout du filtre de recherche
    if (!empty($search)) {
        $searchWild = '%' . $search . '%';
        $sqlBase .= " AND (p.nom LIKE :search OR p.prenom LIKE :search OR p.email LIKE :search)";
        $params[':search'] = $searchWild;
        $countParams[':search'] = $searchWild;
    }

    try {
        // 5. Compter le nombre total d'enregistrements correspondants
        $countQuery = "SELECT COUNT(*) as total" . $sqlBase;
        $totalResult = executeQuery($countQuery, $countParams)->fetch();
        $total = $totalResult['total'] ?? 0;
        $totalPages = ceil($total / $limit);

        // Ajuster le numéro de page si nécessaire (si page > totalPages)
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $limit;

        // 6. Récupérer les employés pour la page actuelle
        $query = "SELECT p.* " . $sqlBase . " ORDER BY p.nom ASC, p.prenom ASC LIMIT :limit OFFSET :offset";

        // Ajouter les paramètres de pagination aux paramètres de la requête principale
        $queryParams = $params;
        $queryParams[':limit'] = $limit; // PDO liera correctement les entiers
        $queryParams[':offset'] = $offset;

        $employees = executeQuery($query, $queryParams)->fetchAll(PDO::FETCH_ASSOC);

        // 7. Formatage des données pour l'affichage
        foreach ($employees as &$employee) {
            if (!isset($employee['derniere_connexion_formatee'])) {
                $employee['derniere_connexion_formatee'] = isset($employee['derniere_connexion']) ? formatDate($employee['derniere_connexion'], 'd/m/Y H:i') : 'Jamais';
            }
            if (!isset($employee['statut_badge'])) {
                $employee['statut_badge'] = isset($employee['statut']) ? getStatusBadge($employee['statut']) : 'N/A';
            }
        }
        unset($employee); // Détacher la référence

        // 8. Préparation des données pour la pagination HTML
        $paginationData = [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $total,
            'perPage' => $limit
        ];

        // Construction de l'URL de base pour les liens de pagination
        $urlParams = [];
        if (in_array($statusFilter, $validStatusFilters)) {
            $urlParams['statut'] = $statusFilter;
        }
        if (!empty($search)) {
            $urlParams['search'] = $search;
        }
        $urlPattern = "employees.php?page={page}";
        if (!empty($urlParams)) {
            $urlPattern .= "&" . http_build_query($urlParams);
        }

        // 9. Retourner les résultats
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
    } catch (PDOException $e) {
        // Gestion spécifique des erreurs PDO
        logSystemActivity('error', "PDO Error in getCompanyEmployees: " . $e->getMessage() . " | Query: " . ($query ?? 'N/A') . " | Params: " . print_r($queryParams ?? $params, true));
        flashMessage("Une erreur de base de données est survenue lors de la récupération des employés.", "danger");
    } catch (Exception $e) {
        // Gestion des autres erreurs
        logSystemActivity('error', "General Error in getCompanyEmployees: " . $e->getMessage());
        flashMessage("Une erreur générale est survenue lors de la récupération des employés.", "danger");
    }

    // Retourner une structure vide en cas d'erreur
    return [
        'employees' => [],
        'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
        'pagination_html' => ''
    ];
}

/**
 * Récupère les contrats d'une entreprise avec pagination.
 *
 * @param int $company_id Identifiant de l'entreprise.
 * @param string|null $status (Paramètre ignoré pour le moment, mais pourrait être utilisé pour filtrer par statut).
 * @param int $page Numéro de la page actuelle.
 * @param int $limit Nombre d'éléments par page.
 * @return array Contenant ['contracts', 'pagination', 'pagination_html']
 */
function getCompanyContracts($company_id, $status = null, $page = 1, $limit = 10)
{
    // 1. Validation et Nettoyage des paramètres
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $page = max(1, (int)sanitizeInput($page));
    $limit = max(1, (int)sanitizeInput($limit));
    // $status = $status ? sanitizeInput($status) : null; // Pour usage futur

    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide.", "danger");
        return [
            'contracts' => [],
            'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
            'pagination_html' => ''
        ];
    }

    // 2. Construction de la base de la requête et des paramètres
    $where = "c.entreprise_id = :company_id";
    $params = [':company_id' => $company_id];
    $countParams = [':company_id' => $company_id];

    // TODO: Ajouter ici la logique de filtrage par statut si nécessaire, en modifiant $where et $params/$countParams
    // if ($status && in_array($status, ['actif', 'expire', 'resilie', 'en_attente'])) {
    //     $where .= " AND c.statut = :status";
    //     $params[':status'] = $status;
    //     $countParams[':status'] = $status;
    // }

    try {
        // 3. Compter le nombre total de contrats
        $countQuery = "SELECT COUNT(DISTINCT c.id) as total FROM contrats c WHERE " . $where;
        $totalResult = executeQuery($countQuery, $countParams)->fetch();
        $total = $totalResult['total'] ?? 0;
        $totalPages = ceil($total / $limit);

        // Ajuster le numéro de page
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $limit;

        // 4. Récupérer les contrats pour la page actuelle
        $query = "SELECT c.* 
                  FROM contrats c 
                  WHERE " . $where . "
                  ORDER BY c.date_debut DESC 
                  LIMIT :limit OFFSET :offset";

        // Ajouter les paramètres de pagination
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $contracts = executeQuery($query, $params)->fetchAll(PDO::FETCH_ASSOC);

        // 5. Formatage des contrats
        foreach ($contracts as &$contract) {
            $contract['reference'] = 'CT-' . str_pad($contract['id'], 6, '0', STR_PAD_LEFT);
            $contract['date_debut_formatee'] = isset($contract['date_debut']) ? formatDate($contract['date_debut'], 'd/m/Y') : 'N/A';
            $contract['date_fin_formatee'] = isset($contract['date_fin']) ? formatDate($contract['date_fin'], 'd/m/Y') : 'Indéterminée';
            $contract['statut_badge'] = isset($contract['statut']) ? getStatusBadge($contract['statut']) : 'N/A';
        }
        unset($contract); // Détacher la référence

        // 6. Préparation des données pour la pagination HTML
        $paginationData = [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $total,
            'perPage' => $limit
        ];
        // Construction de l'URL (sans filtre statut pour l'instant)
        $urlPattern = "contracts.php?page={page}";

        // 7. Retourner les résultats
        return [
            'contracts' => $contracts,
            'pagination' => [
                'current' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages
            ],
            'pagination_html' => renderPagination($paginationData, $urlPattern)
        ];
    } catch (PDOException $e) {
        logSystemActivity('error', "PDO Error in getCompanyContracts: " . $e->getMessage() . " | Query: " . ($query ?? 'N/A') . " | Params: " . print_r($params, true));
        flashMessage("Une erreur de base de données est survenue lors de la récupération des contrats.", "danger");
    } catch (Exception $e) {
        logSystemActivity('error', "General Error in getCompanyContracts: " . $e->getMessage());
        flashMessage("Une erreur générale est survenue lors de la récupération des contrats.", "danger");
    }

    // Retourner une structure vide en cas d'erreur
    return [
        'contracts' => [],
        'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
        'pagination_html' => ''
    ];
}
