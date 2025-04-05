<?php
require_once '../../includes/page_functions/modules/contracts.php';

requireRole(ROLE_ADMIN);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$entreprise = isset($_GET['entreprise']) ? (int)$_GET['entreprise'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'entreprise_id' => $_POST['entreprise_id'] ?? '',
        'date_debut' => $_POST['date_debut'] ?? '',
        'date_fin' => $_POST['date_fin'] ?? null,
        'montant_mensuel' => $_POST['montant_mensuel'] ?? null,
        'nombre_salaries' => $_POST['nombre_salaries'] ?? null,
        'type_contrat' => $_POST['type_contrat'] ?? '',
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

$result = contractsGetList($page, 10, $search, $statut, $entreprise);
$contracts = $result['contracts'];
$totalPages = $result['totalPages'];
$totalContracts = $result['totalItems'];
$page = $result['currentPage'];

$entreprises = contractsGetEntreprises();

$pageTitle = "Gestion des contrats";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
<?php include_once '../../templates/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Gestion des contrats</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="?action=add" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus"></i> Nouveau contrat
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
                            <label for="type_contrat" class="form-label">Type de contrat*</label>
                            <select class="form-select" id="type_contrat" name="type_contrat" required>
                                <option value="">Selectionnez un type...</option>
                                <?php
                                $types = ['standard', 'premium', 'entreprise'];
                                foreach ($types as $t) {
                                    $selected = (isset($contract['type_contrat']) && $contract['type_contrat'] === $t) ? 'selected' : '';
                                    echo "<option value=\"$t\" $selected>" . ucfirst($t) . "</option>";
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
                            <label for="montant_mensuel" class="form-label">Montant mensuel (€)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="montant_mensuel" name="montant_mensuel" value="<?php echo isset($contract['montant_mensuel']) ? htmlspecialchars($contract['montant_mensuel']) : ''; ?>">
                        </div>
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
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/" class="btn btn-secondary">Annuler</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif ($action === 'view' && $contract): ?>
        <!-- affichage des details d'un contrat -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Details du contrat</span>
                <div>
                    <a href="?action=edit&id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="?action=delete&id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce contrat ?')">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Entreprise:</strong> <a href="<?php echo WEBADMIN_URL; ?>/modules/companies/?action=view&id=<?php echo $contract['entreprise_id']; ?>"><?php echo htmlspecialchars($contract['nom_entreprise']); ?></a></p>
                        <p><strong>Type de contrat:</strong> <?php echo htmlspecialchars(ucfirst($contract['type_contrat'])); ?></p>
                        <p><strong>Date de debut:</strong> <?php echo date('d/m/Y', strtotime($contract['date_debut'])); ?></p>
                        <p><strong>Date de fin:</strong> <?php echo $contract['date_fin'] ? date('d/m/Y', strtotime($contract['date_fin'])) : 'Indeterminee'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Montant mensuel:</strong> <?php echo $contract['montant_mensuel'] ? number_format($contract['montant_mensuel'], 2, ',', ' ') . ' €' : 'Non specifie'; ?></p>
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
                
                <!-- calcul de la duree et des stats -->
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
                                    
                                    $montantTotal = 0;
                                    if ($contract['montant_mensuel']) {
                                        $mois = ($duree->y * 12) + $duree->m;
                                        $montantTotal = $mois * $contract['montant_mensuel'];
                                    }
                                    ?>
                                    <div class="col-md-4 text-center">
                                        <h6>Duree</h6>
                                        <p class="h4"><?php echo $duree->y > 0 ? $duree->y . ' an(s) ' : ''; ?><?php echo $duree->m > 0 ? $duree->m . ' mois' : ''; ?></p>
                                    </div>
                                    <?php if ($contract['montant_mensuel']): ?>
                                        <div class="col-md-4 text-center">
                                            <h6>Montant mensuel</h6>
                                            <p class="h4"><?php echo number_format($contract['montant_mensuel'], 2, ',', ' '); ?> €</p>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <h6>Montant total</h6>
                                            <p class="h4"><?php echo number_format($montantTotal, 2, ',', ' '); ?> €</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- liste des contrats -->
        <div class="card mb-4">
            <div class="card-header">
                <form method="get" class="row g-3">
                    <div class="col-md-2">
                        <input type="text" class="form-control" id="search" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="entreprise" name="entreprise">
                            <option value="">Toutes les entreprises</option>
                            <?php foreach ($entreprises as $e): ?>
                                <option value="<?php echo $e['id']; ?>" <?php echo $entreprise == $e['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($e['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="statut" name="statut">
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
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                        <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/" class="btn btn-secondary">Reinitialiser</a>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <?php if (count($contracts) > 0): ?>
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Entreprise</th>
                                <th>Type</th>
                                <th>Date de debut</th>
                                <th>Date de fin</th>
                                <th>Montant mensuel</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contracts as $contract): ?>
                                <tr>
                                    <td><a href="<?php echo WEBADMIN_URL; ?>/modules/companies/?action=view&id=<?php echo $contract['entreprise_id']; ?>"><?php echo htmlspecialchars($contract['nom_entreprise']); ?></a></td>
                                    <td><?php echo htmlspecialchars(ucfirst($contract['type_contrat'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($contract['date_debut'])); ?></td>
                                    <td><?php echo $contract['date_fin'] ? date('d/m/Y', strtotime($contract['date_fin'])) : '-'; ?></td>
                                    <td><?php echo $contract['montant_mensuel'] ? number_format($contract['montant_mensuel'], 2, ',', ' ') . ' €' : '-'; ?></td>
                                    <td><?php echo getStatusBadge($contract['statut']); ?></td>
                                    <td>
                                        <a href="?action=view&id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?action=edit&id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce contrat ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&statut=<?php echo urlencode($statut); ?>&entreprise=<?php echo $entreprise; ?>">Precedent</a>
                                </li>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&statut=<?php echo urlencode($statut); ?>&entreprise=<?php echo $entreprise; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo min($totalPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&statut=<?php echo urlencode($statut); ?>&entreprise=<?php echo $entreprise; ?>">Suivant</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        Aucun contrat trouve. <a href="?action=add" class="alert-link">Ajouter un nouveau contrat</a>
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