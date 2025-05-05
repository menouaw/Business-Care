<?php
$autoload_path = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
} else {
    http_response_code(500);
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
        }
    } else {
    }
} else {
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
    exit('Erreur critique de configuration.');
}
Stripe::setApiKey($secret_key_direct);



$endpoint_secret = $_SERVER['STRIPE_WEBHOOK_SECRET'] ?? null;


if (empty($endpoint_secret)) {
    http_response_code(500);
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
    http_response_code(400);
    exit();
} catch (SignatureVerificationException $e) {
    http_response_code(400);
    exit();
}




switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        $processing_success = false;



        if (isset($session->metadata->donation_user_id)) {


            $processing_success = true;
        } elseif (isset($session->metadata->invoice_id)) {

            $processing_success = handleCheckoutSessionCompleted($session);
        } else {
            $processing_success = true;
        }


        if (!$processing_success) {
        }
        break;


    default:

        $processing_success = true;
}
