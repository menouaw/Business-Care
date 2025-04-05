<?php

/**
 * Espace Entreprise - Gestion des Factures (Module Entreprise)
 *
 * Ce fichier permet aux entreprises de consulter leurs factures.
 * Il gère deux vues principales basées sur le paramètre GET 'action':
 *
 * - 'list' (défaut): Affiche deux listes de factures :
 *      - Factures en cours (statuts: en_attente, retard, impayee).
 *      - Historique des factures (statuts: payee, annulee).
 * - 'view': Affiche les détails d'une facture spécifique (ID requis via GET 'id').
 *      - Inclut les informations de l'entreprise, les détails de la facture,
 *        les lignes (si disponibles), le total et les conditions de paiement.
 *      - Propose un bouton "Payer maintenant" pour les factures éligibles.
 *
 * Récupère les données via les fonctions `getCompanyInvoices` et `getInvoiceDetailsForCompany`.
 * Accès restreint aux utilisateurs avec le rôle ROLE_ENTREPRISE.
 */

require_once __DIR__ . '/../../includes/init.php'; // Functions, config, etc.
require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ENTREPRISE);

$entrepriseId = $_SESSION['user_entreprise'];

// Determine the requested action (list or view)
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'list'; // 'list' by default
$invoiceId = null;
if ($action === 'view' && isset($_GET['id'])) {
    $invoiceId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
}

// Initializations
$invoiceToView = null;
$currentFactures = [];
$historicalFactures = [];

// --- Data Preparation ---

if ($action === 'view' && $invoiceId) {
    // Fetch details for a specific invoice
    $invoiceToView = getInvoiceDetailsForCompany($entrepriseId, $invoiceId);
    if (!$invoiceToView) {
        // getInvoiceDetailsForCompany already sets a flash message if needed
        redirectTo('invoices.php');
    }
    $pageTitle = "Détails Facture " . ($invoiceToView['numero_facture_complet'] ?? $invoiceId) . " - Espace Entreprise";
} else { // Default action: list
    // Fetch lists of invoices
    $currentStatus = ['en_attente', 'retard', 'impayee'];
    $currentInvoicesData = getCompanyInvoices($entrepriseId, 1, 999, null, null, $currentStatus);
    $currentFactures = $currentInvoicesData['invoices'] ?? []; // Use ?? for safety

    $historicalStatus = ['payee', 'annulee'];
    $historicalInvoicesData = getCompanyInvoices($entrepriseId, 1, 999, null, null, $historicalStatus);
    $historicalFactures = $historicalInvoicesData['invoices'] ?? []; // Use ?? for safety

    $pageTitle = "Mes Factures - Espace Entreprise";
}


include_once __DIR__ . '/../../templates/header.php';
?>

