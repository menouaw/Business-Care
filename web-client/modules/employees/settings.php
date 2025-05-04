<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/settings.php';

$viewData = setupEmployeeSettingsPage();

extract($viewData);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                </a>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="row">

                <div class="col-lg-8">

                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Informations du Profil</h5>
                        </div>
                        <div class="card-body">
                            <form action="settings.php" method="post" id="profile-form">
                                <input type="hidden" name="action" value="update_profile">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token_profile ?>">

                                <div class="row g-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="prenom" class="form-label">Prénom*</label>
                                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($employee['prenom'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="nom" class="form-label">Nom*</label>
                                        <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($employee['nom'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="email" class="form-label">Email (non modifiable)</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($employee['email'] ?? '') ?>" disabled readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="telephone" class="form-label">Téléphone</label>
                                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($employee['telephone'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="date_naissance" class="form-label">Date de Naissance</label>
                                        <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($employee['date_naissance'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="genre" class="form-label">Genre</label>
                                        <select class="form-select" id="genre" name="genre">
                                            <option value="" <?= !isset($employee['genre']) ? 'selected' : '' ?>>Non spécifié</option>
                                            <option value="M" <?= ($employee['genre'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin</option>
                                            <option value="F" <?= ($employee['genre'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin</option>
                                            <option value="Autre" <?= ($employee['genre'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
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
                                    <label for="current_password" class="form-label">Mot de passe actuel*</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe*</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="<?= MIN_PASSWORD_LENGTH ?>">
                                    <div class="form-text">Minimum <?= MIN_PASSWORD_LENGTH ?> caractères.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe*</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>

                                <button type="submit" class="btn btn-warning">Changer le mot de passe</button>
                            </form>
                        </div>
                    </div>


                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Mes Intérêts Bien-être</h5>
                        </div>
                        <div class="card-body">
                            <form action="settings.php" method="post" id="interests-form">
                                <input type="hidden" name="action" value="update_interests">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token_interests ?>">

                                <p class="text-muted">Sélectionnez les sujets qui vous intéressent le plus pour personnaliser les contenus proposés.</p>

                                <div class="row">
                                    <?php if (!empty($allInterests)): ?>
                                        <?php foreach ($allInterests as $interest): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input"
                                                        type="checkbox"
                                                        name="interests[]"
                                                        value="<?= (int)$interest['id'] ?>"
                                                        id="interest_<?= (int)$interest['id'] ?>"
                                                        <?= in_array($interest['id'], $userInterestIds ?? []) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="interest_<?= (int)$interest['id'] ?>">
                                                        <?= htmlspecialchars($interest['nom']) ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>Aucun intérêt disponible pour le moment.</p>
                                    <?php endif; ?>
                                </div>

                                <button type="submit" class="btn btn-info mt-3">Enregistrer mes intérêts</button>
                            </form>
                        </div>
                    </div>


                </div>


                <div class="col-lg-4">

                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Photo de Profil</h5>
                        </div>
                        <div class="card-body text-center">
                            <img src="<?= htmlspecialchars($employee['photo_url'] ?? '/assets/images/default-avatar.png') ?>"
                                alt="Photo de profil" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;"
                                id="profile-photo-preview">

                            <?php if ($flash_new_photo_url): ?>
                                <script>
                                    document.getElementById('profile-photo-preview').src = '<?= htmlspecialchars($flash_new_photo_url) ?>';
                                </script>
                            <?php endif; ?>

                            <form action="settings.php" method="post" enctype="multipart/form-data" id="photo-form">
                                <input type="hidden" name="action" value="update_photo">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token_photo ?>">
                                <div class="mb-3">
                                    <label for="profile_photo" class="form-label">Changer la photo (JPG, PNG, GIF - Max 2Mo)</label>
                                    <input class="form-control" type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/gif">
                                </div>
                                <button type="submit" name="action" value="update_photo" class="btn btn-outline-primary">
                                    <i class="fas fa-upload me-1"></i> Mettre à jour la photo
                                </button>
                            </form>
                        </div>
                    </div>



                </div>
            </div>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>