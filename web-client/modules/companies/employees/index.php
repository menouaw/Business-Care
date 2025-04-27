<?php

require_once __DIR__ . '/../../../includes/init.php';
require_once __DIR__ . '/../../../includes/page_functions/companies/employees.php';

requireRole(ROLE_ENTREPRISE);

$entreprise_id = $_SESSION['user_entreprise'] ?? 0;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['employee_id'])) {
    verifyCsrfToken();
    $action = $_POST['action'];
    $employee_id = (int)$_POST['employee_id'];
    $redirectUrl = WEBCLIENT_URL . '/modules/companies/employees/index.php?page=' . $current_page;

    if ($action === 'delete' && $employee_id > 0) {
        if (deactivateEmployee($employee_id, $entreprise_id)) {
            flashMessage('Salarié désactivé avec succès.', 'success');
        } else {
            flashMessage('Erreur lors de la désactivation.', 'danger');
        }
        redirectTo($redirectUrl);
        exit;
    } elseif ($action === 'reactivate' && $employee_id > 0) {
        if (reactivateEmployee($employee_id, $entreprise_id)) {
            flashMessage('Salarié réactivé avec succès.', 'success');
        } else {
            flashMessage('Erreur lors de la réactivation.', 'danger');
        }
        redirectTo($redirectUrl);
        exit;
    }
}

if ($entreprise_id <= 0) {
    flashMessage("Impossible d'identifier votre entreprise.", "danger");
    redirectTo(WEBCLIENT_URL . '/modules/companies/dashboard.php');
    exit;
}

$items_per_page = DEFAULT_ITEMS_PER_PAGE;
$employeeData = getCompanyEmployees($entreprise_id, $current_page, $items_per_page);
$employees = $employeeData['employees'];
$total_employees = $employeeData['total_count'];
$total_pages = ceil($total_employees / $items_per_page);

$pageTitle = "Gestion des Salariés";

include __DIR__ . '/../../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../../templates/sidebar.php';
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= WEBCLIENT_URL ?>/modules/companies/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de Bord
                    </a>
                    <a href="<?= WEBCLIENT_URL ?>/modules/companies/employees/add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Ajouter un salarié
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Nom</th>
                            <th scope="col">Prénom</th>
                            <th scope="col">Email</th>
                            <th scope="col">Téléphone</th>
                            <th scope="col">Site</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Dernière Connexion</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Aucun salarié trouvé pour cette entreprise.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr class="align-middle">
                                    <td><?= htmlspecialchars($employee['nom']) ?></td>
                                    <td><?= htmlspecialchars($employee['prenom']) ?></td>
                                    <td><?= htmlspecialchars($employee['email']) ?></td>
                                    <td><?= htmlspecialchars($employee['telephone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($employee['site_nom'] ?? 'Non défini') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $employee['statut'] === 'actif' ? 'success' : 'secondary' ?>">
                                            <?= htmlspecialchars(ucfirst($employee['statut'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($employee['derniere_connexion'] ? date('d/m/Y H:i', strtotime($employee['derniere_connexion'])) : 'Jamais') ?></td>
                                    <td>
                                        <a href="<?php echo WEBCLIENT_URL; ?>/modules/companies/employees/edit.php?id=<?= $employee['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Voir/Modifier">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <?php if ($employee['statut'] === 'actif'): ?>
                                            <form method="post" action="<?php echo WEBCLIENT_URL; ?>/modules/companies/employees/index.php?page=<?= $current_page ?>" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Désactiver" onclick="return confirm('Êtes-vous sûr de vouloir désactiver ce salarié ?');"><i class="fas fa-user-slash"></i></button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="<?php echo WEBCLIENT_URL; ?>/modules/companies/employees/index.php?page=<?= $current_page ?>" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                                                <input type="hidden" name="action" value="reactivate">
                                                <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Réactiver" onclick="return confirm('Êtes-vous sûr de vouloir réactiver ce salarié ?');"><i class="fas fa-user-check"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <?php
                // Préparer les données pour renderPagination
                $paginationData = [
                    'currentPage' => $current_page,
                    'totalPages' => $total_pages
                    // Si renderPagination a besoin d'autres clés, ajoutez-les ici.
                ];
                // Définir le modèle d'URL pour les liens de page
                $urlPattern = '?page={page}'; // Garde les autres paramètres GET s'il y en a

                // Afficher la pagination générée par la fonction
                echo renderPagination($paginationData, $urlPattern);
                ?>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../../templates/footer.php';
?>