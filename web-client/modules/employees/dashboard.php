<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/dashboard.php';

requireRole(ROLE_SALARIE);

$salarie_id = $_SESSION['user_id'] ?? 0;

if ($salarie_id <= 0) {

    flashMessage("Impossible d'identifier l'utilisateur.", "danger");

    redirectTo(WEBCLIENT_URL . '/auth/login.php');
    exit;
}


$stats = getSalarieDashboardStats($salarie_id);
$upcomingAppointments = getUpcomingAppointments($salarie_id, 5);

$pageTitle = "Mon Espace Personnel";

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php';
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur'); ?> !</h1>
            </div>

            <?php echo displayFlashMessages(); ?>

            
            <div class="row mb-4">
                <div class="col-md-6 mb-4">
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php" class="text-decoration-none">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Prochains Rendez-vous</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($stats['prochains_rdv']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-check fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 mb-4">
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/notifications.php" class="text-decoration-none">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Notifications Non Lues</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($stats['notifications_non_lues']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bell fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <?php if (!empty($upcomingAppointments)): ?>
                <div class="row">
                    <div class="col-lg-12 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($upcomingAppointments as $rdv): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($rdv['prestation_nom'] ?? 'N/D') ?></strong><br>
                                                <small><?= htmlspecialchars(formatDate($rdv['date_rdv'], 'd/m/Y H:i')) ?></small>
                                                <?php if (!empty($rdv['praticien_nom'])): ?>
                                                    <small> avec <?= htmlspecialchars($rdv['praticien_nom']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php" class="btn btn-sm btn-outline-info" title="Voir la liste des rendez-vous">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if (count($upcomingAppointments) >= 5): ?>
                                    <div class="text-center mt-3">
                                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php">Voir tous mes rendez-vous</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>