<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/signalements.php';

$viewData = setupSignalementPage();
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

            <div class="alert alert-info" role="alert">
                <i class="fas fa-shield-alt me-2"></i>
                <strong>Anonymat Garanti :</strong> Votre identité ne sera pas enregistrée avec ce signalement.
                Les informations soumises seront traitées de manière confidentielle.
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Décrivez votre signalement</h5>
                </div>
                <div class="card-body">
                    <form action="signalements.php" method="post" id="signalement-form">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <div class="mb-3">
                            <label for="sujet" class="form-label">Sujet (Optionnel)</label>
                            <input type="text" class="form-control" id="sujet" name="sujet" maxlength="255" placeholder="Ex: Problème de sécurité, Harcèlement, Suggestion...">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description*</label>
                            <textarea class="form-control" id="description" name="description" rows="6" required placeholder="Veuillez décrire précisément la situation, les faits, les personnes impliquées (si pertinent et sans vous mettre en danger), et le lieu/moment si possible."></textarea>
                            <div class="form-text">Soyez aussi précis que possible tout en restant factuel.</div>
                        </div>

                        <button type="submit" name="submit_signalement" class="btn btn-danger">
                            <i class="fas fa-paper-plane me-1"></i> Envoyer le signalement anonymement
                        </button>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>