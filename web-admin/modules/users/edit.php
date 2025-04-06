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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        $errors[] = 'Jeton de sécurité invalide ou expiré. Veuillez réessayer.';
        logSecurityEvent($_SESSION['user_id'] ?? 0, 'csrf_failure', "[SECURITY FAILURE] Tentative de modification utilisateur ID: $userId avec jeton invalide");
    } else {
        $submittedData = [
            'nom'           => trim($_POST['nom'] ?? ''),
            'prenom'        => trim($_POST['prenom'] ?? ''),
            'email'         => trim($_POST['email'] ?? ''),
            'telephone'     => trim($_POST['telephone'] ?? ''),
            'role_id'       => (int)($_POST['role_id'] ?? 0),
            'entreprise_id' => isset($_POST['entreprise_id']) && $_POST['entreprise_id'] !== '' ? (int)$_POST['entreprise_id'] : null,
            'statut'        => $_POST['statut'] ?? 'inactif',
            'mot_de_passe'  => $_POST['mot_de_passe'] ?? '', 
        ];
        
        $formData = array_merge($formData, $submittedData);

        if (!empty($submittedData['mot_de_passe']) && $submittedData['mot_de_passe'] !== ($_POST['confirm_mot_de_passe'] ?? null)) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }

        if (empty($errors)) {
            $result = usersSave($submittedData, $userId);

            if ($result['success']) {
                flashMessage($result['message'], 'success');
                redirectTo(WEBADMIN_URL . "/modules/users/view.php?id=" . $userId);
            } else {
                $errors = array_merge($errors, $result['errors']);
            }
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
            <?php echo displayFlashMessages(); ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view.php?id=<?php echo $userId; ?>" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-eye"></i> Voir l'utilisateur
                    </a>
                     <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">Erreurs de validation</h4>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">Informations</div>
                <div class="card-body">
                    <form action="edit.php?id=<?php echo $userId; ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($formData['prenom'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($formData['nom'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($formData['telephone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="role_id" class="form-label">Rôle <span class="text-danger">*</span></label>
                                <select class="form-select" id="role_id" name="role_id" required>
                                    <option value="">Sélectionnez un rôle...</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['id']; ?>" <?php echo (isset($formData['role_id']) && $formData['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div class="col-md-6">
                                <label for="entreprise_id" class="form-label">Entreprise (si applicable)</label>
                                <select class="form-select" id="entreprise_id" name="entreprise_id">
                                    <option value="">Aucune</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>" <?php echo (isset($formData['entreprise_id']) && $formData['entreprise_id'] == $company['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($company['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                         <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select" id="statut" name="statut" required>
                                    <?php foreach (USER_STATUSES as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo (isset($formData['statut']) && $formData['statut'] === $status) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <hr>
                        <p class="text-muted small">Laissez les champs de mot de passe vides pour ne pas le modifier.</p>

                         <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="mot_de_passe" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" autocomplete="new-password">
                            </div>
                             <div class="col-md-6">
                                <label for="confirm_mot_de_passe" class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" class="form-control" id="confirm_mot_de_passe" name="confirm_mot_de_passe" autocomplete="new-password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        <a href="view.php?id=<?php echo $userId; ?>" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>

</rewritten_file>
