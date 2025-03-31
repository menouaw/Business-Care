<?php
// API pour la gestion des services côté client

// déterminer l'action à effectuer en fonction de la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // récupération des services
        if (isset($id)) {
            // récupérer un service spécifique
            getServiceDetails($id);
        } else {
            // récupérer tous les services disponibles
            getAvailableServices();
        }
        break;
        
    case 'POST':
        // authentification requise pour les requêtes de réservation
        if (!$isAuthenticated) {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Authentification requise'
            ]);
            exit;
        }
        
        // demander un service (créer une réservation)
        requestService();
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'error' => true,
            'message' => 'Méthode non autorisée'
        ]);
        break;
}

/**
 * Récupère tous les services disponibles
 * 
 * Cette fonction renvoie la liste des services disponibles avec possibilité
 * de filtrage par catégorie, type, prix, etc.
 * 
 * @return void Envoie une réponse JSON avec les services disponibles
 */
function getAvailableServices() {
    $pdo = getDbConnection();
    
    try {
        // construire la requête avec filtres possibles
        $query = "SELECT * FROM prestations WHERE 1=1";
        $params = [];
        
        // filtrage par catégorie
        if (isset($_GET['categorie']) && !empty($_GET['categorie'])) {
            $query .= " AND categorie = ?";
            $params[] = $_GET['categorie'];
        }
        
        // filtrage par type
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $query .= " AND type = ?";
            $params[] = $_GET['type'];
        }
        
        // filtrage par prix maximum
        if (isset($_GET['prix_max']) && is_numeric($_GET['prix_max'])) {
            $query .= " AND prix <= ?";
            $params[] = $_GET['prix_max'];
        }
        
        // filtrage par prix minimum
        if (isset($_GET['prix_min']) && is_numeric($_GET['prix_min'])) {
            $query .= " AND prix >= ?";
            $params[] = $_GET['prix_min'];
        }
        
        // recherche textuelle
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $query .= " AND (nom LIKE ? OR description LIKE ?)";
            $searchTerm = "%" . $_GET['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // tri des résultats
        $sortField = 'nom';
        $sortDirection = 'ASC';
        
        if (isset($_GET['sort']) && in_array($_GET['sort'], ['nom', 'prix', 'type', 'categorie'])) {
            $sortField = $_GET['sort'];
        }
        
        if (isset($_GET['direction']) && in_array(strtoupper($_GET['direction']), ['ASC', 'DESC'])) {
            $sortDirection = strtoupper($_GET['direction']);
        }
        
        $query .= " ORDER BY $sortField $sortDirection";
        
        // pagination
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
        $offset = ($page - 1) * $limit;
        
        // ajouter la pagination à la requête
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // exécuter la requête
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // compter le nombre total de services pour la pagination
        $queryCount = "SELECT COUNT(id) FROM prestations WHERE 1=1";
        $paramsCount = [];
        
        if (isset($_GET['categorie']) && !empty($_GET['categorie'])) {
            $queryCount .= " AND categorie = ?";
            $paramsCount[] = $_GET['categorie'];
        }
        
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $queryCount .= " AND type = ?";
            $paramsCount[] = $_GET['type'];
        }
        
        if (isset($_GET['prix_max']) && is_numeric($_GET['prix_max'])) {
            $queryCount .= " AND prix <= ?";
            $paramsCount[] = $_GET['prix_max'];
        }
        
        if (isset($_GET['prix_min']) && is_numeric($_GET['prix_min'])) {
            $queryCount .= " AND prix >= ?";
            $paramsCount[] = $_GET['prix_min'];
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $queryCount .= " AND (nom LIKE ? OR description LIKE ?)";
            $searchTerm = "%" . $_GET['search'] . "%";
            $paramsCount[] = $searchTerm;
            $paramsCount[] = $searchTerm;
        }
        
        $stmtCount = $pdo->prepare($queryCount);
        $stmtCount->execute($paramsCount);
        $totalCount = $stmtCount->fetchColumn();
        
        // calculer le nombre total de pages
        $totalPages = ceil($totalCount / $limit);
        
        // préparer la réponse
        http_response_code(200);
        echo json_encode([
            'error' => false,
            'services' => $services,
            'pagination' => [
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'pages' => $totalPages
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Erreur lors de la récupération des services: ' . $e->getMessage()
        ]);
    }
}