<main class="container py-4">

    <?php
    // --- Conditional Display ---

    if ($action === 'view' && $invoiceToView):
        // --- Invoice Details View ---
    ?>
        <nav aria-label="breadcrumb mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                <li class="breadcrumb-item"><a href="invoices.php">Mes Factures</a></li>
                <li class="breadcrumb-item active" aria-current="page">Facture <?= htmlspecialchars($invoiceToView['numero_facture_complet'] ?? $invoiceId) ?></li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Facture <?= htmlspecialchars($invoiceToView['numero_facture_complet'] ?? $invoiceId) ?></h1>
            <div>
                <!-- Pay Button (if applicable) -->
                <?php if (($invoiceToView['statut'] === 'en_attente' || $invoiceToView['statut'] === 'retard') && isset($invoiceToView['id'])): ?>
                    <a href="#?id=<?= $invoiceToView['id'] ?>" class="btn btn-success me-2">
                        <i class="fas fa-credit-card"></i> Payer maintenant
                    </a>
                <?php endif; ?>
                <a href="invoices.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                </a>
            </div>
        </div>

        <?php displayFlashMessages(); ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row mb-4">
                    <!-- Client Company Info -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Facturé à :</h5>
                        <p class="mb-1 fw-bold"><?= htmlspecialchars($invoiceToView['entreprise_nom'] ?? 'N/A') ?></p>
                        <?php if (!empty($invoiceToView['entreprise_adresse'])): ?>
                            <p class="mb-1 text-muted">
                                <?= nl2br(htmlspecialchars($invoiceToView['entreprise_adresse'])) ?> <br>
                                <?= htmlspecialchars($invoiceToView['entreprise_code_postal'] ?? '') ?> <?= htmlspecialchars($invoiceToView['entreprise_ville'] ?? '') ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($invoiceToView['entreprise_siret'])): ?>
                            <p class="text-muted mb-0">SIRET : <?= htmlspecialchars($invoiceToView['entreprise_siret']) ?></p>
                        <?php endif; ?>
                    </div>
                    <!-- Invoice Details -->
                    <div class="col-md-6 text-md-end">
                        <h5 class="mb-3">Détails Facture :</h5>
                        <p class="mb-1"><span class="fw-bold">Numéro :</span> <?= htmlspecialchars($invoiceToView['numero_facture_complet'] ?? 'N/A') ?></p>
                        <p class="mb-1"><span class="fw-bold">Date d'émission :</span> <?= $invoiceToView['date_emission_formatee'] ?? 'N/A' ?></p>
                        <p class="mb-1"><span class="fw-bold">Date d'échéance :</span> <?= $invoiceToView['date_echeance_formatee'] ?? 'N/A' ?></p>
                        <p class="mb-0 mt-2">Statut : <?= $invoiceToView['statut_badge'] ?? getStatusBadge($invoiceToView['statut'] ?? '') ?></p>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Invoice Lines -->
                <h5 class="mb-3">Résumé :</h5>
                <div class="table-responsive mb-4">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Montant HT</th>
                                <th class="text-end">TVA</th>
                                <th class="text-end">Montant TTC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoiceToView['lignes'])): // Simplified display if no detailed lines 
                            ?>
                                <tr>
                                    <td>Prestations Business Care (Période concernée)</td>
                                    <td class="text-end"><?= $invoiceToView['montant_ht_formate'] ?? 'N/A' ?></td>
                                    <td class="text-end"><?= $invoiceToView['tva_formatee'] ?? 'N/A' ?></td>
                                    <td class="text-end"><?= $invoiceToView['montant_total_formate'] ?? 'N/A' ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoiceToView['lignes'] as $ligne): ?>
                                    <!-- TODO: Iterate over actual lines here if data structure allows -->
                                    <tr>
                                        <td><?= htmlspecialchars($ligne['description'] ?? 'Ligne de facture') ?></td>
                                        <td class="text-end"><?= formatMoney($ligne['montant_ht'] ?? 0) ?></td>
                                        <td class="text-end"><?= formatMoney($ligne['tva'] ?? 0) ?></td>
                                        <td class="text-end"><?= formatMoney($ligne['montant_total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2"></td>
                                <th class="text-end">Sous-total HT :</th>
                                <td class="text-end"><?= $invoiceToView['montant_ht_formate'] ?? 'N/A' ?></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                                <th class="text-end">TVA (<?= $invoiceToView['tva_pourcentage_formate'] ?? 'N/A' ?>) :</th>
                                <?php
                                // Simple VAT calculation based on totals (might be imprecise if line details exist but aren't used)
                                $tva_amount = ($invoiceToView['montant_total'] ?? 0) - ($invoiceToView['montant_ht'] ?? 0);
                                ?>
                                <td class="text-end"><?= formatMoney($tva_amount) ?></td>
                            </tr>
                            <tr class="table-light fw-bold">
                                <td colspan="2"></td>
                                <th class="text-end h5">Total TTC :</th>
                                <td class="text-end h5"><?= $invoiceToView['montant_total_formate'] ?? 'N/A' ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Notes or Conditions -->
                <?php if (!empty($invoiceToView['conditions_paiement'])): ?>
                    <div class="bg-light p-3 rounded">
                        <h6 class="mb-2">Conditions de paiement :</h6>
                        <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($invoiceToView['conditions_paiement'])) ?></p>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    <?php
    else: // ($action === 'list')
        // --- Invoice List View ---
    ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Mes Factures</h1>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de bord
            </a>
        </div>

        <?php displayFlashMessages(); ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Factures en cours</h5>
            </div>
            <div class="card-body">
                <?php if (empty($currentFactures)): ?>
                    <p class="text-center text-muted my-5">Aucune facture en cours.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Numéro</th>
                                    <th>Date d'émission</th>
                                    <th>Date d'échéance</th>
                                    <th class="text-end">Montant TTC</th>
                                    <th class="text-center">Statut</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($currentFactures as $facture): ?>
                                    <tr>
                                        <td>
                                            <a href="invoices.php?action=view&id=<?= $facture['id'] ?>" class="fw-bold">
                                                <?= htmlspecialchars($facture['numero_facture_complet'] ?? ('INV-' . str_pad($facture['id'], 6, '0', STR_PAD_LEFT))) ?>
                                            </a>
                                        </td>
                                        <td><?= $facture['date_emission_formatee'] ?? 'N/A' ?></td>
                                        <td><?= $facture['date_echeance_formatee'] ?? 'N/A' ?></td>
                                        <td class="text-end fw-bold"><?= $facture['montant_total_formate'] ?? 'N/A' ?></td>
                                        <td class="text-center"><?= $facture['statut_badge'] ?? getStatusBadge($facture['statut'] ?? '') ?></td>
                                        <td class="text-center">
                                            <a href="invoices.php?action=view&id=<?= $facture['id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir la facture">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php /* // Pay button (commented out) for current invoices
                                        if (($facture['statut'] === 'en_attente' || $facture['statut'] === 'retard') && isset($facture['id'])) {
                                            echo '<a href="#?id='.$facture['id'].'" class="btn btn-sm btn-success ms-1" title="Payer la facture"><i class="fas fa-credit-card"></i></a>';
                                        }
                                        */ ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Historique des factures</h5>
            </div>
            <div class="card-body">
                <?php if (empty($historicalFactures)): ?>
                    <p class="text-center text-muted my-5">Aucune facture dans l'historique.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Numéro</th>
                                    <th>Date d'émission</th>
                                    <th>Date d'échéance</th>
                                    <th class="text-end">Montant TTC</th>
                                    <th class="text-center">Statut</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historicalFactures as $facture): ?>
                                    <tr>
                                        <td>
                                            <a href="invoices.php?action=view&id=<?= $facture['id'] ?>" class="fw-bold">
                                                <?= htmlspecialchars($facture['numero_facture_complet'] ?? ('INV-' . str_pad($facture['id'], 6, '0', STR_PAD_LEFT))) ?>
                                            </a>
                                        </td>
                                        <td><?= $facture['date_emission_formatee'] ?? 'N/A' ?></td>
                                        <td><?= $facture['date_echeance_formatee'] ?? 'N/A' ?></td>
                                        <td class="text-end fw-bold"><?= $facture['montant_total_formate'] ?? 'N/A' ?></td>
                                        <td class="text-center"><?= $facture['statut_badge'] ?? getStatusBadge($facture['statut'] ?? '') ?></td>
                                        <td class="text-center">
                                            <a href="invoices.php?action=view&id=<?= $facture['id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir la facture">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            {/* No Pay button for history */}
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; // End conditional display (view vs list) 
    ?>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>