<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/companies/dashboard.php';

requireRole(ROLE_ENTREPRISE);

$entreprise_id = $_SESSION['user_entreprise'] ?? 0;

if ($entreprise_id <= 0) {
    logSecurityEvent($_SESSION['user_id'], 'company_dashboard_access_error', '[ERROR] ID entreprise manquant en session pour user ID: ' . $_SESSION['user_id']);

    $pageTitle = "Erreur d'accès Entreprise";
    include __DIR__ . '/../../templates/header.php';
?>
    <div class="container mt-4">
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Erreur d'association !</h4>
            <p>Votre compte utilisateur de type 'Entreprise' n'est actuellement associé à aucune entreprise spécifique dans notre système.</p>
            <hr>
            <p class="mb-0">Pour accéder au tableau de bord, une association est nécessaire. Veuillez contacter le support technique.</p>
        </div>
    </div>
<?php
    include __DIR__ . '/../../templates/footer.php';
    exit;
}

$stats = getCompanyDashboardStats($entreprise_id);
$pageTitle = "Tableau de bord - " . ($_SESSION['user_name'] ?? 'Entreprise'); 

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php';
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur'); ?> !</h1>
                <!-- Suppression de la toolbar générique -->
            </div>

            <?php echo displayFlashMessages();
            ?>

            <!-- Section Statistiques -->
            <div class="row mb-4">
                <!-- Salariés Actifs -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Salariés Actifs</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo htmlspecialchars($stats['active_employees']); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RDV Récents -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        RDV (Prochains - Placeholder)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo htmlspecialchars($stats['recent_appointments']); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-check fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <?php 
            ?>
        </main>
    </div> <!-- Fin de <div class="row"> -->
</div> <!-- Fin de <div class="container-fluid"> -->

<?php include __DIR__ . '/../../templates/footer.php'; 
?>