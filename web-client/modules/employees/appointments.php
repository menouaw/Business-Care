<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/appointments.php';

requireRole(ROLE_SALARIE);

$viewData = setupAppointmentsPage();

extract($viewData);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if ($action === 'view'): ?>
                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                        </a>
                    <?php else: ?>
                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <?php if ($action === 'view' && $appointmentDetails): ?>
                <div class="card shadow mb-4">
                    <div class="card-header">
                        Détails du Rendez-vous #<?= htmlspecialchars($appointmentDetails['id']) ?>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">Date & Heure:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(formatDate($appointmentDetails['date_rdv'], 'l d F Y à H:i')) ?></dd>

                            <dt class="col-sm-3">Prestation:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($appointmentDetails['prestation_nom'] ?? 'N/D') ?></dd>

                            <dt class="col-sm-3">Description Prestation:</dt>
                            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($appointmentDetails['prestation_description'] ?? 'N/D')) ?></dd>

                            <dt class="col-sm-3">Praticien:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($appointmentDetails['praticien_nom'] ?? 'Non spécifié') ?></dd>
                            <?php if (!empty($appointmentDetails['praticien_email'])): ?>
                                <dt class="col-sm-3">Email Praticien:</dt>
                                <dd class="col-sm-9"><a href="mailto:<?= htmlspecialchars($appointmentDetails['praticien_email']) ?>"><?= htmlspecialchars($appointmentDetails['praticien_email']) ?></a></dd>
                            <?php endif; ?>
                            <?php if (!empty($appointmentDetails['praticien_tel'])): ?>
                                <dt class="col-sm-3">Tél. Praticien:</dt>
                                <dd class="col-sm-9"><?= htmlspecialchars($appointmentDetails['praticien_tel']) ?></dd>
                            <?php endif; ?>

                            <dt class="col-sm-3">Type:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(ucfirst($appointmentDetails['type_rdv'] ?? 'N/D')) ?></dd>

                            <dt class="col-sm-3">Lieu:</dt>
                            <dd class="col-sm-9">
                                <?= htmlspecialchars($appointmentDetails['lieu'] ?? ($appointmentDetails['site_adresse'] ?? 'Non défini')) ?>
                                <?php if (!empty($appointmentDetails['site_nom']) && $appointmentDetails['lieu'] !== $appointmentDetails['site_adresse']): ?>
                                    <small class="text-muted">(Site: <?= htmlspecialchars($appointmentDetails['site_nom']) ?>)</small>
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-3">Durée:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($appointmentDetails['duree'] ?? '?') ?> minutes</dd>

                            <dt class="col-sm-3">Statut:</dt>
                            <dd class="col-sm-9">
                                <?php

                                $displayStatusDetail = $appointmentDetails['statut'];
                                $displayBadgeClassDetail = getStatusBadgeClass($appointmentDetails['statut']);
                                $isPastDetail = strtotime($appointmentDetails['date_rdv']) <= time();
                                if (in_array($appointmentDetails['statut'], ['planifie', 'confirme']) && $isPastDetail) {
                                    $displayStatusDetail = 'terminé';
                                    $displayBadgeClassDetail = 'info';
                                } elseif ($appointmentDetails['statut'] === 'annule' && $isPastDetail) {
                                    $displayBadgeClassDetail = 'secondary';
                                }
                                ?>
                                <span class="badge bg-<?= $displayBadgeClassDetail ?>">
                                    <?= htmlspecialchars(ucfirst($displayStatusDetail)) ?>
                                </span>
                            </dd>

                            <dt class="col-sm-3">Notes:</dt>
                            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($appointmentDetails['notes'] ?? 'Aucune')) ?></dd>

                            <dt class="col-sm-3">Créé le:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(formatDate($appointmentDetails['created_at'], 'd/m/Y H:i')) ?></dd>
                        </dl>
                    </div>
                </div>

            <?php else: ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?php
                            if ($bookingStep === 'show_slots' && $selectedService) {
                                echo "Étape 2 : Choisir un créneau pour " . htmlspecialchars($selectedService['nom']);
                            } else {
                                echo "Étape 1 : Choisir une prestation";
                            }
                            ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php

                        if ($bookingStep === 'show_services') {
                        ?>
                            <p>Cliquez sur une prestation pour voir les créneaux disponibles :</p>
                            <?php if (empty($availableServices)): ?>
                                <div class="alert alert-warning">Aucune prestation n'est actuellement disponible à la réservation.</div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($availableServices as $service): ?>
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
                                if ($servicePagination && $servicePagination['totalPages'] > 1) {
                                    $paginationUrlPattern = WEBCLIENT_URL . '/modules/employees/appointments.php?page={page}';
                                    echo renderPagination($servicePagination, $paginationUrlPattern);
                                }
                                ?>
                            <?php endif; ?>
                        <?php
                        } elseif ($bookingStep === 'show_slots' && $selectedService) {
                        ?>
                            <p>Sélectionnez un créneau ci-dessous :</p>
                            <a href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php" class="btn btn-sm btn-outline-secondary mb-3">
                                <i class="fas fa-arrow-left me-1"></i> Retour au choix des prestations
                            </a>
                            <?php if (empty($availableSlots)): ?>
                                <div class="alert alert-info mt-2">Aucun créneau disponible pour cette prestation dans les prochaines semaines. Veuillez réessayer plus tard ou choisir une autre prestation.</div>
                            <?php else: ?>
                                <form method="POST" action="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php">
                                    <input type="hidden" name="service_id" value="<?= $service_id ?>">
                                    <input type="hidden" name="csrf_token" value="<?= generateToken(); ?>">
                                    <div class="list-group">
                                        <?php foreach ($availableSlots as $slot): ?>
                                            <label class="list-group-item list-group-item-action">
                                                <input class="form-check-input me-2" type="radio" name="slot_id" value="<?= $slot['id'] ?>" required>
                                                <?= htmlspecialchars(formatDate($slot['start_time'], 'l d F Y à H:i')) ?>
                                                (Durée: <?= htmlspecialchars($selectedService['duree'] ?? '?') ?> min)
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
                        <?php
                        }
                        ?>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-secondary">Mes Rendez-vous</h6>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Filtres Rendez-vous">

                            <a href="?filter=upcoming#appointments-section" class="btn btn-outline-primary <?= $filter === 'upcoming' ? 'active' : '' ?>">À venir</a>
                            <a href="?filter=cancelled#appointments-section" class="btn btn-outline-warning <?= $filter === 'cancelled' ? 'active' : '' ?>">Annulés</a>
                            <a href="?filter=past#appointments-section" class="btn btn-outline-secondary <?= $filter === 'past' ? 'active' : '' ?>">Historique</a>
                            <a href="?filter=all#appointments-section" class="btn btn-outline-dark <?= $filter === 'all' ? 'active' : '' ?>">Tous</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php 
                        ?>

                        <?php 
                        ?>
                        <?php if ($filter === 'all' || $filter === 'upcoming'): ?>
                            <h5 class="text-primary mb-3 mt-2">Rendez-vous à venir</h5>
                            <?php if (empty($upcomingAppointments)): ?>
                                <p class="text-muted">Aucun rendez-vous à venir.</p>
                            <?php else: ?>
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
                                            <?php foreach ($upcomingAppointments as $rdv): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(formatDate($rdv['date_rdv'], 'd/m/Y H:i')) ?></td>
                                                    <td><?= htmlspecialchars($rdv['prestation_nom'] ?? 'N/D') ?></td>
                                                    <td><?= htmlspecialchars($rdv['praticien_nom'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($rdv['type_rdv'] ?? 'N/D')) ?></td>
                                                    <td><span class="badge bg-<?= getStatusBadgeClass($rdv['statut']) ?>"><?= htmlspecialchars(ucfirst($rdv['statut'])) ?></span></td>
                                                    <td>
                                                        <a href="?action=view&id=<?= $rdv['id'] ?>#appointments-section" class="btn btn-sm btn-outline-info me-1" title="Voir Détails"> <i class="fas fa-eye"></i> </a>
                                                        <a href="?action=cancel&id=<?= $rdv['id'] ?>&csrf=<?= generateToken() ?>&filter=<?= $filter ?>#appointments-section"
                                                            class="btn btn-sm btn-outline-danger" title="Annuler ce rendez-vous"
                                                            onclick="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?');"> <i class="fas fa-times"></i> </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            <?php if ($filter === 'all') echo '<hr class="my-4">'; ?>
                        <?php endif; ?>

                        <?php 
                        ?>
                        <?php if ($filter === 'all' || $filter === 'cancelled'): ?>
                            <h5 class="text-warning mb-3 mt-2">Rendez-vous Annulés</h5>
                            <?php if (empty($cancelledAppointments)): ?>
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
                                            <?php foreach ($cancelledAppointments as $rdv): ?>
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

                        <?php 
                        ?>
                        <?php if ($filter === 'all' || $filter === 'past'): ?>
                            <h5 class="text-secondary mb-3 mt-2">Historique (Terminés)</h5>
                            <?php if (empty($pastCompletedAppointments)): ?>
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
                                            <?php foreach ($pastCompletedAppointments as $rdv):
                                                
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
                                                        <?php 
                                                        ?>
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

            <?php endif; ?>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>