/**
 * Récupère les détails d'un service spécifique
 * 
 * Cette fonction renvoie toutes les informations d'un service identifié par son ID,
 * ainsi que les avis et évaluations associés.
 * 
 * @param int $id L'ID du service à récupérer
 * @return void Envoie une réponse JSON avec les détails du service
 */
function getServiceDetails($id) {
    $pdo = getDbConnection();
    
    try {
        // récupérer les détails du service
        $stmt = $pdo->prepare("SELECT * FROM prestations WHERE id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$service) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Service non trouvé'
            ]);
            return;
        }
        
        // récupérer les évaluations et avis pour ce service
        $stmt = $pdo->prepare("
            SELECT e.*, p.nom, p.prenom 
            FROM evaluations e
            JOIN personnes p ON e.personne_id = p.id
            WHERE e.prestation_id = ?
            ORDER BY e.date_evaluation DESC
        ");
        $stmt->execute([$id]);
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // calculer la note moyenne
        $rating = 0;
        if (count($evaluations) > 0) {
            $sum = 0;
            foreach ($evaluations as $eval) {
                $sum += $eval['note'];
            }
            $rating = $sum / count($evaluations);
        }
        
        // préparer la réponse
        http_response_code(200);
        echo json_encode([
            'error' => false,
            'service' => $service,
            'evaluations' => $evaluations,
            'rating' => [
                'average' => $rating,
                'count' => count($evaluations)
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Erreur lors de la récupération du service: ' . $e->getMessage()
        ]);
    }
}

/**
 * Demande un service (crée une réservation)
 * 
 * Cette fonction permet à un client authentifié de demander un service,
 * ce qui se traduit par la création d'une réservation.
 * 
 * @return void Envoie une réponse JSON indiquant le succès ou l'échec
 */
function requestService() {
    $pdo = getDbConnection();
    
    try {
        // récupérer les données de la demande
        $data = json_decode(file_get_contents('php://input'), true);
        
        // valider les données obligatoires
        if (!isset($data['prestation_id']) || !isset($data['client_id']) || !isset($data['date_demande'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Données incomplètes: prestation_id, client_id et date_demande sont requis'
            ]);
            return;
        }
        
        // vérifier que le client existe et est bien un client
        $stmt = $pdo->prepare("SELECT id FROM personnes WHERE id = ? AND role_id = 3");
        $stmt->execute([$data['client_id']]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Client invalide'
            ]);
            return;
        }
        
        // vérifier que le service existe
        $stmt = $pdo->prepare("SELECT id, prix FROM prestations WHERE id = ?");
        $stmt->execute([$data['prestation_id']]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$service) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Service non trouvé'
            ]);
            return;
        }
        
        // créer la réservation
        $stmt = $pdo->prepare("
            INSERT INTO rendez_vous (
                prestation_id, 
                client_id, 
                date_demande, 
                date_rdv, 
                heure_debut, 
                heure_fin, 
                nb_personnes, 
                note_client, 
                statut, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['prestation_id'],
            $data['client_id'],
            $data['date_demande'],
            $data['date_rdv'] ?? null,
            $data['heure_debut'] ?? null,
            $data['heure_fin'] ?? null,
            $data['nb_personnes'] ?? 1,
            $data['note_client'] ?? null,
            'demande', // statut initial: demande
        ]);
        
        $reservationId = $pdo->lastInsertId();
        
        // réponse de succès
        http_response_code(201);
        echo json_encode([
            'error' => false,
            'message' => 'Demande de service créée avec succès',
            'reservation_id' => $reservationId
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Erreur lors de la création de la demande: ' . $e->getMessage()
        ]);
    }
} 