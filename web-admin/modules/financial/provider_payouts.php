<?php
require_once '../../includes/init.php';
require_once '../../includes/page_functions/modules/financial.php';
require_once '../../includes/page_functions/modules/users.php';

// requireRole(ROLE_ADMIN)

$pageTitle = "Paiements prestataires";


$queryData = getQueryData();
$action = $queryData['action'] ?? ''; 



$page = (int)($queryData['page'] ?? 1);
$perPage = DEFAULT_ITEMS_PER_PAGE;
$startDate = $queryData['start_date'] ?? null;
$endDate = $queryData['end_date'] ?? null;
$providerId = isset($queryData['provider_id']) && is_numeric($queryData['provider_id']) ? (int)$queryData['provider_id'] : null;
$status = $queryData['status'] ?? null;

$filters = [];
if ($providerId) {
    $filters['prestataire_id'] = $providerId;
}
if ($status && in_array($status, PRACTITIONER_INVOICE_STATUSES)) {
    $filters['statut'] = $status;
}


$providersListResult = usersGetList(1, 9999, '', ROLE_PRESTATAIRE);
$providers = $providersListResult['users'] ?? [];


$payoutsData = financialGetDetailedProviderPayouts($startDate, $endDate, $filters, $page, $perPage);


if ($action === 'export_csv') {
    $csvContent = financialGenerateProviderPayoutsCSV($startDate, $endDate, $filters);

    if ($csvContent) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="paiements_prestataires_' . date('Y-m-d') . '.csv"');
        echo $csvContent;
        exit;
    } else {
        flashMessage('Aucune donnée à exporter pour les filtres sélectionnés.', 'warning');
        unset($_GET['action']);
        redirectTo(adminUrl('/modules/financial/provider_payouts.php', $_GET));
    }
}



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
                    <?php
                        $exportParams = $_GET;
                        $exportParams['action'] = 'export_csv';
                    ?>
                    <a href="?<?php echo http_build_query($exportParams); ?>" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-csv me-2"></i> Exporter en CSV
                    </a>
                </div>
            </div>

            
            <div class="card mb-4">
                <div class="card-header">
                    <form method="GET" action="" class="row g-3 align-items-center">
                        <div class="col-md-2">
                            <label for="start_date" class="form-label visually-hidden">Date de début (facture)</label>
                            <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate ?? ''); ?>" title="Date de début (facture)">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label visually-hidden">Date de fin (facture)</label>
                            <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate ?? ''); ?>" title="Date de fin (facture)">
                        </div>
                        <div class="col-md-3">
                            <label for="provider_id" class="form-label visually-hidden">Prestataire</label>
                            <select class="form-select form-select-sm" id="provider_id" name="provider_id" title="Filtrer par prestataire">
                                <option value="">Tous les prestataires</option>
                                <?php foreach ($providers as $provider): ?>
                                    <option value="<?php echo $provider['id']; ?>" <?php echo ($providerId == $provider['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($provider['prenom'] . ' ' . $provider['nom']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label visually-hidden">Statut</label>
                            <select class="form-select form-select-sm" id="status" name="status" title="Filtrer par statut">
                                <option value="">Tous les statuts</option>
                                <?php foreach (PRACTITIONER_INVOICE_STATUSES as $stat): ?>
                                    <option value="<?php echo $stat; ?>" <?php echo ($status == $stat) ? 'selected' : ''; ?>><?php echo ucfirst($stat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex">
                             <button type="submit" class="btn btn-primary btn-sm w-100 me-2">
                                <i class="fas fa-filter me-1"></i> Filtrer
                            </button>
                            <a href="<?php echo adminUrl('/modules/financial/provider_payouts.php'); ?>" class="btn btn-secondary btn-sm" data-bs-toggle="tooltip" title="Réinitialiser les filtres">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th># Facture</th>
                                    <th>Prestataire</th>
                                    <th>Date facture</th>
                                    <th>Période</th>
                                    <th class="text-end">Montant</th>
                                    <th>Statut</th>
                                    <th>Date paiement</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payoutsData['items'])): ?>
                                    <tr>
                                        <td colspan="7" class="text-center fst-italic">Aucune facture prestataire trouvée pour les critères sélectionnés.</td> 
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payoutsData['items'] as $invoice): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($invoice['numero_facture']); ?></td>
                                            <td><?php echo htmlspecialchars($invoice['nom_prestataire'] ?? 'N/A'); ?></td>
                                            <td><?php echo formatDate($invoice['date_facture'], 'd/m/Y'); ?></td>
                                            <td><?php echo formatDate($invoice['periode_debut'], 'd/m/Y') . ' - ' . formatDate($invoice['periode_fin'], 'd/m/Y'); ?></td>
                                            <td class="text-end"><?php echo formatMoney($invoice['montant_total']); ?></td>
                                            <td><?php echo getStatusBadge($invoice['statut']); ?></td>
                                            <td><?php echo $invoice['date_paiement'] ? formatDate($invoice['date_paiement'], 'd/m/Y H:i') : '-'; ?></td>
                                            
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php
                        $paginationUrlPattern = '?' . http_build_query(array_merge($_GET, ['page' => '{page}']));
                        echo renderPagination($payoutsData, $paginationUrlPattern);
                    ?>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
