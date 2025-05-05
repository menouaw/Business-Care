<?php
require_once '../../includes/page_functions/modules/users.php';

// requireRole(ROLE_ADMIN)

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    flashMessage('Identifiant utilisateur invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/users/index.php');
}

$user = usersGetDetails($userId); 

if (!$user) {
    flashMessage("Utilisateur non trouvé.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/users/index.php');
}

$pageTitle = "Informations de l'utilisateur";
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
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="Modifier cet utilisateur">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <form action="<?php echo WEBADMIN_URL; ?>/modules/users/delete.php" method="POST" class="d-inline" id="deleteForm">
                         <input type="hidden" name="id" id="deleteUserId" value="<?php echo $user['id']; ?>">
                         <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                         <button type="submit" class="btn btn-sm btn-danger btn-delete me-2" 
                                 data-user-id="<?php echo $user['id']; ?>" 
                                 data-user-name="<?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>"
                                 data-bs-toggle="tooltip" title="Supprimer cet utilisateur">
                             <i class="fas fa-trash"></i> Supprimer
                         </button>
                    </form>
                    <?php if (isset($user['entreprise_id'])): ?>
                        
                        <?php if ($user['role_id'] == ROLE_ENTREPRISE): ?>
                        <div class="btn-group" role="group" aria-label="Company Quick Actions">
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                     <a href="<?php echo WEBADMIN_URL; ?>/modules/users/index.php" class="btn btn-sm btn-outline-secondary ms-2" data-bs-toggle="tooltip" title="Retourner à la liste">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                   <i class="fas fa-user-circle me-1"></i>Utilisateur
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                             <?php if (!empty($user['photo_url'])): ?>
                                <img src="<?php echo htmlspecialchars(ROOT_URL . $user['photo_url']); ?>" alt="Photo de Profil" class="img-thumbnail rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                            <?php else: ?>
                                <div class="avatar-placeholder rounded-circle mb-3 d-inline-flex align-items-center justify-content-center bg-secondary text-white" style="width: 120px; height: 120px;">
                                    <i class="fas fa-user fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            <h5 class="mb-1"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h5>
                            <p class="text-muted mb-1 small"><?php echo htmlspecialchars($user['role_name'] ?? 'N/A'); ?></p>
                            <p><?php echo getStatusBadge($user['statut']); ?></p>
                        </div>
                        
                        <div class="col-md-9">
                            <div class="mb-3">
                                <small class="text-muted d-block">ID Utilisateur</small>
                                <strong><?php echo $user['id']; ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Email</small>
                                <strong><a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" title="Envoyer un email"><?php echo htmlspecialchars($user['email']); ?></a></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Téléphone</small>
                                <strong><?php echo htmlspecialchars($user['telephone'] ?? '-'); ?></strong>
                            </div>

                            <?php if (($user['role_id'] == ROLE_ENTREPRISE || $user['role_id'] == ROLE_SALARIE) && isset($user['entreprise_id'])): ?>
                            <div class="mb-3 p-2 bg-light border rounded">
                                <small class="text-muted d-block"><i class="fas fa-building text-muted me-1"></i>Entreprise</small>
                                <strong>
                                    <a href="<?php echo WEBADMIN_URL; ?>/modules/companies/view.php?id=<?php echo $user['entreprise_id']; ?>" title="Voir le profil de l'entreprise <?php echo htmlspecialchars($user['entreprise_nom']); ?>">
                                        <?php echo htmlspecialchars($user['entreprise_nom']); ?>
                                    </a>
                                </strong> (ID: <?php echo $user['entreprise_id']; ?>)
                                <?php if (isset($user['current_contract_status']) && $user['current_contract_status']): ?>
                                    <span class="ms-2"><?php echo getStatusBadge($user['current_contract_status']['statut']); ?></span>
                                    <?php if ($user['current_contract_status']['date_fin']): 
                                        $endDate = new DateTime($user['current_contract_status']['date_fin']);
                                        $now = new DateTime();
                                        $interval = $now->diff($endDate);
                                        if ($interval->days <= 30 && !$interval->invert) {
                                            echo ' <span class="badge bg-warning text-dark">Expire bientôt</span>';
                                        } 
                                    ?>
                                    <small class="d-block text-muted">Fin contrat: <?php echo formatDate($user['current_contract_status']['date_fin'], 'd/m/Y'); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-2">Pas de contrat actif</span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?> 

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Date de Création</small>
                                    <strong><?php echo formatDate($user['created_at']); ?></strong>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Dernière Connexion</small>
                                    <strong><?php echo $user['derniere_connexion'] ? formatDate($user['derniere_connexion']) : 'Jamais'; ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($user['login_history'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i> Historique de connexion
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($user['login_history'] as $log): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-clock text-muted me-2"></i><?php echo formatDate($log['created_at']); ?> -
                                <i class="fas fa-info-circle text-muted mx-2"></i><?php echo htmlspecialchars($log['details']); ?>
                            </span>
                            <span class="badge bg-secondary"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($log['ip_address']); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                 <div class="card-footer text-muted small">
                    Affichage des 10 dernières connexions enregistrées.
                </div>
            </div>
            <?php elseif ($user['role_id'] != ROLE_ADMIN): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i> Historique de connexion
                </div>
                 <div class="card-body text-center text-muted fst-italic">
                    Aucun historique de connexion trouvé pour cet utilisateur.
                </div>
            </div>
            <?php endif; ?>

            <?php if ($user['role_id'] == ROLE_SALARIE && isset($user['reservations'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-calendar-check me-1"></i> Réservations récentes
                </div>
                <div class="card-body p-0">
                    <?php if (empty($user['reservations'])): ?>
                    <div class="card-body text-center text-muted fst-italic">
                        Aucune réservation récente trouvée pour cet utilisateur.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover mb-0 small">
                            <thead>
                                <tr>
                                    <th>Date RDV</th>
                                    <th>Prestation</th>
                                    <th>Praticien</th>
                                    <th>Statut</th>
                                    <th>Réservé le</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user['reservations'] as $resa): 
                                    $isUpcoming = isset($resa['date_rdv']) && new DateTime($resa['date_rdv']) > new DateTime();
                                ?>
                                <tr class="<?php echo $isUpcoming ? 'table-info' : ''; ?>">
                                    <td><?php echo formatDate($resa['date_rdv']); ?> <?php echo $isUpcoming ? '<i class="fas fa-clock text-primary ms-1" title="À venir"></i>' : ''; ?></td>
                                    <td>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $resa['prestation_id']; ?>" data-bs-toggle="tooltip" title="Voir la prestation '<?php echo htmlspecialchars($resa['prestation_nom'] ?? ''); ?>'">
                                           <?php echo htmlspecialchars($resa['prestation_nom'] ?? 'N/A'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if(isset($resa['praticien_id'])): ?>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $resa['praticien_id']; ?>" data-bs-toggle="tooltip" title="Voir le profil de <?php echo htmlspecialchars(trim(($resa['praticien_prenom'] ?? '') . ' ' . ($resa['praticien_nom'] ?? ''))); ?>">
                                            <?php echo htmlspecialchars(trim(($resa['praticien_prenom'] ?? '') . ' ' . ($resa['praticien_nom'] ?? ''))); ?>
                                        </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo getStatusBadge($resa['statut']); ?></td>
                                    <td><?php echo formatDate($resa['created_at'], 'd/m/Y'); ?></td>
                                    <td class="table-actions">
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/view.php?id=<?php echo $resa['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir le rendez-vous">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/edit.php?id=<?php echo $resa['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier le rendez-vous">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-muted small">
                    Affichage des 10 dernières réservations. <span class="badge bg-info text-dark">Bleu</span> = à venir.
                </div>
            </div>
            <?php endif; ?>

            <?php if ($user['role_id'] == ROLE_SALARIE && isset($user['evaluations_submitted'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-star-half-alt me-1"></i> Évaluations soumises
                </div>
                <div class="card-body p-0">
                    <?php if (empty($user['evaluations_submitted'])): ?>
                    <div class="card-body text-center text-muted fst-italic">Aucune évaluation soumise par cet utilisateur.</div>
                    <?php else: ?>
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($user['evaluations_submitted'] as $eval): ?>
                        <li class="list-group-item">
                            <span class="fw-bold">
                                <?php for($i = 1; $i <= 5; $i++) { echo '<i class="' . ($i <= $eval['note'] ? 'fas' : 'far') . ' fa-star text-warning"></i>'; } ?>
                                pour "<?php echo htmlspecialchars($eval['prestation_nom'] ?? 'N/A'); ?>"
                            </span>
                            <span class="text-muted float-end"><i class="fas fa-calendar-alt me-1"></i><?php echo formatDate($eval['date_evaluation'], 'd/m/Y'); ?></span><br>
                            <?php if(!empty($eval['commentaire'])): ?>
                                <span class="text-muted fst-italic">"<?php echo htmlspecialchars(substr($eval['commentaire'], 0, 100)) . (strlen($eval['commentaire']) > 100 ? '...' : ''); ?>"</span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-muted small">
                    Affichage des 10 dernières évaluations soumises.
                </div>
            </div>
            <?php endif; ?>

            <?php if ($user['role_id'] == ROLE_SALARIE && isset($user['donations_made'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-hand-holding-heart me-1"></i> Dons effectués
                </div>
                 <div class="card-body p-0">
                    <?php if (empty($user['donations_made'])): ?>
                    <div class="card-body text-center text-muted fst-italic">Aucun don effectué par cet utilisateur.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover mb-0 small">
                             <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Détail / Montant</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                             <tbody>
                                <?php foreach ($user['donations_made'] as $don): ?>
                                <tr>
                                    <td><?php echo formatDate($don['date_don'], 'd/m/Y'); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($don['type'])); ?></td>
                                    <td>
                                        <?php if ($don['type'] == 'materiel'): ?>
                                            <?php echo htmlspecialchars(substr($don['description'] ?? '-', 0, 50)) . (strlen($don['description'] ?? '') > 50 ? '...' : ''); ?>
                                        <?php elseif ($don['type'] == 'financier'): ?>
                                            <?php echo formatMoney($don['montant'] ?? 0); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo getStatusBadge($don['statut']); ?></td>
                                     <td class="table-actions">
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/donations/view.php?id=<?php echo $don['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir le don">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($don['statut'] == 'en_attente'): ?>
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/donations/edit.php?id=<?php echo $don['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier le don">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                 <div class="card-footer text-muted small">
                    Affichage des 10 derniers dons enregistrés. Seuls les dons 'en attente' peuvent être modifiés.
                </div>
            </div>
            <?php endif; ?>

            <?php if ($user['role_id'] == ROLE_PRESTATAIRE && isset($user['prestations'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-briefcase-medical me-1"></i> Prestations récentes / Interventions
                </div>
                <div class="card-body p-0">
                     <?php if (empty($user['prestations'])): ?>
                     <div class="card-body text-center text-muted fst-italic">
                        Aucune prestation récente trouvée pour ce praticien.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Description</th>
                                    <th>Tarif</th>
                                    <th>Statut</th>
                                    <th>Date Création</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user['prestations'] as $prest): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prest['nom']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($prest['description'] ?? '', 0, 50)) . (strlen($prest['description'] ?? '') > 50 ? '...' : ''); ?></td>
                                    <td><?php echo formatMoney($prest['tarif'] ?? 0); ?></td>
                                    <td><?php echo getStatusBadge($prest['statut'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatDate($prest['created_at'], 'd/m/Y'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                 <div class="card-footer text-muted small">
                    Affichage des 10 dernières prestations enregistrées pour ce praticien.
                </div>
            </div>
            <?php endif; ?>

            <?php if ($user['role_id'] == ROLE_PRESTATAIRE && isset($user['appointments_given'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-calendar-check me-1"></i> Rendez-vous donnés
                </div>
                <div class="card-body p-0">
                    <?php if (empty($user['appointments_given'])): ?>
                    <div class="card-body text-center text-muted fst-italic">
                        Aucun rendez-vous récent trouvé pour ce praticien.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover mb-0 small">
                            <thead>
                                <tr>
                                    <th>Date RDV</th>
                                    <th>Prestation</th>
                                    <th>Patient</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user['appointments_given'] as $app): 
                                    $isUpcoming = isset($app['date_rdv']) && new DateTime($app['date_rdv']) > new DateTime();
                                ?>
                                <tr class="<?php echo $isUpcoming ? 'table-info' : ''; ?>">
                                    <td><?php echo formatDate($app['date_rdv']); ?> <?php echo $isUpcoming ? '<i class="fas fa-clock text-primary ms-1" title="À venir"></i>' : ''; ?></td>
                                    <td>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $app['prestation_id']; ?>" data-bs-toggle="tooltip" title="Voir la prestation '<?php echo htmlspecialchars($app['prestation_nom'] ?? ''); ?>'">
                                           <?php echo htmlspecialchars($app['prestation_nom'] ?? 'N/A'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if(isset($app['personne_id'])): ?>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $app['personne_id']; ?>" data-bs-toggle="tooltip" title="Voir le profil de <?php echo htmlspecialchars(trim(($app['client_prenom'] ?? '') . ' ' . ($app['client_nom'] ?? ''))); ?>">
                                            <?php echo htmlspecialchars(trim(($app['client_prenom'] ?? '') . ' ' . ($app['client_nom'] ?? ''))); ?>
                                        </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                     <td><?php echo htmlspecialchars(ucfirst($app['type_rdv'] ?? 'N/A')); ?></td>
                                    <td><?php echo getStatusBadge($app['statut']); ?></td>
                                    <td class="table-actions">
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/view.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir le rendez-vous">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/edit.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier le rendez-vous">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-muted small">
                    Affichage des 10 derniers rendez-vous donnés. <span class="badge bg-info text-dark">Bleu</span> = à venir.
                </div>
            </div>
            <?php endif; ?>

            <?php if ($user['role_id'] == ROLE_ENTREPRISE && isset($user['entreprise_id'])): 
                $pattern = '/@([^.]+)\./';
                preg_match($pattern, $user['email'], $matches);
                $domainName = $matches[1];
            ?>

                <div class="card mb-4">
                     <div class="card-header">
                        <i class="fas fa-chart-bar me-1"></i> Statistiques d'utilisation entreprise
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                <h4 class="mb-0"><?php echo $user['stats_active_employees'] ?? 0; ?></h4>
                                <small class="text-muted">Salariés Actifs</small>
                            </div>
                             <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                <h4 class="mb-0"><?php echo $user['stats_recent_reservations'] ?? 0; ?></h4>
                                <small class="text-muted">Réservations (30j)</small>
                            </div>
                             <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                <h4 class="mb-0">
                                    <?php echo isset($user['stats_avg_satisfaction']) ? $user['stats_avg_satisfaction'] . ' / 5' : 'N/A'; ?>
                                    <?php if (isset($user['stats_avg_satisfaction'])) echo '<i class="fas fa-star text-warning ms-1"></i>'; ?>
                                </h4>
                                <small class="text-muted">Satisfaction Moyenne</small>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                <?php if (!empty($user['stats_top_prestations'])): ?>
                                <ul class="list-unstyled mb-0 small text-start text-lg-center">
                                     <li class="fw-bold"><small class="text-muted">Top Prestations:</small></li>
                                    <?php foreach ($user['stats_top_prestations'] as $topPrest): ?>
                                        <li><?php echo htmlspecialchars($topPrest['nom']); ?> (<?php echo $topPrest['count']; ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php else: ?>
                                     <small class="text-muted d-block mt-2">Top Prestations: N/A</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($user['company_employees'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-users me-1"></i> Salariés associés
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($user['company_employees'])): ?>
                        <div class="card-body text-center text-muted fst-italic">Aucun salarié associé trouvé pour cette entreprise.</div>
                        <?php else: ?>
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
                                    <?php foreach ($user['company_employees'] as $employee): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($employee['prenom'] . ' ' . $employee['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                        <td><?php echo getStatusBadge($employee['statut']); ?></td>
                                        <td>
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/users/view.php?id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Voir Salarié"><i class="fas fa-eye"></i></a>
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/users/edit.php?id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Modifier Salarié"><i class="fas fa-edit"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                     <div class="card-footer text-muted small">
                        <a href="<?php echo WEBADMIN_URL; ?>/modules/users/index.php?search=<?php echo $domainName; ?>&role=<?php echo ROLE_SALARIE; ?>">Voir tous les salariés de cette entreprise</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($user['company_contracts'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-file-contract me-1"></i> Contrats récents
                    </div>
                     <div class="card-body p-0">
                         <?php if (empty($user['company_contracts'])): ?>
                         <div class="card-body text-center text-muted fst-italic">Aucun contrat récent trouvé pour cette entreprise.</div>
                         <?php else: ?>
                         <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 small">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Début</th>
                                        <th>Fin</th>
                                        <th>Montant Mensuel</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user['company_contracts'] as $contract): ?>
                                    <tr>
                                        <td><?php echo $contract['id']; ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($contract['type_contrat'])); ?></td>
                                        <td><?php echo formatDate($contract['date_debut'], 'd/m/Y'); ?></td>
                                        <td><?php echo $contract['date_fin'] ? formatDate($contract['date_fin'], 'd/m/Y') : '-'; ?></td>
                                        <td><?php echo formatMoney($contract['montant_mensuel'] ?? 0); ?></td>
                                        <td><?php echo getStatusBadge($contract['statut']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted small">
                       Affichage des 5 derniers contrats.
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isset($user['company_invoices'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-file-invoice-dollar me-1"></i> Factures récentes
                    </div>
                     <div class="card-body p-0">
                        <?php if (empty($user['company_invoices'])): ?>
                         <div class="card-body text-center text-muted fst-italic">Aucune facture récente trouvée pour cette entreprise.</div>
                        <?php else: ?>
                         <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 small">
                                <thead>
                                    <tr>
                                        <th>Numéro</th>
                                        <th>Émission</th>
                                        <th>Échéance</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user['company_invoices'] as $invoice): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($invoice['numero_facture']); ?></td>
                                        <td><?php echo formatDate($invoice['date_emission'], 'd/m/Y'); ?></td>
                                        <td><?php echo formatDate($invoice['date_echeance'], 'd/m/Y'); ?></td>
                                        <td><?php echo formatMoney($invoice['montant_total'] ?? 0); ?></td>
                                        <td><?php echo getStatusBadge($invoice['statut']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted small">
                       Affichage des 10 dernières factures.
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($user['company_quotes'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-file-alt me-1"></i> Devis récents
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($user['company_quotes'])): ?>
                        <div class="card-body text-center text-muted fst-italic">Aucun devis récent trouvé pour cette entreprise.</div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 small">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Création</th>
                                        <th>Validité</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user['company_quotes'] as $quote): ?>
                                    <tr>
                                        <td><?php echo $quote['id']; ?></td>
                                        <td><?php echo formatDate($quote['date_creation'], 'd/m/Y'); ?></td>
                                        <td><?php echo $quote['date_validite'] ? formatDate($quote['date_validite'], 'd/m/Y') : '-'; ?></td>
                                        <td><?php echo formatMoney($quote['montant_total'] ?? 0); ?></td>
                                        <td><?php echo getStatusBadge($quote['statut']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                     <div class="card-footer text-muted small">
                       Affichage des 10 derniers devis.
                    </div>
                </div>
                <?php endif; ?>
                
            <?php endif; ?>

            <?php if ($user['role_id'] == ROLE_ADMIN): ?>

                <?php if (isset($user['admin_actions'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-clipboard-list me-1"></i> Actions Administratives Récentes
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($user['admin_actions'])): ?>
                        <div class="card-body text-center text-muted fst-italic">Aucune action administrative récente trouvée.</div>
                        <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($user['admin_actions'] as $log): ?>
                            <li class="list-group-item">
                                <span class="fw-bold"><?php echo htmlspecialchars($log['action']); ?></span>
                                <span class="text-muted float-end"><i class="fas fa-clock me-1"></i><?php echo formatDate($log['created_at'], 'd/m/Y H:i'); ?> <i class="fas fa-map-marker-alt ms-2 me-1"></i><?php echo htmlspecialchars($log['ip_address']); ?></span><br>
                                <span class="text-muted"><?php echo nl2br(htmlspecialchars($log['details'] ?? '')); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted small">
                        Affichage des 15 dernières actions administratives enregistrées (hors connexion/déconnexion).
                    </div>
                </div>
                <?php endif; ?>

                 <?php if (isset($user['security_actions'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <i class="fas fa-shield-alt me-1"></i> Actions de Sécurité Récentes
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($user['security_actions'])): ?>
                        <div class="card-body text-center text-muted fst-italic">Aucune action de sécurité récente trouvée.</div>
                        <?php else: ?>
                         <ul class="list-group list-group-flush small">
                            <?php foreach ($user['security_actions'] as $log): 
                                $isFailure = str_contains($log['action'], '[SECURITY FAILURE]');
                            ?>
                            <li class="list-group-item <?php echo $isFailure ? 'list-group-item-danger' : ''; ?>">
                                <span class="fw-bold"><?php echo htmlspecialchars($log['action']); ?></span>
                                <span class="text-muted float-end"><i class="fas fa-clock me-1"></i><?php echo formatDate($log['created_at'], 'd/m/Y H:i'); ?> <i class="fas fa-map-marker-alt ms-2 me-1"></i><?php echo htmlspecialchars($log['ip_address']); ?></span><br>
                                <span class="text-muted"><?php echo nl2br(htmlspecialchars($log['details'] ?? '')); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                     <div class="card-footer text-muted small">
                        Affichage des 10 dernières actions liées à la sécurité enregistrées.
                    </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>
            
            <?php include '../../templates/footer.php'; ?>

        </main>
    </div>
</div> 