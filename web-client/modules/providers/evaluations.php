<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers.php'; // Contains getProviderRatings

requireRole(ROLE_PRESTATAIRE);
$provider_id = $_SESSION['user_id'];

$page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 1;
$page = $page ?: 1;

$ratingsData = getProviderRatings($provider_id, $page); // Fetches ratings, summary, pagination

$ratings = $ratingsData['ratings'] ?? [];
$summary = $ratingsData['summary'] ?? ['average' => 0, 'count' => 0];
$paginationHtml = $ratingsData['pagination_html'] ?? '';

$pageTitle = "Mes Évaluations - Espace Prestataire";

include_once __DIR__ . '/../../templates/header.php';

?>

<main class="provider-evaluations-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2"><i class="fas fa-star-half-alt me-2"></i><?= $pageTitle ?></h1>
                <p class="text-muted">Consultez les retours de vos clients sur vos prestations.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/providers/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <div class="card border-0 shadow-sm mb-4 bg-light">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-6 border-end">
                        <h5 class="mb-1">Note Moyenne</h5>
                        <p class="h3 mb-0"><?= number_format($summary['average'] ?? 0, 1) ?> / 5</p>
                        <div class="mt-1"><?= renderStars($summary['average'] ?? 0) ?></div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-1">Nombre Total d'Avis</h5>
                        <p class="h3 mb-0"><?= htmlspecialchars($summary['count'] ?? 0) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Dernières Évaluations Reçues</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ratings)) : ?>
                    <div class="alert alert-info mb-0 border-0 rounded-0" role="alert">
                        Vous n'avez pas encore reçu d'évaluation.
                    </div>
                <?php else : ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($ratings as $rating) : ?>
                            <div class="list-group-item px-3 py-3">
                                <div class="d-flex w-100 justify-content-between mb-2">
                                    <div>
                                        <h6 class="mb-0">
                                            <i class="fas fa-user-circle me-1 text-muted"></i> <?= htmlspecialchars($rating['client_nom'] ?? 'Client Anonyme') ?>
                                            <small class="text-muted ms-2"> pour </small>
                                            "<?= htmlspecialchars($rating['prestation_nom'] ?? 'Prestation Inconnue') ?>"
                                        </h6>
                                        <small class="text-muted"><?= htmlspecialchars($rating['prestation_type'] ? ucfirst($rating['prestation_type']) : '') ?></small>
                                    </div>
                                    <small class="text-nowrap text-muted" title="<?= htmlspecialchars($rating['date_evaluation'] ?? '') ?>"><?= htmlspecialchars($rating['date_evaluation_formatee'] ?? 'Date inconnue') ?></small>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2"><?= renderStars($rating['note'] ?? 0) ?></span>
                                    <span class="badge bg-primary"><?= htmlspecialchars($rating['note'] ?? '0') ?>/5</span>
                                </div>
                                <?php if (!empty($rating['commentaire'])) : ?>
                                    <p class="mb-0 fst-italic bg-light p-2 rounded border">"<?= nl2br(htmlspecialchars($rating['commentaire'])) ?>"</p>
                                <?php else: ?>
                                    <p class="mb-0 text-muted small ms-1">- Aucun commentaire laissé -</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($paginationHtml) : ?>
                <div class="card-footer bg-light border-top-0">
                    <nav aria-label="Evaluations navigation">
                        <?= $paginationHtml ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>