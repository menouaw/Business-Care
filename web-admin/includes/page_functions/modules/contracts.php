<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../functions.php';

/**
 * Recupere tous les contrats avec pagination
 * 
 * @param int $page Numéro de la page
 * @param int $perPage Nombre d'éléments par page
 * @param string $search Terme de recherche
 * @param string $status Filtre par statut
 * @return array Informations de pagination et liste des contrats
 */
function contractsGetList($page = 1, $perPage = 20, $search = '', $status = '') {
    $where = '';
    $conditions = [];
    
    if ($search) {
        $conditions[] = "(reference LIKE '%$search%' OR description LIKE '%$search%')";
    }
    
    if ($status) {
        $conditions[] = "statut = '$status'";
    }
    
    if (!empty($conditions)) {
        $where = implode(' AND ', $conditions);
    }
    
    return paginateResults('contrats', $page, $perPage, $where, 'date_debut DESC');
}

/**
 * Récupère les détails complets d'un contrat donné.
 *
 * Cette fonction renvoie les informations associées à un contrat identifié par son ID.
 * Si le contrat est trouvé, le tableau retourné est enrichi avec les données de l'entreprise associée
 * ainsi que la liste des paiements du contrat, ordonnés par date décroissante. Si aucun contrat
 * ne correspond à l'ID fourni, la fonction retourne false.
 *
 * @param int $id Identifiant unique du contrat.
 * @return array|false Tableau des données du contrat incluant l'entreprise et les paiements, ou false si non trouvé.
 */
function contractsGetDetails($id) {
    $contract = fetchOne('contrats', "id = $id");
    
    if (!$contract) {
        return false;
    }
    
    // Charger les informations supplementaires
    $contract['entreprise'] = fetchOne('entreprises', "id = {$contract['entreprise_id']}");
    $contract['paiements'] = fetchAll('paiements', "contrat_id = $id", 'date DESC');
    
    return $contract;
}

/**
 * Met à jour le statut d'un contrat après validation.
 *
 * Vérifie que le nouveau statut appartient à la liste des statuts acceptés et, en cas de succès,
 * met à jour le registre du contrat dans la base de données tout en consignant l'activité de mise à jour.
 *
 * @param int $id Identifiant du contrat.
 * @param string $status Nouveau statut à appliquer ('actif', 'inactif', 'en_attente', 'suspendu', 'expire', 'resilie').
 * @return bool Retourne true si la mise à jour est réussie, false sinon.
 */
function contractsUpdateStatus($id, $status) {
    $validStatuses = ['actif', 'inactif', 'en_attente', 'suspendu', 'expire', 'resilie'];
    
    if (!in_array($status, $validStatuses)) {
        return false;
    }
    
    $result = updateRow('contrats', ['statut' => $status], "id = $id");
    
    if ($result) {
        logActivity($_SESSION['user_id'], 'update_contract_status', "Statut du contrat #$id mis à jour: $status");
    }
    
    return $result;
} 