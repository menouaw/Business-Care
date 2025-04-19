<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireRole(ROLE_SALARIE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_SESSION['user_id'];
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $csrf_token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!validateToken($csrf_token)) {
        logSecurityEvent($employee_id, 'csrf_failure', "[SECURITY FAILURE] Tentative POST événement avec jeton invalide");
        flashMessage("Erreur de sécurité (jeton invalide). Veuillez réessayer.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/employees/events.php');
        exit;
    }

    if ($event_id) {
        if ($action === 'register_event') {
            handleRegisterForEvent($employee_id, $event_id);
        } elseif ($action === 'unregister_event') {
            handleUnregisterFromEvent($employee_id, $event_id);
        } else {
            flashMessage("Action invalide.", "danger");
        }
    } else {
        flashMessage("ID d'événement invalide.", "danger");
    }

    redirectTo(WEBCLIENT_URL . '/modules/employees/events.php');
    exit;
}


$pageData = displayEmployeeEventsPage();
$events = $pageData['events'] ?? [];
$currentTypeFilter = $pageData['currentTypeFilter'] ?? 'all';
$eventTypes = $pageData['eventTypes'] ?? [];

$pageTitle = "Événements - Espace Salarié";

include_once __DIR__ . '/../../templates/header.php';

?>

<main class="employee-events-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h1 class="h2">Événements</h1>
                <p class="text-muted">Découvrez et participez aux prochains événements organisés.</p>
            </div>
            <div class="col-md-6 text-md-end mb-2 mb-md-0">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <div class="row mb-4">
            <div class="col-md-6 text-md-end">
                <form action="" method="GET" class="d-inline-block">
                    <div class="input-group">
                        <label class="input-group-text" for="eventTypeFilter">Filtrer par type</label>
                        <select class="form-select" id="eventTypeFilter" name="type" onchange="this.form.submit()">
                            <option value="all" <?= ($currentTypeFilter === 'all') ? 'selected' : '' ?>>Tous les types</option>
                            <?php foreach ($eventTypes as $type) : ?>
                                <option value="<?= htmlspecialchars($type) ?>" <?= ($currentTypeFilter === $type) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst($type)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($events)) : ?>
            <div class="alert alert-info text-center" role="alert">
                Aucun événement à venir correspondant à vos filtres n'a été trouvé.
            </div>
        <?php else : ?>
            <div class="row g-4">
                <?php foreach ($events as $event) : ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="icon-box bg-success bg-opacity-10 text-success rounded p-3 me-3 flex-shrink-0">
                                        <i class="<?= getEventIcon($event['type'] ?? 'autre') ?> fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($event['titre'] ?? 'Événement sans titre') ?></h5>
                                        <span class="badge bg-secondary me-1"><?= htmlspecialchars(ucfirst($event['type'] ?? 'Autre')) ?></span>
                                        <?php if (!empty($event['niveau_difficulte'])) : ?>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars(ucfirst($event['niveau_difficulte'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="card-text text-muted small mb-2">
                                    <i class="far fa-calendar-alt me-1"></i> <?= htmlspecialchars($event['date_debut_formatee'] ?? 'Date inconnue') ?>
                                    <?php if (!empty($event['date_fin_formatee'])) : ?>
                                        - <?= htmlspecialchars($event['date_fin_formatee']) ?>
                                    <?php endif; ?>
                                </p>
                                <p class="card-text text-muted small mb-3">
                                    <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($event['lieu'] ?? 'Lieu à confirmer') ?>
                                </p>
                                <p class="card-text text-muted flex-grow-1"><?= nl2br(htmlspecialchars(substr($event['description'] ?? 'Pas de description.', 0, 150))) . (strlen($event['description'] ?? '') > 150 ? '...' : '') ?></p>
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($event['est_inscrit']) : ?>
                                            <form action="<?= WEBCLIENT_URL ?>/modules/employees/events.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="unregister_event">
                                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $pageData['csrf_token'] ?? '' ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-calendar-times me-1"></i> Se désinscrire
                                                </button>
                                            </form>
                                        <?php elseif ($event['est_complet']) : ?>
                                            <button class="btn btn-sm btn-warning" disabled>Complet</button>
                                        <?php else : ?>
                                            <form action="<?= WEBCLIENT_URL ?>/modules/employees/events.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="register_event">
                                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $pageData['csrf_token'] ?? '' ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-calendar-plus me-1"></i> Participer
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (isset($event['places_restantes']) && $event['places_restantes'] !== null) : ?>
                                        <small class="text-muted">
                                            <i class="fas fa-users me-1"></i>
                                            <?= htmlspecialchars($event['places_restantes']) ?> place<?= $event['places_restantes'] > 1 ? 's' : '' ?> restante<?= $event['places_restantes'] > 1 ? 's' : '' ?>
                                        </small>
                                    <?php elseif (isset($event['capacite_max']) && $event['capacite_max'] !== null):
                                    ?>
                                        <small class="text-muted"><i class="fas fa-users me-1"></i> <?= htmlspecialchars($event['capacite_max']) ?> places max</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 d-flex justify-content-center">
                <?= $pageData['pagination_html'] ?? ''
                ?>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>