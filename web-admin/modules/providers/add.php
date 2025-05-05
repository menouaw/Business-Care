<?php
require_once '../../includes/page_functions/modules/users.php';
require_once '../../includes/page_functions/modules/providers.php';

// requireRole(ROLE_ADMIN)


$formData = [
    'nom' => '',
    'prenom' => '',
    'email' => '',
    'mot_de_passe' => '',
    'mot_de_passe_confirm' => '',
    'telephone' => '',
    'date_naissance' => '',
    'genre' => '',
    'photo_url' => '',
    'statut' => STATUS_ACTIVE, 
];
$errors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        flashMessage('Erreur de sécurité ou session expirée. Veuillez soumettre à nouveau le formulaire.', 'danger');
        $errors['csrf'] = 'Token CSRF invalide.';
        $formData = array_merge($formData, $_POST); 
        $formData['mot_de_passe'] = ''; 
        $formData['mot_de_passe_confirm'] = '';
    } else {
        
        $formData = array_merge($formData, $_POST);
        $nom = trim($formData['nom']);
        $prenom = trim($formData['prenom']);
        $email = trim($formData['email']);
        $password = $formData['mot_de_passe'];
        $passwordConfirm = $formData['mot_de_passe_confirm'];
        $statut = $formData['statut'];

        
        if (empty($nom)) {
            $errors['nom'] = 'Le nom est requis.';
        }
        if (empty($prenom)) {
            $errors['prenom'] = 'Le prénom est requis.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Une adresse email valide est requise.';
        } else {
            
            $existingUser = fetchOne(TABLE_USERS, 'email = :email', '', [':email' => $email]);
            if ($existingUser) {
                $errors['email'] = 'Cet email est déjà utilisé.';
            }
        }
        if (empty($password)) {
            $errors['mot_de_passe'] = 'Le mot de passe est requis.';
        } elseif (strlen($password) < 8) { 
             $errors['mot_de_passe'] = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($password !== $passwordConfirm) {
            $errors['mot_de_passe_confirm'] = 'Les mots de passe ne correspondent pas.';
        }
        if (!in_array($statut, USER_STATUSES)) {
            $errors['statut'] = 'Le statut sélectionné est invalide.';
        }
        

        
        if (empty($errors)) {
            
            $userData = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'mot_de_passe' => $password,
                'mot_de_passe_confirm' => $passwordConfirm,
                'telephone' => $formData['telephone'] ?: null,
                'date_naissance' => $formData['date_naissance'] ?: null,
                'genre' => $formData['genre'] ?: null,
                'photo_url' => $formData['photo_url'] ?: null,
                'statut' => $statut,
                'role_id' => ROLE_PRESTATAIRE,
                'entreprise_id' => null
            ];

            $result = usersSave($userData, 0);

            if ($result['success']) {
                flashMessage($result['message'], 'success');
                
                redirectBasedOnReferer($result['newId'], 'providers');
            } else {
                if (isset($result['errors']) && is_array($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                    foreach ($errors as $errorMsg) {
                         flashMessage($errorMsg, 'danger');
                    }
                } else {
                    flashMessage($result['message'] ?? 'Une erreur inconnue est survenue lors de l\'ajout.', 'danger');
                    $errors['db_error'] = $result['message'] ?? 'Erreur base de données.';
                }
                $formData['mot_de_passe'] = '';
                $formData['mot_de_passe_confirm'] = '';
            }
        } else {
            
            foreach ($errors as $errorMsg) {
                 flashMessage($errorMsg, 'danger');
            }
            $formData['mot_de_passe'] = ''; 
            $formData['mot_de_passe_confirm'] = '';
        }
    }
}

