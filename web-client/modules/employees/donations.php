<?php

require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

$pageData = displayEmployeeDonationsPage();
$donationsHistory = $pageData['donations'] ?? [];
$csrfToken = $pageData['csrf_token'] ?? '';


$pageTitle = "Faire un Don - Espace Salarié";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="employee-donations-page py-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h2">Faire un Don</h1>
                <p class="text-muted">Soutenez les associations partenaires par un don financier ou matériel.</p>
            </div>
        </div>

        <div class="row g-5">
            <!-- Formulaire de Don -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0 text-white">Nouveau Don</h5>
                    </div>
                    <div class="card-body">
                        <form id="donation-form" action="<?= WEBCLIENT_URL ?>/actions/submit_donation.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                            <div class="mb-3">
                                <label for="donation-type" class="form-label">Type de Don <span class="text-danger">*</span></label>
                                <select class="form-select" id="donation-type" name="type" required>
                                    <option value="" selected disabled>Choisir...</option>
                                    <?php foreach (DONATION_TYPES as $dtype): ?>
                                        <option value="<?= htmlspecialchars($dtype) ?>"><?= htmlspecialchars(ucfirst($dtype)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3" id="donation-amount-group">
                                <label for="donation-amount" class="form-label">Montant (€)</label>
                                <input type="number" class="form-control" id="donation-amount" name="montant" min="1" step="0.01" placeholder="Si don financier">
                            </div>

                            <div class="mb-3" id="donation-description-group">
                                <label for="donation-description" class="form-label">Description du matériel</label>
                                <textarea class="form-control" id="donation-description" name="description" rows="3" placeholder="Si don matériel"></textarea>
                                <small class="form-text text-muted">Décrivez précisément le matériel. Requis uniquement pour un don matériel.</small>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Soumettre mon Don</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Historique de mes Dons</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($donationsHistory)) : ?>
                            <p class="text-center text-muted">Vous n'avez pas encore fait de don.</p>
                        <?php else : ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Détails</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($donationsHistory as $don) : ?>
                                            <tr>
                                                <td><?= htmlspecialchars($don['date_don_formatee'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars(ucfirst($don['type'] ?? '?')) ?></td>
                                                <td>
                                                    <?php if ($don['type'] === 'financier') : ?>
                                                        <?= htmlspecialchars($don['montant_formate'] ?? 'N/A') ?> <!-- Assume 'financier' is DONATION_TYPES[0] -->
                                                    <?php else : ?>
                                                        <?= htmlspecialchars($don['description'] ?? 'N/A') ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $don['statut_badge'] ?? '' ?></td>
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
    </div>
</main>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../../templates/footer.php';
?>