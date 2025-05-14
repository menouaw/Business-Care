<?php
require_once __DIR__ . '/../../init.php';



/**
 * Recupere la liste des evenements de type 'conference' avec pagination et filtrage
 *
 * @param int $page Numero de la page
 * @param int $perPage Nombre d'elements par page (defaut via constante)
 * @param string $search Terme de recherche
 * @param int $siteId Filtre par site
 * @param string $startDate Filtre par date de debut (YYYY-MM-DD)
 * @param string $endDate Filtre par date de fin (YYYY-MM-DD)
 * @return array Donnees de pagination et liste des conferences
 */
function conferencesGetList($page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE, $search = '', $siteId = 0, $startDate = '', $endDate = '') {
    $params = [];
    $conditions = ["e.type = 'conference'"]; 

    if ($siteId > 0) {
        $conditions[] = "e.site_id = ?";
        $params[] = (int)$siteId;
    }

    if ($search) {
        $conditions[] = "(e.titre LIKE ? OR e.description LIKE ?)";
        $searchTerm = "%{$search}%";
        array_push($params, $searchTerm, $searchTerm);
    }
    
    if ($startDate) {
        $conditions[] = "e.date_debut >= ?";
        $params[] = $startDate . ' 00:00:00';
    }
    
    if ($endDate) {
        $conditions[] = "e.date_debut <= ?"; 
        $params[] = $endDate . ' 23:59:59';
    }
    
    $whereSql = "WHERE " . implode(' AND ', $conditions);

    $countSql = "SELECT COUNT(e.id) 
                 FROM " . TABLE_EVENEMENTS . " e 
                 LEFT JOIN " . TABLE_SITES . " s ON e.site_id = s.id
                 {$whereSql}";
                 
    $totalConferences = executeQuery($countSql, $params)->fetchColumn();
    
    $totalPages = ceil($totalConferences / $perPage);
    $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT e.*, 
                   s.nom as site_nom, s.ville as site_ville
            FROM " . TABLE_EVENEMENTS . " e
            LEFT JOIN " . TABLE_SITES . " s ON e.site_id = s.id
            {$whereSql}
            ORDER BY e.date_debut DESC 
            LIMIT ?, ?";
            
    $paramsWithPagination = array_merge($params, [(int)$offset, (int)$perPage]);

    $conferences = executeQuery($sql, $paramsWithPagination)->fetchAll();

    return [
        'conferences' => $conferences,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalConferences,
        'perPage' => $perPage
    ];
}

/**
 * Recupere les details d'un evenement de type 'conference'
 *
 * @param int $id Identifiant de la conference
 * @return array|false Donnees de la conference ou false si non trouvee ou non de type conference
 */
function conferencesGetDetails($id) {
    $sql = "SELECT e.*, 
                   s.nom as site_nom, s.ville as site_ville
            FROM " . TABLE_EVENEMENTS . " e
            LEFT JOIN " . TABLE_SITES . " s ON e.site_id = s.id
            WHERE e.id = ? AND e.type = 'conference'
            LIMIT 1";
            
    return executeQuery($sql, [(int)$id])->fetch();
}

/**
 * Crée ou met à jour un evenement de type 'conference' dans la base de données.
 *
 * @param array $data Données de la conference.
 * @param int $id Identifiant de la conference (0 pour création).
 * @return array Résultat ['success' => bool, 'message' => string|null, 'errors' => array|null, 'newId' => int|null]
 */
