<?php
require_once '../../includes/page_functions/modules/users.php';

requireRole(ROLE_ADMIN);

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    flashMessage('Identifiant utilisateur invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/users/index.php');
}

$user = usersGetDetails($userId);
if (!$user) {
    flashMessage("Utilisateur non trouvé.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/users/index.php');
}

$roles = usersGetRoles();
$companies = usersGetEntreprises();

$errors = [];
$formData = $user; 
$formData['mot_de_passe'] = ''; 
$formData['mot_de_passe_confirm'] = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleCsrfFailureRedirect($userId, 'users', 'modification utilisateur');
    } else {
        $submittedData = getFormData(); 
        $formData = array_merge($formData, $submittedData);
        
        $result = usersSave($formData, $userId); 

        if ($result['success']) {
            flashMessage($result['message'], 'success');
            // Use existing function which handles referer logic
            redirectBasedOnReferer($result['userId'], 'users'); 
        } else {
            $errors = $result['errors'] ?? ['db_error' => 'Une erreur technique est survenue lors de la mise à jour.'];
            logSystemActivity('user_edit_failure', '[ERROR] Échec modification utilisateur ID: ' . $userId . ' - ' . implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($errors), $errors)));
        }
    }
}

$pageTitle = "Modifier l'utilisateur";
include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">


            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $userId; ?>" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="tooltip" title="Voir le profil de l'utilisateur">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                     <a href="<?php echo WEBADMIN_URL; ?>/modules/users/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Informations sur l'utilisateur</div>
                <div class="card-body">
                    <form action="<?php echo WEBADMIN_URL; ?>/modules/users/edit.php?id=<?php echo $userId; ?>" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['prenom']) ? 'is-invalid' : ''; ?>" id="prenom" name="prenom" value="<?php echo htmlspecialchars($formData['prenom'] ?? ''); ?>" required>
                                <?php if (isset($errors['prenom'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['prenom']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['nom']) ? 'is-invalid' : ''; ?>" id="nom" name="nom" value="<?php echo htmlspecialchars($formData['nom'] ?? ''); ?>" required>
                                <?php if (isset($errors['nom'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['nom']) . '</div>'; } ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                                <?php if (isset($errors['email'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['email']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control <?php echo isset($errors['telephone']) ? 'is-invalid' : ''; ?>" id="telephone" name="telephone" value="<?php echo htmlspecialchars($formData['telephone'] ?? ''); ?>" placeholder="0612345678">
                                <?php if (isset($errors['telephone'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['telephone']) . '</div>'; } ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="role_id" class="form-label">Rôle <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['role_id']) ? 'is-invalid' : ''; ?>" id="role_id" name="role_id" required>
                                    <option value="">Sélectionnez un rôle...</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['id']; ?>" <?php echo (isset($formData['role_id']) && $formData['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(ucfirst($role['nom'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['role_id'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['role_id']) . '</div>'; } ?>
                            </div>
                             <div class="col-md-6">
                                <label for="entreprise_id" class="form-label">Entreprise (si applicable)</label>
                                <select class="form-select <?php echo isset($errors['entreprise_id']) ? 'is-invalid' : ''; ?>" id="entreprise_id" name="entreprise_id">
                                    <option value="">Aucune</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>" <?php echo (isset($formData['entreprise_id']) && $formData['entreprise_id'] == $company['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($company['nom']); ?> (ID: <?php echo $company['id']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['entreprise_id'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['entreprise_id']) . '</div>'; } ?>
                                <small class="form-text text-muted">Obligatoire si le rôle est Salarié ou Entreprise.</small>
                            </div>
                        </div>

                         <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['statut']) ? 'is-invalid' : ''; ?>" id="statut" name="statut" required>
                                    <?php foreach (USER_STATUSES as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo (isset($formData['statut']) && $formData['statut'] === $status) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['statut'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['statut']) . '</div>'; } ?>
                            </div>
                        </div>
                        
                        <hr>
                        <p class="text-muted small">Laissez les champs de mot de passe vides pour ne pas le modifier.</p>

                         <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="mot_de_passe" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control <?php echo isset($errors['mot_de_passe']) ? 'is-invalid' : ''; ?>" id="mot_de_passe" name="mot_de_passe" autocomplete="new-password">
                                <?php if (isset($errors['mot_de_passe'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['mot_de_passe']) . '</div>'; } ?>
                            </div>
                             <div class="col-md-6">
                                <label for="mot_de_passe_confirm" class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" class="form-control <?php echo isset($errors['mot_de_passe_confirm']) ? 'is-invalid' : ''; ?>" id="mot_de_passe_confirm" name="mot_de_passe_confirm" autocomplete="new-password">
                                <?php if (isset($errors['mot_de_passe_confirm'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['mot_de_passe_confirm']) . '</div>'; } ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Enregistrer les modifications
                        </button>
                        <a href="<?php echo WEBADMIN_URL; ?>/modules/users/index.php" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>


