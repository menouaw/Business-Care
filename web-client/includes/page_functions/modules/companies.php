<?php
/**
 * fonctions pour la gestion des entreprises
 *
 * ce fichier contient les fonctions necessaires pour gérer les entreprises clientes
 */

require_once __DIR__ . '/../../../includes/init.php';

/**
 * Récupère la liste des entreprises avec pagination et recherche
 * 
 * @param int $page numéro de page
 * @param int $limit nombre d'éléments par page
 * @param string $search terme de recherche
 * @return array liste des entreprises et infos de pagination
 */
function getCompaniesList($page = 1, $limit = 20, $search = '') {
    // Sanitize inputs
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);
    $search = sanitizeInput($search);
    
    try {
        $where = '';
        $params = [];
        
        // Ajout de la recherche si spécifiée
        if (!empty($search)) {
            $searchTerm = "%" . $search . "%";
            $where = "(e.nom LIKE :search OR e.email LIKE :search OR e.adresse LIKE :search)";
            $params['search'] = $searchTerm;
        }
        
        // Construction d'une requête custom qui ne peut pas utiliser directement paginateResults
        // car nous avons besoin de jointures et de comptages d'employés et de contrats
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT e.id, e.nom, e.adresse, e.telephone, e.email, e.logo_url, e.statut,
                  COUNT(DISTINCT p.id) as nombre_employes,
                  COUNT(DISTINCT c.id) as nombre_contrats
                  FROM entreprises e 
                  LEFT JOIN personnes p ON e.id = p.entreprise_id AND p.role_id = :role_id
                  LEFT JOIN contrats c ON e.id = c.entreprise_id AND c.date_fin >= CURDATE()
                  WHERE 1=1";
        
        $queryParams = ['role_id' => ROLE_SALARIE];
        
        if (!empty($where)) {
            $query .= " AND $where";
            $queryParams = array_merge($queryParams, $params);
        }
        
        // Groupement et tri
        $query .= " GROUP BY e.id ORDER BY e.nom LIMIT :limit OFFSET :offset";
        $queryParams['limit'] = $limit;
        $queryParams['offset'] = $offset;
        
        // Exécution de la requête principale
        $companies = executeQuery($query, $queryParams)->fetchAll();
        
        // Ajouter les badges de statut pour chaque entreprise
        foreach ($companies as &$company) {
            if (isset($company['statut'])) {
                $company['statut_badge'] = getStatusBadge($company['statut']);
            }
        }
        
        // Exécution de la requête de comptage
        $countQuery = "SELECT COUNT(DISTINCT e.id) as total FROM entreprises e WHERE 1=1";
        $countParams = [];
        
        if (!empty($where)) {
            $countQuery .= " AND " . str_replace('e.', '', $where);
            $countParams = $params;
        }
        
        $countResult = executeQuery($countQuery, $countParams)->fetch();
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
        
        // Construction de l'URL pour la pagination
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
 * @param int $company_id identifiant de l'entreprise
 * @return array|false détails de l'entreprise ou false si non trouvée
 */
function getCompanyDetails($company_id) {
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide", "danger");
        return false;
    }
    
    try {
        // Récupération des informations de base de l'entreprise
        $query = "SELECT e.*, 
                  COUNT(DISTINCT p.id) as nombre_employes,
                  COUNT(DISTINCT c.id) as nombre_contrats
                  FROM entreprises e
                  LEFT JOIN personnes p ON e.id = p.entreprise_id AND p.role_id = :role_id
                  LEFT JOIN contrats c ON e.id = c.entreprise_id
                  WHERE e.id = :company_id
                  GROUP BY e.id";
        
        $company = executeQuery($query, [
            'role_id' => ROLE_SALARIE, 
            'company_id' => $company_id
        ])->fetch();
        
        if (!$company) {
            flashMessage("Entreprise non trouvée", "danger");
            return false;
        }
        
        // Ajouter le badge de statut et formater les dates
        if (isset($company['statut'])) {
            $company['statut_badge'] = getStatusBadge($company['statut']);
        }
        if (isset($company['date_creation'])) {
            $company['date_creation_formatee'] = formatDate($company['date_creation']);
        }
        
        // Récupération des contrats actifs
        $contractsQuery = "SELECT c.*, 
                         CONCAT(p.prenom, ' ', p.nom) as responsable_nom
                         FROM contrats c
                         LEFT JOIN personnes p ON c.responsable_id = p.id
                         WHERE c.entreprise_id = :company_id AND c.date_fin >= CURDATE()
                         ORDER BY c.date_debut DESC";
        $contracts = executeQuery($contractsQuery, ['company_id' => $company_id])->fetchAll();
        
        // Formater les dates des contrats
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
        }
        
        $company['contrats_actifs'] = $contracts;
        
        // Récupération des prestations disponibles via les contrats actifs
        $servicesQuery = "SELECT DISTINCT p.*
                        FROM prestations p
                        JOIN contrats_prestations cp ON p.id = cp.prestation_id
                        JOIN contrats c ON cp.contrat_id = c.id
                        WHERE c.entreprise_id = :company_id AND c.date_fin >= CURDATE()
                        ORDER BY p.categorie, p.nom";
        $company['services_disponibles'] = executeQuery($servicesQuery, ['company_id' => $company_id])->fetchAll();
        
        // Formater les prix des services si disponibles
        foreach ($company['services_disponibles'] as &$service) {
            if (isset($service['prix'])) {
                $service['prix_formate'] = formatMoney($service['prix']);
            }
        }
        
        // Récupération des statistiques d'utilisation
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
        logSystemActivity('error', "Erreur récupération détails entreprise #$company_id: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération des informations de l'entreprise", "danger");
        return false;
    }
}

