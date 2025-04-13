<?php


require_once __DIR__ . '/../../../includes/init.php';

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
                  COUNT(DISTINCT c.id) as nombre_contrats_actifs 
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
                     COUNT(DISTINCT p.id) as employes_actifs 
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


function getCompanyRecentActivity($company_id, $limit = 10)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $limit = min(50, max(1, (int)$limit));

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
        return [];
    }
}


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
        $urlParams = [];

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
        unset($invoice);

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


function addCompanyContract($company_id, $contract_data)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $contract_data = sanitizeInput($contract_data);

    if (!$company_id || empty($contract_data)) {
        flashMessage("Données de contrat invalides", "danger");
        return false;
    }

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
            rollbackTransaction();
            flashMessage("Impossible de créer le contrat", "danger");
            return false;
        }

        if (!empty($contract_data['services']) && is_array($contract_data['services'])) {
            foreach ($contract_data['services'] as $prestationId) {
                $prestationId = filter_var($prestationId, FILTER_VALIDATE_INT);
                if ($prestationId) {

                    $linkData = [
                        'contrat_id' => $contractId,
                        'prestation_id' => $prestationId
                    ];

                    if (!insertRow('contrats_prestations', $linkData)) {
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


function getCompanyContractDetails($company_id, $contract_id)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $contract_id = filter_var(sanitizeInput($contract_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$contract_id) {
        flashMessage("Identifiants invalides.", "danger");
        return false;
    }

    try {
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
            flashMessage("Contrat non trouvé ou accès non autorisé.", "warning");
            return false;
        }

        $contract['reference'] = 'CT-' . str_pad($contract['id'], 6, '0', STR_PAD_LEFT);

        $query_services = "SELECT p.id, p.nom, p.description, p.prix, p.duree, p.type, p.categorie, p.niveau_difficulte, p.capacite_max
                           FROM prestations p
                           JOIN contrats_prestations cp ON p.id = cp.prestation_id
                           WHERE cp.contrat_id = :contract_id
                           ORDER BY p.nom";

        $stmt_services = $pdo->prepare($query_services);
        $stmt_services->execute([':contract_id' => $contract_id]);
        $services = $stmt_services->fetchAll(PDO::FETCH_ASSOC);

        foreach ($services as &$service) {
            if (isset($service['prix'])) {
                $service['prix_formate'] = formatMoney($service['prix']);
            }
        }
        unset($service);

        $contract['services'] = $services;

        return $contract;
    } catch (PDOException $e) {
        logSystemActivity('error', "Erreur BDD getCompanyContractDetails (C:{$company_id}, K:{$contract_id}): " . $e->getMessage());
        flashMessage("Une erreur de base de données est survenue lors de la récupération des détails du contrat.", "danger");
        return false;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur Générale getCompanyContractDetails (C:{$company_id}, K:{$contract_id}): " . $e->getMessage());
        flashMessage("Une erreur inattendue est survenue lors de la récupération des détails du contrat.", "danger");
        return false;
    }
}


function getContractInvoices($contract_id)
{
    $contract_id = filter_var(sanitizeInput($contract_id), FILTER_VALIDATE_INT);
    if (!$contract_id) {
        flashMessage("Identifiant de contrat invalide.", "danger");
        return [];
    }

    try {
        $query = "SELECT entreprise_id FROM contrats WHERE id = :id LIMIT 1";
        $params = [':id' => $contract_id];
        $contractInfo = executeQuery($query, $params)->fetch();

        if (!$contractInfo || !isset($contractInfo['entreprise_id']) || !$contractInfo['entreprise_id']) {
            flashMessage("Impossible de trouver l'entreprise associée à ce contrat.", "warning");
            return [];
        }
        $company_id = $contractInfo['entreprise_id'];

        return getCompanyInvoices($company_id);
    } catch (PDOException $e) {
        logSystemActivity('error', "Erreur BDD getContractInvoices (K:{$contract_id}): " . $e->getMessage());
        flashMessage("Une erreur de base de données est survenue lors de la recherche des factures du contrat.", "danger");
        return [
            'invoices' => [],
            'pagination' => [
                'current' => 1,
                'limit' => 5,
                'total' => 0,
                'totalPages' => 0
            ],
            'pagination_html' => ''
        ];
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur Générale getContractInvoices (K:{$contract_id}): " . $e->getMessage());
        flashMessage("Une erreur inattendue est survenue lors de la recherche des factures du contrat.", "danger");
        return [
            'invoices' => [],
            'pagination' => [
                'current' => 1,
                'limit' => 5,
                'totalPages' => 0
            ],
            'pagination_html' => ''
        ];
    }
}


function getCompanyEmployeeDetails($company_id, $employee_id)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$employee_id) {
        flashMessage("Identifiants invalides.", "danger");
        return false;
    }

    try {
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
            flashMessage("Employé non trouvé ou accès non autorisé.", "warning");
            return false;
        }

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


function updateCompanyEmployee($company_id, $employee_id, $employee_data)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$employee_id || empty($employee_data)) {
        flashMessage("Données invalides pour la mise à jour.", "danger");
        return false;
    }

    $allowedFields = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'date_naissance',
        'genre',
        'statut'
    ];

    $employee_data = sanitizeInput($employee_data);
    $filteredData = array_intersect_key($employee_data, array_flip($allowedFields));

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
        if (isset($filteredData['email'])) {
            $existingUser = fetchOne('personnes', "email = :email AND id != :id", [':email' => $filteredData['email'], ':id' => $employee_id]);
            if ($existingUser) {
                flashMessage("Cette adresse email est déjà utilisée par un autre utilisateur.", "danger");
                return false;
            }
        }

        $whereCondition = 'id = :id AND entreprise_id = :entreprise_id AND role_id = :role_id';
        $whereParams = [
            'id' => $employee_id,
            'entreprise_id' => $company_id,
            'role_id' => ROLE_SALARIE
        ];

        $affectedRows = updateRow('personnes', $filteredData, $whereCondition, $whereParams);

        if ($affectedRows === false) {
            flashMessage("Erreur lors de la mise à jour de l'employé.", "danger");
            return false;
        } elseif ($affectedRows > 0) {
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
            $existsCheck = fetchOne('personnes', 'id = :id AND entreprise_id = :entreprise_id AND role_id = :role_id', $whereParams);
            if ($existsCheck) {
                flashMessage("Aucune modification n'a été détectée.", "info");
                return true;
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


function deleteCompanyEmployee($company_id, $employee_id)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$employee_id) {
        flashMessage("Identifiants invalides pour la suppression.", "danger");
        return false;
    }

    try {
        $whereCondition = 'id = :id AND entreprise_id = :entreprise_id AND role_id = :role_id AND statut != :statut_suspendu';
        $whereParams = [
            'id' => $employee_id,
            'entreprise_id' => $company_id,
            'role_id' => ROLE_SALARIE,
            'statut_suspendu' => 'suspendu'
        ];

        $checkSql = "SELECT nom, prenom FROM personnes WHERE " . $whereCondition . " LIMIT 1";
        $stmtCheck = executeQuery($checkSql, $whereParams);
        $employeeInfo = $stmtCheck->fetch();

        if (!$employeeInfo) {
            $existsCheck = fetchOne('personnes', 'id = :id AND entreprise_id = :entreprise_id AND role_id = :role_id', [
                'id' => $employee_id,
                'entreprise_id' => $company_id,
                'role_id' => ROLE_SALARIE
            ]);
            if (!$existsCheck) {
                flashMessage("Employé non trouvé ou accès non autorisé.", "danger");
            } else if ($existsCheck['statut'] === 'suspendu') {
                flashMessage("Cet employé est déjà suspendu.", "warning");
            } else {
                flashMessage("Employé non trouvé ou accès non autorisé pour la suspension.", "danger");
            }
            return false;
        }

        $employeeFullName = $employeeInfo['prenom'] . ' ' . $employeeInfo['nom'];

        $updateData = ['statut' => 'suspendu'];
        $updateWhereCondition = 'id = :id AND entreprise_id = :entreprise_id AND role_id = :role_id';
        $updateWhereParams = [
            'id' => $employee_id,
            'entreprise_id' => $company_id,
            'role_id' => ROLE_SALARIE
        ];
        $affectedRows = updateRow('personnes', $updateData, $updateWhereCondition, $updateWhereParams);


        if ($affectedRows === false) {
            flashMessage("Erreur lors de la suspension de l'employé.", "danger");
            return false;
        } elseif ($affectedRows > 0) {
            $userIdForLog = (isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) && $_SESSION['user_id'] > 0)
                ? $_SESSION['user_id']
                : null;
            logBusinessOperation(
                $userIdForLog,
                'suspend_employee',
                "Suspension employé {$employeeFullName} (ID: {$employee_id}) par entreprise #{$company_id}"
            );
            flashMessage("L'employé '{$employeeFullName}' a été suspendu avec succès.", "success");
            return true;
        } else {
            flashMessage("La suspension n'a pas pu être effectuée (employé déjà suspendu ou erreur inattendue).", "warning");
            return false;
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur suspension employé #$employee_id: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de la suspension.", "danger");
        return false;
    }
}


