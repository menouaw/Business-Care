<?php
require_once '../../includes/init.php'; 
require_once '../../includes/page_functions/modules/billing.php';


requireRole(ROLE_ADMIN);


$queryData = getQueryData([
    'page' => 1, 
    'search' => '', 
    'status' => '', 
    'type' => 'client', 
    'date_from' => '', 
    'date_to' => ''
]);

$page = (int)$queryData['page'];
$search = $queryData['search'];
$status = $queryData['status'];
$type = $queryData['type'];
$date_from = $queryData['date_from'];
$date_to = $queryData['date_to'];


$clientStatuses = billingGetClientInvoiceStatuses();
$providerStatuses = billingGetProviderInvoiceStatuses();

$currentStatuses = ($type === 'client') ? $clientStatuses : $providerStatuses;


$result = null;
if ($type === 'provider') {
    $result = billingGetProviderInvoicesList($page, DEFAULT_ITEMS_PER_PAGE, $search, $status, 0, $date_from, $date_to);
} else {
    $type = 'client'; 
    $result = billingGetClientInvoicesList($page, DEFAULT_ITEMS_PER_PAGE, $search, $status, $date_from, $date_to);
}

$invoices = $result['items'];
$totalPages = $result['totalPages'];
$totalItems = $result['totalItems'];
$currentPage = $result['currentPage'];
$itemsPerPage = $result['perPage'];

$pageTitle = "Gestion de la facturation";
include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($pageTitle) . " (" . $totalItems . ")"; ?></h1>
                 <div class="btn-toolbar mb-2 mb-md-0">
                   
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <form action="<?php echo WEBADMIN_URL; ?>/modules/billing/index.php" method="get" class="row g-3 align-items-center">
                    <div class="col-md-6 col-lg-4">
                            <label for="search" class="visually-hidden">Rechercher</label>
                            <input type="text" class="form-control form-control-sm" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher...">
                        </div>
                        <div class="col-md-6 col-lg-4">
                             <label for="type" class="visually-hidden">Type de facture</label>
                            <select name="type" id="type" class="form-select form-select-sm">
                                <option value="client" <?php echo ($type === 'client') ? 'selected' : ''; ?>>Factures Client</option>
                                <option value="provider" <?php echo ($type === 'provider') ? 'selected' : ''; ?>>Factures Prestataire</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-4">
                            <label for="status" class="visually-hidden">Statut</label>
                            <select name="status" id="status" class="form-select form-select-sm">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($currentStatuses as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $status == $s ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $s))); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-4 col-lg-4">
                             <label for="date_from" class="visually-hidden">Date début</label>
                             <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" title="Date début">
                         </div>
                          <div class="col-md-4 col-lg-4">
                             <label for="date_to" class="visually-hidden">Date Fin</label>
                             <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" title="Date fin">
                         </div>
                        <div class="col-md-12 col-lg-4 d-flex">
                            <button type="submit" class="btn btn-sm btn-primary w-100 me-2">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/billing/index.php?type=<?php echo $type; ?>" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Réinitialiser les filtres">
                                    <i class="fas fa-undo"></i>
                                </a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (!empty($invoices)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle">
                            <thead>
                                <?php if ($type === 'client'): ?>
                                <tr>
                                    <th>N° Facture</th>
                                    <th>Entreprise</th>
                                    <th>Date émission</th>
                                    <th>Date échéance</th>
                                    <th class="text-end">Montant</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                                <?php elseif ($type === 'provider'): ?>
                                 <tr>
                                    <th>N° Facture</th>
                                    <th>Prestataire</th>
                                    <th>Date facture</th>
                                    <th>Période</th>
                                    <th class="text-end">Montant Total</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                                <?php endif; ?>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $invoice): ?>
                                    <?php if ($type === 'client'): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($invoice['numero_facture']); ?></td>
                                        <td><?php echo htmlspecialchars($invoice['nom_entreprise'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatDate($invoice['date_emission'], 'd/m/Y'); ?></td>
                                        <td><?php echo formatDate($invoice['date_echeance'], 'd/m/Y'); ?></td>
                                        <td class="text-end"><?php echo formatMoney($invoice['montant_total']); ?></td>
                                        <td><?php echo billingGetInvoiceStatusBadge($invoice['statut'], 'client'); ?></td>
                                        <td class="table-actions">
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/billing/view.php?id=<?php echo $invoice['id']; ?>&type=client" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php elseif ($type === 'provider'): ?>
                                     <tr>
                                        <td><?php echo htmlspecialchars($invoice['numero_facture']); ?></td>
                                        <td><?php echo htmlspecialchars($invoice['nom_prestataire'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatDate($invoice['date_facture'], 'd/m/Y'); ?></td>
                                        <td><?php echo formatDate($invoice['periode_debut'], 'd/m/Y') . ' - ' . formatDate($invoice['periode_fin'], 'd/m/Y'); ?></td>
                                        <td class="text-end"><?php echo formatMoney($invoice['montant_total']); ?></td>
                                         <td><?php echo billingGetInvoiceStatusBadge($invoice['statut'], 'provider'); ?></td>
                                        <td class="table-actions">
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/billing/view.php?id=<?php echo $invoice['id']; ?>&type=provider" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php
                    $paginationInfo = [
                        'currentPage' => $currentPage,
                        'totalPages' => $totalPages,
                        'totalItems' => $totalItems,
                        'itemsPerPage' => $itemsPerPage
                    ];
                    $urlParams = array_filter([
                        'type' => $type, 
                        'search' => $search, 
                        'status' => $status,
                        'date_from' => $date_from,
                        'date_to' => $date_to
                        ]);
                    $urlPattern = WEBADMIN_URL . '/modules/billing/index.php?' . http_build_query($urlParams) . (empty($urlParams) ? '' : '&') . 'page={page}';
                    ?>
                    <div class="d-flex justify-content-center">
                        <?php echo renderPagination($paginationInfo, $urlPattern); ?>
                    </div>
                    <?php else: ?>
                        <?php
                        $isFiltering = !empty($search) || !empty($status) || !empty($date_from) || !empty($date_to);
                        $message = $isFiltering 
                            ? "Aucune facture trouvée correspondant à vos critères."
                            : "Aucune facture à afficher pour le moment.";
                        ?>
                        <div class="alert alert-info mt-3" role="alert">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
