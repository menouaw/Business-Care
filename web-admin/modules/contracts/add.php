<?php
require_once '../../includes/page_functions/modules/contracts.php'; 

requireRole(ROLE_ADMIN);

$pageTitle = "Ajouter un contrat";
$errors = [];
$contractData = [
    'entreprise_id' => '',
    'service_id' => '',
    'date_debut' => '',
    'date_fin' => '',
    'nombre_salaries' => '',
    'statut' => DEFAULT_CONTRACT_STATUS, 
    'conditions_particulieres' => ''
];

$entreprises = contractsGetEntreprises();
$services = contractsGetServices();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleCsrfFailureRedirect(0, 'contracts', 'ajout contrat'); 
    } else {
        $submittedData = getFormData(); 
        $contractData = array_merge($contractData, $submittedData);
        $contractData['statut'] = $submittedData['statut'] ?? DEFAULT_CONTRACT_STATUS;

        $saveResult = contractsSave($contractData, 0); 

        if ($saveResult['success']) {
            flashMessage($saveResult['message'] ?? 'Contrat ajouté avec succès !', 'success');
            redirectTo(WEBADMIN_URL . '/modules/contracts/view.php?id=' . $saveResult['newId']); 
        } else {
            $errors = $saveResult['errors'] ?? ['Une erreur technique est survenue lors de l\'ajout.'];
            $logMessage = '[ERROR] Échec ajout contrat: ' . (is_array($errors) ? implode(', ', $errors) : $errors);
            logSystemActivity('contract_add_failure', $logMessage);
            flashMessage($errors, 'danger'); 
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
                 <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>

             <?php echo displayFlashMessages(); ?>

            <div class="card">
                 <div class="card-header">
                    Informations sur le contrat
                </div>
                <div class="card-body">
                     <form action="<?php echo WEBADMIN_URL; ?>/modules/contracts/add.php" method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                        <div class="mb-3">
                            <label for="entreprise_id" class="form-label">Entreprise <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo isset($errors['entreprise_id']) ? 'is-invalid' : ''; ?>" id="entreprise_id" name="entreprise_id" required>
                                <option value="">-- Sélectionner une entreprise --</option>
                                <?php foreach ($entreprises as $entreprise): ?>
                                <option value="<?php echo $entreprise['id']; ?>" <?php echo ($contractData['entreprise_id'] == $entreprise['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($entreprise['nom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                             <?php if (isset($errors['entreprise_id'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['entreprise_id']).'</div>'; } ?>
                        </div>

                        <div class="mb-3">
                            <label for="service_id" class="form-label">Service (Type de contrat) <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo isset($errors['service_id']) ? 'is-invalid' : ''; ?>" id="service_id" name="service_id" required>
                                <option value="">-- Sélectionner un service --</option>
                                <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" <?php echo ($contractData['service_id'] == $service['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['nom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                             <?php if (isset($errors['service_id'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['service_id']).'</div>'; } ?>
                        </div>

                         <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo isset($errors['date_debut']) ? 'is-invalid' : ''; ?>" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($contractData['date_debut']); ?>" required>
                                 <?php if (isset($errors['date_debut'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['date_debut']).'</div>'; } ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_fin" class="form-label">Date de fin</label>
                                <input type="date" class="form-control <?php echo isset($errors['date_fin']) ? 'is-invalid' : ''; ?>" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($contractData['date_fin']); ?>">
                                 <?php if (isset($errors['date_fin'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['date_fin']).'</div>'; } ?>
                            </div>
                        </div>
                        
                         <div class="row">
                             <div class="col-md-6 mb-3">
                                <label for="nombre_salaries" class="form-label">Nombre de salariés couverts</label>
                                <input type="number" min="0" class="form-control <?php echo isset($errors['nombre_salaries']) ? 'is-invalid' : ''; ?>" id="nombre_salaries" name="nombre_salaries" placeholder="50" value="<?php echo htmlspecialchars($contractData['nombre_salaries']); ?>">
                                <?php if (isset($errors['nombre_salaries'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['nombre_salaries']).'</div>'; } ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo isset($errors['statut']) ? 'is-invalid' : ''; ?>" id="statut" name="statut" required>
                                <?php foreach (CONTRACT_STATUSES as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo ($contractData['statut'] === $status) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                             <?php if (isset($errors['statut'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['statut']).'</div>'; } ?>
                        </div>

                        <div class="mb-3">
                            <label for="conditions_particulieres" class="form-label">Conditions particulières</label>
                            <textarea class="form-control" id="conditions_particulieres" name="conditions_particulieres" rows="3"><?php echo htmlspecialchars($contractData['conditions_particulieres']); ?></textarea>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">
                           <i class="fas fa-plus-circle me-1"></i> Ajouter le contrat
                        </button>
                         <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/index.php" class="btn btn-secondary">
                            Annuler
                         </a>
                    </form>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>

</rewritten_file>
