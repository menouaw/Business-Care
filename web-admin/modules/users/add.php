<?php
require_once '../../includes/page_functions/modules/users.php'; 

requireRole(ROLE_ADMIN);

$pageTitle = "Ajouter un utilisateur";
$errors = [];
$formData = [
    'prenom' => '',
    'nom' => '',
    'email' => '',
    'telephone' => '',
    'role_id' => '',
    'entreprise_id' => '',
    'statut' => 'actif', 
    'mot_de_passe' => '',
    'mot_de_passe_confirm' => '', 
];

$roles = usersGetRoles();
$entreprises = usersGetEntreprises();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleCsrfFailureRedirect(0, 'users', 'ajout utilisateur'); 
    } else {
        $submittedData = getFormData(); 
        $formData = array_merge($formData, $submittedData); 

        $saveResult = usersSave($formData, 0); 

        if ($saveResult['success']) {
            flashMessage($saveResult['message'] ?? 'Utilisateur ajouté avec succès !', 'success');
            redirectBasedOnReferer($saveResult['newId'], 'users');
        } else {
            $errors = $saveResult['errors'] ?? ['db_error' => 'Une erreur technique est survenue lors de l\'ajout.'];
            logSystemActivity('user_add_failure', '[ERROR] Échec ajout utilisateur: ' . implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($errors), $errors)));
        }
    }
}

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                 <a href="<?php echo WEBADMIN_URL; ?>/modules/users/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>

             <?php echo displayFlashMessages(); ?>

            <div class="card">
                 <div class="card-header">
                    Informations
                </div>
                <div class="card-body">
                     <form action="<?php echo WEBADMIN_URL; ?>/modules/users/add.php" method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['prenom']) ? 'is-invalid' : ''; ?>" id="prenom" name="prenom" value="<?php echo htmlspecialchars($formData['prenom']); ?>" required>
                                <?php if (isset($errors['prenom'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['prenom']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['nom']) ? 'is-invalid' : ''; ?>" id="nom" name="nom" value="<?php echo htmlspecialchars($formData['nom']); ?>" required>
                                <?php if (isset($errors['nom'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['nom']) . '</div>'; } ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                            <?php if (isset($errors['email'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['email']) . '</div>'; } ?>
                        </div>

                         <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mot_de_passe" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <input type="password" class="form-control <?php echo isset($errors['mot_de_passe']) ? 'is-invalid' : ''; ?>" id="mot_de_passe" name="mot_de_passe" required autocomplete="new-password">
                                <?php if (isset($errors['mot_de_passe'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['mot_de_passe']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mot_de_passe_confirm" class="form-label">Confirmation <span class="text-danger">*</span></label>
                                <input type="password" class="form-control <?php echo isset($errors['mot_de_passe_confirm']) ? 'is-invalid' : ''; ?>" id="mot_de_passe_confirm" name="mot_de_passe_confirm" required autocomplete="new-password">
                                <?php if (isset($errors['mot_de_passe_confirm'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['mot_de_passe_confirm']) . '</div>'; } ?>
                            </div>
                        </div>

                         <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control <?php echo isset($errors['telephone']) ? 'is-invalid' : ''; ?>" id="telephone" name="telephone" placeholder="0612345678" value="<?php echo htmlspecialchars($formData['telephone']); ?>">
                            <?php if (isset($errors['telephone'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['telephone']) . '</div>'; } ?>
                        </div>

                        <div class="row">
                             <div class="col-md-6 mb-3">
                                <label for="role_id" class="form-label">Rôle <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['role_id']) ? 'is-invalid' : ''; ?>" id="role_id" name="role_id" required>
                                    <option value="">-- Sélectionner un rôle --</option>
                                    <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>" <?php echo ($formData['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($role['nom'])); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['role_id'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['role_id']) . '</div>'; } ?>
                            </div>
                             <div class="col-md-6 mb-3">
                                <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['statut']) ? 'is-invalid' : ''; ?>" id="statut" name="statut" required>
                                    <?php foreach (USER_STATUSES as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo ($formData['statut'] === $status) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['statut'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['statut']) . '</div>'; } ?>
                            </div>
                        </div>

                         <div class="mb-3">
                            <label for="entreprise_id" class="form-label">Entreprise (si applicable)</label>
                            <select class="form-select <?php echo isset($errors['entreprise_id']) ? 'is-invalid' : ''; ?>" id="entreprise_id" name="entreprise_id">
                                <option value="">-- Aucune --</option>
                                <?php foreach ($entreprises as $entreprise): ?>
                                <option value="<?php echo $entreprise['id']; ?>" <?php echo ($formData['entreprise_id'] == $entreprise['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($entreprise['nom']); ?> (ID: <?php echo $entreprise['id']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['entreprise_id'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['entreprise_id']) . '</div>'; } ?>
                            <small class="form-text text-muted">Obligatoire si le rôle est Salarié ou Entreprise.</small>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">
                           <i class="fas fa-plus-circle me-1"></i> Ajouter l'utilisateur
                        </button>
                         <a href="<?php echo WEBADMIN_URL; ?>/modules/users/index.php" class="btn btn-secondary">
                            Annuler
                         </a>
                    </form>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
