<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers.php';

requireRole(ROLE_PRESTATAIRE);
$provider_id = $_SESSION['user_id'];


$invoices = getProviderInvoices($provider_id);

$pageTitle = "Mes Factures - Espace Prestataire";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="provider-invoices-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2"><i class="fas fa-file-invoice-dollar me-2"></i><?= $pageTitle ?></h1>
                <p class="text-muted">Consultez l'historique de vos factures mensuelles générées.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/providers/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Dashboard
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Historique des Factures</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($invoices)) : ?>
                    <div class="alert alert-info mb-0 border-0 rounded-0" role="alert">
                        Aucune facture n'a été générée pour le moment. Les factures sont générées mensuellement sur la base des prestations terminées.
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Période</th>
                                    <th>N° Facture (ID)</th>
                                    <th>Nb. Prestations</th>
                                    <th>Première Prestation</th>
                                    <th>Dernière Prestation</th>
                                    <th class="text-end">Montant Total</th>
                                    <th class="text-center">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $invoice) : ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($invoice['periode'] ?? 'N/A') ?></strong></td>
                                        <td><?= htmlspecialchars($invoice['facture_id'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($invoice['nombre_prestations'] ?? '0') ?></td>
                                        <td><?= htmlspecialchars($invoice['premiere_prestation_formatee'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($invoice['derniere_prestation_formatee'] ?? 'N/A') ?></td>
                                        <td class="text-end"><?= $invoice['montant_total_formate'] ?></td>
                                        <td class="text-center"><?= $invoice['statut_badge'] ?></td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($invoices)) : ?>
                <div class="card-footer bg-light border-top-0">
                    <!-- Pagination HTML will go here if implemented -->
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>