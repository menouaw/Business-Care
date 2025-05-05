<?php

use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Exception\ApiErrorException as StripeApiErrorException;

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies/invoices.php';

requireRole(ROLE_ENTREPRISE);

$entreprise_id = $_SESSION['user_entreprise'] ?? 0;

if ($entreprise_id <= 0) {
    flashMessage("Impossible d'identifier votre entreprise.", "danger");
    redirectTo(WEBCLIENT_URL . '/modules/companies/dashboard.php');
    exit;
}

$payment_status = filter_input(INPUT_GET, 'payment', FILTER_SANITIZE_SPECIAL_CHARS);
if ($payment_status === 'success') {
    flashMessage("Votre tentative de paiement a été initiée. Le statut de la facture sera mis à jour après confirmation.", "info");
    redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
    exit;
} elseif ($payment_status === 'cancelled') {
    flashMessage("Le processus de paiement a été annulé.", "warning");
    redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
    exit;
}

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
$invoice_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$pageTitle = "Gestion des Factures";
$invoice = null;
$invoices = [];

if ($action === 'create-checkout-session' && $invoice_id) {
    $invoice = getInvoiceDetails($invoice_id, $entreprise_id);
    $payable_statuses = [INVOICE_STATUS_PENDING, INVOICE_STATUS_LATE, INVOICE_STATUS_UNPAID];

    if (!$invoice || !in_array($invoice['statut'], $payable_statuses)) {
        flashMessage("Facture non trouvée, déjà payée, ou non payable.", "warning");
        redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
        exit;
    }

    if (!defined('STRIPE_SECRET_KEY') || empty(STRIPE_SECRET_KEY)) {
        flashMessage("La configuration pour le paiement en ligne est incomplète. Veuillez contacter le support.", "danger");
        error_log("[FATAL] Clé secrète Stripe non configurée dans les variables d'environnement ou config.php.");
        redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php?action=view&id=' . $invoice_id);
        exit;
    }

    Stripe::setApiKey(STRIPE_SECRET_KEY);

    try {
        $success_url = WEBCLIENT_URL . '/modules/companies/invoices.php?payment=success&session_id={CHECKOUT_SESSION_ID}';
        $cancel_url = WEBCLIENT_URL . '/modules/companies/invoices.php?payment=cancelled';

        $currency = strtolower(defined('DEFAULT_CURRENCY_CODE') ? DEFAULT_CURRENCY_CODE : 'eur');
        $amount_cents = (int) round(($invoice['montant_total'] ?? 0) * 100);

        if ($amount_cents <= 0) {
            throw new Exception("Le montant de la facture est invalide pour le paiement.");
        }

        $checkout_session = StripeCheckoutSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => 'Facture ' . ($invoice['numero_facture'] ?? $invoice['id']),
                        'description' => 'Paiement facture Business Care #' . ($invoice['numero_facture'] ?? $invoice['id']),
                    ],
                    'unit_amount' => $amount_cents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'metadata' => [
                'invoice_id' => $invoice['id'],
                'company_id' => $entreprise_id,
            ],
        ]);

        header("HTTP/1.1 303 See Other");
        header("Location: " . $checkout_session->url);
        exit;
    } catch (StripeApiErrorException $e) {
        error_log("Erreur API Stripe lors de la création de session Checkout pour facture ID: " . $invoice_id . " - " . $e->getMessage());
        flashMessage("Erreur lors de l'initialisation du paiement : " . $e->getMessage(), "danger");
    } catch (Exception $e) {
        error_log("Erreur lors de la création de session Checkout pour facture ID: " . $invoice_id . " - " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de la préparation du paiement.", "danger");
    }

    redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
    exit;
}

