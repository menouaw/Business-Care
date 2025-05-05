<?php
require_once '../../includes/page_functions/modules/contracts.php';

// requireRole(ROLE_ADMIN)

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flashMessage('Identifiant de contrat invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/contracts/index.php');
}

$contract = contractsGetDetails($id);
if (!$contract) {
    flashMessage("Contrat non trouvé.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/contracts/index.php');
}

$entreprises = contractsGetEntreprises(); 
$services = contractsGetServices();

$errors = [];
$formData = $contract;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleCsrfFailureRedirect($id, 'contracts', 'modification contrat');
    } else {
        $submittedData = getFormData();
        $formData = array_merge($formData, $submittedData); 

        $result = contractsSave($formData, $id); 

        if ($result['success']) {
            flashMessage($result['message'], 'success');
            redirectBasedOnReferer($id, 'contracts');
        } else {
            $errors = $result['errors'] ?? ['db_error' => 'Une erreur technique est survenue lors de la mise à jour.'];
                foreach ($errors as $error) {
                flashMessage($error, 'danger');
            }
        }
    }
}

$pageTitle = "Modifier le contrat";
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
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/view.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="tooltip" title="Voir le contrat">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                     <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Informations sur le contrat</div>
                <div class="card-body">
                    <form action="<?php echo WEBADMIN_URL; ?>/modules/contracts/edit.php?id=<?php echo $id; ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="entreprise_id" class="form-label">Entreprise <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['entreprise_id']) ? 'is-invalid' : ''; ?>" id="entreprise_id" name="entreprise_id" required>
                                    <option value="">Selectionnez une entreprise...</option>
                                    <?php foreach ($entreprises as $e): ?>
                                        <option value="<?php echo $e['id']; ?>" <?php echo (isset($formData['entreprise_id']) && $formData['entreprise_id'] == $e['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($e['nom']); ?> (ID: <?php echo $e['id']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['entreprise_id'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['entreprise_id']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="service_id" class="form-label">Service (Type de contrat) <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['service_id']) ? 'is-invalid' : ''; ?>" id="service_id" name="service_id" required>
                                    <option value="">Selectionnez un service...</option>
                                    <?php foreach ($services as $s): ?>
                                        <option value="<?php echo $s['id']; ?>" <?php echo (isset($formData['service_id']) && $formData['service_id'] == $s['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s['type']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['service_id'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['service_id']) . '</div>'; } ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="date_debut" class="form-label">Date de debut <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo isset($errors['date_debut']) ? 'is-invalid' : ''; ?>" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($formData['date_debut'] ?? ''); ?>" required>
                                <?php if (isset($errors['date_debut'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['date_debut']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-6">
                                <label for="date_fin" class="form-label">Date de fin</label>
                                <input type="date" class="form-control <?php echo isset($errors['date_fin']) ? 'is-invalid' : ''; ?>" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($formData['date_fin'] ?? ''); ?>">
                                <?php if (isset($errors['date_fin'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['date_fin']) . '</div>'; } ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="nombre_salaries" class="form-label">Nombre de salariés (contractuel)</label>
                                <input type="number" min="0" class="form-control <?php echo isset($errors['nombre_salaries']) ? 'is-invalid' : ''; ?>" id="nombre_salaries" name="nombre_salaries" value="<?php echo htmlspecialchars($formData['nombre_salaries'] ?? ''); ?>">
                                <?php if (isset($errors['nombre_salaries'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['nombre_salaries']) . '</div>'; } ?>
                            </div>
                            <div class="col-md-4">
                                <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['statut']) ? 'is-invalid' : ''; ?>" id="statut" name="statut" required>
                                    <?php foreach (CONTRACT_STATUSES as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo (isset($formData['statut']) && $formData['statut'] === $s) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(htmlspecialchars($s)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['statut'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['statut']) . '</div>'; } ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="conditions_particulieres" class="form-label">Conditions particulieres</label>
                                <textarea class="form-control <?php echo isset($errors['conditions_particulieres']) ? 'is-invalid' : ''; ?>" id="conditions_particulieres" name="conditions_particulieres" rows="3"><?php echo htmlspecialchars($formData['conditions_particulieres'] ?? ''); ?></textarea>
                                <?php if (isset($errors['conditions_particulieres'])) { echo '<div class="invalid-feedback">' . htmlspecialchars($errors['conditions_particulieres']) . '</div>'; } ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                             <i class="fas fa-save me-1"></i> Enregistrer les modifications
                        </button>
                        <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>


