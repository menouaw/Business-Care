<?php

require_once __DIR__ . '/../../../includes/init.php';
require_once __DIR__ . '/../../../includes/page_functions/companies/employees.php';

requireRole(ROLE_ENTREPRISE);

$entreprise_id = $_SESSION['user_entreprise'] ?? 0;

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $employee_id = (int)$_GET['id'];

    if ($action === 'delete' && $employee_id > 0) {
        if (deactivateEmployee($employee_id, $entreprise_id)) {
            flashMessage('Salarié désactivé avec succès.', 'success');
        } else {
            flashMessage('Erreur lors de la désactivation du salarié ou salarié non trouvé.', 'danger');
        }
        $currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $redirectUrl = WEBCLIENT_URL . '/modules/companies/employees/index.php?page=' . $currentPage;
        redirectTo($redirectUrl);
        exit;
    } elseif ($action === 'reactivate' && $employee_id > 0) {
        if (reactivateEmployee($employee_id, $entreprise_id)) {
            flashMessage('Salarié réactivé avec succès.', 'success');
        } else {
            flashMessage('Erreur lors de la réactivation du salarié ou salarié non trouvé.', 'danger');
        }
        $currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $redirectUrl = WEBCLIENT_URL . '/modules/companies/employees/index.php?page=' . $currentPage;
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
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

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
                                <td colspan="8" class="text-center">Aucun salarié trouvé.</td>
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
                                            <a href="<?php echo WEBCLIENT_URL; ?>/modules/companies/employees/index.php?action=delete&id=<?= $employee['id'] ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Désactiver"
                                                onclick="return confirm('Êtes-vous sûr de vouloir désactiver ce salarié ?');">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo WEBCLIENT_URL; ?>/modules/companies/employees/index.php?action=reactivate&id=<?= $employee['id'] ?>"
                                                class="btn btn-sm btn-outline-success"
                                                title="Réactiver"
                                                onclick="return confirm('Êtes-vous sûr de vouloir réactiver ce salarié ?');">
                                                <i class="fas fa-user-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation employés">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $current_page - 1 ?>" aria-label="Précédent">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);

                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor;

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                        }
                        ?>

                        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>" aria-label="Suivant">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../../templates/footer.php';
?>