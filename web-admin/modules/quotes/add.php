<?php
require_once '../../includes/page_functions/modules/quotes.php';

// requireRole(ROLE_ADMIN)

 
$id = 0;  
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

 
$lines[] = ['prestation_id' => '', 'quantite' => 1, 'description_specifique' => null];

 
$companies = quotesGetCompanies();
$services = quotesGetServices();
$prestations = quotesGetPrestations();
$statuses = quotesGetStatuses();

 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

     
    if (isset($_POST['add_line'])) {
         
        $formData = array_merge($formData, $_POST);
        $lines = [];
        if (isset($_POST['prestation_id']) && is_array($_POST['prestation_id'])) {
            foreach ($_POST['prestation_id'] as $index => $prestationId) {
                 
                $lines[] = [
                    'prestation_id' => $prestationId,  
                    'quantite' => $_POST['quantite'][$index] ?? 1,
                    'description_specifique' => $_POST['description_specifique'][$index] ?? null
                ];
            }
        }
         
        $lines[] = ['prestation_id' => '', 'quantite' => 1, 'description_specifique' => null];
         

     
    } elseif (isset($_POST['delete_line']) && is_array($_POST['delete_line'])) {
        $deleteIndex = key($_POST['delete_line']);  

         
        $formData = array_merge($formData, $_POST);
        $currentLines = [];
         if (isset($_POST['prestation_id']) && is_array($_POST['prestation_id'])) {
            foreach ($_POST['prestation_id'] as $index => $prestationId) {
                 if ($index != $deleteIndex) {  
                    $currentLines[] = [
                        'prestation_id' => $prestationId,
                        'quantite' => $_POST['quantite'][$index] ?? 1,
                        'description_specifique' => $_POST['description_specifique'][$index] ?? null
                    ];
                }
            }
        }
        $lines = $currentLines;  

         
        if (empty($lines)) {
             $lines[] = ['prestation_id' => '', 'quantite' => 1, 'description_specifique' => null];
        }
          

     
    } else {
         
        if (!validateToken($_POST['csrf_token'] ?? '')) {
            flashMessage('Erreur de sécurité ou session expirée. Veuillez soumettre à nouveau le formulaire.', 'danger');
            $formData = array_merge($formData, $_POST);
             
            $postedLines = [];
             if (isset($_POST['prestation_id']) && is_array($_POST['prestation_id'])) {
                foreach ($_POST['prestation_id'] as $index => $prestationId) {
                      
                    $postedLines[] = [
                        'prestation_id' => $prestationId,
                        'quantite' => $_POST['quantite'][$index] ?? 1,
                        'description_specifique' => $_POST['description_specifique'][$index] ?? null
                    ];
                }
            }
              
             if (empty($postedLines)) {
                 $postedLines[] = ['prestation_id' => '', 'quantite' => 1, 'description_specifique' => null];
            }
            $lines = $postedLines;
        } else {
             
            $postData = $_POST;
            $result = quotesHandlePostRequest($postData, $id);  

            if ($result['success']) {
                flashMessage($result['message'], 'success');
                redirectTo(WEBADMIN_URL . '/modules/quotes/view.php?id=' . $result['quoteId']);
            } else {
                $errors = $result['errors'] ?? ['db_error' => 'Une erreur inconnue est survenue lors de la sauvegarde.'];
                $formData = array_merge($formData, $postData);
                 
                 $postedLines = [];
                 if (isset($_POST['prestation_id']) && is_array($_POST['prestation_id'])) {
                    foreach ($_POST['prestation_id'] as $index => $prestationId) {
                          
                         $postedLines[] = [
                            'prestation_id' => $prestationId,
                            'quantite' => $_POST['quantite'][$index] ?? 1,
                            'description_specifique' => $_POST['description_specifique'][$index] ?? null
                        ];
                    }
                }
                  
                 if (empty($postedLines)) {
                     $postedLines[] = ['prestation_id' => '', 'quantite' => 1, 'description_specifique' => null];
                }
                $lines = $postedLines;
                foreach ($errors as $error) {
                     flashMessage($error, 'danger');
                }
            }
        }
    }
}

 
$pageTitle = "Ajouter un devis";
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
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/quotes/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retour à la liste">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <form action="<?php echo WEBADMIN_URL; ?>/modules/quotes/add.php" method="POST" id="quote-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                <div class="card mb-4">
                    <div class="card-header">Informations</div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="entreprise_id" class="form-label">Entreprise<span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['entreprise_id']) ? 'is-invalid' : ''; ?>" id="entreprise_id" name="entreprise_id" required>
                                    <option value="">Sélectionnez une entreprise...</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>" <?php echo (isset($formData['entreprise_id']) && $formData['entreprise_id'] == $company['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($company['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['entreprise_id'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['entreprise_id']).'</div>'; } ?>
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
                                <?php if (isset($errors['statut'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['statut']).'</div>'; } ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="date_creation" class="form-label">Date de création <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo isset($errors['date_creation']) ? 'is-invalid' : ''; ?>" id="date_creation" name="date_creation" value="<?php echo htmlspecialchars($formData['date_creation'] ?? ''); ?>" required>
                                <?php if (isset($errors['date_creation'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['date_creation']).'</div>'; } ?>
                            </div>
                             <div class="col-md-6">
                                <label for="date_validite" class="form-label">Date de validité <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo isset($errors['date_validite']) ? 'is-invalid' : ''; ?>" id="date_validite" name="date_validite" value="<?php echo htmlspecialchars($formData['date_validite'] ?? ''); ?>" required>
                                <?php if (isset($errors['date_validite'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['date_validite']).'</div>'; } ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                             <div class="col-md-6">
                                <label for="delai_paiement" class="form-label">Délai paiement (jours)</label>
                                <input type="number" min="0" class="form-control" id="delai_paiement" name="delai_paiement" value="<?php echo htmlspecialchars($formData['delai_paiement'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="conditions_paiement" class="form-label">Conditions de paiement</label>
                                <input type="text" class="form-control" id="conditions_paiement" name="conditions_paiement" value="<?php echo htmlspecialchars($formData['conditions_paiement'] ?? ''); ?>" placeholder="Ex: 30 jours net">
                            </div>
                        </div>
                    </div>
                </div>

                 <div class="card mb-4">
                    <div class="card-header">Détails</div>
                    <div class="card-body">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="service_id" class="form-label">Pack</label>
                                <select class="form-select <?php echo isset($errors['service_id']) ? 'is-invalid' : ''; ?>" id="service_id" name="service_id">
                                    <option value="">-- Aucun (utiliser prestations spécifiques) --</option>
                                     <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" <?php echo (isset($formData['service_id']) && $formData['service_id'] == $service['id']) ? 'selected' : ''; ?> data-rate="<?php echo $service['tarif_annuel_par_salarie']; ?>">
                                            <?php echo htmlspecialchars($service['type']); ?> (<?php echo formatMoney($service['tarif_annuel_par_salarie']); ?>/salarie/an HT)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['service_id'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['service_id']).'</div>'; } ?>
                                <small class="form-text text-muted">Si sélectionné, le prix sera basé sur ce service et le nombre de salariés.</small>
                            </div>
                             <div class="col-md-6">
                                <label for="nombre_salaries_estimes" class="form-label">Salariés</label>
                                <input type="number" min="1" class="form-control <?php echo isset($errors['nombre_salaries_estimes']) ? 'is-invalid' : ''; ?>" id="nombre_salaries_estimes" name="nombre_salaries_estimes" value="<?php echo htmlspecialchars($formData['nombre_salaries_estimes'] ?? ''); ?>">
                                <?php if (isset($errors['nombre_salaries_estimes'])) { echo '<div class="invalid-feedback">'.htmlspecialchars($errors['nombre_salaries_estimes']).'</div>'; } ?>
                            </div>
                        </div>
                        
                         <div class="mb-2" id="notes-personnalise-group">
                            <label for="notes_negociation" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes_negociation" name="notes_negociation" rows="3"><?php echo htmlspecialchars($formData['notes_negociation'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Visible uniquement en interne.</small>
                        </div>
                        
                        <hr>
                        <h5 class="mb-3"> Prestations additionnelles</5>

                        <div id="prestation-lines-container">
                            <?php foreach ($lines as $index => $line): ?>
                                 <div class="row prestation-line mb-2">
                                    <div class="col-md-5">
                                        <label class="visually-hidden">Prestation</label>
                                        <select name="prestation_id[]" class="form-select form-select-sm prestation-select">
                                            <option value="">Sélectionnez une prestation...</option>
                                            <?php foreach ($prestations as $p): ?>
                                                <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['prix']; ?>" <?php echo (isset($line['prestation_id']) && $line['prestation_id'] == $p['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($p['nom']); ?> (<?php echo formatMoney($p['prix']); ?> HT)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="visually-hidden">Quantité</label>
                                        <input type="number" name="quantite[]" class="form-control form-control-sm line-quantity" placeholder="Qté" min="1" value="<?php echo htmlspecialchars($line['quantite'] ?? '1'); ?>">
                                    </div>
                                     <div class="col-md-5">
                                         <label class="visually-hidden">Description Spécifique</label>
                                        <input type="text" name="description_specifique[<?php echo $index; ?>]" class="form-control form-control-sm" placeholder="Description spécifique (optionnel)" value="<?php echo htmlspecialchars($line['description_specifique'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <!-- Delete button submits the form -->
                                        <button type="submit" name="delete_line[<?php echo $index; ?>]" class="btn btn-sm btn-danger" title="Supprimer cette ligne">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Add Line button submits the form -->
                        <button type="submit" name="add_line" class="btn btn-sm btn-outline-primary mt-2">
                            <i class="fas fa-plus"></i> Ajouter une ligne de prestation
                        </button>

                    </div>
                 </div>

                 <div class="text-end">
                      <button type="submit" class="btn btn-primary">
                         <i class="fas fa-plus-circle me-1"></i> Créer le devis
                     </button>
                     <a href="<?php echo WEBADMIN_URL; ?>/modules/quotes/index.php" class="btn btn-secondary">Annuler</a>
                 </div>

            </form>

            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
