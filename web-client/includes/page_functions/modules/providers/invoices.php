<?php



/**
 * Vérifie si l'utilisateur est authentifié et a le rôle de prestataire.
 * Appelé par setupInvoicesPage.
 * @return array Les informations de l'utilisateur connecté.
 * @throws Exception Si l'utilisateur n'est pas un prestataire authentifié.
 */
function getAuthenticatedPrestataireForInvoices(): array
{
    
    
    if (!isset($_SESSION['user_id'])) {
        
        
        
        throw new Exception("Utilisateur non authentifié.");
    }
    
    $user = getUserInfo(); 

    if (!isPrestataireUser($user)) { 
        throw new Exception("Accès non autorisé. Rôle prestataire requis.");
    }
    return $user;
}

/**
 * Récupère toutes les factures d'un prestataire.
 *
 * @param int $prestataireId L'ID du prestataire.
 * @return array Les factures du prestataire.
 */
function getPrestataireFactures(int $prestataireId): array
{
    return fetchAll(
        'factures_prestataires',
        'prestataire_id = :prestataire_id',
        'date_facture DESC',
        0,
        0,
        [':prestataire_id' => $prestataireId]
    );
}

/**
 * Récupère les détails d'une facture spécifique pour un prestataire.
 *
 * @param int $factureId L'ID de la facture.
 * @param int $prestataireId L'ID du prestataire.
 * @return array|null Les détails de la facture et ses lignes, ou null si non trouvée.
 */
function getFactureDetails(int $factureId, int $prestataireId): ?array
{
    $facture = fetchOne(
        'factures_prestataires',
        'id = :id AND prestataire_id = :prestataire_id',
        ['id' => $factureId, 'prestataire_id' => $prestataireId]
    );

    if (!$facture) {
        return null; 
    }

    $lignes = fetchAll(
        'facture_prestataire_lignes',
        'facture_prestataire_id = :facture_id',
        '',
        0,
        0,
        [':facture_id' => $factureId]
    );

    return ['facture' => $facture, 'lignes' => $lignes];
}

/**
 * Fonction principale pour préparer les données de la page des factures.
 * @return array Données pour la vue.
 */
function setupInvoicesPage(): array
{
    
    $prestataire_id = $_SESSION['user_id'] ?? 0;

    if ($prestataire_id <= 0) {
        
        flashMessage("Session invalide ou utilisateur non trouvé.", "danger");
        redirectTo(WEBCLIENT_URL . '/auth/login.php');
        exit;
    }

    $pageTitle = "Mes Factures";
    $factures = [];
    $facture = null;
    $lignes = [];
    $factureId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($factureId) {
        $details = getFactureDetails($factureId, $prestataire_id);
        if ($details) {
            $facture = $details['facture'];
            $lignes = $details['lignes'];
            $pageTitle = "Détail Facture N° " . ($facture['numero_facture'] ?? $factureId);
        } else {
            flashMessage("Facture introuvable ou accès non autorisé.", "warning");
            
            
            
        }
    } else {
        $factures = getPrestataireFactures($prestataire_id);
    }

    return [
        'pageTitle' => $pageTitle,
        'factures' => $factures,
        'facture' => $facture,
        'lignes' => $lignes,
        'factureId' => $factureId
    ];
}


/**
 * Génère les factures pour le mois précédent pour tous les prestataires.
 * ATTENTION: CETTE FONCTION NE DEVRAIT PAS ÊTRE APPELÉE AUTOMATIQUEMENT LORS DE L'AFFICHAGE D'UNE PAGE.
 * Elle devrait être déclenchée par une tâche CRON ou une action administrative spécifique.
 * @return void
 */
function generateInvoicesForLastMonth(): void
{
    try {
        $pdo = getDbConnection();

        $startDate = date('Y-m-01', strtotime('first day of last month'));
        $endDate = date('Y-m-t', strtotime('last day of last month'));

        $prestataires = fetchAll(TABLE_USERS, "role_id = :role_id", '', 0, 0, ['role_id' => ROLE_PRESTATAIRE]);

        foreach ($prestataires as $prestataire) {
            $prestataireIdLoop = $prestataire['id']; 

            $query = "
                SELECT rdv.id AS rendez_vous_id, p.nom AS prestation_nom, rdv.date_rdv, p.prix
                FROM " . TABLE_APPOINTMENTS . " rdv
                JOIN " . TABLE_PRESTATIONS . " p ON rdv.prestation_id = p.id
                WHERE rdv.praticien_id = :prestataire_id
                AND rdv.date_rdv BETWEEN :start_date AND :end_date
                AND rdv.statut = 'termine'
            ";
            $rendezVous = executeQuery($query, [
                'prestataire_id' => $prestataireIdLoop,
                'start_date' => $startDate,
                'end_date' => $endDate
            ])->fetchAll(PDO::FETCH_ASSOC);

            if (count($rendezVous) > 0) {
                $montantTotal = array_sum(array_column($rendezVous, 'prix'));
                $numeroFacture = INVOICE_PREFIX . '-' . strtoupper(uniqid());

                $factureData = [
                    'prestataire_id' => $prestataireIdLoop,
                    'numero_facture' => $numeroFacture,
                    'date_facture' => date('Y-m-d'),
                    'periode_debut' => $startDate,
                    'periode_fin' => $endDate,
                    'montant_total' => $montantTotal,
                    'statut' => INVOICE_STATUS_PENDING
                ];
                $factureIdResult = insertRow('factures_prestataires', $factureData); 

                foreach ($rendezVous as $rdv) {
                    $ligneData = [
                        'facture_prestataire_id' => $factureIdResult,
                        'rendez_vous_id' => $rdv['rendez_vous_id'],
                        'description' => $rdv['prestation_nom'] . ' - ' . formatDate($rdv['date_rdv']),
                        'montant' => $rdv['prix']
                    ];
                    insertRow('facture_prestataire_lignes', $ligneData);
                }
                
                
                error_log("Facture générée pour le prestataire {$prestataire['nom']} {$prestataire['prenom']} (Facture n°: $numeroFacture)");
            } else {
                
                error_log("Aucune prestation pour le prestataire {$prestataire['nom']} {$prestataire['prenom']} pour le mois précédent.");
            }
        }
    } catch (Exception $e) {
        
        error_log("Erreur lors de la génération des factures : " . $e->getMessage());
    }
}




?>
