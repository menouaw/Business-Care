<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers/availabilities.php';

requireRole(ROLE_PRESTATAIRE);

$provider_id = $_SESSION['user_id'] ?? 0;
$pageTitle = "Mes Disponibilités";
$is_editing = false;
$availability_to_edit = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_availability'])) {
    handleAvailabilityAddRequest($provider_id);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_availability'])) {
    handleAvailabilityUpdateRequest($provider_id);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_availability'])) {
    verifyCsrfToken();
    $availability_id_to_delete = filter_input(INPUT_POST, 'availability_id', FILTER_VALIDATE_INT);
    if ($availability_id_to_delete) {
        deleteProviderAvailability($availability_id_to_delete, $provider_id);
    } else {
        flashMessage("ID invalide pour la suppression.", "danger");
    }
    redirectTo(WEBCLIENT_URL . '/modules/providers/availabilities.php');
    exit;
}


$edit_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);
if ($edit_id) {
    $availability_to_edit = getProviderAvailabilityById($edit_id, $provider_id);
    if ($availability_to_edit) {
        $is_editing = true;
        $pageTitle = "Modifier Disponibilité";
    } else {
        flashMessage("Disponibilité à modifier non trouvée ou accès refusé.", "warning");
    }
}


$availabilities = [];
if (!$is_editing) {
    $availabilities = getProviderAvailabilities($provider_id);
}


$current_year = date('Y');
$current_month = date('n');

