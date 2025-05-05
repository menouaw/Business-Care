<?php
require_once '../../includes/page_functions/modules/donations.php';

// requireRole(ROLE_ADMIN)

$donationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($donationId <= 0) {
    flashMessage('Identifiant de don invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/donations/index.php');
}

$donation = donationsGetDetails($donationId);

if (!$donation) {
    flashMessage("Don non trouvé.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/donations/index.php');
}

$pageTitle = "Informations du don";
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
                    <?php if ($donation['statut'] == 'en_attente'): ?>
                        <a href="<?php echo WEBADMIN_URL; ?>/modules/donations/edit.php?id=<?php echo $donation['id']; ?>" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="Modifier/Traiter ce don">
                            <i class="fas fa-edit"></i> Modifier / Traiter
                        </a>
                    <?php endif; ?>
                     <a href="<?php echo WEBADMIN_URL; ?>/index.php" class="btn btn-sm btn-outline-secondary ms-2" data-bs-toggle="tooltip" title="Retourner à l'accueil">
                        <i class="fas fa-home"></i> Accueil
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                   <i class="fas fa-hand-holding-heart me-1"></i> Informations
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">ID</small>
                            <strong><?php echo $donation['id']; ?></strong>
                        </div>
                         <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Statut</small>
                            <strong><?php echo getStatusBadge($donation['statut']); ?></strong>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block"><i class="fas fa-user text-muted me-1"></i>Donateur</small>
                            <strong>
                                <?php if(isset($donation['personne_id'])): ?>
                                <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $donation['personne_id']; ?>" title="Voir le profil de <?php echo htmlspecialchars(trim($donation['donor_prenom'] . ' ' . $donation['donor_nom'])); ?>">
                                    <?php echo htmlspecialchars(trim($donation['donor_prenom'] . ' ' . $donation['donor_nom'])); ?>
                                </a> (<?php echo htmlspecialchars($donation['donor_email']); ?>)
                                <?php else: ?>
                                    Donateur inconnu
                                <?php endif; ?>
                            </strong>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block"><i class="fas fa-calendar-alt text-muted me-1"></i>Date</small>
                            <strong><?php echo formatDate($donation['date_don']); ?></strong>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Type</small>
                            <strong><?php echo ucfirst(htmlspecialchars($donation['type'])); ?></strong>
                        </div>
                        <div class="col-md-6 mb-3">
                            <?php if ($donation['type'] == 'financier'): ?>
                                <small class="text-muted d-block"><i class="fas fa-euro-sign text-muted me-1"></i>Montant</small>
                                <strong><?php echo $donation['montant_formate'] ?? '-'; ?></strong>
                            <?php elseif ($donation['type'] == 'materiel'): ?>
                                <small class="text-muted d-block"><i class="fas fa-box-open text-muted me-1"></i>Description</small>
                                <p><?php echo nl2br(htmlspecialchars($donation['description'] ?? '-')); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                     <?php if ($donation['statut'] == 'en_attente'): ?>
                     <hr>
                     <p class="text-muted">Actions rapides (ce don est en attente de validation) :</p>
                     <form action="<?php echo WEBADMIN_URL; ?>/modules/donations/edit.php?id=<?php echo $donation['id']; ?>" method="POST" class="d-inline"> 
                         <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                         <input type="hidden" name="action" value="update_status">
                         <input type="hidden" name="statut" value="valide">
                         <button type="submit" class="btn btn-success btn-sm"> <i class="fas fa-check-circle"></i> Valider le Don</button>
                     </form>
                      <form action="<?php echo WEBADMIN_URL; ?>/modules/donations/edit.php?id=<?php echo $donation['id']; ?>" method="POST" class="d-inline ms-2"> 
                         <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                         <input type="hidden" name="action" value="update_status">
                         <input type="hidden" name="statut" value="refuse">
                         <button type="submit" class="btn btn-danger btn-sm"> <i class="fas fa-times-circle"></i> Refuser le Don</button>
                     </form>
                     <?php endif; ?>

                </div>
            </div>
            
            <?php include '../../templates/footer.php'; ?>

        </main>
    </div>
</div>

</code_block_to_apply_changes_from>
