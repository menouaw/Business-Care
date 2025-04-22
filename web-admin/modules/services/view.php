<?php
require_once '../../includes/page_functions/modules/services.php';

requireRole(ROLE_ADMIN);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flashMessage('Identifiant de service invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/services/index.php');
}

$serviceData = servicesGetDetails($id, true); 

if (!$serviceData || !$serviceData['service']) {
    flashMessage("Service non trouve.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/services/index.php');
}

$service = $serviceData['service'];
$appointments = $serviceData['appointments'] ?? [];
$evaluations = $serviceData['evaluations'] ?? [];

$pageTitle = "Informations du service";
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
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/services/edit.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="Modifier ce service">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/services/delete.php?id=<?php echo $service['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger btn-delete me-2"
                       data-bs-toggle="tooltip" title="Supprimer ce service">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                     <a href="<?php echo WEBADMIN_URL; ?>/modules/services/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i> Informations sur le service
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nom:</strong> <?php echo htmlspecialchars($service['nom']); ?></p>
                            <p><strong>Type:</strong> <?php echo htmlspecialchars(ucfirst($service['type'])); ?></p>
                            <p><strong>Prix:</strong> <?php echo formatCurrency($service['prix']); ?></p>
                            <p><strong>Duree:</strong> <?php echo $service['duree'] ? htmlspecialchars($service['duree']) . ' minutes' : 'Non specifiee'; ?></p>
                            <p><strong>Capacite maximale:</strong> <?php echo $service['capacite_max'] ? htmlspecialchars($service['capacite_max']) . ' personnes' : 'Non specifiee'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Categorie:</strong> <?php echo htmlspecialchars($service['categorie'] ?: 'Non specifiee'); ?></p>
                            <p><strong>Niveau de difficulte:</strong> <?php echo htmlspecialchars(ucfirst($service['niveau_difficulte'] ?: 'Non specifie')); ?></p>
                            <p><strong>Date de creation:</strong> <?php echo formatDate($service['created_at']); ?></p>
                            <p><strong>Derniere mise a jour:</strong> <?php echo formatDate($service['updated_at']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h5>Description</h5>
                            <p><?php echo nl2br(htmlspecialchars($service['description'] ?: 'Aucune description disponible.')); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h5>Materiel necessaire</h5>
                            <p><?php echo nl2br(htmlspecialchars($service['materiel_necessaire'] ?: 'Aucun materiel specifie.')); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Prerequis</h5>
                            <p><?php echo nl2br(htmlspecialchars($service['prerequis'] ?: 'Aucun prerequis specifie.')); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-calendar-check me-1"></i> Rendez-vous associes
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($appointments)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Client</th>
                                        <th>Duree</th>
                                        <th>Lieu</th>
                                        <th>Type</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo formatDate($appointment['date_rdv']); ?></td>
                                            <td>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $appointment['personne_id']; ?>" data-bs-toggle="tooltip" title="Voir le profil de <?php echo htmlspecialchars($appointment['prenom_personne'] . ' ' . $appointment['nom_personne']); ?>">
                                                    <?php echo htmlspecialchars($appointment['prenom_personne'] . ' ' . $appointment['nom_personne']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo $appointment['duree'] . ' minutes'; ?></td>
                                            <td><?php echo htmlspecialchars($appointment['lieu'] ?: 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($appointment['type_rdv'] ?? 'N/A')); ?></td>
                                            <td><?php echo getStatusBadge($appointment['statut']); ?></td>
                                            <td class="table-actions">
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir le rendez-vous">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/edit.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier le rendez-vous">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="card-body text-center text-muted fst-italic">
                            Aucun rendez-vous associe a ce service.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-muted small">
                   Liste des rendez-vous utilisant ce service.
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <?php
                    $totalNotes = 0;
                    $averageRating = 0;
                    if (!empty($evaluations)) {
                        foreach ($evaluations as $evaluation) {
                            $totalNotes += $evaluation['note'];
                        }
                        $averageRating = round($totalNotes / count($evaluations), 1);
                    }
                    ?>
                    <i class="fas fa-star-half-alt me-1"></i> Évaluations reçues (Moyenne: <?php echo $averageRating; ?>/5 sur <?php echo count($evaluations); ?> avis)
                </div>
                <div class="card-body">
                    <?php if (!empty($evaluations)): ?>
                        <?php foreach ($evaluations as $evaluation): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $evaluation['personne_id']; ?>" data-bs-toggle="tooltip" title="Voir le profil de <?php echo htmlspecialchars($evaluation['prenom_personne'] . ' ' . $evaluation['nom_personne']); ?>">
                                            <?php echo htmlspecialchars($evaluation['prenom_personne'] . ' ' . $evaluation['nom_personne']); ?>
                                        </a>
                                    </h6>
                                    <div>
                                        <strong>
                                            <?php for($i = 1; $i <= 5; $i++) { echo '<i class="' . ($i <= $evaluation['note'] ? 'fas' : 'far') . ' fa-star text-warning"></i>'; } ?>
                                        </strong> 
                                        <small class="text-muted ms-2"><?php echo formatDate($evaluation['date_evaluation'], 'd/m/Y'); ?></small>
                                    </div>
                                </div>
                                <p class="mb-0 fst-italic text-muted"><?php echo nl2br(htmlspecialchars($evaluation['commentaire'] ?: 'Pas de commentaire.')); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted fst-italic">
                            Aucune evaluation pour ce service.
                        </div>
                    <?php endif; ?>
                </div>
                 <div class="card-footer text-muted small">
                   Liste des évaluations laissées par les utilisateurs pour ce service.
                </div>
            </div>

            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