if (!$is_editing) {

    $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT, [
        'options' => ['default' => $current_year, 'min_range' => 2000, 'max_range' => 2100]
    ]);
    $month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT, [
        'options' => ['default' => $current_month, 'min_range' => 1, 'max_range' => 12]
    ]);


    $prev_month_ts = mktime(0, 0, 0, $month - 1, 1, $year);
    $next_month_ts = mktime(0, 0, 0, $month + 1, 1, $year);
    $prev_year = date('Y', $prev_month_ts);
    $prev_month = date('n', $prev_month_ts);
    $next_year = date('Y', $next_month_ts);
    $next_month = date('n', $next_month_ts);
    $month_name = date('F Y', mktime(0, 0, 0, $month, 1, $year));


    $calendar_days_data = getCalendarDaysData($provider_id, $year, $month);

    $calendar_html = generateCalendarHTML($year, $month, $calendar_days_data);
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if ($is_editing): ?>
                        <a href="<?= WEBCLIENT_URL ?>/modules/providers/availabilities.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-times me-1"></i> Annuler la Modification
                        </a>
                    <?php else: ?>
                        <a href="<?= WEBCLIENT_URL ?>/modules/providers/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                        </a>
                        <button class="btn btn-sm btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addAvailabilityModal">
                            <i class="fas fa-plus me-1"></i> Ajouter Disponibilité/Indisponibilité
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <?php if ($is_editing && $availability_to_edit): ?>

                <div class="card mb-4">
                    <div class="card-header">
                        Modifier l'entrée de disponibilité
                    </div>
                    <div class="card-body">
                        <form id="editAvailabilityForm" method="POST" action="<?= WEBCLIENT_URL ?>/modules/providers/availabilities.php">
                            <input type="hidden" name="update_availability" value="1">
                            <input type="hidden" name="availability_id" value="<?= htmlspecialchars($availability_to_edit['id']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

                            <div class="mb-3">
                                <label for="type_edit" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="type_edit" name="type" required>
                                    <option value="">-- Choisir --</option>
                                    <option value="recurrente" <?= ($availability_to_edit['type'] ?? '') === 'recurrente' ? 'selected' : '' ?>>Disponibilité Récurrente</option>
                                    <option value="specifique" <?= ($availability_to_edit['type'] ?? '') === 'specifique' ? 'selected' : '' ?>>Disponibilité Spécifique</option>
                                    <option value="indisponible" <?= ($availability_to_edit['type'] ?? '') === 'indisponible' ? 'selected' : '' ?>>Indisponibilité</option>
                                </select>
                                <div class="form-text">Choisissez le type, puis remplissez les champs correspondants ci-dessous.</div>
                            </div>

                            <hr>
                            <h6 class="text-muted">Champs pour Disponibilité Récurrente</h6>
                            <div id="recurrenteFields_edit" class="mb-3 p-3 border rounded bg-light">
                                <div class="mb-3">
                                    <label for="jour_semaine_edit" class="form-label">Jour de la semaine <span class="text-info">(requis si récurrent)</span></label>
                                    <select class="form-select" id="jour_semaine_edit" name="jour_semaine">
                                        <option value="">-- Choisir un jour --</option>
                                        <option value="1" <?= (isset($availability_to_edit['jour_semaine']) && $availability_to_edit['jour_semaine'] == 1) ? 'selected' : '' ?>>Lundi</option>
                                        <option value="2" <?= (isset($availability_to_edit['jour_semaine']) && $availability_to_edit['jour_semaine'] == 2) ? 'selected' : '' ?>>Mardi</option>
                                        <option value="3" <?= (isset($availability_to_edit['jour_semaine']) && $availability_to_edit['jour_semaine'] == 3) ? 'selected' : '' ?>>Mercredi</option>
                                        <option value="4" <?= (isset($availability_to_edit['jour_semaine']) && $availability_to_edit['jour_semaine'] == 4) ? 'selected' : '' ?>>Jeudi</option>
                                        <option value="5" <?= (isset($availability_to_edit['jour_semaine']) && $availability_to_edit['jour_semaine'] == 5) ? 'selected' : '' ?>>Vendredi</option>
                                        <option value="6" <?= (isset($availability_to_edit['jour_semaine']) && $availability_to_edit['jour_semaine'] == 6) ? 'selected' : '' ?>>Samedi</option>
                                        <option value="0" <?= (isset($availability_to_edit['jour_semaine']) && $availability_to_edit['jour_semaine'] !== null && $availability_to_edit['jour_semaine'] == 0) ? 'selected' : '' ?>>Dimanche</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="heure_debut_rec_edit" class="form-label">Heure de début <span class="text-info">(requis si récurrent)</span></label>
                                        <input type="time" class="form-control" id="heure_debut_rec_edit" name="heure_debut" value="<?= htmlspecialchars($availability_to_edit['heure_debut'] ? date('H:i', strtotime($availability_to_edit['heure_debut'])) : '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="heure_fin_rec_edit" class="form-label">Heure de fin <span class="text-info">(requis si récurrent)</span></label>
                                        <input type="time" class="form-control" id="heure_fin_rec_edit" name="heure_fin" value="<?= htmlspecialchars($availability_to_edit['heure_fin'] ? date('H:i', strtotime($availability_to_edit['heure_fin'])) : '') ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="recurrence_fin_edit" class="form-label">Fin de la récurrence (Optionnel)</label>
                                    <input type="date" class="form-control" id="recurrence_fin_edit" name="recurrence_fin" value="<?= htmlspecialchars($availability_to_edit['recurrence_fin'] ?? '') ?>">
                                    <div class="form-text">Laissez vide si la disponibilité est récurrente indéfiniment.</div>
                                </div>
                            </div>

                            <hr>
                            <h6 class="text-muted">Champs pour Disponibilité Spécifique ou Indisponibilité</h6>
                            <div id="specifiqueFields_edit" class="mb-3 p-3 border rounded bg-light">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="date_debut_edit" class="form-label">Date de début <span class="text-info">(requis si spécifique/indisponible)</span></label>
                                        <input type="date" class="form-control" id="date_debut_edit" name="date_debut" value="<?= htmlspecialchars($availability_to_edit['date_debut'] ? date('Y-m-d', strtotime($availability_to_edit['date_debut'])) : '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="date_fin_edit" class="form-label">Date de fin (Optionnel)</label>
                                        <input type="date" class="form-control" id="date_fin_edit" name="date_fin" value="<?= htmlspecialchars($availability_to_edit['date_fin'] ? date('Y-m-d', strtotime($availability_to_edit['date_fin'])) : '') ?>">
                                        <div class="form-text">Laissez vide si cela concerne une seule journée.</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="heure_debut_spec_edit" class="form-label">Heure de début (Optionnel)</label>
                                        <input type="time" class="form-control" id="heure_debut_spec_edit" name="heure_debut_specifique" value="<?= htmlspecialchars($availability_to_edit['heure_debut'] ? date('H:i', strtotime($availability_to_edit['heure_debut'])) : '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="heure_fin_spec_edit" class="form-label">Heure de fin (Optionnel)</label>
                                        <input type="time" class="form-control" id="heure_fin_spec_edit" name="heure_fin_specifique" value="<?= htmlspecialchars($availability_to_edit['heure_fin'] ? date('H:i', strtotime($availability_to_edit['heure_fin'])) : '') ?>">
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <div class="mb-3">
                                <label for="notes_edit" class="form-label">Notes (Optionnel)</label>
                                <textarea class="form-control" id="notes_edit" name="notes" rows="2"><?= htmlspecialchars($availability_to_edit['notes'] ?? '') ?></textarea>
                                <div class="form-text">Ajoutez des détails si nécessaire (ex: "Congés annuels").</div>
                            </div>

                            <div class="text-end">
                                <a href="<?= WEBCLIENT_URL ?>/modules/providers/availabilities.php" class="btn btn-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary">Enregistrer les Modifications</button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php else: ?>


                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Vue Calendrier</span>
                        <div class="calendar-nav">
                            <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn btn-sm btn-outline-secondary">&lt; Précédent</a>
                            <span class="mx-2"><strong><?php

                                                        $formatter = new IntlDateFormatter(
                                                            'fr_FR',
                                                            IntlDateFormatter::FULL,
                                                            IntlDateFormatter::NONE,
                                                            null,
                                                            IntlDateFormatter::GREGORIAN,
                                                            'MMMM yyyy'
                                                        );
                                                        echo htmlentities(ucfirst($formatter->format(mktime(0, 0, 0, $month, 1, $year))));
                                                        ?></strong></span>
                            <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn btn-sm btn-outline-secondary">Suivant &gt;</a>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <?= $calendar_html ?>
                        <div class="mt-2 small text-muted">
                            <span class="badge calendar-day-available me-1">&nbsp;</span> Disponible
                            <span class="badge calendar-day-unavailable me-1">&nbsp;</span> Indisponible
                        </div>
                    </div>
                </div>


                <div class="card mb-4">
                    <div class="card-header">
                        Vos créneaux de disponibilité et périodes d'indisponibilité enregistrés (Liste détaillée)
                    </div>
                    <div class="card-body">
                        <?php if (empty($availabilities)): ?>
                            <p class="text-center text-muted">Aucune disponibilité ou indisponibilité enregistrée pour le moment.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($availabilities as $av): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-<?= $av['type'] === 'indisponible' ? 'danger' : ($av['type'] === 'recurrente' ? 'info' : 'success') ?> me-2">
                                                <?= htmlspecialchars(ucfirst($av['type'])) ?>
                                            </span>
                                            <?= formatAvailabilityForDisplay($av) ?>
                                        </div>
                                        <div>
                                            <a href="<?= WEBCLIENT_URL ?>/modules/providers/availabilities.php?edit_id=<?= $av['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="<?= WEBCLIENT_URL ?>/modules/providers/availabilities.php" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette entrée ?');">
                                                <input type="hidden" name="delete_availability" value="1">
                                                <input type="hidden" name="availability_id" value="<?= $av['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>


                <div class="modal fade" id="addAvailabilityModal" tabindex="-1" aria-labelledby="addAvailabilityModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addAvailabilityModalLabel">Ajouter une Disponibilité / Indisponibilité</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="availabilityForm" method="POST" action="<?= WEBCLIENT_URL ?>/modules/providers/availabilities.php">
                                <div class="modal-body">
                                    <input type="hidden" name="add_availability" value="1">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

                                    <div class="mb-3">
                                        <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                        <select class="form-select" id="type" name="type" required>
                                            <option value="">-- Choisir --</option>
                                            <option value="recurrente">Disponibilité Récurrente</option>
                                            <option value="specifique">Disponibilité Spécifique</option>
                                            <option value="indisponible">Indisponibilité</option>
                                        </select>
                                        <div class="form-text">Choisissez le type, puis remplissez les champs correspondants ci-dessous.</div>
                                    </div>

                                    <hr>
                                    <h6 class="text-muted">Champs pour Disponibilité Récurrente</h6>

                                    <div id="recurrenteFields" class="mb-3 p-3 border rounded bg-light">
                                        <div class="mb-3">
                                            <label for="jour_semaine" class="form-label">Jour de la semaine <span class="text-info">(requis si récurrent)</span></label>
                                            <select class="form-select" id="jour_semaine" name="jour_semaine">
                                                <option value="">-- Choisir un jour --</option>
                                                <option value="1">Lundi</option>
                                                <option value="2">Mardi</option>
                                                <option value="3">Mercredi</option>
                                                <option value="4">Jeudi</option>
                                                <option value="5">Vendredi</option>
                                                <option value="6">Samedi</option>
                                                <option value="0">Dimanche</option>
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="heure_debut_rec" class="form-label">Heure de début <span class="text-info">(requis si récurrent)</span></label>
                                                <input type="time" class="form-control" id="heure_debut_rec" name="heure_debut">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="heure_fin_rec" class="form-label">Heure de fin <span class="text-info">(requis si récurrent)</span></label>
                                                <input type="time" class="form-control" id="heure_fin_rec" name="heure_fin">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="recurrence_fin" class="form-label">Fin de la récurrence (Optionnel)</label>
                                            <input type="date" class="form-control" id="recurrence_fin" name="recurrence_fin">
                                            <div class="form-text">Laissez vide si la disponibilité est récurrente indéfiniment.</div>
                                        </div>
                                    </div>

                                    <hr>
                                    <h6 class="text-muted">Champs pour Disponibilité Spécifique ou Indisponibilité</h6>

                                    <div id="specifiqueFields" class="mb-3 p-3 border rounded bg-light">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="date_debut" class="form-label">Date de début <span class="text-info">(requis si spécifique/indisponible)</span></label>
                                                <input type="date" class="form-control" id="date_debut" name="date_debut">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="date_fin" class="form-label">Date de fin (Optionnel)</label>
                                                <input type="date" class="form-control" id="date_fin" name="date_fin">
                                                <div class="form-text">Laissez vide si cela concerne une seule journée.</div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="heure_debut_spec" class="form-label">Heure de début (Optionnel)</label>
                                                <input type="time" class="form-control" id="heure_debut_spec" name="heure_debut_specifique">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="heure_fin_spec" class="form-label">Heure de fin (Optionnel)</label>
                                                <input type="time" class="form-control" id="heure_fin_spec" name="heure_fin_specifique">
                                            </div>
                                        </div>
                                    </div>

                                    <hr>
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes (Optionnel)</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                                        <div class="form-text">Ajoutez des détails si nécessaire (ex: "Congés annuels").</div>
                                    </div>

                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                </div>
                            </form>
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