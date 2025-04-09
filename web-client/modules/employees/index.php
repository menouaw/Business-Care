<?php

require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

$dashboardData = displayEmployeeDashboard();

$pageTitle = "Tableau de bord - Espace Salarié";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="dashboard-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2 mb-0">Tableau de bord</h1>
                <p class="text-muted mb-0">Bienvenue dans votre espace salarié, <?= htmlspecialchars($dashboardData['user']['prenom'] ?? 'Utilisateur') ?>!</p>
            </div>
        </div>

        <div class="row mt-4 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Actions rapides</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6 col-md-3 mb-2 mb-md-0">
                                <a href="<?= WEBCLIENT_URL ?>/modules/employees/services.php" class="btn btn-primary d-block py-3">
                                    <i class="fas fa-search me-2"></i>Catalogue Services
                                </a>
                            </div>
                            <div class="col-sm-6 col-md-3 mb-2 mb-md-0">
                                <a href="<?= WEBCLIENT_URL ?>/modules/employees/signalement.php" class="btn btn-warning d-block py-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Signaler Situation
                                </a>
                            </div>
                            <div class="col-sm-6 col-md-3 mb-2 mb-md-0">
                                <a href="<?= WEBCLIENT_URL ?>/modules/employees/counsel.php" class="btn btn-success d-block py-3">
                                    <i class="fas fa-heart me-2"></i>Conseils Bien-être
                                </a>
                            </div>
                            <div class="col-sm-6 col-md-3 mb-2 mb-md-0">
                                <a href="<?= WEBCLIENT_URL ?>/modules/employees/communities.php" class="btn btn-info d-block py-3">
                                    <i class="fas fa-comments me-2"></i>Communautés
                                </a>
                            </div>
                            <div class="col-sm-6 col-md-3 mb-2 mb-md-0">
                                <a href="<?= WEBCLIENT_URL ?>/modules/employees/chatbot.php" class="btn btn-dark d-block py-3">
                                    <i class="fas fa-robot me-2"></i>Assistant Virtuel
                                </a>
                            </div>
                            <div class="col-sm-6 col-md-3 mb-2 mb-md-0">
                                <a href="<?= WEBCLIENT_URL ?>/modules/employees/settings.php" class="btn btn-secondary d-block py-3">
                                    <i class="fas fa-cog me-2"></i>Mes Paramètres
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                                <i class="fas fa-calendar-alt fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Rendez-vous</h6>
                                <h2 class="card-title mb-0"><?= count($dashboardData['upcoming_appointments'] ?? []) ?></h2>
                                <small class="text-muted">Prochainement</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php" class="btn btn-sm btn-outline-primary">Voir mon planning</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-success bg-opacity-10 text-success rounded p-3 me-3">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Événements</h6>
                                <h2 class="card-title mb-0"><?= count($dashboardData['upcoming_events'] ?? []) ?></h2>
                                <small class="text-muted">À venir</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="<?= WEBCLIENT_URL ?>/modules/employees/events.php" class="btn btn-sm btn-outline-success">Explorer les événements</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                                <i class="fas fa-bell fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Notifications</h6>
                                <h2 class="card-title mb-0"><?= count($dashboardData['unread_notifications'] ?? []) ?></h2>
                                <small class="text-muted">Non lues</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="<?= WEBCLIENT_URL ?>/notifications.php" class="btn btn-sm btn-outline-warning">Voir les notifications</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-info bg-opacity-10 text-info rounded p-3 me-3">
                                <i class="fas fa-hand-holding-heart fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Associations</h6>
                                <h2 class="card-title mb-0">Soutenir</h2>
                                <small class="text-muted">Faire un don</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="<?= WEBCLIENT_URL ?>/modules/employees/donations.php" class="btn btn-sm btn-outline-info">Faire un don</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Prochains rendez-vous</h5>
                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php" class="btn btn-sm btn-outline-primary">Voir mon planning</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($dashboardData['upcoming_appointments']['items'])) : ?>
                            <p class="text-center text-muted my-5">Aucun rendez-vous planifié</p>
                        <?php else : ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($dashboardData['upcoming_appointments']['items'] as $rdv) : ?>
                                    <?php if (is_array($rdv)):
                                    ?>
                                        <div class="list-group-item border-0 px-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($rdv['prestation_nom'] ?? 'Prestation inconnue') ?></h6>
                                                    <p class="text-muted mb-0 small">
                                                        <i class="far fa-calendar-alt me-1"></i> <?= htmlspecialchars($rdv['date_rdv_formatee'] ?? 'Date inconnue') ?>
                                                        <?php if (!empty($rdv['praticien_complet']) && $rdv['praticien_complet'] !== 'Non assigné') : ?>
                                                            <i class="fas fa-user-md ms-2 me-1"></i> <?= htmlspecialchars($rdv['praticien_complet']) ?>
                                                        <?php endif; ?>
                                                    </p>
                                                    <p class="text-muted mb-0 small">
                                                        <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($rdv['lieu'] ?: ($rdv['type_rdv'] === 'visio' ? 'Visioconférence' : 'Téléphone')) ?>
                                                    </p>
                                                </div>
                                                <?= $rdv['statut_badge'] ?? '<span class="badge bg-secondary">Inconnu</span>'; ?>
                                            </div>
                                        </div>
                                    <?php else:
                                        error_log("[Warning] Invalid data type found in upcoming_appointments array in index.php. Expected array, got: " . gettype($rdv));
                                    ?>
                                        <div class="list-group-item border-0 px-0 text-danger">Donnée de rendez-vous invalide.</div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Événements à venir</h5>
                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/events.php" class="btn btn-sm btn-outline-primary">Tous les événements</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($dashboardData['upcoming_events'])) : ?>
                            <p class="text-center text-muted my-5">Aucun événement à venir</p>
                        <?php else : ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($dashboardData['upcoming_events'], 0, 3) as $event) :
                                ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($event['titre'] ?? 'Événement sans titre') ?></h6>
                                                <p class="text-muted mb-0 small">
                                                    <i class="far fa-calendar-alt me-1"></i> <?= htmlspecialchars($event['date_debut_formatee'] ?? 'Date inconnue') ?>
                                                </p>
                                                <p class="text-muted mb-0 small">
                                                    <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($event['lieu'] ?? 'Lieu à confirmer') ?>
                                                </p>
                                            </div>
                                            <a href="<?= WEBCLIENT_URL ?>/events.php?id=<?= $event['id'] ?? '' ?>" class="btn btn-sm btn-outline-success">Voir détails</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Activité Récente</h5>
                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/history.php" class="btn btn-sm btn-outline-secondary">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <?php
                        $activites = $dashboardData['recent_activity']['activities'] ?? [];
                        ?>
                        <?php if (empty($activites)): ?>
                            <p class="text-center text-muted">Aucune activité récente enregistrée.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($activites as $activite): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <div>
                                            <i class="<?= htmlspecialchars($activite['icon'] ?? 'fas fa-history text-muted') ?> me-2"></i>
                                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $activite['action']))) ?>
                                            <?php if (!empty($activite['details'])): ?>
                                                <small class="text-muted d-block ms-4"><?= htmlspecialchars(substr($activite['details'], 0, 100)) . (strlen($activite['details']) > 100 ? '...' : '') ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted ms-3 text-nowrap"><?= htmlspecialchars($activite['created_at_formatted'] ?? 'Date inconnue') ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>