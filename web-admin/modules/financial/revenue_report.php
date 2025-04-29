<?php
require_once '../../includes/init.php';
require_once '../../includes/page_functions/modules/financial.php';


requireRole(ROLE_ADMIN);


$pageTitle = "Rapport Détaillé des Revenus";


$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');


$reportData = financialGetDetailedRevenueReport($startDate, $endDate);
$summary = financialGetTaxSummaryReport($startDate, $endDate);

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($pageTitle); ?></h1>
            </div>

            
            <form method="GET" action="" class="row g-3 mb-4 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Date de début</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Date de fin</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>

            
            <div class="card mb-4">
                <div class="card-header">Résumé pour la période</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Total HT :</strong> <?php echo formatMoney($summary['total_ht']); ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Total TVA :</strong> <?php echo formatMoney($summary['total_tva']); ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Total TTC :</strong> <?php echo formatMoney($summary['total_ttc']); ?>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th># Facture</th>
                            <th>Entreprise</th>
                            <th>Date Paiement</th>
                            <th class="text-end">Montant HT</th>
                            <th class="text-end">Montant TVA</th>
                            <th class="text-end">Montant TTC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportData)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Aucune facture payée trouvée pour la période sélectionnée.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reportData as $invoice): ?>
                                <tr>
                                    <td><a href="<?php echo WEBADMIN_URL; ?>/modules/billing/view.php?id=<?php echo htmlspecialchars($invoice['id']); ?>"><?php echo htmlspecialchars($invoice['numero_facture']); ?></a></td>
                                    <td><?php echo htmlspecialchars($invoice['nom_entreprise'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatDate($invoice['date_paiement'], 'd/m/Y H:i'); ?></td>
                                    <td class="text-end"><?php echo formatMoney($invoice['montant_ht']); ?></td>
                                    <td class="text-end"><?php echo formatMoney($invoice['montant_total'] - $invoice['montant_ht']); ?></td>
                                    <td class="text-end"><?php echo formatMoney($invoice['montant_total']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>

</rewritten_file>
