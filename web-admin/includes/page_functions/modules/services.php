<?php
require_once __DIR__ . '/../../init.php';

/**
 * Recupere la liste des services avec pagination et filtrage
 * 
 * @param int $page Numero de la page
 * @param int $perPage Nombre d'elements par page
 * @param string $search Terme de recherche
 * @param string $type Filtre par type
 * @return array Donnees de pagination et liste des services
 */
function servicesGetList($page = 1, $perPage = 10, $search = '', $type = '') {
    $whereClauses = [];
    $params = [];

    if ($search) {
        $whereClauses[] = "(nom LIKE ? OR description LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    if ($type) {
        $whereClauses[] = "type = ?";
        $params[] = $type;
    }
    
    $whereSql = !empty($whereClauses) ? implode(' AND ', $whereClauses) : '1';

    $countSql = "SELECT COUNT(id) FROM " . TABLE_PRESTATIONS . " WHERE {$whereSql}";
    $totalServices = executeQuery($countSql, $params)->fetchColumn();
    
    $totalPages = ceil($totalServices / $perPage);
    $page = max(1, min($page, $totalPages)); 
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT * FROM " . TABLE_PRESTATIONS . " WHERE {$whereSql} ORDER BY nom ASC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $perPage;

    $services = executeQuery($sql, $params)->fetchAll();

    return [
        'services' => $services,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalServices,
        'perPage' => $perPage
    ];
}

/**
 * Recupere les details d'un service, et optionnellement les rendez-vous et evaluations associes.
 * 
 * @param int $id Identifiant du service
 * @param bool $fetchRelated Indique s'il faut recuperer les donnees associees (rendez-vous, evaluations)
 * @return array|false Donnees du service (et donnees associees si demande) ou false si non trouve
 */
function servicesGetDetails($id, $fetchRelated = false) {
    $service = executeQuery("SELECT * FROM " . TABLE_PRESTATIONS . " WHERE id = ? LIMIT 1", [$id])->fetch();

    if (!$service) {
        return false;
    }

    $result = ['service' => $service];

    if ($fetchRelated) {
        $sqlAppointments = "SELECT r.*, p.nom as nom_personne, p.prenom as prenom_personne 
                            FROM " . TABLE_APPOINTMENTS . " r 
                            LEFT JOIN " . TABLE_USERS . " p ON r.personne_id = p.id 
                            WHERE r.prestation_id = ? 
                            ORDER BY r.date_rdv DESC";
        $result['appointments'] = executeQuery($sqlAppointments, [$id])->fetchAll();
        
        $sqlEvaluations = "SELECT e.*, p.nom as nom_personne, p.prenom as prenom_personne 
                           FROM " . TABLE_EVALUATIONS . " e 
                           LEFT JOIN " . TABLE_USERS . " p ON e.personne_id = p.id 
                           WHERE e.prestation_id = ? 
                           ORDER BY e.date_evaluation DESC";
        $result['evaluations'] = executeQuery($sqlEvaluations, [$id])->fetchAll();
    }
    
    return $result;
}

/**
 * Recupere la liste des types de services
 * 
 * @return array Liste des types
 */
