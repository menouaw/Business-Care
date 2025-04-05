<?php

/**
 * Espace Entreprise - Visualisation d'une Facture
 *
 * Affiche les détails d'une facture spécifique.
 */

require_once __DIR__ . '/../../includes/init.php';

require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ENTREPRISE);

$entrepriseId = $_SESSION['user_entreprise'];

$invoiceId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$invoiceId) {
    flashMessage("Identifiant de facture manquant.", "warning");
    redirectTo('invoices.php');
}

$facture = getInvoiceDetailsForCompany($entrepriseId, $invoiceId);

if (!$facture) {
    redirectTo('invoices.php');
}

$pageTitle = "Détails Facture " . ($facture['numero_facture_complet']) . " - Espace Entreprise";

include_once __DIR__ . '/../../templates/header.php';

?>

<main class="container py-4">
    <nav aria-label="breadcrumb mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="invoices.php">Mes Factures</a></li>
            <li class="breadcrumb-item active" aria-current="page">Facture <?= $facture['numero_facture_complet'] ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Facture <?= $facture['numero_facture_complet'] ?></h1>
        <div>
            <!-- Bouton Payer (si applicable) -->
            <?php if ($facture['statut'] === 'en_attente' || $facture['statut'] === 'retard'): ?>
                <a href="#?id=<?= $facture['id'] ?>" class="btn btn-success me-2">
                    <i class="fas fa-credit-card"></i> Payer maintenant
                </a>
            <?php endif; ?>

        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row mb-4">
                <!-- Informations Entreprise Cliente -->
                <div class="col-md-6">
                    <h5 class="mb-3">Facturé à :</h5>
                    <p class="mb-1 fw-bold"><?= htmlspecialchars($facture['entreprise_nom']) ?></p>
                    <?php if (!empty($facture['entreprise_adresse'])): ?>
                        <p class="mb-1 text-muted">
                            <?= nl2br(htmlspecialchars($facture['entreprise_adresse'])) ?> <br>
                            <?= htmlspecialchars($facture['entreprise_code_postal']) ?> <?= htmlspecialchars($facture['entreprise_ville']) ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($facture['entreprise_siret'])): ?>
                        <p class="text-muted mb-0">SIRET : <?= htmlspecialchars($facture['entreprise_siret']) ?></p>
                    <?php endif; ?>
                </div>
                <!-- Informations Facture -->
                <div class="col-md-6 text-md-end">
                    <h5 class="mb-3">Détails Facture :</h5>
                    <p class="mb-1"><span class="fw-bold">Numéro :</span> <?= htmlspecialchars($facture['numero_facture_complet']) ?></p>
                    <p class="mb-1"><span class="fw-bold">Date d'émission :</span> <?= $facture['date_emission_formatee'] ?? 'N/A' ?></p>
                    <p class="mb-1"><span class="fw-bold">Date d'échéance :</span> <?= $facture['date_echeance_formatee'] ?? 'N/A' ?></p>
                    <p class="mb-0 mt-2">Statut : <?= $facture['statut_badge'] ?? getStatusBadge($facture['statut']) ?></p>
                </div>
            </div>

            <hr class="my-4">

            <!-- Lignes de la facture (TODO: à implémenter si les données sont disponibles) -->
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
                        <?php if (empty($facture['lignes'])): // Affichage simplifié si pas de lignes détaillées 
                        ?>
                            <tr>
                                <td>Prestations Business Care (Période concernée)</td>
                                <td class="text-end"><?= $facture['montant_ht_formate'] ?? 'N/A' ?></td>
                                <td class="text-end"><?= $facture['tva_formatee'] ?? 'N/A' ?></td>
                                <td class="text-end"><?= $facture['montant_total_formate'] ?? 'N/A' ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($facture['lignes'] as $ligne): ?>
                                <!-- Itérer sur les lignes réelles ici -->
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2"></td>
                            <th class="text-end">Sous-total HT :</th>
                            <td class="text-end"><?= $facture['montant_ht_formate'] ?? 'N/A' ?></td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                            <th class="text-end">TVA (<?= $facture['tva_formatee'] ?? 'N/A' ?>) :</th>
                            <?php
                            // Calcul simple de la TVA basée sur les totaux (peut être imprécis)
                            $tva_amount = ($facture['montant_total'] ?? 0) - ($facture['montant_ht'] ?? 0);
                            ?>
                            <td class="text-end"><?= formatMoney($tva_amount) ?></td>
                        </tr>
                        <tr class="table-light fw-bold">
                            <td colspan="2"></td>
                            <th class="text-end h5">Total TTC :</th>
                            <td class="text-end h5"><?= $facture['montant_total_formate'] ?? 'N/A' ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Notes ou Conditions -->
            <?php if (!empty($facture['conditions_paiement'])): ?>
                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2">Conditions de paiement :</h6>
                    <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($facture['conditions_paiement'])) ?></p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../../templates/footer.php';
?>