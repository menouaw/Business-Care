<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireRole(ROLE_SALARIE);

$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        $employee_id_for_log = $_SESSION['user_id'] ?? null;
        logSecurityEvent($employee_id_for_log, 'csrf_failure', '[SECURITY FAILURE] Tentative action settings avec jeton invalide ou manquant');
        flashMessage("Erreur de sécurité (jeton invalide).", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/employees/settings.php');
        exit;
    }

    if ($action === 'update_settings') {
        handleUpdateEmployeeSettings();
    } elseif ($action === 'change_password') {
        handlePasswordChangeRequest($_POST, $_SESSION['user_id']);
    }
    redirectTo(WEBCLIENT_URL . '/modules/employees/settings.php');
    exit;
}

$pageData = displayEmployeeSettings();
$employee = $pageData['employee'] ?? [];
$settings = $pageData['settings'] ?? [];

$pageTitle = "Mes Paramètres";

include_once __DIR__ . '/../../templates/header.php';

?>

<main class="employee-settings-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2"><?= $pageTitle ?></h1>
                <p class="text-muted">Gérez vos informations personnelles et vos préférences.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Profil & Préférences</h5>
            </div>
            <div class="card-body">
                <form action="<?= WEBCLIENT_URL ?>/modules/employees/settings.php" method="POST">
                    <input type="hidden" name="action" value="update_settings">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <h6>Informations Personnelles</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($employee['prenom'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($employee['nom'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($employee['telephone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($employee['email'] ?? '') ?>" disabled>
                                <a href="<?= WEBCLIENT_URL ?>/modules/employees/email-change-request.php" class="btn btn-outline-secondary">Demander modification</a>
                            </div>
                            <small class="text-muted">Pour des raisons de sécurité, la modification d'email nécessite une vérification spécifique.</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6>Préférences</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="langue" class="form-label">Langue d'affichage</label>
                            <select class="form-select" id="langue" name="langue">
                                <option value="fr" <?= (($settings['langue'] ?? 'fr') === 'fr') ? 'selected' : '' ?>>Français</option>
                                <option value="en" <?= (($settings['langue'] ?? 'fr') === 'en') ? 'selected' : '' ?>>English</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch pt-md-4">
                                <input class="form-check-input" type="checkbox" role="switch" id="notif_email" name="notif_email" value="1" <?= (($settings['notif_email'] ?? 1) == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notif_email">Recevoir les notifications par e-mail</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid d-md-block mt-3">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Enregistrer Profil & Préférences</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Changer le Mot de Passe</h5>
            </div>
            <div class="card-body">
                <form action="<?= WEBCLIENT_URL ?>/modules/employees/settings.php" method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="new_password" name="new_password"
                            pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                            required aria-describedby="passwordHelp">
                        <div id="passwordHelp" class="form-text">
                            Doit comporter au moins 8 caractères, dont une majuscule, une minuscule, un chiffre et un caractère spécial (@$!%*?&).
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid d-md-block">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-key me-1"></i> Changer le mot de passe</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>