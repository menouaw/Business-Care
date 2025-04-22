<?php

if (!$isAuthenticated) {
    http_response_code(401);
    echo json_encode([
        'error' => true,
        'message' => 'Authentification requise'
    ]);
    exit;
}

$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;

if (!$clientId) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => 'ID client manquant'
    ]);
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($id)) {
            getClientContract($clientId, $id);
        } else {
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

function getClientContracts($clientId) {
    $pdo = getDbConnection();
    
    try {
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

function getClientContract($clientId, $contractId) {
    $pdo = getDbConnection();
    
    try {
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
        
        if (!$client['entreprise_id']) {
            http_response_code(403);
            echo json_encode([
                'error' => true,
                'message' => 'Client non associé à une entreprise'
            ]);
            return;
        }
        
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
        
        $stmt = $pdo->prepare("
            SELECT ps.*, p.nom, p.description, p.prix, p.type
            FROM prestations_services ps
            JOIN prestations p ON ps.prestation_id = p.id
            WHERE ps.contrat_id = ?
        ");
        $stmt->execute([$contractId]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT * FROM paiements
            WHERE contrat_id = ?
            ORDER BY date_paiement DESC
        ");
        $stmt->execute([$contractId]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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