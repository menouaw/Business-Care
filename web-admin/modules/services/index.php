<?php
require_once '../../includes/page_functions/modules/services.php';

requireRole(ROLE_ADMIN);

$filterData = getQueryData(['page' => 1, 'search' => '', 'type' => '', 'category' => '']);
$page = $filterData['page'];
$search = $filterData['search'];
$type = $filterData['type'];
$category = $filterData['category'];

$listResult = servicesGetList($page, 10, $search, $type, $category);
$services = $listResult['services'];
$totalPages = $listResult['totalPages'];
$totalServices = $listResult['totalItems'];
$page = $listResult['currentPage'];
$itemsPerPage = $listResult['perPage'];

$serviceTypes = servicesGetTypes();
$serviceCategories = servicesGetCategories();

$pageTitle = "Gestion des services ({$totalServices})";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/services/add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Ajouter un service
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="card mb-4">
                <div class="card-header">
                    <form method="get" action="<?php echo WEBADMIN_URL; ?>/modules/services/index.php" class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <label for="search" class="visually-hidden">Rechercher</label>
                            <input type="text" class="form-control form-control-sm" id="search" name="search" placeholder="Rechercher par nom, description..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="type" class="visually-hidden">Type</label>
                            <select class="form-select form-select-sm" id="type" name="type">
                                <option value="">Tous les types</option>
                                <?php foreach ($serviceTypes as $t): ?>
                                    <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $type === $t ? 'selected' : ''; ?>>
                                        <?php echo ucfirst(htmlspecialchars($t)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="visually-hidden">Categorie</label>
                            <select class="form-select form-select-sm" id="category" name="category">
                                <option value="">Toutes les categories</option>
                                <?php foreach ($serviceCategories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                        <?php echo ucfirst(htmlspecialchars($cat)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex">
                            <button type="submit" class="btn btn-sm btn-primary w-100 me-2">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/services/index.php" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Réinitialiser les filtres">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (count($services) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Prix</th>
                                        <th>Duree</th>
                                        <th>Categorie</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($service['nom']); ?></td>
                                            <td><?php echo htmlspecialchars($service['type']); ?></td>
                                            <td><?php echo formatCurrency($service['prix']); ?></td>
                                            <td><?php echo $service['duree'] ? htmlspecialchars($service['duree']) . ' min' : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($service['categorie'] ?: '-'); ?></td>
                                            <td class="table-actions">
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/services/edit.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/services/delete.php?id=<?php echo $service['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger btn-delete" data-bs-toggle="tooltip" title="Supprimer">
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
                            'totalItems' => $totalServices,
                            'itemsPerPage' => $itemsPerPage
                        ];
                        $urlPattern = WEBADMIN_URL . '/modules/services/index.php?search=' . urlencode($search) . '&type=' . urlencode($type) . '&category=' . urlencode($category) . '&page={page}';
                        ?>
                        <div class="d-flex justify-content-center">
                            <?php echo renderPagination($paginationInfo, $urlPattern); ?>
                        </div>
                    <?php else: ?>
                        <?php
                        $isFiltering = !empty($search) || !empty($type) || !empty($category);
                        $message = $isFiltering
                            ? "Aucun service trouvé correspondant à vos critères de recherche."
                            : "Aucun service trouvé. <a href=\"" . WEBADMIN_URL . "/modules/services/add.php\" class=\"alert-link\">Ajouter un nouveau service</a>";
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