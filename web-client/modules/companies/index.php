<?php

/**
 * Tableau de Bord - Espace Entreprise (Module Entreprise)
 *
 * Page d'accueil de l'espace entreprise. Affiche un résumé des informations
 * clés et des actions rapides pour l'entreprise connectée.
 *
 * Contenu Affiché :
 * - Cartes récapitulatives (Salariés, Contrats Actifs, Prestations, Factures).
 * - Liste des contrats actifs récents.
 * - Liste des dernières factures.
 * - Boutons d'actions rapides (Demander devis, Ajouter salarié, Contacter support).
 *
 * Récupère les données via les fonctions du module `companies.php`.
 * Accès restreint aux utilisateurs avec le rôle ROLE_ENTREPRISE.
 */

// Inclure les fonctions spécifiques au module entreprises
require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

// vérifier que l'utilisateur est connecté et a le rôle entreprise
requireRole(ROLE_ENTREPRISE);

// récupérer les informations de l'entreprise
$entrepriseId = $_SESSION['user_entreprise'];
$entreprise = getCompanyDetails($entrepriseId);

// DEBUG: Vérifier l'ID entreprise utilisé
error_log("[DEBUG index.php] ID Entreprise utilisé pour contrats: " . $entrepriseId);

// récupérer les contrats actifs
$contrats = getCompanyContracts($entrepriseId, 'actif');

// DEBUG: Vérifier le retour de getCompanyContracts
error_log("[DEBUG index.php] Contenu de \$contrats: " . print_r($contrats, true));

// récupérer les dernières factures
$factures = getCompanyInvoices($entrepriseId);

// récupérer les employés rattachés
$employeesData = getCompanyEmployees($entrepriseId, 1, 100);
$employes = $employeesData['employees'];

// nombre total d'employés
$totalEmployes = count($employes);

// récupérer les activités récentes
$activites = getCompanyRecentActivity($entrepriseId, 5);

// définir le titre de la page
$pageTitle = "Tableau de bord - Espace Entreprise";

// inclure l'en-tête
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

        <!-- cartes d'information -->
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
                            <a href="liste-salaries.php" class="btn btn-sm btn-outline-primary">Voir tous</a>
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
                            <div class="icon-box bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                                <i class="fas fa-calendar-check fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Prestations</h6>
                                <h2 class="card-title mb-0">--</h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="prestations.php" class="btn btn-sm btn-outline-warning">Réserver</a>
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
        </div>

        <div class="row g-4">
            <!-- contrats actifs -->
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
                                                <td><?= ucfirst($contrat['type_contrat']) ?></td>
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

            <!-- dernières factures -->
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
                                        <?php foreach (array_slice($factures['invoices'], 0, 5) as $facture): ?>
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

        <!-- actions rapides -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Actions rapides</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <a href="devis.php" class="btn btn-primary d-block py-3">
                                    <i class="fas fa-file-invoice me-2"></i>Demander un devis
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="add-employee.php" class="btn btn-success d-block py-3">
                                    <i class="fas fa-user-plus me-2"></i>Ajouter des salariés
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="contact-support.php" class="btn btn-info d-block py-3">
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
// inclure le pied de page
include_once __DIR__ . '/../../templates/footer.php';
?>