<?php

use Stripe\Checkout\Session as StripeCheckoutSession;

/**
 * Gère l'événement Stripe 'checkout.session.completed'.
 * Met à jour le statut de la facture associée dans la base de données.
 *
 * @param StripeCheckoutSession $session L'objet Session de Stripe.
 * @return bool Retourne true si le traitement réussit, false sinon.
 */
function handleCheckoutSessionCompleted(StripeCheckoutSession $session): bool
{

    error_log("[INFO] Stripe Handler: Traitement de checkout.session.completed pour Session ID: " . $session->id);

    
    $invoice_id = $session->metadata->invoice_id ?? null;
    $payment_intent_id = $session->payment_intent; 

    if (!$invoice_id) {
        error_log("[ERROR] Stripe Handler: invoice_id manquant dans les métadonnées pour Session ID: " . $session->id);
        
        return false; 
    }

    error_log("[INFO] Stripe Handler: Tentative de mise à jour pour Facture ID: " . $invoice_id);

    try {
        
        $invoice = fetchOne('factures', 'id = :id', [':id' => $invoice_id]);

        if (!$invoice) {
            error_log("[ERROR] Stripe Handler: Facture ID " . $invoice_id . " non trouvée pour Session ID: " . $session->id);
            return false; 
        }

        if ($invoice['statut'] === 'payée') {
            error_log("[INFO] Stripe Handler: Facture ID " . $invoice_id . " déjà marquée comme payée (Session ID: " . $session->id . "). Aucune action nécessaire.");
            return true; 
        }

        
        $updateData = [
            'statut' => 'payée',
            'date_paiement' => date('Y-m-d H:i:s'), 
            'stripe_payment_intent_id' => $payment_intent_id 
        ];
        $updated = updateRow('factures', $updateData, 'id = :id', [':id' => $invoice_id]);

        if ($updated) {
            error_log("[SUCCESS] Stripe Handler: Facture ID " . $invoice_id . " marquée comme payée avec succès (Session ID: " . $session->id . ").");

            
            
            if (!empty($invoice['devis_id'])) {
                updateRow('devis', ['statut' => DEVIS_STATUT_VALIDE], 'id = :id', [':id' => $invoice['devis_id']]);
                error_log("[INFO] Stripe Handler: Statut du devis ID " . $invoice['devis_id'] . " mis à jour.");
            }

            
            $user_id = $invoice['personne_id'] ?? null; 
            if ($user_id && function_exists('createNotification')) {
                $client_link = WEBCLIENT_URL . '/modules/companies/invoices.php?action=view&id=' . $invoice_id;
                createNotification(
                    $user_id,
                    'Paiement confirmé',
                    'Votre paiement pour la facture #' . $invoice_id . ' a été confirmé.',
                    'success',
                    $client_link
                );
                error_log("[INFO] Stripe Handler: Notification de paiement envoyée à l'utilisateur ID " . $user_id . ".");
            }
            

            return true; 
        } else {
            error_log("[ERROR] Stripe Handler: Échec de la mise à jour de la base de données pour Facture ID: " . $invoice_id . " (Session ID: " . $session->id . ").");
            return false; 
        }
    } catch (\PDOException $e) {
        error_log("[CRITICAL] Stripe Handler: Erreur PDO lors de la mise à jour de la facture ID " . $invoice_id . " (Session ID: " . $session->id . ") - Erreur: " . $e->getMessage());
        
        
        return false; 
    } catch (\Exception $e) {
        error_log("[CRITICAL] Stripe Handler: Erreur générale lors du traitement de la facture ID " . $invoice_id . " (Session ID: " . $session->id . ") - Erreur: " . $e->getMessage());
        
        return false; 
    }
}
