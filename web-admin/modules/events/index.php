<?php
require_once '../../includes/page_functions/modules/events.php';

requireRole(ROLE_ADMIN);

$queryData = getQueryData();

$page = $queryData['page'] ?? 1;
$search = $queryData['search'] ?? '';
$type = $queryData['type'] ?? '';
$siteId = $queryData['siteId'] ?? 0;
$organiseParBc = $queryData['organiseParBc'] ?? '';
$startDate = $queryData['startDate'] ?? '';
$endDate = $queryData['endDate'] ?? '';
$action = $queryData['action'] ?? '';
$id = $queryData['id'] ?? 0;

if ($action === 'delete' && $id > 0) {
    if (!isset($_GET['csrf_token']) || !validateToken($_GET['csrf_token'])) {
        flashMessage('Erreur de securite.', 'danger');
    } else {
        $result = eventsDelete($id);
        flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
    }
    
    $redirectParams = array_filter($queryData, function($key) {
        return !in_array($key, ['action', 'id']); 
    }, ARRAY_FILTER_USE_KEY);
    redirectTo(WEBADMIN_URL . '/modules/events/' . (!empty($redirectParams) ? '?' . http_build_query($redirectParams) : ''));
}

$result = eventsGetList($page, DEFAULT_ITEMS_PER_PAGE, $search, $type, $siteId, $organiseParBc, $startDate, $endDate);
$events = $result['events'];
$totalPages = $result['totalPages'];
$totalEvents = $result['totalItems'];
$page = $result['currentPage'];
$itemsPerPage = $result['perPage'];

$sites = eventsGetSites();
$eventTypes = eventsGetTypes();

$organiseParBcOptions = [
    '' => 'Tous les organisateurs',
    'oui' => 'BC',
    'non' => 'Autre',
];

$pageTitle = "Gestion des evenements ({$totalEvents})";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/events/add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Ajouter un evenement
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="card mb-4">
                <div class="card-header">
                    <form method="get" action="<?php echo WEBADMIN_URL; ?>/modules/events/index.php" class="row g-3 align-items-center">
                        <div class="col-lg-3 col-md-6">
                            <label for="search" class="visually-hidden">Rechercher</label>
                            <input type="text" class="form-control form-control-sm" id="search" name="search" placeholder="Rechercher (titre, description, lieu)..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="type" class="visually-hidden">Type</label>
                            <select class="form-select form-select-sm" id="type" name="type">
                                <option value="">Tous les types</option>
                                <?php foreach ($eventTypes as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo ($type === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-lg-3 col-md-6">
                            <label for="siteId" class="visually-hidden">Site</label>
                            <select class="form-select form-select-sm" id="siteId" name="siteId">
                                <option value="0">Tous les sites</option>
                                <?php foreach ($sites as $id_site => $name): ?>
                                    <option value="<?php echo $id_site; ?>" <?php echo ($siteId == $id_site) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <label for="organiseParBc" class="visually-hidden">Organise par BC</label>
                            <select class="form-select form-select-sm" id="organiseParBc" name="organiseParBc">
                                <?php foreach ($organiseParBcOptions as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo ($organiseParBc === (string)$value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label for="startDate" class="visually-hidden">Date debut</label>
                            <input type="date" class="form-control form-control-sm" id="startDate" name="startDate" value="<?php echo htmlspecialchars($startDate); ?>" title="Date de debut">
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label for="endDate" class="visually-hidden">Date fin</label>
                            <input type="date" class="form-control form-control-sm" id="endDate" name="endDate" value="<?php echo htmlspecialchars($endDate); ?>" title="Date de fin">
                        </div>

                        <div class="col-lg-4 col-md-6 d-flex">
                            <button type="submit" class="btn btn-sm btn-primary w-100 me-2">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/events/index.php" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Reinitialiser les filtres">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (count($events) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Date & Heure</th>
                                        <th>Titre</th>
                                        <th>Type</th>
                                        <th>Site / Lieu</th>
                                        <th>Capacite max</th>
                                        <th>Organise par BC</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events as $event): ?>
                                        <tr>
                                            <td><?php echo formatDate($event['date_debut']); ?></td>
                                            <td><?php echo htmlspecialchars($event['titre']); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $event['type']))); ?></td>
                                            <td>
                                                <?php
                                                    if ($event['site_id'] && $event['site_nom']) {
                                                        echo htmlspecialchars($event['site_nom'] . ' (' . $event['site_ville'] . ')');
                                                    } else {
                                                         echo htmlspecialchars($event['lieu'] ?: '-');
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($event['capacite_max'] ?: 'Illimitee'); ?></td>
                                             <td><?php echo $event['organise_par_bc'] ? 'Oui' : 'Non'; ?></td>
                                            <td class="table-actions">
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/events/view.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/events/edit.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/events/index.php?action=delete&id=<?php echo $event['id']; ?>&csrf_token=<?php echo generateToken(); ?>&<?php echo http_build_query(array_filter($queryData, fn($key) => !in_array($key, ['action', 'id']), ARRAY_FILTER_USE_KEY)); ?>" 
                                                   class="btn btn-sm btn-danger btn-delete" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Supprimer">
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
                            'totalItems' => $totalEvents,
                            'itemsPerPage' => $itemsPerPage
                        ];
                        $urlParams = $queryData;
                        unset($urlParams['page'], $urlParams['action'], $urlParams['id']);
                        $urlParams['organiseParBc'] = $organiseParBc;
                        $urlPattern = WEBADMIN_URL . '/modules/events/index.php?' . http_build_query(array_filter($urlParams)) . '&page={page}';
                        ?>
                        <div class="d-flex justify-content-center">
                            <?php echo renderPagination($paginationInfo, $urlPattern); ?>
                        </div>
                    <?php else:
                        $isFiltering = !empty($search) || !empty($type) || !empty($siteId) || !empty($organiseParBc) || !empty($startDate) || !empty($endDate);
                        $message = $isFiltering
                            ? "Aucun evenement trouve correspondant a vos criteres de recherche."
                            : "Aucun evenement trouve. <a href=\"" . WEBADMIN_URL . "/modules/events/add.php\" class=\"alert-link\">Ajouter un evenement</a>";
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
