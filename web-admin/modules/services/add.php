<?php
require_once '../../includes/page_functions/modules/services.php'; 

requireRole(ROLE_ADMIN);

$pageTitle = "Ajouter un service";
$errors = [];
$formData = [
    'nom' => '',
    'description' => '',
    'prix' => '',
    'duree' => '',
    'type' => '',
    'categorie' => '',
    'niveau_difficulte' => '',
    'capacite_max' => '',
    'materiel_necessaire' => '',
    'prerequis' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleCsrfFailureRedirect(0, 'services', 'ajout service'); 
    } else {
        $submittedData = getFormData(); 
        $formData = array_merge($formData, $submittedData); 

        $saveData = [
            'nom' => $formData['nom'] ?? '',
            'description' => $formData['description'] ?? '',
            'prix' => $formData['prix'] ?? '',
            'duree' => $formData['duree'] ?? null,
            'type' => $formData['type'] ?? '',
            'categorie' => $formData['categorie'] ?? null,
            'niveau_difficulte' => $formData['niveau_difficulte'] ?? null,
            'capacite_max' => $formData['capacite_max'] ?? null,
            'materiel_necessaire' => $formData['materiel_necessaire'] ?? null,
            'prerequis' => $formData['prerequis'] ?? null
        ];

        $saveResult = servicesSave($saveData, 0); 

        if ($saveResult['success']) {
            flashMessage($saveResult['message'] ?? 'Service ajouté avec succès !', 'success');
            redirectTo(WEBADMIN_URL . '/modules/services/view.php?id=' . $saveResult['newId']); 
        } else {
            $errors = $saveResult['errors'] ?? ['db_error' => 'Une erreur technique est survenue lors de l\'ajout.'];
            $logMessage = '[ERROR] Échec ajout service: ' . implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($errors), $errors));
            logSystemActivity('service_add_failure', $logMessage);
        }
    }
}

include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                 <a href="<?php echo WEBADMIN_URL; ?>/modules/services/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>

             <?php 
            if (isset($errors['db_error'])) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($errors['db_error']) . '</div>';
                unset($errors['db_error']);
            }
            foreach($errors as $key => $errorMsg) {
                if (!in_array($key, ['nom', 'description', 'prix', 'duree', 'type', 'categorie', 'niveau_difficulte', 'capacite_max', 'materiel_necessaire', 'prerequis'])) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($errorMsg) . '</div>';
                    unset($errors[$key]);
                }
            }
             ?>
             <?php echo displayFlashMessages(); ?>

            <div class="card">
                 <div class="card-header">
                    Nouveau service
                </div>
                <div class="card-body">
                     <form action="<?php echo WEBADMIN_URL; ?>/modules/services/add.php" method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                         <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom du service <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['nom']) ? 'is-invalid' : ''; ?>" id="nom" name="nom" value="<?php echo htmlspecialchars($formData['nom'] ?? ''); ?>" required>
                                <?php if (isset($errors['nom'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['nom']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="type" class="form-label">Type de service <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['type']) ? 'is-invalid' : ''; ?>" id="type" name="type" required>
                                    <option value="">Selectionnez...</option>
                                    <?php
                                    foreach (PRESTATION_TYPES as $t) {
                                        $selected = (isset($formData['type']) && $formData['type'] === $t) ? 'selected' : '';
                                        echo "<option value=\"".htmlspecialchars($t)."\" $selected>" . ucfirst(htmlspecialchars($t)) . "</option>";
                                    }
                                    ?>
                                </select>
                                <?php if (isset($errors['type'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['type']) . '</div>'; } ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="3"><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                                <?php if (isset($errors['description'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['description']) . '</div>'; } ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="prix" class="form-label">Prix (€) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control <?php echo isset($errors['prix']) ? 'is-invalid' : ''; ?>" id="prix" name="prix" min="0" step="0.01" placeholder="50.00" value="<?php echo htmlspecialchars($formData['prix'] ?? ''); ?>" required>
                                <?php if (isset($errors['prix'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['prix']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-4">
                                <label for="duree" class="form-label">Duree (minutes)</label>
                                <input type="number" class="form-control <?php echo isset($errors['duree']) ? 'is-invalid' : ''; ?>" id="duree" name="duree" min="0" placeholder="60" value="<?php echo htmlspecialchars($formData['duree'] ?? ''); ?>">
                                <?php if (isset($errors['duree'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['duree']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-4">
                                <label for="capacite_max" class="form-label">Capacite maximale</label>
                                <input type="number" class="form-control <?php echo isset($errors['capacite_max']) ? 'is-invalid' : ''; ?>" id="capacite_max" name="capacite_max" min="1" placeholder="20" value="<?php echo htmlspecialchars($formData['capacite_max'] ?? ''); ?>">
                                <?php if (isset($errors['capacite_max'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['capacite_max']) . '</div>'; } ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="categorie" class="form-label">Categorie</label>
                                <input type="text" class="form-control <?php echo isset($errors['categorie']) ? 'is-invalid' : ''; ?>" id="categorie" name="categorie" placeholder="Santé Mentale" value="<?php echo htmlspecialchars($formData['categorie'] ?? ''); ?>">
                                <?php if (isset($errors['categorie'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['categorie']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="niveau_difficulte" class="form-label">Niveau de difficulte</label>
                                <select class="form-select <?php echo isset($errors['niveau_difficulte']) ? 'is-invalid' : ''; ?>" id="niveau_difficulte" name="niveau_difficulte">
                                    <option value="">Selectionnez...</option>
                                    <?php
                                    foreach (PRESTATION_DIFFICULTIES as $level) {
                                        $selected = (isset($formData['niveau_difficulte']) && $formData['niveau_difficulte'] === $level) ? 'selected' : '';
                                        echo "<option value=\"".htmlspecialchars($level)."\" $selected>" . ucfirst(htmlspecialchars($level)) . "</option>";
                                    }
                                    ?>
                                </select>
                                <?php if (isset($errors['niveau_difficulte'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['niveau_difficulte']) . '</div>'; } ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="materiel_necessaire" class="form-label">Materiel necessaire</label>
                                <textarea class="form-control <?php echo isset($errors['materiel_necessaire']) ? 'is-invalid' : ''; ?>" id="materiel_necessaire" name="materiel_necessaire" rows="2" placeholder="Tapis de yoga, tenue confortable..."><?php echo htmlspecialchars($formData['materiel_necessaire'] ?? ''); ?></textarea>
                                <?php if (isset($errors['materiel_necessaire'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['materiel_necessaire']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="prerequis" class="form-label">Prerequis</label>
                                <textarea class="form-control <?php echo isset($errors['prerequis']) ? 'is-invalid' : ''; ?>" id="prerequis" name="prerequis" rows="2" placeholder="Aucun prérequis particulier."><?php echo htmlspecialchars($formData['prerequis'] ?? ''); ?></textarea>
                                <?php if (isset($errors['prerequis'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['prerequis']) . '</div>'; } ?>
                            </div>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">
                           <i class="fas fa-plus-circle me-1"></i> Ajouter le service
                        </button>
                         <a href="<?php echo WEBADMIN_URL; ?>/modules/services/index.php" class="btn btn-secondary">
                            Annuler
                         </a>
                    </form>
                </div>
            </div>

            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>

</rewritten_file>
