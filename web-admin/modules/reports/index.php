<?php
require_once '../../includes/init.php';


// requireRole(ROLE_ADMIN)


$selectedDate = date('Y-m-d');
$reportUrl = null;
$currentDateYmd = date('Y-m-d');
$displayDate = ''; 


if (isset($_GET['report_date'])) {
    $submittedDate = sanitizeInput($_GET['report_date']);
    $timestamp = strtotime($submittedDate);

    if ($timestamp !== false) {
        $selectedDate = date('Y-m-d', $timestamp);
        $formattedDateForLink = date('d-m-Y', $timestamp);
        $reportUrl = JAVA_REPORTS_URL . '/report_' . $formattedDateForLink . '.pdf';
        $displayDate = date('d/m/Y', $timestamp);
    } else {
        $selectedDate = $submittedDate; 
    }
}


$pageTitle = "Rapports"; 
include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($pageTitle); ?></h1>
            </div>

            
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3 mb-4 align-items-end">
                <div class="col-md-4">
                    <label for="report_date" class="form-label">Date du rapport :</label>
                    <input type="date" id="report_date" name="report_date" class="form-control"
                        value="<?php echo htmlspecialchars($selectedDate); ?>"
                        max="<?php echo $currentDateYmd; ?>"
                        required>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary" title="Chercher">
                        <i class="fas fa-search"></i> 
                    </button>
                </div>
                <div class="col-md-auto"> 
                    <?php if ($reportUrl): ?>
                        <a href="<?php echo htmlspecialchars($reportUrl); ?>" target="_blank" class="btn btn-outline-secondary">
                            <i class="fas fa-file-pdf me-2"></i> Consulter le rapport du <?php echo htmlspecialchars($displayDate); ?> (PDF)
                        </a>
                    <?php endif; ?>
                </div>
            </form>


            
            <?php if (!$reportUrl && isset($_GET['report_date'])): ?>
                <div class="alert alert-warning mt-3">
                     Impossible de générer un lien pour la date fournie (<?php echo htmlspecialchars($selectedDate); ?>). Format invalide ou rapport non trouvé.
                 </div>
            <?php endif; ?>


            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>