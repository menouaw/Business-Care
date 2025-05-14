<?php
require_once '../../includes/page_functions/modules/conferences.php';

requireRole(ROLE_ADMIN);

$queryData = getQueryData();

$page = $queryData['page'] ?? 1;
$search = $queryData['search'] ?? '';
$siteId = $queryData['siteId'] ?? 0;
$startDate = $queryData['startDate'] ?? '';
$endDate = $queryData['endDate'] ?? '';
$action = $queryData['action'] ?? '';
$id = $queryData['id'] ?? 0;


if ($action === 'delete' && $id > 0) {
    
    if (!isset($_GET['csrf_token']) || !validateToken($_GET['csrf_token'])) {
        flashMessage('Erreur de securite (CSRF token invalide).', 'danger');
    } else {
        $result = conferencesDelete($id);
        flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
    }
    
    $redirectParams = array_filter($queryData, function($key) {
        return !in_array($key, ['action', 'id']);
    }, ARRAY_FILTER_USE_KEY);
     redirectTo(WEBADMIN_URL . '/modules/conferences/' . (!empty($redirectParams) ? '?' . http_build_query($redirectParams) : ''));
}


$result = conferencesGetList($page, DEFAULT_ITEMS_PER_PAGE, $search, (int)$siteId, $startDate, $endDate);
$conferences = $result['conferences'];
$totalPages = $result['totalPages'];
$totalConferences = $result['totalItems'];
$page = $result['currentPage'];
$itemsPerPage = $result['perPage'];


$sites = conferencesGetSites();


$pageTitle = "Gestion des conférences ({$totalConferences})";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Ajouter une conférence
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="card mb-4">
                <div class="card-header">
                    <form method="get" action="<?php echo WEBADMIN_URL; ?>/modules/conferences/" class="row g-3 align-items-center">
                        <div class="col-lg-3 col-md-6">
                            <label for="search" class="visually-hidden">Rechercher</label>
                            <input type="text" class="form-control form-control-sm" id="search" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                         <div class="col-lg-3 col-md-6">
                            <label for="siteId" class="visually-hidden">Site</label>
                            <select class="form-select form-select-sm" id="siteId" name="siteId">
                                <option value="0">Tous les sites</option>
                                <?php foreach ($sites as $id_site => $name): ?>
                                    <option value="<?php echo $id_site; ?>" <?php echo ($siteId == $id_site) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label for="startDate" class="visually-hidden">Date debut</label>
                            <input type="date" class="form-control form-control-sm" id="startDate" name="startDate" value="<?php echo htmlspecialchars($startDate); ?>" title="Date de début">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label for="endDate" class="visually-hidden">Date fin</label>
                            <input type="date" class="form-control form-control-sm" id="endDate" name="endDate" value="<?php echo htmlspecialchars($endDate); ?>" title="Date de fin">
                        </div>

                        <div class="col-lg-2 col-md-6 d-flex">
                            <button type="submit" class="btn btn-sm btn-primary w-100 me-2">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/conferences/" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Réinitialiser les filtres">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (count($conferences) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Titre</th>
                                        <th>Date</th>
                                        <th>Lieu</th>
                                        <th>Site</th>
                                        <th>Capacité</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($conferences as $conference): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($conference['titre']); ?></td>
                                            <td><?php echo formatDate($conference['date_debut']); ?></td>
                                            <td><?php echo htmlspecialchars($conference['lieu'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($conference['site_nom'] ? $conference['site_nom'] . ' (' . $conference['site_ville'] . ')' : '-'); ?></td>
                                            <td><?php echo htmlspecialchars($conference['capacite_max'] ?: '-'); ?></td>
                                            <td class="table-actions">
                                                <a href="view.php?id=<?php echo $conference['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $conference['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php
                                                
                                                $currentFilters = array_filter($queryData, function($key) {
                                                    return !in_array($key, ['action', 'id']);
                                                }, ARRAY_FILTER_USE_KEY);
                                                $deleteUrlParams = array_merge($currentFilters, [
                                                    'action' => 'delete',
                                                    'id' => $conference['id'],
                                                    'csrf_token' => generateToken()
                                                ]);
                                                $deleteUrl = WEBADMIN_URL . '/modules/conferences/index.php?' . http_build_query($deleteUrlParams);
                                                ?>
                                                <a href="<?php echo $deleteUrl; ?>"
                                                   class="btn btn-sm btn-danger btn-delete"
                                                   data-bs-toggle="tooltip"
                                                   title="Supprimer"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette conférence ?');">
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
                            'totalItems' => $totalConferences,
                            'itemsPerPage' => $itemsPerPage
                        ];
                        
                        $currentFilters = array_filter($queryData, function($key) {
                            return !in_array($key, ['page', 'action', 'id']);
                        }, ARRAY_FILTER_USE_KEY);
                        $urlPattern = WEBADMIN_URL . '/modules/conferences/index.php?' . http_build_query($currentFilters) . '&page={page}';
                        ?>
                        <div class="d-flex justify-content-center">
                            <?php echo renderPagination($paginationInfo, $urlPattern); ?>
                        </div>
                    <?php else:
                        $isFiltering = !empty($search) || $siteId > 0 || !empty($startDate) || !empty($endDate);
                        $message = $isFiltering
                            ? "Aucune conférence trouvée correspondant à vos critères de recherche."
                            : "Aucune conférence trouvée. <a href=\"add.php\" class=\"alert-link\">Ajouter une conférence</a>";
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