function servicesGetTypes() {
    $sql = "SELECT DISTINCT type FROM " . TABLE_PRESTATIONS . " ORDER BY type";
    return executeQuery($sql)->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Crée ou met à jour un service dans la base de données.
 *
 * Cette fonction vérifie que les données obligatoires (nom, prix et type) sont présentes et valides.
 * En cas d'erreur de validation, elle retourne un tableau contenant les messages d'erreur.
 * Si les validations réussissent, elle utilise updateRow ou insertRow pour effectuer l'opération.
 *
 * @param array $data Les informations du service.
 * @param int $id L'identifiant du service à mettre à jour, ou 0 pour créer.
 * @return array Résultat ['success' => bool, 'message' => string|null, 'errors' => array|null]
 */
function servicesSave($data, $id = 0) {
    $errors = [];
    
    if (empty($data['nom'])) {
        $errors[] = "Le nom du service est obligatoire";
    }
    
    if (!isset($data['prix']) || !is_numeric($data['prix']) || $data['prix'] < 0) {
        $errors[] = "Le prix du service est obligatoire et doit être un nombre positif ou nul";
    }
    
    if (empty($data['type'])) {
        $errors[] = "Le type de service est obligatoire";
    }
    
    if (!empty($errors)) {
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
    
    $dbData = [
        'nom' => $data['nom'], 
        'description' => $data['description'], 
        'prix' => $data['prix'],
        'duree' => $data['duree'] ? (int)$data['duree'] : null,
        'type' => $data['type'],
        'categorie' => $data['categorie'],
        'niveau_difficulte' => $data['niveau_difficulte'],
        'capacite_max' => $data['capacite_max'] ? (int)$data['capacite_max'] : null,
        'materiel_necessaire' => $data['materiel_necessaire'],
        'prerequis' => $data['prerequis']
    ];

    try {
        if ($id > 0) {
            $affectedRows = updateRow(TABLE_PRESTATIONS, $dbData, "id = ?", [$id]);
            
            if ($affectedRows !== false) {
                 logBusinessOperation($_SESSION['user_id'], 'service_update', 
                    "Mise à jour service: {$dbData['nom']} (ID: $id), type: {$dbData['type']}, prix: {$dbData['prix']}€");
                $message = "Le service a ete mis a jour avec succes";
                 return ['success' => true, 'message' => $message];
            } else {
                throw new Exception("La mise à jour a échoué ou aucune ligne n'a été modifiée.");
            }
        } 
        else {
            $newId = insertRow(TABLE_PRESTATIONS, $dbData);
            
            if ($newId) {
                logBusinessOperation($_SESSION['user_id'], 'service_create', 
                    "Création service: {$dbData['nom']} (ID: $newId), type: {$dbData['type']}, prix: {$dbData['prix']}€");
                $message = "Le service a ete cree avec succes";
                 return ['success' => true, 'message' => $message, 'newId' => $newId];
            } else {
                 throw new Exception("L'insertion a échoué.");
            }
        }
        
    } catch (Exception $e) {
        $errorMessage = "Erreur de base de données : " . $e->getMessage();
        $errors[] = $errorMessage;
        logSystemActivity('error', "Erreur BDD dans servicesSave: " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/**
 * Gère la soumission du formulaire d'ajout/modification de service.
 *
 * Valide le token CSRF, prépare les données, et appelle servicesSave.
 * Retourne un tableau avec le résultat de l'opération (succès, message/erreurs).
 *
 * @param array $postData Données du formulaire ($_POST)
 * @param int $id ID du service (0 pour ajout)
 * @return array Résultat de l'opération ['success' => bool, 'message' => string|null, 'errors' => array|null]
 */
function servicesHandlePostRequest($postData, $id) {
    if (!validateToken($postData['csrf_token'] ?? '')) {
        return [
            'success' => false,
            'errors' => ["Erreur de sécurité, veuillez réessayer."]
        ];
    }
    
    $data = [
        'nom' => $postData['nom'] ?? '',
        'description' => $postData['description'] ?? '',
        'prix' => $postData['prix'] ?? '',
        'duree' => $postData['duree'] ?? null,
        'type' => $postData['type'] ?? '',
        'categorie' => $postData['categorie'] ?? null,
        'statut' => 'actif',
        'niveau_difficulte' => $postData['niveau_difficulte'] ?? null,
        'capacite_max' => $postData['capacite_max'] ?? null,
        'materiel_necessaire' => $postData['materiel_necessaire'] ?? null,
        'prerequis' => $postData['prerequis'] ?? null
    ];

    return servicesSave($data, $id);
}

/**
 * Supprime un service après vérification des dépendances associées.
 *
 * Utilise les fonctions de db.php pour vérifier les dépendances et supprimer.
 *
 * @param int $id L'identifiant du service à supprimer.
 * @return array Résultat ['success' => bool, 'message' => string]
 */
function servicesDelete($id) {
    $appointmentCount = executeQuery("SELECT COUNT(id) FROM " . TABLE_APPOINTMENTS . " WHERE prestation_id = ?", [$id])->fetchColumn();

    if ($appointmentCount > 0) {
        logBusinessOperation($_SESSION['user_id'], 'service_delete_attempt', 
            "Tentative échouée de suppression service ID: $id - Rendez-vous associés existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer ce service car il a des rendez-vous associes"
        ];
    } 
    
    $evaluationCount = executeQuery("SELECT COUNT(id) FROM " . TABLE_EVALUATIONS . " WHERE prestation_id = ?", [$id])->fetchColumn();

    if ($evaluationCount > 0) { 
        logBusinessOperation($_SESSION['user_id'], 'service_delete_attempt', 
            "Tentative échouée de suppression service ID: $id - Évaluations associées existent");
        return [
            'success' => false,
            'message' => "Impossible de supprimer ce service car il a des evaluations associees"
        ];
    }
    
    try {
        $deletedRows = deleteRow(TABLE_PRESTATIONS, "id = ?", [$id]);
        
        if ($deletedRows > 0) {
            logBusinessOperation($_SESSION['user_id'], 'service_delete', 
                "Suppression service ID: $id");
            return [
                'success' => true,
                'message' => "Le service a ete supprime avec succes"
            ];
        } else {
             logBusinessOperation($_SESSION['user_id'], 'service_delete_attempt', 
                "Tentative échouée de suppression service ID: $id - Service non trouvé ou déjà supprimé?");
            return [
                'success' => false,
                'message' => "Impossible de supprimer le service (non trouvé ou déjà supprimé)"
            ];
        }
    } catch (Exception $e) {
         logSystemActivity('error', "Erreur BDD dans servicesDelete: " . $e->getMessage());
         return [
            'success' => false,
            'message' => "Erreur de base de données lors de la suppression."
         ];
    }
} 