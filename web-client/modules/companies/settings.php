<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies/settings.php';

requireRole(ROLE_ENTREPRISE);

$entreprise_id = $_SESSION['user_entreprise'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if ($entreprise_id <= 0 || $user_id <= 0) {
    flashMessage("Impossible d'identifier votre compte ou votre entreprise.", "danger");
    redirectTo(WEBCLIENT_URL . '/modules/companies/dashboard.php');
    exit;
}

$pageTitle = "Paramètres";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        flashMessage("Le nouveau mot de passe et sa confirmation ne correspondent pas.", "danger");
    } else {
        $result = updateCompanyRepresentativePassword($user_id, $current_password, $new_password);
        flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
    }
    redirectTo(WEBCLIENT_URL . '/modules/companies/settings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_photo'])) {
    verifyCsrfToken();

    if (isset($_FILES['profile_photo'])) {
        $result = updateUserProfilePhoto($user_id, $_FILES['profile_photo']);
        if ($result['success'] && $result['new_photo_url']) {
            $_SESSION['user_photo'] = $result['new_photo_url'];
        }
        flashMessage($result['message'], $result['success'] ? 'success' : 'danger');
    } else {
        flashMessage("Aucun fichier photo n'a été envoyé.", "warning");
    }
    redirectTo(WEBCLIENT_URL . '/modules/companies/settings.php');
    exit;
}

$company_details = getCompanyDetailsForSettings($entreprise_id);
$user_info = getUserInfo($user_id);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= WEBCLIENT_URL ?>/modules/companies/dashboard.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de Bord
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>


            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-building me-1"></i> Informations sur l'entreprise
                </div>
                <div class="card-body">
                    <?php if ($company_details): ?>
                        <dl class="row">
                            <dt class="col-sm-3">Nom:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($company_details['nom']) ?></dd>

                            <dt class="col-sm-3">SIRET:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($company_details['siret'] ?? 'N/A') ?></dd>

                            <dt class="col-sm-3">Adresse:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($company_details['adresse'] ?? '') ?>, <?= htmlspecialchars($company_details['code_postal'] ?? '') ?> <?= htmlspecialchars($company_details['ville'] ?? '') ?></dd>

                            <dt class="col-sm-3">Téléphone:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($company_details['telephone'] ?? 'N/A') ?></dd>

                            <dt class="col-sm-3">Email Contact:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($company_details['email'] ?? 'N/A') ?></dd>
                        </dl>
                    <?php else: ?>
                        <p class="text-danger">Impossible de charger les informations de l'entreprise.</p>
                    <?php endif; ?>
                </div>
            </div>


            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i> Vos informations personnelles
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-9">
                            <?php if ($user_info): ?>
                                <dl class="row">
                                    <dt class="col-sm-3">Nom:</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($user_info['prenom'] . ' ' . $user_info['nom']) ?></dd>

                                    <dt class="col-sm-3">Email:</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($user_info['email']) ?></dd>

                                    <dt class="col-sm-3">Rôle:</dt>
                                    <dd class="col-sm-9">Représentant Entreprise</dd>

                                    <dt class="col-sm-3">Téléphone:</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($user_info['telephone'] ?? 'Non renseigné') ?></dd>
                                </dl>
                            <?php else: ?>
                                <p class="text-danger">Impossible de charger vos informations utilisateur.</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 text-center">
                            <h5>Photo de Profil</h5>
                            <img src="<?= htmlspecialchars($_SESSION['user_photo'] ?? ASSETS_URL . '/images/user_default.png') ?>" alt="Photo de profil" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: contain;">

                            <form method="POST" action="<?= WEBCLIENT_URL ?>/modules/companies/settings.php" enctype="multipart/form-data">
                                <input type="hidden" name="change_photo" value="1">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()); ?>">

                                <div class="mb-3">
                                    <label for="profile_photo" class="form-label">Changer la photo</label>
                                    <input class="form-control form-control-sm" type="file" id="profile_photo" name="profile_photo" accept="image/jpeg, image/png, image/gif" required>
                                    <div class="form-text">Taille max 2Mo. Formats: JPG, PNG, GIF.</div>
                                </div>
                                <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-upload me-1"></i> Mettre à jour la photo</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card">
                <div class="card-header">
                    <i class="fas fa-key me-1"></i> Changer votre mot de passe
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= WEBCLIENT_URL ?>/modules/companies/settings.php">
                        <input type="hidden" name="change_password" value="1">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()); ?>">

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Minimum <?= defined('MIN_PASSWORD_LENGTH') ? MIN_PASSWORD_LENGTH : 8 ?> caractères.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Mettre à jour le mot de passe</button>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

