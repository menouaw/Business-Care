<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/appointments.php';

requireRole(ROLE_SALARIE);

$viewData = setupAppointmentsPage();

if (!is_array($viewData)) {
    exit('Erreur lors de la récupération des données de la page.');
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($viewData['pageTitle']) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if ($viewData['action'] === 'view'): ?>
                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                        </a>
                    <?php elseif (!empty($viewData['bookingStep'])): ?>
                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Retour à Mes RDV
                        </a>
                    <?php elseif (empty($viewData['bookingStep']) && $viewData['action'] !== 'view'): ?>
                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                        </a>
                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php?bookingStep=show_services" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i> Prendre un rendez-vous
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <?php



            $showSpecificContent = false;
            if ($viewData['action'] === 'view' && !empty($viewData['appointmentDetails'])) {
                $showSpecificContent = true;

            ?>
                <div class="card shadow mb-4">
                    <div class="card-header">
                        Détails du Rendez-vous #<?= htmlspecialchars($viewData['appointmentDetails']['id']) ?>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">Date & Heure:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(formatDate($viewData['appointmentDetails']['date_rdv'], 'l d F Y à H:i')) ?></dd>

                            <dt class="col-sm-3">Prestation:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($viewData['appointmentDetails']['prestation_nom'] ?? 'N/D') ?></dd>

                            <dt class="col-sm-3">Description Prestation:</dt>
                            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($viewData['appointmentDetails']['prestation_description'] ?? 'N/D')) ?></dd>

                            <dt class="col-sm-3">Praticien:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($viewData['appointmentDetails']['praticien_nom'] ?? 'Non spécifié') ?></dd>
                            <?php if (!empty($viewData['appointmentDetails']['praticien_email'])): ?>
                                <dt class="col-sm-3">Email Praticien:</dt>
                                <dd class="col-sm-9"><a href="mailto:<?= htmlspecialchars($viewData['appointmentDetails']['praticien_email']) ?>"><?= htmlspecialchars($viewData['appointmentDetails']['praticien_email']) ?></a></dd>
                            <?php endif; ?>
                            <?php if (!empty($viewData['appointmentDetails']['praticien_tel'])): ?>
                                <dt class="col-sm-3">Tél. Praticien:</dt>
                                <dd class="col-sm-9"><?= htmlspecialchars($viewData['appointmentDetails']['praticien_tel']) ?></dd>
                            <?php endif; ?>

                            <dt class="col-sm-3">Type:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(ucfirst($viewData['appointmentDetails']['type_rdv'] ?? 'N/D')) ?></dd>

                            <dt class="col-sm-3">Lieu:</dt>
                            <dd class="col-sm-9">
                                <?= htmlspecialchars($viewData['appointmentDetails']['lieu'] ?? ($viewData['appointmentDetails']['site_adresse'] ?? 'Non défini')) ?>
                                <?php if (!empty($viewData['appointmentDetails']['site_nom']) && $viewData['appointmentDetails']['lieu'] !== $viewData['appointmentDetails']['site_adresse']): ?>
                                    <small class="text-muted">(Site: <?= htmlspecialchars($viewData['appointmentDetails']['site_nom']) ?>)</small>
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-3">Durée:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($viewData['appointmentDetails']['duree'] ?? '?') ?> minutes</dd>

                            <dt class="col-sm-3">Statut:</dt>
                            <dd class="col-sm-9">
                                <?php
                                $displayStatusDetail = $viewData['appointmentDetails']['statut'];
                                $displayBadgeClassDetail = getStatusBadgeClass($viewData['appointmentDetails']['statut']);
                                $isPastDetail = strtotime($viewData['appointmentDetails']['date_rdv']) <= time();
                                if (in_array($viewData['appointmentDetails']['statut'], ['planifie', 'confirme']) && $isPastDetail) {
                                    $displayStatusDetail = 'terminé';
                                    $displayBadgeClassDetail = 'info';
                                } elseif ($viewData['appointmentDetails']['statut'] === 'annule' && $isPastDetail) {
                                    $displayBadgeClassDetail = 'secondary';
                                }
                                ?>
                                <span class="badge bg-<?= $displayBadgeClassDetail ?>">
                                    <?= htmlspecialchars(ucfirst($displayStatusDetail)) ?>
                                </span>
                            </dd>

                            <dt class="col-sm-3">Notes:</dt>
                            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($viewData['appointmentDetails']['notes'] ?? 'Aucune')) ?></dd>

                            <dt class="col-sm-3">Créé le:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(formatDate($viewData['appointmentDetails']['created_at'], 'd/m/Y H:i')) ?></dd>
                        </dl>
                    </div>
                </div>
            <?php
            } elseif ($viewData['bookingStep'] === 'show_slots' && !empty($viewData['selectedService'])) {
                $showSpecificContent = true;

            ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Étape 2 : Choisir un créneau pour <?= htmlspecialchars($viewData['selectedService']['nom']) ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <p>Sélectionnez un créneau ci-dessous :</p>
                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php?bookingStep=show_services" class="btn btn-sm btn-outline-secondary mb-3">
                            <i class="fas fa-arrow-left me-1"></i> Retour au choix des prestations
                        </a>
                        <?php if (empty($viewData['availableSlots'])): ?>
                            <div class="alert alert-info mt-2">Aucun créneau disponible pour cette prestation dans les prochaines semaines. Veuillez réessayer plus tard ou choisir une autre prestation.</div>
                            <a href="<?= WEBCLIENT_URL ?>/modules/employees/services.php" class="btn btn-primary mt-2">
                                <i class="fas fa-th-list me-1"></i> Retour au Catalogue des Services
                            </a>
                        <?php else: ?>
                            <form method="POST" action="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php">
                                <input type="hidden" name="service_id" value="<?= htmlspecialchars($viewData['service_id'] ?? '') ?>">
                                <input type="hidden" name="csrf_token" value="<?= generateToken(); ?>">
                                <div class="list-group">
                                    <?php foreach ($viewData['availableSlots'] as $slot): ?>
                                        <label class="list-group-item list-group-item-action">
                                            <input class="form-check-input me-2" type="radio" name="slot_id" value="<?= $slot['id'] ?>" required>
                                            <?= htmlspecialchars(formatDate($slot['start_time'], 'l d F Y à H:i')) ?>
                                            (Durée: <?= htmlspecialchars($viewData['selectedService']['duree'] ?? '?') ?> min)
                                            <?php if (!empty($slot['praticien_nom'])): ?>
                                                <span class="text-muted ms-2">- Proposé par <?= htmlspecialchars($slot['praticien_nom']) ?></span>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <button type="submit" class="btn btn-success mt-3">
                                    <i class="fas fa-check me-1"></i> Réserver le créneau sélectionné
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
            } elseif ($viewData['bookingStep'] === 'show_services') {
                $showSpecificContent = true;

            ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Étape 1 : Choisir une prestation
                        </h6>
                    </div>
                    <div class="card-body">
                        <p>Cliquez sur une prestation pour voir les créneaux disponibles :</p>
                        <?php if (empty($viewData['availableServices'])): ?>
                            <div class="alert alert-warning">Aucune prestation n'est actuellement disponible à la réservation.</div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($viewData['availableServices'] as $service): ?>
                                    <div class="col-md-4 col-lg-3 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body d-flex flex-column">
                                                <h5 class="card-title fs-6"><?= htmlspecialchars($service['nom']) ?></h5>
                                                <p class="card-text small flex-grow-1"><?= htmlspecialchars(substr($service['description'] ?? '', 0, 80)) . (strlen($service['description'] ?? '') > 80 ? '...' : '') ?></p>
                                                <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php?action=select_slot&service_id=<?= $service['id'] ?>" class="btn btn-sm btn-outline-primary mt-auto">
                                                    Choisir <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php
                            if (
                                !empty($viewData['servicePagination']) &&
                                isset($viewData['servicePagination']['totalPages']) &&
                                $viewData['servicePagination']['totalPages'] > 1
                            ) {
                                $paginationUrlPattern = WEBCLIENT_URL . '/modules/employees/appointments.php?bookingStep=show_services&page={page}';
                                echo renderPagination($viewData['servicePagination'], $paginationUrlPattern);
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
            }



            if (!$showSpecificContent) {
                $filter = $viewData['filter'] ?? 'upcoming';
            ?>
                <div class="card shadow mb-4" id="appointments-section">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-secondary">Mes Rendez-vous</h6>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Filtres Rendez-vous">
                            <a href="?filter=upcoming#appointments-section" class="btn btn-outline-primary <?= $filter === 'upcoming' ? 'active' : '' ?>">Pour vous</a>
                            <a href="?filter=cancelled#appointments-section" class="btn btn-outline-warning <?= $filter === 'cancelled' ? 'active' : '' ?>">Annulés</a>
                            <a href="?filter=past#appointments-section" class="btn btn-outline-secondary <?= $filter === 'past' ? 'active' : '' ?>">Historique</a>
                            <a href="?filter=all#appointments-section" class="btn btn-outline-dark <?= $filter === 'all' ? 'active' : '' ?>">Tous</a>
                        </div>
                    </div>
                    <div class="card-body">

                        <?php
                        
                        $csrfToken = generateToken();
                        ?>

                        <?php if ($filter === 'all' || $filter === 'upcoming'): ?>
                            <h5 class="text-primary mb-3 mt-2">Pour vous</h5>
                            <?php if (empty($viewData['upcomingAppointments'])):
                            ?>
                                <p class="text-muted">Aucun rendez-vous à venir.</p>
                            <?php else:
                            ?>
                                <div class="table-responsive mb-4">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Date & Heure</th>
                                                <th>Prestation</th>
                                                <th>Praticien</th>
                                                <th>Type</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($viewData['upcomingAppointments'] as $rdv):
                                            ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(formatDate($rdv['date_rdv'], 'd/m/Y H:i')) ?></td>
                                                    <td><?= htmlspecialchars($rdv['prestation_nom'] ?? 'N/D') ?></td>
                                                    <td><?= htmlspecialchars($rdv['praticien_nom'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($rdv['type_rdv'] ?? 'N/D')) ?></td>
                                                    <td><span class="badge bg-<?= getStatusBadgeClass($rdv['statut']) ?>"><?= htmlspecialchars(ucfirst($rdv['statut'])) ?></span></td>
                                                    <td>
                                                        <a href="?action=view&id=<?= $rdv['id'] ?>#appointments-section" class="btn btn-sm btn-outline-info me-1" title="Voir Détails"> <i class="fas fa-eye"></i> </a>
                                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?');">
                                                            <input type="hidden" name="action" value="cancel">
                                                            <input type="hidden" name="id" value="<?= $rdv['id'] ?>">
                                                            <input type="hidden" name="filter" value="<?= $filter ?>">
                                                            
                                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Annuler ce rendez-vous">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            <?php if ($filter === 'all') echo '<hr class="my-4">'; ?>
                        <?php endif; ?>

                        <?php if ($filter === 'all' || $filter === 'cancelled'): ?>
                            <h5 class="text-warning mb-3 mt-2">Rendez-vous Annulés</h5>
                            <?php if (empty($viewData['cancelledAppointments'])): ?>
                                <p class="text-muted">Aucun rendez-vous annulé.</p>
                            <?php else: ?>
                                <div class="table-responsive mb-4">
                                    <table class="table table-striped table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date & Heure (Prévue)</th>
                                                <th>Prestation</th>
                                                <th>Praticien</th>
                                                <th>Type</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($viewData['cancelledAppointments'] as $rdv): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(formatDate($rdv['date_rdv'], 'd/m/Y H:i')) ?></td>
                                                    <td><?= htmlspecialchars($rdv['prestation_nom'] ?? 'N/D') ?></td>
                                                    <td><?= htmlspecialchars($rdv['praticien_nom'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($rdv['type_rdv'] ?? 'N/D')) ?></td>
                                                    <td><span class="badge bg-secondary">Annulé</span></td>
                                                    <td>
                                                        <a href="?action=view&id=<?= $rdv['id'] ?>#appointments-section" class="btn btn-sm btn-outline-info me-1" title="Voir Détails"> <i class="fas fa-eye"></i> </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            <?php if ($filter === 'all') echo '<hr class="my-4">'; ?>
                        <?php endif; ?>

                        <?php if ($filter === 'all' || $filter === 'past'): ?>
                            <h5 class="text-secondary mb-3 mt-2">Historique (Terminés)</h5>
                            <?php if (empty($viewData['pastCompletedAppointments'])): ?>
                                <p class="text-muted">Aucun historique de rendez-vous terminés.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date & Heure</th>
                                                <th>Prestation</th>
                                                <th>Praticien</th>
                                                <th>Type</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($viewData['pastCompletedAppointments'] as $rdv):
                                                $displayStatusHist = (in_array($rdv['statut'], ['planifie', 'confirme']) && strtotime($rdv['date_rdv']) <= time()) ? 'terminé' : $rdv['statut'];
                                                $displayBadgeClassHist = getStatusBadgeClass($displayStatusHist);
                                            ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(formatDate($rdv['date_rdv'], 'd/m/Y H:i')) ?></td>
                                                    <td><?= htmlspecialchars($rdv['prestation_nom'] ?? 'N/D') ?></td>
                                                    <td><?= htmlspecialchars($rdv['praticien_nom'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($rdv['type_rdv'] ?? 'N/D')) ?></td>
                                                    <td><span class="badge bg-<?= $displayBadgeClassHist ?>"><?= htmlspecialchars(ucfirst($displayStatusHist)) ?></span></td>
                                                    <td>
                                                        <a href="?action=view&id=<?= $rdv['id'] ?>#appointments-section" class="btn btn-sm btn-outline-info me-1" title="Voir Détails"> <i class="fas fa-eye"></i> </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
            <?php
            }
            ?>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>