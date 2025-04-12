<?php
require_once __DIR__ . '/../../init.php';


/**
 * Récupère une liste paginée de prestataires.
 *
 * @param array $filters Tableau associatif de filtres (ex: ['status' => 'actif', 'search' => 'John']).
 * @param array $pagination Paramètres de pagination (ex: ['page' => 1, 'perPage' => 10]).
 * @param string $orderBy Clause SQL ORDER BY.
 * @return array Tableau de résultat de pagination incluant role_name.
 */
function getProvidersList($filters = [], $pagination = [], $orderBy = 'p.nom ASC, p.prenom ASC')
{
    $page = $pagination['page'] ?? 1;
    $perPage = $pagination['perPage'] ?? DEFAULT_ITEMS_PER_PAGE;
    
    $params = [':role_id' => ROLE_PRESTATAIRE];
    $conditions = ['p.role_id = :role_id'];

    if (!empty($filters['status'])) {
        $conditions[] = 'p.statut = :status';
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['search'])) {
        $conditions[] = '(p.nom LIKE :search OR p.prenom LIKE :search OR p.email LIKE :search)';
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    $whereSql = implode(' AND ', $conditions);
    
    $countSql = "SELECT COUNT(p.id) FROM " . TABLE_USERS . " p WHERE " . $whereSql;
    $totalItems = executeQuery($countSql, $params)->fetchColumn();
    
    $totalPages = $perPage > 0 ? ceil($totalItems / $perPage) : 1;
    $page = max(1, min($page, ($totalPages > 0 ? $totalPages : 1) ));
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT p.*, r.nom as role_name 
            FROM " . TABLE_USERS . " p 
            LEFT JOIN " . TABLE_ROLES . " r ON p.role_id = r.id 
            WHERE " . $whereSql . " 
            ORDER BY " . $orderBy;
            
    if ($perPage > 0) {
         $sql .= " LIMIT :limit OFFSET :offset";
         $params[':limit'] = $perPage;
         $params[':offset'] = $offset;
    }

    $stmt = executeQuery($sql, $params);
    $items = $stmt->fetchAll();

    return [
        'items' => $items,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'perPage' => $perPage
    ];
}

/**
 * Récupère tous les détails d'un prestataire unique, incluant le nom du rôle.
 *
 * @param int $provider_id L'ID du prestataire.
 * @return array|false Détails du prestataire ou false si non trouvé.
 */
function getProviderDetails($provider_id)
{
    $sql = "SELECT p.*, r.nom as role_name 
            FROM " . TABLE_USERS . " p 
            LEFT JOIN " . TABLE_ROLES . " r ON p.role_id = r.id 
            WHERE p.id = :id AND p.role_id = :role_id LIMIT 1";
            
    return executeQuery($sql, [':id' => $provider_id, ':role_id' => ROLE_PRESTATAIRE])->fetch();
}

/**
 * Met à jour le statut d'un prestataire.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param string $new_status Le nouveau statut.
 * @return int Nombre de lignes affectées.
 */
function updateProviderStatus($provider_id, $new_status)
{
    if (!in_array($new_status, USER_STATUSES)) {
        throw new InvalidArgumentException("Statut invalide fourni.");
    }
    $affectedRows = updateRow(
        TABLE_USERS,
        ['statut' => $new_status],
        'id = :id AND role_id = :role_id',
        ['id' => $provider_id, 'role_id' => ROLE_PRESTATAIRE]
    );
    
    if ($affectedRows > 0) {
        logBusinessOperation($_SESSION['user_id'] ?? 0, 'provider_status_update', 
            "[SUCCESS] Statut prestataire ID: {$provider_id} mis à jour à {$new_status}");
    }
    
    return $affectedRows;
}



/**
 * Récupère toutes les habilitations associées à un prestataire spécifique.
 *
 * @param int $provider_id L'ID du prestataire.
 * @return array Liste des habilitations.
 */
function getProviderHabilitations($provider_id)
{
    return fetchAll(TABLE_HABILITATIONS, 'prestataire_id = :provider_id', 'date_expiration DESC', 0, 0, [':provider_id' => $provider_id]);
}

/**
 * Ajoute une nouvelle habilitation pour un prestataire.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param array $habilitation_data Tableau associatif des données de l'habilitation.
 * @return int|false ID de la ligne insérée ou false en cas d'échec.
 */
