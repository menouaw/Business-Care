<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers/services.php';

requireRole(ROLE_PRESTATAIRE);

$provider_id = $_SESSION['user_id'] ?? 0;
$pageTitle = "Mes Services Habilités";


$assigned_services = [];
if ($provider_id > 0) {
    $assigned_services = getProviderAssignedServices($provider_id);
}

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

            <div class="card">
                <div class="card-header">
                    Liste des services pour lesquels vous êtes habilité(e) (<?= count($assigned_services) ?> service(s))
                </div>
                <div class="card-body">
                    <?php if (empty($assigned_services)): ?>
                        <div class="alert alert-info text-center" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucun service ne vous est actuellement assigné. Vous pourriez avoir besoin de <a href="<?= WEBCLIENT_URL ?>/modules/providers/habilitations.php">mettre à jour vos habilitations</a> ou contacter le support si vous pensez qu'il s'agit d'une erreur.
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($assigned_services as $service): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm service-card">
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title text-primary mb-2"><?= htmlspecialchars($service['nom']) ?></h5>
                                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars(formatServiceType($service['type'])) ?> - <?= htmlspecialchars($service['categorie'] ?? 'N/A') ?></h6>
                                            <p class="card-text flex-grow-1 small text-muted"><em><?= htmlspecialchars($service['description']) ?></em></p>
                                            <ul class="list-unstyled small mt-auto mb-0 service-details">
                                                <li><i class="fas fa-clock fa-fw me-2 text-secondary"></i> <strong>Durée:</strong> <?= htmlspecialchars($service['duree']) ?> min</li>
                                                <?php if (!empty($service['capacite_max']) && $service['capacite_max'] > 1): ?>
                                                    <li><i class="fas fa-users fa-fw me-2 text-secondary"></i> <strong>Capacité:</strong> <?= htmlspecialchars($service['capacite_max']) ?> pers.</li>
                                                <?php endif; ?>
                                                <?php if (!empty($service['niveau_difficulte'])): ?>
                                                    <li><i class="fas fa-tachometer-alt fa-fw me-2 text-secondary"></i> <strong>Niveau:</strong> <?= htmlspecialchars(ucfirst($service['niveau_difficulte'])) ?></li>
                                                <?php endif; ?>
                                                <?php if (!empty($service['materiel_necessaire'])): ?>
                                                    <li><i class="fas fa-tools fa-fw me-2 text-secondary"></i> <strong>Matériel:</strong> <?= htmlspecialchars($service['materiel_necessaire']) ?></li>
                                                <?php endif; ?>
                                                <?php if (!empty($service['prerequis'])): ?>
                                                    <li><i class="fas fa-clipboard-check fa-fw me-2 text-secondary"></i> <strong>Prérequis:</strong> <?= htmlspecialchars($service['prerequis']) ?></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-muted small">
                    Cette liste reflète les services que vous pouvez potentiellement réaliser via la plateforme Business Care, basée sur vos habilitations enregistrées.
                </div>
            </div>

        </main>
    </div>
</div>