/**
 * Met à jour les informations d'une entreprise
 * 
 * @param int $company_id identifiant de l'entreprise
 * @param array $company_data données à mettre à jour
 * @return bool résultat de la mise à jour
 */
function updateCompany($company_id, $company_data) {
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id || empty($company_data)) {
        return false;
    }
    
    // Liste des champs autorisés
    $allowedFields = [
        'nom', 'adresse', 'telephone', 'email', 'site_web', 'logo_url', 
        'statut', 'siret', 'description', 'secteur_activite'
    ];
    
    // Sanitize and filter input data
    $company_data = sanitizeInput($company_data);
    $filteredData = array_intersect_key($company_data, array_flip($allowedFields));
    
    if (empty($filteredData)) {
        return false;
    }
    
    try {
        // Utilisation de la fonction updateRow du fichier db.php
        $result = updateRow('entreprises', $filteredData, 'id = :id', ['id' => $company_id]);
        
        if ($result) {
            logBusinessOperation($_SESSION['user_id'] ?? null, 'update_company', "Mise à jour entreprise #$company_id");
            flashMessage("Les informations de l'entreprise ont été mises à jour avec succès", "success");
        }
        
        return $result > 0;
        
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur mise à jour entreprise: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la mise à jour de l'entreprise", "danger");
        return false;
    }
}

/**
 * Ajoute une nouvelle entreprise
 * 
 * @param array $company_data données de l'entreprise à créer
 * @return int|false identifiant de la nouvelle entreprise ou false en cas d'erreur
 */
function addCompany($company_data) {
    // Sanitize input data
    $company_data = sanitizeInput($company_data);
    
    // Validation des données requises
    if (empty($company_data['nom']) || empty($company_data['email'])) {
        flashMessage("Le nom et l'email de l'entreprise sont obligatoires", "danger");
        return false;
    }
    
    // Liste des champs autorisés
    $allowedFields = [
        'nom', 'adresse', 'telephone', 'email', 'site_web', 'logo_url', 
        'statut', 'siret', 'description', 'secteur_activite'
    ];
    
    // Filtrage des données
    $filteredData = array_intersect_key($company_data, array_flip($allowedFields));
    
    // Définition des valeurs par défaut
    if (!isset($filteredData['statut'])) {
        $filteredData['statut'] = 'actif';
    }
    
    try {
        // Utilisation de la fonction insertRow du fichier db.php
        $companyId = insertRow('entreprises', $filteredData);
        
        if ($companyId) {
            logBusinessOperation($_SESSION['user_id'] ?? null, 'add_company', "Création entreprise #$companyId: {$filteredData['nom']}");
            flashMessage("L'entreprise {$filteredData['nom']} a été ajoutée avec succès", "success");
        }
        
        return $companyId;
        
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur création entreprise: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la création de l'entreprise", "danger");
        return false;
    }
}

