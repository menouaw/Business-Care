<?php
require_once '../../includes/init.php'; 
require_once '../../includes/page_functions/modules/quotes.php'; 

requireRole(ROLE_ADMIN);

// Determine if it's an edit or add operation
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditing = ($id > 0);

// Initialize variables
$quote = null;
$lines = [];
$formData = [
    'entreprise_id' => null,
    'service_id' => null,
    'nombre_salaries_estimes' => null,
    'date_creation' => date('Y-m-d'),
    'date_validite' => date('Y-m-d', strtotime('+30 days')),
    'statut' => QUOTE_STATUS_PENDING,
    'conditions_paiement' => null,
    'delai_paiement' => null,
    'est_personnalise' => false,
    'notes_negociation' => null
];
$errors = [];

// Fetch data for editing
if ($isEditing) {
    $result = quotesGetDetails($id);
    if (!$result) {
        flashMessage("Devis non trouvé.", 'danger');
        redirectTo(WEBADMIN_URL . '/modules/quotes/index.php');
    }
    $quote = $result['quote'];
    $lines = $result['lines'];
    $formData = array_merge($formData, $quote); // Pre-fill form with existing data
}

// Fetch data for dropdowns
$companies = quotesGetCompanies();
$services = quotesGetServices();
$prestations = quotesGetPrestations();
$statuses = quotesGetStatuses();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
         // Using a generic message for CSRF failure on POST
        flashMessage('Erreur de sécurité ou session expirée. Veuillez soumettre à nouveau le formulaire.', 'danger');
        // Re-populate form data from POST to avoid data loss
        $formData = array_merge($formData, $_POST);
        // Re-populate lines data if possible (tricky without JS state preservation)
        // This part might require client-side handling for better UX if validation fails
        $postedLines = [];
         if (isset($_POST['prestation_id']) && is_array($_POST['prestation_id'])) {
            foreach ($_POST['prestation_id'] as $index => $prestationId) {
                 if (!empty($prestationId) && isset($_POST['quantite'][$index]) && $_POST['quantite'][$index] > 0) {
                    $postedLines[] = [
                        'prestation_id' => (int)$prestationId,
                        'quantite' => (int)$_POST['quantite'][$index],
                        'description_specifique' => $_POST['description_specifique'][$index] ?? null
                    ];
                }
            }
        }
        // We reassign $lines here for form repopulation, but these are not yet fully validated/priced
        $lines = $postedLines; 

    } else {
        // Call the handler function which includes validation and saving
        $postData = $_POST; // Pass the raw POST data
        $result = quotesHandlePostRequest($postData, $id);

        if ($result['success']) {
            flashMessage($result['message'], 'success');
            redirectTo(WEBADMIN_URL . '/modules/quotes/view.php?id=' . $result['quoteId']);
        } else {
            $errors = $result['errors'] ?? ['db_error' => 'Une erreur inconnue est survenue lors de la sauvegarde.'];
            // Re-populate form data from POST
            $formData = array_merge($formData, $postData);
            // Re-populate lines (same caveat as above)
             $postedLines = [];
             if (isset($_POST['prestation_id']) && is_array($_POST['prestation_id'])) {
                foreach ($_POST['prestation_id'] as $index => $prestationId) {
                     if (!empty($prestationId) && isset($_POST['quantite'][$index]) && $_POST['quantite'][$index] > 0) {
                        $postedLines[] = [
                            'prestation_id' => (int)$prestationId,
                            'quantite' => (int)$_POST['quantite'][$index],
                            'description_specifique' => $_POST['description_specifique'][$index] ?? null
                        ];
                    }
                }
            }
            $lines = $postedLines; 
            // Display errors as flash messages
            foreach ($errors as $error) {
                 flashMessage($error, 'danger');
            }
        }
    }
}

