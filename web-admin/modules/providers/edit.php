<?php
require_once '../../includes/page_functions/modules/providers.php';
require_once '../../includes/page_functions/modules/users.php';

// requireRole(ROLE_ADMIN)

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flashMessage('Identifiant de prestataire invalide pour la modification.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/providers/index.php');
}


$formData = [
    'nom' => '',
    'prenom' => '',
    'email' => '',
    'telephone' => '',
    'date_naissance' => '',
    'genre' => '',
    'photo_url' => '',
    'statut' => STATUS_ACTIVE, 
];
$errors = [];


$provider = getProviderDetails($id);
if (!$provider) {
    flashMessage("Prestataire non trouvé.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/providers/index.php');
}

$formData = array_merge($formData, $provider);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        flashMessage('Erreur de sécurité ou session expirée. Veuillez soumettre à nouveau le formulaire.', 'danger');
        $errors['csrf'] = 'Token CSRF invalide.';
        $formData = array_merge($formData, $_POST); 
    } else {
        
        $formData = array_merge($formData, $_POST); 
        $nom = trim($formData['nom']);
        $prenom = trim($formData['prenom']);
        $email = trim($formData['email']);
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
            
            $existingUser = fetchOne(TABLE_USERS, 'email = :email AND id != :id', '', [':email' => $email, ':id' => $id]);
            if ($existingUser) {
                $errors['email'] = 'Cet email est déjà utilisé par un autre utilisateur.';
            }
        }
        if (!in_array($statut, USER_STATUSES)) {
            $errors['statut'] = 'Le statut sélectionné est invalide.';
        }

        
        if (empty($errors)) {
            try {
                
                $updateData = [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'telephone' => $formData['telephone'] ?: null,
                    'date_naissance' => $formData['date_naissance'] ?: null,
                    'genre' => $formData['genre'] ?: null,
                    'photo_url' => $formData['photo_url'] ?: null,
                    'statut' => $statut,
                    'role_id' => ROLE_PRESTATAIRE,
                    'entreprise_id' => null
                ];

                $result = usersSave($updateData, $id); 
                
                if ($result['success']) {
                    flashMessage($result['message'] ?? 'Les informations du prestataire ont été mises à jour avec succès.', 'success');
                } else {
                    if (isset($result['errors']) && is_array($result['errors'])) {
                        $errors = array_merge($errors, $result['errors']);
                        foreach ($errors as $errorMsg) {
                            flashMessage($errorMsg, 'danger');
                        }
                    } else {
                        flashMessage($result['message'] ?? 'Aucune modification détectée ou erreur lors de la mise à jour.', 'info'); 
                        $errors['db_error'] = $result['message'] ?? 'Erreur base de données.';
                    }
                    $stayOnPage = true;
                }
                
                if (!isset($stayOnPage) || !$stayOnPage) {
                   redirectBasedOnReferer($id, 'providers');
                }
                
            } catch (Exception $e) {
                flashMessage('Erreur lors de la mise à jour du prestataire: ' . $e->getMessage(), 'danger');
                $errors['db_error'] = 'Erreur base de données: ' . $e->getMessage();
                
            }
        } else {
            
            foreach ($errors as $errorMsg) {
                 flashMessage($errorMsg, 'danger');
            }
        }
    }
}

$pageTitle = "Modifier le prestataire";
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
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/view.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="tooltip" title="Voir le prestataire">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retour à la liste des prestataires">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <form action="<?php echo WEBADMIN_URL; ?>/modules/providers/edit.php?id=<?php echo $id; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                <div class="card mb-4">
                    <div class="card-header">Informations</div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['prenom']) ? 'is-invalid' : ''; ?>" id="prenom" name="prenom" value="<?php echo htmlspecialchars($formData['prenom']); ?>" required>
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
                                <input type="url" class="form-control <?php echo isset($errors['photo_url']) ? 'is-invalid' : ''; ?>" id="photo_url" name="photo_url">
                                <?php if (isset($errors['photo_url'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['photo_url']).'</div>'; } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Enregistrer les modifications
                    </button>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </form>

            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
