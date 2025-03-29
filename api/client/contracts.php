<?php
// API pour la gestion des contrats côté client

// vérification de l'authentification
if (!$isAuthenticated) {
    http_response_code(401);
    echo json_encode([
        'error' => true,
        'message' => 'Authentification requise'
    ]);
    exit;
}

// récupérer l'ID du client (normalement extrait du token)
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;

if (!$clientId) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => 'ID client manquant'
    ]);
    exit;
}

// déterminer l'action à effectuer en fonction de la méthode HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($id)) {
            // récupérer un contrat spécifique du client
            getClientContract($clientId, $id);
        } else {
            // récupérer tous les contrats du client
            getClientContracts($clientId);
        }
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
 * Récupère tous les contrats associés à un client
 * 
 * Cette fonction renvoie la liste des contrats liés soit directement au client,
 * soit à l'entreprise du client.
 * 
 * @param int $clientId L'ID du client dont on veut récupérer les contrats
 * @return void Envoie une réponse JSON avec les contrats ou un message d'erreur
 */
function getClientContracts($clientId) {
    $pdo = getDbConnection();
    
    try {
        // d'abord, récupérer les informations du client pour obtenir son entreprise_id
        $stmt = $pdo->prepare("SELECT entreprise_id FROM personnes WHERE id = ? AND role_id = 2");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Client non trouvé'
            ]);
            return;
        }
        
        // récupérer les contrats de l'entreprise du client
        if ($client['entreprise_id']) {
            $stmt = $pdo->prepare("
                SELECT c.*, e.nom as nom_entreprise 
                FROM contrats c
                JOIN entreprises e ON c.entreprise_id = e.id
                WHERE c.entreprise_id = ?
                ORDER BY c.date_debut DESC
            ");
            $stmt->execute([$client['entreprise_id']]);
            $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ajouter des informations sur les services inclus pour chaque contrat
            foreach ($contracts as &$contract) {
                $stmt = $pdo->prepare("
                    SELECT ps.*, p.nom, p.description
                    FROM prestations_services ps
                    JOIN prestations p ON ps.prestation_id = p.id
                    WHERE ps.contrat_id = ?
                ");
                $stmt->execute([$contract['id']]);
                $contract['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            http_response_code(200);
            echo json_encode([
                'error' => false,
                'contracts' => $contracts
            ]);
        } else {
            // si le client n'est pas associé à une entreprise, renvoyer un tableau vide
            http_response_code(200);
            echo json_encode([
                'error' => false,
                'message' => 'Aucun contrat trouvé - client non associé à une entreprise',
                'contracts' => []
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Erreur lors de la récupération des contrats: ' . $e->getMessage()
        ]);
    }
}

/**
 * Récupère les détails d'un contrat spécifique
 * 
 * Cette fonction vérifie d'abord que le contrat appartient bien à l'entreprise du client,
 * puis renvoie les détails complets du contrat ainsi que les services associés.
 * 
 * @param int $clientId L'ID du client demandant le contrat
 * @param int $contractId L'ID du contrat à récupérer
 * @return void Envoie une réponse JSON avec les détails du contrat ou un message d'erreur
 */
function getClientContract($clientId, $contractId) {
    $pdo = getDbConnection();
    
    try {
        // vérifier que le client existe et récupérer son entreprise_id
        $stmt = $pdo->prepare("SELECT entreprise_id FROM personnes WHERE id = ? AND role_id = 2");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Client non trouvé'
            ]);
            return;
        }
        
        // vérifier que le client a une entreprise associée
        if (!$client['entreprise_id']) {
            http_response_code(403);
            echo json_encode([
                'error' => true,
                'message' => 'Client non associé à une entreprise'
            ]);
            return;
        }
        
        // récupérer le contrat en vérifiant qu'il appartient à l'entreprise du client
        $stmt = $pdo->prepare("
            SELECT c.*, e.nom as nom_entreprise, e.adresse, e.code_postal, e.ville
            FROM contrats c
            JOIN entreprises e ON c.entreprise_id = e.id
            WHERE c.id = ? AND c.entreprise_id = ?
        ");
        $stmt->execute([$contractId, $client['entreprise_id']]);
        $contract = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$contract) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Contrat non trouvé ou non autorisé'
            ]);
            return;
        }
        
        // récupérer les services inclus dans le contrat
        $stmt = $pdo->prepare("
            SELECT ps.*, p.nom, p.description, p.prix, p.type
            FROM prestations_services ps
            JOIN prestations p ON ps.prestation_id = p.id
            WHERE ps.contrat_id = ?
        ");
        $stmt->execute([$contractId]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // récupérer l'historique des paiements
        $stmt = $pdo->prepare("
            SELECT * FROM paiements
            WHERE contrat_id = ?
            ORDER BY date_paiement DESC
        ");
        $stmt->execute([$contractId]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // préparer la réponse complète
        $response = [
            'error' => false,
            'contract' => $contract,
            'services' => $services,
            'payments' => $payments
        ];
        
        http_response_code(200);
        echo json_encode($response);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Erreur lors de la récupération du contrat: ' . $e->getMessage()
        ]);
    }
} 