<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

$page_title = "Dons et engagements";
$page_description = "Effectuez des dons financiers ou matériels et participez aux actions solidaires";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != ROLE_SALARIE) {
    // Rediriger vers la page de connexion si non connecté ou non salarié
    if (function_exists('flashMessage')) {
        flashMessage("Vous devez être connecté en tant que salarié pour accéder à cette page", "warning");
    }
    header('Location: ' . ROOT_URL . '/common/connexion/');
    exit;
}

$employee_id = $_SESSION['user_id'];

$selected_donation_type = isset($_POST['donation_type']) ? $_POST['donation_type'] : '';

$validation_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_donation'])) {
    if (empty($_POST['donation_type'])) {
        $validation_errors[] = "Veuillez sélectionner un type de don";
    } else {
        if ($_POST['donation_type'] == 'financier' && (empty($_POST['amount']) || floatval($_POST['amount']) <= 0)) {
            $validation_errors[] = "Veuillez saisir un montant valide pour votre don financier";
        }

        if ($_POST['donation_type'] == 'materiel' && empty($_POST['description'])) {
            $validation_errors[] = "Veuillez fournir une description pour votre don";
        }
    }

    if (empty($validation_errors)) {
        $donation_data = [
            'type' => $_POST['donation_type'] ?? '',
            'montant' => $_POST['amount'] ?? null,
            'description' => $_POST['description'] ?? '',
            'association_id' => $_POST['association_id'] ?? null
        ];

        // Traitement du don via la fonction du module employees.php
        $result = manageEmployeeDonations($employee_id, $donation_data);

        // Redirection pour éviter le problème de re-soumission du formulaire
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$donation_history = getDonationHistory($employee_id);

$associations = getAssociations();

include_once __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-hand-holding-heart me-2"></i>
                        <?= $page_title ?>
                    </h4>
                    <p class="mb-0 small"><?= $page_description ?></p>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <a href="../../modules/employees/index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
                        </a>
                    </div>

                    <?php
                    if (function_exists('displayFlashMessages')) {
                        displayFlashMessages();
                    }

                    if (!empty($validation_errors)) {
                        echo '<div class="alert alert-danger">';
                        echo '<h6><i class="fas fa-exclamation-triangle me-2"></i>Veuillez corriger les erreurs suivantes :</h6>';
                        echo '<ul class="mb-0">';
                        foreach ($validation_errors as $error) {
                            echo '<li>' . htmlspecialchars($error) . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                    ?>

                    <div class="row">
                        <!-- Formulaire de don -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Faire un don</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <div class="mb-3">
                                            <label for="donation_type" class="form-label">Type de don</label>
                                            <select class="form-select" id="donation_type" name="donation_type" required>
                                                <option value="">Sélectionner le type de don</option>
                                                <option value="financier" <?= $selected_donation_type === 'financier' ? 'selected' : '' ?>>Don financier</option>
                                                <option value="materiel" <?= $selected_donation_type === 'materiel' ? 'selected' : '' ?>>Don matériel</option>
                                            </select>
                                            <div class="form-text">Pour changer le type de don, sélectionnez une option et cliquez sur "Afficher les champs"</div>
                                        </div>

                                        <div class="d-grid mb-3">
                                            <button type="submit" name="show_fields" class="btn btn-outline-secondary">Afficher les champs</button>
                                        </div>

                                        <?php if (!empty($selected_donation_type)): ?>

                                            <div class="mb-3">
                                                <label for="association_id" class="form-label">Association bénéficiaire</label>
                                                <select class="form-select" id="association_id" name="association_id">
                                                    <option value="">Sélectionner une association</option>
                                                    <?php foreach ($associations as $association): ?>
                                                        <option value="<?= $association['id'] ?>" <?= (isset($_POST['association_id']) && $_POST['association_id'] == $association['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($association['nom']) ?> - <?= htmlspecialchars($association['domaine']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <?php if ($selected_donation_type === 'financier'): ?>
                                                <div class="mb-3">
                                                    <label for="amount" class="form-label">Montant (€)</label>
                                                    <input type="number" class="form-control" id="amount" name="amount" min="1" step="1" value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>">
                                                </div>
                                            <?php endif; ?>

                                            <div class="mb-3">
                                                <label for="description" class="form-label">Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                            </div>

                                            <button type="submit" name="submit_donation" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-2"></i>Envoyer mon don
                                            </button>

                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Informations importantes</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Don financier</h6>
                                        <p class="small mb-0">Les dons financiers sont déductibles de vos impôts à hauteur de 66% dans la limite de 20% de votre revenu imposable. Un reçu fiscal vous sera automatiquement envoyé.</p>
                                    </div>

                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Don matériel</h6>
                                        <p class="small mb-0">Pour les dons matériels (équipements informatiques, mobilier...), décrivez précisément les articles. Un responsable vous contactera pour organiser la collecte.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Historique des dons -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Historique de vos dons</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($donation_history)): ?>
                                        <div class="alert alert-info">
                                            <p class="mb-0">Vous n'avez pas encore effectué de don. N'hésitez pas à contribuer aux causes qui vous tiennent à cœur !</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Type</th>
                                                        <th>Montant/Description</th>
                                                        <th>Statut</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($donation_history as $donation): ?>
                                                        <tr>
                                                            <td><?= formatDate($donation['date_don']) ?></td>
                                                            <td>
                                                                <?php if ($donation['type'] == 'financier'): ?>
                                                                    <span class="badge bg-success">Financier</span>
                                                                <?php elseif ($donation['type'] == 'materiel'): ?>
                                                                    <span class="badge bg-primary">Matériel</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Autre</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($donation['type'] == 'financier'): ?>
                                                                    <?= formatMoney($donation['montant']) ?>
                                                                <?php else: ?>
                                                                    <?= nl2br(htmlspecialchars($donation['description'])) ?>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($donation['statut'] == 'en_attente'): ?>
                                                                    <span class="badge bg-warning text-dark">En attente</span>
                                                                <?php elseif ($donation['statut'] == 'valide'): ?>
                                                                    <span class="badge bg-success">Validé</span>
                                                                <?php elseif ($donation['statut'] == 'refuse'): ?>
                                                                    <span class="badge bg-danger">Refusé</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Traitement en cours</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Associations partenaires -->
                            <div class="card mt-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Nos associations partenaires</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach (array_slice($associations, 0, 4) as $association): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6 class="card-title"><?= htmlspecialchars($association['nom']) ?></h6>
                                                        <p class="badge bg-secondary mb-2"><?= htmlspecialchars($association['domaine']) ?></p>
                                                        <p class="card-text small"><?= htmlspecialchars(substr($association['description'], 0, 100)) ?>...</p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if (count($associations) > 4): ?>
                                        <div class="text-center mt-3">
                                            <a href="?show_all=1" class="btn btn-sm btn-outline-primary">
                                                Voir plus d'associations
                                            </a>
                                        </div>

                                        <?php if (isset($_GET['show_all'])): ?>
                                            <div class="row mt-3">
                                                <?php foreach (array_slice($associations, 4) as $association): ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card">
                                                            <div class="card-body">
                                                                <h6 class="card-title"><?= htmlspecialchars($association['nom']) ?></h6>
                                                                <p class="badge bg-secondary mb-2"><?= htmlspecialchars($association['domaine']) ?></p>
                                                                <p class="card-text small"><?= htmlspecialchars(substr($association['description'], 0, 100)) ?>...</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../../templates/footer.php'; ?>