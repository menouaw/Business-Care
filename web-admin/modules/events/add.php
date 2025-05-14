<?php
require_once '../../includes/init.php';
require_once '../../includes/page_functions/modules/events.php';

requireRole(ROLE_ADMIN);

$errors = [];
$formData = [];


$formData['titre'] = '';
$formData['type'] = '';
$formData['date_debut'] = '';
$formData['date_fin'] = '';
$formData['site_id'] = '';
$formData['lieu'] = '';
$formData['capacite_max'] = '';
$formData['niveau_difficulte'] = '';
$formData['organise_par_bc'] = '1';
$formData['description'] = '';
$formData['materiel_necessaire'] = '';
$formData['prerequis'] = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleCsrfFailureRedirect(0, 'events', 'creation evenement');
    } else {
        $submittedData = getFormData();

        
        $formData = array_merge($formData, $submittedData);

        
        $formData['organise_par_bc'] = isset($submittedData['organise_par_bc']) ? '1' : '0';

        
        $formData['date_debut_formatted'] = $formData['date_debut'] ? (new DateTime($formData['date_debut']))->format('Y-m-d\TH:i') : '';
        $formData['date_fin_formatted'] = $formData['date_fin'] ? (new DateTime($formData['date_fin']))->format('Y-m-d\TH:i') : '';

        $result = eventsSave($formData, 0);

        if ($result['success']) {
            flashMessage($result['message'], 'success');
            
            redirectTo(WEBADMIN_URL . '/modules/events/view.php?id=' . $result['newId']);
        } else {
            $errors = $result['errors'] ?? ['db_error' => 'Une erreur technique est survenue lors de la création.'];
             foreach ($errors as $field => $errorMsg) {
                
                flashMessage(htmlspecialchars($errorMsg), 'danger');
            }
            
             $formData['date_debut'] = $formData['date_debut_formatted'];
             $formData['date_fin'] = $formData['date_fin_formatted'];
        }
    }
}


$sites = eventsGetSites();
$eventTypes = eventsGetTypes();
$difficultyOptions = eventsGetNiveauDifficulteOptions();


$pageTitle = "Ajouter un evenement";
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
                     <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour a la liste
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Nouvel evenement</div>
                <div class="card-body">
                    <form action="add.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="titre" class="form-label">Titre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['titre']) ? 'is-invalid' : ''; ?>" id="titre" name="titre" value="<?php echo htmlspecialchars($formData['titre'] ?? ''); ?>" required>
                                 <?php if (isset($errors['titre'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['titre']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-4">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['type']) ? 'is-invalid' : ''; ?>" id="type" name="type" required>
                                     <option value="">Selectionnez un type...</option>
                                     <?php foreach ($eventTypes as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo (isset($formData['type']) && $formData['type'] === $value) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['type'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['type']) . '</div>'; } ?>
                            </div>
                        </div>

                         <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="date_debut" class="form-label">Date et heure de debut <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control <?php echo isset($errors['date_debut']) ? 'is-invalid' : ''; ?>" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($formData['date_debut'] ?? ''); ?>" required>
                                <?php if (isset($errors['date_debut'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['date_debut']) . '</div>'; } ?>
                            </div>
                             <div class="col-md-6">
                                <label for="date_fin" class="form-label">Date et heure de fin</label>
                                <input type="datetime-local" class="form-control <?php echo isset($errors['date_fin']) ? 'is-invalid' : ''; ?>" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($formData['date_fin'] ?? ''); ?>">
                                 <?php if (isset($errors['date_fin'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['date_fin']) . '</div>'; } ?>
                            </div>
                        </div>
                        
                         <div class="row mb-3">
                             <div class="col-md-6">
                                <label for="site_id" class="form-label">Site</label>
                                <select class="form-select <?php echo isset($errors['site_id']) ? 'is-invalid' : ''; ?>" id="site_id" name="site_id">
                                     <option value="">Selectionnez un site...</option>
                                     <?php foreach ($sites as $site_id => $site_name): ?>
                                        <option value="<?php echo $site_id; ?>" <?php echo (isset($formData['site_id']) && $formData['site_id'] == $site_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($site_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['site_id'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['site_id']) . '</div>'; } ?>
                            </div>
                             <div class="col-md-6">
                                <label for="lieu" class="form-label">Lieu</label>
                                <input type="text" class="form-control <?php echo isset($errors['lieu']) ? 'is-invalid' : ''; ?>" id="lieu" name="lieu" value="<?php echo htmlspecialchars($formData['lieu'] ?? ''); ?>">
                                 <?php if (isset($errors['lieu'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['lieu']) . '</div>'; } ?>
                            </div>
                         </div>

                        <div class="row mb-3">
                             <div class="col-md-4">
                                <label for="capacite_max" class="form-label">Capacite maximale</label>
                                <input type="number" min="1" class="form-control <?php echo isset($errors['capacite_max']) ? 'is-invalid' : ''; ?>" id="capacite_max" name="capacite_max" value="<?php echo htmlspecialchars($formData['capacite_max'] ?? ''); ?>">
                                 <?php if (isset($errors['capacite_max'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['capacite_max']) . '</div>'; } ?>
                            </div>
                             <div class="col-md-4">
                                <label for="niveau_difficulte" class="form-label">Niveau de difficulte</label>
                                <select class="form-select <?php echo isset($errors['niveau_difficulte']) ? 'is-invalid' : ''; ?>" id="niveau_difficulte" name="niveau_difficulte">
                                     <option value="">Selectionnez un niveau...</option>
                                     <?php foreach ($difficultyOptions as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo (isset($formData['niveau_difficulte']) && $formData['niveau_difficulte'] === $value) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['niveau_difficulte'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['niveau_difficulte']) . '</div>'; } ?>
                            </div>
                             <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check mb-1">
                                     <input class="form-check-input <?php echo isset($errors['organise_par_bc']) ? 'is-invalid' : ''; ?>" type="checkbox" value="1" id="organise_par_bc" name="organise_par_bc" <?php echo (isset($formData['organise_par_bc']) && $formData['organise_par_bc'] == '1') ? 'checked' : ''; ?>>
                                     <label class="form-check-label" for="organise_par_bc">
                                        Organise par BC
                                     </label>
                                     <?php if (isset($errors['organise_par_bc'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['organise_par_bc']) . '</div>'; } ?>
                                 </div>
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
                             <div class="col-md-12">
                                <label for="materiel_necessaire" class="form-label">Materiel necessaire</label>
                                <textarea class="form-control <?php echo isset($errors['materiel_necessaire']) ? 'is-invalid' : ''; ?>" id="materiel_necessaire" name="materiel_necessaire" rows="3"><?php echo htmlspecialchars($formData['materiel_necessaire'] ?? ''); ?></textarea>
                                <?php if (isset($errors['materiel_necessaire'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['materiel_necessaire']) . '</div>'; } ?>
                             </div>
                         </div>

                         <div class="row mb-3">
                             <div class="col-md-12">
                                <label for="prerequis" class="form-label">Prerequis</label>
                                <textarea class="form-control <?php echo isset($errors['prerequis']) ? 'is-invalid' : ''; ?>" id="prerequis" name="prerequis" rows="3"><?php echo htmlspecialchars($formData['prerequis'] ?? ''); ?></textarea>
                                <?php if (isset($errors['prerequis'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['prerequis']) . '</div>'; } ?>
                             </div>
                         </div>

                        <button type="submit" class="btn btn-primary">Ajouter l'evenement</button>
                        <a href="index.php" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div> 