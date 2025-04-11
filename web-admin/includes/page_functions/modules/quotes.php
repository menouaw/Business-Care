<?php
require_once __DIR__ . '/../../init.php';

/**
 * Recupere la liste des devis avec pagination et filtrage
 * 
 * @param int $page Numero de la page
 * @param int $perPage Nombre d'elements par page
 * @param string $search Terme de recherche (sur ID devis ou nom entreprise)
 * @param string $status Filtre par statut
 * @param string $sector Filtre par secteur d'activité de l'entreprise
 * @param string $orderBy Clause ORDER BY
 * @return array Donnees de pagination et liste des devis
 */
function quotesGetList($page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE, $search = '', $status = '', $sector = '', $orderBy = 'd.created_at DESC') {
    $whereClauses = ['1=1'];
    $params = [];

    if ($search) {
        $whereClauses[] = "(d.id LIKE ? OR e.nom LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    if ($status && in_array($status, QUOTE_STATUSES)) {
        $whereClauses[] = "d.statut = ?";
        $params[] = $status;
    }

    if ($sector) {
        $whereClauses[] = "e.secteur_activite = ?";
        $params[] = $sector;
    }
    
    $whereSql = implode(' AND ', $whereClauses);
    $baseSql = "FROM " . TABLE_QUOTES . " d LEFT JOIN " . TABLE_COMPANIES . " e ON d.entreprise_id = e.id WHERE {$whereSql}";

    $countSql = "SELECT COUNT(d.id) " . $baseSql;
    $totalItems = executeQuery($countSql, $params)->fetchColumn();
    
    $totalPages = ceil($totalItems / $perPage);
    $page = max(1, min($page, (int)$totalPages)); 
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT d.*, e.nom as nom_entreprise " . $baseSql . " ORDER BY {$orderBy} LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $perPage;

    $items = executeQuery($sql, $params)->fetchAll();

    return [
        'items' => $items,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'perPage' => $perPage
    ];
}

/**
 * Recupere les details d'un devis et de ses lignes
 * 
 * @param int $id Identifiant du devis
 * @return array|false Donnees du devis et de ses lignes, ou false si non trouve
 */
function quotesGetDetails($id) {
    $quote = executeQuery(
        "SELECT d.*, e.nom as nom_entreprise, s.type as type_service, s.tarif_annuel_par_salarie 
         FROM " . TABLE_QUOTES . " d 
         LEFT JOIN " . TABLE_COMPANIES . " e ON d.entreprise_id = e.id 
         LEFT JOIN " . TABLE_SERVICES . " s ON d.service_id = s.id
         WHERE d.id = ? LIMIT 1", 
        [$id]
    )->fetch();

    if (!$quote) {
        return false;
    }

    $sqlLines = "SELECT dp.*, p.nom as nom_prestation, p.description as description_prestation 
                 FROM " . TABLE_QUOTE_PRESTATIONS . " dp 
                 JOIN " . TABLE_PRESTATIONS . " p ON dp.prestation_id = p.id 
                 WHERE dp.devis_id = ?";
                 
    $quoteLines = executeQuery($sqlLines, [$id])->fetchAll();
    
    return [
        'quote' => $quote,
        'lines' => $quoteLines
    ];
}

/**
 * Recupere la liste des statuts de devis possibles
 * 
 * @return array Liste des statuts
 */
function quotesGetStatuses() {
    return QUOTE_STATUSES;
}

/**
 * Recupere la liste des secteurs d'activite distincts des entreprises
 * 
 * @return array Liste des secteurs
 */