/**
 * Récupère les contrats d'une entreprise
 * 
 * @param int $company_id identifiant de l'entreprise
 * @param string $status filtre par statut (active, expired, all)
 * @return array liste des contrats
 */
function getCompanyContracts($company_id, $status = 'active') {
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide", "danger");
        return [];
    }
    
    // Construction de la condition WHERE de base
    $where = "c.entreprise_id = " . $company_id;
    
    // Filtrage par statut
    if ($status === 'active') {
        $where .= " AND c.date_fin >= CURDATE()";
    } else if ($status === 'expired') {
        $where .= " AND c.date_fin < CURDATE()";
    }
    
    try {
        // Utilisation d'une requête personnalisée car fetchAll ne supporte pas
        // directement les jointures et agrégations complexes
        $query = "SELECT c.*, 
                CONCAT(p.prenom, ' ', p.nom) as responsable_nom,
                COUNT(cp.prestation_id) as nombre_prestations
                FROM contrats c
                LEFT JOIN personnes p ON c.responsable_id = p.id
                LEFT JOIN contrats_prestations cp ON c.id = cp.contrat_id
                WHERE " . $where . "
                GROUP BY c.id ORDER BY c.date_debut DESC";
        
        $contracts = executeQuery($query)->fetchAll(PDO::FETCH_ASSOC);
        
        // Pour chaque contrat, récupérer les prestataires associés
        foreach ($contracts as &$contract) {
            $providersQuery = "SELECT DISTINCT CONCAT(pr.prenom, ' ', pr.nom) as nom,
                            pr.id as personne_id, 
                            COUNT(r.id) as nombre_prestations
                            FROM personnes pr
                            JOIN rendez_vous r ON pr.id = r.personne_id
                            JOIN prestations p ON r.prestation_id = p.id
                            JOIN contrats_prestations cp ON p.id = cp.prestation_id
                            WHERE cp.contrat_id = ?
                            GROUP BY pr.id
                            ORDER BY nombre_prestations DESC
                            LIMIT 5";
            
            $contract['prestataires'] = executeQuery($providersQuery, [$contract['id']])->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $contracts;
        
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération contrats: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération des contrats", "danger");
        return [];
    }
}

/**
 * Récupère les employés d'une entreprise
 * 
 * @param int $company_id identifiant de l'entreprise
 * @param int $page numéro de page
 * @param int $limit nombre d'éléments par page
 * @param string $search terme de recherche
 * @return array liste des employés et infos de pagination
 */
