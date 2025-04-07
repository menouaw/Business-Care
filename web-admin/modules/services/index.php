<?php
require_once '../../includes/page_functions/modules/services.php';

requireRole(ROLE_ADMIN);

$filterData = getQueryData(['page' => 1, 'search' => '', 'type' => '', 'action' => '', 'id' => 0]);
$page = $filterData['page'];
$search = $filterData['search'];
$type = $filterData['type'];
$action = $filterData['action'];
$id = $filterData['id'];

$errors = [];
$serviceData = null; 
$service = []; 
$appointments = [];
$evaluations = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = servicesHandlePostRequest($_POST, $id);
    
    if ($result['success']) {
        flashMessage($result['message'], "success");
        redirectTo(WEBADMIN_URL . '/modules/services/');
    } else {
        $errors = $result['errors'];
        $service = $_POST; 
    }
}

if ($action === 'delete' && $id > 0) {
    $result = servicesDelete($id);
    flashMessage($result['message'], $result['success'] ? "success" : "danger");
    redirectTo(WEBADMIN_URL . '/modules/services/');
}

if (($action === 'edit' || $action === 'view') && $id > 0) {
    $serviceData = servicesGetDetails($id, $action === 'view'); 
    
    if (!$serviceData) {
        flashMessage("Service non trouve", "danger");
        redirectTo(WEBADMIN_URL . '/modules/services/');
    } else {
        $service = $serviceData['service']; 
        if ($action === 'view') {
            $appointments = $serviceData['appointments'] ?? [];
            $evaluations = $serviceData['evaluations'] ?? [];
        }
    }
}

$listResult = servicesGetList($page, 10, $search, $type);
$services = $listResult['services'];
$totalPages = $listResult['totalPages'];
$totalServices = $listResult['totalItems'];
$page = $listResult['currentPage'];
$itemsPerPage = $listResult['itemsPerPage']; // Assuming the function returns itemsPerPage

$serviceTypes = servicesGetTypes();

