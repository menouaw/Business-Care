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

            <?php if (empty($conseilsGrouped)): 
            ?>
                <div class="alert alert-info">Aucun conseil bien-être n'est disponible pour le moment ou pour cette page.</div>
                <?php else:
                
                foreach ($conseilsGrouped as $categorie => $conseils):
                ?>
                    <h3 class="mt-4 mb-3"><?= htmlspecialchars($categorie) ?></h3>
                    <div class="list-group mb-4">
                        <?php
                        
                        foreach ($conseils as $conseil):
                            $titre = htmlspecialchars($conseil['titre'] ?? 'Conseil sans titre');
                            $resume = !empty($conseil['resume']) ? htmlspecialchars($conseil['resume']) : 'Pas de résumé.';
                            $icone = 'fas fa-info-circle'; 
                            $detailUrl = WEBCLIENT_URL . '/modules/employees/counsel_detail.php?id=' . $conseil['id'];
                        ?>
                            <a href="<?= $detailUrl ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h5 class="mb-1">
                                        <i class="<?= $icone ?> me-2 text-primary"></i><?= $titre ?>
                                    </h5>
                                    <small class="text-muted">Voir le détail</small>
                                </div>
                                <p class="mb-1 mt-2 ms-4 ps-1 small text-muted"><?= $resume ?></p>
                            </a>
                        <?php endforeach; 
                        ?>
                    </div>
            <?php
                endforeach; 
            endif; 
            ?>

            <?php 
            
            $paginationUrlPattern = WEBCLIENT_URL . '/modules/employees/counsel.php?page={page}';
            echo renderPagination($pagination, $paginationUrlPattern);
            ?>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>