<?php
require_once __DIR__ . '/../../init.php';

/**
 * recupere la liste des dons avec pagination et filtrage
 * 
 * @param int $page numero de la page
 * @param int $perPage nombre d'elements par page
 * @param string $search terme de recherche (nom/prenom donateur)
 * @param string $status filtre par statut
 * @param string $type filtre par type de don
 * @param string $startDate filtre par date de debut (YYYY-MM-DD)
 * @param string $endDate filtre par date de fin (YYYY-MM-DD)
 * @return array donnees de pagination et liste des dons
 */
function donationsGetList($page = 1, $perPage = DEFAULT_ITEMS_PER_PAGE, $search = '', $status = '', $type = '', $startDate = '', $endDate = '') {
    $params = [];
    $conditions = [];

    if ($search) {
        $conditions[] = "(p_donor.nom LIKE ? OR p_donor.prenom LIKE ?)";
        $searchTerm = "%{$search}%";
        array_push($params, $searchTerm, $searchTerm);
    }

    if ($status && in_array($status, DONATION_STATUSES)) {
        $conditions[] = "d.statut = ?";
        $params[] = $status;
    }

    if ($type && in_array($type, DONATION_TYPES)) {
        $conditions[] = "d.type = ?";
        $params[] = $type;
    }

    if ($startDate) {
        $conditions[] = "d.date_don >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $conditions[] = "d.date_don <= ?";
        $params[] = $endDate;
    }
    
    $whereSql = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : '';

    $countSql = "SELECT COUNT(d.id) 
                 FROM " . TABLE_DONATIONS . " d
                 LEFT JOIN " . TABLE_USERS . " p_donor ON d.personne_id = p_donor.id
                 {$whereSql}";
                 
    $totalDonations = executeQuery($countSql, $params)->fetchColumn();
    
    $totalPages = ceil($totalDonations / $perPage);
    $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT d.*, 
                   p_donor.nom as donor_nom, p_donor.prenom as donor_prenom, p_donor.email as donor_email
            FROM " . TABLE_DONATIONS . " d
            LEFT JOIN " . TABLE_USERS . " p_donor ON d.personne_id = p_donor.id
            {$whereSql}
            ORDER BY d.created_at DESC 
            LIMIT ?, ?";
            
    $paramsWithPagination = array_merge($params, [(int)$offset, (int)$perPage]);

    $donations = executeQuery($sql, $paramsWithPagination)->fetchAll();

    // formater les montants
    foreach ($donations as &$donation) {
        if ($donation['type'] == 'financier' && isset($donation['montant'])) {
            $donation['montant_formate'] = formatMoney($donation['montant']);
        }
    }

    return [
        'donations' => $donations,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalDonations,
        'perPage' => $perPage
    ];
}

/**
 * recupere les details d'un don
 * 
 * @param int $id identifiant du don
 * @return array|false donnees du don ou false si non trouve
 */
function donationsGetDetails($id) {
    $sql = "SELECT d.*, 
                   p_donor.nom as donor_nom, p_donor.prenom as donor_prenom, p_donor.email as donor_email
            FROM " . TABLE_DONATIONS . " d
            LEFT JOIN " . TABLE_USERS . " p_donor ON d.personne_id = p_donor.id
            WHERE d.id = ? 
            LIMIT 1";
            
    $donation = executeQuery($sql, [$id])->fetch();

    if ($donation && $donation['type'] == 'financier' && isset($donation['montant'])) {
        $donation['montant_formate'] = formatMoney($donation['montant']);
    }

    return $donation;
}

/**
 * met a jour le statut d'un don
 *
 * @param int $id identifiant du don
 * @param string $newStatus nouveau statut ('valide', 'refuse')
 * @return array resultat ['success' => bool, 'message' => string]
 */
function donationsUpdateStatus($id, $newStatus) {
    if (!in_array($newStatus, ['valide', 'refuse'])) {
        return ['success' => false, 'message' => "statut invalide."];
    }

    $donation = donationsGetDetails($id);
    if (!$donation) {
        return ['success' => false, 'message' => "don non trouvé."];
    }

    if ($donation['statut'] !== 'en_attente') {
         return ['success' => false, 'message' => "le statut de ce don ne peut plus etre modifie."];
    }

    try {
        beginTransaction();

        $affectedRows = updateRow(TABLE_DONATIONS, 
            ['statut' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')], 
            "id = :where_id", 
            ['where_id' => $id]
        );

        if ($affectedRows > 0) {
            commitTransaction();
            logBusinessOperation(
                $_SESSION['user_id'] ?? null, 
                'donation_status_update', 
                "[SUCCESS] Mise à jour statut Don ID: $id vers '$newStatus'"
            );
            // TODO: ajouter une notification a l'utilisateur ici si necessaire
            return [
                'success' => true,
                'message' => "le statut du don a ete mis a jour avec succes."
            ];
        } else {
            rollbackTransaction(); 
            return [
                'success' => false,
                'message' => "aucune modification n'a ete effectuee (statut deja a jour?)."
            ];
        }
    } catch (Exception $e) {
        rollbackTransaction();
        logSystemActivity('error', "[ERROR] Erreur BDD dans donationsUpdateStatus: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "erreur de base de donnees lors de la mise a jour du statut."
        ];
    }
}

/**
 * recupere la liste des types de dons possibles
 * 
 * @return array liste des types [valeur => Libelle]
 */
function donationsGetTypes() {
    $types = [];
    foreach (DONATION_TYPES as $type) {
        $types[$type] = ucfirst($type);
    }
    return $types;
}

/**
 * recupere la liste des statuts de dons possibles
 * 
 * @return array liste des statuts [valeur => Libelle]
 */
function donationsGetStatuses() {
    $statuses = [];
    foreach (DONATION_STATUSES as $status) {
        $statuses[$status] = ucfirst(str_replace('_', ' ', $status));
    }
    return $statuses;
}

/**
 * recupere la liste des donateurs potentiels (salaries actifs)
 * 
 * @return array liste des salaries [id => 'Nom Prenom (Email)']
 */
function donationsGetDonors() {
    $sql = "SELECT id, nom, prenom, email FROM " . TABLE_USERS . " WHERE role_id = ? AND statut = 'actif' ORDER BY nom, prenom";
    $users = executeQuery($sql, [ROLE_SALARIE])->fetchAll();
    $options = [];
    foreach ($users as $user) {
        $options[$user['id']] = $user['nom'] . ' ' . $user['prenom'] . ' (' . $user['email'] . ')';
    }
    return $options;
}
?>
