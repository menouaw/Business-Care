<?php
require_once '../../includes/page_functions/modules/conferences.php';


requireRole(ROLE_ADMIN);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flashMessage('Identifiant de conférence invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/conferences/index.php');
}

$conference = conferencesGetDetails($id);

if (!$conference) {
    flashMessage("Conférence non trouvée.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/conferences/index.php');
}

$pageTitle = "Informations de la conférence";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/conferences/edit.php?id=<?php echo $conference['id']; ?>" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="Modifier cette conférence">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/conferences/index.php?action=delete&id=<?php echo $conference['id']; ?>&csrf_token=<?php echo generateToken(); ?>" 
                       class="btn btn-sm btn-danger me-2 btn-delete"
                       data-bs-toggle="tooltip" title="Supprimer cette conférence">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                     <a href="<?php echo WEBADMIN_URL; ?>/modules/conferences/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                   <i class="fas fa-calendar-alt me-1"></i> Détails de la conférence
                </div>
                <div class="card-body">
                     <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Titre</small>
                                <strong><?php echo htmlspecialchars($conference['titre']); ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Date et Heure de début</small>
                                <strong><?php echo formatDate($conference['date_debut'], 'd/m/Y H:i'); ?></strong>
                            </div>
                            <?php if ($conference['date_fin']): ?>
                            <div class="mb-3">
                                <small class="text-muted d-block">Date et Heure de fin</small>
                                <strong><?php echo formatDate($conference['date_fin'], 'd/m/Y H:i'); ?></strong>
                            </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <small class="text-muted d-block">Lieu</small>
                                <strong><?php echo htmlspecialchars($conference['lieu'] ?: '-'); ?></strong>
                            </div>
                             <?php if ($conference['site_id']): ?>
                            <div class="mb-3">
                                <small class="text-muted d-block">Site</small>
                                <strong><?php echo htmlspecialchars($conference['site_nom'] . ' (' . $conference['site_ville'] . ')'); ?></strong>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                           <div class="mb-3">
                                <small class="text-muted d-block">Type</small>
                                <strong><?php echo htmlspecialchars(ucfirst($conference['type'])); ?></strong>
                            </div>
                             <?php if ($conference['capacite_max']): ?>
                            <div class="mb-3">
                                <small class="text-muted d-block">Capacité maximale</small>
                                <strong><?php echo htmlspecialchars($conference['capacite_max']); ?></strong>
                            </div>
                            <?php endif; ?>
                            <?php if ($conference['niveau_difficulte']): ?>
                             <div class="mb-3">
                                <small class="text-muted d-block">Niveau de difficulté</small>
                                <strong><?php echo htmlspecialchars(ucfirst($conference['niveau_difficulte'])); ?></strong>
                            </div>
                            <?php endif; ?>
                             <div class="mb-3">
                                <small class="text-muted d-block">Organisée par BC</small>
                                <strong><?php echo $conference['organise_par_bc'] ? 'Oui' : 'Non'; ?></strong>
                            </div>
                             <div class="mb-3">
                                <small class="text-muted d-block">Créée le</small>
                                <strong><?php echo formatDate($conference['created_at'], 'd/m/Y H:i'); ?></strong>
                            </div>
                        </div>
                     </div>
                      <?php if ($conference['description']): ?>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <small class="text-muted d-block">Description</small>
                                <p><?php echo nl2br(htmlspecialchars($conference['description'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                     <?php if ($conference['materiel_necessaire']): ?>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <small class="text-muted d-block">Matériel nécessaire</small>
                                <p><?php echo nl2br(htmlspecialchars($conference['materiel_necessaire'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                     <?php if ($conference['prerequis']): ?>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <small class="text-muted d-block">Prérequis</small>
                                <p><?php echo nl2br(htmlspecialchars($conference['prerequis'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>