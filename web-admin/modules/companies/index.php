<?php
require_once '../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ADMIN);

$filterData = getQueryData(['page' => 1, 'search' => '', 'city' => '', 'size' => '', 'action' => '', 'id' => 0]);
$page = $filterData['page'];
$search = $filterData['search'];
$city = $filterData['city'];
$size = $filterData['size'];
$action = $filterData['action'];
$id = $filterData['id'];

$errors = [];
$company = null;

$cities = companiesGetCities();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom' => $_POST['nom'] ?? '',
        'siret' => $_POST['siret'] ?? '',
        'adresse' => $_POST['adresse'] ?? '',
        'code_postal' => $_POST['code_postal'] ?? '',
        'ville' => $_POST['ville'] ?? '',
        'telephone' => $_POST['telephone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'site_web' => $_POST['site_web'] ?? '',
        'taille_entreprise' => $_POST['taille_entreprise'] ?? '',
        'secteur_activite' => $_POST['secteur_activite'] ?? '',
        'date_creation' => $_POST['date_creation'] ?? null
    ];

    $result = companiesSave($data, $id);
    
    if ($result['success']) {
        flashMessage($result['message'], "success");
        redirectTo(WEBADMIN_URL . '/modules/companies/');
    } else {
        $errors = $result['errors'];
    }
}

if ($action === 'delete' && $id > 0) {
    $result = companiesDelete($id);
    flashMessage($result['message'], $result['success'] ? "success" : "danger");
    redirectTo(WEBADMIN_URL . '/modules/companies/');
}

if (($action === 'edit' || $action === 'view') && $id > 0) {
    $company = companiesGetDetails($id);
    
    if (!$company) {
        flashMessage("Entreprise non trouvee", "danger");
        redirectTo(WEBADMIN_URL . '/modules/companies/');
    }
}

$result = companiesGetList($page, 10, $search, $city, $size);
$companies = $result['companies'];
$totalPages = $result['totalPages'];
$totalCompanies = $result['totalItems'];
$page = $result['currentPage'];
$itemsPerPage = $result['perPage'];

