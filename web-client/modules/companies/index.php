<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ENTREPRISE);

$entrepriseId = $_SESSION['user_entreprise'];
$entreprise = getCompanyDetails($entrepriseId);

error_log("[DEBUG index.php] ID Entreprise utilisé pour contrats: " . $entrepriseId);

$contrats = getCompanyContracts($entrepriseId, STATUS_ACTIVE);

error_log("[DEBUG index.php] Contenu de \$contrats: " . print_r($contrats, true));

$factures = getCompanyInvoices($entrepriseId);

$employeesData = getCompanyEmployees($entrepriseId, 1, 1);
$totalEmployes = $employeesData['pagination']['total'] ?? 0;

$activites = getCompanyRecentActivity($entrepriseId, DASHBOARD_ITEMS_LIMIT);

$pageTitle = "Tableau de bord - Espace Entreprise";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="dashboard-page py-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h2">Tableau de bord</h1>
                <p class="text-muted">Bienvenue dans votre espace entreprise, <?= $_SESSION['user_name'] ?></p>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Salariés</h6>
                                <h2 class="card-title mb-0"><?= $totalEmployes ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="employees.php" class="btn btn-sm btn-outline-primary">Voir tous</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-success bg-opacity-10 text-success rounded p-3 me-3">
                                <i class="fas fa-file-contract fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Contrats </h6>
                                <h2 class="card-title mb-0"><?= count($contrats['contracts'] ?? []) ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="contracts.php" class="btn btn-sm btn-outline-success">Voir tous</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-info bg-opacity-10 text-info rounded p-3 me-3">
                                <i class="fas fa-file-invoice-dollar fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Factures</h6>
                                <h2 class="card-title mb-0"><?= count($factures['invoices']) ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="invoices.php" class="btn btn-sm btn-outline-info">Voir toutes</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                                <i class="fas fa-star fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Abonnement Actuel</h6>
                                <?php
                                $abonnementActuel = 'Aucun';
                                if (!empty($contrats['contracts'][0]) && isset($contrats['contracts'][0]['service_nom'])) {
                                    $abonnementActuel = $contrats['contracts'][0]['service_nom'];
                                }
                                ?>
                                <p class="card-text fw-bold mb-0"><?= htmlspecialchars($abonnementActuel) ?></p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="contracts.php" class="btn btn-sm btn-outline-warning">Détails</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Contrats </h5>
                        <a href="contracts.php" class="btn btn-sm btn-outline-primary">Tous les contrats</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($contrats['contracts'])): ?>
                            <p class="text-center text-muted my-5">Aucun contrat actif</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Date début</th>
                                            <th>Date fin</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contrats['contracts'] as $contrat): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($contrat['prestation_nom'] ?? 'N/A') ?></td>
                                                <td><?= formatDate($contrat['date_debut'], 'd/m/Y') ?></td>
                                                <td><?= $contrat['date_fin'] ? formatDate($contrat['date_fin'], 'd/m/Y') : 'Indéterminée' ?></td>
                                                <td><?= getStatusBadge($contrat['statut']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Dernières factures</h5>
                        <a href="invoices.php" class="btn btn-sm btn-outline-primary">Toutes les factures</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($factures['invoices'])): ?>
                            <p class="text-center text-muted my-5">Aucune facture disponible</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Numéro</th>
                                            <th>Date</th>
                                            <th>Montant</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($factures['invoices'], 0, DASHBOARD_ITEMS_LIMIT) as $facture): ?>
                                            <tr>
                                                <td><?= $facture['reference'] ?? $facture['numero_facture'] ?? 'N/A' ?></td>
                                                <td><?= $facture['date_emission_formatee'] ?? formatDate($facture['date_emission'], 'd/m/Y') ?></td>
                                                <td><?= $facture['montant_ttc_formate'] ?? formatMoney($facture['montant_total']) ?></td>
                                                <td><?= $facture['statut_badge'] ?? getStatusBadge($facture['statut']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Actions rapides</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <a href="quotes.php" class="btn btn-primary d-block py-3">
                                    <i class="fas fa-file-invoice me-2"></i>Demander un devis
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="employees.php" class="btn btn-success d-block py-3">
                                    <i class="fas fa-user-plus me-2"></i>Ajouter des salariés
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="contact.php" class="btn btn-info d-block py-3">
                                    <i class="fas fa-headset me-2"></i>Contacter le support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php

include_once __DIR__ . '/../../templates/footer.php';
?>