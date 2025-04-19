<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

$action = $_GET['action'] ?? 'list';
$selected_prestation_id = isset($_GET['prestation_id']) ? filter_var($_GET['prestation_id'], FILTER_VALIDATE_INT) : null;
$selected_date = isset($_GET['selected_date']) ? sanitizeInput($_GET['selected_date']) : null;
$selected_creneau_id = isset($_GET['creneau_id']) ? filter_var($_GET['creneau_id'], FILTER_VALIDATE_INT) : null;

if ($action === 'book_slot') {
    requireRole(ROLE_SALARIE);
    if (!isset($_GET['csrf_token']) || !validateToken($_GET['csrf_token'])) { // Vérifier CSRF dans GET
        handleClientCsrfFailureRedirect('réservation de créneau', WEBCLIENT_URL . '/modules/employees/services.php');
        exit;
    }
    $employee_id = $_SESSION['user_id'];

    if ($selected_creneau_id) {
        $success = processSlotBooking($employee_id, $selected_creneau_id);
        if ($success) {
            redirectTo(WEBCLIENT_URL . '/modules/employees/appointments.php');
            exit;
        } else {
            $redirectUrl = WEBCLIENT_URL . "/modules/employees/services.php?action=select_date&prestation_id=" . ($selected_prestation_id ?? '');
            redirectTo($redirectUrl);
            exit;
        }
    } else {
        flashMessage('Aucun créneau sélectionné.', 'danger');
        $redirectUrl = WEBCLIENT_URL . "/modules/employees/services.php?action=select_date&prestation_id=" . ($selected_prestation_id ?? '');
        redirectTo($redirectUrl);
        exit;
    }
}

$pageViewData = getServiceCatalogPageData($action, $selected_prestation_id, $selected_date);

extract($pageViewData);


include_once __DIR__ . '/../../templates/header.php';

?>

