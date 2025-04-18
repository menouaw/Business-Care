<?php
require_once '../../includes/page_functions/modules/providers.php';
require_once '../../includes/page_functions/modules/users.php';

requireRole(ROLE_ADMIN);


$filterData = getQueryData([
    'page' => 1, 
    'search' => '', 
    'status' => '', 
    'entreprise_id' => 0
]);
$page = $filterData['page'];
$search = $filterData['search'];
$status = $filterData['status'];


$listResult = usersGetList(
    $page, 
    DEFAULT_ITEMS_PER_PAGE, 
    $search, 
    ROLE_PRESTATAIRE,
    0,
    $status
);
$providers = $listResult['users'];
$totalPages = $listResult['totalPages'];
$totalItems = $listResult['totalItems'];
$page = $listResult['currentPage'];
$itemsPerPage = $listResult['perPage'];

$pageTitle = "Gestion des prestataires ({$totalItems})";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Ajouter un prestataire
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="card mb-4">
                <div class="card-header">
                    <form method="get" action="<?php echo WEBADMIN_URL; ?>/modules/providers/index.php" class="row g-3 align-items-center">
                        <div class="col-md-5">
                            <label for="search" class="visually-hidden">Rechercher</label>
                            <input type="text" class="form-control form-control-sm" id="search" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="visually-hidden">Statut</label>
                            <select class="form-select form-select-sm" id="status" name="status">
                                <option value="">Tous les statuts</option>
                                <?php foreach (USER_STATUSES as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $status === $s ? 'selected' : ''; ?>>
                                        <?php echo ucfirst(htmlspecialchars($s)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex">
                            <button type="submit" class="btn btn-sm btn-primary w-100 me-2">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/index.php" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Réinitialiser les filtres">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (count($providers) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($providers as $provider): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($provider['nom']); ?></td>
                                            <td><?php echo htmlspecialchars($provider['prenom']); ?></td>
                                            <td><a href="mailto:<?php echo htmlspecialchars($provider['email']); ?>"><?php echo htmlspecialchars($provider['email']); ?></a></td>
                                            <td><?php echo htmlspecialchars($provider['telephone'] ?: '-'); ?></td>
                                            <td><?php echo getStatusBadge($provider['statut']); ?></td>
                                            <td class="table-actions">
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/view.php?id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/edit.php?id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/delete.php?id=<?php echo $provider['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger btn-delete" data-bs-toggle="tooltip" title="Supprimer" data-provider-name="<?php echo htmlspecialchars($provider['prenom'] . ' ' . $provider['nom']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php
                        $paginationInfo = [
                            'currentPage' => $page,
                            'totalPages' => $totalPages,
                            'totalItems' => $totalItems,
                            'itemsPerPage' => $itemsPerPage
                        ];
                        $urlPattern = WEBADMIN_URL . '/modules/providers/index.php?search=' . urlencode($search) . '&status=' . urlencode($status) . '&page={page}';
                        ?>
                        <div class="d-flex justify-content-center">
                            <?php echo renderPagination($paginationInfo, $urlPattern); ?>
                        </div>
                    <?php else:
                        $isFiltering = !empty($search) || !empty($status);
                        $message = $isFiltering
                            ? "Aucun prestataire trouvé correspondant à vos critères de recherche."
                            : "Aucun prestataire trouvé. <a href=\"" . WEBADMIN_URL . "/modules/providers/add.php\" class=\"alert-link\">Ajouter un nouveau prestataire</a>";
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
