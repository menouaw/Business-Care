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
    $dotenv_path = '/var/www/html';
    $env_file_path = $dotenv_path . '/.env';



    if (file_exists($env_file_path)) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable($dotenv_path);
            $dotenv->load();
        } catch (\Exception $e) {
            error_log("Webhook Stripe: Erreur Dotenv: " . $e->getMessage());
        }
    } else {
        error_log("Webhook Stripe: Fichier .env NON TROUVÉ à: " . $env_file_path);
    }
} else {
    error_log("Webhook Stripe: Classe Dotenv non trouvée.");
}


require_once __DIR__ . '/../../shared/web-client/logging.php';
require_once __DIR__ . '/../includes/page_functions/modules/companies/stripe_handlers.php';
require_once __DIR__ . '/../includes/page_functions/modules/employees/donations.php';
require_once __DIR__ . '/../../shared/web-client/functions.php';

use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Exception\SignatureVerificationException;


$secret_key_direct = $_SERVER['STRIPE_SECRET_KEY'] ?? null;
if (!$secret_key_direct) {
    error_log("[FATAL] Webhook Stripe: Clé secrète STRIPE_SECRET_KEY non récupérée via SERVER.");
    exit('Erreur critique de configuration.');
}
Stripe::setApiKey($secret_key_direct);



$endpoint_secret = $_SERVER['STRIPE_WEBHOOK_SECRET'] ?? null;


if (empty($endpoint_secret)) {
    http_response_code(500);
    error_log("[FATAL] Webhook Stripe: Clé secrète STRIPE_WEBHOOK_SECRET non configurée ou vide après récupération via SERVER.");
    exit('Erreur de configuration Webhook.');
}


$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;


error_log("DEBUG - Webhook Stripe: Valeur de HTTP_STRIPE_SIGNATURE: " . ($sig_header ?? 'NULL'));

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
        $processing_success = false;


        // Check if it's a donation (metadata check)
        if (isset($session->metadata->donation_user_id)) {
            // >>> ERROR HERE: This function was commented out as per previous request <<<
            // $processing_success = handleDonationCheckoutCompleted($session);
            error_log("[WARNING] Webhook: checkout.session.completed pour un don reçu, mais la logique webhook est désactivée (utilisation de la méthode par session). Événement ignoré.");
            $processing_success = true; // Consider it "handled" by ignoring it, as intended by the session method.

            // Check if it's an invoice payment
        } elseif (isset($session->metadata->invoice_id)) {

            $processing_success = handleCheckoutSessionCompleted($session);
        } else {
            error_log("[WARNING] Webhook: checkout.session.completed reçu SANS métadonnées identifiables (don ou facture) - Session ID: " . $session->id);
            $processing_success = true;
        }


        if (!$processing_success) {
            error_log("[ERROR] Webhook: Le traitement de " . $event->type . " (Session ID: " . $session->id . ") a échoué ou rencontré un problème.");
        }
        break;


    default:

        $processing_success = true;
}


if ($processing_success) {
    http_response_code(200);
    echo 'Événement traité avec succès';
} else {





    http_response_code(200);
    echo 'Événement reçu mais non traité ou erreur interne';
}
