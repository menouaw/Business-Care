<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers/dashboard.php';


requireRole(ROLE_PRESTATAIRE);

$provider_id = $_SESSION['user_id'] ?? 0;
$provider_name = $_SESSION['user_name'] ?? 'Prestataire';
$pageTitle = "Tableau de bord Prestataire";


$stats = getProviderDashboardStats($provider_id);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; 
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">Bienvenue, <?= htmlspecialchars($provider_name); ?> !</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="me-2">Statut du profil:
                        <span class="badge bg-<?= getStatusBadgeClass($stats['profile_status']) ?>">
                            <?= htmlspecialchars(ucfirst($stats['profile_status'])); ?>
                        </span>
                    </span>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="row mb-4">

                
                <div class="col-xl-3 col-md-6 mb-4">
                    <a href="<?= WEBCLIENT_URL ?>/modules/providers/appointments.php" class="text-decoration-none">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            RDV Confirmés à Venir</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($stats['upcoming_appointments']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-check fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                
                <div class="col-xl-3 col-md-6 mb-4">
                    <a href="<?= WEBCLIENT_URL ?>/modules/providers/habilitations.php" class="text-decoration-none">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Habilitations en Attente</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($stats['pending_habilitations']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-stamp fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                
                <div class="col-xl-3 col-md-6 mb-4">
                    <a href="<?= WEBCLIENT_URL ?>/modules/providers/availabilities.php" class="text-decoration-none">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Gérer mes Disponibilités</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">&nbsp;</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-alt fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                
                <div class="col-xl-3 col-md-6 mb-4">
                    <a href="<?= WEBCLIENT_URL ?>/modules/providers/evaluations.php" class="text-decoration-none">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Voir mes Évaluations</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">&nbsp;</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-star-half-alt fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

            </div>


        </main>
    </div>
</div>