<main class="employee-services-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2 mb-0"><?= htmlspecialchars($pageTitle) // Utiliser le titre dynamique 
                                    ?></h1>
                <?php if ($action === 'list'): ?>
                    <p class="text-muted mb-0">Découvrez les prestations et services proposés par Business Care.</p>
                <?php endif; ?>
            </div>
            <div class="col-auto">
                <?php if ($action === 'select_slot'):
                ?>
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/services.php?action=select_date&prestation_id=<?= $selected_prestation_id ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Changer Date
                    </a>
                <?php elseif ($action !== 'list'): ?>
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/services.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour Catalogue
                    </a>
                <?php else: ?>
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php echo displayFlashMessages(); ?>

        <?php if (!$hasActiveContract && $action === 'list'): ?>
            <div class="alert alert-warning">Votre entreprise n'a pas de contrat actif pour accéder aux services.</div>
        <?php else:
        ?>

            <?php if ($action === 'list'): ?>
                <div class="row mb-4 g-2">
                    <form action="" method="GET" class="d-flex flex-wrap">
                        <div class="col-md-4 col-sm-6 mb-2 me-2">
                            <div class="input-group">
                                <label class="input-group-text" for="serviceTypeFilter">Type</label>
                                <select class="form-select" id="serviceTypeFilter" name="type" onchange="this.form.submit()">
                                    <option value="" <?= ($currentTypeFilter === '') ? 'selected' : '' ?>>Tous les types</option>
                                    <?php foreach ($types as $type) : ?>
                                        <option value="<?= htmlspecialchars($type) ?>" <?= ($currentTypeFilter === $type) ? 'selected' : '' ?>>
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
                                    <option value="" <?= ($currentCategoryFilter === '') ? 'selected' : '' ?>>Toutes les catégories</option>
                                    <?php foreach ($categories as $category) : ?>
                                        <option value="<?= htmlspecialchars($category) ?>" <?= ($currentCategoryFilter === $category) ? 'selected' : '' ?>>
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
                <!-- Liste des Services -->
                <?php if (empty($services)): ?>
                    <div class="alert alert-info text-center" role="alert">
                        Aucun service trouvé correspondant à vos filtres.
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($services as $service): ?>
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
                                            <?php if (isset($service['prix_formate'])) : // Use prix_formate if available 
                                            ?><li><i class="fas fa-euro-sign me-2"></i><?= htmlspecialchars($service['prix_formate']) ?></li><?php endif; ?>
                                        </ul>
                                        <div class="mt-auto">
                                            <?php if ($service['type'] === 'consultation'): ?>
                                                <a href="<?= WEBCLIENT_URL ?>/modules/employees/services.php?action=select_date&prestation_id=<?= $service['id'] ?>" class="btn btn-sm btn-primary w-100">
                                                    Choisir un créneau
                                                </a>
                                            <?php else: ?>
                                                <form action="#" method="POST" class="d-inline">
                                                    <input type="hidden" name="prestation_id" value="<?= $service['id'] ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <button type="submit" class="btn btn-sm btn-secondary w-100" disabled>Réserver (Autre)</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 d-flex justify-content-center">
                        <?= $paginationHtml ?? '' ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($action === 'select_date' && $selected_prestation_id): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Choisir un créneau pour : <?= htmlspecialchars($prestation_details['nom'] ?? 'Consultation') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($grouped_slots)): ?>
                            <div class="alert alert-warning text-center" role="alert">
                                <i class="fas fa-info-circle me-2"></i> Il n'y a actuellement aucun créneau disponible pour cette prestation.
                                <br><a href="<?= WEBCLIENT_URL ?>/modules/employees/services.php" class="alert-link mt-2 d-inline-block">Retour au catalogue</a>
                            </div>
                        <?php else: ?>
                            <form method="GET" action="<?= WEBCLIENT_URL ?>/modules/employees/services.php">
                                <input type="hidden" name="action" value="book_slot">
                                <input type="hidden" name="prestation_id" value="<?= $selected_prestation_id ?>">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                                <?php foreach ($grouped_slots as $date => $slots): ?>
                                    <h6 class="mt-3 mb-2 border-bottom pb-1"><i class="far fa-calendar-alt me-2"></i><?= htmlspecialchars(formatDate($date, 'l d F Y')) ?></h6>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($slots as $slot): ?>
                                            <label class="list-group-item list-group-item-action ps-0">
                                                <input class="form-check-input me-2" type="radio" name="creneau_id" value="<?= $slot['id'] ?>" required>
                                                <span class="fw-bold"><?= htmlspecialchars(formatDate($slot['start_time'], 'H:i')) ?> - <?= htmlspecialchars(formatDate($slot['end_time'], 'H:i')) ?></span>
                                                <?php if (!empty($slot['praticien_nom'])): ?>
                                                    <small class="text-muted ms-2">(avec <?= htmlspecialchars($slot['praticien_nom']) ?>)</small>
                                                <?php endif; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>

                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/services.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Annuler</a>
                                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Réserver le créneau sélectionné</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($action === 'select_slot' && $selected_prestation_id && $selected_date): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header">Choisir un créneau pour le <?= htmlspecialchars(formatDate($selected_date, 'd/m/Y')) ?></div>
                    <div class="card-body">
                        <form method="GET" action="<?= WEBCLIENT_URL ?>/modules/employees/services.php">
                            <input type="hidden" name="action" value="book_slot">
                            <input type="hidden" name="prestation_id" value="<?= $selected_prestation_id ?>">
                            <input type="hidden" name="selected_date" value="<?= $selected_date ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                            <h5>Créneaux disponibles :</h5>
                            <?php if (empty($available_slots)): ?>
                                <p class="text-danger">Aucun créneau disponible pour cette date.</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($available_slots as $slot): ?>
                                        <label class="list-group-item">
                                            <input class="form-check-input me-1" type="radio" name="creneau_id" value="<?= $slot['id'] ?>" required>
                                            <?= htmlspecialchars(formatDate($slot['start_time'], 'H:i')) ?> - <?= htmlspecialchars(formatDate($slot['end_time'], 'H:i')) ?>
                                            <?php if (!empty($slot['praticien_nom'])): // Afficher praticien si dispo 
                                            ?>
                                                <small class="text-muted ms-2">(avec <?= htmlspecialchars($slot['praticien_nom']) ?>)</small>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <button type="submit" class="btn btn-success mt-3">Réserver le créneau sélectionné</button>
                            <?php endif; ?>
                            <a href="<?= WEBCLIENT_URL ?>/modules/employees/services.php?action=select_date&prestation_id=<?= $selected_prestation_id ?>" class="btn btn-secondary ms-2 mt-3">Changer de date</a>
                        </form>
                    </div>
                </div>
            <?php else:
                // Determine the specific reason for falling into this block
                $debug_message = "Action non reconnue ou données manquantes.";
                if ($action === 'select_date' && !$selected_prestation_id) {
                    $debug_message = "Action 'select_date' reçue mais l'ID de la prestation est manquant ou invalide.";
                } elseif (!in_array($action, ['list', 'select_date', 'select_slot'])) { // 'select_slot' is technically still checked
                    $debug_message = "L'action reçue ('" . htmlspecialchars($action) . "') n'est pas gérée.";
                }
                // Log the issue for backend debugging
                error_log("[WARNING] services.php reached final else block. Action: '$action', Prestation ID: '$selected_prestation_id'. Message: $debug_message");
            ?>
                <div class="alert alert-warning">
                    <?= htmlspecialchars($debug_message) ?>
                    Veuillez <a href="<?= WEBCLIENT_URL ?>/modules/employees/services.php" class="alert-link">retourner au catalogue</a> et réessayer.
                </div>
            <?php endif; ?>

        <?php endif;
        ?>
    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>