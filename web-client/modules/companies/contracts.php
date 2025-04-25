<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/companies/contracts.php';

requireRole(ROLE_ENTREPRISE);



$entreprise_id = $_SESSION['user_entreprise'] ?? 0;

if ($entreprise_id <= 0) {
    flashMessage("Impossible d'identifier votre entreprise.", "danger");
    redirectTo(WEBCLIENT_URL . '/modules/companies/dashboard.php');
    exit;
}

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
$contract_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$pageTitle = "Gestion des Contrats";
$contract = null;
$contracts = [];
$total_contracts = 0;
$total_pages = 1;
$current_page = 1;

if ($action === 'view' && $contract_id) {
    $contract = getContractDetails($contract_id, $entreprise_id);
    if (!$contract) {
        flashMessage("Contrat non trouvé ou accès refusé.", "warning");
        redirectTo(WEBCLIENT_URL . '/modules/companies/contracts.php');
        exit;
    }
    $pageTitle = "Détails du Contrat #" . $contract['id'];
} else {
    $action = 'list';
    $pageTitle = "Mes Contrats";

    // --- Logique de Pagination ---
    $items_per_page = DEFAULT_ITEMS_PER_PAGE;
    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    // Appel de la fonction modifiée
    $contractsData = getCompanyContracts($entreprise_id, $current_page, $items_per_page);
    $contracts = $contractsData['contracts'];
    $total_contracts = $contractsData['total_count'];
    $total_pages = ceil($total_contracts / $items_per_page);
    // --- Fin Logique de Pagination ---
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">

            <?php echo displayFlashMessages(); ?>

            <?php
            ?>

            <?php if ($action === 'view' && $contract): ?>
                <?php
                ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= WEBCLIENT_URL ?>/modules/companies/contracts.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-list me-1"></i> Retour à la liste
                        </a>
                        <a href="<?= WEBCLIENT_URL ?>/modules/companies/dashboard.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de Bord
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        Informations sur le contrat
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">ID Contrat:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($contract['id']) ?></dd>

                            <dt class="col-sm-3">Service / Pack:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($contract['service_nom'] ?? 'N/D') ?></dd>

                            <dt class="col-sm-3">Date de Début:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(date('d/m/Y', strtotime($contract['date_debut']))) ?></dd>

                            <dt class="col-sm-3">Date de Fin:</dt>
                            <dd class="col-sm-9"><?= $contract['date_fin'] ? htmlspecialchars(date('d/m/Y', strtotime($contract['date_fin']))) : 'Indéfinie' ?></dd>

                            <dt class="col-sm-3">Nombre de salariés couverts:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($contract['nombre_salaries'] ?? 'N/D') ?></dd>

                            <dt class="col-sm-3">Statut:</dt>
                            <dd class="col-sm-9">
                                <span class="badge bg-<?= getStatusBadgeClass($contract['statut']) ?>">
                                    <?= htmlspecialchars(ucfirst($contract['statut'])) ?>
                                </span>
                            </dd>

                            <dt class="col-sm-3">Conditions Particulières:</dt>
                            <dd class="col-sm-9">
                                <pre><?= htmlspecialchars($contract['conditions_particulieres'] ?? 'Aucune') ?></pre>
                            </dd>

                            <dt class="col-sm-3">Date Création (Système):</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(date(DEFAULT_DATE_FORMAT, strtotime($contract['created_at']))) ?></dd>

                            <dt class="col-sm-3">Dernière Mise à Jour:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(date(DEFAULT_DATE_FORMAT, strtotime($contract['updated_at']))) ?></dd>
                        </dl>
                    </div>
                </div>
                <!

                    <?php else: // $action === 'list' 
                    ?>
                    <?php
                    ?>
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= WEBCLIENT_URL ?>/modules/companies/dashboard.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de Bord
                        </a>
                    </div>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Service / Pack</th>
                    <th>Date Début</th>
                    <th>Date Fin</th>
                    <th>Statut</th>
                    <th>Dernière MAJ</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contracts)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Aucun contrat trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contracts as $contract_item): ?>
                        <tr>
                            <td><?= htmlspecialchars($contract_item['id']) ?></td>
                            <td><?= htmlspecialchars($contract_item['service_nom'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($contract_item['date_debut']))) ?></td>
                            <td><?= $contract_item['date_fin'] ? htmlspecialchars(date('d/m/Y', strtotime($contract_item['date_fin']))) : 'Indéfinie' ?></td>
                            <td>
                                <span class="badge bg-<?= getStatusBadgeClass($contract_item['statut']) ?>">
                                    <?= htmlspecialchars(ucfirst($contract_item['statut'])) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(date(DEFAULT_DATE_FORMAT, strtotime($contract_item['updated_at']))) ?></td>
                            <td>
                                <a href="<?= WEBCLIENT_URL ?>/modules/companies/contracts.php?action=view&id=<?= $contract_item['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir Détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php /* Boutons Edit/Delete supprimés */ ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Contrôles de Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation contrats">
            <ul class="pagination justify-content-center">
                <!-- Bouton Précédent -->
                <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $current_page - 1 ?>" aria-label="Précédent">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>

                <!-- Liens Numéros de Page -->
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

                <!-- Bouton Suivant -->
                <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $current_page + 1 ?>" aria-label="Suivant">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
    <!-- Fin Contrôles de Pagination -->

<?php endif; ?>

</main>
</div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>