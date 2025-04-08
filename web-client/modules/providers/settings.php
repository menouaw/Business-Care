<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers.php';

requireRole(ROLE_PRESTATAIRE);
$provider_id = $_SESSION['user_id'];

$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        logSecurityEvent($provider_id, 'csrf_failure', '[SECURITY FAILURE] Tentative action settings prestataire avec jeton invalide ou manquant');
        flashMessage("Erreur de sécurité (jeton invalide).", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/providers/settings.php');
        exit;
    }

    if ($action === 'update_settings') {
        handleUpdateProviderSettings();
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        handleChangePassword($provider_id, $current_password, $new_password, $confirm_password);
    } else {
        flashMessage("Action non reconnue.", "warning");
    }

    redirectTo(WEBCLIENT_URL . '/modules/providers/settings.php');
    exit;
}

$pageData = displayProviderSettingsPageData();

$provider = $pageData['provider'] ?? [];
$settings = $pageData['settings'] ?? [];
$csrfToken = generateToken();

$pageTitle = "Mes Paramètres - Espace Prestataire";

include_once __DIR__ . '/../../templates/header.php';

?>

<main class="provider-settings-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2"><?= $pageTitle ?></h1>
                <p class="text-muted">Gérez vos informations personnelles, vos préférences et votre mot de passe.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/providers/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour 
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <?php if (empty($provider)):
        ?>
            <div class="alert alert-danger" role="alert">
                Impossible de charger les informations du compte. Veuillez réessayer ou contacter le support.
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Profil & Préférences</h5>
                </div>
                <div class="card-body">
                    <form action="<?= WEBCLIENT_URL ?>/modules/providers/settings.php" method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <h6>Informations Personnelles</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($provider['prenom'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($provider['nom'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($provider['telephone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($provider['email'] ?? '') ?>" disabled readonly>
                                <small class="text-muted">Non modifiable.</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6>Préférences</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="langue" class="form-label">Langue</label>
                                <select class="form-select" id="langue" name="langue">
                                    <option value="fr" <?= (($settings['langue'] ?? 'fr') === 'fr') ? 'selected' : '' ?>>Français</option>
                                    <option value="en" <?= (($settings['langue'] ?? 'fr') === 'en') ? 'selected' : '' ?>>English</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch pt-md-4">
                                    <input class="form-check-input" type="checkbox" role="switch" id="notif_email" name="notif_email" value="1" <?= (($settings['notif_email'] ?? 1) == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notif_email">Notifications par e-mail</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid d-md-block mt-3">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Changer le Mot de Passe</h5>
                </div>
                <div class="card-body">
                    <form action="<?= WEBCLIENT_URL ?>/modules/providers/settings.php" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Actuel <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required aria-describedby="passwordHelp">
                            <div id="passwordHelp" class="form-text">8 caractères minimum.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid d-md-block">
                            <button type="submit" class="btn btn-warning"><i class="fas fa-key me-1"></i> Changer</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif;
        ?>
    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>