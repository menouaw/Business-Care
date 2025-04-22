<?php

require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

session_status() === PHP_SESSION_ACTIVE || session_start();
$current_employee_id = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleDonationSubmission($_POST, $current_employee_id);

    $redirectUrl = WEBCLIENT_URL . '/modules/employees/donations.php';
    redirectTo($redirectUrl);
    exit;
}


$pageData = displayEmployeeDonationsPage();
$donationsHistory = $pageData['donations'] ?? [];
$csrfToken = $pageData['csrf_token'] ?? '';
$associations = $pageData['associations'] ?? [];

$donationTypes = defined('DONATION_TYPES') ? DONATION_TYPES : ['financier', 'materiel'];

$pageTitle = "Faire un Don - Espace Salarié";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="employee-donations-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2">Faire un Don</h1>
                <p class="text-muted">Soutenez les associations partenaires par un don financier ou matériel.</p>
            </div>
            <div class="col-auto">
                <a href="javascript:history.back()" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <div class="row g-5">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0 text-white">Nouveau Don</h5>
                    </div>
                    <div class="card-body">
                        <form id="donation-form" action="<?= WEBCLIENT_URL ?>/modules/employees/donations.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">

                            <div class="mb-3">
                                <label for="association-id" class="form-label">Association <span class="text-danger">*</span></label>
                                <select class="form-select" id="association-id" name="association_id" required>
                                    <option value="" selected disabled>Choisir une association...</option>
                                    <?php if (empty($associations)):
                                    ?>
                                        <option value="" disabled>Aucune association disponible</option>
                                    <?php else:
                                    ?>
                                        <?php foreach ($associations as $assoc):
                                        ?>
                                            <option value="<?= htmlspecialchars($assoc['id']) ?>"><?= htmlspecialchars($assoc['nom']) ?></option>
                                        <?php endforeach;
                                        ?>
                                    <?php endif;
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="donation-type" class="form-label">Type de Don <span class="text-danger">*</span></label>
                                <select class="form-select" id="donation-type" name="type" required>
                                    <option value="" selected disabled>Choisir...</option>
                                    <?php foreach ($donationTypes as $dtype):
                                    ?>
                                        <option value="<?= htmlspecialchars($dtype) ?>"><?= htmlspecialchars(ucfirst($dtype)) ?></option>
                                    <?php endforeach;
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3 d-none" id="donation-amount-group">
                                <label for="donation-amount" class="form-label">Montant (€) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="donation-amount" name="montant" min="1" step="0.01" placeholder="Ex: 50.00">
                            </div>

                            <div class="mb-3 d-none" id="donation-description-group">
                                <label for="donation-description" class="form-label">Description du matériel <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="donation-description" name="description" rows="3" placeholder="Ex: Ordinateur portable Dell Latitude 7400"></textarea>
                                <small class="form-text text-muted">Décrivez précisément le matériel.</small>
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
                                            <th>Association</th>
                                            <th>Type</th>
                                            <th>Détails</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($donationsHistory as $don) : ?>
                                            <tr>
                                                <td><?= htmlspecialchars($don['date_don_formatee'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($don['association_nom'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars(ucfirst($don['type'] ?? '?')) ?></td>
                                                <td>
                                                    <?php if ($don['type'] === 'financier') : ?>
                                                        <?= htmlspecialchars($don['montant_formate'] ?? 'N/A') ?>
                                                    <?php else : ?>
                                                        <?= nl2br(htmlspecialchars($don['description'] ?? 'N/A')) ?>
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

<!-- JavaScript for conditional form fields -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const donationTypeSelect = document.getElementById('donation-type');
        const amountGroup = document.getElementById('donation-amount-group');
        const amountInput = document.getElementById('donation-amount');
        const descriptionGroup = document.getElementById('donation-description-group');
        const descriptionTextarea = document.getElementById('donation-description');

        function toggleFields() {
            const selectedType = donationTypeSelect.value;

            if (selectedType === 'financier') {
                amountGroup.classList.remove('d-none');
                amountInput.required = true;
                descriptionGroup.classList.add('d-none');
                descriptionTextarea.required = false;
            } else if (selectedType === 'materiel') {
                amountGroup.classList.add('d-none');
                amountInput.required = false;
                descriptionGroup.classList.remove('d-none');
                descriptionTextarea.required = true;
            } else {
                amountGroup.classList.add('d-none');
                amountInput.required = false;
                descriptionGroup.classList.add('d-none');
                descriptionTextarea.required = false;
            }
        }

        toggleFields();

        donationTypeSelect.addEventListener('change', toggleFields);
    });
</script>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>