<?php
// API pour la gestion des rendez-vous côté client

// vérification de l'authentification
if (!$isAuthenticated) {
    http_response_code(401);
    echo json_encode([
        'error' => true,
        'message' => 'Authentification requise'
    ]);
    exit;
}

// récupérer l'id du client (normalement extrait du jeton)
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;

if (!$clientId) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => 'ID client manquant'
    ]);
    exit;
}

// vérifier que l'utilisateur est bien un client
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

// déterminer l'action à effectuer en fonction de la méthode HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($id)) {
            getAppointmentDetails($clientId, $id);
        } else {
            getClientAppointments($clientId);
        }
        break;
        
    case 'POST':
        // créer un nouveau rendez-vous
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
        // Mettre à jour un rendez-vous
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
        // annuler un rendez-vous
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

/**
 * Récupère tous les rendez-vous d'un client
 * 
 * Cette fonction retourne la liste des rendez-vous avec possibilité
 * de filtrage par statut, date, etc.
 * 
 * @param int $clientId L'ID du client dont on récupère les rendez-vous
 * @return void Envoie une réponse JSON avec les rendez-vous
 */
function getClientAppointments($clientId) {
    $pdo = getDbConnection();
    
    try {
        // Construire la requête avec filtres possibles
        $query = "SELECT r.*, p.nom as nom_prestation, p.prix, p.type
                  FROM rendez_vous r
                  JOIN prestations p ON r.prestation_id = p.id
                  WHERE r.client_id = ?";
        $params = [$clientId];
        
        // Filtrage par statut
        if (isset($_GET['statut']) && !empty($_GET['statut'])) {
            $query .= " AND r.statut = ?";
            $params[] = $_GET['statut'];
        }
        
        // Filtrage par date (à partir de)
        if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
            $query .= " AND r.date_rdv >= ?";
            $params[] = $_GET['date_debut'];
        }
        
        // Filtrage par date (jusqu'à)
        if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
            $query .= " AND r.date_rdv <= ?";
            $params[] = $_GET['date_fin'];
        }
        
        // Filtrage par service
        if (isset($_GET['prestation_id']) && is_numeric($_GET['prestation_id'])) {
            $query .= " AND r.prestation_id = ?";
            $params[] = $_GET['prestation_id'];
        }
        
        // Tri par date (par défaut les plus récents d'abord)
        $query .= " ORDER BY r.date_rdv DESC, r.heure_debut DESC";
        
        // Exécuter la requête
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Réponse
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

/**
 * Récupère les détails d'un rendez-vous spécifique
 * 
 * Cette fonction vérifie d'abord que le rendez-vous appartient bien au client,
 * puis renvoie les détails complets du rendez-vous.
 * 
 * @param int $clientId L'ID du client demandant le rendez-vous
 * @param int $appointmentId L'ID du rendez-vous à récupérer
 * @return void Envoie une réponse JSON avec les détails du rendez-vous
 */
function getAppointmentDetails($clientId, $appointmentId) {
    $pdo = getDbConnection();
    
    try {
        // Récupérer le rendez-vous avec vérification qu'il appartient au client
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
        
        // récupérer les informations sur le prestataire si assigné
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
        
        // récupérer les éventuels documents associés
        $stmt = $pdo->prepare("
            SELECT id, nom_fichier, type_fichier, taille, date_creation
            FROM documents
            WHERE rendez_vous_id = ?
        ");
        $stmt->execute([$appointmentId]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // récupérer les éventuelles communications
        $stmt = $pdo->prepare("
            SELECT id, expediteur_id, destinataire_id, sujet, contenu, date_envoi, lu
            FROM communications
            WHERE rendez_vous_id = ? AND (expediteur_id = ? OR destinataire_id = ?)
            ORDER BY date_envoi DESC
        ");
        $stmt->execute([$appointmentId, $clientId, $clientId]);
        $communications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // préparer la réponse complète
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

/**
 * Crée un nouveau rendez-vous
 * 
 * Cette fonction permet à un client de demander un nouveau rendez-vous
 * pour une prestation spécifique.
 * 
 * @param int $clientId L'ID du client qui crée le rendez-vous
 * @return void Envoie une réponse JSON avec le statut de la création
 */
function createAppointment($clientId) {
    $pdo = getDbConnection();
    
    try {
        // récupérer les données de la demande
        $data = json_decode(file_get_contents('php://input'), true);
        
        // valider les données obligatoires
        if (!isset($data['prestation_id']) || !isset($data['date_demande'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Données incomplètes: prestation_id et date_demande sont requis'
            ]);
            return;
        }
        
        // vérifier que le service existe
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
        
        // créer le rendez-vous
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
            'demande', // statut initial: demande
        ]);
        
        $appointmentId = $pdo->lastInsertId();
        
        // envoi d'une notification (à implémenter selon le système de notifications)
        // ...
        
        // réponse de succès
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

/**
 * Met à jour un rendez-vous existant
 * 
 * Cette fonction permet au client de modifier un rendez-vous qu'il a créé,
 * à condition que celui-ci soit encore en statut "demande" ou "planifié".
 * 
 * @param int $clientId L'ID du client qui modifie le rendez-vous
 * @param int $appointmentId L'ID du rendez-vous à modifier
 * @return void Envoie une réponse JSON avec le statut de la mise à jour
 */
function updateAppointment($clientId, $appointmentId) {
    $pdo = getDbConnection();
    
    try {
        // vérifier que le rendez-vous appartient au client et peut être modifié
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
        
        // récupérer les données de la mise à jour
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Aucune donnée fournie pour la mise à jour'
            ]);
            return;
        }
        
        // champs autorisés à être modifiés par le client
        $allowedFields = [
            'date_rdv', 'heure_debut', 'heure_fin', 'nb_personnes', 'note_client'
        ];
        
        // construire la requête de mise à jour
        $updateFields = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        // s'il n'y a rien à mettre à jour
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Aucun champ valide à mettre à jour'
            ]);
            return;
        }
        
        // ajouter l'id du rendez-vous pour la clause WHERE
        $params[] = $appointmentId;
        $params[] = $clientId;
        
        // exécuter la mise à jour
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

/**
 * Annule un rendez-vous
 * 
 * Cette fonction permet au client d'annuler un rendez-vous qu'il a créé,
 * à condition que celui-ci ne soit pas déjà terminé ou annulé.
 * 
 * @param int $clientId L'ID du client qui annule le rendez-vous
 * @param int $appointmentId L'ID du rendez-vous à annuler
 * @return void Envoie une réponse JSON avec le statut de l'annulation
 */
function cancelAppointment($clientId, $appointmentId) {
    $pdo = getDbConnection();
    
    try {
        // vérifier que le rendez-vous appartient au client et peut être annulé
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
        
        // récupérer les données de l'annulation
        $data = json_decode(file_get_contents('php://input'), true);
        $motif = isset($data['motif_annulation']) ? $data['motif_annulation'] : 'Annulé par le client';
        
        // mettre à jour le rendez-vous
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
            
            // TODO: envoyer une notification
            // ...
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