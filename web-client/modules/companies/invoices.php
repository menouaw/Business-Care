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

handleStripeReturn();

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'list';
$invoice_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$pageTitle = "Gestion des Factures";
$invoice = null;
$invoices = [];

switch ($action) {
    case 'create-checkout-session':
        if ($invoice_id) {
            handleInvoiceCheckoutSession($invoice_id, $entreprise_id);
        } else {
            flashMessage("ID de facture manquant pour le paiement.", "warning");
            redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
            exit;
        }

    case 'download':
        if ($invoice_id) {
            handleInvoiceDownload($invoice_id, $entreprise_id);
        } else {
            flashMessage("ID de facture manquant pour le téléchargement.", "warning");
            redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
            exit;
        }

    case 'view':
        $invoice = null;
        if ($invoice_id) {
            $invoice = getViewInvoiceData($invoice_id, $entreprise_id);
            if ($invoice === null) {
                redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
                exit;
            }
            $pageTitle = "Détails de la Facture #" . htmlspecialchars($invoice['numero_facture'] ?? $invoice['id']);
        } else {
            flashMessage("ID de facture manquant pour la visualisation.", "warning");
            redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
            exit;
        }
        break;

    case 'list':
    default:
        $action = 'list';
        $pageTitle = "Mes Factures";
        $invoices = getListInvoicesData($entreprise_id);
        break;
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

            <?php if ($action === 'view' && isset($invoice)): ?>
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
                            <a href="<?= WEBCLIENT_URL ?>/modules/companies/invoices.php?action=create-checkout-session&id=<?= $invoice['id'] ?>" class="btn btn-sm btn-primary" title="Payer avec Stripe">
                                <i class="fa-brands fa-stripe me-1"></i> Payer avec Stripe
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
                                                <a href="<?= WEBCLIENT_URL ?>/modules/companies/invoices.php?action=create-checkout-session&id=<?= $invoice_item['id'] ?>" class="btn btn-sm btn-primary" title="Payer avec Stripe">
                                                    <i class="fas fa-credit-card me-1"></i> Payer
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