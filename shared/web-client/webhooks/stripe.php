<?php
$autoload_path = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
} else {
    http_response_code(500);
    error_log("Webhook Stripe: Autoloader non trouvé.");
    exit('Erreur de configuration serveur.');
}

if (class_exists('Dotenv\Dotenv')) {
    $dotenv_path = __DIR__ . '/../..';
    if (file_exists($dotenv_path . '.env')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable($dotenv_path);
            $dotenv->load();

            // DEBUG: Vérifier la valeur de la clé webhook dans $_ENV ici
            $debug_webhook_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '#<NOT_SET_IN_ENV>#';
            error_log("DEBUG (webhook script): \$_ENV['STRIPE_WEBHOOK_SECRET'] value after load: " . $debug_webhook_secret);
        } catch (\Exception $e) {
            error_log("Webhook Stripe: Erreur Dotenv: " . $e->getMessage());
        }
    }
}


require_once __DIR__ . '/../../shared/web-client/config.php';
require_once __DIR__ . '/../../shared/web-client/db.php';
require_once __DIR__ . '/../../shared/web-client/logging.php';

error_log("--- DEBUG: Webhook script stripe.php ENTERED ---");

use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Exception\SignatureVerificationException;

Stripe::setApiKey(STRIPE_SECRET_KEY);


$endpoint_secret = STRIPE_WEBHOOK_SECRET;
if (empty($endpoint_secret)) {
    http_response_code(500);
    error_log("[FATAL] Webhook Stripe: Clé secrète STRIPE_WEBHOOK_SECRET non configurée.");
    exit('Erreur de configuration Webhook.');
}


$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
$event = null;


try {
    $event = Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );
} catch (\UnexpectedValueException $e) {

    error_log("[ERROR] Webhook Stripe: Payload invalide. Signature Header: " . $sig_header . " - Erreur: " . $e->getMessage());
    http_response_code(400);
    exit();
} catch (SignatureVerificationException $e) {

    error_log("[ERROR] Webhook Stripe: Signature invalide. Signature Header: " . $sig_header . " - Erreur: " . $e->getMessage());
    http_response_code(400);
    exit();
}


error_log("[INFO] Webhook Stripe: Événement reçu - Type: " . $event->type . " - Payload subset: " . substr(json_encode($event->data->object), 0, 500));


switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;


        if (isset($session->metadata->invoice_id) && isset($session->payment_status) && $session->payment_status == 'paid') {
            $invoice_id = (int)$session->metadata->invoice_id;
            $company_id = isset($session->metadata->company_id) ? (int)$session->metadata->company_id : null;
            $payment_intent_id = $session->payment_intent;

            error_log("[INFO] Webhook: checkout.session.completed reçu pour facture ID: " . $invoice_id);


            try {

                $invoice = fetchOne('factures', 'id = :id', [':id' => $invoice_id]);

                if ($invoice && $invoice['statut'] !== INVOICE_STATUS_PAID) {
                    $updateData = [
                        'statut' => INVOICE_STATUS_PAID,
                        'date_paiement' => date('Y-m-d H:i:s'),


                    ];
                    $updated = updateRow('factures', $updateData, 'id = :id', [':id' => $invoice_id]);

                    if ($updated > 0) {
                        error_log("[SUCCESS] Webhook: Facture ID: " . $invoice_id . " marquée comme PAYEE.");
                    } else {
                        error_log("[WARNING] Webhook: Facture ID: " . $invoice_id . " trouvée mais non mise à jour (peut-être déjà payée ou erreur DB).");
                    }
                } elseif ($invoice && $invoice['statut'] === INVOICE_STATUS_PAID) {
                    error_log("[INFO] Webhook: Facture ID: " . $invoice_id . " est déjà marquée comme PAYEE. Événement ignoré.");
                } else {
                    error_log("[WARNING] Webhook: Facture ID: " . $invoice_id . " non trouvée dans la base de données pour l'événement checkout.session.completed.");
                }
            } catch (PDOException $e) {
                error_log("[ERROR] Webhook: Erreur DB lors de la mise à jour de la facture ID: " . $invoice_id . " - " . $e->getMessage());

                http_response_code(500);
                exit();
            }
        } else {
            error_log("[WARNING] Webhook: checkout.session.completed reçu sans invoice_id valide dans metadata ou payment_status != 'paid'. Session ID: " . ($session->id ?? 'N/A'));
        }
        break;



    default:

        error_log("[INFO] Webhook: Événement non géré reçu: " . $event->type);
}

error_log("--- DEBUG: Webhook script stripe.php FINISHED PROCESSING, sending 200 --- ");


http_response_code(200);
echo 'Événement reçu';
