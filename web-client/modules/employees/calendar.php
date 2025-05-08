<?php
require_once __DIR__ . '/../../includes/init.php';

require_once __DIR__ . '/../../includes/page_functions/modules/employees/calendar.php';

requireRole(ROLE_SALARIE);

$salarie_id = $_SESSION['user_id'] ?? 0;

if ($salarie_id <= 0) {

    flashMessage("Impossible d'identifier votre compte pour afficher le calendrier.", "danger");
    redirectTo(WEBCLIENT_URL . '/auth/login.php');
    exit;
}


$pageData = setupEmployeeCalendarPageData();


$pageTitle = $pageData['pageTitle'];
$calendar_html = $pageData['calendar_html'];



$base_calendar_url = WEBCLIENT_URL . '/modules/employees/calendar.php';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php
        include __DIR__ . '/../../templates/sidebar.php';
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                    </a>
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php?bookingStep=show_services" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Prendre un nouveau RDV
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="card">
                <div class="card-body p-3">
                    <?= $calendar_html ?>
                </div>
            </div>


        </main>
    </div>
</div>
