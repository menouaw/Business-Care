<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers/invoices.php';

requireRole(ROLE_PRESTATAIRE);

$provider_id = $_SESSION['user_id'] ?? 0;


$pageData = setupProviderInvoicePageData($provider_id);


$pageTitle = $pageData['pageTitle'];
$invoice_details = $pageData['invoice_details']; 
$invoices = $pageData['invoices'];             
$total_invoices = $pageData['total_invoices'];
$current_page = $pageData['current_page'];
$total_pages = $pageData['total_pages'];

/**
 * Renders the appropriate view (list or details) for provider invoices.
 *
 * @param array|null $invoice_details Details of a single invoice (if in detail view), null otherwise.
 * @param array $invoices List of invoices (for list view).
 * @param int $total_invoices Total number of invoices (for list view header).
 * @param int $current_page Current page number (for list view pagination).
 * @param int $total_pages Total number of pages (for list view pagination).
 * @return void Echos the HTML for the view.
 */
function render_provider_invoices_view(?array $invoice_details, array $invoices, int $total_invoices, int $current_page, int $total_pages): void
{
    if ($invoice_details) {
        
?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Détails de la Facture : <?= htmlspecialchars($invoice_details['numero_facture'] ?? 'N/A') ?></span>
                <a href="invoices.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-list me-1"></i> Retour à la liste
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Numéro :</strong> <?= htmlspecialchars($invoice_details['numero_facture'] ?? 'N/A') ?></p>
                        <p><strong>Date Facture :</strong> <?= $invoice_details['date_facture'] ? htmlspecialchars(date('d/m/Y', strtotime($invoice_details['date_facture']))) : 'N/A' ?></p>
                        <p><strong>Période :</strong>
                            <?= $invoice_details['periode_debut'] ? htmlspecialchars(date('d/m/Y', strtotime($invoice_details['periode_debut']))) : 'N/A' ?>
                            -
                            <?= $invoice_details['periode_fin'] ? htmlspecialchars(date('d/m/Y', strtotime($invoice_details['periode_fin']))) : 'N/A' ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p><strong>Montant Total :</strong> <span class="fs-5 fw-bold"><?= htmlspecialchars(number_format((float)($invoice_details['montant_total'] ?? 0), 2, ',', ' ')) ?> €</span></p>
                        <p><strong>Statut :</strong>
                            <span class="badge bg-<?= getInvoiceStatusBadgeClass($invoice_details['statut']) ?>">
                                <?= htmlspecialchars(formatInvoiceStatus($invoice_details['statut'])) ?>
                            </span>
                        </p>
                        <?php if ($invoice_details['date_paiement']): ?>
                            <p><strong>Payée le :</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($invoice_details['date_paiement']))) ?></p>
                        <?php endif; ?>
                        <a href="<?= WEBCLIENT_URL ?>/modules/providers/invoices.php?action=download&id=<?= $invoice_details['id'] ?>"
                            class="btn btn-sm btn-secondary mt-2"
                            title="Télécharger la facture <?= htmlspecialchars($invoice_details['numero_facture'] ?? '') ?>">
                            <i class="fas fa-download me-1"></i> Télécharger PDF (Basique)
                        </a>
                    </div>
                </div>

                <hr>
                <h5 class="mb-3">Détail des prestations facturées :</h5>
                <?php if (empty($invoice_details['lines'])): ?>
                    <p class="text-muted">Aucun détail de ligne trouvé pour cette facture.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Date Prestation</th>
                                    <th>Prestation</th>
                                    <th>Salarié Concerné</th>
                                    <th>Description Ligne</th>
                                    <th class="text-end">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoice_details['lines'] as $line): ?>
                                    <tr>
                                        <td><?= $line['date_rdv'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($line['date_rdv']))) : 'N/A' ?></td>
                                        <td><?= htmlspecialchars($line['prestation_nom'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars(($line['salarie_prenom'] ?? '') . ' ' . ($line['salarie_nom'] ?? '')) ?: 'N/A' ?></td>
                                        <td><?= htmlspecialchars($line['line_description'] ?? 'N/A') ?></td>
                                        <td class="text-end"><?= htmlspecialchars(number_format((float)($line['line_amount'] ?? 0), 2, ',', ' ')) ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-bold">
                                    <td colspan="4" class="text-end">Total Calculé des Lignes :</td>
                                    <td class="text-end">
                                        <?php
                                        $lines_total = array_sum(array_column($invoice_details['lines'], 'line_amount'));
                                        echo htmlspecialchars(number_format($lines_total, 2, ',', ' ')) . ' €';
                                        ?>
                                    </td>
                                </tr>
                                <tr class="table-light fw-bold">
                                    <td colspan="4" class="text-end">Montant Total Facture :</td>
                                    <td class="text-end"><?= htmlspecialchars(number_format((float)($invoice_details['montant_total'] ?? 0), 2, ',', ' ')) ?> €</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php
    } else {
        
    ?>
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
                                            <a href="invoices.php?view_details=<?= $invoice['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Voir Détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= WEBCLIENT_URL ?>/modules/providers/invoices.php?action=download&id=<?= $invoice['id'] ?>"
                                                class="btn btn-sm btn-outline-secondary"
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
<?php
    }
}

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

            <?php
            
            render_provider_invoices_view(
                $invoice_details,
                $invoices ?? [],
                $total_invoices ?? 0,
                $current_page ?? 1,
                $total_pages ?? 1
            );
            ?>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>