// Set page title based on operation
$pageTitle = $isEditing ? "Modifier le devis #" . $id : "Créer un nouveau devis";
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
                    <?php if ($isEditing): ?>
                        <a href="<?php echo WEBADMIN_URL; ?>/modules/quotes/view.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="tooltip" title="Voir le devis">
                            <i class="fas fa-eye"></i> Voir
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/quotes/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retour à la liste">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <form action="<?php echo WEBADMIN_URL; ?>/modules/quotes/edit.php<?php echo $isEditing ? '?id=' . $id : ''; ?>" method="POST" id="quote-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                <div class="card mb-4">
                    <div class="card-header">Informations Générales</div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="entreprise_id" class="form-label">Entreprise Cliente <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['entreprise_id']) ? 'is-invalid' : ''; ?>" id="entreprise_id" name="entreprise_id" required>
                                    <option value="">Sélectionnez une entreprise...</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>" <?php echo (isset($formData['entreprise_id']) && $formData['entreprise_id'] == $company['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($company['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div class="col-md-6">
                                <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['statut']) ? 'is-invalid' : ''; ?>" id="statut" name="statut" required>
                                    <?php foreach ($statuses as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo (isset($formData['statut']) && $formData['statut'] === $s) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(ucfirst($s)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="date_creation" class="form-label">Date de Création <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo isset($errors['date_creation']) ? 'is-invalid' : ''; ?>" id="date_creation" name="date_creation" value="<?php echo htmlspecialchars($formData['date_creation'] ?? ''); ?>" required>
                            </div>
                             <div class="col-md-6">
                                <label for="date_validite" class="form-label">Date de Validité <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo isset($errors['date_validite']) ? 'is-invalid' : ''; ?>" id="date_validite" name="date_validite" value="<?php echo htmlspecialchars($formData['date_validite'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                             <div class="col-md-6">
                                <label for="delai_paiement" class="form-label">Délai Paiement (jours)</label>
                                <input type="number" min="0" class="form-control" id="delai_paiement" name="delai_paiement" value="<?php echo htmlspecialchars($formData['delai_paiement'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="conditions_paiement" class="form-label">Conditions de Paiement</label>
                                <input type="text" class="form-control" id="conditions_paiement" name="conditions_paiement" value="<?php echo htmlspecialchars($formData['conditions_paiement'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                 <div class="card mb-4">
                    <div class="card-header">Détails du Devis (Choisir Service OU Prestations spécifiques)</div>
                    <div class="card-body">
                         <div class="mb-3 form-check">
                             <input type="hidden" name="est_personnalise" value="0"> <!-- Default value -->
                            <input type="checkbox" class="form-check-input" id="est_personnalise" name="est_personnalise" value="1" <?php echo (isset($formData['est_personnalise']) && $formData['est_personnalise']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="est_personnalise">Devis Personnalisé (négocié)</label>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="service_id" class="form-label">Basé sur un Service Standard</label>
                                <select class="form-select" id="service_id" name="service_id">
                                    <option value="">-- Aucun (utiliser prestations spécifiques) --</option>
                                     <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" <?php echo (isset($formData['service_id']) && $formData['service_id'] == $service['id']) ? 'selected' : ''; ?> data-rate="<?php echo $service['tarif_annuel_par_salarie']; ?>">
                                            <?php echo htmlspecialchars($service['nom']); ?> (<?php echo formatMoney($service['tarif_annuel_par_salarie']); ?>/salarie/an HT)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Si sélectionné, le prix sera basé sur ce service et le nombre de salariés.</small>
                            </div>
                             <div class="col-md-6">
                                <label for="nombre_salaries_estimes" class="form-label">Nombre Salariés Estimés (requis si service choisi)</label>
                                <input type="number" min="1" class="form-control" id="nombre_salaries_estimes" name="nombre_salaries_estimes" value="<?php echo htmlspecialchars($formData['nombre_salaries_estimes'] ?? ''); ?>">
                            </div>
                        </div>
                        
                         <div class="mb-3 <?php echo (isset($formData['est_personnalise']) && $formData['est_personnalise']) ? '' : 'd-none'; ?>" id="notes-personnalise-group">
                            <label for="notes_negociation" class="form-label">Notes Internes (Négociation)</label>
                            <textarea class="form-control" id="notes_negociation" name="notes_negociation" rows="3"><?php echo htmlspecialchars($formData['notes_negociation'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Visible uniquement en interne.</small>
                        </div>
                        
                        <hr>
                        <h5 class="mb-3">OU Lignes de Prestations Spécifiques / Additionnelles</h5>

                        <div id="prestation-lines-container">
                            <?php if (empty($lines)): ?>
                                <!-- Start with one empty line if none exist -->
                                <div class="row prestation-line mb-2">
                                    <div class="col-md-4">
                                        <label class="visually-hidden">Prestation</label>
                                        <select name="prestation_id[]" class="form-select form-select-sm prestation-select">
                                            <option value="">Sélectionnez une prestation...</option>
                                            <?php foreach ($prestations as $p): ?>
                                                <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['prix']; ?>"><?php echo htmlspecialchars($p['nom']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="visually-hidden">Quantité</label>
                                        <input type="number" name="quantite[]" class="form-control form-control-sm line-quantity" placeholder="Qté" min="1" value="1">
                                    </div>
                                     <div class="col-md-4">
                                         <label class="visually-hidden">Description Spécifique</label>
                                        <input type="text" name="description_specifique[]" class="form-control form-control-sm" placeholder="Description spécifique (optionnel)">
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button type="button" class="btn btn-sm btn-danger remove-line-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($lines as $line): ?>
                                     <div class="row prestation-line mb-2">
                                        <div class="col-md-4">
                                            <label class="visually-hidden">Prestation</label>
                                            <select name="prestation_id[]" class="form-select form-select-sm prestation-select">
                                                <option value="">Sélectionnez une prestation...</option>
                                                <?php foreach ($prestations as $p): ?>
                                                    <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['prix']; ?>" <?php echo (isset($line['prestation_id']) && $line['prestation_id'] == $p['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($p['nom']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="visually-hidden">Quantité</label>
                                            <input type="number" name="quantite[]" class="form-control form-control-sm line-quantity" placeholder="Qté" min="1" value="<?php echo htmlspecialchars($line['quantite'] ?? '1'); ?>">
                                        </div>
                                         <div class="col-md-4">
                                             <label class="visually-hidden">Description Spécifique</label>
                                            <input type="text" name="description_specifique[]" class="form-control form-control-sm" placeholder="Description spécifique (optionnel)" value="<?php echo htmlspecialchars($line['description_specifique'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button type="button" class="btn btn-sm btn-danger remove-line-btn">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                         <button type="button" class="btn btn-sm btn-outline-primary" id="add-line-btn">
                            <i class="fas fa-plus"></i> Ajouter une ligne de prestation
                        </button>

                    </div>
                 </div>

                 <div class="text-end">
                      <button type="submit" class="btn btn-primary">
                         <i class="fas fa-save me-1"></i> <?php echo $isEditing ? 'Enregistrer les modifications' : 'Créer le devis'; ?>
                     </button>
                     <a href="<?php echo WEBADMIN_URL; ?>/modules/quotes/index.php" class="btn btn-secondary">Annuler</a>
                 </div>

            </form>

            <!-- Template Row for JS -->
            <div id="prestation-line-template" class="row prestation-line mb-2" style="display: none;">
                 <div class="col-md-4">
                    <label class="visually-hidden">Prestation</label>
                    <select name="prestation_id[]" class="form-select form-select-sm prestation-select">
                        <option value="">Sélectionnez une prestation...</option>
                        <?php foreach ($prestations as $p): ?>
                            <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['prix']; ?>"><?php echo htmlspecialchars($p['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="visually-hidden">Quantité</label>
                    <input type="number" name="quantite[]" class="form-control form-control-sm line-quantity" placeholder="Qté" min="1" value="1">
                </div>
                 <div class="col-md-4">
                     <label class="visually-hidden">Description Spécifique</label>
                    <input type="text" name="description_specifique[]" class="form-control form-control-sm" placeholder="Description spécifique (optionnel)">
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-sm btn-danger remove-line-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>


            <?php include_once '../../templates/footer.php'; ?>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const container = document.getElementById('prestation-lines-container');
                    const addButton = document.getElementById('add-line-btn');
                    const template = document.getElementById('prestation-line-template');
                    const personnaliseCheckbox = document.getElementById('est_personnalise');
                    const notesGroup = document.getElementById('notes-personnalise-group');

                    // Function to add remove listener
                    const addRemoveListener = (button) => {
                        button.addEventListener('click', function() {
                            // Only remove if more than one line exists or if container is not empty
                             if (container.querySelectorAll('.prestation-line').length > 1) {
                                this.closest('.prestation-line').remove();
                             } else {
                                 // Optionally clear the first line instead of removing? Or just leave it.
                                 // For simplicity, we allow removing the last line. The backend validation
                                 // ensures at least a service or a line is present.
                                 this.closest('.prestation-line').remove();
                             }
                        });
                    };

                    // Add line functionality
                    addButton.addEventListener('click', function() {
                        const clone = template.cloneNode(true);
                        clone.removeAttribute('id');
                        clone.removeAttribute('style'); // Make it visible
                        container.appendChild(clone);
                        // Add listener to the new remove button
                        addRemoveListener(clone.querySelector('.remove-line-btn'));
                    });

                    // Add listeners to existing remove buttons
                    container.querySelectorAll('.remove-line-btn').forEach(addRemoveListener);

                    // Toggle notes field based on personnalise checkbox
                     personnaliseCheckbox.addEventListener('change', function() {
                        if (this.checked) {
                            notesGroup.classList.remove('d-none');
                        } else {
                            notesGroup.classList.add('d-none');
                        }
                    });
                    // Initial check
                     if (personnaliseCheckbox.checked) {
                        notesGroup.classList.remove('d-none');
                    }

                    // Optional: Add logic to disable/enable service vs specific lines based on selection
                    // (This can get complex depending on exact desired UX)
                });
            </script>
        </main>
    </div>
</div>

</rewritten_file>
