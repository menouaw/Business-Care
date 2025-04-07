<?php
require_once __DIR__ . '/../../init.php';

/**
 * Recupere la liste des rendez-vous avec pagination et filtrage
 * 
 * @param int $page Numero de la page
 * @param int $perPage Nombre d'elements par page
 * @param string $search Terme de recherche (nom/prenom patient/praticien, nom prestation)
 * @param string $status Filtre par statut
 * @param string $type Filtre par type de rdv
 * @param int $prestationId Filtre par ID prestation
 * @param string $startDate Filtre par date de debut (YYYY-MM-DD)
 * @param string $endDate Filtre par date de fin (YYYY-MM-DD)
 * @return array Donnees de pagination et liste des rendez-vous
 */
function appointmentsGetList($page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE, $search = '', $status = '', $type = '', $prestationId = 0, $startDate = '', $endDate = '') {
    $params = [];
    $conditions = [];

    if ($search) {
        $conditions[] = "(p_patient.nom LIKE ? OR p_patient.prenom LIKE ? OR p_practitioner.nom LIKE ? OR p_practitioner.prenom LIKE ? OR pr.nom LIKE ?)";
        $searchTerm = "%{$search}%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }

    if ($status && in_array($status, APPOINTMENT_STATUSES)) {
        $conditions[] = "rv.statut = ?";
        $params[] = $status;
    }

    if ($type && in_array($type, APPOINTMENT_TYPES)) {
        $conditions[] = "rv.type_rdv = ?";
        $params[] = $type;
    }

    if ($prestationId > 0) {
        $conditions[] = "rv.prestation_id = ?";
        $params[] = (int)$prestationId;
    }

    if ($startDate) {
        $conditions[] = "rv.date_rdv >= ?";
        $params[] = $startDate . ' 00:00:00';
    }
    
    if ($endDate) {
        $conditions[] = "rv.date_rdv <= ?";
        $params[] = $endDate . ' 23:59:59';
    }
    
    $whereSql = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : '';

    $countSql = "SELECT COUNT(rv.id) 
                 FROM " . TABLE_APPOINTMENTS . " rv
                 LEFT JOIN " . TABLE_USERS . " p_patient ON rv.personne_id = p_patient.id
                 LEFT JOIN " . TABLE_USERS . " p_practitioner ON rv.praticien_id = p_practitioner.id
                 LEFT JOIN " . TABLE_PRESTATIONS . " pr ON rv.prestation_id = pr.id
                 {$whereSql}";
                 
    $totalAppointments = executeQuery($countSql, $params)->fetchColumn();
    
    $totalPages = ceil($totalAppointments / $perPage);
    $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT rv.*, 
                   p_patient.nom as patient_nom, p_patient.prenom as patient_prenom,
                   p_practitioner.nom as practitioner_nom, p_practitioner.prenom as practitioner_prenom,
                   pr.nom as prestation_nom
            FROM " . TABLE_APPOINTMENTS . " rv
            LEFT JOIN " . TABLE_USERS . " p_patient ON rv.personne_id = p_patient.id
            LEFT JOIN " . TABLE_USERS . " p_practitioner ON rv.praticien_id = p_practitioner.id
            LEFT JOIN " . TABLE_PRESTATIONS . " pr ON rv.prestation_id = pr.id
            {$whereSql}
            ORDER BY rv.date_rdv DESC 
            LIMIT ?, ?";
            
    $paramsWithPagination = array_merge($params, [(int)$offset, (int)$perPage]);

    $appointments = executeQuery($sql, $paramsWithPagination)->fetchAll();

    return [
        'appointments' => $appointments,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalAppointments,
        'perPage' => $perPage
    ];
}

/**
 * Recupere les details d'un rendez-vous
 * 
 * @param int $id Identifiant du rendez-vous
 * @return array|false Donnees du rendez-vous ou false si non trouve
 */
function appointmentsGetDetails($id) {
    $sql = "SELECT rv.*, 
                   p_patient.nom as patient_nom, p_patient.prenom as patient_prenom, p_patient.email as patient_email,
                   p_practitioner.nom as practitioner_nom, p_practitioner.prenom as practitioner_prenom, p_practitioner.email as practitioner_email,
                   pr.nom as prestation_nom, pr.prix as prestation_prix, pr.duree as prestation_duree_default
            FROM " . TABLE_APPOINTMENTS . " rv
            LEFT JOIN " . TABLE_USERS . " p_patient ON rv.personne_id = p_patient.id
            LEFT JOIN " . TABLE_USERS . " p_practitioner ON rv.praticien_id = p_practitioner.id
            LEFT JOIN " . TABLE_PRESTATIONS . " pr ON rv.prestation_id = pr.id
            WHERE rv.id = ? 
            LIMIT 1";
            
    return executeQuery($sql, [$id])->fetch();
}

/**
 * Crée ou met à jour un rendez-vous dans la base de données.
 *
 * @param array $data Données du rendez-vous.
 * @param int $id Identifiant du rendez-vous (0 pour création).
 * @return array Résultat ['success' => bool, 'message' => string|null, 'errors' => array|null, 'newId' => int|null]
 */
function appointmentsSave($data, $id = 0) {
    $errors = [];
    $isNew = ($id == 0);

    $data['personne_id'] = isset($data['personne_id']) ? (int)$data['personne_id'] : 0;
    $data['prestation_id'] = isset($data['prestation_id']) ? (int)$data['prestation_id'] : 0;
    $data['praticien_id'] = isset($data['praticien_id']) && $data['praticien_id'] !== '' ? (int)$data['praticien_id'] : null;
    $data['date_rdv'] = $data['date_rdv'] ?? '';
    $data['duree'] = isset($data['duree']) ? (int)$data['duree'] : 0;
    $data['lieu'] = trim($data['lieu'] ?? '');
    $data['type_rdv'] = $data['type_rdv'] ?? '';
    $data['statut'] = $data['statut'] ?? ($isNew ? 'planifie' : null);
    $data['notes'] = trim($data['notes'] ?? '');
    
    if (empty($data['personne_id']) || !fetchOne(TABLE_USERS, 'id = ? AND role_id = ?', '', [$data['personne_id'], ROLE_SALARIE])) {
        $errors['personne_id'] = "Le patient (salarie) selectionne est invalide.";
    }
    
    if (empty($data['prestation_id']) || !fetchOne(TABLE_PRESTATIONS, 'id = ?', '', [$data['prestation_id']])) {
        $errors['prestation_id'] = "La prestation selectionnee est invalide.";
    }
    
    if ($data['praticien_id'] !== null && !fetchOne(TABLE_USERS, 'id = ? AND role_id = ?', '', [$data['praticien_id'], ROLE_PRESTATAIRE])) {
        $errors['praticien_id'] = "Le praticien selectionne est invalide.";
    }
    
    if (empty($data['date_rdv'])) {
        $errors['date_rdv'] = "La date et l'heure du rendez-vous sont obligatoires.";
    } else {
        try {
            new DateTime($data['date_rdv']);
        } catch (Exception $e) {
            $errors['date_rdv'] = "Format de date et heure invalide.";
        }
    }

    if (empty($data['duree']) || $data['duree'] <= 0) {
        $errors['duree'] = "La duree doit etre un nombre entier positif.";
    }
    
    if (empty($data['type_rdv']) || !in_array($data['type_rdv'], APPOINTMENT_TYPES)) {
        $errors['type_rdv'] = "Le type de rendez-vous selectionne est invalide.";
    }
    
    if (empty($data['statut']) || !in_array($data['statut'], APPOINTMENT_STATUSES)) {
        $errors['statut'] = "Le statut selectionne est invalide.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $dbData = [
        'personne_id' => $data['personne_id'],
        'prestation_id' => $data['prestation_id'],
        'praticien_id' => $data['praticien_id'],
        'date_rdv' => $data['date_rdv'],
        'duree' => $data['duree'],
        'lieu' => $data['lieu'] ?: null,
        'type_rdv' => $data['type_rdv'],
        'statut' => $data['statut'],
        'notes' => $data['notes'] ?: null,
    ];

    try {
        beginTransaction();

        if (!$isNew) {
            $affectedRows = updateRow(TABLE_APPOINTMENTS, $dbData, "id = :where_id", ['where_id' => $id]);
            
            if ($affectedRows !== false) {
                logBusinessOperation($_SESSION['user_id'], 'appointment_update', 
                    "[SUCCESS] Mise à jour RDV ID: $id, Patient ID: {$dbData['personne_id']}, Prestation ID: {$dbData['prestation_id']}, Date: {$dbData['date_rdv']}");
                $message = "Le rendez-vous a ete mis a jour avec succes.";
                commitTransaction();
                return ['success' => true, 'message' => $message, 'appointmentId' => $id];
            } else {
                throw new Exception("La mise à jour a échoué ou aucune donnée n'a été modifiée.");
            }
        } 
        else {
            $newId = insertRow(TABLE_APPOINTMENTS, $dbData);
            
            if ($newId) {
                logBusinessOperation($_SESSION['user_id'], 'appointment_create', 
                    "[SUCCESS] Création RDV ID: $newId, Patient ID: {$dbData['personne_id']}, Prestation ID: {$dbData['prestation_id']}, Date: {$dbData['date_rdv']}");
                $message = "Le rendez-vous a ete cree avec succes.";
                commitTransaction();
                return ['success' => true, 'message' => $message, 'newId' => $newId];
            } else {
                throw new Exception("L'insertion a échoué.");
            }
        }
        
    } catch (Exception $e) {
        rollbackTransaction();
        $action = $isNew ? 'création' : 'mise à jour';
        $errorMessage = "Erreur lors de la {$action} du rendez-vous : " . $e->getMessage();
        logSystemActivity('appointment_save_error', "[ERROR] Erreur BDD dans appointmentsSave (Action: {$action}, ID: {$id}): " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => ['db_error' => $errorMessage]
        ];
    }
}

/**
 * Supprime un rendez-vous de la base de données.
 *
 * @param int $id Identifiant du rendez-vous à supprimer.
 * @return array Résultat ['success' => bool, 'message' => string]
 */
function appointmentsDelete($id) {
    
    try {
        beginTransaction(); 
        $deletedRows = deleteRow(TABLE_APPOINTMENTS, "id = ?", [$id]);
        
        if ($deletedRows > 0) {
            commitTransaction(); 
            logBusinessOperation($_SESSION['user_id'], 'appointment_delete', 
                "[SUCCESS] Suppression RDV ID: $id");
            return [
                'success' => true,
                'message' => "Le rendez-vous a ete supprime avec succes"
            ];
        } else {
            rollbackTransaction(); 
            logBusinessOperation($_SESSION['user_id'], 'appointment_delete_attempt', 
                "[ERROR] Tentative échouée de suppression RDV ID: $id - RDV non trouvé?");
            return [
                'success' => false,
                'message' => "Impossible de supprimer le rendez-vous (non trouvé ou déjà supprimé)"
            ];
        }
    } catch (Exception $e) {
        rollbackTransaction(); 
         logSystemActivity('error', "[ERROR] Erreur BDD dans appointmentsDelete: " . $e->getMessage());
         return [
            'success' => false,
            'message' => "Erreur de base de données lors de la suppression."
         ];
    }
} 

/**
 * Recupere la liste des patients (salaries) pour les formulaires
 * 
 * @return array Liste des patients [id => 'Nom Prenom (Email)']
 */
function appointmentsGetPatients() {
    $sql = "SELECT id, nom, prenom, email FROM " . TABLE_USERS . " WHERE role_id = ? AND statut = 'actif' ORDER BY nom, prenom";
    $users = executeQuery($sql, [ROLE_SALARIE])->fetchAll();
    $options = [];
    foreach ($users as $user) {
        $options[$user['id']] = $user['nom'] . ' ' . $user['prenom'] . ' (' . $user['email'] . ')';
    }
    return $options;
}

/**
 * Recupere la liste des praticiens pour les formulaires
 * 
 * @return array Liste des praticiens [id => 'Nom Prenom (Email)']
 */
function appointmentsGetPractitioners() {
    $sql = "SELECT id, nom, prenom, email FROM " . TABLE_USERS . " WHERE role_id = ? AND statut = 'actif' ORDER BY nom, prenom";
    $users = executeQuery($sql, [ROLE_PRESTATAIRE])->fetchAll();
    $options = [];
    foreach ($users as $user) {
        $options[$user['id']] = $user['nom'] . ' ' . $user['prenom'] . ' (' . $user['email'] . ')';
    }
    return $options;
}

/**
 * Recupere la liste des services (prestations) pour les formulaires
 * 
 * @return array Liste des services [id => 'Nom (Type)']
 */
function appointmentsGetServices() {
    $sql = "SELECT id, nom, type FROM " . TABLE_PRESTATIONS . " ORDER BY nom";
    $services = executeQuery($sql)->fetchAll();
    $options = [];
    foreach ($services as $service) {
        $options[$service['id']] = $service['nom'] . ' (' . ucfirst($service['type']) . ')';
    }
    return $options;
}

/**
 * Recupere la liste des statuts de rendez-vous possibles
 * 
 * @return array Liste des statuts [valeur => Libelle]
 */
function appointmentsGetStatuses() {
    $statuses = [];
    foreach (APPOINTMENT_STATUSES as $status) {
        $statuses[$status] = ucfirst(str_replace('_', ' ', $status));
    }
    return $statuses;
}

/**
 * Recupere la liste des types de rendez-vous possibles
 * 
 * @return array Liste des types [valeur => Libelle]
 */
function appointmentsGetTypes() {
     $types = [];
    foreach (APPOINTMENT_TYPES as $type) {
        $types[$type] = ucfirst($type);
    }
    return $types;
}
?>
