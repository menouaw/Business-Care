<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/providers.php';

require_once __DIR__ . '/../../includes/page_functions/modules/providers/evaluations.php';

requireRole(ROLE_SALARIE);

$employee_id = $_SESSION['user_id'] ?? 0;
$provider_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$view_mode = $provider_id ? 'detail' : 'list'; 

$pageTitle = "Nos Prestataires"; 
$provider_details = null;
$available_providers = [];

if ($view_mode === 'detail') {
    
    if ($employee_id > 0) {
        $provider_details = getProviderProfileDetailsForEmployee($provider_id, $employee_id);
    }
    if ($provider_details) {
        $pageTitle = "Profil de " . htmlspecialchars($provider_details['prenom'] . ' ' . $provider_details['nom']);
    } else {
        flashMessage("Impossible d'accéder au profil de ce prestataire. Il n'est peut-être pas disponible pour votre entreprise ou l'ID est incorrect.", "warning");
        
        $view_mode = 'list';
        $provider_id = null; 
        $pageTitle = "Nos Prestataires"; 
    }
}

if ($view_mode === 'list') {
    
    $pageTitle = "Nos Prestataires";
    if ($employee_id > 0) {
        $available_providers = getAvailableProvidersForEmployee($employee_id);
    }
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <?php if ($view_mode === 'detail'): ?>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/providers.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php echo displayFlashMessages(); ?>

            <?php if ($view_mode === 'detail' && $provider_details): ?>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <img src="<?= htmlspecialchars($provider_details['photo_url'] ?? '/assets/images/icons/default-user.png') ?>"
                                    alt="Photo de <?= htmlspecialchars($provider_details['prenom'] . ' ' . $provider_details['nom']) ?>"
                                    class="img-fluid rounded-circle mb-3"
                                    style="max-width: 150px; height: auto;">

                                <?php if ($provider_details['average_rating'] !== null): ?>
                                    <div class="mb-2">
                                        <strong>Note moyenne :</strong> <?= formatRatingStars((int)round($provider_details['average_rating'])) ?>
                                        (<?= htmlspecialchars($provider_details['average_rating']) ?>/5 sur <?= $provider_details['total_ratings'] ?> évaluation<?= $provider_details['total_ratings'] > 1 ? 's' : '' ?>)
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted small">Pas encore d'évaluations.</p>
                                <?php endif; ?>

                            </div>
                            <div class="col-md-9">
                                <h3><?= htmlspecialchars($provider_details['prenom'] . ' ' . $provider_details['nom']) ?></h3>

                                <h5 class="mt-4">Spécialités proposées</h5>
                                <?php if (empty($provider_details['specialties'])): ?>
                                    <p class="text-muted">Aucune spécialité listée pour ce prestataire.</p>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($provider_details['specialties'] as $specialty): ?>
                                            <li class="list-group-item ps-0">
                                                <strong><?= htmlspecialchars($specialty['nom']) ?></strong>
                                                <?php if (!empty($specialty['description'])): ?>
                                                    <p class="small mb-0 text-muted"><?= htmlspecialchars($specialty['description']) ?></p>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <h5 class="mt-4">Habilitations Vérifiées</h5>
                                <?php if (empty($provider_details['habilitations'])): ?>
                                    <p class="text-muted">Aucune habilitation vérifiée pour ce prestataire.</p>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($provider_details['habilitations'] as $hab): ?>
                                            <li class="list-group-item ps-0">
                                                <strong><?= htmlspecialchars(ucfirst($hab['type'])) ?> :</strong> <?= htmlspecialchars($hab['nom_document']) ?>
                                                <?php if ($hab['organisme_emission']): ?>
                                                    <span class="text-muted small">(<?= htmlspecialchars($hab['organisme_emission']) ?>)</span>
                                                <?php endif; ?>
                                                <?php if ($hab['date_expiration']): ?>
                                                    <span class="text-muted small"> - Valide jusqu'au <?= htmlspecialchars(date('d/m/Y', strtotime($hab['date_expiration']))) ?></span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>
                

            <?php elseif ($view_mode === 'list'): ?>
                
                <p class="lead">Retrouvez ici les professionnels du bien-être accessibles via le programme de votre entreprise.</p>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php if (empty($available_providers)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center" role="alert">
                                Aucun prestataire n'est actuellement disponible correspondant aux services de votre contrat.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($available_providers as $provider): ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body d-flex flex-column">
                                        <div class="text-center mb-3">
                                            <img src="<?= htmlspecialchars($provider['photo_url'] ?? '/assets/images/icons/default-user.png') ?>" alt="Photo de <?= htmlspecialchars($provider['prenom'] . ' ' . $provider['nom']) ?>" class="avatar-md img-thumbnail">
                                        </div>
                                        <h5 class="card-title text-center mb-1"><?= htmlspecialchars($provider['prenom'] . ' ' . $provider['nom']) ?></h5>
                                        <?php /* if (!empty($provider['primary_specialty'])): ?>
                                            <p class="card-text text-center text-muted small mb-3"><?= htmlspecialchars($provider['primary_specialty']) ?></p>
                                        <?php endif; */ ?>
                                        <div class="mt-auto text-center">
                                            
                                            <a href="<?= WEBCLIENT_URL ?>/modules/employees/providers.php?id=<?= $provider['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                Voir le profil
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
            <?php endif; ?>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>