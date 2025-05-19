<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/events.php';

$pageData = setupEventsPage();
$pageTitle = $pageData['pageTitle'];

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
            </div>

            <?php echo displayFlashMessages(); ?>

            <?php if ($pageData['quota_depasse']): ?>
            <div class="alert alert-warning mb-4">
                <i class="fas fa-exclamation-triangle"></i> Votre quota d'ateliers organisés par BC est atteint pour cette période. Les inscriptions sont désactivées jusqu'à la prochaine période.
            </div>
            <?php endif; ?>

            <?php if (!empty($pageData['preferredEvents'])): ?>
            <div class="mb-4">
                <h3 class="h4 mb-3">Événements recommandés pour vous</h3>
                <div class="row g-4">
                    <?php foreach ($pageData['preferredEvents'] as $event): ?>
                        <div class="col-md-4">
                            <div class="card h-100 d-flex flex-column <?= $event['quota_depasse'] ? 'border-warning' : '' ?>">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($event['titre']) ?></h5>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> <?= formatDate($event['date_debut'], 'd/m/Y H:i') ?>
                                            <?php if ($event['date_fin']): ?>
                                                - <?= formatDate($event['date_fin'], 'd/m/Y H:i') ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <?php if ($event['lieu']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['lieu']) ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($event['organise_par_bc']): ?>
                                    <div class="mb-2">
                                        <button class="btn btn-info btn-sm disabled" style="font-size: 0.75em; padding: 0.15rem 0.4rem;">Organisé par BC</button>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($event['capacite_max']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-users"></i> 
                                            <?= $event['current_registrations'] ?>/<?= $event['capacite_max'] ?> places occupées
                                            <?php if ($event['remaining_spots'] === 0): ?>
                                                <span class="text-danger">(Complet)</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                    <div class="mt-auto">
                                        <?php if ($event['is_registered']): ?>
                                            <a href="?action=unregister&id=<?= $event['id'] ?>" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i> Se désinscrire
                                            </a>
                                        <?php elseif ($event['quota_depasse']): ?>
                                            <button class="btn btn-warning btn-sm" disabled>
                                                <i class="fas fa-lock"></i> Quota dépassé
                                            </button>
                                        <?php elseif ($event['remaining_spots'] === 0): ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-ban"></i> Complet
                                            </button>
                                        <?php else: ?>
                                            <?php
                                            $btn_class = 'btn-primary'; 
                                            if ($event['organise_par_bc']) {
                                                $btn_class = 'btn-success'; 
                                            }
                                            ?>
                                            <a href="?action=register&id=<?= $event['id'] ?>" class="btn <?= $btn_class ?> btn-sm">
                                                <i class="fas fa-plus"></i> S'inscrire
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($pageData['otherEvents'])): ?>
            <div>
                <h3 class="h4 mb-3">Autres événements disponibles</h3>
                <div class="row g-4">
                    <?php foreach ($pageData['otherEvents'] as $event): ?>
                        <div class="col-md-4">
                            <div class="card h-100 d-flex flex-column <?= $event['quota_depasse'] ? 'border-warning' : '' ?>">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($event['titre']) ?></h5>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> <?= formatDate($event['date_debut'], 'd/m/Y H:i') ?>
                                            <?php if ($event['date_fin']): ?>
                                                - <?= formatDate($event['date_fin'], 'd/m/Y H:i') ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <?php if ($event['lieu']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['lieu']) ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($event['organise_par_bc']): ?>
                                    <div class="mb-2">
                                        <button class="btn btn-info btn-sm disabled" style="font-size: 0.75em; padding: 0.15rem 0.4rem;">Organisé par BC</button>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($event['capacite_max']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-users"></i> 
                                            <?= $event['current_registrations'] ?>/<?= $event['capacite_max'] ?> places occupées
                                            <?php if ($event['remaining_spots'] === 0): ?>
                                                <span class="text-danger">(Complet)</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                    <div class="mt-auto">
                                        <?php if ($event['is_registered']): ?>
                                            <a href="?action=unregister&id=<?= $event['id'] ?>" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i> Se désinscrire
                                            </a>
                                        <?php elseif ($event['quota_depasse']): ?>
                                            <button class="btn btn-warning btn-sm" disabled>
                                                <i class="fas fa-lock"></i> Quota dépassé
                                            </button>
                                        <?php elseif ($event['remaining_spots'] === 0): ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-ban"></i> Complet
                                            </button>
                                        <?php else: ?>
                                            <?php
                                            $btn_class = 'btn-primary'; 
                                            if ($event['organise_par_bc']) {
                                                $btn_class = 'btn-success'; 
                                            }
                                            ?>
                                            <a href="?action=register&id=<?= $event['id'] ?>" class="btn <?= $btn_class ?> btn-sm">
                                                <i class="fas fa-plus"></i> S'inscrire
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($pageData['preferredEvents']) && empty($pageData['otherEvents'])): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Aucun événement à venir n'est disponible pour le moment.
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

