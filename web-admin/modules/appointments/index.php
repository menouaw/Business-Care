<?php
require_once '../../includes/page_functions/modules/appointments.php';

requireRole(ROLE_ADMIN);

$filterData = getQueryData([
    'page' => 1, 
    'search' => '', 
    'status' => '', 
    'type' => '', 
    'prestationId' => 0, 
    'startDate' => '', 
    'endDate' => '', 
    'action' => '', 
    'id' => 0
]);

$page = $filterData['page'];
$search = $filterData['search'];
$status = $filterData['status'];
$type = $filterData['type'];
$prestationId = $filterData['prestationId'];
$startDate = $filterData['startDate'];
$endDate = $filterData['endDate'];
$action = $filterData['action'];
$id = $filterData['id'];

if ($action === 'delete' && $id > 0) {
    if (!isset($_GET['csrf_token']) || !validateToken($_GET['csrf_token'])) {
        flashMessage('Erreur de securite.', 'danger');
    } else {
        $result = appointmentsDelete($id);
        flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
    }
    $redirectParams = array_filter($filterData, function($key) { 
        return !in_array($key, ['action', 'id', 'practitionerId', 'personId']);
    }, ARRAY_FILTER_USE_KEY);
    redirectTo(WEBADMIN_URL . '/modules/appointments/' . (!empty($redirectParams) ? '?' . http_build_query($redirectParams) : ''));
}

$result = appointmentsGetList($page, DEFAULT_ITEMS_PER_PAGE, $search, $status, $type, $prestationId, $startDate, $endDate);
$appointments = $result['appointments'];
$totalPages = $result['totalPages'];
$totalAppointments = $result['totalItems'];
$page = $result['currentPage'];
$itemsPerPage = $result['perPage'];

$services = appointmentsGetServices();
$statuses = appointmentsGetStatuses();
$types = appointmentsGetTypes();

$pageTitle = "Gestion des rendez-vous ({$totalAppointments})";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Ajouter un rendez-vous
                    </a>
                </div>
            </div>
            
            <?php echo displayFlashMessages(); ?>

            <div class="card mb-4">
                <div class="card-header">
                    <form method="get" action="<?php echo WEBADMIN_URL; ?>/modules/appointments/index.php" class="row g-3 align-items-center">
                        <div class="col-lg-3 col-md-6">
                            <label for="search" class="visually-hidden">Rechercher</label>
                            <input type="text" class="form-control form-control-sm" id="search" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="status" class="visually-hidden">Statut</label>
                            <select class="form-select form-select-sm" id="status" name="status">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($statuses as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo ($status === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="type" class="visually-hidden">Type</label>
                            <select class="form-select form-select-sm" id="type" name="type">
                                <option value="">Tous les types</option>
                                <?php foreach ($types as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo ($type === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="prestationId" class="visually-hidden">Service</label>
                            <select class="form-select form-select-sm" id="prestationId" name="prestationId">
                                <option value="0">Tous les services</option>
                                <?php foreach ($services as $id_service => $name): ?>
                                    <option value="<?php echo $id_service; ?>" <?php echo ($prestationId == $id_service) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label for="startDate" class="visually-hidden">Date debut</label>
                            <input type="date" class="form-control form-control-sm" id="startDate" name="startDate" value="<?php echo htmlspecialchars($startDate); ?>" title="Date de début">
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label for="endDate" class="visually-hidden">Date fin</label>
                            <input type="date" class="form-control form-control-sm" id="endDate" name="endDate" value="<?php echo htmlspecialchars($endDate); ?>" title="Date de fin">
                        </div>

                        <div class="col-lg-4 col-md-6 d-flex">
                            <button type="submit" class="btn btn-sm btn-primary w-100 me-2">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/index.php" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Réinitialiser les filtres">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (count($appointments) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Patient</th>
                                        <th>Praticien</th>
                                        <th>Service</th>
                                        <th>Duree</th>
                                        <th>Type</th>
                                        <th>Lieu</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo formatDate($appointment['date_rdv']); ?></td>
                                            <td>
                                                <?php if ($appointment['personne_id']): ?>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $appointment['personne_id']; ?>">
                                                    <?php echo htmlspecialchars($appointment['patient_prenom'] . ' ' . $appointment['patient_nom']); ?>
                                                </a>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($appointment['praticien_id']): ?>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $appointment['praticien_id']; ?>">
                                                    <?php echo htmlspecialchars($appointment['practitioner_prenom'] . ' ' . $appointment['practitioner_nom']); ?>
                                                </a>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                 <?php if ($appointment['prestation_id']): ?>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $appointment['prestation_id']; ?>">
                                                    <?php echo htmlspecialchars($appointment['prestation_nom']); ?>
                                                </a>
                                                 <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($appointment['duree']); ?> min</td>
                                            <td><?php echo htmlspecialchars(ucfirst($appointment['type_rdv'])); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['lieu'] ?: '-'); ?></td>
                                            <td><?php echo getStatusBadge($appointment['statut']); ?></td>
                                            <td class="table-actions">
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/edit.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/index.php?action=delete&id=<?php echo $appointment['id']; ?>&csrf_token=<?php echo generateToken(); ?>&<?php echo http_build_query(array_filter($filterData, fn($key) => !in_array($key, ['action', 'id', 'practitionerId', 'personId']), ARRAY_FILTER_USE_KEY)); ?>" 
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
                            'totalItems' => $totalAppointments,
                            'itemsPerPage' => $itemsPerPage
                        ];
                        $urlParams = $filterData; 
                        unset($urlParams['page'], $urlParams['action'], $urlParams['id'], $urlParams['practitionerId'], $urlParams['personId']);
                        $urlPattern = WEBADMIN_URL . '/modules/appointments/index.php?' . http_build_query(array_filter($urlParams)) . '&page={page}';
                        ?>
                        <div class="d-flex justify-content-center">
                            <?php echo renderPagination($paginationInfo, $urlPattern); ?>
                        </div>
                    <?php else:
                        $isFiltering = !empty($search) || !empty($status) || !empty($type) || !empty($prestationId) || !empty($startDate) || !empty($endDate);
                        $message = $isFiltering 
                            ? "Aucun rendez-vous trouvé correspondant à vos critères de recherche."
                            : "Aucun rendez-vous trouvé. <a href=\"" . WEBADMIN_URL . "/modules/appointments/add.php\" class=\"alert-link\">Ajouter un rendez-vous</a>";
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
