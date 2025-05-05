<?php
require_once '../../includes/page_functions/modules/appointments.php';

// requireRole\(ROLE_ADMIN\)

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flashMessage('Identifiant de rendez-vous invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/appointments/index.php');
}

$appointment = appointmentsGetDetails($id);
if (!$appointment) {
    flashMessage("Rendez-vous non trouvé.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/appointments/index.php');
}

$appointment['date_rdv_formatted'] = $appointment['date_rdv'] ? (new DateTime($appointment['date_rdv']))->format('Y-m-d\TH:i') : '';

$patients = appointmentsGetPatients();
$practitioners = appointmentsGetPractitioners();
$services = appointmentsGetServices();
$statuses = appointmentsGetStatuses();
$types = appointmentsGetTypes();

$errors = [];
$formData = $appointment;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleCsrfFailureRedirect($id, 'appointments', 'modification rendez-vous');
    } else {
        $submittedData = getFormData();
        $formData = array_merge($formData, $submittedData);

        $result = appointmentsSave($formData, $id);

        if ($result['success']) {
            flashMessage($result['message'], 'success');
            redirectBasedOnReferer($id, 'appointments');
        } else {
            $errors = $result['errors'] ?? ['db_error' => 'Une erreur technique est survenue lors de la mise à jour.'];
             foreach ($errors as $field => $errorMsg) {
                flashMessage(htmlspecialchars($errorMsg), 'danger');
            }
            $formData['date_rdv_formatted'] = $formData['date_rdv'] ? (new DateTime($formData['date_rdv']))->format('Y-m-d\TH:i') : '';
        }
    }
}

$pageTitle = "Modifier le rendez-vous";
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
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/view.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="tooltip" title="Voir le rendez-vous">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Informations sur le rendez-vous</div>
                <div class="card-body">
                    <form action="<?php echo WEBADMIN_URL; ?>/modules/appointments/edit.php?id=<?php echo $id; ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="personne_id" class="form-label">Patient (Salarie) <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['personne_id']) ? 'is-invalid' : ''; ?>" id="personne_id" name="personne_id" required>
                                    <option value="">Selectionnez un patient...</option>
                                    <?php foreach ($patients as $patient_id => $patient_name): ?>
                                        <option value="<?php echo $patient_id; ?>" <?php echo (isset($formData['personne_id']) && $formData['personne_id'] == $patient_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($patient_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['personne_id'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['personne_id']) . '</div>'; } ?>
                            </div>
                             <div class="col-md-6">
                                <label for="praticien_id" class="form-label">Praticien</label>
                                <select class="form-select <?php echo isset($errors['praticien_id']) ? 'is-invalid' : ''; ?>" id="praticien_id" name="praticien_id">
                                    <option value="">Aucun praticien selectionne</option>
                                    <?php foreach ($practitioners as $practitioner_id => $practitioner_name): ?>
                                        <option value="<?php echo $practitioner_id; ?>" <?php echo (isset($formData['praticien_id']) && $formData['praticien_id'] == $practitioner_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($practitioner_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                 <?php if (isset($errors['praticien_id'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['praticien_id']) . '</div>'; } ?>
                            </div>
                        </div>

                         <div class="row mb-3">
                             <div class="col-md-12">
                                <label for="prestation_id" class="form-label">Service <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['prestation_id']) ? 'is-invalid' : ''; ?>" id="prestation_id" name="prestation_id" required>
                                    <option value="">Selectionnez un service...</option>
                                    <?php foreach ($services as $service_id => $service_name): ?>
                                        <option value="<?php echo $service_id; ?>" <?php echo (isset($formData['prestation_id']) && $formData['prestation_id'] == $service_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($service_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['prestation_id'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['prestation_id']) . '</div>'; } ?>
                            </div>
                         </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="date_rdv" class="form-label">Date et Heure <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control <?php echo isset($errors['date_rdv']) ? 'is-invalid' : ''; ?>" id="date_rdv" name="date_rdv" value="<?php echo htmlspecialchars($formData['date_rdv_formatted'] ?? ''); ?>" required>
                                <?php if (isset($errors['date_rdv'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['date_rdv']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="duree" class="form-label">Duree (minutes) <span class="text-danger">*</span></label>
                                <input type="number" min="1" class="form-control <?php echo isset($errors['duree']) ? 'is-invalid' : ''; ?>" id="duree" name="duree" value="<?php echo htmlspecialchars($formData['duree'] ?? ''); ?>" required>
                                <?php if (isset($errors['duree'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['duree']) . '</div>'; } ?>
                            </div>
                        </div>

                         <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="type_rdv" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['type_rdv']) ? 'is-invalid' : ''; ?>" id="type_rdv" name="type_rdv" required>
                                     <?php foreach ($types as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo (isset($formData['type_rdv']) && $formData['type_rdv'] === $value) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['type_rdv'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['type_rdv']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-4">
                                <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['statut']) ? 'is-invalid' : ''; ?>" id="statut" name="statut" required>
                                    <?php foreach ($statuses as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo (isset($formData['statut']) && $formData['statut'] === $value) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                 <?php if (isset($errors['statut'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['statut']) . '</div>'; } ?>
                            </div>
                             <div class="col-md-4">
                                <label for="lieu" class="form-label">Lieu</label>
                                <input type="text" class="form-control <?php echo isset($errors['lieu']) ? 'is-invalid' : ''; ?>" id="lieu" name="lieu" value="<?php echo htmlspecialchars($formData['lieu'] ?? ''); ?>">
                                <?php if (isset($errors['lieu'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['lieu']) . '</div>'; } ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control <?php echo isset($errors['notes']) ? 'is-invalid' : ''; ?>" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($formData['notes'] ?? ''); ?></textarea>
                                <?php if (isset($errors['notes'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['notes']) . '</div>'; } ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                             <i class="fas fa-save me-1"></i> Enregistrer les modifications
                        </button>
                        <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