function getCompanyEmployees($company_id, $page = 1, $limit = 20, $search = '') {
    // Sanitize inputs
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);
    $search = sanitizeInput($search);
    
    if (!$company_id) {
        return [
            'employees' => [],
            'pagination' => [
                'current' => $page,
                'limit' => $limit,
                'total' => 0,
                'totalPages' => 0
            ],
            'pagination_html' => ''
        ];
    }
    
    // Construction de la requête principale avec les jointures nécessaires
    $query = "SELECT p.id, p.nom, p.prenom, p.email, p.telephone, p.photo_url, p.statut, 
              p.date_creation, p.derniere_connexion
              FROM personnes p
              WHERE p.entreprise_id = ? AND p.role_id = ?";
    
    $params = [$company_id, ROLE_SALARIE];
    
    // Ajout de la recherche si spécifiée
    if (!empty($search)) {
        $searchTerm = "%" . $search . "%";
        $query .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR p.email LIKE ?)";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Calcul de l'offset pour la pagination
    $offset = ($page - 1) * $limit;
    
    // Ajout du tri et de la pagination
    $query .= " ORDER BY p.nom, p.prenom ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    // Exécution de la requête principale
    $employees = executeQuery($query, $params)->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque employé, ajouter le badge de statut et formater les dates
    foreach ($employees as &$employee) {
        // Ajout du badge de statut
        if (isset($employee['statut'])) {
            $employee['statut_badge'] = getStatusBadge($employee['statut']);
        }
        
        // Formatage des dates
        if (isset($employee['date_creation'])) {
            $employee['date_creation_formatee'] = formatDate($employee['date_creation']);
        }
        if (isset($employee['derniere_connexion'])) {
            $employee['derniere_connexion_formatee'] = formatDate($employee['derniere_connexion']);
        }
        
        // Récupération du nombre de rendez-vous
        $appointmentsQuery = "SELECT 
                            COUNT(id) as total,
                            SUM(CASE WHEN date_rdv >= CURDATE() THEN 1 ELSE 0 END) as a_venir
                            FROM rendez_vous
                            WHERE personne_id = ?";
        $appointmentsInfo = executeQuery($appointmentsQuery, [$employee['id']])->fetch(PDO::FETCH_ASSOC);
        
        $employee['rendez_vous'] = [
            'total' => $appointmentsInfo['total'] ?? 0,
            'a_venir' => $appointmentsInfo['a_venir'] ?? 0
        ];
    }
    
    // Exécution de la requête de comptage
    $countQuery = "SELECT COUNT(id) as total FROM personnes WHERE entreprise_id = ? AND role_id = ?";
    $countParams = [$company_id, ROLE_SALARIE];
    
    if (!empty($search)) {
        $countQuery .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
        $countParams = array_merge($countParams, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    $total = executeQuery($countQuery, $countParams)->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calcul des informations de pagination
    $totalPages = ceil($total / $limit);
    
    // Préparer les données pour renderPagination
    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];
    
    // Construction de l'URL pour la pagination
    $urlPattern = "?company_id=$company_id&page={page}";
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
 * Ajoute un employé à une entreprise
 * 
 * @param int $company_id identifiant de l'entreprise
 * @param array $employee_data données de l'employé
 * @return int|false identifiant du nouvel employé ou false en cas d'erreur
 */
function addCompanyEmployee($company_id, $employee_data) {
    // Validation de l'ID d'entreprise et des données requises
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $employee_data = sanitizeInput($employee_data);
    
    if (!$company_id || empty($employee_data['nom']) || empty($employee_data['prenom']) || empty($employee_data['email'])) {
        flashMessage("ID d'entreprise invalide ou données employé incomplètes", "danger");
        return false;
    }
    
    // Vérification que l'entreprise existe
    $company = fetchOne('entreprises', "id = $company_id");
    if (!$company) {
        flashMessage("Entreprise non trouvée", "danger");
        return false;
    }
    
    // Vérification que l'email n'est pas déjà utilisé
    $existingUser = fetchOne('personnes', "email = '" . $employee_data['email'] . "'");
    if ($existingUser) {
        flashMessage("Cette adresse email est déjà utilisée", "danger");
        return false; 
    }
    
    // Génération d'un mot de passe temporaire si non fourni
    if (empty($employee_data['mot_de_passe'])) {
        $employee_data['mot_de_passe'] = bin2hex(random_bytes(4)); // 8 caractères aléatoires
    }
    
    // Hashage du mot de passe
    $passwordHash = password_hash($employee_data['mot_de_passe'], PASSWORD_DEFAULT);
    
    try {
        // Préparation des données pour l'insertion
        $insertData = [
            'nom' => $employee_data['nom'],
            'prenom' => $employee_data['prenom'],
            'email' => $employee_data['email'],
            'telephone' => $employee_data['telephone'] ?? null,
            'entreprise_id' => $company_id,
            'role_id' => ROLE_SALARIE,
            'mot_de_passe' => $passwordHash,
            'statut' => $employee_data['statut'] ?? 'actif',
            'date_creation' => date('Y-m-d H:i:s')
        ];
        
        // Insertion de l'employé
        $employeeId = insertRow('personnes', $insertData);
        
        if ($employeeId) {
            logBusinessOperation($_SESSION['user_id'] ?? null, 'add_employee', 
                "Ajout employé #{$employeeId} ({$insertData['prenom']} {$insertData['nom']}) à l'entreprise #{$company_id}");
            
            flashMessage("L'employé a été ajouté avec succès", "success");
            
            // TODO: Envoyer un email de bienvenue avec les identifiants
        } else {
            flashMessage("Erreur lors de l'ajout de l'employé", "danger");
        }
        
        return $employeeId;
        
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur ajout employé: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de l'ajout de l'employé", "danger");
        return false;
    }
}

/**
 * Récupère les statistiques d'utilisation d'une entreprise
 * 
 * @param int $company_id identifiant de l'entreprise
 * @param string|null $start_date date de début (format Y-m-d)
 * @param string|null $end_date date de fin (format Y-m-d)
 * @return array statistiques d'utilisation
 */
function getCompanyStats($company_id, $start_date = null, $end_date = null) {
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id) {
        flashMessage("ID d'entreprise invalide", "danger");
        return [];
    }
    
    // Définition des dates par défaut si non spécifiées
    if (!$start_date) {
        $start_date = date('Y-m-d', strtotime('-3 months'));
    }
    
    if (!$end_date) {
        $end_date = date('Y-m-d');
    }
    
    try {
        // Statistiques générales
        $statsQuery = "SELECT 
                     COUNT(DISTINCT p.id) as total_employes,
                     COUNT(DISTINCT r.id) as total_rdv,
                     COUNT(DISTINCT CASE WHEN r.statut = 'termine' THEN r.id END) as rdv_termines,
                     COUNT(DISTINCT CASE WHEN r.statut = 'annule' THEN r.id END) as rdv_annules,
                     COUNT(DISTINCT CASE WHEN r.date_rdv > CURDATE() THEN r.id END) as rdv_futurs,
                     COUNT(DISTINCT CASE WHEN p.derniere_connexion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN p.id END) as employes_actifs
                     FROM entreprises e
                     LEFT JOIN personnes p ON e.id = p.entreprise_id AND p.role_id = :role_id
                     LEFT JOIN rendez_vous r ON p.id = r.personne_id AND r.date_rdv BETWEEN :start_date AND :end_date
                     WHERE e.id = :company_id";
        
        $stats = executeQuery($statsQuery, [
            'role_id' => ROLE_SALARIE, 
            'start_date' => $start_date, 
            'end_date' => $end_date, 
            'company_id' => $company_id
        ])->fetch(PDO::FETCH_ASSOC);
        
        // Répartition des rendez-vous par type de prestation
        $typeStatsQuery = "SELECT pr.type, COUNT(r.id) as count
                         FROM rendez_vous r
                         JOIN personnes p ON r.personne_id = p.id
                         JOIN prestations pr ON r.prestation_id = pr.id
                         WHERE p.entreprise_id = :company_id AND p.role_id = :role_id 
                         AND r.date_rdv BETWEEN :start_date AND :end_date
                         GROUP BY pr.type
                         ORDER BY count DESC";
        
        $stats['repartition_par_type'] = executeQuery($typeStatsQuery, [
            'company_id' => $company_id, 
            'role_id' => ROLE_SALARIE, 
            'start_date' => $start_date, 
            'end_date' => $end_date
        ])->fetchAll(PDO::FETCH_ASSOC);
        
        // Évolution mensuelle des rendez-vous
        $monthlyStatsQuery = "SELECT 
                            DATE_FORMAT(r.date_rdv, '%Y-%m') as mois,
                            COUNT(r.id) as total,
                            COUNT(DISTINCT r.personne_id) as employes_distincts
                            FROM rendez_vous r
                            JOIN personnes p ON r.personne_id = p.id
                            WHERE p.entreprise_id = :company_id AND p.role_id = :role_id 
                            AND r.date_rdv BETWEEN :start_date AND :end_date
                            GROUP BY DATE_FORMAT(r.date_rdv, '%Y-%m')
                            ORDER BY mois";
        
        $monthlyStats = executeQuery($monthlyStatsQuery, [
            'company_id' => $company_id, 
            'role_id' => ROLE_SALARIE, 
            'start_date' => $start_date, 
            'end_date' => $end_date
        ])->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les dates pour l'évolution mensuelle
        foreach ($monthlyStats as &$month) {
            if (isset($month['mois'])) {
                $month['mois_formate'] = date('F Y', strtotime($month['mois'] . '-01'));
            }
        }
        
        $stats['evolution_mensuelle'] = $monthlyStats;
        
        // Top des prestations les plus demandées
        $topServicesQuery = "SELECT pr.id, pr.nom, pr.type, COUNT(r.id) as count
                           FROM rendez_vous r
                           JOIN personnes p ON r.personne_id = p.id
                           JOIN prestations pr ON r.prestation_id = pr.id
                           WHERE p.entreprise_id = :company_id AND p.role_id = :role_id 
                           AND r.date_rdv BETWEEN :start_date AND :end_date
                           GROUP BY pr.id
                           ORDER BY count DESC
                           LIMIT 5";
        
        $stats['top_prestations'] = executeQuery($topServicesQuery, [
            'company_id' => $company_id, 
            'role_id' => ROLE_SALARIE, 
            'start_date' => $start_date, 
            'end_date' => $end_date
        ])->fetchAll(PDO::FETCH_ASSOC);
        
        // Ajouter les dates formatées pour l'interface
        $stats['periode'] = [
            'debut' => formatDate($start_date, 'd/m/Y'),
            'fin' => formatDate($end_date, 'd/m/Y')
        ];
        
        return $stats;
        
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération statistiques entreprise: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération des statistiques", "danger");
        return [];
    }
}

