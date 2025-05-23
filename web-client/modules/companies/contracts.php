<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies/contracts.php';

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
$usage_stats = []; 

if ($action === 'view' && $contract_id) {
    $contract = getContractDetails($contract_id, $entreprise_id);
    if (!$contract) {
        flashMessage("Contrat non trouvé ou accès refusé.", "warning");
        redirectTo(WEBCLIENT_URL . '/modules/companies/contracts.php');
        exit;
    }
    $pageTitle = "Détails du Contrat #" . $contract['id'];
    
    $usage_stats = getContractUsageStats($contract_id, $entreprise_id);
} else {
    $action = 'list';
    $pageTitle = "Mes Contrats";

    $items_per_page = DEFAULT_ITEMS_PER_PAGE;
    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    $contractsData = getCompanyContracts($entreprise_id, $current_page, $items_per_page);
    $contracts = $contractsData['contracts'];
    $total_contracts = $contractsData['total_count'];
    $total_pages = ceil($total_contracts / $items_per_page);
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">

            <?php echo displayFlashMessages(); ?>

            <?php if ($action === 'view' && $contract): ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
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

                
                <div class="card mt-4">
                    <div class="card-header">
                        Statistiques d'Utilisation (Anonymisées)
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center ps-0">
                                Consultations médicales/sensibles utilisées :
                                <span class="badge bg-primary rounded-pill">
                                    <?= htmlspecialchars($usage_stats['medical_consultations_count'] ?? 0) ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center ps-0">
                                Autres prestations utilisées (ateliers, webinars...) :
                                <span class="badge bg-secondary rounded-pill">
                                    <?= htmlspecialchars($usage_stats['other_prestations_count'] ?? 0) ?>
                                </span>
                            </li>
                            
                        </ul>
                        <div class="form-text mt-2">Ces statistiques sont agrégées et ne contiennent aucune information nominative sur les salariés ayant utilisé les services.</div>
                    </div>
                </div>
                

            <?php else: ?>
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
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php

                $paginationData = [
                    'currentPage' => $current_page,
                    'totalPages' => $total_pages

                ];

                $urlPattern = WEBCLIENT_URL . '/modules/companies/contracts.php?page={page}';


                echo renderPagination($paginationData, $urlPattern);
                ?>

            <?php endif; ?>

        </main>
    </div>
</div>

