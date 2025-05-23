<?php
require_once '../../includes/page_functions/modules/contracts.php';

requireRole(ROLE_ADMIN);

$queryData = getQueryData();
$page = $queryData['page'] ?? 1;
$search = $queryData['search'] ?? '';
$statut = $queryData['statut'] ?? '';
$serviceId = $queryData['service_id'] ?? 0;
$action = $queryData['action'] ?? '';
$id = $queryData['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'entreprise_id' => $_POST['entreprise_id'] ?? '',
        'service_id' => $_POST['service_id'] ?? '',
        'date_debut' => $_POST['date_debut'] ?? '',
        'date_fin' => $_POST['date_fin'] ?? null,
        'nombre_salaries' => $_POST['nombre_salaries'] ?? null,
        'statut' => $_POST['statut'] ?? 'actif',
        'conditions_particulieres' => $_POST['conditions_particulieres'] ?? null
    ];

    $result = contractsSave($data, $id);
    
    if ($result['success']) {
        flashMessage($result['message'], "success");
        redirectTo(WEBADMIN_URL . '/modules/contracts/');
    } else {
        $errors = $result['errors'];
    }
}

if ($action === 'delete' && $id > 0) {
    $result = contractsDelete($id);
    flashMessage($result['message'], $result['success'] ? "success" : "danger");
    redirectTo(WEBADMIN_URL . '/modules/contracts/');
}

$contract = null;
if (($action === 'edit' || $action === 'view') && $id > 0) {
    $contract = contractsGetDetails($id);
    
    if (!$contract) {
        flashMessage("Contrat non trouve", "danger");
        redirectTo(WEBADMIN_URL . '/modules/contracts/');
    }
}

$result = contractsGetList($page, 10, $search, $statut, $serviceId);
$contracts = $result['contracts'];
$totalPages = $result['totalPages'];
$totalContracts = $result['totalItems'];
$page = $result['currentPage'];
$itemsPerPage = $result['perPage'];

$entreprises = contractsGetEntreprises();
$services = contractsGetServices();

