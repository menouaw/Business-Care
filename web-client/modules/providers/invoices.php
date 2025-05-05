<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers/invoices.php';

requireRole(ROLE_PRESTATAIRE);

$provider_id = $_SESSION['user_id'] ?? 0;

handleProviderInvoiceDownloadRequest($provider_id);

$pageTitle = "Mes Factures";

$items_per_page = 15;
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($current_page - 1) * $items_per_page;

$invoices_data = [];
$total_invoices = 0;
if ($provider_id > 0) {
    $invoices_data = getProviderInvoices($provider_id, $items_per_page, $offset);
    $total_invoices = $invoices_data['total'] ?? 0;
}
$invoices = $invoices_data['invoices'] ?? [];
$total_pages = ceil($total_invoices / $items_per_page);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= WEBCLIENT_URL ?>/modules/providers/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                    </a>
                    
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="card mb-4">
                <div class="card-header">
                    Historique de vos factures (<?= $total_invoices ?> au total)
                </div>
                <div class="card-body">
                    <?php if (empty($invoices)): ?>
                        <p class="text-center text-muted">Aucune facture n'a été générée pour le moment.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Numéro</th>
                                        <th>Date Facture</th>
                                        <th>Période</th>
                                        <th class="text-end">Montant Total</th>
                                        <th>Statut</th>
                                        <th>Date Paiement</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $invoice): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($invoice['numero_facture'] ?? 'N/A') ?></td>
                                            <td><?= $invoice['date_facture'] ? htmlspecialchars(date('d/m/Y', strtotime($invoice['date_facture']))) : 'N/A' ?></td>
                                            <td>
                                                <?= $invoice['periode_debut'] ? htmlspecialchars(date('d/m/Y', strtotime($invoice['periode_debut']))) : 'N/A' ?>
                                                -
                                                <?= $invoice['periode_fin'] ? htmlspecialchars(date('d/m/Y', strtotime($invoice['periode_fin']))) : 'N/A' ?>
                                            </td>
                                            <td class="text-end"><?= number_format((float)($invoice['montant_total'] ?? 0), 2, ',', ' ') ?> €</td>
                                            <td>
                                                <span class="badge bg-<?= getInvoiceStatusBadgeClass($invoice['statut']) ?>">
                                                    <?= htmlspecialchars(formatInvoiceStatus($invoice['statut'])) ?>
                                                </span>
                                            </td>
                                            <td><?= $invoice['date_paiement'] ? htmlspecialchars(date('d/m/Y', strtotime($invoice['date_paiement']))) : '-' ?></td>
                                            <td>
                                                <a href="<?= WEBCLIENT_URL ?>/modules/providers/invoices.php?action=download&id=<?= $invoice['id'] ?>"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Télécharger la facture <?= htmlspecialchars($invoice['numero_facture'] ?? '') ?>">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation factures prestataires" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $current_page - 1 ?>">Précédent</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $current_page + 1 ?>">Suivant</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        

                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>