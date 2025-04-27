<?php
   
require_once __DIR__ . '/../../../shared/web-client/functions.php'; 

/**
 * Gère l'événement Stripe 'checkout.session.completed'.
 * Met à jour le statut de la facture, gère la transaction DB, et envoie une notification.
 *
 * @param object $session L'objet Session de Stripe.
 * @return bool True si le traitement a réussi (ou si l'événement a été ignoré car déjà traité), False en cas d'erreur nécessitant potentiellement une attention.
 */
function handleCheckoutSessionCompleted(object $session): bool
{
    
    if (!isset($session->metadata->invoice_id) || !isset($session->payment_status) || $session->payment_status !== 'paid') {
        error_log("[WARNING] Handler: checkout.session.completed sans invoice_id valide ou statut non 'paid'. Session ID: " . ($session->id ?? 'N/A'));
        
        
        return true;
    }

    $invoice_id = (int)$session->metadata->invoice_id;
    $payment_intent_id = $session->payment_intent;
    $db = null;

    try {
        $db = getDbConnection();
        $db->beginTransaction();

        
        $invoice = fetchOne('factures', 'id = :id', [':id' => $invoice_id], 'id, statut, entreprise_id');

        if ($invoice && $invoice['statut'] !== INVOICE_STATUS_PAID) {
            
            $updateData = [
                'statut' => INVOICE_STATUS_PAID,
                'date_paiement' => date('Y-m-d H:i:s'),
                'stripe_payment_intent_id' => $payment_intent_id
            ];
            $updated = updateRow('factures', $updateData, 'id = :id', [':id' => $invoice_id]);

            if ($updated > 0) {
                error_log("[SUCCESS] Handler: Facture ID: " . $invoice_id . " marquée comme PAYEE.");

                
                $company_user_id = findCompanyUserId($invoice['entreprise_id']);
                if ($company_user_id) {
                    $notifCreated = createNotification(
                        $company_user_id,
                        'Facture Payée',
                        "Votre paiement pour la facture #" . $invoice_id . " a été reçu avec succès.",
                        'success',
                        WEBCLIENT_URL . '/modules/companies/invoices.php?action=view&id=' . $invoice_id
                    );
                    if (!$notifCreated) {
                        error_log("[WARNING] Handler: Échec création notification pour paiement facture ID: " . $invoice_id);
                    }
                } else {
                    error_log("[WARNING] Handler: Utilisateur non trouvé pour entreprise ID: " . ($invoice['entreprise_id'] ?? 'N/A') . " (notification facture ID: " . $invoice_id . ")");
                }

                
                $db->commit();
                error_log("[INFO] Handler: Transaction commitée pour facture ID: " . $invoice_id);
                return true; 

            } else {
                
                error_log("[WARNING] Handler: Facture ID: " . $invoice_id . " trouvée mais non mise à jour. Rollback.");
                $db->rollBack();
                return false; 
            }
        } elseif ($invoice && $invoice['statut'] === INVOICE_STATUS_PAID) {
            
            error_log("[INFO] Handler: Facture ID: " . $invoice_id . " déjà PAYEE. Rollback (aucun changement).");
            $db->rollBack();
            return true; 

        } else {
            
            error_log("[WARNING] Handler: Facture ID: " . $invoice_id . " non trouvée. Rollback.");
            $db->rollBack();
            return false; 
        }
    } catch (PDOException $e) {
        error_log("[ERROR] Handler: Erreur DB pour facture ID: " . $invoice_id . " - " . $e->getMessage() . ". Rollback.");
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        return false; 

    } catch (Exception $e) {
        error_log("[ERROR] Handler: Erreur Générale pour facture ID: " . $invoice_id . " - " . $e->getMessage() . ". Rollback.");
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        return false; 
    }
}

/**
 * Trouve l'ID de l'utilisateur principal associé à une entreprise.
 * Adapté depuis le fichier stripe.php original.
 *
 * @param int|null $entreprise_id
 * @return int|null L'ID de l'utilisateur ou null si non trouvé.
 */
function findCompanyUserId(?int $entreprise_id): ?int
{
    if ($entreprise_id === null || $entreprise_id <= 0) {
        return null;
    }
    
    $user = fetchOne(
        'personnes',
        'entreprise_id = :entreprise_id AND role_id = :role_entreprise AND statut = :statut',
        [':entreprise_id' => $entreprise_id, ':role_entreprise' => ROLE_ENTREPRISE, ':statut' => 'actif'],
        'id'
    );
    return $user ? (int)$user['id'] : null;
}




