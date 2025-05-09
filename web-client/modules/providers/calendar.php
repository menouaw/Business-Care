<?php
require_once __DIR__ . '/../../includes/init.php';

require_once __DIR__ . '/../../includes/page_functions/modules/providers/calendar.php';

requireRole(ROLE_PRESTATAIRE);

$provider_id = $_SESSION['user_id'] ?? 0;
$pageTitle = "Mon Calendrier";


$current_year = date('Y');
$current_month = date('n');

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT, [
    'options' => ['default' => $current_year, 'min_range' => 2000, 'max_range' => 2100]
]);
$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT, [
    'options' => ['default' => $current_month, 'min_range' => 1, 'max_range' => 12]
]);


$prev_month_ts = mktime(0, 0, 0, $month - 1, 1, $year);
$next_month_ts = mktime(0, 0, 0, $month + 1, 1, $year);
$prev_year = date('Y', $prev_month_ts);
$prev_month = date('n', $prev_month_ts);
$next_year = date('Y', $next_month_ts);
$next_month = date('n', $next_month_ts);


$events_data = getProviderCalendarEventsForMonth($provider_id, $year, $month);
$calendar_html = generateEventCalendarHTML($year, $month, $events_data);


include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= WEBCLIENT_URL ?>/modules/providers/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                    </a>
                    <a href="<?= WEBCLIENT_URL ?>/modules/providers/availabilities.php" class="btn btn-sm btn-info">
                        <i class="fas fa-calendar-plus me-1"></i> Gérer mes Disponibilités
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Vue Mensuelle</span>
                    <div class="calendar-nav">
                        <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn btn-sm btn-outline-secondary">&lt; Précédent</a>
                        <span class="mx-2"><strong><?php
                                                    
                                                    $formatter = new IntlDateFormatter(
                                                        'fr_FR',
                                                        IntlDateFormatter::FULL,
                                                        IntlDateFormatter::NONE,
                                                        null,
                                                        IntlDateFormatter::GREGORIAN,
                                                        'MMMM yyyy'
                                                    );
                                                    echo htmlentities(ucfirst($formatter->format(mktime(0, 0, 0, $month, 1, $year))));
                                                    ?></strong></span>
                        <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn btn-sm btn-outline-secondary">Suivant &gt;</a>
                    </div>
                </div>
                <div class="card-body p-2">
                    <?= $calendar_html ?>
                    <div class="mt-2 small text-muted d-flex flex-wrap gap-3 calendar-legend">
                        <div><span class="badge event-confirmed"></span> RDV Confirmé</div>
                        <div><span class="badge event-planned"></span> RDV Planifié</div>
                        <div><span class="badge event-intervention-booked"></span> Intervention Réservée</div>
                        <div><span class="badge event-intervention-free"></span> Intervention Libre</div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

