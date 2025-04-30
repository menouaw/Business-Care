<?php
require_once '../../includes/page_functions/modules/financial.php';
require_once '../../includes/page_functions/modules/companies.php'; 
require_once '../../includes/page_functions/modules/services.php';  

requireRole(ROLE_ADMIN);

$pageTitle = "Revenus";


$queryData = getQueryData(); 

$startDate = $queryData['start_date'] ?? null;
$endDate = $queryData['end_date'] ?? null;
$companyId = isset($queryData['company_id']) && is_numeric($queryData['company_id']) ? (int)$queryData['company_id'] : null;
$serviceId = isset($queryData['service_id']) && is_numeric($queryData['service_id']) ? (int)$queryData['service_id'] : null;
$sortBy = $queryData['sort_by'] ?? 'f.date_paiement';
$sortOrder = $queryData['sort_order'] ?? 'DESC';
$action = $queryData['action'] ?? '';


$filters = [];
if ($companyId) {
    $filters['company_id'] = $companyId;
}
if ($serviceId) {
    $filters['service_id'] = $serviceId;
}


if ($action === 'export_csv') {
    $csvContent = financialGenerateRevenueReportCSV($startDate, $endDate, $filters);
    if ($csvContent) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rapport_revenus_' . date('Y-m-d') . '.csv"');
        echo $csvContent;
        exit;
    } else {
        flashMessage('Aucune donnée à exporter pour les filtres sélectionnés.', 'warning');
        
        unset($_GET['action']);
        redirectTo(WEBADMIN_URL . '/modules/financial/revenue_report.php?' . http_build_query($_GET));
    }
}


$reportData = financialGetDetailedRevenueReport($startDate, $endDate, $filters, $sortBy, $sortOrder);
$summary = financialGetTaxSummaryReport($startDate, $endDate, $filters);
$companies = companiesGetList(); 
$mainServicePacks = getMainServicePacks(); 


include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['action' => 'export_csv'])); ?>" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-csv me-2"></i> Exporter en CSV
                    </a>
                </div>
            </div>

            <!-- Résumé pour la période -->
            <div class="card mb-4">
                <div class="card-header">Résumé pour la période filtrée</div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="fs-5">Total HT</div>
                            <div class="fw-bold"><?php echo formatMoney($summary['total_ht']); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="fs-5">Total TVA</div>
                            <div class="fw-bold"><?php echo formatMoney($summary['total_tva']); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="fs-5">Total TTC</div>
                            <div class="fw-bold"><?php echo formatMoney($summary['total_ttc']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card for Filters and Results -->
            <div class="card mb-4">
                <div class="card-header">
                    <form method="GET" action="" class="row g-3 align-items-center">
                        <div class="col-md-2">
                            <label for="start_date" class="form-label visually-hidden">Date de début</label>
                            <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" title="Date de début">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label visually-hidden">Date de fin</label>
                            <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" title="Date de fin">
                        </div>
                        <div class="col-md-3">
                            <label for="company_id" class="form-label visually-hidden">Entreprise</label>
                            <select class="form-select form-select-sm" id="company_id" name="company_id" title="Filtrer par entreprise">
                                <option value="">Toutes les entreprises</option>
                                <?php foreach ($companies['companies'] as $company): ?>
                                    <option value="<?php echo $company['id']; ?>" <?php echo ($companyId == $company['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($company['nom']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="service_id" class="form-label visually-hidden">Service</label>
                            <select class="form-select form-select-sm" id="service_id" name="service_id" title="Filtrer par service">
                                <option value="">Tous les services</option>
                                <?php foreach ($mainServicePacks as $service): ?>
                                    <option value="<?php echo $service['id']; ?>" <?php echo ($serviceId == $service['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($service['type']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex">
                            <button type="submit" class="btn btn-primary btn-sm w-100 me-2">
                                <i class="fas fa-filter me-1"></i> Filtrer
                            </button>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/financial/revenue_report.php" class="btn btn-secondary btn-sm" data-bs-toggle="tooltip" title="Réinitialiser les filtres">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <!-- Tableau détaillé -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th># Facture</th>
                                    <th>Entreprise</th>
                                    <th>Service</th>
                                    <th>Date de paiement</th>
                                    <th class="text-end">Montant HT</th>
                                    <th class="text-end">Montant TVA</th>
                                    <th class="text-end">Montant TTC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center fst-italic">Aucune facture payée trouvée pour les critères sélectionnés.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $invoice): ?>
                                        <tr>
                                            <td><a href="<?php echo WEBADMIN_URL; ?>/modules/billing/view.php?id=<?php echo htmlspecialchars($invoice['id']); ?>"><?php echo htmlspecialchars($invoice['numero_facture']); ?></a></td>
                                            <td><?php echo htmlspecialchars($invoice['nom_entreprise'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($invoice['nom_service'] ?? 'N/A'); ?></td>
                                            <td><?php echo formatDate($invoice['date_paiement'], 'd/m/Y H:i'); ?></td>
                                            <td class="text-end"><?php echo formatMoney($invoice['montant_ht']); ?></td>
                                            <td class="text-end"><?php echo formatMoney($invoice['montant_total'] - $invoice['montant_ht']); ?></td>
                                            <td class="text-end fw-bold"><?php echo formatMoney($invoice['montant_total']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>

</rewritten_file>