$pageTitle = "Gestion des entreprises ({$totalCompanies})";
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
                        <i class="fas fa-plus"></i> Ajouter une entreprise
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
                        <?php echo $action === 'add' ? 'Ajouter une nouvelle entreprise' : 'Modifier l\'entreprise'; ?>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nom" class="form-label">Nom de l'entreprise*</label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo isset($company['nom']) ? htmlspecialchars($company['nom']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="siret" class="form-label">SIRET</label>
                                    <input type="text" class="form-control" id="siret" name="siret" value="<?php echo isset($company['siret']) ? htmlspecialchars($company['siret']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <textarea class="form-control" id="adresse" name="adresse" rows="2"><?php echo isset($company['adresse']) ? htmlspecialchars($company['adresse']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="code_postal" class="form-label">Code postal</label>
                                    <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?php echo isset($company['code_postal']) ? htmlspecialchars($company['code_postal']) : ''; ?>">
                                </div>
                                <div class="col-md-8">
                                    <label for="ville" class="form-label">Ville</label>
                                    <input type="text" class="form-control" id="ville" name="ville" value="<?php echo isset($company['ville']) ? htmlspecialchars($company['ville']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="telephone" class="form-label">Telephone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo isset($company['telephone']) ? htmlspecialchars($company['telephone']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($company['email']) ? htmlspecialchars($company['email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="site_web" class="form-label">Site web</label>
                                    <input type="url" class="form-control" id="site_web" name="site_web" value="<?php echo isset($company['site_web']) ? htmlspecialchars($company['site_web']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="date_creation" class="form-label">Date de creation</label>
                                    <input type="date" class="form-control" id="date_creation" name="date_creation" value="<?php echo isset($company['date_creation']) ? htmlspecialchars($company['date_creation']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="taille_entreprise" class="form-label">Taille de l'entreprise</label>
                                    <select class="form-select" id="taille_entreprise" name="taille_entreprise">
                                        <option value="">Selectionnez...</option>
                                        <?php
                                        foreach (COMPANY_SIZES as $s) {
                                            $selected = (isset($company['taille_entreprise']) && $company['taille_entreprise'] === $s) ? 'selected' : '';
                                            echo "<option value=\"$s\" $selected>$s</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="secteur_activite" class="form-label">Secteur d'activite</label>
                                    <input type="text" class="form-control" id="secteur_activite" name="secteur_activite" value="<?php echo isset($company['secteur_activite']) ? htmlspecialchars($company['secteur_activite']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($action === 'view' && $company): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Details de l'entreprise</span>
                        <div>
                            <a href="edit.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <a href="delete.php?id=<?php echo $company['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger btn-delete" 
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette entreprise ?')" data-bs-toggle="tooltip" title="Supprimer">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nom:</strong> <?php echo htmlspecialchars($company['nom']); ?></p>
                                <p><strong>SIRET:</strong> <?php echo htmlspecialchars($company['siret'] ?: 'Non renseigne'); ?></p>
                                <p><strong>Adresse:</strong> <?php echo htmlspecialchars($company['adresse'] ?: 'Non renseignee'); ?></p>
                                <p><strong>Code postal:</strong> <?php echo htmlspecialchars($company['code_postal'] ?: 'Non renseigne'); ?></p>
                                <p><strong>Ville:</strong> <?php echo htmlspecialchars($company['ville'] ?: 'Non renseignee'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Telephone:</strong> <?php echo htmlspecialchars($company['telephone'] ?: 'Non renseigne'); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($company['email'] ?: 'Non renseigne'); ?></p>
                                <p><strong>Site web:</strong> <?php echo htmlspecialchars($company['site_web'] ?: 'Non renseigne'); ?></p>
                                <p><strong>Taille:</strong> <?php echo htmlspecialchars($company['taille_entreprise'] ?: 'Non renseignee'); ?></p>
                                <p><strong>Secteur d'activite:</strong> <?php echo htmlspecialchars($company['secteur_activite'] ?: 'Non renseigne'); ?></p>
                                <p><strong>Date de creation:</strong> <?php echo htmlspecialchars($company['date_creation'] ?: 'Non renseignee'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h3>Contrats associes</h3>
                    <?php $contracts = $company['contracts']; ?>
                    
                    <?php if ($contracts): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Date debut</th>
                                    <th>Date fin</th>
                                    <th>Montant mensuel</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contracts as $contract): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($contract['type_contrat']); ?></td>
                                        <td><?php echo htmlspecialchars($contract['date_debut']); ?></td>
                                        <td><?php echo htmlspecialchars($contract['date_fin'] ?: '-'); ?></td>
                                        <td><?php echo number_format($contract['montant_mensuel'], 2, ',', ' ') . ' €'; ?></td>
                                        <td><?php echo getStatusBadge($contract['statut']); ?></td>
                                        <td>
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/view.php?id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir contrat">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Aucun contrat associe a cette entreprise
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4">
                    <h3>Utilisateurs associes</h3>
                    <?php $users = $company['users']; ?>
                    
                    <?php if ($users): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Telephone</th>
                                    <th>Rôle</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['telephone'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                        <td>
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir utilisateur">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Aucun utilisateur associe a cette entreprise
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <form method="get" action="<?php echo WEBADMIN_URL; ?>/modules/companies/index.php" class="row g-3 align-items-center">
                            <div class="col-md-3">
                                <label for="search" class="visually-hidden">Rechercher</label>
                                <input type="text" class="form-control form-control-sm" id="search" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="city" class="visually-hidden">Ville</label>
                                <select name="city" id="city" class="form-select form-select-sm">
                                    <option value="">Toutes les villes</option>
                                    <?php foreach ($cities as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $city == $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="size" class="visually-hidden">Taille</label>
                                <select name="size" id="size" class="form-select form-select-sm">
                                    <option value="">Toutes les tailles</option>
                                    <?php foreach (COMPANY_SIZES as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $size == $s ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex">
                                <button type="submit" class="btn btn-sm btn-primary w-100 me-2">
                                    <i class="fas fa-filter"></i> Filtrer
                                </button>
                                <a href="<?php echo WEBADMIN_URL; ?>/modules/companies/index.php" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="Réinitialiser les filtres">
                                    <i class="fas fa-undo"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php if (count($companies) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Ville</th>
                                            <th>Telephone</th>
                                            <th>Email</th>
                                            <th>Taille</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($companies as $company): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($company['nom']); ?></td>
                                                <td><?php echo htmlspecialchars($company['ville'] ?: '-'); ?></td>
                                                <td><?php echo htmlspecialchars($company['telephone'] ?: '-'); ?></td>
                                                <td><?php echo htmlspecialchars($company['email'] ?: '-'); ?></td>
                                                <td><?php echo htmlspecialchars($company['taille_entreprise'] ?: '-'); ?></td>
                                                <td class="table-actions">
                                                    <a href="<?php echo WEBADMIN_URL; ?>/modules/companies/view.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo WEBADMIN_URL; ?>/modules/companies/edit.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?php echo WEBADMIN_URL; ?>/modules/companies/delete.php?id=<?php echo $company['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger btn-delete" 
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
                                'totalItems' => $totalCompanies,
                                'itemsPerPage' => $itemsPerPage
                            ];
                            $urlPattern = WEBADMIN_URL . '/modules/companies/index.php?search=' . urlencode($search) . '&city=' . urlencode($city) . '&size=' . urlencode($size) . '&page={page}';
                            ?>
                            <div class="d-flex justify-content-center">
                                <?php echo renderPagination($paginationInfo, $urlPattern); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <?php 
                                $isFiltering = !empty($search) || !empty($city) || !empty($size);
                                $message = $isFiltering 
                                    ? "Aucune entreprise trouvée correspondant à vos critères."
                                    : "Aucune entreprise trouvée. <a href=\"" . WEBADMIN_URL . "/modules/companies/add.php\" class=\"alert-link\">Ajouter une entreprise</a>";
                                echo $message;
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