function addProviderHabilitation($provider_id, $habilitation_data)
{
    if (!isset($habilitation_data['statut']) || !in_array($habilitation_data['statut'], HABILITATION_STATUSES)) {
        $habilitation_data['statut'] = HABILITATION_STATUS_PENDING;
    }
    $habilitation_data['prestataire_id'] = $provider_id;
    $newId = insertRow(TABLE_HABILITATIONS, $habilitation_data);
    
    if ($newId) {
         logBusinessOperation($_SESSION['user_id'] ?? 0, 'habilitation_add', 
            "[SUCCESS] Habilitation ajoutée (ID: {$newId}) pour prestataire ID: {$provider_id}");
    }
    
    return $newId;
}

/**
 * Met à jour le statut d'une habilitation existante.
 *
 * @param int $habilitation_id L'ID de l'habilitation.
 * @param string $status Le nouveau statut (ex: HABILITATION_STATUS_VERIFIED).
 * @return int Nombre de lignes affectées.
 */
function updateHabilitationStatus($habilitation_id, $status)
{
    if (!in_array($status, HABILITATION_STATUSES)) {
        throw new InvalidArgumentException("Statut d'habilitation invalide.");
    }
    $affectedRows = updateRow(TABLE_HABILITATIONS, ['statut' => $status], 'id = :id', ['id' => $habilitation_id]);
    
    if ($affectedRows > 0) {
        logBusinessOperation($_SESSION['user_id'] ?? 0, 'habilitation_status_update', 
            "[SUCCESS] Statut habilitation ID: {$habilitation_id} mis à jour à {$status}");
        if ($status == HABILITATION_STATUS_VERIFIED || $status == HABILITATION_STATUS_REJECTED) {
            logSecurityEvent($_SESSION['user_id'] ?? 0, 'habilitation_validation', 
                "[INFO] Habilitation ID: {$habilitation_id} marked as {$status}");
        }
    }
    
    return $affectedRows;
}

/**
 * Supprime une habilitation.
 *
 * @param int $habilitation_id L'ID de l'habilitation à supprimer.
 * @return int Nombre de lignes affectées.
 */
function deleteProviderHabilitation($habilitation_id)
{
    $affectedRows = deleteRow(TABLE_HABILITATIONS, 'id = :id', [':id' => $habilitation_id]);
    
    if ($affectedRows > 0) {
        logBusinessOperation($_SESSION['user_id'] ?? 0, 'habilitation_delete', 
            "[SUCCESS] Habilitation ID: {$habilitation_id} supprimée");
    }
    
    return $affectedRows;
}



/**
 * Liste les prestations spécifiques qu'un prestataire est autorisé à délivrer.
 *
 * @param int $provider_id L'ID du prestataire.
 * @return array Liste des prestations assignées avec leurs détails.
 */
function getProviderAssignedPrestations($provider_id)
{
    $sql = "SELECT pr.* 
            FROM " . TABLE_PRESTATIONS . " pr
            JOIN " . TABLE_PROVIDER_SERVICES . " ps ON pr.id = ps.prestation_id
            WHERE ps.prestataire_id = :provider_id
            ORDER BY pr.nom ASC";
            
    $stmt = executeQuery($sql, [':provider_id' => $provider_id]);
    return $stmt->fetchAll();
}

/**
 * Lie une prestation spécifique du catalogue à un prestataire.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param int $prestation_id L'ID de la prestation.
 * @return int|false|true ID de la ligne insérée, false en cas d'échec, ou true si déjà assigné.
 */
function assignPrestationToProvider($provider_id, $prestation_id)
{
    $exists = fetchOne(TABLE_PROVIDER_SERVICES, 'prestataire_id = :provider_id AND prestation_id = :prestation_id', '', [':provider_id' => $provider_id, ':prestation_id' => $prestation_id]);
    if ($exists) {
        return true;
    }
    
    $data = [
        'prestataire_id' => $provider_id,
        'prestation_id' => $prestation_id
    ];
    $result = insertRow(TABLE_PROVIDER_SERVICES, $data);
    
    if ($result) {
        logBusinessOperation($_SESSION['user_id'] ?? 0, 'provider_prestation_assign', 
            "[SUCCESS] Prestation ID: {$prestation_id} assignée au prestataire ID: {$provider_id}");
    }
    
    return $result;
}

/**
 * Dissocie une prestation d'un prestataire.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param int $prestation_id L'ID de la prestation.
 * @return int Nombre de lignes affectées.
 */
