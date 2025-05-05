<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers/interventions.php';

requireRole(ROLE_PRESTATAIRE);

$provider_id = $_SESSION['user_id'] ?? 0;
$pageTitle = "Mes Interventions";


$allowed_filters = ['upcoming', 'past', 'all'];
$filter_status = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_SPECIAL_CHARS);
if (!$filter_status || !in_array($filter_status, $allowed_filters)) {
    $filter_status = 'upcoming';
}


$items_per_page = 15;
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($current_page - 1) * $items_per_page;


$interventions_data = [];
$total_interventions = 0;
if ($provider_id > 0) {
    $interventions_data = getProviderInterventions($provider_id, $filter_status, $items_per_page, $offset);
    $total_interventions = $interventions_data['total'] ?? 0;
}
$interventions = $interventions_data['interventions'] ?? [];
$total_pages = ceil($total_interventions / $items_per_page);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <a href="<?= WEBCLIENT_URL ?>/modules/providers/dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                </a>
            </div>

            <?php echo displayFlashMessages(); ?>


            <ul class="nav nav-pills mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= ($filter_status === 'upcoming') ? 'active' : '' ?>" href="?filter=upcoming">À venir</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($filter_status === 'past') ? 'active' : '' ?>" href="?filter=past">Passées</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($filter_status === 'all') ? 'active' : '' ?>" href="?filter=all">Toutes</a>
                </li>
            </ul>

            <div class="card mb-4">
                <div class="card-header">
                    <?php
                    $filter_label = match ($filter_status) {
                        'upcoming' => 'Interventions à venir',
                        'past' => 'Historique des interventions passées',
                        'all' => 'Toutes les interventions programmées',
                        default => 'Interventions'
                    };
                    ?>
                    <?= htmlspecialchars($filter_label) ?> (<?= $total_interventions ?> au total pour ce filtre)
                </div>
                <div class="card-body">
                    <?php if (empty($interventions)): ?>
                        <p class="text-center text-muted">Aucune intervention trouvée pour ce filtre.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Date & Heure Début</th>
                                        <th>Durée</th>
                                        <th>Prestation</th>
                                        <th>Type</th>
                                        <th>Lieu / Site</th>
                                        <th>Statut</th>
                                        <th>Réservé?</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($interventions as $int): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date(DEFAULT_DATE_FORMAT . ' H:i', strtotime($int['start_time']))) ?></td>
                                            <td>
                                                <?php
                                                $duration = calculateInterventionDuration($int['start_time'], $int['end_time']);
                                                echo $duration !== null ? $duration . ' min' : 'N/A';
                                                ?>
                                            </td>
                                            <td><?= htmlspecialchars($int['prestation_nom']) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($int['prestation_type'] ?? 'N/A')) ?></td>
                                            <td>
                                                <?php if (!empty($int['site_nom'])): ?>
                                                    <span title="<?= htmlspecialchars($int['site_adresse'] . ', ' . $int['site_cp'] . ' ' . $int['site_ville']) ?>">
                                                        <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($int['site_nom']) ?>
                                                    </span>
                                                <?php elseif (in_array($int['prestation_type'], ['webinar', 'visio'])): ?>
                                                    <i class="fas fa-video me-1"></i> En ligne / Visio
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= getInterventionStatusBadgeClass($int['start_time']) ?>">
                                                    <?= htmlspecialchars(formatInterventionStatus($int['start_time'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($int['is_booked']): ?>
                                                    <span class="badge bg-success">Oui</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Non</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>


                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation interventions prestataires" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?filter=<?= $filter_status ?>&page=<?= $current_page - 1 ?>">Précédent</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?filter=<?= $filter_status ?>&page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?filter=<?= $filter_status ?>&page=<?= $current_page + 1 ?>">Suivant</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>


                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>