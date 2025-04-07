<?php
require_once '../../includes/page_functions/modules/appointments.php';

requireRole(ROLE_ADMIN);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flashMessage('Identifiant de rendez-vous invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/appointments/index.php');
}

$appointment = appointmentsGetDetails($id);

if (!$appointment) {
    flashMessage("Rendez-vous non trouve.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/appointments/index.php');
}

$pageTitle = "Details du rendez-vous";
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
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/edit.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="Modifier ce rendez-vous">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/index.php?action=delete&id=<?php echo $appointment['id']; ?>&csrf_token=<?php echo generateToken(); ?>" 
                       class="btn btn-sm btn-danger me-2 btn-delete"
                       data-bs-toggle="tooltip" title="Supprimer ce rendez-vous">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                     <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                           <i class="fas fa-calendar-check me-1"></i> Informations sur le rendez-vous
                        </div>
                        <div class="card-body">
                             <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Date et Heure</small>
                                        <strong><?php echo formatDate($appointment['date_rdv'], 'd/m/Y H:i'); ?></strong>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Duree</small>
                                        <strong><?php echo htmlspecialchars($appointment['duree']); ?> minutes</strong>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Type</small>
                                        <strong><?php echo htmlspecialchars(ucfirst($appointment['type_rdv'])); ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                     <div class="mb-3">
                                        <small class="text-muted d-block">Statut</small>
                                        <?php echo getStatusBadge($appointment['statut']); ?>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Lieu</small>
                                        <strong><?php echo htmlspecialchars($appointment['lieu'] ?: '-'); ?></strong>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Cree le</small>
                                        <strong><?php echo formatDate($appointment['created_at'], 'd/m/Y H:i'); ?></strong>
                                    </div>
                                </div>
                             </div>
                              <?php if ($appointment['notes']): ?>
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <small class="text-muted d-block">Notes</small>
                                        <p><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                     <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-briefcase me-1"></i> Service associé
                        </div>
                        <div class="card-body">
                            <?php if ($appointment['prestation_id']): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Nom du service</small>
                                    <strong><a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $appointment['prestation_id']; ?>" title="Voir le service"><?php echo htmlspecialchars($appointment['prestation_nom']); ?></a></strong>
                                </div>
                                <div class="row">
                                     <div class="col-md-6">
                                        <small class="text-muted d-block">Prix indicatif</small>
                                        <strong><?php echo $appointment['prestation_prix'] ? formatCurrency($appointment['prestation_prix']) : 'N/A'; ?></strong>
                                    </div>
                                     <div class="col-md-6">
                                        <small class="text-muted d-block">Duree par defaut</small>
                                        <strong><?php echo $appointment['prestation_duree_default'] ? $appointment['prestation_duree_default'] . ' min' : 'N/A'; ?></strong>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Aucun service associe.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-user-injured me-1"></i> Patient
                        </div>
                        <div class="card-body">
                            <?php if ($appointment['personne_id']): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Nom</small>
                                    <strong><a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $appointment['personne_id']; ?>" title="Voir l'utilisateur"><?php echo htmlspecialchars($appointment['patient_prenom'] . ' ' . $appointment['patient_nom']); ?></a></strong>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Email</small>
                                    <strong><?php echo htmlspecialchars($appointment['patient_email']); ?></strong>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Aucun patient associe.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-user-md me-1"></i> Praticien
                        </div>
                        <div class="card-body">
                           <?php if ($appointment['praticien_id']): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Nom</small>
                                    <strong><a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $appointment['praticien_id']; ?>" title="Voir l'utilisateur"><?php echo htmlspecialchars($appointment['practitioner_prenom'] . ' ' . $appointment['practitioner_nom']); ?></a></strong>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Email</small>
                                    <strong><?php echo htmlspecialchars($appointment['practitioner_email']); ?></strong>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Aucun praticien assigne.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
