<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireRole(ROLE_SALARIE);

$employee_id = $_SESSION['user_id'];

$page = filter_input(INPUT_GET, 'prestation_page', FILTER_VALIDATE_INT) ?: 1;
$limit = 6;

if (!function_exists('getAvailablePrestationsForEmployee')) {
    die("Erreur: La fonction getAvailablePrestationsForEmployee n'est pas définie.");
}
$prestationsData = getAvailablePrestationsForEmployee($employee_id, $page, $limit);
$prestations = $prestationsData['prestations'];
$pagination_html = $prestationsData['pagination_html'];
$totalPrestations = $prestationsData['pagination']['totalItems'];

$pageTitle = "Catalogue des Services";
$pageDescription = "Découvrez et réservez les prestations bien-être proposées.";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="display-5 mb-1"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="lead text-muted"><?= htmlspecialchars($pageDescription) ?></p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
        </a>
    </div>

    <?php displayFlashMessages(); ?>


    <?php if (empty($prestations)): ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fas fa-info-circle fa-3x mb-3"></i><br>
            Aucune prestation n'est disponible pour le moment.
        </div>
    <?php else: ?>
        <p class="text-muted mb-3"><?= $totalPrestations ?> prestation(s) trouvée(s).</p>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($prestations as $prestation): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm prestation-card">

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($prestation['nom']) ?></h5>

                            <div class="mb-2">
                                <?php if (!empty($prestation['type'])): ?>
                                    <span class="badge bg-info me-1"><?= htmlspecialchars(ucfirst($prestation['type'])) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($prestation['categorie'])): ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($prestation['categorie'])) ?></span>
                                <?php endif; ?>
                            </div>

                            <p class="card-text small text-muted flex-grow-1">
                                <?= htmlspecialchars(substr($prestation['description'] ?? '', 0, 120)) ?>...
                            </p>

                            <ul class="list-unstyled small text-muted mb-3">
                                <?php if (isset($prestation['duree']) && $prestation['duree'] > 0): ?>
                                    <li><i class="fas fa-clock me-2"></i><?= $prestation['duree'] ?> minutes</li>
                                <?php endif; ?>
                                <?php if (!empty($prestation['praticien_nom'])): ?>
                                    <li><i class="fas fa-user-md me-2"></i><?= htmlspecialchars($prestation['praticien_nom']) ?></li>
                                <?php endif; ?>
                                <?php if (!empty($prestation['lieu'])): ?>
                                    <li><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($prestation['lieu']) ?></li>
                                <?php endif; ?>
                                <li><i class="fas fa-euro-sign me-2"></i><?= htmlspecialchars($prestation['prix_formate']) ?></li>
                            </ul>

                            <div class="mt-auto text-center">
                                <a href="catalog_detail.php?id=<?= $prestation['id'] ?>" class="btn btn-primary btn-sm w-100">
                                    Voir les détails / Réserver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($pagination_html): ?>
            <div class="d-flex justify-content-center mt-5">
                <?= $pagination_html ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</main>


<?php
include_once __DIR__ . '/../../templates/footer.php';
?>