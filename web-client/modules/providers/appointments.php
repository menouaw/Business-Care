<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers/appointments.php';

requireRole(ROLE_PRESTATAIRE);

$provider_id = $_SESSION['user_id'] ?? 0;


$viewData = setupProviderAppointmentsPageData($provider_id);


extract($viewData);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <a href="<?= WEBCLIENT_URL ?>/modules/providers/dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                </a>
            </div>

            <?php echo displayFlashMessages(); ?>

            
            <ul class="nav nav-pills mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= ($filter_status === 'upcoming') ? 'active' : '' ?>" href="?filter=upcoming">À venir</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($filter_status === 'past') ? 'active' : '' ?>" href="?filter=past">Passés</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($filter_status === 'canceled') ? 'active' : '' ?>" href="?filter=canceled">Annulés</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($filter_status === 'all') ? 'active' : '' ?>" href="?filter=all">Tous</a>
                </li>
            </ul>

            <div class="card mb-4">
                <div class="card-header">
                    <?php
                    $filter_label = match ($filter_status) {
                        'upcoming' => 'Rendez-vous à venir',
                        'past' => 'Historique des rendez-vous passés',
                        'canceled' => 'Rendez-vous annulés',
                        'all' => 'Tous les rendez-vous',
                        default => 'Rendez-vous'
                    };
                    ?>
                    <?= htmlspecialchars($filter_label) ?> (<?= $total_appointments ?> au total pour ce filtre)
                </div>
                <div class="card-body">
                    <?php if (empty($appointments)): ?>
                        <p class="text-center text-muted">Aucun rendez-vous trouvé pour ce filtre.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Date & Heure</th>
                                        <th>Prestation</th>
                                        <th>Salarié</th>
                                        <th>Contact Salarié</th>
                                        <th>Type</th>
                                        <th>Lieu</th>
                                        <th>Statut</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appt): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date(DEFAULT_DATE_FORMAT . ' H:i', strtotime($appt['date_rdv']))) ?></td>
                                            <td><?= htmlspecialchars($appt['prestation_nom']) ?></td>
                                            <td><?= htmlspecialchars($appt['salarie_prenom'] . ' ' . $appt['salarie_nom']) ?></td>
                                            <td>
                                                <?php if ($appt['salarie_email']): ?>
                                                    <a href="mailto:<?= htmlspecialchars($appt['salarie_email']) ?>" title="Envoyer un email"><i class="fas fa-envelope me-1"></i></a>
                                                <?php endif; ?>
                                                <?php if ($appt['salarie_telephone']): ?>
                                                    <a href="tel:<?= htmlspecialchars($appt['salarie_telephone']) ?>" title="Appeler"><i class="fas fa-phone"></i></a>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars(ucfirst($appt['type_rdv'] ?? 'N/A')) ?></td>
                                            <td><?= htmlspecialchars($appt['lieu'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="badge bg-<?= getAppointmentStatusBadgeClass($appt['statut']) ?>">
                                                    <?= htmlspecialchars(formatAppointmentStatus($appt['statut'])) ?>
                                                </span>
                                            </td>
                                            <td title="<?= htmlspecialchars($appt['notes_rdv'] ?? '') ?>">
                                                <?= htmlspecialchars(substr($appt['notes_rdv'] ?? '', 0, 30)) . (strlen($appt['notes_rdv'] ?? '') > 30 ? '...' : '') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation rendez-vous prestataires" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?filter=<?= $filter_status ?>&page=<?= $current_page - 1 ?>">Précédent</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?filter=<?= $filter_status ?>&page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?filter=<?= $filter_status ?>&page=<?= $current_page + 1 ?>">Suivant</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        

                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