function removePrestationFromProvider($provider_id, $prestation_id)
{
    $affectedRows = deleteRow(TABLE_PROVIDER_SERVICES, 'prestataire_id = :provider_id AND prestation_id = :prestation_id', [':provider_id' => $provider_id, ':prestation_id' => $prestation_id]);
    
    if ($affectedRows > 0) {
         logBusinessOperation($_SESSION['user_id'] ?? 0, 'provider_prestation_remove', 
            "[SUCCESS] Prestation ID: {$prestation_id} retirée du prestataire ID: {$provider_id}");
    }
        
    return $affectedRows;
}



/**
 * Récupère le calendrier de disponibilité du prestataire pour une période donnée.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param string $start_date Date de début (YYYY-MM-DD).
 * @param string $end_date Date de fin (YYYY-MM-DD).
 * @return array Liste des créneaux de disponibilité.
 */
function getProviderAvailabilities($provider_id, $start_date, $end_date)
{
     $sql = "SELECT * FROM " . TABLE_PROVIDER_AVAILABILITY . " 
            WHERE prestataire_id = :provider_id 
            AND (
                 (type = :type_specific AND date_debut BETWEEN :start_date AND :end_date) 
                 OR 
                 (type = :type_recurring AND (recurrence_fin IS NULL OR recurrence_fin >= :start_date)) 
                 OR
                 (type = :type_unavailable AND date_debut BETWEEN :start_date AND :end_date) 
            )
            ORDER BY date_debut ASC, heure_debut ASC";

    $params = [
        ':provider_id' => $provider_id,
        ':start_date' => $start_date,
        ':end_date' => $end_date,
        ':type_specific' => AVAILABILITY_TYPE_SPECIFIC,
        ':type_recurring' => AVAILABILITY_TYPE_RECURRING,
        ':type_unavailable' => AVAILABILITY_TYPE_UNAVAILABLE
    ];

    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Ajoute une nouvelle entrée de disponibilité pour un prestataire.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param array $availability_data Tableau associatif des données de disponibilité. La structure dépend du type.
 * @return int|false ID de la ligne insérée ou false en cas d'échec.
 */
function addProviderAvailabilitySlot($provider_id, $availability_data)
{
    if (!isset($availability_data['type']) || !in_array($availability_data['type'], AVAILABILITY_TYPES)) {
         throw new InvalidArgumentException("Type de disponibilité invalide.");
    }
    $availability_data['prestataire_id'] = $provider_id;
    
    if ($availability_data['type'] == AVAILABILITY_TYPE_RECURRING && !isset($availability_data['jour_semaine'])) {
         throw new InvalidArgumentException("Le jour de la semaine est requis pour une disponibilité récurrente.");
    }
    if ($availability_data['type'] == AVAILABILITY_TYPE_SPECIFIC && !isset($availability_data['date_debut'])) {
         throw new InvalidArgumentException("La date de début est requise pour une disponibilité spécifique.");
    }
    
    $newId = insertRow(TABLE_PROVIDER_AVAILABILITY, $availability_data);
    
    if ($newId) {
        logBusinessOperation($_SESSION['user_id'] ?? 0, 'provider_availability_add', 
            "[SUCCESS] Disponibilité ajoutée (ID: {$newId}) pour prestataire ID: {$provider_id}, Type: {$availability_data['type']}");
    }
    
    return $newId;
}

/**
 * Modifie une entrée de disponibilité existante.
 *
 * @param int $availability_id L'ID du créneau de disponibilité.
 * @param array $updated_data Tableau associatif des données à mettre à jour.
 * @return int Nombre de lignes affectées.
 */
function updateProviderAvailabilitySlot($availability_id, $updated_data)
{
     if (isset($updated_data['type']) && !in_array($updated_data['type'], AVAILABILITY_TYPES)) {
         throw new InvalidArgumentException("Type de disponibilité invalide.");
    }
    
    $affectedRows = updateRow(TABLE_PROVIDER_AVAILABILITY, $updated_data, 'id = :id', ['id' => $availability_id]);
    
    if ($affectedRows > 0) {
        logBusinessOperation($_SESSION['user_id'] ?? 0, 'provider_availability_update', 
            "[SUCCESS] Disponibilité ID: {$availability_id} mise à jour.");
    }
        
    return $affectedRows;
}

/**
 * Supprime une entrée de disponibilité.
 *
 * @param int $availability_id L'ID du créneau de disponibilité à supprimer.
 * @return int Nombre de lignes affectées.
 */
function deleteProviderAvailabilitySlot($availability_id)
{
    $affectedRows = deleteRow(TABLE_PROVIDER_AVAILABILITY, 'id = :id', [':id' => $availability_id]);
    
    if ($affectedRows > 0) {
        logBusinessOperation($_SESSION['user_id'] ?? 0, 'provider_availability_delete', 
            "[SUCCESS] Disponibilité ID: {$availability_id} supprimée.");
    }
    
    return $affectedRows;
}



/**
 * Récupère une liste paginée de rendez-vous assignés à un prestataire spécifique.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param array $filters Filtres (ex: ['status' => 'planifie', 'date_start' => '...', 'date_end' => '...']).
 * @param array $pagination Paramètres de pagination.
 * @param string $orderBy Clause SQL ORDER BY.
 * @return array Tableau de résultat de pagination.
 */
function getProviderAppointments($provider_id, $filters = [], $pagination = [], $orderBy = 'rdv.date_rdv DESC')
{
    $page = $pagination['page'] ?? 1;
    $perPage = $pagination['perPage'] ?? DEFAULT_ITEMS_PER_PAGE;

    $params = [':provider_id' => $provider_id];
    $conditions = ['rdv.praticien_id = :provider_id'];

    if (!empty($filters['status'])) {
        $conditions[] = 'rdv.statut = :status';
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['date_start'])) {
        $conditions[] = 'rdv.date_rdv >= :date_start';
        $params[':date_start'] = date('Y-m-d H:i:s', strtotime($filters['date_start'] . ' 00:00:00'));
    }
     if (!empty($filters['date_end'])) {
        $conditions[] = 'rdv.date_rdv <= :date_end';
        $params[':date_end'] = date('Y-m-d H:i:s', strtotime($filters['date_end'] . ' 23:59:59'));
    }
    if (!empty($filters['prestation_id'])) {
        $conditions[] = 'rdv.prestation_id = :prestation_id';
        $params[':prestation_id'] = $filters['prestation_id'];
    }
     if (!empty($filters['client_search'])) {
        $conditions[] = '(u.nom LIKE :client_search OR u.prenom LIKE :client_search OR u.email LIKE :client_search)';
        $params[':client_search'] = '%' . $filters['client_search'] . '%';
    }
    
    $whereSql = implode(' AND ', $conditions);

    $countSql = "SELECT COUNT(rdv.id) 
                 FROM " . TABLE_APPOINTMENTS . " rdv 
                 LEFT JOIN " . TABLE_USERS . " u ON rdv.personne_id = u.id 
                 LEFT JOIN " . TABLE_PRESTATIONS . " p ON rdv.prestation_id = p.id 
                 WHERE " . $whereSql;
    $countStmt = executeQuery($countSql, $params);
    $totalItems = $countStmt->fetchColumn();
    
    $totalPages = $perPage > 0 ? ceil($totalItems / $perPage) : 1;
    $page = max(1, min($page, ($totalPages > 0 ? $totalPages : 1)));
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT rdv.*, CONCAT(u.prenom, ' ', u.nom) as nom_client, p.nom as nom_prestation 
            FROM " . TABLE_APPOINTMENTS . " rdv
            LEFT JOIN " . TABLE_USERS . " u ON rdv.personne_id = u.id
            LEFT JOIN " . TABLE_PRESTATIONS . " p ON rdv.prestation_id = p.id
            WHERE " . $whereSql . " 
            ORDER BY " . $orderBy;
            
    if ($perPage > 0) {
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;
    }

    $stmt = executeQuery($sql, $params);
    $items = $stmt->fetchAll();

    return [
        'items' => $items,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'perPage' => $perPage
    ];
}

