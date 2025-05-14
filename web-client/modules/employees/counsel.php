<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/counsel.php';

$viewData = setupCounselPage();
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

            <?php echo displayFlashMessages(); ?>

            <?php    
            if (isset($userServiceType) && $userServiceType === 'Starter Pack') :
            ?>
                <div class="alert alert-info shadow-sm mb-4" role="alert">
                    <h4 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Informations concernant votre Pack Starter</h4>
                    <p>Avec votre abonnement actuel <strong>Starter Pack</strong>, les <strong>conseils hebdomadaires</strong> (qu'ils soient personnalisés ou généraux) ne sont pas inclus.</p>
                    <p class="mb-0">Vous continuez cependant à bénéficier d'un accès illimité à toutes nos <strong>fiches pratiques</strong> de bien-être. Celles-ci apparaîtront ci-dessous si elles correspondent aux catégories sélectionnées.</p>
                </div>
            <?php endif; ?>

            <?php 
            ?>
            <?php if (!empty($preferredCounselsGrouped)): ?>
                <h2 class="text-primary mt-4 mb-3"><i class="fas fa-star me-2"></i>Pour Vous</h2>
                <?php foreach ($preferredCounselsGrouped as $categorie => $conseils): ?>
                    <h4 class="mt-4 mb-3"><?= htmlspecialchars($categorie) ?></h4>
                    <div class="list-group mb-4 shadow-sm">
                        <?php foreach ($conseils as $conseil):
                            $titre = htmlspecialchars($conseil['titre'] ?? 'Conseil sans titre');
                            $resume = !empty($conseil['resume']) ? htmlspecialchars($conseil['resume']) : 'Pas de résumé.';
                            
                            $icone = !empty($conseil['icone']) ? htmlspecialchars($conseil['icone']) : 'fas fa-lightbulb';
                            $detailUrl = WEBCLIENT_URL . '/modules/employees/counsel_detail.php?id=' . $conseil['id'];
                        ?>
                            <a href="<?= $detailUrl ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h5 class="mb-1">
                                        <i class="<?= $icone ?> me-2 text-info"></i><?= $titre ?>
                                    </h5>
                                    <small class="text-muted">Voir le détail <i class="fas fa-arrow-right"></i></small>
                                </div>
                                <?php if (!empty($conseil['resume'])): ?>
                                    <p class="mb-1 mt-2 ms-4 ps-1 small text-muted"><?= $resume ?></p>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <hr class="my-5">
            <?php endif; ?>

            <?php 
            ?>
            <?php if (!empty($otherCounselsGrouped)): ?>
                <h2 class="mt-4 mb-3">Autres Conseils</h2>
                <?php foreach ($otherCounselsGrouped as $categorie => $conseils): ?>
                    <h4 class="mt-4 mb-3"><?= htmlspecialchars($categorie) ?></h4>
                    <div class="list-group mb-4 shadow-sm">
                        <?php foreach ($conseils as $conseil):
                            $titre = htmlspecialchars($conseil['titre'] ?? 'Conseil sans titre');
                            $resume = !empty($conseil['resume']) ? htmlspecialchars($conseil['resume']) : 'Pas de résumé.';
                            $icone = !empty($conseil['icone']) ? htmlspecialchars($conseil['icone']) : 'fas fa-book-open';
                            $detailUrl = WEBCLIENT_URL . '/modules/employees/counsel_detail.php?id=' . $conseil['id'];
                        ?>
                            <a href="<?= $detailUrl ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h5 class="mb-1">
                                        <i class="<?= $icone ?> me-2 text-secondary"></i><?= $titre ?>
                                    </h5>
                                    <small class="text-muted">Voir le détail <i class="fas fa-arrow-right"></i></small>
                                </div>
                                <?php if (!empty($conseil['resume'])): ?>
                                    <p class="mb-1 mt-2 ms-4 ps-1 small text-muted"><?= $resume ?></p>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php 
            ?>
            <?php if (empty($preferredCounselsGrouped) && empty($otherCounselsGrouped)): ?>
                <div class="alert alert-info">Aucun conseil bien-être n'est disponible pour les catégories de cette page.</div>
            <?php endif; ?>

            <?php
            
            $paginationUrlPattern = WEBCLIENT_URL . '/modules/employees/counsel.php?page={page}';
            echo renderPagination($pagination, $paginationUrlPattern);
            ?>

        </main>
    </div>
</div>