function reactivateCompanyEmployee($company_id, $employee_id)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $employee_id = filter_var(sanitizeInput($employee_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$employee_id) {
        flashMessage("Identifiants invalides pour la réactivation.", "danger");
        return false;
    }

    try {
        $statusSuspended = 'suspendu';

        $whereCondition = 'id = :id AND entreprise_id = :entreprise_id AND role_id = :role_id AND statut = :statut_suspendu';
        $whereParams = [
            'id' => $employee_id,
            'entreprise_id' => $company_id,
            'role_id' => ROLE_SALARIE,
            'statut_suspendu' => $statusSuspended
        ];

        $employeeCheck = fetchOne('personnes', $whereCondition, $whereParams);
        if (!$employeeCheck) {
            flashMessage("Employé non trouvé, non suspendu, ou accès non autorisé pour la réactivation.", "warning");
            return false;
        }

        $updateData = ['statut' => STATUS_ACTIVE];

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


function getUserById($userId)
{
    $userId = filter_var(sanitizeInput($userId), FILTER_VALIDATE_INT);
    if (!$userId) {
        return false;
    }

    try {
        return fetchOne('personnes', 'id = :id', [':id' => $userId]);
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur getUserById #$userId: " . $e->getMessage());
        return false;
    }
}



function updateUserProfile($userId, $data)
{
    $userId = filter_var(sanitizeInput($userId), FILTER_VALIDATE_INT);
    if (! $userId) {
        return ['success' => false, 'errors' => ['Identifiant utilisateur invalide.']];
    }

    $allowedFields = ['nom', 'prenom', 'email'];
    $updateData = sanitizeInput($data);
    $filteredData = array_intersect_key($updateData, array_flip($allowedFields));
    $errors = [];

    if (empty($filteredData['nom'])) {
        $errors[] = "Le nom est obligatoire.";
    }
    if (empty($filteredData['prenom'])) {
        $errors[] = "Le prénom est obligatoire.";
    }
    if (empty($filteredData['email'])) {
        $errors[] = "Une adresse email est obligatoire.";
    } elseif (!filter_var($filteredData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Le format de l'adresse email est invalide.";
    }

    if (isset($filteredData['email']) && filter_var($filteredData['email'], FILTER_VALIDATE_EMAIL)) {
        try {
            $existingUser = fetchOne(TABLE_USERS, 'email = :email AND id != :id', [
                ':email' => $filteredData['email'],
                ':id' => $userId
            ]);
            if ($existingUser) {
                $errors[] = 'Cette adresse email est déjà utilisée par un autre compte.';
            }
        } catch (Exception $e) {
            logSystemActivity('error', "Erreur BDD vérification email dans updateUserProfile #$userId: " . $e->getMessage());
            $errors[] = 'Erreur technique lors de la vérification de l\'email.';
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    if (empty($filteredData)) {
        return ['success' => true, 'errors' => []];
    }

    try {
        $affectedRows = updateRow(TABLE_USERS, $filteredData, 'id = :id', [':id' => $userId]);

        if ($affectedRows === false) {
            $errors[] = "Erreur interne lors de la mise à jour du profil.";
            return ['success' => false, 'errors' => $errors];
        }
        if ($affectedRows > 0) {
            logBusinessOperation($userId, 'update_profile', "Mise à jour profil utilisateur #$userId");
        }
        return ['success' => true, 'errors' => []];
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur updateUserProfile #$userId: " . $e->getMessage());
        $errors[] = 'Une erreur technique est survenue lors de la mise à jour du profil.';
        return ['success' => false, 'errors' => $errors];
    }
}



function changeUserPassword($userId, $currentPassword, $newPassword, $confirmPassword)
{
    $userId = filter_var(sanitizeInput($userId), FILTER_VALIDATE_INT);
    $errors = [];

    if (! $userId) {
        $errors[] = 'Identifiant utilisateur invalide.';
        return ['success' => false, 'errors' => $errors];
    }
    if (empty($currentPassword)) {
        $errors[] = "Le mot de passe actuel est obligatoire.";
    }
    if (empty($newPassword)) {
        $errors[] = "Le nouveau mot de passe est obligatoire.";
    }
    if (empty($confirmPassword)) {
        $errors[] = "La confirmation du nouveau mot de passe est obligatoire.";
    }
    if (!empty($newPassword) && !empty($confirmPassword) && $newPassword !== $confirmPassword) {
        $errors[] = "Les nouveaux mots de passe ne correspondent pas.";
    }
    if (!empty($currentPassword) && !empty($newPassword) && $currentPassword === $newPassword) {
        $errors[] = "Le nouveau mot de passe doit être différent de l'ancien.";
    }
    if (!empty($newPassword)) {
        if (strlen($newPassword) < 8) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        }
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins une majuscule.";
        }
        if (!preg_match('/[a-z]/', $newPassword)) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins une minuscule.";
        }
        if (!preg_match('/\d/', $newPassword)) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins un chiffre.";
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    try {
        $user = getUserById($userId);
        if (! $user || !isset($user['mot_de_passe'])) {
            $errors[] = 'Utilisateur non trouvé.';
            return ['success' => false, 'errors' => $errors];
        }

        if (!password_verify($currentPassword, $user['mot_de_passe'])) {
            $errors[] = 'Le mot de passe actuel fourni est incorrect.';
            return ['success' => false, 'errors' => $errors];
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if (! $newPasswordHash) {
            logSystemActivity('error', "Erreur hachage nouveau mot de passe pour utilisateur #$userId");
            $errors[] = 'Erreur technique lors de la préparation du nouveau mot de passe.';
            return ['success' => false, 'errors' => $errors];
        }

        $updateData = ['mot_de_passe' => $newPasswordHash];
        $affectedRows = updateRow(TABLE_USERS, $updateData, 'id = :id', [':id' => $userId]);

        if ($affectedRows > 0) {
            logBusinessOperation($userId, 'change_password', "Changement mot de passe utilisateur #$userId");
            return ['success' => true, 'errors' => []];
        } else {

            logSystemActivity('error', "changeUserPassword: Echec updateRow pour user #$userId (affectedRows: " . print_r($affectedRows, true) . ")");
            $errors[] = 'Erreur interne lors de la mise à jour du mot de passe.';
            return ['success' => false, 'errors' => $errors];
        }
    } catch (Exception $e) {
        logSystemActivity('error', "Exception dans changeUserPassword #$userId: " . $e->getMessage());
        $errors[] = 'Une erreur technique est survenue lors du changement de mot de passe.';
        return ['success' => false, 'errors' => $errors];
    }
}


function addCompanyEmployee($company_id, $employee_data)
{

    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $employee_data = sanitizeInput($employee_data);

    if (!$company_id) {

        return ['success' => false, 'errors' => ['Identifiant d\'entreprise invalide.']];
    }


    $errors = [];
    if (empty($employee_data['nom'])) $errors[] = "Le nom est obligatoire.";
    if (empty($employee_data['prenom'])) $errors[] = "Le prénom est obligatoire.";
    if (empty($employee_data['email'])) {
        $errors[] = "Une adresse email est obligatoire.";
    } elseif (!filter_var($employee_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email fournie n'est pas valide.";
    }


    if (!empty($employee_data['telephone']) && !preg_match('/^(0|\+33)[1-9]([-. ]?[0-9]{2}){4}$/', $employee_data['telephone'])) {
        $errors[] = "Le format du numéro de téléphone est invalide.";
    }


    if (!empty($employee_data['genre']) && !in_array($employee_data['genre'], ['F', 'M', 'Autre'])) {
        $errors[] = "La valeur pour le genre est invalide.";
    }

    validateBirthDate($employee_data['date_naissance'] ?? null, $errors);

    if (!empty($errors)) {

        return ['success' => false, 'errors' => $errors];
    }


    try {

        $company = fetchOne('entreprises', "id = :id", [':id' => $company_id]);
        if (!$company) {

            return ['success' => false, 'errors' => ["Entreprise non trouvée."]];
        }


        $existingUser = fetchOne('personnes', "email = :email", [':email' => $employee_data['email']]);
        if ($existingUser) {
            $errorMessage = "Erreur : L\'adresse email '" . htmlspecialchars($employee_data['email']) . "' est déjà utilisée.";

            return ['success' => false, 'errors' => [$errorMessage]];
        }


        $plainPassword = null;
        if (empty($employee_data['mot_de_passe'])) {
            $plainPassword = bin2hex(random_bytes(6));
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
            'date_naissance' => $employee_data['date_naissance'] ?? null,
            'genre' => $employee_data['genre'] ?? null,
            'photo_url' => $employee_data['photo_url'] ?? null,
            'entreprise_id' => $company_id,
            'role_id' => ROLE_SALARIE,
            'statut' => $employee_data['statut'] ?? 'actif'
        ];


        $employeeId = insertRow('personnes', $insertData);

        if ($employeeId) {
            $userIdForLog = $_SESSION['user_id'] ?? null;
            if (!filter_var($userIdForLog, FILTER_VALIDATE_INT)) {
                $userIdForLog = null;
            }

            logBusinessOperation(
                $userIdForLog,
                'add_employee',
                "Ajout employé #$employeeId ({$insertData['prenom']} {$insertData['nom']}) à l'entreprise #$company_id"
            );


            $successMessage = "L'employé {$insertData['prenom']} {$insertData['nom']} a été ajouté avec succès.";
            if ($plainPassword) {
                $successMessage .= " Un mot de passe temporaire lui sera communiqué.";
                logSystemActivity('info', "Mot de passe temporaire généré pour employé #$employeeId: $plainPassword");
            }


            return ['success' => true, 'newId' => $employeeId];
        } else {
            $errorMessage = "Erreur interne : Impossible d'ajouter l'employé. Contactez le support.";
            error_log("Échec d'insertion dans la table personnes : " . print_r($insertData, true));

            return ['success' => false, 'errors' => [$errorMessage]];
        }
    } catch (Exception $e) {
        $errorMessage = "Erreur technique inattendue lors de l'ajout de l'employé.";
        error_log("Erreur dans addCompanyEmployee : " . $e->getMessage());
        error_log("Données employé : " . print_r($employee_data, true));

        return ['success' => false, 'errors' => [$errorMessage]];
    }
}


function getCompanyUsageStatistics($company_id)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id) {
        return false;
    }

    try {
        $query = "SELECT COUNT(r.id) as total_rdv,
                     SUM(CASE WHEN r.statut = 'termine' THEN 1 ELSE 0 END) as rdv_termines,
                     SUM(CASE WHEN r.date_rdv >= CURDATE() THEN 1 ELSE 0 END) as rdv_a_venir,
                     COUNT(DISTINCT p.id) as employes_actifs 
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

function getCompanyEmployees($company_id, $page = 1, $limit = 5, $search = '', $statusFilter = 'actif')
{

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

    $sqlBase = " FROM personnes p WHERE p.entreprise_id = :company_id AND p.role_id = :role_id";
    $params = [
        ':company_id' => $company_id,
        ':role_id' => ROLE_SALARIE
    ];
    $countParams = $params;

    $validStatusFilters = ['actif', 'inactif', 'suspendu', 'tous'];
    if (in_array($statusFilter, $validStatusFilters) && $statusFilter !== 'tous') {
        $sqlBase .= " AND p.statut = :status";
        $params[':status'] = $statusFilter;
        $countParams[':status'] = $statusFilter;
    }

    if (!empty($search)) {
        $searchWild = '%' . $search . '%';
        $sqlBase .= " AND (p.nom LIKE :search OR p.prenom LIKE :search OR p.email LIKE :search)";
        $params[':search'] = $searchWild;
        $countParams[':search'] = $searchWild;
    }

    try {
        $countQuery = "SELECT COUNT(*) as total" . $sqlBase;
        $totalResult = executeQuery($countQuery, $countParams)->fetch();
        $total = $totalResult['total'] ?? 0;
        $totalPages = ceil($total / $limit);

        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $limit;

        $query = "SELECT p.* " . $sqlBase . " ORDER BY p.nom ASC, p.prenom ASC LIMIT :limit OFFSET :offset";

        $queryParams = $params;
        $queryParams[':limit'] = $limit;
        $queryParams[':offset'] = $offset;

        $employees = executeQuery($query, $queryParams)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($employees as &$employee) {
            if (!isset($employee['derniere_connexion_formatee'])) {
                $employee['derniere_connexion_formatee'] = isset($employee['derniere_connexion']) ? formatDate($employee['derniere_connexion'], 'd/m/Y H:i') : 'Jamais';
            }
            if (!isset($employee['statut_badge'])) {
                $employee['statut_badge'] = isset($employee['statut']) ? getStatusBadge($employee['statut']) : 'N/A';
            }
        }
        unset($employee);

        $paginationData = [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $total,
            'perPage' => $limit
        ];

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
        logSystemActivity('error', "PDO Error in getCompanyEmployees: " . $e->getMessage() . " | Query: " . ($query ?? 'N/A') . " | Params: " . print_r($queryParams ?? $params, true));
        flashMessage("Une erreur de base de données est survenue lors de la récupération des employés.", "danger");
    } catch (Exception $e) {
        logSystemActivity('error', "General Error in getCompanyEmployees: " . $e->getMessage());
        flashMessage("Une erreur générale est survenue lors de la récupération des employés.", "danger");
    }

    return [
        'employees' => [],
        'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
        'pagination_html' => ''
    ];
}


function getCompanyContracts($company_id, $status = null, $page = 1, $limit = 10)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $page = max(1, (int)sanitizeInput($page));
    $limit = max(1, (int)sanitizeInput($limit));

    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide.", "danger");
        return [
            'contracts' => [],
            'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
            'pagination_html' => ''
        ];
    }

    $where = "c.entreprise_id = :company_id";
    $params = [':company_id' => $company_id];
    $countParams = [':company_id' => $company_id];

    try {
        $countQuery = "SELECT COUNT(DISTINCT c.id) as total FROM contrats c WHERE " . $where;
        $totalResult = executeQuery($countQuery, $countParams)->fetch();
        $total = $totalResult['total'] ?? 0;
        $totalPages = ceil($total / $limit);

        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $limit;

        $query = "SELECT c.* 
                  FROM contrats c 
                  WHERE " . $where . "
                  ORDER BY c.date_debut DESC 
                  LIMIT :limit OFFSET :offset";

        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $contracts = executeQuery($query, $params)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($contracts as &$contract) {
            $contract['reference'] = 'CT-' . str_pad($contract['id'], 6, '0', STR_PAD_LEFT);
            $contract['date_debut_formatee'] = isset($contract['date_debut']) ? formatDate($contract['date_debut'], 'd/m/Y') : 'N/A';
            $contract['date_fin_formatee'] = isset($contract['date_fin']) ? formatDate($contract['date_fin'], 'd/m/Y') : 'Indéterminée';
            $contract['statut_badge'] = isset($contract['statut']) ? getStatusBadge($contract['statut']) : 'N/A';
        }
        unset($contract);

        $paginationData = [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $total,
            'perPage' => $limit
        ];
        $urlPattern = "contracts.php?page={page}";

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

    return [
        'contracts' => [],
        'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
        'pagination_html' => ''
    ];
}

function processContactFormSubmission($postData)
{
    $userIdForLog = $_SESSION['user_id'] ?? null;

    if (!isset($postData['csrf_token']) || !validateToken($postData['csrf_token'])) {
        logSecurityEvent($userIdForLog, 'csrf_failure', "[SECURITY FAILURE] Tentative de soumission formulaire contact avec jeton invalide");
        flashMessage('Erreur de sécurité. Impossible de traiter votre demande.', 'danger');
        redirectTo(WEBCLIENT_URL . '/modules/companies/contact.php');
        exit;
    }

    $name = trim(sanitizeInput($postData['name'] ?? ''));
    $email = trim(sanitizeInput($postData['email'] ?? ''));
    $subject = trim(sanitizeInput($postData['subject'] ?? ''));
    $message = trim(sanitizeInput($postData['message'] ?? ''));
    $errors = [];

    if (empty($name)) {
        $errors[] = "Le nom est obligatoire.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Une adresse email valide est obligatoire.";
    }
    if (empty($subject)) {
        $errors[] = "Le sujet est obligatoire.";
    }
    if (empty($message)) {
        $errors[] = "Le message ne peut pas être vide.";
    }

    if (empty($errors)) {
        $logDetail = "Formulaire contact reçu de: $name ($email), Sujet: $subject";
        logSystemActivity('contact_form_submission', $logDetail);

        flashMessage("Votre message a bien été envoyé. Nous vous répondrons bientôt.", "success");
    } else {
        flashMessage("Erreurs dans le formulaire: <br> - " . implode("<br> - ", $errors), "danger");
        $_SESSION['contact_form_data'] = $postData;
    }

    redirectTo(WEBCLIENT_URL . '/modules/companies/contact.php');
    exit;
}

function processAddEmployeeRequest($postData, $entrepriseId)
{
    error_log(">>> DEBUG [PROCESS_ADD]: Function Entry");
    global $errors, $submittedData;
    $errors = [];
    $submittedData = [];


    $nom = sanitizeInput($postData['nom'] ?? '');
    $prenom = sanitizeInput($postData['prenom'] ?? '');
    $email = filter_var(sanitizeInput($postData['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $telephone = sanitizeInput($postData['telephone'] ?? '');
    $date_naissance = sanitizeInput($postData['date_naissance'] ?? '');
    $genre = sanitizeInput($postData['genre'] ?? '');
    $statut = sanitizeInput($postData['statut'] ?? STATUS_ACTIVE);

    $submittedData = [
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $postData['email'] ?? '',
        'telephone' => $telephone,
        'date_naissance' => $date_naissance,
        'genre' => $genre,
        'statut' => $statut
    ];


    if (empty($nom)) {
        $errors[] = "Le nom est obligatoire.";
    }
    if (empty($prenom)) {
        $errors[] = "Le prénom est obligatoire.";
    }
    if (empty($email)) {
        $errors[] = "L'adresse email est invalide.";
    }
    if (!in_array($statut, USER_STATUSES)) {
        $errors[] = "Le statut sélectionné n'est pas valide.";
        $statut = STATUS_ACTIVE;
    }
    if (!empty($genre) && !in_array($genre, ['M', 'F', 'Autre'])) {
        $errors[] = "Le genre sélectionné n'est pas valide.";
    }

    if (!empty($errors)) {
        error_log(">>> DEBUG [PROCESS_ADD]: Validation FAILED: " . implode('; ', $errors));
        flashMessage("Erreur(s) de validation :<br>" . implode("<br>", $errors), "danger");
        return ['success' => false, 'errors' => $errors];
    }

    error_log(">>> DEBUG [PROCESS_ADD]: Validation OK.");

    $employee_data = [
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'telephone' => $telephone ?: null,
        'date_naissance' => $date_naissance ?: null,
        'genre' => $genre ?: null,
        'statut' => $statut,
        'role_id' => ROLE_SALARIE
    ];

    error_log(">>> DEBUG [PROCESS_ADD]: Calling addCompanyEmployee.");

    $result = addCompanyEmployee($entrepriseId, $employee_data);

    if ($result['success']) {
        error_log(">>> DEBUG [PROCESS_ADD]: addCompanyEmployee SUCCESS (ID: " . ($result['newId'] ?? 'N/A') . ")");
        flashMessage("L'employé a été ajouté avec succès.", "success");
        error_log(">>> DEBUG [PROCESS_ADD]: Redirecting after success.");
        redirectTo(WEBCLIENT_URL . '/modules/companies/employees.php');
        exit;
    } else {

        $dbErrors = $result['errors'] ?? ['Erreur inconnue lors de l\'ajout.'];
        error_log(">>> DEBUG [PROCESS_ADD]: addCompanyEmployee FAILED: " . implode('; ', $dbErrors));
        flashMessage("Erreur lors de l'ajout de l'employé :<br>" . implode("<br>", $dbErrors), "danger");

        $errors = array_merge($errors, $dbErrors);
        return ['success' => false, 'errors' => $errors];
    }
}


function processModifyEmployeeRequest($postData, $entrepriseId, $employeeId)
{
    global $errors, $submittedData;
    $errors = [];
    $submittedData = sanitizeInput($postData);

    if (empty($submittedData['nom'])) $errors[] = "Le nom est obligatoire.";
    if (empty($submittedData['prenom'])) $errors[] = "Le prénom est obligatoire.";
    if (empty($submittedData['email']) || !filter_var($submittedData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Une adresse email valide est obligatoire.";
    }
    if (isset($submittedData['statut']) && !in_array($submittedData['statut'], USER_STATUSES)) {
        $errors[] = "Le statut fourni n'est pas valide.";
    }
    if (isset($submittedData['genre']) && !empty($submittedData['genre']) && !in_array($submittedData['genre'], ['M', 'F', 'Autre'])) {
        $errors[] = "Le genre sélectionné n'est pas valide.";
    }

    validateBirthDate($submittedData['date_naissance'] ?? null, $errors);

    if (empty($errors)) {
        $updateData = [
            'nom' => $submittedData['nom'],
            'prenom' => $submittedData['prenom'],
            'email' => $submittedData['email'],
            'telephone' => $submittedData['telephone'] ?? null,
            'date_naissance' => !empty($submittedData['date_naissance']) ? $submittedData['date_naissance'] : null,
            'genre' => $submittedData['genre'] ?? null,
            'statut' => $submittedData['statut'] ?? STATUS_ACTIVE
        ];


        $updateSuccess = updateCompanyEmployee($entrepriseId, $employeeId, $updateData);

        if ($updateSuccess) {

            redirectTo(WEBCLIENT_URL . '/modules/companies/employees.php?action=view&id=' . $employeeId);
            exit;
        } else {
            flashMessage("Erreurs dans le formulaire :<br> - " . implode("<br> - ", $errors), "danger");
            return false;
        }
    } else {

        flashMessage("Erreurs dans le formulaire :<br> - " . implode("<br> - ", $errors), "danger");
        return false;
    }
}

function getActiveServices(): array
{
    $available_services = [];
    $tableName = defined('TABLE_SERVICES') ? TABLE_SERVICES : 'services';
    $query = "SELECT id, nom, description FROM {$tableName} WHERE actif = 1 ORDER BY ordre";

    try {
        $stmt = executeQuery($query);
        $servicesResult = $stmt->fetchAll();

        if ($servicesResult) {
            foreach ($servicesResult as $service) {
                $description = $service['nom'] . (!empty($service['description']) ? ' - ' . $service['description'] : '');
                $available_services[$service['id']] = $description;
            }
        }

        return $available_services;
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des services actifs : " . $e->getMessage());
        return [];
    }
}

function getInvoiceDetailsForCompany($company_id, $invoice_id)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $invoice_id = filter_var(sanitizeInput($invoice_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$invoice_id) {
        flashMessage("Identifiants invalides.", "danger");
        return false;
    }

    try {
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
            flashMessage("Facture non trouvée ou accès non autorisé.", "warning");
            return false;
        }

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
            $invoice['tva_formatee'] = formatMoney(($invoice['montant_total'] ?? 0) - ($invoice['montant_ht'] ?? 0));
            $invoice['tva_pourcentage_formate'] = $invoice['tva'] . '%';
        }

        $invoice['lignes'] = [];

        return $invoice;
    } catch (PDOException $e) {
        logSystemActivity('error', "Erreur BDD getInvoiceDetailsForCompany (E:{$company_id}, F:{$invoice_id}): " . $e->getMessage());
        flashMessage("Une erreur de base de données est survenue lors de la récupération des détails de la facture.", "danger");
        return false;
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur Générale getInvoiceDetailsForCompany (E:{$company_id}, F:{$invoice_id}): " . $e->getMessage());
        flashMessage("Une erreur inattendue est survenue lors de la récupération des détails de la facture.", "danger");
        return false;
    }
}

function requestCompanyQuote($data)
{
    $errors = [];
    if (empty($data['entreprise_id'])) {
        $errors[] = "L'identifiant de l'entreprise est manquant.";
    } else {
        $company_id = filter_var(sanitizeInput($data['entreprise_id']), FILTER_VALIDATE_INT);
        if (!$company_id) {
            $errors[] = "L'identifiant de l'entreprise est invalide.";
        } else {
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

    if (!empty($data['contact_telephone'])) {
        $phonePattern = '/^(0|\+33)[1-9]([-. ]?[0-9]{2}){4}$/';
        if (!preg_match($phonePattern, trim($data['contact_telephone']))) {
            $errors[] = "Le format du numéro de téléphone est invalide (attendu : 0XXXXXXXXX ou +33XXXXXXXXX).";
        }
    }

    if (!empty($errors)) {
        return [
            'success' => false,
            'message' => implode("<br>", $errors)
        ];
    }



    try {
        $devisData = [
            'entreprise_id' => $data['entreprise_id'],
            'date_creation' => date('Y-m-d'),
            'montant_total' => 0,
            'montant_ht' => 0,
            'tva' => 0,
            'statut' => 'en_attente',
            'conditions_paiement' => "Demande: " . sanitizeInput($data['description_besoin'] ?? 'N/A') .
                " | Contact: " . sanitizeInput($data['contact_personne'] ?? 'N/A') .
                " (" . sanitizeInput($data['contact_email'] ?? 'N/A') .
                " / " . sanitizeInput($data['contact_telephone'] ?? 'N/A') . ")",
        ];

        $devisId = insertRow('devis', $devisData);

        if (!$devisId) {
            logSystemActivity('error', "Échec de l'insertion du devis pour l'entreprise #{$data['entreprise_id']}");
            return [
                'success' => false,
                'message' => "Erreur lors de l'enregistrement de votre demande de devis."
            ];
        }

        $details_demande = "Service/Contrat: " . ($data['service_souhaite'] ?? 'N/A') . "\n";
        $details_demande .= "Nb Salariés: " . ($data['nombre_salaries'] ?? 'N/A') . "\n";
        $details_demande .= "Description: " . ($data['description_besoin'] ?? 'N/A') . "\n";
        $details_demande .= "Contact: " . ($data['contact_personne'] ?? 'N/A') . " (" . ($data['contact_email'] ?? 'N/A') . " / " . ($data['contact_telephone'] ?? 'N/A') . ")";

        if (function_exists('notifyAdminsNewQuoteRequest')) {
            notifyAdminsNewQuoteRequest($devisId, $data['entreprise_id'], $details_demande);
        } else {
            logSystemActivity('warning', "Fonction notifyAdminsNewQuoteRequest non trouvée pour devis #$devisId");
        }

        $userIdForLog = (isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) && $_SESSION['user_id'] > 0)
            ? $_SESSION['user_id']
            : null;
        logBusinessOperation($userIdForLog, 'demande_devis', "Demande de devis #$devisId pour entreprise #{$data['entreprise_id']}");

        return [
            'success' => true,
            'message' => "Votre demande de devis a bien été envoyée (N° $devisId). Nous reviendrons vers vous rapidement."
        ];
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur demande devis: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        return [
            'success' => false,
            'message' => "Erreur technique: " . $e->getMessage()
        ];
    }
}


if (!function_exists('notifyAdminsNewQuoteRequest')) {
    function notifyAdminsNewQuoteRequest($devisId, $entrepriseId, $detailsMessage = '')
    {
        try {
            $adminRoleId = ROLE_ADMIN;
            $admins = fetchAll('personnes', 'role_id = :role_id AND statut = \'actif\'', '', 0, 0, [':role_id' => $adminRoleId]);
            $titre = "Nouvelle demande de devis (#$devisId)";
            $entreprise = fetchOne('entreprises', 'id = :id', [':id' => $entrepriseId]);
            $nomEntreprise = $entreprise ? $entreprise['nom'] : "Entreprise ID #$entrepriseId";
            $message = "Demande reçue de $nomEntreprise.\n" . $detailsMessage;
            $lien = '/admin/quotes/view/' . $devisId;

            foreach ($admins as $admin) {
                insertRow('notifications', [
                    'personne_id' => $admin['id'],
                    'titre' => $titre,
                    'message' => $message,
                    'type' => 'info',
                    'lien' => $lien
                ]);
            }
            logSystemActivity('info', "Notification envoyée aux admins pour devis #$devisId");
        } catch (Exception $e) {
            logSystemActivity('error', "Erreur notification admin pour devis #$devisId: " . $e->getMessage());
        }
    }
}

function handleCompanyEmployeePostRequest(array $postData, int $entrepriseId): array
{
    global $action, $employeeId;

    if (!isset($postData['csrf_token']) || !validateToken($postData['csrf_token'])) {
        logSecurityEvent($_SESSION['user_id'] ?? null, 'csrf_failure', '[SECURITY FAILURE] Tentative POST avec jeton invalide sur employees.php (depuis handleCompanyEmployeePostRequest)');
        flashMessage("Erreur de sécurité (jeton invalide).", "danger");
        return ['redirectUrl' => WEBCLIENT_URL . '/modules/companies/employees.php'];
    }

    $postAction = $postData['action'] ?? '';
    $postEmployeeId = isset($postData['employee_id']) ? filter_var($postData['employee_id'], FILTER_VALIDATE_INT) : null;
    $result = ['action' => $action, 'employeeId' => $employeeId];

    switch ($postAction) {
        case 'add_employee':
            $addResult = processAddEmployeeRequest($postData, $entrepriseId);
            if (!$addResult['success']) {
                $_SESSION['submitted_data'] = $postData;
                $result['action'] = 'add';
            } else {
                return ['redirectUrl' => WEBCLIENT_URL . '/modules/companies/employees.php'];
            }
            break;

        case 'edit_employee':
            if ($postEmployeeId) {
                $editResult = processModifyEmployeeRequest($postData, $entrepriseId, $postEmployeeId);
                if (!$editResult) {
                    $_SESSION['submitted_data'] = $postData;
                    $result['action'] = 'edit';
                    $result['employeeId'] = $postEmployeeId;
                } else {
                    return ['redirectUrl' => WEBCLIENT_URL . '/modules/companies/employees.php?action=view&id=' . $postEmployeeId];
                }
            } else {
                flashMessage("ID d'employé manquant pour la modification.", "danger");
                $result['action'] = 'list';
            }
            break;

        case 'delete_employee':
            if ($postEmployeeId) {
                deleteCompanyEmployee($entrepriseId, $postEmployeeId);
            } else {
                flashMessage("ID d'employé manquant pour la suppression.", "danger");
            }
            return ['redirectUrl' => WEBCLIENT_URL . '/modules/companies/employees.php'];

        case 'reactivate_employee':
            if ($postEmployeeId) {
                reactivateCompanyEmployee($entrepriseId, $postEmployeeId);
            } else {
                flashMessage("ID d'employé manquant pour la réactivation.", "danger");
            }
            return ['redirectUrl' => WEBCLIENT_URL . '/modules/companies/employees.php'];

        default:
            flashMessage("Action POST non reconnue.", "warning");
            return ['redirectUrl' => WEBCLIENT_URL . '/modules/companies/employees.php'];
    }

    return $result;
}


function prepareCompanyEmployeeViewData(string $action, int $entrepriseId, ?int $employeeId, int $page, string $search, string $statusFilter, array $submittedData): array
{
    $viewData = [
        'pageTitle' => "Gestion des Salariés",
        'employee' => null,
        'employeesData' => [],
        'paginationHtml' => '',
        'action' => $action,
        'redirectUrl' => null
    ];

    switch ($action) {
        case 'view':
            if ($employeeId) {
                $employee = getCompanyEmployeeDetails($entrepriseId, $employeeId);
                if (!$employee) {
                    flashMessage("Employé non trouvé ou accès non autorisé.", "warning");
                    $viewData['redirectUrl'] = WEBCLIENT_URL . '/modules/companies/employees.php';
                } else {
                    $viewData['employee'] = $employee;
                    $viewData['pageTitle'] = "Détails - " . htmlspecialchars($employee['prenom'] . ' ' . $employee['nom']);
                }
            } else {
                flashMessage("ID d'employé manquant pour la visualisation.", "warning");
                $viewData['redirectUrl'] = WEBCLIENT_URL . '/modules/companies/employees.php';
            }
            break;

        case 'add':
            $viewData['pageTitle'] = "Ajouter un Salarié";
            $viewData['employee'] = $submittedData;
            break;

        case 'edit':
            if ($employeeId) {
                $employeeDetails = getCompanyEmployeeDetails($entrepriseId, $employeeId);
                if (!$employeeDetails) {
                    flashMessage("Employé non trouvé ou accès non autorisé.", "warning");
                    $viewData['redirectUrl'] = WEBCLIENT_URL . '/modules/companies/employees.php';
                } else {
                    $viewData['employee'] = !empty($submittedData) ? $submittedData : $employeeDetails;
                    $viewData['pageTitle'] = "Modifier - " . htmlspecialchars($employeeDetails['prenom'] . ' ' . $employeeDetails['nom']);
                }
            } else {
                flashMessage("ID d'employé manquant pour la modification.", "warning");
                $viewData['redirectUrl'] = WEBCLIENT_URL . '/modules/companies/employees.php';
            }
            break;

        case 'list':
        default:
            $viewData['action'] = 'list';
            $viewData['pageTitle'] = "Liste des Salariés";
            $employeesResult = getCompanyEmployees($entrepriseId, $page, 6, $search, $statusFilter); // Utiliser 6 au lieu de DEFAULT_ITEMS_PER_PAGE
            $viewData['employeesData'] = $employeesResult['employees'];
            $viewData['paginationHtml'] = $employeesResult['pagination_html'];
            break;
    }

    return $viewData;
}

function getCompanyQuotesList($company_id, $page = 1, $limit = DEFAULT_ITEMS_PER_PAGE)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $page = max(1, (int)sanitizeInput($page));
    $limit = max(1, (int)sanitizeInput($limit));

    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide.", "danger");
        return [
            'quotes' => [],
            'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
            'pagination_html' => ''
        ];
    }

    $where = "entreprise_id = :company_id";
    $params = [':company_id' => $company_id];
    $countParams = $params;

    try {
        $total = countTableRows(TABLE_QUOTES, $where, $countParams);
        $totalPages = ceil($total / $limit);
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $limit;

        $quotes = fetchAll(TABLE_QUOTES, $where, 'date_creation DESC', $limit, $offset, $params);

        foreach ($quotes as &$quote) {
            $quote['numero_devis'] = 'DV-' . str_pad($quote['id'], 6, '0', STR_PAD_LEFT);
            $quote['date_creation_formatee'] = isset($quote['date_creation']) ? formatDate($quote['date_creation'], 'd/m/Y') : 'N/A';
            $quote['statut_badge'] = isset($quote['statut']) ? getStatusBadge($quote['statut']) : 'N/A';
            $quote['montant_total_formate'] = isset($quote['montant_total']) && is_numeric($quote['montant_total']) ? formatMoney($quote['montant_total']) : 'N/D';
        }
        unset($quote);

        $paginationData = [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $total,
            'perPage' => $limit
        ];
        $urlPattern = "quotes.php?action=list&page={page}";

        return [
            'quotes' => $quotes,
            'pagination' => [
                'current' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages
            ],
            'pagination_html' => renderPagination($paginationData, $urlPattern)
        ];
    } catch (PDOException $e) {
        logSystemActivity('error', "PDO Error in getCompanyQuotesList: " . $e->getMessage() . " | Params: " . print_r($params, true));
        flashMessage("Une erreur de base de données est survenue lors de la récupération des devis.", "danger");
    } catch (Exception $e) {
        logSystemActivity('error', "General Error in getCompanyQuotesList: " . $e->getMessage());
        flashMessage("Une erreur générale est survenue lors de la récupération des devis.", "danger");
    }

    return [
        'quotes' => [],
        'pagination' => ['current' => 1, 'limit' => $limit, 'total' => 0, 'totalPages' => 0],
        'pagination_html' => ''
    ];
}


function getCompanyQuoteDetails($company_id, $quote_id)
{
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $quote_id = filter_var(sanitizeInput($quote_id), FILTER_VALIDATE_INT);

    if (!$company_id || !$quote_id) {
        flashMessage("Identifiants invalides pour la visualisation du devis.", "danger");
        return false;
    }

    try {
        $quote = fetchOne(TABLE_QUOTES, "id = :quote_id AND entreprise_id = :company_id", [
            ':quote_id' => $quote_id,
            ':company_id' => $company_id
        ]);

        if (!$quote) {
            flashMessage("Devis non trouvé ou accès non autorisé.", "warning");
            return false;
        }

        $quote['numero_devis'] = 'DV-' . str_pad($quote['id'], 6, '0', STR_PAD_LEFT);
        $quote['date_creation_formatee'] = isset($quote['date_creation']) ? formatDate($quote['date_creation'], 'd/m/Y') : 'N/A';
        $quote['date_validite_formatee'] = isset($quote['date_validite']) ? formatDate($quote['date_validite'], 'd/m/Y') : 'N/A';
        $quote['statut_badge'] = isset($quote['statut']) ? getStatusBadge($quote['statut']) : 'N/A';
        $quote['montant_total_formate'] = isset($quote['montant_total']) && is_numeric($quote['montant_total']) ? formatMoney($quote['montant_total']) : 'N/D';
        $quote['montant_ht_formate'] = isset($quote['montant_ht']) && is_numeric($quote['montant_ht']) ? formatMoney($quote['montant_ht']) : 'N/D';
        $quote['tva_formate'] = $quote['montant_total_formate'] !== 'N/D' && $quote['montant_ht_formate'] !== 'N/D' ? formatMoney($quote['montant_total'] - $quote['montant_ht']) : 'N/D';
        $quote['tva_taux'] = isset($quote['tva']) ? $quote['tva'] . '%' : 'N/D';


        return $quote;
    } catch (PDOException $e) {
        logSystemActivity('error', "PDO Error in getCompanyQuoteDetails: " . $e->getMessage() . " | Quote ID: {$quote_id}, Company ID: {$company_id}");
        flashMessage("Une erreur de base de données est survenue lors de la récupération des détails du devis.", "danger");
        return false;
    } catch (Exception $e) {
        logSystemActivity('error', "General Error in getCompanyQuoteDetails: " . $e->getMessage());
        flashMessage("Une erreur générale est survenue lors de la récupération des détails du devis.", "danger");
        return false;
    }
}

function validateQuoteRequestData($formData)
{
    $errors = [];
    if (empty($formData['service_souhaite'])) {
        $errors[] = "Vous devez sélectionner un service.";
    }
    if (empty($formData['description_besoin'])) {
        $errors[] = "La description du besoin est obligatoire.";
    }
    if (!empty($formData['contact_email']) && !filter_var($formData['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    return $errors;
}


function processQuoteRequestSubmission(array $formData, int $companyId): array
{
    if (!isset($formData['csrf_token']) || !validateToken($formData['csrf_token'])) {
        logSecurityEvent($companyId, 'csrf_failure', '[SECURITY FAILURE] Tentative de soumission de devis avec jeton invalide');
        return ['success' => false, 'message' => 'Erreur de sécurité. Impossible de traiter votre demande.', 'submittedData' => $formData];
    }

    $errors = validateQuoteRequestData($formData);

    if (!empty($errors)) {
        $errorMessage = "Erreurs dans le formulaire :<br> - " . implode("<br> - ", $errors);
        return ['success' => false, 'message' => $errorMessage, 'submittedData' => $formData];
    }

    $formData['entreprise_id'] = $companyId;

    $result = requestCompanyQuote($formData);

    if ($result['success']) {
        return ['success' => true, 'message' => $result['message']];
    } else {
        return ['success' => false, 'message' => $result['message'] ?? 'Une erreur inconnue est survenue lors de la demande.', 'submittedData' => $formData];
    }
}


function prepareQuotesViewData(string $action, int $companyId, ?int $quoteId, int $page, array $submittedData = []): array
{
    $viewData = [
        'pageTitle' => "Mes Devis - Espace Entreprise",
        'quotesData' => [],
        'quoteDetails' => null,
        'paginationHtml' => '',
        'available_services' => [],
        'submittedData' => $submittedData,
        'redirectUrl' => null
    ];

    switch ($action) {
        case 'list':
            $viewData['pageTitle'] = "Mes Devis Soumis - Espace Entreprise";
            $quotesResult = getCompanyQuotesList($companyId, $page);
            $viewData['quotesData'] = $quotesResult['quotes'];
            $viewData['paginationHtml'] = $quotesResult['pagination_html'];
            break;

        case 'request':
            $viewData['pageTitle'] = "Demander un nouveau Devis - Espace Entreprise";
            $available_services = getActiveServices();
            if (empty($available_services)) {
                flashMessage("Impossible de charger la liste des services disponibles pour le moment.", "warning");
            }
            $viewData['available_services'] = $available_services;
            break;

        case 'view':
            if (!$quoteId) {
                flashMessage("Identifiant de devis manquant.", "danger");
                $viewData['redirectUrl'] = WEBCLIENT_URL . '/modules/companies/quotes.php?action=list';
            } else {
                $quoteDetails = getCompanyQuoteDetails($companyId, $quoteId);
                if (!$quoteDetails) {
                    $viewData['redirectUrl'] = WEBCLIENT_URL . '/modules/companies/quotes.php?action=list';
                } else {
                    $viewData['quoteDetails'] = $quoteDetails;
                    $viewData['pageTitle'] = "Détails du Devis " . htmlspecialchars($quoteDetails['numero_devis']);
                }
            }
            break;

        default:
            flashMessage("Action non valide.", "warning");
            $viewData['redirectUrl'] = WEBCLIENT_URL . '/modules/companies/quotes.php?action=list';
            break;
    }

    return $viewData;
}


function processProfileUpdateRequest(array $postData, int $userId): array
{
    if (!isset($postData['csrf_token']) || !validateToken($postData['csrf_token'])) {
        logSecurityEvent($userId, 'csrf_failure', '[SECURITY FAILURE] Tentative de mise à jour de profil avec jeton invalide');
        return ['success' => false, 'errors' => ['Erreur de sécurité.'], 'submittedData' => $postData];
    }
    $profileData = sanitizeInput($postData);

    $result = updateUserProfile($userId, $profileData);

    if ($result['success']) {
        $_SESSION['user_name'] = $profileData['prenom'] . ' ' . $profileData['nom'];
        $_SESSION['user_email'] = $profileData['email'];
        return ['success' => true, 'errors' => [], 'submittedData' => $profileData];
    } else {
        return ['success' => false, 'errors' => $result['errors'] ?? ['Une erreur inconnue est survenue.'], 'submittedData' => $profileData];
    }
}

function processPasswordChangeRequest(array $postData, int $userId): array
{
    if (!isset($postData['csrf_token']) || !validateToken($postData['csrf_token'])) {
        logSecurityEvent($userId, 'csrf_failure', '[SECURITY FAILURE] Tentative de changement de mot de passe avec jeton invalide');
        return ['success' => false, 'errors' => ['Erreur de sécurité.']];
    }

    $passwordData = sanitizeInput($postData);

    $result = changeUserPassword(
        $userId,
        $passwordData['current_password'] ?? '',
        $passwordData['new_password'] ?? '',
        $passwordData['confirm_password'] ?? ''
    );

    if ($result['success']) {
        return ['success' => true, 'errors' => []];
    } else {
        return ['success' => false, 'errors' => $result['errors'] ?? ['Une erreur inconnue est survenue.']];
    }
}

function prepareSettingsViewData(int $companyId, int $userId, array $submittedProfileData = []): array
{
    $viewData = [
        'companyDetails' => null,
        'currentUser' => null,
        'profileSubmittedData' => [],
        'redirectUrl' => null,
    ];

    $viewData['companyDetails'] = getCompanyDetails($companyId);
    if (!$viewData['companyDetails']) {
        flashMessage("Impossible de récupérer les informations de l'entreprise.", 'danger');
        $viewData['redirectUrl'] = 'index.php';
        return $viewData;
    }

    $viewData['currentUser'] = getUserById($userId);
    if (!$viewData['currentUser']) {
        flashMessage("Impossible de récupérer les informations de l'utilisateur.", 'danger');
        $viewData['redirectUrl'] = 'index.php';
        return $viewData;
    }

    $viewData['profileSubmittedData'] = ! empty($submittedProfileData) ? $submittedProfileData : [
        'nom' => $viewData['currentUser']['nom'] ?? '',
        'prenom' => $viewData['currentUser']['prenom'] ?? '',
        'email' => $viewData['currentUser']['email'] ?? ''
    ];

    return $viewData;
}
