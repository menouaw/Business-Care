<?php

/**
 * Espace Entreprise - Gestion des Factures
 *
 * Permet aux entreprises de visualiser leurs factures.
 */

require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ENTREPRISE);

$entrepriseId = $_SESSION['user_entreprise'];

// Récupérer les factures "en cours" (Limite haute, pas de pagination par section pour l'instant)
$currentStatus = ['en_attente', 'retard', 'impayee'];
$currentInvoicesData = getCompanyInvoices($entrepriseId, 1, 999, null, null, $currentStatus);
$currentFactures = $currentInvoicesData['invoices'];

// Récupérer les factures "historique" (Limite haute, pas de pagination par section pour l'instant)
$historicalStatus = ['payee', 'annulee'];
$historicalInvoicesData = getCompanyInvoices($entrepriseId, 1, 999, null, null, $historicalStatus);
$historicalFactures = $historicalInvoicesData['invoices'];

$pageTitle = "Mes Factures - Espace Entreprise";

// Inclure l'en-tête
include_once __DIR__ . '/../../templates/header.php';
?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Mes Factures</h1>
        <a href="index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de bord
        </a>
    </div>

    <!-- Section Factures en Cours -->
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
                                        <a href="view-invoice.php?id=<?= $facture['id'] ?>" class="fw-bold">
                                            <?= $facture['numero_facture'] ?? ('INV-' . str_pad($facture['id'], 6, '0', STR_PAD_LEFT)) ?>
                                        </a>
                                    </td>
                                    <td><?= $facture['date_emission_formatee'] ?? 'N/A' ?></td>
                                    <td><?= $facture['date_echeance_formatee'] ?? 'N/A' ?></td>
                                    <td class="text-end fw-bold"><?= $facture['montant_total_formate'] ?? 'N/A' ?></td>
                                    <td class="text-center"><?= $facture['statut_badge'] ?? getStatusBadge($facture['statut']) ?></td>
                                    <td class="text-center">
                                        <a href="view-invoice.php?id=<?= $facture['id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir la facture">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php /* // Bouton Payer (commenté) pour les factures en cours
                                        <a href="pay-invoice.php?id=<?= $facture['id'] ?>" class="btn btn-sm btn-success ms-1" title="Payer la facture">
                                            <i class="fas fa-credit-card"></i> Payer
                                        </a>
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

    <!-- Section Historique des Factures -->
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
                                        <a href="view-invoice.php?id=<?= $facture['id'] ?>" class="fw-bold">
                                            <?= $facture['numero_facture'] ?? ('INV-' . str_pad($facture['id'], 6, '0', STR_PAD_LEFT)) ?>
                                        </a>
                                    </td>
                                    <td><?= $facture['date_emission_formatee'] ?? 'N/A' ?></td>
                                    <td><?= $facture['date_echeance_formatee'] ?? 'N/A' ?></td>
                                    <td class="text-end fw-bold"><?= $facture['montant_total_formate'] ?? 'N/A' ?></td>
                                    <td class="text-center"><?= $facture['statut_badge'] ?? getStatusBadge($facture['statut']) ?></td>
                                    <td class="text-center">
                                        <a href="view-invoice.php?id=<?= $facture['id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir la facture">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        {/* Pas de bouton Payer pour l'historique */}
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>