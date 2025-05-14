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

            <?php if ($stats['pack_info']): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Votre Pack</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="card-title"><?= htmlspecialchars($stats['pack_info']['type']) ?></h5>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            Date de début : <?= htmlspecialchars(formatDate($stats['pack_info']['date_debut'], 'd/m/Y')) ?>
                                            <?php if ($stats['pack_info']['date_fin']): ?>
                                            <br>Date de fin : <?= htmlspecialchars(formatDate($stats['pack_info']['date_fin'], 'd/m/Y')) ?>
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <div class="usage-stats">
                                        <div class="mb-2">
                                            <span><i class="fas fa-book"></i> Accès aux fiches pratiques BC illimité</span>
                                        </div>
                                        <div class="mb-2">
                                            <span><i class="fas fa-user-md"></i> RDV médicaux :
                                                <?php
                                                    if (isset($stats['pack_info']['usage_stats']['consultations'])) {
                                                        echo $stats['pack_info']['usage_stats']['consultations']['used'] . '/' . $stats['pack_info']['usage_stats']['consultations']['total'] . ' utilisé' . ($stats['pack_info']['usage_stats']['consultations']['total'] > 1 ? 's' : '');
                                                    } else {
                                                        echo 'illimité';
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            <span><i class="fas fa-robot"></i> Chatbot :
                                                <?php
                                                    $limite = $stats['pack_info']['chatbot_questions_limite'] ?? null;
                                                    $usage = $stats['pack_info']['usage_stats']['chatbot'] ?? null;
                                                    if ($limite === null) {
                                                        echo 'illimité';
                                                        if ($usage && isset($usage['used'])) {
                                                            echo ' (' . $usage['used'] . ' utilisée' . ($usage['used'] > 1 ? 's' : '') . ')';
                                                        }
                                                    } else {
                                                        echo htmlspecialchars($limite) . ' question' . ($limite > 1 ? 's' : '');
                                                        if ($usage && isset($usage['used'], $usage['total'])) {
                                                            echo ' (' . $usage['used'] . '/' . $usage['total'] . ' utilisée' . ($usage['total'] > 1 ? 's' : '') . ')';
                                                        }
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            <span><i class="fas fa-lightbulb"></i> Conseils hebdomadaires :
                                                <?php
                                                    if (!empty($stats['pack_info']['conseils_hebdo_personnalises'])) {
                                                        echo 'oui (personnalisés)';
                                                    } elseif (($stats['pack_info']['type'] ?? '') === 'Basic Pack') {
                                                        echo 'oui (non personnalisés)';
                                                    } else {
                                                        echo 'non';
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            <span><i class="fas fa-calendar-alt"></i> Événements organisés par BC :
                                                <?php
                                                    $used = $stats['pack_info']['usage_stats']['ateliers']['used'] ?? 0;
                                                    $total = $stats['pack_info']['usage_stats']['ateliers']['total'] ?? null;
                                                    if ($total) {
                                                        echo $used . '/' . $total;
                                                    } else {
                                                        echo 'illimité';
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            <span><i class="fas fa-users"></i> Événements / Communautés : accès illimité</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