function conferencesSave($data, $id = 0) {
    $errors = [];
    $isNew = ($id == 0);

    
    if (empty($data['titre'])) {
        $errors['titre'] = "Le titre est obligatoire.";
    }
    if (empty($data['date_debut'])) {
        $errors['date_debut'] = "La date de debut est obligatoire.";
    } else {
        try {
            new DateTime($data['date_debut']);
        } catch (Exception $e) {
            $errors['date_debut'] = "Format de date et heure de debut invalide.";
        }
    }
     if (!empty($data['date_fin'])) {
        try {
            new DateTime($data['date_fin']);
             if (isset($data['date_debut']) && $data['date_fin'] < $data['date_debut']) {
                $errors['date_fin'] = "La date de fin ne peut pas être antérieure à la date de début.";
            }
        } catch (Exception $e) {
            $errors['date_fin'] = "Format de date et heure de fin invalide.";
        }
    }
    
    $data['site_id'] = isset($data['site_id']) && $data['site_id'] !== '' ? (int)$data['site_id'] : null;
    if ($data['site_id'] !== null) {
        if (!fetchOne(TABLE_SITES, 'id = ?', '', [$data['site_id']])) {
             $errors['site_id'] = "Le site selectionne est invalide.";
        }
    }

     if (!empty($data['capacite_max']) && (!is_numeric($data['capacite_max']) || $data['capacite_max'] <= 0)) {
         $errors['capacite_max'] = "La capacite maximale doit etre un nombre positif.";
     }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    
    $dbData = [
        'titre' => $data['titre'],
        'description' => $data['description'] ?? null,
        'date_debut' => $data['date_debut'],
        'date_fin' => $data['date_fin'] ?? null,
        'lieu' => $data['lieu'] ?? null, 
        'type' => 'conference', 
        'capacite_max' => $data['capacite_max'] ?? null,
        'niveau_difficulte' => $data['niveau_difficulte'] ?? null,
        'materiel_necessaire' => $data['materiel_necessaire'] ?? null,
        'prerequis' => $data['prerequis'] ?? null,
        'site_id' => $data['site_id'],
        'organise_par_bc' => true, 
    ];

    
    
    
    
    
    

    try {
        beginTransaction();

        if (!$isNew) {
            
            $existingEvent = fetchOne(TABLE_EVENEMENTS, 'id = ? AND type = \'conference\'', '', [$id]);
            if (!$existingEvent) {
                 throw new Exception("Conference à mettre à jour non trouvée ou n'est pas de type conférence.");
            }

            $affectedRows = updateRow(TABLE_EVENEMENTS, $dbData, "id = :where_id", ['where_id' => $id]);
            
            

            logBusinessOperation($_SESSION['user_id'], 'conference_update', 
                "[SUCCESS] Mise à jour conférence ID: $id, Titre: {$dbData['titre']}");
            $message = "La conference a ete mise a jour avec succes.";
            commitTransaction();
            return ['success' => true, 'message' => $message, 'newId' => $id];
            
        } 
        else { 
            $newId = insertRow(TABLE_EVENEMENTS, $dbData);
            
            if ($newId) {
                

                logBusinessOperation($_SESSION['user_id'], 'conference_create', 
                    "[SUCCESS] Création conférence ID: $newId, Titre: {$dbData['titre']}");
                $message = "La conference a ete creee avec succes.";
                commitTransaction();
                return ['success' => true, 'message' => $message, 'newId' => $newId];
            } else {
                 throw new Exception("L'insertion de la conférence a échoué.");
            }
        }
        
    } catch (Exception $e) {
        rollbackTransaction();
        $action = $isNew ? 'création' : 'mise à jour';
        $errorMessage = "Erreur lors de la {$action} de la conférence : " . $e->getMessage();
        logSystemActivity('conference_save_error', "[ERROR] Erreur BDD dans conferencesSave (Action: {$action}, ID: {$id}): " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => ['db_error' => $errorMessage]
        ];
    }
}

/**
 * Supprime un evenement de type 'conference' de la base de données.
 *
 * @param int $id Identifiant de la conference à supprimer.
 * @return array Résultat ['success' => bool, 'message' => string]
 */
function conferencesDelete($id) {
    
    try {
        beginTransaction(); 
        
        $existingEvent = fetchOne(TABLE_EVENEMENTS, 'id = ? AND type = \'conference\'', '', [$id]);
        if (!$existingEvent) {
             rollbackTransaction();
             logBusinessOperation($_SESSION['user_id'], 'conference_delete_attempt', 
                "[ERROR] Tentative échouée de suppression conférence ID: $id - Non trouvée ou n'est pas de type conférence.");
             return [
                 'success' => false,
                 'message' => "Conference non trouvée ou n'est pas de type conférence." 
             ];
        }

        $deletedRows = deleteRow(TABLE_EVENEMENTS, "id = ?", [$id]);
        
        if ($deletedRows > 0) {
            commitTransaction(); 
            logBusinessOperation($_SESSION['user_id'], 'conference_delete', 
                "[SUCCESS] Suppression conférence ID: $id (Titre: {$existingEvent['titre']})");
            return [
                'success' => true,
                'message' => "La conference a ete supprimee avec succes"
            ];
        } else {
            
            
            rollbackTransaction(); 
            logBusinessOperation($_SESSION['user_id'], 'conference_delete_attempt', 
                "[ERROR] Tentative échouée de suppression conférence ID: $id - Aucune ligne affectée par la suppression.");
            return [
                'success' => false,
                'message' => "Impossible de supprimer la conference (aucune ligne affectée)"
            ];
        }
    } catch (Exception $e) {
        rollbackTransaction(); 
         logSystemActivity('error', "[ERROR] Erreur BDD dans conferencesDelete (ID: {$id}): " . $e->getMessage());
         return [
            'success' => false,
            'message' => "Erreur de base de données lors de la suppression." . $e->getMessage()
         ];
    }
} 

/**
 * Recupere la liste des sites pour les formulaires (ceux organisés par BC)
 *
 * @return array Liste des sites [id => 'Nom du Site (Ville)']
 */
function conferencesGetSites() {
    
    
    
    $sql = "SELECT id, nom, ville FROM " . TABLE_SITES . " ORDER BY nom";
    $sites = executeQuery($sql)->fetchAll();
    $options = [];
    foreach ($sites as $site) {
        $options[$site['id']] = $site['nom'] . ' (' . $site['ville'] . ')';
    }
    return $options;
}

/**
 * Recupere la liste des prestataires (personnes avec role_id ROLE_PRESTATAIRE et statut actif) pour les formulaires
 *
 * @return array Liste des prestataires [id => 'Nom Prenom (Email)']
 */
function conferencesGetPrestataires() {
     $sql = "SELECT id, nom, prenom, email FROM " . TABLE_USERS . " WHERE role_id = ? AND statut = 'actif' ORDER BY nom, prenom";
    $users = executeQuery($sql, [ROLE_PRESTATAIRE])->fetchAll();
    $options = [];
    foreach ($users as $user) {
        $options[$user['id']] = $user['nom'] . ' ' . $user['prenom'] . ' (' . $user['email'] . ')';
    }
    return $options;
}

