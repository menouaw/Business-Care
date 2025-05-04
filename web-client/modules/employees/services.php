<?php
require_once __DIR__ . '/../../includes/init.php';

require_once __DIR__ . '/../../includes/page_functions/modules/employees/services.php';


function renderServicesList($prestations, $colorTheme)
{
    if (empty($prestations)) {
        return;
    }

    foreach ($prestations as $prestation) {
        $detailUrl = WEBCLIENT_URL . '/modules/employees/service_detail.php?id=' . $prestation['id'];
        $categoryLower = strtolower($prestation['categorie'] ?? 'default');
        $prestationType = $prestation['type'] ?? '';

        $icon = 'fas fa-concierge-bell';
        if (str_contains($categoryLower, 'mental') || str_contains($categoryLower, 'stress') || str_contains($categoryLower, 'psycholo')) $icon = 'fas fa-brain';
        elseif (str_contains($categoryLower, 'physique') || str_contains($categoryLower, 'yoga') || str_contains($categoryLower, 'massage')) $icon = 'fas fa-heartbeat';
        elseif (str_contains($categoryLower, 'nutrition')) $icon = 'fas fa-apple-alt';
        elseif (str_contains($categoryLower, 'formation') || str_contains($categoryLower, 'coaching')) $icon = 'fas fa-chalkboard-teacher';
        elseif (str_contains($categoryLower, 'ergonomie')) $icon = 'fas fa-chair';
        elseif (str_contains($categoryLower, 'sommeil')) $icon = 'fas fa-moon';
        elseif (str_contains($categoryLower, 'communication')) $icon = 'fas fa-comments';
?>
        <div class="col">
            <div class="card h-100 shadow-sm prestation-card border-start border-4 border-<?= htmlspecialchars($colorTheme) ?>">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">
                        <i class="<?= $icon ?> me-2 text-<?= htmlspecialchars($colorTheme) ?>"></i>
                        <?= htmlspecialchars($prestation['nom']) ?>
                    </h5>
                    <h6 class="card-subtitle mb-2 text-muted small">
                        Catégorie: <?= htmlspecialchars($prestation['categorie'] ?? 'N/A') ?>
                    </h6>
                    <p class="card-text small text-muted flex-grow-1">
                        <?= htmlspecialchars(truncateText($prestation['description'] ?? 'Pas de description.', 120)) ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2">
                        <span class="text-primary fw-bold"><?= formatMoney($prestation['prix'] ?? 0) ?></span>
                        <div>
                            <?php if ($prestationType === 'consultation') : ?>
                                <a href="<?= WEBCLIENT_URL . '/modules/employees/appointments.php?action=select_slot&service_id=' . $prestation['id'] ?>" class="btn btn-sm btn-success">Choisir Créneau</a>
                            <?php elseif (in_array($prestationType, ['conference', 'webinar', 'atelier', 'defi_sportif'])) : ?>
                                <a href="<?= WEBCLIENT_URL . '/modules/employees/events.php' ?>" class="btn btn-sm btn-info">Voir Détails / S'inscrire</a>
                            <?php endif; 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    } 
} 

$viewData = setupEmployeeServicesPage();
extract($viewData);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                </a>
            </div>

            <?= displayFlashMessages(); ?>

            <?php if (!empty($preferredPrestations)) : ?>
                <h2 class="text-primary mt-4 mb-3"><i class="fas fa-star me-2"></i>Pour Vous</h2>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
                    <?php renderServicesList($preferredPrestations, 'info'); ?>
                </div>
                <hr class="my-5">
            <?php endif; ?>

            <?php if (!empty($otherPrestations)) : ?>
                <h2 class="mt-4 mb-3">Autres Services</h2>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php renderServicesList($otherPrestations, 'secondary'); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($preferredPrestations) && empty($otherPrestations)) : ?>
                <div class="alert alert-info mt-4">Aucun service n'est disponible pour le moment.</div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>