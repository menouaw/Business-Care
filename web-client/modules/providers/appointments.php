<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers.php';

requireRole(ROLE_PRESTATAIRE);
$provider_id = $_SESSION['user_id'];

$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'upcoming';
$page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 1;
$page = $page ?: 1;

$appointmentData = getProviderAppointments($provider_id, $status_filter, $page);

$appointments = $appointmentData['appointments'] ?? [];
$paginationHtml = $appointmentData['pagination_html'] ?? '';
$current_filter = $appointmentData['current_filter'] ?? 'upcoming';

$pageTitle = "Mes Rendez-vous - Espace Prestataire";

include_once __DIR__ . '/../../templates/header.php';

?>

<main class="provider-appointments-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2"><i class="fas fa-calendar-alt me-2"></i><?= $pageTitle ?></h1>
                <p class="text-muted">Consultez vos rendez-vous planifiés, passés et annulés.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/providers/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour 
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <div class="mb-4">
            <div class="btn-group" role="group" aria-label="Filter appointments by status">
                <a href="?status=upcoming" class="btn <?= $current_filter === 'upcoming' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="fas fa-clock me-1"></i> À venir
                </a>
                <a href="?status=past" class="btn <?= $current_filter === 'past' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="fas fa-history me-1"></i> Passés
                </a>
                <a href="?status=cancelled" class="btn <?= $current_filter === 'cancelled' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="fas fa-ban me-1"></i> Annulés
                </a>
                <a href="?status=all" class="btn <?= $current_filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <i class="fas fa-list me-1"></i> Tous
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <?php
                    echo match ($current_filter) {
                        'upcoming' => 'Rendez-vous à venir',
                        'past' => 'Rendez-vous passés',
                        'cancelled' => 'Rendez-vous annulés',
                        'all' => 'Tous les rendez-vous',
                        default => 'Rendez-vous'
                    };
                    ?>
                </h5>
                <span class="badge bg-secondary"><?= $appointmentData['total'] ?? 0 ?> RDV</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($appointments)) : ?>
                    <div class="alert alert-info mb-0 border-0 rounded-0" role="alert">
                        Aucun rendez-vous trouvé pour ce filtre.
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Heure</th>
                                    <th>Client</th>
                                    <th>Prestation</th>
                                    <th>Type / Lieu</th>
                                    <th>Durée</th>
                                    <th class="text-center">Statut</th>
                                    <!-- <th class="text-center">Actions</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $rdv) : ?>
                                    <tr>
                                        <td class="text-nowrap"><?= htmlspecialchars($rdv['date_formatee_jour'] ?? 'N/A') ?></td>
                                        <td class="text-nowrap"><?= htmlspecialchars($rdv['date_formatee_heure'] ?? 'N/A') ?></td>
                                        <td>
                                            <div title="<?= htmlspecialchars($rdv['client_email'] ?? '') ?> | <?= htmlspecialchars($rdv['client_telephone'] ?? '') ?>">
                                                <i class="fas fa-user me-1 text-muted"></i><?= htmlspecialchars($rdv['client_nom'] ?? 'N/A') ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($rdv['prestation_nom'] ?? 'N/A') ?></td>
                                        <td class="text-nowrap" title="<?= htmlspecialchars($rdv['type_rdv_text'] ?? '?') ?>">
                                            <i class="fas <?= $rdv['type_rdv_icon'] ?? 'fa-question-circle' ?> me-1"></i>
                                            <?= ($rdv['type_rdv'] === 'presentiel' && !empty($rdv['lieu'])) ? htmlspecialchars($rdv['lieu']) : htmlspecialchars($rdv['type_rdv_text'] ?? '?') ?>
                                        </td>
                                        <td class="text-nowrap"><?= htmlspecialchars($rdv['duree'] ?? '?') ?> min</td>
                                        <td class="text-center"><?= $rdv['statut_badge'] ?></td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($paginationHtml) : ?>
                <div class="card-footer bg-light border-top-0">
                    <nav aria-label="Appointments navigation">
                        <?= $paginationHtml ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>