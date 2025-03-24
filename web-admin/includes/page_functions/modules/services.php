<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../functions.php';

/**
 * Récupère la liste paginée des services avec support de filtrage par terme de recherche et par type.
 *
 * Cette fonction construit dynamiquement une clause SQL en fonction des critères fournis, calcule le nombre total
 * d'éléments, ajuste la pagination et renvoie un tableau associatif contenant les services ainsi que les informations
 * de pagination.
 *
 * @param int $page Numéro de la page à afficher, au minimum 1.
 * @param int $perPage Nombre d'éléments par page.
 * @param string $search Terme de recherche à appliquer sur le nom ou la description du service.
 * @param string $type Filtre optionnel pour limiter les résultats à un type de service spécifique.
 *
 * @return array Tableau associatif comprenant :
 *               - 'services' : Liste des services pour la page courante.
 *               - 'currentPage' : Numéro de la page affichée.
 *               - 'totalPages' : Nombre total de pages disponibles.
 *               - 'totalItems' : Nombre total de services correspondant aux critères.
 *               - 'perPage' : Nombre d'éléments par page.
 */
function servicesGetList($page = 1, $perPage = 10, $search = '', $type = '') {
    $where = '';
    $params = [];
    $conditions = [];

    if ($search) {
        $conditions[] = "(nom LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($type) {
        $conditions[] = "type = ?";
        $params[] = $type;
    }
    
    if (!empty($conditions)) {
        $where = "WHERE " . implode(' AND ', $conditions);
    }

    // recupere les services pagines
    $offset = ($page - 1) * $perPage;

    $pdo = getDbConnection();
    $countSql = "SELECT COUNT(id) FROM prestations $where";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalServices = $countStmt->fetchColumn();
    $totalPages = ceil($totalServices / $perPage);
    $page = max(1, min($page, $totalPages));

    $sql = "SELECT * FROM prestations $where ORDER BY nom ASC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $perPage;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'services' => $services,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalServices,
        'perPage' => $perPage
    ];
}

/**
 * Recupere les details d'un service
 * 
 * @param int $id Identifiant du service
 * @return array|false Donnees du service ou false si non trouve
 */
function servicesGetDetails($id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM prestations WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        return false;
    }
    
    return $service;
}

/**
 * Récupère la liste distincte des types de services.
 *
 * Exécute une requête SQL sur la table "prestations" pour obtenir les différents types de services,
 * triés par ordre alphabétique, et retourne ces types sous forme de tableau.
 *
 * @return array Les types de services.
 */
function servicesGetTypes() {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT DISTINCT type FROM prestations ORDER BY type");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Crée ou met à jour un service.
 *
 * Cette fonction valide les données essentielles d'un service (notamment 'nom', 'prix' et 'type') et, en fonction de l'ID fourni,
 * met à jour un service existant (si l'ID est supérieur à 0) ou en crée un nouveau. En cas d'erreur de validation ou de problème
 * lors de l'exécution de la requête SQL, la fonction retourne un tableau avec 'success' à false et un tableau d'erreurs. Sinon,
 * un message confirmant l'opération réussie est renvoyé avec 'success' à true.
 *
 * @param array $data Données du service comprenant au minimum 'nom', 'prix' et 'type'. Des champs optionnels tels que 'description',
 *                    'duree', 'categorie', 'niveau_difficulte', 'capacite_max', 'materiel_necessaire' et 'prerequis' peuvent également être fournis.
 * @param int $id Identifiant du service à mettre à jour, ou 0 pour la création d'un nouveau service.
 * @return array Tableau associatif indiquant le résultat de l'opération, avec 'success' (bool) et soit 'message' (string) en cas de succès,
 *               soit 'errors' (array) en cas d'erreur.
 */
function servicesSave($data, $id = 0) {
    $errors = [];
    
    if (empty($data['nom'])) {
        $errors[] = "Le nom du service est obligatoire";
    }
    
    if (empty($data['prix']) || !is_numeric($data['prix'])) {
        $errors[] = "Le prix du service est obligatoire et doit etre un nombre";
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
    
    $pdo = getDbConnection();
    
    try {
        // cas de mise a jour
        if ($id > 0) {
            $sql = "UPDATE prestations SET 
                    nom = ?, description = ?, prix = ?, duree = ?, 
                    type = ?, categorie = ?, niveau_difficulte = ?, 
                    capacite_max = ?, materiel_necessaire = ?, prerequis = ? 
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nom'], 
                $data['description'], 
                $data['prix'],
                $data['duree'],
                $data['type'],
                $data['categorie'],
                $data['niveau_difficulte'],
                $data['capacite_max'],
                $data['materiel_necessaire'],
                $data['prerequis'],
                $id
            ]);
            
            $message = "Le service a ete mis a jour avec succes";
        } 
        // cas de creation
        else {
            $sql = "INSERT INTO prestations (nom, description, prix, duree, type, 
                   categorie, niveau_difficulte, capacite_max, materiel_necessaire, prerequis) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nom'], 
                $data['description'], 
                $data['prix'],
                $data['duree'],
                $data['type'],
                $data['categorie'],
                $data['niveau_difficulte'],
                $data['capacite_max'],
                $data['materiel_necessaire'],
                $data['prerequis']
            ]);
            
            $message = "Le service a ete cree avec succes";
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
    } catch (PDOException $e) {
        $errors[] = "Erreur de base de données : " . $e->getMessage();
        
        // log l'erreur pour l'administrateur
        error_log("Erreur DB dans services/index.php : " . $e->getMessage());
        
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
}

/**
 * Tente de supprimer un service identifié par son ID.
 *
 * La suppression est effectuée uniquement si le service n'a aucun rendez-vous ni évaluation associée.
 * En présence d'une ou plusieurs liaisons existantes, l'opération est annulée et un message d'erreur adapté est retourné.
 *
 * @param int $id Identifiant unique du service à supprimer.
 * @return array Tableau associatif contenant un indicateur de succès et un message explicatif.
 */
function servicesDelete($id) {
    $pdo = getDbConnection();
    
    // verifie si le service a des rendez-vous associes
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM rendez_vous WHERE prestation_id = ?");
    $stmt->execute([$id]);
    $appointmentCount = $stmt->fetchColumn();
    
    // verifie si le service a des evaluations associees
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM evaluations WHERE prestation_id = ?");
    $stmt->execute([$id]);
    $evaluationCount = $stmt->fetchColumn();
    
    if ($appointmentCount > 0) {
        return [
            'success' => false,
            'message' => "Impossible de supprimer ce service car il a des rendez-vous associes"
        ];
    } else if ($evaluationCount > 0) {
        return [
            'success' => false,
            'message' => "Impossible de supprimer ce service car il a des evaluations associees"
        ];
    } else {
        $stmt = $pdo->prepare("DELETE FROM prestations WHERE id = ?");
        $stmt->execute([$id]);
        return [
            'success' => true,
            'message' => "Le service a ete supprime avec succes"
        ];
    }
} 