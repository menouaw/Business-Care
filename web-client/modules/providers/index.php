<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers.php';

requireRole(ROLE_PRESTATAIRE);

$provider_id = $_SESSION['user_id'];


$appointmentDataUpcoming = getProviderAppointments($provider_id, 'upcoming', 1, 3);
$upcomingAppointments = $appointmentDataUpcoming['appointments'] ?? [];
$totalUpcomingAppointments = $appointmentDataUpcoming['total'] ?? 0;

$providerServices = getProviderServices($provider_id);
$serviceCount = count($providerServices);

$ratingsData = getProviderRatings($provider_id, 1, 3);
$latestEvaluations = $ratingsData['ratings'] ?? [];
$ratingsSummary = $ratingsData['summary'] ?? ['average' => 0, 'count' => 0];
$totalEvaluationsCount = $ratingsSummary['count'] ?? 0;

$invoices = getProviderInvoices($provider_id);
$invoiceCount = count($invoices);

$averageRating = $ratingsSummary['average'] ?? 0;


$pageTitle = "Tableau de bord - Espace Prestataire";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="dashboard-page py-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h2">Tableau de bord</h1>
                <p class="text-muted">Bienvenue dans votre espace prestataire, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Prestataire') ?></p>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>


        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-auto">
                            <div class="icon-box bg-primary bg-opacity-10 text-primary rounded p-3 me-3 flex-shrink-0">
                                <i class="fas fa-calendar-check fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">RDV à venir</h6>
                                <h2 class="card-title mb-0"><?= $totalUpcomingAppointments ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="appointments.php?status=upcoming" class="btn btn-sm btn-outline-primary w-100">Voir les RDV à venir</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-auto">
                            <div class="icon-box bg-success bg-opacity-10 text-success rounded p-3 me-3 flex-shrink-0">
                                <i class="fas fa-clipboard-list fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Prestations Actives</h6>
                                <h2 class="card-title mb-0"><?= $serviceCount ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="contracts.php" class="btn btn-sm btn-outline-success w-100">Gérer les Prestations</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-auto">
                            <div class="icon-box bg-warning bg-opacity-10 text-warning rounded p-3 me-3 flex-shrink-0">
                                <i class="fas fa-star fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Note Moyenne</h6>
                                <h2 class="card-title mb-0"><?= number_format($averageRating, 1) ?> / 5</h2>
                                <small class="text-muted">(<?= $totalEvaluationsCount ?> avis)</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="evaluations.php" class="btn btn-sm btn-outline-warning w-100">Voir les Évaluations</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-auto">
                            <div class="icon-box bg-info bg-opacity-10 text-info rounded p-3 me-3 flex-shrink-0">
                                <i class="fas fa-file-invoice-dollar fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Factures Générées</h6>
                                <h2 class="card-title mb-0"><?= $invoiceCount ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="invoices.php" class="btn btn-sm btn-outline-info w-100">Voir les Factures</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-calendar-day me-2 text-primary"></i>Prochains rendez-vous</h5>
                        <a href="appointments.php?status=upcoming" class="btn btn-sm btn-outline-primary">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingAppointments)): ?>
                            <p class="text-center text-muted my-5">Aucun rendez-vous planifié à venir.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcomingAppointments as $rdv): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-auto text-center" style="width: 65px;">
                                                <strong class="d-block text-primary fs-5"><?= formatDate($rdv['date_rdv'], 'd') ?></strong>
                                                <small class="text-muted text-uppercase"><?= formatDate($rdv['date_rdv'], 'M') ?></small>
                                            </div>
                                            <div class="col">
                                                <h6 class="mb-1"><?= htmlspecialchars($rdv['prestation_nom'] ?? 'Prestation inconnue') ?></h6>
                                                <p class="mb-1 small text-muted">
                                                    <i class="fas fa-user me-1"></i> <?= htmlspecialchars($rdv['client_nom'] ?? 'Client inconnu') ?>
                                                </p>
                                                <p class="mb-0 small text-muted">
                                                    <i class="far fa-clock me-1"></i> <?= htmlspecialchars($rdv['date_formatee_heure'] ?? '?') ?> (<?= htmlspecialchars($rdv['duree'] ?? '?') ?> min)
                                                    <i class="fas <?= $rdv['type_rdv_icon'] ?? 'fa-question-circle' ?> ms-2 me-1"></i> <?= ($rdv['type_rdv'] === 'presentiel' && !empty($rdv['lieu'])) ? htmlspecialchars($rdv['lieu']) : htmlspecialchars($rdv['type_rdv_text'] ?? '?') ?>
                                                </p>
                                            </div>
                                            <div class="col-auto text-end">
                                                <?= $rdv['statut_badge']
                                                ?>

                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-star me-2 text-warning"></i>Dernières évaluations</h5>
                        <a href="evaluations.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($latestEvaluations)): ?>
                            <p class="text-center text-muted my-5">Aucune évaluation reçue récemment.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($latestEvaluations as $evaluation): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($evaluation['prestation_nom'] ?? 'Prestation inconnue') ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($evaluation['client_nom'] ?? 'Client Anonyme') ?> - <?= htmlspecialchars($evaluation['date_evaluation_formatee'] ?? '?') ?></small>
                                            </div>
                                            <div class="text-nowrap ms-2">
                                                <?= renderStars($evaluation['note'] ?? 0) ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($evaluation['commentaire'])) : ?>
                                            <p class="mb-0 small fst-italic bg-light p-2 rounded border">"<?= nl2br(htmlspecialchars($evaluation['commentaire'])) ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>


        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Actions rapides</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6 col-md-4">
                                <a href="#" class="btn btn-outline-success d-block py-3 disabled">
                                    <i class="fas fa-calendar-plus me-2"></i>Gérer disponibilités
                                </a>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <a href="contracts.php" class="btn btn-outline-secondary d-block py-3">
                                    <i class="fas fa-briefcase me-2"></i>Prestations & Contrats
                                </a>
                            </div>
                            <div class="col-sm-12 col-md-4">
                                <?php
                                $subject = "Proposition Nouveau Service Prestataire";
                                $contactUrl = WEBCLIENT_URL . '/modules/companies/contact.php?subject=' . urlencode($subject);
                                ?>
                                <a href="<?= $contactUrl ?>" class="btn btn-outline-info d-block py-3">
                                    <i class="fas fa-headset me-2"></i>Contacter le support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>