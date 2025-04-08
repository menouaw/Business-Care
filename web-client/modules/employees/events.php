<?php


require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';


$pageData = displayEmployeeEventsPage();
$events = $pageData['events'] ?? [];
$currentTypeFilter = $pageData['currentTypeFilter'] ?? 'all';
$eventTypes = $pageData['eventTypes'] ?? [];

$pageTitle = "Événements - Espace Salarié";

include_once __DIR__ . '/../../templates/header.php';

function getEventIcon($type)
{
    switch ($type) {
        case 'conference':
            return 'fas fa-chalkboard-teacher';
        case 'webinar':
            return 'fas fa-desktop';
        case 'atelier':
            return 'fas fa-tools';
        case 'defi_sportif':
            return 'fas fa-running';
        default:
            return 'fas fa-calendar-alt';
    }
}

?>

<main class="employee-events-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h1 class="h2">Événements</h1>
                <p class="text-muted">Découvrez et participez aux prochains événements organisés.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <!-- Filtre par type -->
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
                                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/event_details.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline-success">Voir les détails</a>
                                    <?php if (!empty($event['capacite_max'])) : ?>
                                        <small class="text-muted"><i class="fas fa-users me-1"></i> <?= htmlspecialchars($event['capacite_max']) ?> places</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../../templates/footer.php';
?>