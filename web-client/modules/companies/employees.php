<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ENTREPRISE);

$entrepriseId = $_SESSION['user_entreprise'] ?? null;
if (!$entrepriseId) {
    flashMessage("Impossible d'identifier votre entreprise. Veuillez vous reconnecter.", "danger");
    redirectTo(WEBCLIENT_URL . '/connexion.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$employeeId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]) : 1;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['statut']) ? sanitizeInput($_GET['statut']) : 'actif';

$employee = null;
$errors = [];
$submittedData = $_SESSION['submitted_data'] ?? [];
unset($_SESSION['submitted_data']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postResult = handleCompanyEmployeePostRequest($_POST, $entrepriseId);

    if (isset($postResult['redirectUrl'])) {
        redirectTo($postResult['redirectUrl']);
        exit;
    }

    $action = $postResult['action'] ?? 'list';
    $employeeId = $postResult['employeeId'] ?? null;
}

$employeesResult = getCompanyEmployees($entrepriseId, $page, 6, $search, $statusFilter);

$viewData = prepareCompanyEmployeeViewData($action, $entrepriseId, $employeeId, $page, $search, $statusFilter, $submittedData);

if (isset($viewData['redirectUrl'])) {
    redirectTo($viewData['redirectUrl']);
    exit;
}

$pageTitle = $viewData['pageTitle'];
$employee = $viewData['employee'];
$employeesData = $viewData['employeesData'];
$paginationHtml = $viewData['paginationHtml'];

$csrfToken = generateToken();

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="container py-4">

    <?php echo displayFlashMessages(); ?>

    <?php if ($action === 'list'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?= $pageTitle ?></h1>
            <div>
                <a href="index.php" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left me-1"></i> Retour</a>
                <a href="?action=add" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Ajouter un Salarié
                </a>
            </div>
        </div>

        <form method="get" action="employees.php" class="mb-4">
            <input type="hidden" name="action" value="list">
            <div class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher par nom, prénom, email..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-4">
                    <select name="statut" class="form-select">
                        <option value="actif" <?= $statusFilter === 'actif' ? 'selected' : '' ?>>Actifs</option>
                        <option value="inactif" <?= $statusFilter === 'inactif' ? 'selected' : '' ?>>Inactifs</option>
                        <option value="suspendu" <?= $statusFilter === 'suspendu' ? 'selected' : '' ?>>Suspendus</option>
                        <option value="tous" <?= $statusFilter === 'tous' ? 'selected' : '' ?>>Tous les statuts</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid d-md-block">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Filtrer</button>
                    <a href="?action=list" class="btn btn-outline-secondary">Réinitialiser</a>
                </div>
            </div>
        </form>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?php if (empty($employeesData)): ?>
                    <div class="alert alert-info text-center">Aucun salarié trouvé pour les critères sélectionnés.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Statut</th>
                                    <th>Dernière connexion</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employeesData as $emp): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($emp['nom']) ?></td>
                                        <td><?= htmlspecialchars($emp['prenom']) ?></td>
                                        <td><?= htmlspecialchars($emp['email']) ?></td>
                                        <td><?= htmlspecialchars($emp['telephone'] ?: 'N/A') ?></td>
                                        <td><?= $emp['statut_badge'] ?? getStatusBadge($emp['statut']) ?></td>
                                        <td><?= $emp['derniere_connexion_formatee'] ?? 'Jamais' ?></td>
                                        <td class="text-end">
                                            <a href="?action=view&id=<?= $emp['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir"><i class="fas fa-eye"></i></a>
                                            <a href="?action=edit&id=<?= $emp['id'] ?>" class="btn btn-sm btn-outline-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                            <?php if ($emp['statut'] === 'suspendu'): ?>
                                                <form action="employees.php" method="post" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir réactiver cet employé ?');">
                                                    <input type="hidden" name="action" value="reactivate_employee">
                                                    <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Réactiver"><i class="fas fa-play-circle"></i></button>
                                                </form>
                                            <?php elseif ($emp['statut'] !== 'supprime' && $emp['statut'] !== 'suspendu'): ?>
                                                <form action="employees.php" method="post" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir suspendre cet employé ? Il ne pourra plus se connecter.');">
                                                    <input type="hidden" name="action" value="delete_employee">
                                                    <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Suspendre"><i class="fas fa-user-slash"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 d-flex justify-content-center">
                        <?= $paginationHtml ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($action === 'view' && $employee): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?= $pageTitle ?></h1>
            <div>
                <a href="dashboard.php" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left me-1"></i> Retour</a>
                <?php if ($employee['statut'] !== 'supprime'): ?>
                    <a href="?action=edit&id=<?= $employee['id'] ?>" class="btn btn-warning"><i class="fas fa-edit me-1"></i> Modifier</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-3">
                        <img src="<?= $employee['photo_url'] ? htmlspecialchars(UPLOAD_URL . $employee['photo_url']) : ASSETS_URL . '/img/placeholder_avatar.png' ?>" alt="Photo de profil" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                        <h5 class="mt-2 mb-0"><?= htmlspecialchars($employee['prenom'] . ' ' . $employee['nom']) ?></h5>
                        <?= $employee['statut_badge'] ?? getStatusBadge($employee['statut']) ?>
                    </div>
                    <div class="col-md-9">
                        <dl class="row">
                            <dt class="col-sm-3">Email</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($employee['email']) ?></dd>

                            <dt class="col-sm-3">Téléphone</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($employee['telephone'] ?: 'Non renseigné') ?></dd>

                            <dt class="col-sm-3">Date de naissance</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($employee['date_naissance_formatee'] ?: 'Non renseignée') ?></dd>

                            <dt class="col-sm-3">Genre</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($employee['genre_formate'] ?? 'Non spécifié') ?></dd>

                            <dt class="col-sm-3">Statut</dt>
                            <dd class="col-sm-9"><?= $employee['statut_badge'] ?? getStatusBadge($employee['statut']) ?></dd>

                            <dt class="col-sm-3">Dernière connexion</dt>
                            <dd class="col-sm-9"><?= $employee['derniere_connexion_formatee'] ?></dd>

                            <dt class="col-sm-3">Membre depuis</dt>
                            <dd class="col-sm-9"><?= isset($employee['created_at']) ? formatDate($employee['created_at'], 'd/m/Y') : 'Date inconnue' ?></dd>
                        </dl>
                        <?php if ($employee['statut'] === 'suspendu'): ?>
                            <div class="alert alert-warning mt-3">Cet employé est actuellement suspendu et ne peut pas se connecter.</div>
                        <?php elseif ($employee['statut'] === 'supprime'): ?>
                            <div class="alert alert-danger mt-3">Ce compte employé a été désactivé (statut 'supprime').</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif (($action === 'add' || $action === 'edit') && isset($entrepriseId)): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?= $pageTitle ?></h1>
            <div>
                <a href="dashboard.php" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left me-1"></i> Retour</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="employees.php<?= $action === 'edit' && $employeeId ? '?action=edit&id=' . $employeeId : '?action=add' ?>" method="post">
                    <input type="hidden" name="action" value="<?= $action === 'edit' ? 'edit_employee' : 'add_employee' ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <?php if ($action === 'edit' && $employeeId): ?>
                        <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                    <?php endif; ?>

                    <?php
                    if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <strong>Erreur(s) :</strong><br>
                            <?= implode('<br>', $errors) ?>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($employee['nom'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($employee['prenom'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($employee['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($employee['telephone'] ?? '') ?>" placeholder="0XXXXXXXXX ou +33XXXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label for="date_naissance" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($employee['date_naissance'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="genre" class="form-label">Genre</label>
                            <select class="form-select" id="genre" name="genre">
                                <option value="" <?= !isset($employee['genre']) || $employee['genre'] === '' ? 'selected' : '' ?>>Non spécifié</option>
                                <option value="M" <?= isset($employee['genre']) && $employee['genre'] === 'M' ? 'selected' : '' ?>>Masculin</option>
                                <option value="F" <?= isset($employee['genre']) && $employee['genre'] === 'F' ? 'selected' : '' ?>>Féminin</option>
                                <option value="Autre" <?= isset($employee['genre']) && $employee['genre'] === 'Autre' ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>

                        <?php if ($action === 'edit'): ?>
                            <div class="col-md-6">
                                <label for="statut" class="form-label">Statut</label>
                                <select class="form-select" id="statut" name="statut">
                                    <?php foreach (USER_STATUSES as $status):
                                        if ($status === 'supprime') continue;
                                        $currentStatus = $employee['statut'] ?? 'actif';
                                        if ($currentStatus === 'supprime' && $status !== 'supprime') continue;
                                    ?>
                                        <option value="<?= $status ?>" <?= $currentStatus === $status ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(ucfirst($status)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else:
                        ?>
                            <input type="hidden" name="statut" value="actif">
                        <?php endif; ?>

                        <?php if ($action === 'add'): ?>
                            <div class="col-12">
                                <div class="alert alert-info small">
                                    Un mot de passe temporaire sécurisé sera généré automatiquement pour le nouvel employé et devra lui être communiqué (ou une procédure de réinitialisation de mot de passe sera initiée).
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-<?= $action === 'edit' ? 'save' : 'plus' ?> me-1"></i>
                            <?= $action === 'edit' ? 'Enregistrer les modifications' : 'Ajouter le Salarié' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>

</main>

<?php include_once __DIR__ . '/../../templates/footer.php'; ?>