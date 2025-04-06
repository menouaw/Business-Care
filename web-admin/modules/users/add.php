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
];

$roles = usersGetRoles();
$entreprises = usersGetEntreprises();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Erreur de sécurité (jeton CSRF invalide).';
        logSecurityEvent($_SESSION['user_id'] ?? null, 'csrf_failure', '[FAILURE] Tentative d\'ajout utilisateur échouée - CSRF invalide', true);
    } else {
        $submittedData = getFormData(); 
        $formData = array_merge($formData, $submittedData); 

        if (empty($formData['prenom'])) $errors[] = "Le prénom est obligatoire.";
        if (empty($formData['nom'])) $errors[] = "Le nom est obligatoire.";
        if (empty($formData['email'])) {
             $errors[] = "L'email est obligatoire.";
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
             $errors[] = "L'email n'est pas valide.";
        } else {
             $existingUser = fetchOne('personnes', 'email = ?', '', [$formData['email']]);
             if ($existingUser) {
                 $errors[] = "Cet email est déjà utilisé par un autre compte.";
             }
        }
        if (empty($formData['mot_de_passe'])) {
             $errors[] = "Le mot de passe est obligatoire.";
        } elseif ($formData['mot_de_passe'] !== ($_POST['mot_de_passe_confirm'] ?? '')) {
             $errors[] = "Les mots de passe ne correspondent pas.";
        }
        if (empty($formData['role_id'])) $errors[] = "Le rôle est obligatoire.";
        if (empty($formData['statut'])) $errors[] = "Le statut est obligatoire.";

        if (!empty($formData['role_id']) && !fetchOne('roles', 'id = ?', '', [(int)$formData['role_id']])) {
            $errors[] = "Le rôle sélectionné est invalide.";
        }
        if (!empty($formData['entreprise_id']) && !fetchOne('entreprises', 'id = ?', '', [(int)$formData['entreprise_id']])) {
            $errors[] = "L'entreprise sélectionnée est invalide.";
        }

        if (empty($errors)) {
            $saveResult = usersSave($formData, 0); 

            if ($saveResult['success']) {
                flashMessage($saveResult['message'] ?? 'Utilisateur ajouté avec succès !', 'success');
                redirectTo('view.php?id=' . $saveResult['newId']);
            } else {
                $errors = array_merge($errors, $saveResult['errors'] ?? ['Une erreur technique est survenue lors de l\'ajout.']);
                logSystemActivity('user_add_failure', '[ERROR] Échec ajout utilisateur: ' . implode(', ', $errors));
            }
        }
    }

     if (!empty($errors)) {
        flashMessage('Erreurs de validation: ' . implode('<br>', array_map('htmlspecialchars', $errors)), 'danger');
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
                 <a href="index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retour à la liste">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>

             <?php echo displayFlashMessages(); ?>

            <div class="card">
                 <div class="card-header">
                    Informations
                </div>
                <div class="card-body">
                     <form action="add.php" method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($formData['prenom']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($formData['nom']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                        </div>

                         <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mot_de_passe" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mot_de_passe_confirm" class="form-label">Confirmation <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="mot_de_passe_confirm" name="mot_de_passe_confirm" required>
                            </div>
                        </div>

                         <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="0612345678" value="<?php echo htmlspecialchars($formData['telephone']); ?>">
                        </div>

                        <div class="row">
                             <div class="col-md-6 mb-3">
                                <label for="role_id" class="form-label">Rôle <span class="text-danger">*</span></label>
                                <select class="form-select" id="role_id" name="role_id" required>
                                    <option value="">-- Sélectionner un rôle --</option>
                                    <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>" <?php echo ($formData['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($role['nom'])); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div class="col-md-6 mb-3">
                                <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select" id="statut" name="statut" required>
                                    <?php foreach (USER_STATUSES as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo ($formData['statut'] === $status) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                         <div class="mb-3">
                            <label for="entreprise_id" class="form-label">Entreprise (si applicable)</label>
                            <select class="form-select" id="entreprise_id" name="entreprise_id">
                                <option value="">-- Aucune --</option>
                                <?php foreach ($entreprises as $entreprise): ?>
                                <option value="<?php echo $entreprise['id']; ?>" <?php echo ($formData['entreprise_id'] == $entreprise['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($entreprise['nom']); ?> (ID: <?php echo $entreprise['id']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Obligatoire si le rôle est Salarié ou Entreprise.</small>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">
                           <i class="fas fa-plus-circle me-1"></i> Ajouter l'utilisateur
                        </button>
                         <a href="index.php" class="btn btn-secondary">
                            Annuler
                         </a>
                    </form>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
