<?php
require_once '../../includes/page_functions/modules/providers.php';

// requireRole(ROLE_ADMIN)

$habilitation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$provider_id = isset($_GET['provider_id']) ? (int)$_GET['provider_id'] : 0;

if ($habilitation_id <= 0 || $provider_id <= 0) {
    flashMessage('Identifiants invalides.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/providers/index.php'); 
}

$habilitation = getHabilitationDetails($habilitation_id);
$provider = getProviderDetails($provider_id);

if (!$habilitation || !$provider || $habilitation['prestataire_id'] != $provider_id) {
    flashMessage("Habilitation ou prestataire non trouvé, ou l'habilitation n'appartient pas à ce prestataire.", 'danger');
    $redirectUrl = $provider_id > 0 ? WEBADMIN_URL . '/modules/providers/view.php?id=' . $provider_id : WEBADMIN_URL . '/modules/providers/index.php';
    redirectTo($redirectUrl);
}

$pageTitle = "Modifier l'habilitation pour " . htmlspecialchars($provider['prenom'] . ' ' . $provider['nom']);
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
                     <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/view.php?id=<?php echo $provider_id; ?>&tab=habilitations" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la vue du prestataire">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-edit me-1"></i> Détails de l'habilitation
                </div>
                <div class="card-body">
                    <form id="editHabilitationForm" method="post" action="<?php echo WEBADMIN_URL; ?>/modules/providers/actions.php">
                        <input type="hidden" name="action" value="edit_habilitation">
                        <input type="hidden" name="habilitation_id" value="<?php echo $habilitation['id']; ?>">
                        <input type="hidden" name="provider_id" value="<?php echo $provider_id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_hab_type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_hab_type" name="type" required>
                                    <option value="" disabled <?php echo empty($habilitation['type']) ? 'selected' : ''; ?>>Sélectionner...</option>
                                    <option value="diplome" <?php echo ($habilitation['type'] === 'diplome') ? 'selected' : ''; ?>>Diplôme</option>
                                    <option value="certification" <?php echo ($habilitation['type'] === 'certification') ? 'selected' : ''; ?>>Certification</option>
                                    <option value="agrement" <?php echo ($habilitation['type'] === 'agrement') ? 'selected' : ''; ?>>Agrément</option>
                                    <option value="autre" <?php echo ($habilitation['type'] === 'autre') ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>
                             <div class="col-md-6 mb-3">
                                <label for="edit_hab_nom_document" class="form-label">Nom du Document/Diplôme</label>
                                <input type="text" class="form-control" id="edit_hab_nom_document" name="nom_document" value="<?php echo htmlspecialchars($habilitation['nom_document'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_hab_organisme" class="form-label">Organisme Émetteur</label>
                                <input type="text" class="form-control" id="edit_hab_organisme" name="organisme_emission" value="<?php echo htmlspecialchars($habilitation['organisme_emission'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_hab_document_url" class="form-label">URL Document (Scan)</label>
                                <input type="url" class="form-control" id="edit_hab_document_url" name="document_url" placeholder="https://..." value="<?php echo htmlspecialchars($habilitation['document_url'] ?? ''); ?>">
                            </div>
                        </div>

                         <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_hab_date_obtention" class="form-label">Date Obtention</label>
                                <input type="date" class="form-control" id="edit_hab_date_obtention" name="date_obtention" value="<?php echo $habilitation['date_obtention']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_hab_date_expiration" class="form-label">Date Expiration (si applicable)</label>
                                <input type="date" class="form-control" id="edit_hab_date_expiration" name="date_expiration" value="<?php echo $habilitation['date_expiration']; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_hab_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="edit_hab_notes" name="notes" rows="3"><?php echo htmlspecialchars($habilitation['notes'] ?? ''); ?></textarea>
                        </div>

                         <div class="mb-3">
                             <label for="edit_hab_statut" class="form-label">Statut <span class="text-danger">*</span></label>
                             <select class="form-select" id="edit_hab_statut" name="statut" required>
                                 <?php foreach (HABILITATION_STATUSES as $stat): ?>
                                     <option value="<?php echo $stat; ?>" <?php echo ($habilitation['statut'] === $stat) ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $stat)); ?></option>
                                 <?php endforeach; ?>
                             </select>
                          </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/view.php?id=<?php echo $provider_id; ?>&tab=habilitations" class="btn btn-secondary me-2">Annuler</a>
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
