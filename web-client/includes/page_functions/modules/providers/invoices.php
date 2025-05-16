<?php

require_once __DIR__ . '/../shared/web-client/functions.php';


/* * Vérifie si l'utilisateur est authentifié et a le rôle de prestataire.
 *
 * @return array $user Les informations de l'utilisateur connecté.
 */
function getAuthenticatedPrestataire()
{
    requireAuthentication();
    $user = getUserInfo();

    if (!isPrestataireUser()) {
        die("Accès non autorisé.");
    }

    return $user;
}

/*
 * Récupère toutes les factures d'un prestataire.
 *
 * @param int $prestataireId L'ID du prestataire.
 * @return array Les factures du prestataire.
 */

function getPrestataireFactures($prestataireId)
{
    return fetchAll(
        'factures_prestataires',
        'prestataire_id = :prestataire_id',
        'date_facture DESC',
        0,
        0,
        ['prestataire_id' => $prestataireId]
    );
}

/*
 * Récupère les détails d'une facture spécifique pour un prestataire.
 *
 * @param int $factureId L'ID de la facture.
 * @param int $prestataireId L'ID du prestataire.
 * @return array Les détails de la facture et ses lignes.
 */

function getFactureDetails($factureId, $prestataireId)
{
    $facture = fetchAll(
        'factures_prestataires',
        'id = :id AND prestataire_id = :prestataire_id',
        '',
        1,
        0,
        ['id' => $factureId, 'prestataire_id' => $prestataireId]
    )[0] ?? null;

    if (!$facture) {
        die("Facture introuvable ou accès non autorisé.");
    }

    $lignes = fetchAll(
        'facture_prestataire_lignes',
        'facture_prestataire_id = :facture_id',
        '',
        0,
        0,
        ['facture_id' => $factureId]
    );

    return ['facture' => $facture, 'lignes' => $lignes];
}


$user = getAuthenticatedPrestataire();
$prestataireId = $user['id'];


$factureId = $_GET['id'] ?? null;

if ($factureId) {
    
    $details = getFactureDetails($factureId, $prestataireId);
    $facture = $details['facture'];
    $lignes = $details['lignes'];
} else {
    
    $factures = getPrestataireFactures($prestataireId);
}

/*
 * Génère les factures pour le mois précédent pour tous les prestataires.
 *
 * @return void
 */

 
function generateInvoicesForLastMonth()
{
    try {
        $pdo = getDbConnection();

        
        $startDate = date('Y-m-01', strtotime('first day of last month'));
        $endDate = date('Y-m-t', strtotime('last day of last month'));

        
        $prestataires = fetchAll(TABLE_USERS, "role_id = :role_id", '', 0, 0, ['role_id' => ROLE_PRESTATAIRE]);

        foreach ($prestataires as $prestataire) {
            $prestataireId = $prestataire['id'];

            
            $query = "
                SELECT rdv.id AS rendez_vous_id, p.nom AS prestation_nom, rdv.date_rdv, p.prix
                FROM " . TABLE_APPOINTMENTS . " rdv
                JOIN " . TABLE_PRESTATIONS . " p ON rdv.prestation_id = p.id
                WHERE rdv.praticien_id = :prestataire_id
                AND rdv.date_rdv BETWEEN :start_date AND :end_date
                AND rdv.statut = 'termine'
            ";
            $rendezVous = executeQuery($query, [
                'prestataire_id' => $prestataireId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ])->fetchAll(PDO::FETCH_ASSOC);

            if (count($rendezVous) > 0) {
                
                $montantTotal = array_sum(array_column($rendezVous, 'prix'));

                
                $numeroFacture = INVOICE_PREFIX . '-' . strtoupper(uniqid());

                
                $factureData = [
                    'prestataire_id' => $prestataireId,
                    'numero_facture' => $numeroFacture,
                    'date_facture' => date('Y-m-d'),
                    'periode_debut' => $startDate,
                    'periode_fin' => $endDate,
                    'montant_total' => $montantTotal,
                    'statut' => INVOICE_STATUS_PENDING
                ];
                $factureId = insertRow('factures_prestataires', $factureData);

                
                foreach ($rendezVous as $rdv) {
                    $ligneData = [
                        'facture_prestataire_id' => $factureId,
                        'rendez_vous_id' => $rdv['rendez_vous_id'],
                        'description' => $rdv['prestation_nom'] . ' - ' . formatDate($rdv['date_rdv']),
                        'montant' => $rdv['prix']
                    ];
                    insertRow('facture_prestataire_lignes', $ligneData);
                }

                echo "Facture générée pour le prestataire {$prestataire['nom']} {$prestataire['prenom']} (Facture n°: $numeroFacture)\n";
            } else {
                echo "Aucune prestation pour le prestataire {$prestataire['nom']} {$prestataire['prenom']} pour le mois précédent.\n";
            }
        }
    } catch (Exception $e) {
        echo "Erreur lors de la génération des factures : " . $e->getMessage();
    }
}

generateInvoicesForLastMonth();