$pageTitle = "Ajouter un prestataire";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                 <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retour à la liste des prestataires">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <form action="<?php echo WEBADMIN_URL; ?>/modules/providers/add.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                <div class="card mb-4">
                    <div class="card-header">Informations</div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['prenom']) ? 'is-invalid' : ''; ?>" id="prenom" name="prenom" value="<?php echo htmlspecialchars($formData['prenom']); ?>" required>
                                <?php if (isset($errors['prenom'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['prenom']).'</div>'; } ?>
                            </div>
                             <div class="col-md-6">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['nom']) ? 'is-invalid' : ''; ?>" id="nom" name="nom" value="<?php echo htmlspecialchars($formData['nom']); ?>" required>
                                <?php if (isset($errors['nom'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['nom']).'</div>'; } ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                                 <?php if (isset($errors['email'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['email']).'</div>'; } ?>
                            </div>
                             <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control <?php echo isset($errors['telephone']) ? 'is-invalid' : ''; ?>" id="telephone" name="telephone" value="<?php echo htmlspecialchars($formData['telephone']); ?>">
                                <?php if (isset($errors['telephone'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['telephone']).'</div>'; } ?>
                            </div>
                        </div>

                         <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="mot_de_passe" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <input type="password" class="form-control <?php echo isset($errors['mot_de_passe']) ? 'is-invalid' : ''; ?>" id="mot_de_passe" name="mot_de_passe" required autocomplete="new-password">
                                 <?php if (isset($errors['mot_de_passe'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['mot_de_passe']).'</div>'; } ?>
                            </div>
                             <div class="col-md-6">
                                <label for="mot_de_passe_confirm" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                                <input type="password" class="form-control <?php echo isset($errors['mot_de_passe_confirm']) ? 'is-invalid' : ''; ?>" id="mot_de_passe_confirm" name="mot_de_passe_confirm" required autocomplete="new-password">
                                 <?php if (isset($errors['mot_de_passe_confirm'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['mot_de_passe_confirm']).'</div>'; } ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="date_naissance" class="form-label">Date de naissance</label>
                                <input type="date" class="form-control <?php echo isset($errors['date_naissance']) ? 'is-invalid' : ''; ?>" id="date_naissance" name="date_naissance" value="<?php echo htmlspecialchars($formData['date_naissance']); ?>">
                                <?php if (isset($errors['date_naissance'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['date_naissance']).'</div>'; } ?>
                            </div>
                            <div class="col-md-4">
                                <label for="genre" class="form-label">Genre</label>
                                <select class="form-select <?php echo isset($errors['genre']) ? 'is-invalid' : ''; ?>" id="genre" name="genre">
                                    <option value="" <?php echo empty($formData['genre']) ? 'selected' : ''; ?>>Non spécifié</option>
                                    <option value="M" <?php echo ($formData['genre'] === 'M') ? 'selected' : ''; ?>>Masculin</option>
                                    <option value="F" <?php echo ($formData['genre'] === 'F') ? 'selected' : ''; ?>>Féminin</option>
                                    <option value="Autre" <?php echo ($formData['genre'] === 'Autre') ? 'selected' : ''; ?>>Autre</option>
                                </select>
                                <?php if (isset($errors['genre'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['genre']).'</div>'; } ?>
                            </div>
                            <div class="col-md-4">
                                <label for="photo_url" class="form-label">URL photo de profil</label>
                                <input type="url" class="form-control <?php echo isset($errors['photo_url']) ? 'is-invalid' : ''; ?>" id="photo_url" name="photo_url" placeholder="https:
                                <?php if (isset($errors['photo_url'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['photo_url']).'</div>'; } ?>
                            </div>
                        </div>

                         <div class="row">
                             <div class="col-md-6">
                                <label for="statut" class="form-label">Statut du compte <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['statut']) ? 'is-invalid' : ''; ?>" id="statut" name="statut" required>
                                    <?php foreach (USER_STATUSES as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo ($formData['statut'] === $s) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(htmlspecialchars($s)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['statut'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['statut']).'</div>'; } ?>
                             </div>
                         </div>
                    </div>
                </div>

                <div class="text-end mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Ajouter le prestataire
                    </button>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>

            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>