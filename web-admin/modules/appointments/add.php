<?php
require_once '../../includes/page_functions/modules/appointments.php';

requireRole(ROLE_ADMIN);

$pageTitle = "Ajouter un rendez-vous";

$patients = appointmentsGetPatients();
$practitioners = appointmentsGetPractitioners();
$services = appointmentsGetServices();
$statuses = appointmentsGetStatuses();
$types = appointmentsGetTypes();

$errors = [];
$formData = [
    'personne_id' => '',
    'prestation_id' => '',
    'praticien_id' => '',
    'date_rdv' => '', 
    'duree' => '',
    'lieu' => '',
    'type_rdv' => 'presentiel', 
    'statut' => 'planifie', 
    'notes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleCsrfFailureRedirect(0, 'appointments', 'ajout rendez-vous');
    } else {
        $submittedData = getFormData();
        $formData = array_merge($formData, $submittedData);
        
        $formData['statut'] = $submittedData['statut'] ?? 'planifie';

        $result = appointmentsSave($formData, 0);

        if ($result['success']) {
            flashMessage($result['message'] ?? 'Rendez-vous ajouté avec succès !', 'success');
            redirectTo(WEBADMIN_URL . '/modules/appointments/view.php?id=' . $result['newId']);
        } else {
            $errors = $result['errors'] ?? ['db_error' => 'Une erreur technique est survenue lors de l\'ajout.'];
            logSystemActivity('appointment_add_failure', '[ERROR] Échec ajout RDV: ' . implode(', ', $errors));
             foreach ($errors as $field => $errorMsg) {
                flashMessage(htmlspecialchars($errorMsg), 'danger');
            }
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
                 <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>

             <?php echo displayFlashMessages(); ?>

            <div class="card">
                 <div class="card-header">
                    Informations sur le rendez-vous
                </div>
                <div class="card-body">
                     <form action="<?php echo WEBADMIN_URL; ?>/modules/appointments/add.php" method="post" novalidate>
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
                                <input type="datetime-local" class="form-control <?php echo isset($errors['date_rdv']) ? 'is-invalid' : ''; ?>" id="date_rdv" name="date_rdv" value="<?php echo htmlspecialchars($formData['date_rdv'] ?? ''); ?>" required>
                                <?php if (isset($errors['date_rdv'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['date_rdv']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="duree" class="form-label">Duree (minutes) <span class="text-danger">*</span></label>
                                <input type="number" min="1" class="form-control <?php echo isset($errors['duree']) ? 'is-invalid' : ''; ?>" id="duree" name="duree" value="<?php echo htmlspecialchars($formData['duree'] ?? ''); ?>" required placeholder="Ex: 60">
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
                                <input type="text" class="form-control <?php echo isset($errors['lieu']) ? 'is-invalid' : ''; ?>" id="lieu" name="lieu" value="<?php echo htmlspecialchars($formData['lieu'] ?? ''); ?>" placeholder="Ex: Bureau 101, Salle Zen">
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

                        <hr>

                        <button type="submit" class="btn btn-primary">
                           <i class="fas fa-plus-circle me-1"></i> Ajouter le rendez-vous
                        </button>
                         <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/index.php" class="btn btn-secondary">
                            Annuler
                         </a>
                    </form>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
