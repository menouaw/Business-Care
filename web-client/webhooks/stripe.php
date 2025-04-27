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
        } catch (\Exception $e) {
            error_log("Webhook Stripe: Erreur Dotenv: " . $e->getMessage());
        }
    }
}

// Chemins corrects vers les fichiers partagés
require_once __DIR__ . '/../../shared/web-client/config.php';
require_once __DIR__ . '/../../shared/web-client/db.php';
require_once __DIR__ . '/../../shared/web-client/logging.php';
// Chemin correct vers le handler local
require_once __DIR__ . '/../../includes/page_functions/modules/companies/stripe_handlers.php';

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
    error_log("[ERROR] Webhook Stripe: Payload invalide. Signature Header: " . ($sig_header ?? 'N/A') . " - Erreur: " . $e->getMessage());
    http_response_code(400);
    exit();
} catch (SignatureVerificationException $e) {
    error_log("[ERROR] Webhook Stripe: Signature invalide. Signature Header: " . ($sig_header ?? 'N/A') . " - Erreur: " . $e->getMessage());
    http_response_code(400);
    exit();
}


error_log("[INFO] Webhook Stripe: Événement reçu - Type: " . $event->type . " - ID: " . $event->id);




switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;

        $processing_success = handleCheckoutSessionCompleted($session);

        if (!$processing_success) {

            error_log("[ERROR] Webhook: Le traitement de checkout.session.completed (Facture ID: " . ($session->metadata->invoice_id ?? 'N/A') . ") a rencontré un problème.");
        }
        break;






    default:
        error_log("[INFO] Webhook: Événement non géré reçu: " . $event->type);
}

http_response_code(200);
echo 'Événement reçu';
