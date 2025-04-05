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
function getCompaniesList($page = 1, $limit = 20, $search = '')
{
    // Sanitize inputs
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);
    $search = sanitizeInput($search);

    try {
        $where = '1=1'; // Condition de base
        $params = [];

        // Ajout de la recherche si spécifiée
        if (!empty($search)) {
            $searchTerm = "%" . $search . "%";
            // Appliquer la recherche sur les colonnes existantes
            $where .= " AND (e.nom LIKE :search OR e.email LIKE :search OR e.adresse LIKE :search OR e.ville LIKE :search)";
            $params['search'] = $searchTerm;
        }

        $offset = ($page - 1) * $limit;

        // Sélection des colonnes existantes et comptages
        $query = "SELECT e.id, e.nom, e.siret, e.adresse, e.code_postal, e.ville, e.telephone, e.email, e.site_web, e.logo_url, e.taille_entreprise, e.secteur_activite, e.date_creation, e.created_at, e.updated_at,
                  COUNT(DISTINCT p.id) as nombre_employes,
                  COUNT(DISTINCT c.id) as nombre_contrats_actifs -- Renommé pour clarté
                  FROM entreprises e 
                  LEFT JOIN personnes p ON e.id = p.entreprise_id AND p.role_id = :role_id
                  LEFT JOIN contrats c ON e.id = c.entreprise_id AND (c.date_fin IS NULL OR c.date_fin >= CURDATE()) AND c.statut = 'actif'
                  WHERE " . $where; // Appliquer la condition WHERE ici

        $queryParams = ['role_id' => ROLE_SALARIE];
        $queryParams = array_merge($queryParams, $params); // Fusionner les paramètres

        // Groupement par toutes les colonnes non agrégées de la table entreprises
        $query .= " GROUP BY e.id, e.nom, e.siret, e.adresse, e.code_postal, e.ville, e.telephone, e.email, e.site_web, e.logo_url, e.taille_entreprise, e.secteur_activite, e.date_creation, e.created_at, e.updated_at
                    ORDER BY e.nom LIMIT :limit OFFSET :offset";
        $queryParams['limit'] = $limit;
        $queryParams['offset'] = $offset;

        // Exécution de la requête principale
        $companies = executeQuery($query, $queryParams)->fetchAll();

        // Exécution de la requête de comptage
        $countQuery = "SELECT COUNT(DISTINCT e.id) as total FROM entreprises e WHERE " . $where;
        $countResult = executeQuery($countQuery, $params)->fetch(); // Utiliser $params pour le comptage
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
function getCompanyDetails($company_id)
{
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide", "danger");
        return false;
    }

    try {
        // Récupération des informations de base de l'entreprise et comptages
        // Sélectionne e.* et les comptes agrégés
        $query = "SELECT e.*, 
                  COUNT(DISTINCT p.id) as nombre_employes,
                  COUNT(DISTINCT c.id) as nombre_contrats_actifs -- Renommé pour clarté
                  FROM entreprises e
                  LEFT JOIN personnes p ON e.id = p.entreprise_id AND p.role_id = :role_id
                  LEFT JOIN contrats c ON e.id = c.entreprise_id AND (c.date_fin IS NULL OR c.date_fin >= CURDATE()) AND c.statut = 'actif'
                  WHERE e.id = :company_id
                  -- Group by toutes les colonnes de e.* pour être compatible SQL standard
                  GROUP BY e.id, e.nom, e.siret, e.adresse, e.code_postal, e.ville, e.telephone, e.email, e.site_web, e.logo_url, e.taille_entreprise, e.secteur_activite, e.date_creation, e.created_at, e.updated_at";

        $company = executeQuery($query, [
            'role_id' => ROLE_SALARIE,
            'company_id' => $company_id
        ])->fetch();

        if (!$company) {
            flashMessage("Entreprise non trouvée", "danger");
            return false;
        }

        // Formater les dates
        if (isset($company['date_creation'])) {
            // Utiliser la bonne clé de colonne 'date_creation' si elle existe ou 'created_at' si c'est le standard
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


        // Récupération des contrats actifs (semble correct)
        $contractsQuery = "SELECT c.*
                         FROM contrats c
                         WHERE c.entreprise_id = :company_id AND (c.date_fin IS NULL OR c.date_fin >= CURDATE()) AND c.statut = 'actif'
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
            // Générer référence contrat
            $contract['reference'] = 'CT-' . str_pad($contract['id'], 6, '0', STR_PAD_LEFT);
        }
        $company['contrats_actifs'] = $contracts;

        // Récupération des prestations disponibles via les contrats actifs (semble correct)
        $servicesQuery = "SELECT DISTINCT p.*
                        FROM prestations p
                        JOIN contrats_prestations cp ON p.id = cp.prestation_id
                        JOIN contrats c ON cp.contrat_id = c.id
                        WHERE c.entreprise_id = :company_id AND (c.date_fin IS NULL OR c.date_fin >= CURDATE()) AND c.statut = 'actif'
                        ORDER BY p.categorie, p.nom";
        $company['services_disponibles'] = executeQuery($servicesQuery, ['company_id' => $company_id])->fetchAll();

        // Formater les prix des services si disponibles (semble correct)
        foreach ($company['services_disponibles'] as &$service) {
            if (isset($service['prix'])) {
                $service['prix_formate'] = formatMoney($service['prix']);
            }
        }

        // Récupération des statistiques d'utilisation (semble correct)
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
function updateCompany($company_id, $company_data)
{
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id || empty($company_data)) {
        return false;
    }

    // Liste des champs autorisés de la table 'entreprises'
    $allowedFields = [
        'nom',
        'siret',
        'adresse',
        'code_postal',
        'ville',
        'telephone',
        'email',
        'site_web',
        'logo_url',
        'taille_entreprise',
        'secteur_activite',
        'date_creation'
        // 'statut', // Colonne inexistante
        // 'description' // Colonne inexistante
    ];

    // Sanitize and filter input data
    $company_data = sanitizeInput($company_data);
    $filteredData = array_intersect_key($company_data, array_flip($allowedFields));

    if (empty($filteredData)) {
        flashMessage("Aucune donnée valide à mettre à jour.", "warning");
        return false;
    }

    try {
        // Utilisation de la fonction updateRow du fichier db.php
        $result = updateRow('entreprises', $filteredData, 'id = :id', ['id' => $company_id]);

        if ($result) {
            // Vérification plus stricte de l'ID utilisateur pour le log
            $userIdForLog = (isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) && $_SESSION['user_id'] > 0)
                ? $_SESSION['user_id']
                : null;
            logBusinessOperation($userIdForLog, 'update_company', "Mise à jour entreprise #$company_id");
            flashMessage("Les informations de l'entreprise ont été mises à jour avec succès", "success");
        } else {
            flashMessage("Aucune modification n'a été apportée.", "info");
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
function addCompany($company_data)
{
    // Sanitize input data
    $company_data = sanitizeInput($company_data);

    // Validation des données requises
    if (empty($company_data['nom']) || empty($company_data['email'])) {
        flashMessage("Le nom et l'email de l'entreprise sont obligatoires", "danger");
        return false;
    }

    // Vérifier l'unicité de l'email si nécessaire
    $existing = fetchOne('entreprises', 'email = :email', ['email' => $company_data['email']]);
    if ($existing) {
        flashMessage("Cette adresse email est déjà utilisée par une autre entreprise.", "danger");
        return false;
    }
    // Vérifier l'unicité du SIRET si fourni
    if (!empty($company_data['siret'])) {
        $existingSiret = fetchOne('entreprises', 'siret = :siret', ['siret' => $company_data['siret']]);
        if ($existingSiret) {
            flashMessage("Ce numéro SIRET est déjà utilisé par une autre entreprise.", "danger");
            return false;
        }
    }


    // Liste des champs autorisés de la table 'entreprises'
    $allowedFields = [
        'nom',
        'siret',
        'adresse',
        'code_postal',
        'ville',
        'telephone',
        'email',
        'site_web',
        'logo_url',
        'taille_entreprise',
        'secteur_activite',
        'date_creation'
        // 'statut', // Colonne inexistante
        // 'description' // Colonne inexistante
    ];

    // Filtrage des données
    $filteredData = array_intersect_key($company_data, array_flip($allowedFields));

    // Ajouter la date de création si non fournie (created_at est automatique)
    if (empty($filteredData['date_creation'])) {
        $filteredData['date_creation'] = date('Y-m-d');
    }

    // Définition des valeurs par défaut (pour colonnes qui n'existent pas, commenté)
    /* if (!isset($filteredData['statut'])) {
        $filteredData['statut'] = 'actif';
    } */

    try {
        // Utilisation de la fonction insertRow du fichier db.php
        $companyId = insertRow('entreprises', $filteredData);

        if ($companyId) {
            // Vérification plus stricte de l'ID utilisateur pour le log
            $userIdForLog = (isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) && $_SESSION['user_id'] > 0)
                ? $_SESSION['user_id']
                : null;
            logBusinessOperation($userIdForLog, 'add_company', "Création entreprise #$companyId: {$filteredData['nom']}");
            flashMessage("L'entreprise {$filteredData['nom']} a été ajoutée avec succès", "success");
        } else {
            flashMessage("Erreur lors de l'ajout de l'entreprise.", "danger");
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
 * @param string $status filtre par statut (active, expired, all, ou autre statut de la table)
 * @return array liste des contrats
 */
function getCompanyContracts($company_id, $status = 'active')
{
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide", "danger");
        return [];
    }

    // Construction de la condition WHERE de base
    $where = "c.entreprise_id = :company_id";
    $params = [':company_id' => $company_id];

    // Filtrage par statut
    if ($status === 'active') {
        $where .= " AND (c.date_fin IS NULL OR c.date_fin >= CURDATE()) AND c.statut = 'actif'";
    } else if ($status === 'expired') {
        // Statut 'expire' dans la table 'contrats' est un ENUM, on filtre sur celui-ci
        // On peut aussi ajouter une condition sur la date de fin si nécessaire
        $where .= " AND c.statut = 'expire'";
        // Alternative: $where .= " AND c.date_fin < CURDATE()"; // Si le statut 'expire' n'est pas toujours à jour
    } else if ($status !== null && $status !== 'all') {
        $allowed_statuses = ['actif', 'expire', 'resilie', 'en_attente']; // Statuts de la table contrats
        if (in_array($status, $allowed_statuses)) {
            $where .= " AND c.statut = :status";
            $params[':status'] = $status;
        } else {
            // Statut invalide, on ne filtre pas ou on retourne une erreur ? Pour l'instant, on ignore.
            // flashMessage("Statut de filtre invalide: $status", "warning");
        }
    } // Si $status est null ou 'all', on récupère tout sans filtre de statut

    try {
        // Requête pour récupérer les contrats et le nombre de prestations associées
        $query = "SELECT c.*, COUNT(cp.prestation_id) as nombre_prestations
                FROM contrats c
                LEFT JOIN contrats_prestations cp ON c.id = cp.contrat_id
                WHERE " . $where . "
                  -- Group by toutes les colonnes de c.*
                  GROUP BY c.id, c.entreprise_id, c.date_debut, c.date_fin, c.montant_mensuel,
                           c.nombre_salaries, c.type_contrat, c.statut, c.conditions_particulieres,
                           c.created_at, c.updated_at
                ORDER BY c.date_debut DESC";

        $contracts = executeQuery($query, $params)->fetchAll(PDO::FETCH_ASSOC);

        // Pour chaque contrat, générer une référence si besoin et récupérer les prestataires associés
        foreach ($contracts as &$contract) {
            // Générer une référence
            $contract['reference'] = 'CT-' . str_pad($contract['id'], 6, '0', STR_PAD_LEFT);

            // Récupération des prestataires associés (limité pour performance)
            // Cette requête est complexe et peut être optimisée ou retirée si non essentielle pour la vue liste
            $providersQuery = "SELECT DISTINCT CONCAT(pr.prenom, ' ', pr.nom) as nom,
                            pr.id as personne_id, 
                            COUNT(r.id) as nombre_prestations_par_prestataire
                            FROM personnes pr
                            JOIN rendez_vous r ON pr.id = r.personne_id
                            JOIN prestations p ON r.prestation_id = p.id
                            JOIN contrats_prestations cp ON p.id = cp.prestation_id AND cp.contrat_id = :contrat_id
                            WHERE r.personne_id IS NOT NULL -- S'assurer qu'il y a un prestataire lié au RDV
                            GROUP BY pr.id, nom
                            ORDER BY nombre_prestations_par_prestataire DESC
                            LIMIT 5";

            $contract['prestataires'] = executeQuery($providersQuery, [':contrat_id' => $contract['id']])->fetchAll(PDO::FETCH_ASSOC);
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
function getCompanyEmployees($company_id, $page = 1, $limit = 20, $search = '')
{
    // Sanitize inputs
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $page = (int)sanitizeInput($page);
    $limit = (int)sanitizeInput($limit);
    $search = sanitizeInput($search);

    if (!$company_id) {
        // Retourner une structure vide cohérente
        return [
            'employees' => [],
            'pagination' => [
                'current' => 1,
                'limit' => $limit,
                'total' => 0,
                'totalPages' => 0
            ],
            'pagination_html' => ''
        ];
    }

    // Construction de la requête principale avec les jointures nécessaires
    // Sélectionner les colonnes existantes
    $query = "SELECT p.id, p.nom, p.prenom, p.email, p.telephone, p.photo_url, p.statut, 
              p.created_at, p.derniere_connexion
              FROM personnes p
              WHERE p.entreprise_id = :company_id AND p.role_id = :role_id";

    $params = [':company_id' => $company_id, ':role_id' => ROLE_SALARIE];

    // Ajout de la recherche si spécifiée
    if (!empty($search)) {
        $searchTerm = "%" . $search . "%";
        $query .= " AND (p.nom LIKE :search OR p.prenom LIKE :search OR p.email LIKE :search)";
        $params[':search'] = $searchTerm;
    }

    // Calcul de l'offset pour la pagination
    $offset = ($page - 1) * $limit;

    // Ajout du tri et de la pagination
    $query .= " ORDER BY p.nom, p.prenom ASC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    // Exécution de la requête principale
    $employees = executeQuery($query, $params)->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque employé, ajouter le badge de statut et formater les dates
    foreach ($employees as &$employee) {
        // Ajout du badge de statut
        if (isset($employee['statut'])) {
            $employee['statut_badge'] = getStatusBadge($employee['statut']);
        }

        // Formatage des dates
        if (isset($employee['created_at'])) { // Utiliser created_at
            $employee['created_at_formatee'] = formatDate($employee['created_at']); // Créer created_at_formatee
        }
        if (isset($employee['derniere_connexion'])) {
            $employee['derniere_connexion_formatee'] = formatDate($employee['derniere_connexion']);
        }

        // Récupération du nombre de rendez-vous (semble correct)
        $appointmentsQuery = "SELECT 
                            COUNT(id) as total,
                            SUM(CASE WHEN date_rdv >= CURDATE() THEN 1 ELSE 0 END) as a_venir
                            FROM rendez_vous
                            WHERE personne_id = :employee_id";
        $appointmentsInfo = executeQuery($appointmentsQuery, [':employee_id' => $employee['id']])->fetch(PDO::FETCH_ASSOC);

        $employee['rendez_vous'] = [
            'total' => $appointmentsInfo['total'] ?? 0,
            'a_venir' => $appointmentsInfo['a_venir'] ?? 0
        ];
    }

    // Exécution de la requête de comptage
    $countQuery = "SELECT COUNT(id) as total FROM personnes WHERE entreprise_id = :company_id AND role_id = :role_id";
    $countParams = [':company_id' => $company_id, ':role_id' => ROLE_SALARIE];

    if (!empty($search)) {
        $countQuery .= " AND (nom LIKE :search OR prenom LIKE :search OR email LIKE :search)";
        $countParams[':search'] = $searchTerm;
    }

    $total = executeQuery($countQuery, $countParams)->fetch(PDO::FETCH_ASSOC)['total'];

    // Calcul des informations de pagination
    $totalPages = ceil($total / $limit);
    // S'assurer que la page actuelle n'est pas supérieure au nombre total de pages
    $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));

    // Préparer les données pour renderPagination
    $paginationData = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $total,
        'perPage' => $limit
    ];

    // Construction de l'URL pour la pagination
    $urlPattern = "?company_id=$company_id&page={page}"; // company_id n'est pas nécessaire si on est dans le module company
    $urlPattern = "?page={page}";
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
function addCompanyEmployee($company_id, $employee_data)
{
    // Validation de l'ID d'entreprise et des données requises
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $employee_data = sanitizeInput($employee_data);

    if (!$company_id || empty($employee_data['nom']) || empty($employee_data['prenom']) || empty($employee_data['email'])) {
        flashMessage("ID d'entreprise invalide ou données employé incomplètes", "danger");
        return false;
    }

    // Vérification que l'entreprise existe
    $company = fetchOne('entreprises', "id = :id", [':id' => $company_id]);
    if (!$company) {
        flashMessage("Entreprise non trouvée", "danger");
        return false;
    }

    // Vérification que l'email n'est pas déjà utilisé
    $existingUser = fetchOne('personnes', "email = :email", [':email' => $employee_data['email']]);
    if ($existingUser) {
        flashMessage("Cette adresse email est déjà utilisée", "danger");
        return false;
    }

    // Génération d'un mot de passe temporaire si non fourni
    if (empty($employee_data['mot_de_passe'])) {
        $employee_data['mot_de_passe'] = bin2hex(random_bytes(4)); // 8 caractères aléatoires
        // Idéalement, stocker le mot de passe clair temporairement pour l'envoyer à l'utilisateur
        $plainPassword = $employee_data['mot_de_passe'];
    } else {
        $plainPassword = null; // Le mot de passe a été fourni
    }

    // Hashage du mot de passe
    $passwordHash = password_hash($employee_data['mot_de_passe'], PASSWORD_DEFAULT);

    try {
        // Préparation des données pour l'insertion en utilisant les colonnes existantes
        $insertData = [
            'nom' => $employee_data['nom'],
            'prenom' => $employee_data['prenom'],
            'email' => $employee_data['email'],
            'telephone' => $employee_data['telephone'] ?? null,
            'date_naissance' => $employee_data['date_naissance'] ?? null,
            'genre' => $employee_data['genre'] ?? null,
            'photo_url' => $employee_data['photo_url'] ?? null,
            'entreprise_id' => $company_id,
            'role_id' => ROLE_SALARIE,
            'mot_de_passe' => $passwordHash,
            'statut' => $employee_data['statut'] ?? 'actif'
            // created_at et updated_at sont gérés par la DB
        ];

        // Insertion de l'employé
        $employeeId = insertRow('personnes', $insertData);

        if ($employeeId) {
            // Vérification plus stricte de l'ID utilisateur pour le log
            $userIdForLog = (isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) && $_SESSION['user_id'] > 0)
                ? $_SESSION['user_id']
                : null;
            logBusinessOperation(
                $userIdForLog,
                'add_employee',
                "Ajout employé #{$employeeId} ({$insertData['prenom']} {$insertData['nom']}) à l'entreprise #{$company_id}"
            );

            flashMessage("L'employé a été ajouté avec succès" . ($plainPassword ? ". Mot de passe temporaire : $plainPassword" : ""), "success");

            // TODO: Envoyer un email de bienvenue avec les identifiants (surtout si mot de passe généré)
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
function getCompanyStats($company_id, $start_date = null, $end_date = null)
{
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id) {
        flashMessage("ID d'entreprise invalide", "danger");
        return [];
    }

    // Définition des dates par défaut si non spécifiées
    $endDateObj = $end_date ? new DateTime($end_date) : new DateTime();
    // Assurer que la date de fin inclut toute la journée
    $endDateObj->setTime(23, 59, 59);
    $endDateSql = $endDateObj->format('Y-m-d H:i:s');

    $startDateObj = $start_date ? new DateTime($start_date) : (clone $endDateObj)->modify('-3 months')->setTime(0, 0, 0);
    $startDateSql = $startDateObj->format('Y-m-d H:i:s');


    try {
        // Statistiques générales (semble correct)
        $statsQuery = "SELECT 
                     COUNT(DISTINCT p.id) as total_employes,
                     COUNT(DISTINCT r.id) as total_rdv,
                     COUNT(DISTINCT CASE WHEN r.statut = 'termine' THEN r.id END) as rdv_termines,
                     COUNT(DISTINCT CASE WHEN r.statut = 'annule' THEN r.id END) as rdv_annules,
                     COUNT(DISTINCT CASE WHEN r.date_rdv > NOW() THEN r.id END) as rdv_futurs, -- Utiliser NOW()
                     COUNT(DISTINCT CASE WHEN p.derniere_connexion >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN p.id END) as employes_actifs
                     FROM entreprises e
                     LEFT JOIN personnes p ON e.id = p.entreprise_id AND p.role_id = :role_id
                     LEFT JOIN rendez_vous r ON p.id = r.personne_id AND r.date_rdv BETWEEN :start_date AND :end_date
                     WHERE e.id = :company_id";

        $stats = executeQuery($statsQuery, [
            'role_id' => ROLE_SALARIE,
            'start_date' => $startDateSql,
            'end_date' => $endDateSql,
            'company_id' => $company_id
        ])->fetch(PDO::FETCH_ASSOC);

        // Répartition des rendez-vous par type de prestation (semble correct)
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
            'start_date' => $startDateSql,
            'end_date' => $endDateSql
        ])->fetchAll(PDO::FETCH_ASSOC);

        // Évolution mensuelle des rendez-vous (semble correct)
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
            'start_date' => $startDateSql,
            'end_date' => $endDateSql
        ])->fetchAll(PDO::FETCH_ASSOC);

        // Formater les dates pour l'évolution mensuelle
        foreach ($monthlyStats as &$month) {
            if (isset($month['mois'])) {
                // Utiliser DateTime pour formater correctement
                try {
                    $monthDate = DateTime::createFromFormat('Y-m', $month['mois']);
                    if ($monthDate) {
                        $month['mois_formate'] = $monthDate->format('F Y'); // Ex: Mars 2024
                    } else {
                        $month['mois_formate'] = $month['mois']; // Fallback
                    }
                } catch (Exception $e) {
                    $month['mois_formate'] = $month['mois']; // Fallback
                }
            }
        }
        $stats['evolution_mensuelle'] = $monthlyStats;


        // Top des prestations les plus demandées (semble correct)
        $topServicesQuery = "SELECT pr.id, pr.nom, pr.type, COUNT(r.id) as count
                           FROM rendez_vous r
                           JOIN personnes p ON r.personne_id = p.id
                           JOIN prestations pr ON r.prestation_id = pr.id
                           WHERE p.entreprise_id = :company_id AND p.role_id = :role_id 
                           AND r.date_rdv BETWEEN :start_date AND :end_date
                           GROUP BY pr.id, pr.nom, pr.type -- Ajouter colonnes non agrégées au GROUP BY
                           ORDER BY count DESC
                           LIMIT 5";

        $stats['top_prestations'] = executeQuery($topServicesQuery, [
            'company_id' => $company_id,
            'role_id' => ROLE_SALARIE,
            'start_date' => $startDateSql,
            'end_date' => $endDateSql
        ])->fetchAll(PDO::FETCH_ASSOC);

        // Ajouter les dates formatées pour l'interface
        $stats['periode'] = [
            'debut' => formatDate($startDateSql, 'd/m/Y'),
            'fin' => formatDate($endDateSql, 'd/m/Y')
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
function getCompanyRecentActivity($company_id, $limit = 10)
{
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    $limit = min(50, max(1, (int)$limit)); // Limiter entre 1 et 50 activités

    if (!$company_id) {
        // Ne pas afficher de message flash ici, juste retourner un tableau vide
        // flashMessage("ID d'entreprise invalide", "danger");
        return [];
    }

    try {
        // Requête utilisant les colonnes existantes
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

        // Formatage des dates et ajout d'icônes pour les activités
        foreach ($activities as &$activity) {
            $activity['date_formatee'] = formatDate($activity['created_at']);

            // Détermination de l'icône en fonction du type d'action
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
                // Ajouter d'autres actions loguées si nécessaire
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
 * Récupère les factures d'une entreprise
 * 
 * @param int $company_id identifiant de l'entreprise
 * @param string|null $start_date date de début (format Y-m-d)
 * @param string|null $end_date date de fin (format Y-m-d)
 * @return array liste des factures
 */
function getCompanyInvoices($company_id, $start_date = null, $end_date = null)
{
    // Validation de l'ID
    $company_id = filter_var(sanitizeInput($company_id), FILTER_VALIDATE_INT);
    if (!$company_id) {
        flashMessage("Identifiant d'entreprise invalide", "danger");
        return [];
    }

    try {
        // Construction de la requête de base utilisant les colonnes existantes de 'factures'
        $query = "SELECT f.id, f.numero_facture, f.date_emission, f.date_echeance,
                f.montant_ht, f.montant_total, f.statut, f.devis_id
                FROM factures f
                WHERE f.entreprise_id = :company_id"; // Filtrer par entreprise_id dans factures

        $params = [':company_id' => $company_id];

        // Ajout des filtres de date si spécifiés
        if ($start_date) {
            $query .= " AND f.date_emission >= :start_date";
            // Formater la date pour SQL
            $params[':start_date'] = date('Y-m-d', strtotime($start_date));
        }

        if ($end_date) {
            $query .= " AND f.date_emission <= :end_date";
            // Formater la date pour SQL
            $params[':end_date'] = date('Y-m-d', strtotime($end_date));
        }

        $query .= " ORDER BY f.date_emission DESC";

        $invoices = executeQuery($query, $params)->fetchAll();

        // Formater les dates et les montants pour l'affichage
        foreach ($invoices as &$invoice) {
            // On ne peut pas récupérer la référence du contrat directement ici
            // $invoice['contrat_reference'] = '...';

            // Si on veut lier au devis (si pertinent)
            if ($invoice['devis_id']) {
                $invoice['devis_reference'] = 'DV-' . str_pad($invoice['devis_id'], 6, '0', STR_PAD_LEFT);
            } else {
                $invoice['devis_reference'] = 'N/A';
            }


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
            if (isset($invoice['montant_total'])) {
                $invoice['montant_total_formate'] = formatMoney($invoice['montant_total']);
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
function addCompanyContract($company_id, $contract_data)
{
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
            flashMessage("Des champs obligatoires sont manquants: " . $field, "danger");
            return false;
        }
    }

    // Liste des champs autorisés pour la table 'contrats'
    $allowedFields = [
        'date_debut',
        'date_fin',
        'montant_mensuel',
        'nombre_salaries',
        'type_contrat',
        'statut',
        'conditions_particulieres'
    ];

    // Filtrage des données
    $filteredData = array_intersect_key($contract_data, array_flip($allowedFields));
    // Ajout de l'ID de l'entreprise
    $filteredData['entreprise_id'] = $company_id;
    // Statut par défaut si non fourni
    if (!isset($filteredData['statut'])) {
        $filteredData['statut'] = 'actif';
    }
    // Vérifier que date_fin est NULL si vide
    if (isset($filteredData['date_fin']) && empty($filteredData['date_fin'])) {
        $filteredData['date_fin'] = null;
    }
    // Vérifier que montant_mensuel est NULL si vide
    if (isset($filteredData['montant_mensuel']) && $filteredData['montant_mensuel'] === '') {
        $filteredData['montant_mensuel'] = null;
    }
    // Vérifier que nombre_salaries est NULL si vide
    if (isset($filteredData['nombre_salaries']) && $filteredData['nombre_salaries'] === '') {
        $filteredData['nombre_salaries'] = null;
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

        // Si des services sont spécifiés, les associer au contrat dans 'contrats_prestations'
        if (!empty($contract_data['services']) && is_array($contract_data['services'])) {
            foreach ($contract_data['services'] as $prestationId) { // Utiliser prestationId comme nom de variable
                $prestationId = filter_var($prestationId, FILTER_VALIDATE_INT);
                if ($prestationId) {
                    // Préparer les données pour la table de liaison (colonnes existent)
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

        // Valider la transaction si tout s'est bien passé
        commitTransaction();

        // Journalisation et notification
        $contractReference = 'CT-' . str_pad($contractId, 6, '0', STR_PAD_LEFT);
        // Vérification plus stricte de l'ID utilisateur pour le log
        $userIdForLog = (isset($_SESSION['user_id']) && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) && $_SESSION['user_id'] > 0)
            ? $_SESSION['user_id']
            : null;
        logBusinessOperation($userIdForLog, 'add_contract', "Création contrat #$contractReference pour entreprise #$company_id");
        flashMessage("Le contrat $contractReference a été créé avec succès", "success");

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
 * Génère et envoie un PDF du contrat (Orchestrateur).
 * Appelle la fonction _generateContractPDFContent pour la génération effective.
 *
 * @param int $contract_id Identifiant du contrat
 * @return array Résultat de l'opération (succès/échec avant génération ou si erreur)
 */
function generateContractPDF($contract_id)
{
    // Validation de l'ID
    $contract_id = filter_var(sanitizeInput($contract_id), FILTER_VALIDATE_INT);

    if (!$contract_id) {
        return [
            'success' => false,
            'message' => "ID de contrat invalide"
        ];
    }

    try {
        // Vérifier que l'utilisateur a accès à ce contrat (si appelé depuis l'espace client)
        $entrepriseId = $_SESSION['user_entreprise'] ?? null;
        if (!$entrepriseId && isset($_SESSION['user_role']) && $_SESSION['user_role'] !== ROLE_ADMIN) {
            // Ou récupérer l'ID entreprise différemment si admin
            return ['success' => false, 'message' => "Accès non autorisé"];
        }

        // Récupérer les détails du contrat (incluant entreprise et services)
        if (!$entrepriseId) {
            // Logique pour récupérer l'ID entreprise si admin - A FAIRE
            // Exemple simple (peut nécessiter plus de vérifications) :
            $contract_base_info = fetchOne('contrats', 'id = :id', [':id' => $contract_id]);
            if (!$contract_base_info) return ['success' => false, 'message' => "Contrat non trouvé"];
            $entrepriseId = $contract_base_info['entreprise_id'];
            // L'appel suivant à getCompanyContractDetails fonctionnera avec cet ID,
            // mais il faut s'assurer que l'admin A LE DROIT de voir ce contrat spécifique.
            // La logique d'autorisation pour admin pourrait être plus complexe.
        }

        $contract = getCompanyContractDetails($entrepriseId, $contract_id);

        if (!$contract) {
            return [
                'success' => false,
                'message' => "Contrat non trouvé ou accès non autorisé"
            ];
        }

        // Appeler la fonction helper qui génère le PDF et termine le script
        _generateContractPDFContent($contract);

        // Normalement, le script s'arrête dans la fonction helper après l'envoi du PDF.
        // Ce retour ne sera atteint qu'en cas d'échec avant l'appel ou si exit() échoue (improbable).
        return [
            'success' => true,
            'message' => "PDF généré avec succès (théoriquement)"
        ];
    } catch (Exception $e) {
        logSystemActivity('error', "Erreur lors de la tentative de génération PDF contrat #$contract_id: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "Une erreur est survenue lors de la préparation de la génération du PDF."
            // Ne pas afficher $e->getMessage() à l'utilisateur final directement
        ];
    }
}

/**
 * Fonction helper pour générer le contenu PDF du contrat et l'envoyer.
 * Utilise TCPDF.
 * NOTE: Cette fonction appelle exit() après avoir envoyé le PDF.
 *
 * @param array $contract Données complètes du contrat.
 * @throws Exception Si TCPDF n'est pas trouvé ou si une erreur interne survient.
 */
function _generateContractPDFContent(array $contract)
{
    // Charger la bibliothèque TCPDF
    // Assurez-vous que TCPDF est installé (via Composer ou téléchargement)
    $tcpdfPath = __DIR__ . '/../../../vendor/tecnickcom/tcpdf/tcpdf.php'; // Chemin typique Composer
    if (!file_exists($tcpdfPath)) {
        // Si téléchargé manuellement, cherchez ailleurs ou lancez une erreur
        // Exemple : $tcpdfPath = __DIR__ . '/../../../libs/tcpdf/tcpdf.php';
        // if (!file_exists($tcpdfPath)) { ... }
        logSystemActivity('critical', "Bibliothèque TCPDF non trouvée à : $tcpdfPath");
        throw new Exception("Erreur interne: La bibliothèque PDF est manquante.");
    }
    require_once $tcpdfPath;

    // Charger les fonctions utilitaires (pour formatDate, formatMoney)
    $functionsPath = __DIR__ . '/../../shared/web-client/functions.php';
    if (!file_exists($functionsPath)) {
        logSystemActivity('critical', "Fichier de fonctions non trouvé à : $functionsPath");
        throw new Exception("Erreur interne: Le fichier de fonctions est manquant.");
    }
    require_once $functionsPath;

    // Créer une instance PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Définir les métadonnées du document
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Business Care');
    $pdf->SetTitle('Contrat ' . ($contract['reference'] ?? $contract['id']));
    $pdf->SetSubject('Détails du contrat de prestation de services');
    $pdf->SetKeywords('Business Care, Contrat, Prestation, Service');

    // Définir marges (en-tête/pied de page gérés par défaut par TCPDF ou via héritage)
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Définir les sauts de page automatiques
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Définir la police principale
    $pdf->SetFont('helvetica', '', 10);

    // Ajouter une page
    $pdf->AddPage();

    // ---- Contenu HTML du contrat ----
    $html = <<<HTML
<style>
    h1 { font-size: 16pt; font-weight: bold; text-align: center; margin-bottom: 20px; color: #333; }
    h2 { font-size: 12pt; font-weight: bold; margin-top: 15px; margin-bottom: 8px; border-bottom: 1px solid #cccccc; padding-bottom: 3px; color: #555; }
    p { line-height: 1.5; margin-bottom: 10px; text-align: justify; }
    strong { font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; margin-bottom: 15px; }
    th { background-color: #f2f2f2; border: 1px solid #dddddd; text-align: left; padding: 8px; font-weight: bold; color: #444; }
    td { border: 1px solid #dddddd; text-align: left; padding: 8px; vertical-align: top; }
    .info-block { margin-bottom: 20px; padding: 10px; border: 1px solid #eee; border-radius: 4px; background-color: #f9f9f9; }
    .info-block p { margin-bottom: 6px; }
    .info-label { font-weight: bold; width: 160px; display: inline-block; color: #666; }
    .conditions { margin-top: 20px; padding: 15px; border: 1px dashed #ccc; background-color: #fafafa; }
    .page-title { font-size: 18pt; text-align: center; margin-bottom: 25px; font-weight: bold; color: #2c3e50; }
</style>

<div class="page-title">Contrat de Prestation de Services</div>
HTML;

    // Informations sur le Contrat
    $html .= "<h2>Informations Générales du Contrat</h2>";
    $html .= "<div class=\"info-block\">";
    $html .= "<p><span class=\"info-label\">Référence Contrat:</span> " . htmlspecialchars($contract['reference'] ?? 'N/A') . "</p>";
    $html .= "<p><span class=\"info-label\">Type de Contrat:</span> " . htmlspecialchars(ucfirst($contract['type_contrat'] ?? 'N/A')) . "</p>";
    $html .= "<p><span class=\"info-label\">Date de Début:</span> " . (isset($contract['date_debut']) ? formatDate($contract['date_debut'], 'd/m/Y') : 'N/A') . "</p>";
    $html .= "<p><span class=\"info-label\">Date de Fin:</span> " . (isset($contract['date_fin']) && $contract['date_fin'] ? formatDate($contract['date_fin'], 'd/m/Y') : 'Indéterminée') . "</p>";
    $html .= "<p><span class=\"info-label\">Montant Mensuel:</span> " . (isset($contract['montant_mensuel']) ? formatMoney($contract['montant_mensuel']) : 'N/A') . "</p>";
    $html .= "<p><span class=\"info-label\">Nombre de Salariés:</span> " . htmlspecialchars($contract['nombre_salaries'] ?? 'Non spécifié') . "</p>";
    $html .= "<p><span class=\"info-label\">Statut:</span> " . htmlspecialchars(ucfirst($contract['statut'] ?? 'N/A')) . "</p>";
    $html .= "</div>";

    // Informations sur l'Entreprise Cliente
    $html .= "<h2>Informations sur l'Entreprise Cliente</h2>";
    $html .= "<div class=\"info-block\">";
    $html .= "<p><span class=\"info-label\">Nom:</span> " . htmlspecialchars($contract['entreprise_nom'] ?? 'N/A') . "</p>";
    $html .= "<p><span class=\"info-label\">SIRET:</span> " . htmlspecialchars($contract['entreprise_siret'] ?? 'N/A') . "</p>";
    $html .= "<p><span class=\"info-label\">Adresse:</span> " . htmlspecialchars($contract['entreprise_adresse'] ?? '') . ", " . htmlspecialchars($contract['entreprise_code_postal'] ?? '') . " " . htmlspecialchars($contract['entreprise_ville'] ?? '') . "</p>";
    $html .= "</div>";

    // Services Inclus
    if (!empty($contract['services'])) {
        $html .= "<h2>Services Inclus dans le Contrat</h2>";
        $html .= "<table>";
        $html .= "<thead><tr><th>Service</th><th>Description</th><th>Catégorie</th><th>Prix Indicatif</th></tr></thead>";
        $html .= "<tbody>";
        foreach ($contract['services'] as $service) {
            $html .= "<tr>";
            $html .= "<td>" . htmlspecialchars($service['nom'] ?? 'N/A') . "</td>";
            $html .= "<td>" . htmlspecialchars($service['description'] ?? '-') . "</td>";
            $html .= "<td>" . htmlspecialchars(ucfirst($service['categorie'] ?? '-')) . "</td>";
            $html .= "<td>" . htmlspecialchars($service['prix_formate'] ?? 'N/A') . "</td>";
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";
    } else {
        $html .= "<h2>Services Inclus dans le Contrat</h2>";
        $html .= "<p>Aucun service spécifique listé pour ce contrat.</p>";
    }
    $html .= "<br><br>";

    // Conditions Particulières, elles sont dans la table 'contrats'
    if (!empty($contract['conditions_particulieres'])) {
        $html .= "<h2>Conditions Particulières</h2>";
        $html .= "<div class=\"conditions\">" . nl2br(htmlspecialchars($contract['conditions_particulieres'])) . "</div>";
    }

    // Ajouter le contenu HTML au PDF
    $pdf->writeHTML($html, true, false, true, false, '');


    // Fermer et générer le document PDF pour téléchargement
    $filename = 'Contrat_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $contract['reference'] ?? $contract['id']) . '.pdf'; // Nettoyer nom fichier
    try {
        $pdf->Output($filename, 'D');
    } catch (Exception $e) {
        // Log l'erreur spécifique de TCPDF si Output échoue
        logSystemActivity('error', "Erreur TCPDF Output pour contrat #$contract[id]: " . $e->getMessage());
        // Tenter d'afficher un message d'erreur simple (peut ne pas fonctionner si les en-têtes sont déjà envoyés)
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
            echo "Erreur lors de la finalisation du PDF. Veuillez contacter le support.";
        }
    }
    exit; // Arrêter l'exécution après avoir envoyé le PDF (ou tenté de l'envoyer)
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

    // 1. Trouver l'entreprise liée à ce contrat
    $contractInfo = fetchOne('contrats', 'id = :id', [':id' => $contract_id]);
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
    // TODO: Implémenter la logique pour récupérer l'historique du contrat.
    // Nécessite la création de la table 'historique_contrats' dans la base de données.
    logSystemActivity('warning', "Fonctionnalité getContractHistory non implémentée (table 'historique_contrats' manquante).");
    return []; // Retourne un tableau vide pour l'instant
}

/**
 * Demande le renouvellement d'un contrat
 *
 * @param int $company_id Identifiant de l'entreprise
 * @param array $data Données de la demande
 * @return array Résultat de l'opération
 */
function requestContractRenewal($company_id, $data)
{
    // TODO: Implémenter la logique pour la demande de renouvellement de contrat.
    // Nécessite la création des tables 'demandes_renouvellement' et 'historique_contrats'.
    // Revoir aussi la logique de notification admin pour correspondre à la table 'notifications'.
    logSystemActivity('warning', "Fonctionnalité requestContractRenewal non implémentée (tables manquantes).");
    return [
        'success' => false,
        'message' => "Fonctionnalité de renouvellement non disponible pour le moment."
    ]; // Retourne une erreur contrôlée
}

/**
 * Traite une demande de devis d'une entreprise
 *
 * @param array $data Données de la demande (incluant entreprise_id, service_souhaite, description_besoin, etc.)
 * @return array Résultat de l'opération (success, message)
 */
function requestCompanyQuote($data)
{
    // ... (Validation des données de base) ...

    // Sécurité: Valider le token CSRF si vous l'utilisez
    if (!isset($data['csrf_token']) || !validateToken($data['csrf_token'])) {
        return [
            'success' => false,
            'message' => "Erreur de sécurité. Veuillez réessayer."
        ];
    }

    try {
        // Préparer les données pour l'insertion dans la table 'devis'
        // Utiliser uniquement les colonnes existantes dans 'devis'
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
        // 'description_demande' => $data['description_besoin'],
        // 'service_souhaite' => $data['service_souhaite'],
        // 'nombre_salaries_estime' => $data['nombre_salaries'] ?? null,
        // 'contact_personne' => $data['contact_personne'] ?? null,
        // 'contact_email' => $data['contact_email'] ?? null,
        // 'contact_telephone' => $data['contact_telephone'] ?? null

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
        $adminRoleId = ROLE_ADMIN; // Assurez-vous que ROLE_ADMIN est défini
        $admins = fetchAll('personnes', 'role_id = :role_id', [':role_id' => $adminRoleId]);
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
