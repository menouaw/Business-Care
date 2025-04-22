<?php
require_once __DIR__ . '/../../includes/init.php';

requireRole(ROLE_SALARIE);

$conseil_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$conseil_id) {
    flashMessage('ID de conseil invalide.', 'danger');
    redirectTo(WEBCLIENT_URL . '/modules/employees/counsel.php');
    exit;
}

$conseil = null;
$dbError = null;

try {
    $conseil = fetchOne('conseils', 'id = :id', [':id' => $conseil_id]);
} catch (Exception $e) {
    logSystemActivity('error', 'Erreur chargement conseil ID ' . $conseil_id . ': ' . $e->getMessage());
    $dbError = 'Une erreur est survenue lors du chargement du conseil.';
}

if (!$conseil && !$dbError) {
    flashMessage('Conseil non trouvé.', 'warning');
    redirectTo(WEBCLIENT_URL . '/modules/employees/counsel.php');
    exit;
}

$pageTitle = $conseil ? htmlspecialchars($conseil['titre']) : "Détail du Conseil";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="conseil-detail-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2 mb-0">
                    <?php if ($conseil): ?>
                        <i class="<?= htmlspecialchars($conseil['icone'] ?? 'fas fa-info-circle') ?> me-2"></i>
                        <?= htmlspecialchars($conseil['titre']) ?>
                    <?php else: ?>
                        Erreur
                    <?php endif; ?>
                </h1>
                <?php if ($conseil && !empty($conseil['categorie'])): ?>
                    <span class="badge bg-secondary"><?= htmlspecialchars($conseil['categorie']) ?></span>
                <?php endif; ?>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/counsel.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour aux Conseils
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <?php if ($dbError): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($dbError) ?>
            </div>
        <?php elseif ($conseil): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($conseil['resume'])): ?>
                        <p class="lead mb-4"><em><?= htmlspecialchars($conseil['resume']) ?></em></p>
                        <hr class="mb-4">
                    <?php endif; ?>

                    <div class="conseil-content">
                        <?php
                        // nl2br pour conserver les sauts de ligne
                        echo nl2br(htmlspecialchars($conseil['contenu']));
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?> 