/**
 * Récupère les détails d'un rendez-vous spécifique, avec potentiellement des informations supplémentaires pour la vue admin/prestataire.
 *
 * @param int $appointment_id L'ID du rendez-vous.
 * @return array|false Détails du rendez-vous ou false si non trouvé.
 */
function getAppointmentDetailsForProvider($appointment_id)
{
     $sql = "SELECT rdv.*, 
                    CONCAT(u.prenom, ' ', u.nom) as nom_client, u.email as email_client, u.telephone as tel_client,
                    p.nom as nom_prestation, p.description as description_prestation,
                    CONCAT(prov.prenom, ' ', prov.nom) as nom_praticien, prov.email as email_praticien
             FROM " . TABLE_APPOINTMENTS . " rdv
             LEFT JOIN " . TABLE_USERS . " u ON rdv.personne_id = u.id
             LEFT JOIN " . TABLE_PRESTATIONS . " p ON rdv.prestation_id = p.id
             LEFT JOIN " . TABLE_USERS . " prov ON rdv.praticien_id = prov.id
             WHERE rdv.id = :id";
             
     $stmt = executeQuery($sql, [':id' => $appointment_id]);
     return $stmt->fetch();
}

/**
 * Permet à un admin de mettre à jour le statut d'un rendez-vous.
 *
 * @param int $appointment_id L'ID du rendez-vous.
 * @param string $status Le nouveau statut.
 * @return int Nombre de lignes affectées.
 */