function quotesGetCompanySectors() {
     $sql = "SELECT DISTINCT secteur_activite FROM " . TABLE_COMPANIES . " WHERE secteur_activite IS NOT NULL AND secteur_activite != '' ORDER BY secteur_activite ASC";
    return executeQuery($sql)->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Recupere la liste des entreprises clientes
 * 
 * @return array Liste des entreprises (id, nom)
 */
function quotesGetCompanies() {
    return executeQuery("SELECT id, nom FROM " . TABLE_COMPANIES . " ORDER BY nom ASC")->fetchAll();
}

/**
 * Recupere la liste des prestations disponibles pour un devis
 * 
 * @return array Liste des prestations (id, nom, prix)
 */
function quotesGetPrestations() {
    return executeQuery("SELECT id, nom, prix FROM " . TABLE_PRESTATIONS . " ORDER BY nom ASC")->fetchAll();
}

/**
 * Recupere la liste des services (tiers) disponibles
 * 
 * @return array Liste des services (id, type, tarif_annuel_par_salarie)
 */
function quotesGetServices() {
    return executeQuery("SELECT id, type, tarif_annuel_par_salarie FROM " . TABLE_SERVICES . " WHERE actif = 1 ORDER BY ordre ASC")->fetchAll();
}

/**
 * Crée ou met à jour un devis et ses lignes dans la base de données.
 *
 * @param array $data Données du devis (entreprise_id, service_id, nombre_salaries_estimes, date_creation, date_validite, statut, conditions_paiement, delai_paiement, est_personnalise, notes_negociation)
 * @param array $lines Lignes de prestation additionnelles (optionnel) [['prestation_id' => int, 'quantite' => int, 'description_specifique' => string], ...]
 * @param int $id L'identifiant du devis à mettre à jour, ou 0 pour créer.
 * @return array Résultat ['success' => bool, 'message' => string|null, 'errors' => array|null, 'quoteId' => int|null]
 */
function quotesSave($data, $lines, $id = 0) {
    $errors = [];
    $totalHT = 0;
    $totalTTC = 0;
    $tvaAmount = 0;
    $selectedService = null;

    if (empty($data['entreprise_id'])) {
        $errors[] = "L'entreprise cliente est obligatoire.";
    }
    if (empty($data['date_creation'])) {
        $errors[] = "La date de création est obligatoire.";
    }
     if (empty($data['date_validite'])) {
        $errors[] = "La date de validité est obligatoire.";
    }
    if (!empty($data['statut']) && !in_array($data['statut'], QUOTE_STATUSES)) {
        $errors[] = "Le statut sélectionné n'est pas valide.";
    }
    
    
    if (!empty($data['service_id'])) {
        if (empty($data['nombre_salaries_estimes']) || !is_numeric($data['nombre_salaries_estimes']) || $data['nombre_salaries_estimes'] <= 0) {
            $errors[] = "Le nombre de salariés estimés est obligatoire et doit être positif.";
        } else {
            $selectedService = fetchOne(TABLE_SERVICES, "id = ? AND actif = 1", '', [(int)$data['service_id']]);
            if (!$selectedService) {
                 $errors[] = "Le service sélectionné est invalide ou inactif.";
            } else {
                 $totalHT = $selectedService['tarif_annuel_par_salarie'] * (int)$data['nombre_salaries_estimes'];
            }
        }
    } else {
         
         if (empty($lines)) {
              $errors[] = "Le devis doit contenir au moins une prestation ou être basé sur un service.";
         }
    }

    
    $validLines = [];
    if (!empty($lines)) {
        $prestationIds = array_column($lines, 'prestation_id');
        $availablePrestations = [];
        if (!empty($prestationIds)) {
            $placeholders = implode(',', array_fill(0, count($prestationIds), '?'));
            $availablePrestations = executeQuery("SELECT id, prix FROM " . TABLE_PRESTATIONS . " WHERE id IN ($placeholders)", $prestationIds)->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        foreach ($lines as $index => $line) {
            if (empty($line['prestation_id']) || !isset($availablePrestations[$line['prestation_id']])) {
                $errors[] = "Ligne de prestation " . ($index + 1) . ": Prestation invalide ou non trouvée.";
                continue;
            }
            if (!isset($line['quantite']) || !is_numeric($line['quantite']) || $line['quantite'] <= 0) {
                $errors[] = "Ligne de prestation " . ($index + 1) . ": Quantité invalide.";
                continue;
            }

            $prixUnitaire = $availablePrestations[$line['prestation_id']];
            $lineTotal = $prixUnitaire * $line['quantite'];
            
            
            if(empty($data['service_id'])) {
                $totalHT += $lineTotal;
            } 
            
            

            $validLines[] = [
                'prestation_id' => $line['prestation_id'],
                'quantite' => (int)$line['quantite'],
                'prix_unitaire_devis' => $prixUnitaire,
                'description_specifique' => $line['description_specifique'] ?? null
            ];
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    
    if ($totalHT >= 0) { 
        $tvaAmount = $totalHT * TVA_RATE;
        $totalTTC = $totalHT + $tvaAmount;
    } else {
         
         $totalHT = 0;
         $totalTTC = 0;
         $tvaAmount = 0;
         $errors[] = "Impossible de calculer le montant du devis.";
         return ['success' => false, 'errors' => $errors];
    }


    $dbData = [
        'entreprise_id' => (int)$data['entreprise_id'],
        'service_id' => !empty($data['service_id']) ? (int)$data['service_id'] : null,
        'nombre_salaries_estimes' => !empty($data['nombre_salaries_estimes']) ? (int)$data['nombre_salaries_estimes'] : null,
        'date_creation' => $data['date_creation'],
        'date_validite' => $data['date_validite'],
        'montant_total' => $totalTTC,
        'montant_ht' => $totalHT,
        'tva' => TVA_RATE * 100,
        'statut' => $data['statut'] ?: QUOTE_STATUS_PENDING,
        'conditions_paiement' => $data['conditions_paiement'] ?? null,
        'delai_paiement' => $data['delai_paiement'] ? (int)$data['delai_paiement'] : null,
        'est_personnalise' => isset($data['est_personnalise']) ? (bool)$data['est_personnalise'] : false,
        'notes_negociation' => $data['notes_negociation'] ?? null
    ];

    try {
        beginTransaction();
        $quoteId = $id;
        $logServiceInfo = $dbData['service_id'] ? "Service ID: {$dbData['service_id']}, Salaries: {$dbData['nombre_salaries_estimes']}" : "Prestations specifiques";

        if ($id > 0) { 
            updateRow(TABLE_QUOTES, $dbData, "id = :where_id", [':where_id' => $id]);
            
            deleteRow(TABLE_QUOTE_PRESTATIONS, "devis_id = ?", [$id]); 
            $logAction = 'quote_update';
            $logMessage = "Mise à jour devis ID: $id pour entreprise ID: {$dbData['entreprise_id']}, {$logServiceInfo}, Statut: {$dbData['statut']}, Montant HT: {$totalHT}€";
            $successMessage = "Le devis a été mis à jour avec succès";
        } else {
            $quoteId = insertRow(TABLE_QUOTES, $dbData);
            if (!$quoteId) {
                throw new Exception("Échec de la création de l'enregistrement principal du devis.");
            }
            $logAction = 'quote_create';
             $logMessage = "Création devis ID: $quoteId pour entreprise ID: {$dbData['entreprise_id']}, {$logServiceInfo}, Statut: {$dbData['statut']}, Montant HT: {$totalHT}€";
            $successMessage = "Le devis a été créé avec succès";
        }

        
        foreach ($validLines as $lineData) {
            $lineData['devis_id'] = $quoteId;
            insertRow(TABLE_QUOTE_PRESTATIONS, $lineData);
        }

        commitTransaction();
        logBusinessOperation($_SESSION['user_id'], $logAction, $logMessage);
        return ['success' => true, 'message' => $successMessage, 'quoteId' => $quoteId];

    } catch (Exception $e) {
        rollbackTransaction();
        $errorMessage = "Erreur de base de données : " . $e->getMessage();
        $errors[] = $errorMessage;
        logSystemActivity('error', "Erreur BDD dans quotesSave: " . $e->getMessage());
        return ['success' => false, 'errors' => $errors];
    }
}

/**
 * Gère la soumission du formulaire d'ajout/modification de devis.
 *
 * @param array $postData Données du formulaire ($_POST)
 * @param int $id ID du devis (0 pour ajout)
 * @return array Résultat de l'opération ['success' => bool, 'message' => string|null, 'errors' => array|null, 'quoteId' => int|null]
 */
function quotesHandlePostRequest($postData, $id) {
    if (!validateToken($postData['csrf_token'] ?? '')) {
        return [
            'success' => false,
            'errors' => ["Erreur de sécurité, veuillez réessayer."]
        ];
    }
    
    $data = [
        'entreprise_id' => $postData['entreprise_id'] ?? null,
        'service_id' => $postData['service_id'] ?? null, 
        'nombre_salaries_estimes' => $postData['nombre_salaries_estimes'] ?? null, 
        'date_creation' => $postData['date_creation'] ?? date('Y-m-d'),
        'date_validite' => $postData['date_validite'] ?? null,
        'statut' => $postData['statut'] ?? QUOTE_STATUS_PENDING,
        'conditions_paiement' => $postData['conditions_paiement'] ?? null,
        'delai_paiement' => $postData['delai_paiement'] ?? null,
        'est_personnalise' => isset($postData['est_personnalise']) && $postData['est_personnalise'] == 1, 
        'notes_negociation' => $postData['notes_negociation'] ?? null
    ];

    $lines = [];
    if (isset($postData['prestation_id']) && is_array($postData['prestation_id'])) {
        foreach ($postData['prestation_id'] as $index => $prestationId) {
            
             if (!empty($prestationId) && isset($postData['quantite'][$index]) && $postData['quantite'][$index] > 0) {
                $lines[] = [
                    'prestation_id' => (int)$prestationId,
                    'quantite' => (int)$postData['quantite'][$index],
                    'description_specifique' => $postData['description_specifique'][$index] ?? null
                ];
            }
        }
    }

    
    if (empty($data['service_id']) && empty($lines)) {
         return [
            'success' => false,
            'errors' => ["Vous devez sélectionner un service ou ajouter au moins une prestation au devis."]
        ];
    }

    return quotesSave($data, $lines, $id);
}

/**
 * Supprime un devis s'il est dans un statut approprié (ex: 'en_attente').
 *
 * @param int $id L'identifiant du devis à supprimer.
 * @return array Résultat ['success' => bool, 'message' => string]
 */
function quotesDelete($id) {
    try {
        beginTransaction();
        
        $quote = fetchOne(TABLE_QUOTES, "id = ?", '', [$id]);
        if (!$quote) {
            rollbackTransaction();
            return ['success' => false, 'message' => "Devis non trouvé."];
        }

        deleteRow(TABLE_QUOTE_PRESTATIONS, "devis_id = ?", [$id]);
        
        $deletedRows = deleteRow(TABLE_QUOTES, "id = ?", [$id]);
        
        if ($deletedRows > 0) {
            commitTransaction();
            logBusinessOperation($_SESSION['user_id'], ':quote_delete', "Suppression devis ID: $id");
            return ['success' => true, 'message' => "Le devis a été supprimé avec succès"];
        } else {
            rollbackTransaction();
            logBusinessOperation($_SESSION['user_id'], ':quote_delete_attempt', "[ERROR] ID: $id - Échec suppression enregistrement principal");
            return ['success' => false, 'message' => "Impossible de supprimer le devis (erreur inattendue)."];
        }
    } catch (Exception $e) {
        rollbackTransaction();
         logSystemActivity('error', "Erreur BDD dans quotesDelete: " . $e->getMessage());
         return ['success' => false, 'message' => "Erreur de base de données lors de la suppression."];
    }
}
?>