$pageTitle = "Gestion des services ({$totalServices})"; // Add total count to title
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
                <!-- formulaire d'ajout/edition -->
                <div class="card">
                    <div class="card-header">
                        <?php echo $action === 'add' ? 'Ajouter un nouveau service' : 'Modifier le service'; ?>
                    </div>
                    <div class="card-body">
                        <form method="post">
                           <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nom" class="form-label">Nom du service*</label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo isset($service['nom']) ? htmlspecialchars($service['nom']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="type" class="form-label">Type de service*</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Selectionnez...</option>
                                        <?php
                                        $types = ['conference', 'webinar', 'atelier', 'consultation', 'evenement', 'autre'];
                                        foreach ($types as $t) {
                                            $selected = (isset($service['type']) && $service['type'] === $t) ? 'selected' : '';
                                            echo "<option value=\"$t\" $selected>" . ucfirst($t) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($service['description']) ? htmlspecialchars($service['description']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="prix" class="form-label">Prix (€)*</label>
                                    <input type="number" class="form-control" id="prix" name="prix" min="0" step="0.01" value="<?php echo isset($service['prix']) ? htmlspecialchars($service['prix']) : ''; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="duree" class="form-label">Duree (minutes)</label>
                                    <input type="number" class="form-control" id="duree" name="duree" min="0" value="<?php echo isset($service['duree']) ? htmlspecialchars($service['duree']) : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="capacite_max" class="form-label">Capacite maximale</label>
                                    <input type="number" class="form-control" id="capacite_max" name="capacite_max" min="1" value="<?php echo isset($service['capacite_max']) ? htmlspecialchars($service['capacite_max']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="categorie" class="form-label">Categorie</label>
                                    <input type="text" class="form-control" id="categorie" name="categorie" value="<?php echo isset($service['categorie']) ? htmlspecialchars($service['categorie']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="niveau_difficulte" class="form-label">Niveau de difficulte</label>
                                    <select class="form-select" id="niveau_difficulte" name="niveau_difficulte">
                                        <option value="">Selectionnez...</option>
                                        <?php
                                        $levels = ['debutant', 'intermediaire', 'avance'];
                                        foreach ($levels as $level) {
                                            $selected = (isset($service['niveau_difficulte']) && $service['niveau_difficulte'] === $level) ? 'selected' : '';
                                            echo "<option value=\"$level\" $selected>" . ucfirst($level) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="materiel_necessaire" class="form-label">Materiel necessaire</label>
                                    <textarea class="form-control" id="materiel_necessaire" name="materiel_necessaire" rows="2"><?php echo isset($service['materiel_necessaire']) ? htmlspecialchars($service['materiel_necessaire']) : ''; ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="prerequis" class="form-label">Prerequis</label>
                                    <textarea class="form-control" id="prerequis" name="prerequis" rows="2"><?php echo isset($service['prerequis']) ? htmlspecialchars($service['prerequis']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    <a href="<?php echo WEBADMIN_URL; ?>/modules/services/index.php" class="btn btn-secondary">Annuler</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($action === 'view' && $service): ?>
                <!-- affichage des details d'un service -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Details du service</span>
                        <div>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/services/edit.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/services/delete.php?id=<?php echo $service['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger btn-delete"
                               onclick="return confirm('Etes-vous sûr de vouloir supprimer ce service ?')" data-bs-toggle="tooltip" title="Supprimer">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nom:</strong> <?php echo htmlspecialchars($service['nom']); ?></p>
                                <p><strong>Type:</strong> <?php echo htmlspecialchars($service['type']); ?></p>
                                <p><strong>Prix:</strong> <?php echo number_format($service['prix'], 2, ',', ' ') . ' €'; ?></p>
                                <p><strong>Duree:</strong> <?php echo $service['duree'] ? htmlspecialchars($service['duree']) . ' minutes' : 'Non specifiee'; ?></p>
                                <p><strong>Capacite maximale:</strong> <?php echo $service['capacite_max'] ? htmlspecialchars($service['capacite_max']) . ' personnes' : 'Non specifiee'; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Categorie:</strong> <?php echo htmlspecialchars($service['categorie'] ?: 'Non specifiee'); ?></p>
                                <p><strong>Niveau de difficulte:</strong> <?php echo htmlspecialchars($service['niveau_difficulte'] ?: 'Non specifie'); ?></p>
                                <p><strong>Date de creation:</strong> <?php echo htmlspecialchars($service['created_at']); ?></p>
                                <p><strong>Derniere mise a jour:</strong> <?php echo htmlspecialchars($service['updated_at']); ?></p>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Description</h5>
                                <p><?php echo nl2br(htmlspecialchars($service['description'] ?: 'Aucune description disponible')); ?></p>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h5>Materiel necessaire</h5>
                                <p><?php echo nl2br(htmlspecialchars($service['materiel_necessaire'] ?: 'Aucun materiel specifie')); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Prerequis</h5>
                                <p><?php echo nl2br(htmlspecialchars($service['prerequis'] ?: 'Aucun prerequis specifie')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- liste des rendez-vous associes -->
                <div class="mt-4">
                    <h3>Rendez-vous associes</h3>
                    <?php
                    if (!empty($appointments)):
                    ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Duree</th>
                                    <th>Lieu</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($appointment['date_rdv'])); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['prenom_personne'] . ' ' . $appointment['nom_personne']); ?></td>
                                        <td><?php echo $appointment['duree'] . ' minutes'; ?></td>
                                        <td><?php echo htmlspecialchars($appointment['lieu'] ?: 'Non specifie'); ?></td>
                                        <td><?php echo getStatusBadge($appointment['statut']); ?></td>
                                        <td>
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir Rendez-vous">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/edit.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier Rendez-vous">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/delete.php?id=<?php echo $appointment['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger btn-delete" 
                                               onclick="return confirm('Etes-vous sûr de vouloir supprimer ce rendez-vous ?')" data-bs-toggle="tooltip" title="Supprimer Rendez-vous">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Aucun rendez-vous associe a ce service
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- liste des evaluations associees -->
                <div class="mt-4">
                    <h3>Évaluations reçues</h3>
                    <?php
                    if (!empty($evaluations)):
                    ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <?php
                                $totalNotes = 0;
                                foreach ($evaluations as $evaluation) {
                                    $totalNotes += $evaluation['note'];
                                }
                                $moyenne = count($evaluations) > 0 ? round($totalNotes / count($evaluations), 1) : 0;
                                ?>
                                <h5>Note moyenne: <?php echo $moyenne; ?>/5 (<?php echo count($evaluations); ?> avis)</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($evaluations as $evaluation): ?>
                                    <div class="border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between">
                                            <h6><?php echo htmlspecialchars($evaluation['prenom_personne'] . ' ' . $evaluation['nom_personne']); ?></h6>
                                            <div>
                                                <strong>Note:</strong> <?php echo htmlspecialchars($evaluation['note']); ?>/5 - 
                                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($evaluation['date_evaluation'])); ?></small>
                                            </div>
                                        </div>
                                        <p><?php echo nl2br(htmlspecialchars($evaluation['commentaire'] ?: 'Pas de commentaire')); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Aucune evaluation pour ce service
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- liste des services -->
                <div class="card mb-4">
                    <div class="card-header">
                        <form method="get" action="<?php echo WEBADMIN_URL; ?>/modules/services/index.php" class="row g-3 align-items-center">
                            <div class="col-md-4">
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
                            <div class="col-md-5 d-flex">
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
                                            <td><?php echo number_format($service['prix'], 2, ',', ' ') . ' €'; ?></td>
                                            <td><?php echo $service['duree'] ? htmlspecialchars($service['duree']) . ' min' : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($service['categorie'] ?: '-'); ?></td>
                                            <td class="table-actions">
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/services/edit.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/services/delete.php?id=<?php echo $service['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger btn-delete" 
                                                   onclick="return confirm('Etes-vous sûr de vouloir supprimer ce service ?')" data-bs-toggle="tooltip" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                            
                            <?php 
                            // Use renderPagination
                            $paginationInfo = [
                                'currentPage' => $page,
                                'totalPages' => $totalPages,
                                'totalItems' => $totalServices,
                                'itemsPerPage' => $itemsPerPage
                            ];
                            $urlPattern = WEBADMIN_URL . '/modules/services/index.php?search=' . urlencode($search) . '&type=' . urlencode($type) . '&page={page}';
                            ?>
                            <div class="d-flex justify-content-center">
                                <?php echo renderPagination($paginationInfo, $urlPattern); ?>
                            </div>
                        <?php else: ?>
                            <?php
                            $isFiltering = !empty($search) || !empty($type);
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
            <?php endif; ?>
            
            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>