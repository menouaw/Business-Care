<?php
require_once '../../includes/init.php';
require_once '../../includes/page_functions/modules/events.php';

requireRole(ROLE_ADMIN);

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($eventId <= 0) {
    flashMessage('Identifiant evenement invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/events/index.php');
}

$event = eventsGetDetails($eventId);

if (!$event) {
    flashMessage("Evenement non trouve.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/events/index.php');
}

$pageTitle = "Informations de l'evenement";
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
                    <a href="edit.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="Modifier cet evenement">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="index.php?action=delete&id=<?php echo $event['id']; ?>&csrf_token=<?php echo generateToken(); ?>" 
                       class="btn btn-sm btn-danger btn-delete me-2" data-bs-toggle="tooltip" title="Supprimer cet evenement">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retour a la liste des evenements">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-calendar-alt me-1"></i> Details de l'evenement
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Titre</small>
                                <strong><?php echo htmlspecialchars($event['titre']); ?></strong>
                            </div>
                             <div class="mb-3">
                                <small class="text-muted d-block">Type</small>
                                <strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $event['type']))); ?></strong>
                            </div>
                             <div class="mb-3">
                                <small class="text-muted d-block">Date et heure de debut</small>
                                <strong><?php echo formatDate($event['date_debut']); ?></strong>
                            </div>
                             <div class="mb-3">
                                <small class="text-muted d-block">Date et heure de fin</small>
                                <strong><?php echo $event['date_fin'] ? formatDate($event['date_fin']) : 'Non renseignee'; ?></strong>
                            </div>
                             <div class="mb-3">
                                <small class="text-muted d-block">Site / Lieu</small>
                                <strong>
                                     <?php
                                        if ($event['site_id'] && $event['site_nom']) {
                                            echo htmlspecialchars($event['site_nom'] . ' (' . $event['site_ville'] . ')');
                                        } else {
                                             echo htmlspecialchars($event['lieu'] ?: 'Non renseigne');
                                        }
                                    ?>
                                </strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Capacite maximale</small>
                                <strong><?php echo htmlspecialchars($event['capacite_max'] ?: 'Illimitee'); ?></strong>
                            </div>
                             <div class="mb-3">
                                <small class="text-muted d-block">Niveau de difficulte</small>
                                <strong><?php echo htmlspecialchars($event['niveau_difficulte'] ? ucfirst($event['niveau_difficulte']) : 'Non renseigne'); ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Organise par BC</small>
                                <strong><?php echo $event['organise_par_bc'] ? 'Oui' : 'Non'; ?></strong>
                            </div>
                             <div class="mb-3">
                                <small class="text-muted d-block">Cree le</small>
                                <strong><?php echo formatDate($event['created_at'], 'd/m/Y H:i'); ?></strong>
                            </div>
                             <div class="mb-3">
                                <small class="text-muted d-block">Mis a jour le</small>
                                <strong><?php echo formatDate($event['updated_at'], 'd/m/Y H:i'); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($event['description']): ?>
                         <div class="row mt-3">
                             <div class="col-md-12">
                                 <small class="text-muted d-block">Description</small>
                                 <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                             </div>
                         </div>
                     <?php endif; ?>

                     <?php if ($event['materiel_necessaire']): ?>
                         <div class="row mt-3">
                             <div class="col-md-12">
                                 <small class="text-muted d-block">Materiel necessaire</small>
                                 <p><?php echo nl2br(htmlspecialchars($event['materiel_necessaire'])); ?></p>
                             </div>
                         </div>
                     <?php endif; ?>

                     <?php if ($event['prerequis']): ?>
                         <div class="row mt-3">
                             <div class="col-md-12">
                                 <small class="text-muted d-block">Prerequis</small>
                                 <p><?php echo nl2br(htmlspecialchars($event['prerequis'])); ?></p>
                             </div>
                         </div>
                     <?php endif; ?>
                </div>
            </div>
            
             <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-users me-1"></i> Inscriptions
                </div>
                <div class="card-body p-0">
                    <?php $inscriptions = eventsGetInscriptions($event['id']); ?>
                    <?php if ($inscriptions): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 small">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inscriptions as $inscription): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($inscription['prenom'] . ' ' . $inscription['nom']); ?></td>
                                            <td><?php echo htmlspecialchars($inscription['email']); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($inscription['statut'])); ?></td>
                                            <td>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $inscription['id']; ?>" 
                                                   class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir l'utilisateur">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                         <div class="card-body text-center text-muted fst-italic">
                             Aucune inscription enregistree pour cet evenement.
                        </div>
                    <?php endif; ?>
                </div>
                 <div class="card-footer text-muted small">
                    Liste des personnes inscrites a cet evenement.
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
