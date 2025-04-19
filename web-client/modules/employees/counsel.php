<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireRole(ROLE_SALARIE);

$pageData = displayCounselPageData();
$counselTopics = $pageData['counselTopics'];
$dbError = $pageData['dbError'];

$pageTitle = "Conseils Bien-être - Espace Salarié";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="employee-counsel-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2 mb-0"><i class="fas fa-heartbeat me-2"></i>Conseils Bien-être</h1>
                <p class="text-muted mb-0">Retrouvez ici des articles et conseils pour améliorer votre qualité de vie au travail et personnelle.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Dashboard
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <?php if ($dbError): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($dbError) ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (empty($counselTopics) && !$dbError): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center" role="alert">
                        Aucun conseil disponible pour le moment.
                    </div>
                </div>
            <?php elseif (!empty($counselTopics)): ?>
                <?php foreach ($counselTopics as $topic): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="icon-box bg-success bg-opacity-10 text-success rounded p-3 me-3 flex-shrink-0">
                                        <i class="<?= htmlspecialchars($topic['icone'] ?? 'fas fa-info-circle') ?> fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($topic['titre'] ?? 'Conseil sans titre') ?></h5>
                                        <span class="badge bg-light text-dark"><?= htmlspecialchars($topic['categorie'] ?? 'Général') ?></span>
                                    </div>
                                </div>
                                <p class="card-text text-muted flex-grow-1"><?= htmlspecialchars($topic['resume'] ?? 'Pas de résumé.') ?></p>
                                <p class="card-text text-muted small flex-grow-1">
                                    <i><?= htmlspecialchars(substr($topic['contenu'] ?? '', 0, 150)) . (strlen($topic['contenu'] ?? '') > 150 ? '...' : '') ?></i>
                                </p>
                                <div class="mt-auto">
                                    <!-- Link should eventually go to a detail page, e.g., counsel_detail.php?id=<?= $topic['id'] ?> -->
                                    <a href="#" class="btn btn-sm btn-outline-success disabled">Lire la suite (Bientôt disponible)</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>