function updateAppointmentStatusByAdmin($appointment_id, $status)
{
    if (!in_array($status, APPOINTMENT_STATUSES)) {
         throw new InvalidArgumentException("Statut de rendez-vous invalide.");
    }
    $affectedRows = updateRow(TABLE_APPOINTMENTS, ['statut' => $status], 'id = :id', ['id' => $appointment_id]);
    
    if ($affectedRows > 0) {
        logBusinessOperation($_SESSION['user_id'] ?? 0, 'appointment_status_update', 
            "[SUCCESS] Statut RDV ID: {$appointment_id} mis à jour à {$status} par admin.");
    }
    
    return $affectedRows;
}

/**
 * Change le prestataire assigné à un rendez-vous existant.
 *
 * @param int $appointment_id L'ID du rendez-vous.
 * @param int $new_provider_id L'ID du nouveau prestataire.
 * @return int Nombre de lignes affectées.
 */
function reassignAppointmentProvider($appointment_id, $new_provider_id)
{
    $provider = getProviderDetails($new_provider_id);
    if (!$provider) {
         throw new InvalidArgumentException("Le nouveau prestataire sélectionné est invalide ou n'existe pas.");
    }
    
    $affectedRows = updateRow(TABLE_APPOINTMENTS, ['praticien_id' => $new_provider_id], 'id = :id', ['id' => $appointment_id]);
    
    if ($affectedRows > 0) {
        logBusinessOperation($_SESSION['user_id'] ?? 0, 'appointment_reassign', 
            "[SUCCESS] RDV ID: {$appointment_id} réassigné au prestataire ID: {$new_provider_id} par admin.");
    }
    
    return $affectedRows;
}

/**
 * Récupère les évaluations récentes et la note moyenne pour les prestations susceptibles d'être réalisées par un prestataire spécifique.
 * Note: Basé sur les prestations assignées, pas sur les rendez-vous spécifiques effectués par ce prestataire,
 * en raison de la structure actuelle de la table 'evaluations'.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param int $limit Le nombre maximum d'évaluations récentes à récupérer.
 * @return array Contenant 'evaluations' (liste), 'average_score' (float|null), 'total_evaluations' (int).
 */
function getProviderEvaluations($provider_id, $limit = 5)
{
    // Récupérer les évaluations récentes pour les prestations que ce prestataire peut effectuer
    $sql = "SELECT e.*, p.nom as prestation_nom, 
                   CONCAT(u.prenom, ' ', u.nom) as client_nom
            FROM " . TABLE_EVALUATIONS . " e
            JOIN " . TABLE_PRESTATIONS . " p ON e.prestation_id = p.id
            JOIN " . TABLE_USERS . " u ON e.personne_id = u.id 
            WHERE e.prestation_id IN (SELECT prestation_id FROM " . TABLE_PROVIDER_SERVICES . " WHERE prestataire_id = :provider_id)
            ORDER BY e.date_evaluation DESC
            LIMIT :limit";
            
    $evaluations = executeQuery($sql, [':provider_id' => $provider_id, ':limit' => $limit])->fetchAll();

    $sqlAvg = "SELECT AVG(e.note) as average_score, COUNT(e.id) as total_evaluations
               FROM " . TABLE_EVALUATIONS . " e
               WHERE e.prestation_id IN (SELECT prestation_id FROM " . TABLE_PROVIDER_SERVICES . " WHERE prestataire_id = :provider_id)";
               
    $stats = executeQuery($sqlAvg, [':provider_id' => $provider_id])->fetch();
    
    return [
        'evaluations' => $evaluations,
        'average_score' => $stats['average_score'] ? round($stats['average_score'], 2) : null,
        'total_evaluations' => $stats['total_evaluations'] ?? 0
    ];
}

?>
