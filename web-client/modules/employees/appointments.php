<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    processAppointmentCancellationRequest($_POST);
}

$pageData = displayEmployeeAppointmentsPage();
$appointments = $pageData['appointments'] ?? [];
$currentFilter = $pageData['currentFilter'] ?? 'upcoming';
$csrfToken = $pageData['csrf_token'] ?? '';

$pageTitle = "Mes Rendez-vous - Espace Salarié";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="employee-appointments-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2 mb-0">Mon Planning</h1>
                <p class="text-muted mb-0">Consultez l'historique et les détails de vos réservations (consultations, ateliers, etc.).</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <div class="row mb-4">
            <div class="col">
                <div class="btn-group" role="group" aria-label="Filtrer les rendez-vous">
                    <a href="?filter=upcoming" class="btn <?= $currentFilter === 'upcoming' ? 'btn-primary' : 'btn-outline-primary' ?>">À venir</a>
                    <a href="?filter=past" class="btn <?= $currentFilter === 'past' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Passés</a>
                    <a href="?filter=annule" class="btn <?= $currentFilter === 'annule' ? 'btn-danger' : 'btn-outline-danger' ?>">Annulés</a>
                    <a href="?filter=all" class="btn <?= $currentFilter === 'all' ? 'btn-info' : 'btn-outline-info' ?>">Tous</a>
                </div>
            </div>
            <div class="col text-end">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/services.php" class="btn btn-sm btn-outline-success"><i class="fas fa-book-open me-1"></i> Voir le catalogue</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <?php
                    switch ($currentFilter) {
                        case 'past':
                            echo 'Rendez-vous Passés';
                            break;
                        case 'annule':
                            echo 'Rendez-vous Annulés';
                            break;
                        case 'all':
                            echo 'Tous les Rendez-vous';
                            break;
                        case 'upcoming':
                        default:
                            echo 'Rendez-vous À Venir';
                            break;
                    }
                    ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($appointments)) : ?>
                    <p class="text-center text-muted my-5">
                        <?php
                        switch ($currentFilter) {
                            case 'past':
                                echo 'Aucun rendez-vous passé trouvé.';
                                break;
                            case 'annule':
                                echo 'Aucun rendez-vous annulé trouvé.';
                                break;
                            case 'all':
                                echo 'Aucun rendez-vous trouvé.';
                                break;
                            case 'upcoming':
                            default:
                                echo 'Aucun rendez-vous à venir planifié.';
                                break;
                        }
                        ?>
                        <br>
                    </p>
                <?php else : ?>
                    <div class="list-group ">
                        <?php foreach ($appointments as $rdv) : ?>
                            <div class="list-group-item border-0 px-0 py-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6 col-lg-7 mb-2 mb-md-0">
                                        <h6 class="mb-1"><?= htmlspecialchars($rdv['prestation_nom'] ?? 'Prestation inconnue') ?></h6>
                                        <p class="text-muted mb-1 small">
                                            <i class="far fa-calendar-alt me-1"></i> <?= htmlspecialchars($rdv['date_rdv_formatee'] ?? 'Date inconnue') ?>
                                            <i class="far fa-clock ms-2 me-1"></i> <?= htmlspecialchars($rdv['duree'] ?? '?') ?> min
                                        </p>
                                        <p class="text-muted mb-1 small">
                                            <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($rdv['lieu'] ?: ($rdv['type_rdv'] === 'visio' ? 'Visioconférence' : 'Téléphone')) ?>
                                        </p>
                                        <?php if (!empty($rdv['praticien_complet']) && $rdv['praticien_complet'] !== 'Non assigné') : ?>
                                            <p class="text-muted mb-0 small">
                                                <i class="fas fa-user-md me-1"></i> Avec: <?= htmlspecialchars($rdv['praticien_complet']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 col-lg-2 mb-2 mb-md-0 text-md-center">
                                        <?= $rdv['statut_badge'] ?? '' ?>
                                    </div>
                                    <div class="col-md-3 col-lg-3 text-md-end">
                                        <?php if (in_array($rdv['statut'], APPOINTMENT_CANCELABLE_STATUSES)) : ?>
                                            <form action="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php" method="POST" class="d-inline">
                                                <input type="hidden" name="reservation_id" value="<?= $rdv['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-times me-1"></i> Annuler
                                                </button>
                                            </form>
                                        <?php elseif ($rdv['statut'] === 'termine') : ?>
                                            <a href="<?= WEBCLIENT_URL ?>/evaluer-prestation.php?prestation_id=<?= $rdv['prestation_id'] ?>&rdv_id=<?= $rdv['id'] ?>" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-star me-1"></i> Évaluer
                                            </a>
                                        <?php endif; ?>


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
        </div>
    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>