/**
 * Récupère les activités récentes d'une entreprise
 * 
 * @param int $company_id identifiant de l'entreprise
 * @param int $limit nombre maximum d'activités à récupérer
 * @return array liste des activités récentes
 */
function getCompanyRecentActivity($company_id, $limit = 10) {
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $limit = min(50, max(1, (int)$limit)); // Limiter entre 1 et 50 activités
    
    if (!$company_id) {
        flashMessage("ID d'entreprise invalide", "danger");
        return [];
    }
    
    try {
        // Requête assez spécifique qui ne peut pas utiliser directement fetchAll de db.php
        $query = "SELECT l.id, l.action, l.details, l.created_at, 
                CONCAT(p.prenom, ' ', p.nom) as utilisateur
                FROM logs l
                JOIN personnes p ON l.personne_id = p.id
                WHERE p.entreprise_id = :company_id
                ORDER BY l.created_at DESC
                LIMIT :limit";
        
        $activities = executeQuery($query, [
            'company_id' => $company_id,
            'limit' => $limit
        ])->fetchAll();
        
        // Formatage des dates et ajout d'icônes pour les activités
        foreach ($activities as &$activity) {
            $activity['date_formatee'] = formatDate($activity['created_at']);
            
            // Détermination de l'icône en fonction du type d'action
            $actionIcons = [
                'login' => 'fas fa-sign-in-alt',
                'logout' => 'fas fa-sign-out-alt',
                'reservation' => 'fas fa-calendar-check',
                'update_profile' => 'fas fa-user-edit',
                'payment' => 'fas fa-credit-card'
            ];
            
            $activity['icone'] = $actionIcons[$activity['action']] ?? 'fas fa-history';
        }
        
        return $activities;
        
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération activités: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération des activités récentes", "danger");
        return [];
    }
}

