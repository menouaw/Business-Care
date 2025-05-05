<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers/evaluations.php';

requireRole(ROLE_PRESTATAIRE);

$provider_id = $_SESSION['user_id'] ?? 0;
$pageTitle = "Mes Évaluations";


$items_per_page = 10;
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($current_page - 1) * $items_per_page;


$evaluations_data = [];
$total_evaluations = 0;
if ($provider_id > 0) {
    $evaluations_data = getProviderEvaluations($provider_id, $items_per_page, $offset);
    $total_evaluations = $evaluations_data['total'] ?? 0;
}
$evaluations = $evaluations_data['evaluations'] ?? [];
$total_pages = ceil($total_evaluations / $items_per_page);

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
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="card mb-4">
                <div class="card-header">
                    Liste des évaluations reçues pour vos prestations (<?= $total_evaluations ?> au total)
                </div>
                <div class="card-body">
                    <?php if (empty($evaluations)): ?>
                        <p class="text-center text-muted">Vous n'avez reçu aucune évaluation pour le moment.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($evaluations as $eval): ?>
                                <div class="list-group-item list-group-item-action flex-column align-items-start mb-2 shadow-sm">
                                    <div class="d-flex w-100 justify-content-between mb-2">
                                        <h5 class="mb-1 text-primary"><?= htmlspecialchars($eval['prestation_nom'] ?? 'N/A') ?></h5>
                                        <small class="text-muted"><?= htmlspecialchars(date('d/m/Y', strtotime($eval['date_evaluation']))) ?></small>
                                    </div>
                                    <div class="mb-2">
                                        <span class="me-2">Note:</span> <?= formatRatingStars((int)$eval['note']) ?>
                                    </div>
                                    <?php if (!empty($eval['commentaire'])): ?>
                                        <p class="mb-1">
                                            <?= nl2br(htmlspecialchars($eval['commentaire'])) ?>
                                        </p>
                                    <?php endif; ?>
                                    <small class="text-muted">Par:
                                        <?php
                                        $salarie_display = 'Anonyme';
                                        if (!empty($eval['salarie_prenom'])) {
                                            $salarie_display = htmlspecialchars($eval['salarie_prenom']);
                                            if (!empty($eval['salarie_nom'])) {
                                                $salarie_display .= ' ' . htmlspecialchars(substr($eval['salarie_nom'], 0, 1)) . '.';
                                            }
                                        }
                                        echo $salarie_display;
                                        ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation évaluations" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $current_page - 1 ?>">Précédent</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $current_page + 1 ?>">Suivant</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        <!-- Fin Pagination -->

                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>