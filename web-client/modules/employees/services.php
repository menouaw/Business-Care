<?php


require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['prestation_id'], $_POST['csrf_token'])) {

        requireRole(ROLE_SALARIE);
        $employee_id = $_SESSION['user_id'];
        $prestation_id = filter_var($_POST['prestation_id'], FILTER_VALIDATE_INT);
        $success = false;
        $message = 'Erreur inconnue.';

        if ($prestation_id) {

            $dummy_appointment_data = [
                'prestation_id' => $prestation_id,
                'date_rdv'      => date('Y-m-d H:i:s', strtotime('+1 day')),
                'duree'         => 60,
                'type_rdv'      => 'visio',
                'notes'         => 'Réservation rapide depuis catalogue.'
            ];

            $result = bookEmployeeAppointment($employee_id, $dummy_appointment_data);

            if ($result) {
                $success = true;
                $message = "La prestation a bien été réservée (ID: $result). Statut : Planifié.";
            } else {

                $flashMessages = $_SESSION['flash_messages'] ?? [];
                $lastMessage = end($flashMessages);
                $message = $lastMessage['message'] ?? "Erreur lors de la tentative de réservation.";
            }
        } else {
            $message = "ID de prestation invalide.";
        }

        flashMessage($message, $success ? 'success' : 'danger');
        redirectTo(WEBCLIENT_URL . '/modules/employees/appointments.php');
        exit;
    }
}

if (isset($_SESSION['flash_messages'])) {
    unset($_SESSION['flash_messages']);
}

$pageData = displayServiceCatalog();
$services = $pageData['services'] ?? [];
$paginationHtml = $pageData['pagination_html'] ?? '';
$types = $pageData['types'] ?? [];
$categories = $pageData['categories'] ?? [];
$currentType = $pageData['currentTypeFilter'] ?? '';
$currentCategory = $pageData['currentCategoryFilter'] ?? '';
$hasActiveContract = $pageData['hasActiveContract'] ?? false;

$pageTitle = "Catalogue des Services - Espace Salarié";

include_once __DIR__ . '/../../templates/header.php';

$csrfToken = $_SESSION['csrf_token'] ?? '';

?>

<main class="employee-services-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2 mb-0">Catalogue des Services</h1>
                <p class="text-muted mb-0">Découvrez les prestations et services proposés par Business Care.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <?php
        if (!$hasActiveContract && !isset($_SESSION['flash_messages_displayed_once'])) :
            echo '<div class="alert alert-warning">Votre entreprise n\'a pas de contrat actif pour accéder aux services.</div>';
        endif;
        ?>

        <?php if ($hasActiveContract): ?>
            <div class="row mb-4 g-2">
                <form action="" method="GET" class="d-flex flex-wrap">
                    <div class="col-md-4 col-sm-6 mb-2 me-2">
                        <div class="input-group">
                            <label class="input-group-text" for="serviceTypeFilter">Type</label>
                            <select class="form-select" id="serviceTypeFilter" name="type" onchange="this.form.submit()">
                                <option value="" <?= ($currentType === '') ? 'selected' : '' ?>>Tous les types</option>
                                <?php foreach ($types as $type) : ?>
                                    <option value="<?= htmlspecialchars($type) ?>" <?= ($currentType === $type) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst($type)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 mb-2 me-2">
                        <div class="input-group">
                            <label class="input-group-text" for="serviceCategoryFilter">Catégorie</label>
                            <select class="form-select" id="serviceCategoryFilter" name="categorie" onchange="this.form.submit()">
                                <option value="" <?= ($currentCategory === '') ? 'selected' : '' ?>>Toutes les catégories</option>
                                <?php foreach ($categories as $category) : ?>
                                    <option value="<?= htmlspecialchars($category) ?>" <?= ($currentCategory === $category) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst($category)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-12 mb-2">
                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/services.php" class="btn btn-outline-secondary w-100">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <?php if (empty($services)) : ?>
                <div class="alert alert-info text-center" role="alert">
                    Aucun service trouvé correspondant à vos filtres.
                </div>
            <?php else : ?>
                <div class="row g-4">
                    <?php foreach ($services as $service) : ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="icon-box bg-primary bg-opacity-10 text-primary rounded p-3 me-3 flex-shrink-0">
                                            <i class="<?= getServiceIcon($service['type'] ?? 'autre') ?> fa-2x"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1"><?= htmlspecialchars($service['nom'] ?? 'Service sans nom') ?></h5>
                                            <span class="badge bg-secondary me-1"><?= htmlspecialchars(ucfirst($service['type'] ?? 'Autre')) ?></span>
                                            <?php if (!empty($service['categorie'])) : ?>
                                                <span class="badge bg-light text-dark me-1"><?= htmlspecialchars(ucfirst($service['categorie'])) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($service['niveau_difficulte'])) : ?>
                                                <span class="badge bg-light text-dark"><?= htmlspecialchars(ucfirst($service['niveau_difficulte'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted flex-grow-1"><?= nl2br(htmlspecialchars(substr($service['description'] ?? 'Pas de description.', 0, 120))) . (strlen($service['description'] ?? '') > 120 ? '...' : '') ?></p>
                                    <ul class="list-unstyled text-muted small mb-3">
                                        <?php if (isset($service['duree'])) : ?><li><i class="far fa-clock me-2"></i><?= htmlspecialchars($service['duree']) ?> min</li><?php endif; ?>
                                        <?php if (isset($service['prix'])) : ?><li><i class="fas fa-euro-sign me-2"></i><?= htmlspecialchars($service['prix_formate']) ?></li><?php endif; ?>
                                    </ul>
                                    <div class="mt-auto">
                                        <form action="<?= WEBCLIENT_URL ?>/modules/employees/services.php" method="POST" class="d-inline reservation-form">
                                            <input type="hidden" name="prestation_id" value="<?= $service['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">Réserver</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4 d-flex justify-content-center">
                    <?= $paginationHtml ?>
                </div>
            <?php endif; ?>
        <?php endif;
        ?>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>