$pageTitle = "Gestion des contrats ({$totalContracts})"; 
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
<?php include_once '../../templates/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?php echo $pageTitle; ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/add.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Ajouter un contrat
            </a>
        </div>
    </div>
    
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php echo displayFlashMessages(); ?>
    
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="card">
            <div class="card-header">
                <?php echo $action === 'add' ? 'Ajouter un nouveau contrat' : 'Modifier le contrat'; ?>
            </div>
            <div class="card-body">
                <form method="post">
                   <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="entreprise_id" class="form-label">Entreprise*</label>
                            <select class="form-select" id="entreprise_id" name="entreprise_id" required>
                                <option value="">Selectionnez une entreprise...</option>
                                <?php foreach ($entreprises as $e): ?>
                                    <option value="<?php echo $e['id']; ?>" <?php echo (isset($contract['entreprise_id']) && $contract['entreprise_id'] == $e['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($e['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="service_id" class="form-label">Service (Type de contrat)*</label>
                            <select class="form-select" id="service_id" name="service_id" required>
                                <option value="">Selectionnez un service...</option>
                                <?php
                                foreach ($services as $s) {
                                    $selected = (isset($contract['service_id']) && $contract['service_id'] == $s['id']) ? 'selected' : '';
                                    echo "<option value=\"{$s['id']}\" $selected>" . htmlspecialchars($s['nom']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date_debut" class="form-label">Date de debut*</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo isset($contract['date_debut']) ? htmlspecialchars($contract['date_debut']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo isset($contract['date_fin']) ? htmlspecialchars($contract['date_fin']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="nombre_salaries" class="form-label">Nombre de salaries</label>
                            <input type="number" min="1" class="form-control" id="nombre_salaries" name="nombre_salaries" value="<?php echo isset($contract['nombre_salaries']) ? htmlspecialchars($contract['nombre_salaries']) : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <?php
                                $statuts = ['actif', 'expire', 'resilie', 'en_attente'];
                                foreach ($statuts as $s) {
                                    $selected = (isset($contract['statut']) && $contract['statut'] === $s) ? 'selected' : '';
                                    echo "<option value=\"$s\" $selected>" . ucfirst($s) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="conditions_particulieres" class="form-label">Conditions particulieres</label>
                            <textarea class="form-control" id="conditions_particulieres" name="conditions_particulieres" rows="3"><?php echo isset($contract['conditions_particulieres']) ? htmlspecialchars($contract['conditions_particulieres']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/index.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif ($action === 'view' && $contract): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Details du contrat</span>
                <div>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/edit.php?id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/delete.php?id=<?php echo $contract['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger btn-delete"
                       data-bs-toggle="tooltip" title="Supprimer">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Entreprise:</strong> <a href="<?php echo WEBADMIN_URL; ?>/modules/companies/view.php?id=<?php echo $contract['entreprise_id']; ?>"><?php echo htmlspecialchars($contract['nom_entreprise']); ?></a></p>
                        <p><strong>Service:</strong> <?php echo htmlspecialchars(ucfirst($contract['type_service'])); ?></p>
                        <p><strong>Date de debut:</strong> <?php echo date('d/m/Y', strtotime($contract['date_debut'])); ?></p>
                        <p><strong>Date de fin:</strong> <?php echo $contract['date_fin'] ? date('d/m/Y', strtotime($contract['date_fin'])) : 'Indeterminee'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Nombre de salaries:</strong> <?php echo $contract['nombre_salaries'] ?: 'Non specifie'; ?></p>
                        <p><strong>Statut:</strong> <?php echo getStatusBadge($contract['statut']); ?></p>
                        <p><strong>Date de creation:</strong> <?php echo date('d/m/Y', strtotime($contract['created_at'])); ?></p>
                    </div>
                </div>
                
                <?php if ($contract['conditions_particulieres']): ?>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h5>Conditions particulieres</h5>
                            <p><?php echo nl2br(htmlspecialchars($contract['conditions_particulieres'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Statistiques du contrat</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php
                                    $dateDebut = new DateTime($contract['date_debut']);
                                    $dateFin = $contract['date_fin'] ? new DateTime($contract['date_fin']) : new DateTime();
                                    $duree = $dateDebut->diff($dateFin);
                                    ?>
                                    <div class="col-md-4 text-center">
                                        <h6>Duree</h6>
                                        <p class="h4"><?php echo $duree->y > 0 ? $duree->y . ' an(s) ' : ''; ?><?php echo $duree->m > 0 ? $duree->m . ' mois' : ($duree->y == 0 ? $duree->d . ' jour(s)' : ''); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header">
                <form method="get" action="<?php echo WEBADMIN_URL; ?>/modules/contracts/index.php" class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <label for="search" class="visually-hidden">Rechercher</label>
                        <input type="text" class="form-control form-control-sm" id="search" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="service_id" class="visually-hidden">Service</label>
                        <select class="form-select form-select-sm" id="service_id" name="service_id">
                            <option value="0">Tous les services</option>
                            <?php
                            foreach ($services as $s) {
                                $selected = ($serviceId == $s['id']) ? 'selected' : '';
                                echo "<option value=\"{$s['id']}\" $selected>" . htmlspecialchars($s['type']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="statut" class="visually-hidden">Statut</label>
                        <select class="form-select form-select-sm" id="statut" name="statut">
                            <option value="">Tous les statuts</option>
                            <?php
                            $statutsList = ['actif', 'expire', 'resilie', 'en_attente'];
                            foreach ($statutsList as $s) {
                                $selected = $statut === $s ? 'selected' : '';
                                echo "<option value=\"$s\" $selected>" . ucfirst($s) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex">
                        <button type="submit" class="btn btn-sm btn-primary w-100 me-2">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                         <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/index.php" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Réinitialiser les filtres">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <?php if (count($contracts) > 0): ?>
                    <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Entreprise</th>
                                <th>Service</th>
                                <th>Date de debut</th>
                                <th>Date de fin</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contracts as $contract): ?>
                                <tr>
                                    <td><a href="<?php echo WEBADMIN_URL; ?>/modules/companies/view.php?id=<?php echo $contract['entreprise_id']; ?>"><?php echo htmlspecialchars($contract['nom_entreprise']); ?></a></td>
                                    <td><?php echo htmlspecialchars(ucfirst($contract['type_service'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($contract['date_debut'])); ?></td>
                                    <td><?php echo $contract['date_fin'] ? date('d/m/Y', strtotime($contract['date_fin'])) : '-'; ?></td>
                                    <td><?php echo getStatusBadge($contract['statut']); ?></td>
                                    <td class="table-actions">
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/view.php?id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/edit.php?id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/delete.php?id=<?php echo $contract['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger btn-delete" 
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
                        'totalItems' => $totalContracts,
                        'itemsPerPage' => $itemsPerPage
                    ];
                    $urlPattern = WEBADMIN_URL . '/modules/contracts/index.php?search=' . urlencode($search) . '&statut=' . urlencode($statut) . '&service_id=' . urlencode($serviceId) . '&page={page}';
                    ?>
                    <div class="d-flex justify-content-center">
                        <?php echo renderPagination($paginationInfo, $urlPattern); ?>
                    </div>
                <?php else: ?>
                    <?php
                    $isFiltering = !empty($search) || !empty($statut) || !empty($serviceId);
                    $message = $isFiltering 
                        ? "Aucun contrat trouvé correspondant à vos critères de recherche."
                        : "Aucun contrat trouvé. <a href=\"" . WEBADMIN_URL . "/modules/contracts/add.php\" class=\"alert-link\">Ajouter un contrat</a>";
                    ?>
                    <div class="alert alert-info">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php include_once '../../templates/footer.php'; ?>
</main>
</div>
</div>
?> 