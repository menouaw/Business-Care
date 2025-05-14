<?php
require_once __DIR__ . '/../../init.php';

/**
 * Recupere une liste paginee de tous les evenements, avec filtrage par type, recherche, site, si organise par BC, et dates.
 *
 * @param int $page Numero de la page
 * @param int $perPage Nombre d'elements par page (defaut via constante)
 * @param string $search Terme de recherche (titre, description, lieu)
 * @param string $type Filtre par type d'evenement (conference, webinar, atelier, etc.). Peut etre vide pour tous les types.
 * @param int $siteId Filtre par site (sites.id).
 * @param string $organiseParBc Filtre par si l'evenement est organise par BC ('oui', 'non', ou vide pour tous).
 * @param string $startDate Filtre par date de debut (YYYY-MM-DD)
 * @param string $endDate Filtre par date de fin (YYYY-MM-DD)
 * @return array Donnees de pagination et liste des evenements
 */
function eventsGetList($page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE, $search = '', $type = '', $siteId = 0, $organiseParBc = '', $startDate = '', $endDate = '') {
    $params = [];
    $conditions = [];

    if ($type && in_array($type, EVENEMENT_TYPES)) {
        $conditions[] = "e.type = ?";
        $params[] = $type;
    }

    if ($siteId > 0) {
        $conditions[] = "e.site_id = ?";
        $params[] = (int)$siteId;
    }
    
    if ($organiseParBc !== '') {
        $conditions[] = "e.organise_par_bc = ?";
        $params[] = ($organiseParBc === 'oui');
    }

    if ($search) {
        $conditions[] = "(e.titre LIKE ? OR e.description LIKE ? OR e.lieu LIKE ?)";
        $searchTerm = "%{$search}%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
    }
    
    if ($startDate) {
        $conditions[] = "e.date_debut >= ?";
        $params[] = $startDate . ' 00:00:00';
    }
    
    if ($endDate) {
        $conditions[] = "e.date_debut <= ?"; 
        $params[] = $endDate . ' 23:59:59';
    }
    
    $whereSql = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : '';

    $countSql = "SELECT COUNT(e.id) 
                 FROM " . TABLE_EVENEMENTS . " e 
                 LEFT JOIN " . TABLE_SITES . " s ON e.site_id = s.id
                 {$whereSql}";
                 
    $totalEvents = executeQuery($countSql, $params)->fetchColumn();
    
    $totalPages = ceil($totalEvents / $perPage);
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

    $events = executeQuery($sql, $paramsWithPagination)->fetchAll();

    return [
        'events' => $events,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalEvents,
        'perPage' => $perPage
    ];
}

/**
 * Recupere les details d'un evenement specifique.
 *
 * @param int $id Identifiant de l'evenement
 * @return array|false Donnees de l'evenement ou false si non trouve
 */
function eventsGetDetails($id) {
    $sql = "SELECT e.*, 
                   s.nom as site_nom, s.ville as site_ville
            FROM " . TABLE_EVENEMENTS . " e
            LEFT JOIN " . TABLE_SITES . " s ON e.site_id = s.id
            WHERE e.id = ? 
            LIMIT 1";
            
    return executeQuery($sql, [(int)$id])->fetch();
}

/**
 * Crée ou met à jour un evenement dans la base de données.
 *
 * @param array $data Données de l'evenement.
 * @param int $id Identifiant de l'evenement (0 pour création).
 * @return array Résultat ['success' => bool, 'message' => string|null, 'errors' => array|null, 'newId' => int|null]
 */
function eventsSave($data, $id = 0) {
    $errors = [];
    $isNew = ($id == 0);

    
    if (empty($data['titre'])) {
        $errors['titre'] = "Le titre est obligatoire.";
    }
    if (empty($data['type']) || !in_array($data['type'], EVENEMENT_TYPES)) {
        $errors['type'] = "Le type d'evenement selectionne est invalide.";
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
    } else {
        
        
    }

     if (!empty($data['capacite_max']) && (!is_numeric($data['capacite_max']) || $data['capacite_max'] <= 0)) {
         $errors['capacite_max'] = "La capacite maximale doit etre un nombre positif.";
     }
    
    
    if (!empty($data['niveau_difficulte']) && !in_array($data['niveau_difficulte'], PRESTATION_DIFFICULTIES)) {
         $errors['niveau_difficulte'] = "Le niveau de difficulte selectionne est invalide.";
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
        'type' => $data['type'], 
        'capacite_max' => $data['capacite_max'] ?? null,
        'niveau_difficulte' => $data['niveau_difficulte'] ?? null,
        'materiel_necessaire' => $data['materiel_necessaire'] ?? null,
        'prerequis' => $data['prerequis'] ?? null,
        'site_id' => $data['site_id'],
        'organise_par_bc' => $data['organise_par_bc'] ?? true, 
    ];

    
    try {
        beginTransaction();

        if (!$isNew) {
            
            $existingEvent = fetchOne(TABLE_EVENEMENTS, 'id = ?', '', [$id]);
            if (!$existingEvent) {
                 throw new Exception("Evenement à mettre à jour non trouvé.");
            }

            $affectedRows = updateRow(TABLE_EVENEMENTS, $dbData, "id = :where_id", ['where_id' => $id]);
            
            
            logBusinessOperation($_SESSION['user_id'], 'event_update', 
                "[INFO] Mise à jour evenement ID: $id, Titre: {$dbData['titre']} (Lignes affectees: {$affectedRows})");
            
            $message = "L'evenement a ete mis a jour avec succes.";
            commitTransaction();
            return ['success' => true, 'message' => $message, 'newId' => $id]; 
            
        } 
        else { 
            
            $newId = insertRow(TABLE_EVENEMENTS, $dbData);
            
            if ($newId) {
                logBusinessOperation($_SESSION['user_id'], 'event_create', 
                    "[SUCCESS] Création evenement ID: $newId, Titre: {$dbData['titre']}");
                $message = "L'evenement a ete cree avec succes.";
                commitTransaction();
                return ['success' => true, 'message' => $message, 'newId' => $newId];
            } else {
                 throw new Exception("L'insertion de l'evenement a échoué en base de données.");
            }
        }
        
    } catch (Exception $e) {
        rollbackTransaction();
        $action = $isNew ? 'création' : 'mise à jour';
        $errorMessage = "Erreur lors de la {$action} de l'evenement : " . $e->getMessage();
        logSystemActivity('event_save_error', "[ERROR] Erreur BDD dans eventsSave (Action: {$action}, ID: {$id}): " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => ['db_error' => $errorMessage]
        ];
    }
}