/**
 * Récupère les factures d'une entreprise
 * 
 * @param int $company_id identifiant de l'entreprise
 * @param string|null $start_date date de début (format Y-m-d)
 * @param string|null $end_date date de fin (format Y-m-d)
 * @return array liste des factures
 */
function getCompanyInvoices($company_id, $start_date = null, $end_date = null) {
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide", "danger");
        return [];
    }
    
    try {
        // Construction de la requête de base
        $query = "SELECT f.id, f.reference, f.date_emission, f.date_echeance, 
                f.montant_ht, f.montant_ttc, f.statut, f.contrat_id,
                c.reference as contrat_reference
                FROM factures f
                JOIN contrats c ON f.contrat_id = c.id
                WHERE c.entreprise_id = :company_id";
        
        $params = ['company_id' => $company_id];
        
        // Ajout des filtres de date si spécifiés
        if ($start_date) {
            $query .= " AND f.date_emission >= :start_date";
            $params['start_date'] = $start_date;
        }
        
        if ($end_date) {
            $query .= " AND f.date_emission <= :end_date";
            $params['end_date'] = $end_date;
        }
        
        $query .= " ORDER BY f.date_emission DESC";
        
        $invoices = executeQuery($query, $params)->fetchAll();
        
        // Formater les dates et les montants pour l'affichage
        foreach ($invoices as &$invoice) {
            // Formater les dates
            if (isset($invoice['date_emission'])) {
                $invoice['date_emission_formatee'] = formatDate($invoice['date_emission'], 'd/m/Y');
            }
            if (isset($invoice['date_echeance'])) {
                $invoice['date_echeance_formatee'] = formatDate($invoice['date_echeance'], 'd/m/Y');
            }
            
            // Formater les montants
            if (isset($invoice['montant_ht'])) {
                $invoice['montant_ht_formate'] = formatMoney($invoice['montant_ht']);
            }
            if (isset($invoice['montant_ttc'])) {
                $invoice['montant_ttc_formate'] = formatMoney($invoice['montant_ttc']);
            }
            
            // Ajouter le badge de statut
            if (isset($invoice['statut'])) {
                $invoice['statut_badge'] = getStatusBadge($invoice['statut']);
            }
        }
        
        return $invoices;
        
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur récupération factures: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la récupération des factures", "danger");
        return [];
    }
}

