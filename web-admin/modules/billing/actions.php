<?php
require_once '../../includes/init.php';
require_once '../../includes/page_functions/modules/billing.php';

// requireRole\(ROLE_ADMIN\)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo(WEBADMIN_URL . '/modules/billing/index.php');
}

$action = $_POST['action'] ?? null;
$csrfToken = $_POST['csrf_token'] ?? null;
$invoiceId = isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : 0;
$newStatus = $_POST['new_status'] ?? null;

if (!validateToken($csrfToken)) {
    handleCsrfFailureRedirect($invoiceId, 'billing', 'update status');
    exit;
}

if ($invoiceId <= 0) {
     flashMessage('ID de facture invalide.', 'danger');
     redirectTo(WEBADMIN_URL . '/modules/billing/index.php');
}

$result = null;
$redirectType = 'client';

switch ($action) {
    case 'update_client_status':
        $paymentMode = $_POST['payment_mode'] ?? null;
        $redirectType = 'client';
        if ($newStatus !== INVOICE_STATUS_PAID) {
             flashMessage('Action invalide pour facture client.', 'danger');
        } elseif (!$paymentMode) {
            flashMessage('Le mode de paiement est requis pour marquer la facture comme payée.', 'warning');
        } else {
            $result = billingUpdateClientInvoiceStatus($invoiceId, $newStatus, $paymentMode);
        }
        break;

    case 'update_provider_status':
        $redirectType = 'provider';
        if (!$newStatus) {
             flashMessage('Le nouveau statut est requis.', 'warning');
        } else {
             $result = billingUpdateProviderInvoiceStatus($invoiceId, $newStatus);
        }
       
        break;

    default:
        flashMessage('Action inconnue.', 'danger');
        break;
}

if ($result !== null) {
    if ($result['success']) {
        flashMessage($result['message'], 'success');
    } else {
        flashMessage($result['message'], 'danger');
    }
}

redirectTo(WEBADMIN_URL . "/modules/billing/view.php?id={$invoiceId}&type={$redirectType}");
?>
