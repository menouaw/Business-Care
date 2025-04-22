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

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT id FROM personnes WHERE id = ? AND role_id = 3");
$stmt->execute([$clientId]);

if ($stmt->rowCount() === 0) {
    http_response_code(403);
    echo json_encode([
        'error' => true,
        'message' => 'Accès non autorisé'
    ]);
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($id)) {
            getAppointmentDetails($clientId, $id);
        } else {
            getClientAppointments($clientId);
        }
        break;
        
    case 'POST':
        createAppointment($clientId);
        break;
        
    case 'PUT':
        if (!isset($id)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'ID de rendez-vous requis'
            ]);
            exit;
        }
        updateAppointment($clientId, $id);
        break;
        
    case 'DELETE':
        if (!isset($id)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'ID de rendez-vous requis'
            ]);
            exit;
        }
        cancelAppointment($clientId, $id);
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'error' => true,
            'message' => 'Méthode non autorisée'
        ]);
        break;
}

function getClientAppointments($clientId) {
    $pdo = getDbConnection();
    
    try {
        $query = "SELECT r.*, p.nom as nom_prestation, p.prix, p.type
                  FROM rendez_vous r
                  JOIN prestations p ON r.prestation_id = p.id
                  WHERE r.client_id = ?";
        $params = [$clientId];
        
        if (isset($_GET['statut']) && !empty($_GET['statut'])) {
            $query .= " AND r.statut = ?";
            $params[] = $_GET['statut'];
        }
        
        if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
            $query .= " AND r.date_rdv >= ?";
            $params[] = $_GET['date_debut'];
        }
        
        if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
            $query .= " AND r.date_rdv <= ?";
            $params[] = $_GET['date_fin'];
        }
        
        if (isset($_GET['prestation_id']) && is_numeric($_GET['prestation_id'])) {
            $query .= " AND r.prestation_id = ?";
            $params[] = $_GET['prestation_id'];
        }
        
        $query .= " ORDER BY r.date_rdv DESC, r.heure_debut DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'error' => false,
            'appointments' => $appointments
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Erreur lors de la récupération des rendez-vous: ' . $e->getMessage()
        ]);
    }
}

function getAppointmentDetails($clientId, $appointmentId) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, p.nom as nom_prestation, p.description, p.prix, p.duree, 
                  p.type, p.categorie, p.niveau_difficulte, p.materiel_necessaire
            FROM rendez_vous r
            JOIN prestations p ON r.prestation_id = p.id
            WHERE r.id = ? AND r.client_id = ?
        ");
        $stmt->execute([$appointmentId, $clientId]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appointment) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Rendez-vous non trouvé ou non autorisé'
            ]);
            return;
        }
        
        if (isset($appointment['prestataire_id']) && $appointment['prestataire_id']) {
            $stmt = $pdo->prepare("
                SELECT id, nom, prenom, email, telephone, photo_url
                FROM personnes
                WHERE id = ?
            ");
            $stmt->execute([$appointment['prestataire_id']]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);
            $appointment['prestataire'] = $provider;
        }
        
        $stmt = $pdo->prepare("
            SELECT id, nom_fichier, type_fichier, taille, date_creation
            FROM documents
            WHERE rendez_vous_id = ?
        ");
        $stmt->execute([$appointmentId]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT id, expediteur_id, destinataire_id, sujet, contenu, date_envoi, lu
            FROM communications
            WHERE rendez_vous_id = ? AND (expediteur_id = ? OR destinataire_id = ?)
            ORDER BY date_envoi DESC
        ");
        $stmt->execute([$appointmentId, $clientId, $clientId]);
        $communications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'error' => false,
            'appointment' => $appointment,
            'documents' => $documents,
            'communications' => $communications
        ];
        
        http_response_code(200);
        echo json_encode($response);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Erreur lors de la récupération du rendez-vous: ' . $e->getMessage()
        ]);
    }
}

function createAppointment($clientId) {
    $pdo = getDbConnection();
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['prestation_id']) || !isset($data['date_demande'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Données incomplètes: prestation_id et date_demande sont requis'
            ]);
            return;
        }
        
        $stmt = $pdo->prepare("SELECT id FROM prestations WHERE id = ?");
        $stmt->execute([$data['prestation_id']]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Service non trouvé'
            ]);
            return;
        }
        
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
            $clientId,
            $data['date_demande'],
            $data['date_rdv'] ?? null,
            $data['heure_debut'] ?? null,
            $data['heure_fin'] ?? null,
            $data['nb_personnes'] ?? 1,
            $data['note_client'] ?? null,
            'demande', 
        ]);
        
        $appointmentId = $pdo->lastInsertId();
        

        http_response_code(201);
        echo json_encode([
            'error' => false,
            'message' => 'Rendez-vous créé avec succès',
            'appointment_id' => $appointmentId
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Erreur lors de la création du rendez-vous: ' . $e->getMessage()
        ]);
    }
}

function updateAppointment($clientId, $appointmentId) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, statut 
            FROM rendez_vous 
            WHERE id = ? AND client_id = ? AND statut IN ('demande', 'planifié')
        ");
        $stmt->execute([$appointmentId, $clientId]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appointment) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Rendez-vous non trouvé, non autorisé ou ne peut plus être modifié'
            ]);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Aucune donnée fournie pour la mise à jour'
            ]);
            return;
        }
        
        $allowedFields = [
            'date_rdv', 'heure_debut', 'heure_fin', 'nb_personnes', 'note_client'
        ];
        
        $updateFields = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Aucun champ valide à mettre à jour'
            ]);
            return;
        }
        
        $params[] = $appointmentId;
        $params[] = $clientId;
        
        $query = "UPDATE rendez_vous SET " . implode(', ', $updateFields) . " WHERE id = ? AND client_id = ?";
        $stmt = $pdo->prepare($query);
        
        if ($stmt->execute($params)) {
            http_response_code(200);
            echo json_encode([
                'error' => false,
                'message' => 'Rendez-vous mis à jour avec succès'
            ]);
            

        } else {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Erreur lors de la mise à jour du rendez-vous'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Erreur lors de la mise à jour du rendez-vous: ' . $e->getMessage()
        ]);
    }
}

function cancelAppointment($clientId, $appointmentId) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, statut 
            FROM rendez_vous 
            WHERE id = ? AND client_id = ? AND statut NOT IN ('terminé', 'annulé')
        ");
        $stmt->execute([$appointmentId, $clientId]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appointment) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Rendez-vous non trouvé, non autorisé ou ne peut plus être annulé'
            ]);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $motif = isset($data['motif_annulation']) ? $data['motif_annulation'] : 'Annulé par le client';
        
        $stmt = $pdo->prepare("
            UPDATE rendez_vous 
            SET statut = 'annulé', 
                motif_annulation = ?,
                date_annulation = NOW() 
            WHERE id = ? AND client_id = ?
        ");
        
        if ($stmt->execute([$motif, $appointmentId, $clientId])) {
            http_response_code(200);
            echo json_encode([
                'error' => false,
                'message' => 'Rendez-vous annulé avec succès'
            ]);
            

        } else {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Erreur lors de l\'annulation du rendez-vous'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Erreur lors de l\'annulation du rendez-vous: ' . $e->getMessage()
        ]);
    }
} 