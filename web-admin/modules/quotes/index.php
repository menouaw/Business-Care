<?php
require_once '../../includes/page_functions/modules/quotes.php'; 

requireRole(ROLE_ADMIN);

$filterData = getQueryData([
    'page' => 1, 
    'search' => '', 
    'status' => '', 
    'sector' => '',
    'action' => '', 
    'id' => 0
]);

$page = $filterData['page'];
$search = $filterData['search'];
$status = $filterData['status'];
$sector = $filterData['sector'];
$action = $filterData['action'];
$id = $filterData['id'];

$result = quotesGetList($page, DEFAULT_ITEMS_PER_PAGE, $search, $status, $sector);
$quotes = $result['items'];
$totalPages = $result['totalPages'];
$totalItems = $result['totalItems'];
$currentPage = $result['currentPage'];
$itemsPerPage = $result['perPage'];

$statuses = quotesGetStatuses();
$sectors = quotesGetCompanySectors();

$pageTitle = "Gestion des devis ({$totalItems})";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/quotes/edit.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Ajouter un devis
                    </a>
                </div>
            </div>
            
            <?php echo displayFlashMessages(); ?>

            <div class="card mb-4">
                <div class="card-header">
                     <form method="get" action="<?php echo WEBADMIN_URL; ?>/modules/quotes/index.php" class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <label for="search" class="visually-hidden">Rechercher</label>
                            <input type="text" class="form-control form-control-sm" id="search" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="visually-hidden">Statut</label>
                            <select class="form-select form-select-sm" id="status" name="status">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s); ?>" <?php echo ($status === $s) ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($s)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sector" class="visually-hidden">Secteur d'activité</label>
                            <select class="form-select form-select-sm" id="sector" name="sector">
                                <option value="">Tous les secteurs</option>
                                <?php foreach ($sectors as $sec): ?>
                                    <option value="<?php echo htmlspecialchars($sec); ?>" <?php echo ($sector === $sec) ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($sec)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex">
                            <button type="submit" class="btn btn-sm btn-primary w-100 me-2">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/quotes/index.php" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Réinitialiser les filtres">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (count($quotes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Entreprise</th>
                                        <th>Date Création</th>
                                        <th>Date Validité</th>
                                        <th>Montant TTC</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quotes as $quote): ?>
                                        <tr>
                                            <td>
                                                <?php if ($quote['entreprise_id']): ?>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/companies/view.php?id=<?php echo $quote['entreprise_id']; ?>">
                                                    <?php echo htmlspecialchars($quote['nom_entreprise']); ?>
                                                </a>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($quote['date_creation'], 'd/m/Y'); ?></td>
                                            <td><?php echo formatDate($quote['date_validite'], 'd/m/Y'); ?></td>
                                            <td><?php echo formatMoney($quote['montant_total']); ?></td>
                                            <td><?php echo getStatusBadge($quote['statut']); ?></td>
                                            <td class="table-actions">
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/quotes/view.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/quotes/edit.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                 <form method="POST" action="<?php echo WEBADMIN_URL; ?>/modules/quotes/delete.php" style="display: inline;">
                                                     <input type="hidden" name="id" value="<?php echo $quote['id']; ?>">
                                                     <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                                     <button type="submit" class="btn btn-sm btn-danger btn-delete" data-bs-toggle="tooltip" title="Supprimer">
                                                         <i class="fas fa-trash"></i>
                                                     </button>
                                                 </form>
                                            </td>
                                        </tr>
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
                        $urlParams = $filterData; 
                        unset($urlParams['page'], $urlParams['action'], $urlParams['id']);
                        $urlPattern = WEBADMIN_URL . '/modules/quotes/index.php?' . http_build_query(array_filter($urlParams)) . '&page={page}';
                        ?>
                        <div class="d-flex justify-content-center">
                            <?php echo renderPagination($paginationInfo, $urlPattern); ?>
                        </div>
                    <?php else:
                        $isFiltering = !empty($search) || !empty($status) || !empty($sector);
                        $message = $isFiltering 
                            ? "Aucun devis trouvé correspondant à vos critères de recherche."
                            : "Aucun devis n'a été créé pour le moment. <a href=\"" . WEBADMIN_URL . "/modules/quotes/edit.php\" class=\"alert-link\">Ajouter un devis</a>";
                        ?>
                        <div class="alert alert-info">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
