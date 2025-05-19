<?php
require_once __DIR__ . '/../../includes/init.php';

require_once __DIR__ . '/../../includes/page_functions/modules/providers/settings.php';


$viewData = setupProviderSettingsPage();


extract($viewData);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; 
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <a href="<?= WEBCLIENT_URL ?>/modules/providers/dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                </a>
            </div>

            <?php echo displayFlashMessages(); 
            ?>

            <div class="row">

                <div class="col-lg-12">

                    
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Informations du Profil Prestataire</h5>
                        </div>
                        <div class="card-body">
                            <form action="settings.php" method="post" id="profile-form">
                                <input type="hidden" name="action" value="update_profile">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token_profile ?>">

                                <div class="row g-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($provider['prenom'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($provider['nom'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="email" class="form-label">Email (non modifiable)</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($provider['email'] ?? '') ?>" disabled readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="telephone" class="form-label">Téléphone</label>
                                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($provider['telephone'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="date_naissance" class="form-label">Date de Naissance</label>
                                        <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($provider['date_naissance'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="genre" class="form-label">Genre</label>
                                        <select class="form-select" id="genre" name="genre">
                                            <option value="" <?= !isset($provider['genre']) ? 'selected' : '' ?>>Non spécifié</option>
                                            <option value="M" <?= ($provider['genre'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin</option>
                                            <option value="F" <?= ($provider['genre'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin</option>
                                            <option value="Autre" <?= ($provider['genre'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                                        </select>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary mt-3">Enregistrer les modifications du profil</button>
                            </form>
                        </div>
                    </div>

                    
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Modifier le mot de passe</h5>
                        </div>
                        <div class="card-body">
                            <form action="settings.php" method="post" id="password-form">
                                <input type="hidden" name="action" value="update_password">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token_password ?>">

                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="<?= defined('MIN_PASSWORD_LENGTH') ? MIN_PASSWORD_LENGTH : 8 ?>">
                                    <div class="form-text">Minimum <?= defined('MIN_PASSWORD_LENGTH') ? MIN_PASSWORD_LENGTH : 8 ?> caractères.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>

                                <button type="submit" class="btn btn-warning">Changer le mot de passe</button>
                            </form>
                        </div>
                    </div>

                    
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Préférences Générales</h5>
                        </div>
                        <div class="card-body">
                            <form action="settings.php" method="post" id="preferences-form">
                                <input type="hidden" name="action" value="update_preferences">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token_preferences ?>">

                                <div class="mb-3">
                                    <label for="langue" class="form-label">Langue</label>
                                    <select class="form-select" id="langue" name="langue">
                                        <option value="fr" <?= ($preferences['langue'] ?? 'fr') === 'fr' ? 'selected' : '' ?>>Français</option>
                                        <option value="en" <?= ($preferences['langue'] ?? 'fr') === 'en' ? 'selected' : '' ?>>English</option>
                                    </select>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="notif_email" name="notif_email" value="1" <?= !empty($preferences['notif_email']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notif_email">
                                        Recevoir les notifications importantes par email
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-info">Enregistrer les préférences</button>
                            </form>
                        </div>
                    </div>

                </div>

            </div>

        </main>
    </div>
</div>
