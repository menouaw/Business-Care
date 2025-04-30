<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/counsel_detail.php';

$viewData = setupCounselDetailPage();
extract($viewData);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <?php if ($conseil && !empty($conseil['icone'])): ?>
                        <i class="<?= htmlspecialchars($conseil['icone']) ?> me-2 text-primary"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($pageTitle) ?>
                </h1>
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/counsel.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour aux Conseils
                </a>
            </div>

            <?php echo displayFlashMessages(); ?>

            <?php if (!$conseil): ?>
                <div class="alert alert-warning">Le conseil demandé n'a pas pu être chargé ou n'existe pas.</div>
            <?php else:
                $contenu_display = !empty($conseil['contenu']) ? nl2br(htmlspecialchars(html_entity_decode($conseil['contenu'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8')) : 'Pas de contenu disponible.';
                $categorie = htmlspecialchars($conseil['categorie'] ?? 'Non classé');
                $resume = !empty($conseil['resume']) ? htmlspecialchars($conseil['resume']) : '';
            ?>

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <strong class="text-primary">Catégorie :</strong> <?= $categorie ?>
                    </div>
                    <div class="card-body">
                        <?php if ($resume): ?>
                            <p class="text-muted fst-italic mb-4"><?= $resume ?></p>
                        <?php endif; ?>
                        <div class="content-display lh-lg">
                            <?= $contenu_display ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>