/**
 * Supprime un evenement de la base de données.
 *
 * @param int $id Identifiant de l'evenement à supprimer.
 * @return array Résultat ['success' => bool, 'message' => string]
 */
function eventsDelete($id) {
    
    try {
        beginTransaction(); 
        
        
        $existingEvent = fetchOne(TABLE_EVENEMENTS, 'id = ?', '', [$id]);
        if (!$existingEvent) {
             rollbackTransaction();
             logBusinessOperation($_SESSION['user_id'] ?? 0, 'event_delete_attempt', 
                "[ERROR] Tentative échouée de suppression evenement ID: $id - Non trouvé.");
             return [
                 'success' => false,
                 'message' => "Evenement non trouvé." 
             ];
        }

        $deletedRows = deleteRow(TABLE_EVENEMENTS, "id = ?", [$id]);
        
        if ($deletedRows > 0) {
            commitTransaction(); 
            logBusinessOperation($_SESSION['user_id'] ?? 0, 'event_delete', 
                "[SUCCESS] Suppression evenement ID: $id (Titre: {$existingEvent['titre']})");
            return [
                'success' => true,
                'message' => "L'evenement a ete supprime avec succes"
            ];
        } else {
            
            rollbackTransaction(); 
            logBusinessOperation($_SESSION['user'], 'event_delete_attempt', 
                "[ERROR] Tentative échouée de suppression evenement ID: $id - Aucune ligne affectée par la suppression (BDD?).");
            return [
                'success' => false,
                'message' => "Impossible de supprimer l'evenement (aucune ligne affectée)"
            ];
        }
    } catch (Exception $e) {
        rollbackTransaction(); 
         logSystemActivity('error', "[ERROR] Erreur BDD dans eventsDelete (ID: {$id}): " . $e->getMessage());
         return [
            'success' => false,
            'message' => "Erreur de base de données lors de la suppression." . $e->getMessage()
         ];
    }
} 

/**
 * Recupere la liste des sites pour les formulaires (ceux organisés par BC ou externes)
 *
 * @return array Liste des sites [id => 'Nom du Site (Ville)']
 */
function eventsGetSites() {
    $sql = "SELECT id, nom, ville FROM " . TABLE_SITES . " ORDER BY nom";
    $sites = executeQuery($sql)->fetchAll();
    $options = [];
    foreach ($sites as $site) {
        $options[$site['id']] = $site['nom'] . ' (' . $site['ville'] . ')';
    }
    return $options;
}

/**
 * Recupere la liste des types d'evenements possibles.
 *
 * @return array Liste des types [valeur => Libelle]
 */
function eventsGetTypes() {
    $types = [];
    
    foreach (EVENEMENT_TYPES as $type) {
        $types[$type] = ucfirst(str_replace('_', ' ', $type));
    }
    return $types;
}

/**
 * Recupere la liste des options pour le niveau de difficulte.
 *
 * @return array Liste des niveaux [valeur => Libelle]
 */
function eventsGetNiveauDifficulteOptions() {
    $difficulties = [];
    
    foreach (PRESTATION_DIFFICULTIES as $difficulty) {
        $difficulties[$difficulty] = ucfirst(str_replace('_', ' ', $difficulty));
    }
    return $difficulties;
}

/**
 * Recupere la liste des prestataires (personnes avec role_id ROLE_PRESTATAIRE et statut actif) pour les formulaires
 *
 * @return array Liste des prestataires [id => 'Nom Prenom (Email)']
 */
function eventsGetPotentialIntervenants() {
     $sql = "SELECT id, nom, prenom, email FROM " . TABLE_USERS . " WHERE role_id = ? AND statut = 'actif' ORDER BY nom, prenom";
    $users = executeQuery($sql, [ROLE_PRESTATAIRE])->fetchAll();
    $options = [];
    foreach ($users as $user) {
        $options[$user['id']] = $user['nom'] . ' ' . $user['prenom'] . ' (' . $user['email'] . ')';
    }
    return $options;
}

/**
 * Recupere la liste des personnes inscrites a un evenement donne.
 *
 * @param int $evenementId L'identifiant de l'evenement.
 * @return array Liste des personnes inscrites.
 */
function eventsGetInscriptions($evenementId) {
    $sql = "SELECT p.id, p.nom, p.prenom, p.email, ei.statut
            FROM " . TABLE_EVENEMENT_INSCRIPTIONS . " ei
            JOIN " . TABLE_USERS . " p ON ei.personne_id = p.id
            WHERE ei.evenement_id = ?
            ORDER BY p.nom, p.prenom";
    return executeQuery($sql, [$evenementId])->fetchAll();
}
?>
