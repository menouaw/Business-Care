<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireRole(ROLE_SALARIE);

$errors = [];
$submittedData = [
    'sujet' => $_POST['sujet'] ?? '',
    'description' => $_POST['description'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = processSignalementSubmission($_POST);

    if (isset($errors['csrf'])) {
        redirectTo(WEBCLIENT_URL . '/modules/employees/signalement.php');
        exit;
    }

    if (empty($errors)) {
        $submittedData = [];
    }
}

$csrfToken = generateToken();
$_SESSION['csrf_token'] = $csrfToken;

$pageTitle = "Faire un Signalement Anonyme";
include_once __DIR__ . '/../../templates/header.php';

?>

<main class="employee-report-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2"><?= $pageTitle ?></h1>
                <p class="text-muted">Signalez une situation critique de manière confidentielle et anonyme.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading"><i class="fas fa-shield-alt me-2"></i>Votre Anonymat est Garanti</h4>
            <p>Ce formulaire vous permet de signaler une situation préoccupante (harcèlement, discrimination, problème de sécurité, mal-être, etc.) de manière totalement anonyme. Votre identité ne sera ni enregistrée ni transmise.</p>
            <hr>
            <p class="mb-0">Votre signalement sera traité de manière confidentielle par l'équipe compétente.</p>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Détails du Signalement</h5>
            </div>
            <div class="card-body">
                <form action="<?= WEBCLIENT_URL ?>/modules/employees/signalement.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="mb-3">
                        <label for="sujet" class="form-label">Sujet (Optionnel)</label>
                        <input type="text" class="form-control <?php echo isset($errors['sujet']) ? 'is-invalid' : ''; ?>" id="sujet" name="sujet" value="<?= htmlspecialchars($submittedData['sujet'] ?? '') ?>" maxlength="255">
                        <?php if (isset($errors['sujet'])) : ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['sujet']) ?></div>
                        <?php endif; ?>
                        <small class="form-text text-muted">Un titre bref pour résumer la situation.</small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description détaillée <span class="text-danger">*</span></label>
                        <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="6" required><?= htmlspecialchars($submittedData['description'] ?? '') ?></textarea>
                        <?php if (isset($errors['description'])) : ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['description']) ?></div>
                        <?php endif; ?>
                        <small class="form-text text-muted">Décrivez la situation le plus précisément possible (faits, dates, lieux, personnes impliquées si pertinent, etc.).</small>
                    </div>

                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-paper-plane me-1"></i> Envoyer le Signalement Anonyme
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>