/**
 * Ajoute un nouveau contrat pour une entreprise
 * 
 * @param int $company_id identifiant de l'entreprise
 * @param array $contract_data données du contrat à créer
 * @return int|false identifiant du nouveau contrat ou false en cas d'erreur
 */
function addCompanyContract($company_id, $contract_data) {
    // Validation de l'ID et des données
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
            flashMessage("Des champs obligatoires sont manquants", "danger");
            return false;
        }
    }
    
    // Liste des champs autorisés pour le contrat principal
    $allowedFields = [
        'date_debut', 'date_fin', 'montant_mensuel', 'nombre_salaries',
        'type_contrat', 'statut', 'conditions_particulieres', 'responsable_id'
    ];
    
    // Filtrage des données
    $filteredData = array_intersect_key($contract_data, array_flip($allowedFields));
    // Ajout de l'ID de l'entreprise
    $filteredData['entreprise_id'] = $company_id;
    // Statut par défaut
    if (!isset($filteredData['statut'])) {
        $filteredData['statut'] = 'actif';
    }
    
    try {
        // Début de transaction car l'opération touche plusieurs tables
        beginTransaction();
        
        // Insertion du contrat principal
        $contractId = insertRow('contrats', $filteredData);
        
        if (!$contractId) {
            // Si l'insertion échoue, annuler la transaction
            rollbackTransaction();
            flashMessage("Impossible de créer le contrat", "danger");
            return false;
        }
        
        // Si des services sont spécifiés, les associer au contrat
        if (!empty($contract_data['services']) && is_array($contract_data['services'])) {
            foreach ($contract_data['services'] as $serviceId) {
                $serviceId = filter_var($serviceId, FILTER_VALIDATE_INT);
                if ($serviceId) {
                    $linkData = [
                        'contrat_id' => $contractId,
                        'prestation_id' => $serviceId,
                        'date_ajout' => date('Y-m-d')
                    ];
                    
                    if (!insertRow('contrats_prestations', $linkData)) {
                        // Si une association échoue, annuler la transaction
                        rollbackTransaction();
                        flashMessage("Impossible d'associer les services au contrat", "danger");
                        return false;
                    }
                }
            }
        }
        
        // Valider la transaction si tout s'est bien passé
        commitTransaction();
        
        // Journalisation et notification
        logBusinessOperation($_SESSION['user_id'] ?? null, 'add_contract', "Création contrat #$contractId pour entreprise #$company_id");
        flashMessage("Le contrat a été créé avec succès", "success");
        
        return $contractId;
        
    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        rollbackTransaction();
        logSystemActivity('error', "Erreur création contrat: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la création du contrat", "danger");
        return false;
    }
}

/**
 * Met à jour le statut d'une entreprise
 * 
 * @param int $company_id identifiant de l'entreprise
 * @param string $status nouveau statut (actif, inactif, suspendu)
 * @return bool résultat de la mise à jour
 */
function updateCompanyStatus($company_id, $status) {
    // Validation des paramètres
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $status = sanitizeInput($status);
    
    if (!$company_id || !in_array($status, ['actif', 'inactif', 'suspendu'])) {
        flashMessage("Statut invalide ou identifiant d'entreprise incorrect", "danger");
        return false;
    }
    
    try {
        // Utilisation de updateRow pour la mise à jour
        $result = updateRow('entreprises', ['statut' => $status], 'id = :id', ['id' => $company_id]);
        
        if ($result) {
            $badge = getStatusBadge($status);
            $statusName = strip_tags($badge); // On récupère le texte sans le HTML
            
            logBusinessOperation($_SESSION['user_id'] ?? null, 'update_company_status', "Modification statut entreprise #$company_id: $statusName");
            flashMessage("Le statut de l'entreprise a été mis à jour: $statusName", "success");
            
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur mise à jour statut entreprise: " . $e->getMessage());
        flashMessage("Une erreur est survenue lors de la mise à jour du statut", "danger");
        return false;
    }
}
?>