if ($action === 'download' && $invoice_id) {
    $invoice = getInvoiceDetails($invoice_id, $entreprise_id);
    if (!$invoice) {
        flashMessage("Facture non trouvée ou accès refusé pour la génération PDF.", "warning");
        redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
        exit;
    }

    $pdfGenerated = generateInvoicePdf($invoice);

    if ($pdfGenerated === false) {
        flashMessage("Une erreur est survenue lors de la génération du fichier PDF. Veuillez réessayer ou contacter le support.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
        exit;
    }
}

if ($action === 'view' && $invoice_id) {
    $invoice = getInvoiceDetails($invoice_id, $entreprise_id);
    if (!$invoice) {
        flashMessage("Facture non trouvée ou accès refusé.", "warning");
        redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
        exit;
    }
    $pageTitle = "Détails de la Facture #" . htmlspecialchars($invoice['numero_facture'] ?? $invoice['id']);
} else {
    $action = 'list';
    $pageTitle = "Mes Factures";
    $invoices = getCompanyInvoices($entreprise_id);
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">

            <?php echo displayFlashMessages(); ?>

            <?php
            ?>

            <?php if ($action === 'view' && $invoice): ?>
                <?php
                ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= WEBCLIENT_URL ?>/modules/companies/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de Bord
                        </a>
                        <a href="<?= WEBCLIENT_URL ?>/modules/companies/invoices.php?action=download&id=<?= $invoice['id'] ?>" class="btn btn-sm btn-outline-primary me-2" title="Télécharger le PDF">
                            <i class="fas fa-download me-1"></i> Télécharger PDF
                        </a>
                        <?php
                        ?>
                        <?php
                        $payable_statuses = [INVOICE_STATUS_PENDING, INVOICE_STATUS_LATE, INVOICE_STATUS_UNPAID];
                        if (in_array($invoice['statut'], $payable_statuses)) : ?>
                            <a href="<?= WEBCLIENT_URL ?>/modules/companies/invoices.php?action=create-checkout-session&id=<?= $invoice['id'] ?>" class="btn btn-sm btn-success" title="Payer cette facture">
                                <i class="fas fa-credit-card me-1"></i> Payer Maintenant
                            </a>
                        <?php endif; ?>
                        <?php
                        ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        Informations sur la facture
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">Numéro de Facture:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($invoice['numero_facture'] ?? $invoice['id']) ?></dd>

                            <dt class="col-sm-3">Date d'Émission:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(date('d/m/Y', strtotime($invoice['date_emission']))) ?></dd>

                            <dt class="col-sm-3">Date d'Échéance:</dt>
                            <dd class="col-sm-9"><?= $invoice['date_echeance'] ? htmlspecialchars(date('d/m/Y', strtotime($invoice['date_echeance']))) : 'N/A' ?></dd>

                            <dt class="col-sm-3">Montant HT:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(number_format($invoice['montant_ht'] ?? 0, 2, ',', ' ')) ?> <?= defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'EUR' ?></dd>

                            <dt class="col-sm-3">TVA (<?= htmlspecialchars($invoice['tva'] ?? '0') ?>%):</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(number_format(($invoice['montant_total'] ?? 0) - ($invoice['montant_ht'] ?? 0), 2, ',', ' ')) ?> <?= defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'EUR' ?></dd>

                            <dt class="col-sm-3">Montant Total TTC:</dt>
                            <dd class="col-sm-9 fw-bold"><?= htmlspecialchars(number_format($invoice['montant_total'] ?? 0, 2, ',', ' ')) ?> <?= defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'EUR' ?></dd>

                            <dt class="col-sm-3">Statut:</dt>
                            <dd class="col-sm-9">
                                <span class="badge bg-<?= getInvoiceStatusBadgeClass($invoice['statut']) ?>">
                                    <?= htmlspecialchars(ucfirst($invoice['statut'])) ?>
                                </span>
                            </dd>

                            <dt class="col-sm-3">Mode de Paiement:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(ucfirst($invoice['mode_paiement'] ?? 'N/D')) ?></dd>

                            <dt class="col-sm-3">Date de Paiement:</dt>
                            <dd class="col-sm-9"><?= $invoice['date_paiement'] ? htmlspecialchars(date(defined('DEFAULT_DATE_FORMAT') ? DEFAULT_DATE_FORMAT : 'd/m/Y H:i', strtotime($invoice['date_paiement']))) : '-' ?></dd>

                        </dl>

                    </div>
                </div>

            <?php elseif ($action === 'list'):
            ?>
                <?php
                ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= WEBCLIENT_URL ?>/modules/companies/dashboard.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de Bord
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Numéro</th>
                                <th>Date Émission</th>
                                <th>Date Échéance</th>
                                <th>Montant TTC</th>
                                <th>Statut</th>
                                <th>Date Paiement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="7">Aucune facture trouvée.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $invoice_item): ?>
                                    <?php
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($invoice_item['numero_facture'] ?? $invoice_item['id']) ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($invoice_item['date_emission']))) ?></td>
                                        <td><?= $invoice_item['date_echeance'] ? htmlspecialchars(date('d/m/Y', strtotime($invoice_item['date_echeance']))) : '' ?></td>
                                        <td class="text-end"><?= htmlspecialchars(number_format($invoice_item['montant_total'] ?? 0, 2, ',', ' ')) ?> <?= defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'EUR' ?></td>
                                        <td>
                                            <span class="badge bg-<?= getInvoiceStatusBadgeClass($invoice_item['statut']) ?>">
                                                <?= htmlspecialchars(ucfirst($invoice_item['statut'])) ?>
                                            </span>
                                        </td>
                                        <td><?= $invoice_item['date_paiement'] ? htmlspecialchars(date('d/m/Y', strtotime($invoice_item['date_paiement']))) : '-' ?></td>
                                        <td>
                                            <a href="<?= WEBCLIENT_URL ?>/modules/companies/invoices.php?action=view&id=<?= $invoice_item['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="Voir Détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= WEBCLIENT_URL ?>/modules/companies/invoices.php?action=download&id=<?= $invoice_item['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Télécharger le PDF">
                                                <i class="fas fa-download"></i><span class="visually-hidden">Télécharger PDF</span>
                                            </a>
                                            <?php
                                            $payable_statuses = [INVOICE_STATUS_PENDING, INVOICE_STATUS_LATE, INVOICE_STATUS_UNPAID];
                                            if (in_array($invoice_item['statut'], $payable_statuses)) : ?>
                                                <a href="<?= WEBCLIENT_URL ?>/modules/companies/invoices.php?action=create-checkout-session&id=<?= $invoice_item['id'] ?>" class="btn btn-sm btn-success" title="Payer cette facture">
                                                    <i class="fas fa-credit-card"></i><span class="visually-hidden">Payer facture</span>
                                                </a>
                                            <?php endif; ?>
                                            <?php
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>