<?php
require_once __DIR__ . '/../../includes/init.php';

requireEmployeeLogin();

$employee_id = $_SESSION['user_id'];
$pageTitle = generatePageTitle('Tableau de bord Salarié');

require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

$userId = $_SESSION['user_id'];
$user = getEmployeeDetails($userId);
$entrepriseId = $_SESSION['user_entreprise'];

$rdvs_data = getEmployeeAppointments($userId, 'upcoming');

$evenements = getEmployeeEvents($userId, 'upcoming');

$communautes = getEmployeeCommunities($userId);

$donations = getDonationHistory($userId);

$activites = getEmployeeActivityHistory($userId, 1, 5);

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="dashboard-page py-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h2">Tableau de bord</h1>
                <p class="text-muted">Bienvenue dans votre espace salarié, <?= $_SESSION['user_name'] ?></p>
            </div>
        </div>

        <!-- cartes d'information -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                                <i class="fas fa-calendar-alt fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Rendez-vous</h6>
                                <h2 class="card-title mb-0"><?= count($rdvs_data['appointments']) ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="appointments.php" class="btn btn-sm btn-outline-primary">Tous les rendez-vous</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-success bg-opacity-10 text-success rounded p-3 me-3">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <h6 class="card-subtitle text-muted mb-1">Communautés</h6>
                                <h2 class="card-title mb-0"><?= count($communautes) ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="communautes.php" class="btn btn-sm btn-outline-success">Explorer</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                                <i class="fas fa-star fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Événements</h6>
                                <h2 class="card-title mb-0"><?= count($evenements) ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="events.php" class="btn btn-sm btn-outline-warning">Voir tous</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-info bg-opacity-10 text-info rounded p-3 me-3">
                                <i class="fas fa-hand-holding-heart fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Dons</h6>
                                <h2 class="card-title mb-0"><?= count($donations) ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="donations.php" class="btn btn-sm btn-outline-info">Faire un don</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Prochains rendez-vous</h5>
                        <a href="appointments.php" class="btn btn-sm btn-outline-primary">Voir tous</a>
                    </div>
                    <div class="card-body">
                        <?php
                        $upcomingAppointments = isset($rdvs_data['appointments']) && is_array($rdvs_data['appointments']) ? $rdvs_data['appointments'] : [];

                        if (empty($upcomingAppointments)):
                        ?>
                            <p class="text-center text-muted my-5">Aucun rendez-vous planifié</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($upcomingAppointments, 0, 5) as $rdv): ?>
                                    <?php // Les commentaires redondants sur fetchOne ont été supprimés 
                                    ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($rdv['prestation_nom'] ?? 'Prestation inconnue') ?></h6>
                                                <p class="text-muted mb-0">
                                                    <i class="far fa-calendar-alt me-1"></i> <?= isset($rdv['date_formatee']) ? $rdv['date_formatee'] : formatDate($rdv['date_rdv'], 'd/m/Y') ?>
                                                    <i class="far fa-clock ms-2 me-1"></i> <?= isset($rdv['heure_formatee']) ? $rdv['heure_formatee'] : formatDate($rdv['date_rdv'], 'H:i') ?>
                                                </p>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($rdv['lieu'] ?? 'Non précisé') ?>
                                                </p>
                                            </div>
                                            <span class="badge bg-<?= isset($rdv['statut_classe']) ? $rdv['statut_classe'] : ($rdv['statut'] == 'confirme' ? 'success' : 'warning') ?> ms-2">
                                                <?= htmlspecialchars(ucfirst($rdv['statut'])) ?>
                                            </span>
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
                        <h5 class="card-title mb-0">Événements à venir</h5>
                        <a href="events.php" class="btn btn-sm btn-outline-primary">Tous les événements</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($evenements)): ?>
                            <p class="text-center text-muted my-5">Aucun événement à venir</p>
                        <?php else:
                            // Utiliser array_slice pour ne prendre que les 3 premiers événements
                            $upcomingEventsPreview = array_slice($evenements, 0, 3);
                        ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcomingEventsPreview as $event): // Boucler sur les 3 premiers 
                                ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($event['titre']) // Sécuriser avec htmlspecialchars 
                                                                    ?></h6>
                                                <p class="text-muted mb-0 small">
                                                    <i class="far fa-calendar-alt me-1"></i> <?= $event['date_debut_formatted'] ?? formatDate($event['date_debut'], 'd/m/Y') // Utiliser le champ formaté si dispo, sinon formater ici 
                                                                                                ?>
                                                    <i class="far fa-clock ms-2 me-1"></i> <?= formatDate($event['date_debut'], 'H:i') ?>
                                                </p>
                                                <p class="text-muted mb-0 small">
                                                    <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($event['lieu'] ?? 'Non précisé') ?>
                                                </p>
                                            </div>
                                            <!-- Lien vers la page d'inscription ou d'événements -->
                                            <a href="events.php" class="btn btn-sm btn-outline-success">Voir</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- actions rapides -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Actions rapides</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg col-md-4 col-6">
                                <a href="catalog.php" class="btn btn-primary d-block py-3 text-truncate">
                                    <i class="fas fa-book-open me-2"></i>Catalogue Services
                                </a>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="signalement.php" class="btn btn-warning d-block py-3 text-truncate">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Signaler situation
                                </a>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="advice.php" class="btn btn-success d-block py-3 text-truncate">
                                    <i class="fas fa-heart me-2"></i>Conseils Bien-être
                                </a>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="communities.php" class="btn btn-info d-block py-3 text-truncate">
                                    <i class="fas fa-users me-2"></i>Communautés
                                </a>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="donations.php" class="btn btn-secondary d-block py-3 text-truncate">
                                    <i class="fas fa-hand-holding-heart me-2"></i>Faire un Don
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