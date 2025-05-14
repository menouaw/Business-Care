<?php
require_once '../../includes/page_functions/modules/conferences.php';

requireRole(ROLE_ADMIN);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flashMessage('Identifiant de conférence invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/conferences/index.php');
}

$conference = conferencesGetDetails($id);

if (!$conference) {
    flashMessage("Conférence non trouvée.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/conferences/index.php');
}

$sites = conferencesGetSites();
$niveauDifficulteOptions = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'];

$errors = [];
$formData = $conference;

// Format dates for datetime-local input
$formData['date_debut_formatted'] = $formData['date_debut'] ? (new DateTime($formData['date_debut']))->format('Y-m-d\TH:i') : '';
$formData['date_fin_formatted'] = $formData['date_fin'] ? (new DateTime($formData['date_fin']))->format('Y-m-d\TH:i') : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleCsrfFailureRedirect($id, 'conferences', 'modification conférence');
    } else {
        $submittedData = getFormData();
        
        // Merge submitted data but keep formatted dates for sticky form
        $formData = array_merge($formData, $submittedData);

        // Convert datetime-local format back to expected string for save function
        $dataToSave = $formData;
        $dataToSave['date_debut'] = $formData['date_debut_formatted'] ?? '';
        $dataToSave['date_fin'] = $formData['date_fin_formatted'] ?? null;
        
        $result = conferencesSave($dataToSave, $id);

        if ($result['success']) {
            flashMessage($result['message'], 'success');
            redirectBasedOnReferer($id, 'conferences');
        } else {
            $errors = $result['errors'] ?? ['db_error' => 'Une erreur technique est survenue lors de la mise à jour.'];
             foreach ($errors as $field => $errorMsg) {
                // Check if the error is for a formatted date field and display the correct message
                if ($field === 'date_debut' && isset($result['errors']['date_debut'])) {
                     flashMessage('Date et heure de début : ' . htmlspecialchars($result['errors']['date_debut']), 'danger');
                } elseif ($field === 'date_fin' && isset($result['errors']['date_fin'])) {
                     flashMessage('Date et heure de fin : ' . htmlspecialchars($result['errors']['date_fin']), 'danger');
                } else {
                    flashMessage(htmlspecialchars($errorMsg), 'danger');
                }
            }
             // Re-format submitted dates for sticky form display in case of errors
             $formData['date_debut_formatted'] = $submittedData['date_debut'] ?? '';
             $formData['date_fin_formatted'] = $submittedData['date_fin'] ?? '';
        }
    }
}

$pageTitle = "Modifier la conférence";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/conferences/view.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="tooltip" title="Voir la conférence">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                     <a href="<?php echo WEBADMIN_URL; ?>/modules/conferences/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retour à la liste">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Informations sur la conférence</div>
                <div class="card-body">
                    <form action="<?php echo WEBADMIN_URL; ?>/modules/conferences/edit.php?id=<?php echo $id; ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="titre" class="form-label">Titre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['titre']) ? 'is-invalid' : ''; ?>" id="titre" name="titre" value="<?php echo htmlspecialchars($formData['titre'] ?? ''); ?>" required>
                                <?php if (isset($errors['titre'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['titre']) . '</div>'; } ?>
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
                            <div class="col-md-6">
                                <label for="date_debut" class="form-label">Date et Heure de début <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control <?php echo isset($errors['date_debut']) ? 'is-invalid' : ''; ?>" id="date_debut" name="date_debut_formatted" value="<?php echo htmlspecialchars($formData['date_debut_formatted'] ?? ''); ?>" required>
                                <?php if (isset($errors['date_debut'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['date_debut']) . '</div>'; } ?>
                            </div>
                             <div class="col-md-6">
                                <label for="date_fin" class="form-label">Date et Heure de fin</label>
                                <input type="datetime-local" class="form-control <?php echo isset($errors['date_fin']) ? 'is-invalid' : ''; ?>" id="date_fin" name="date_fin_formatted" value="<?php echo htmlspecialchars($formData['date_fin_formatted'] ?? ''); ?>">
                                <?php if (isset($errors['date_fin'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['date_fin']) . '</div>'; } ?>
                            </div>
                        </div>

                         <div class="row mb-3">
                             <div class="col-md-6">
                                <label for="lieu" class="form-label">Lieu</label>
                                <input type="text" class="form-control <?php echo isset($errors['lieu']) ? 'is-invalid' : ''; ?>" id="lieu" name="lieu" value="<?php echo htmlspecialchars($formData['lieu'] ?? ''); ?>">
                                <?php if (isset($errors['lieu'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['lieu']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="site_id" class="form-label">Site</label>
                                <select class="form-select <?php echo isset($errors['site_id']) ? 'is-invalid' : ''; ?>" id="site_id" name="site_id">
                                    <option value="">Selectionnez un site...</option>
                                    <?php foreach ($sites as $site_id => $site_name): ?>
                                        <option value="<?php echo $site_id; ?>" <?php echo (isset($formData['site_id']) && $formData['site_id'] !== null && (int)$formData['site_id'] === (int)$site_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($site_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['site_id'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['site_id']) . '</div>'; } ?>
                            </div>
                        </div>

                         <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="capacite_max" class="form-label">Capacité maximale</label>
                                <input type="number" min="1" class="form-control <?php echo isset($errors['capacite_max']) ? 'is-invalid' : ''; ?>" id="capacite_max" name="capacite_max" value="<?php echo htmlspecialchars($formData['capacite_max'] ?? ''); ?>">
                                <?php if (isset($errors['capacite_max'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['capacite_max']) . '</div>'; } ?>
                            </div>
                             <div class="col-md-6">
                                <label for="niveau_difficulte" class="form-label">Niveau de difficulté</label>
                                <select class="form-select <?php echo isset($errors['niveau_difficulte']) ? 'is-invalid' : ''; ?>" id="niveau_difficulte" name="niveau_difficulte">
                                    <option value="">Selectionnez un niveau...</option>
                                    <?php foreach ($niveauDifficulteOptions as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo (isset($formData['niveau_difficulte']) && $formData['niveau_difficulte'] === $value) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['niveau_difficulte'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['niveau_difficulte']) . '</div>'; } ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="materiel_necessaire" class="form-label">Matériel nécessaire</label>
                                <textarea class="form-control <?php echo isset($errors['materiel_necessaire']) ? 'is-invalid' : ''; ?>" id="materiel_necessaire" name="materiel_necessaire" rows="3"><?php echo htmlspecialchars($formData['materiel_necessaire'] ?? ''); ?></textarea>
                                 <?php if (isset($errors['materiel_necessaire'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['materiel_necessaire']) . '</div>'; } ?>
                            </div>
                        </div>

                         <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="prerequis" class="form-label">Prérequis</label>
                                <textarea class="form-control <?php echo isset($errors['prerequis']) ? 'is-invalid' : ''; ?>" id="prerequis" name="prerequis" rows="3"><?php echo htmlspecialchars($formData['prerequis'] ?? ''); ?></textarea>
                                 <?php if (isset($errors['prerequis'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['prerequis']) . '</div>'; } ?>
                            </div>
                        </div>


                        <button type="submit" class="btn btn-primary">
                             <i class="fas fa-save me-1"></i> Enregistrer les modifications
                        </button>
                        <a href="<?php echo WEBADMIN_URL; ?>/modules/conferences/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>

            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>