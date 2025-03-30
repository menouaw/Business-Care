<?php
/**
 * tableau de bord - salariés
 *
 * page d'accueil du module salariés
 */

// Inclure les fonctions spécifiques au module salariés
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

// vérifier que l'utilisateur est connecté et a le rôle salarié
requireRole(ROLE_SALARIE);

// récupérer les informations de l'utilisateur
$userId = $_SESSION['user_id'];
$user = getEmployeeDetails($userId);
$entrepriseId = $_SESSION['user_entreprise'];

// récupérer les rendez-vous à venir
$rdvs = getEmployeeAppointments($userId, 'upcoming');

// récupérer les événements à venir
$evenements = getEmployeeEvents($userId, 'upcoming');

// récupérer les communautés de l'utilisateur
$communautes = getEmployeeCommunities($userId);

// récupérer l'historique d'activité récente
$activites = getEmployeeActivityHistory($userId, 1, 5);

// définir le titre de la page
$pageTitle = "Tableau de bord - Espace Salarié";

// inclure l'en-tête
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
                                <h2 class="card-title mb-0"><?= count($rdvs) ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="mes-rendez-vous.php" class="btn btn-sm btn-outline-primary">Tous les rendez-vous</a>
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
                            <div>
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
                            <a href="evenements.php" class="btn btn-sm btn-outline-warning">Voir tous</a>
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
                                <h2 class="card-title mb-0">--</h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="dons.php" class="btn btn-sm btn-outline-info">Faire un don</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- prochains rendez-vous -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Prochains rendez-vous</h5>
                        <a href="mes-rendez-vous.php" class="btn btn-sm btn-outline-primary">Voir tous</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rdvs)): ?>
                            <p class="text-center text-muted my-5">Aucun rendez-vous planifié</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($rdvs, 0, 5) as $rdv): 
                                    // récupérer les détails de la prestation si nécessaire
                                    $prestation = isset($rdv['prestation']) ? $rdv['prestation'] : (isset($rdv['prestation_id']) ? fetchOne('prestations', "id = {$rdv['prestation_id']}") : null);
                                ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?= $prestation['nom'] ?? $rdv['prestation_nom'] ?? 'Prestation inconnue' ?></h6>
                                                <p class="text-muted mb-0">
                                                    <i class="far fa-calendar-alt me-1"></i> <?= isset($rdv['date_formatee']) ? $rdv['date_formatee'] : formatDate($rdv['date_rdv'], 'd/m/Y') ?>
                                                    <i class="far fa-clock ms-2 me-1"></i> <?= isset($rdv['heure_formatee']) ? $rdv['heure_formatee'] : formatDate($rdv['date_rdv'], 'H:i') ?>
                                                </p>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-map-marker-alt me-1"></i> <?= $rdv['lieu'] ?? 'Non précisé' ?>
                                                </p>
                                            </div>
                                            <span class="badge bg-<?= isset($rdv['statut_classe']) ? $rdv['statut_classe'] : ($rdv['statut'] == 'confirme' ? 'success' : 'warning') ?> ms-2">
                                                <?= ucfirst($rdv['statut']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- événements à venir -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Événements à venir</h5>
                        <a href="evenements.php" class="btn btn-sm btn-outline-primary">Tous les événements</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($evenements)): ?>
                            <p class="text-center text-muted my-5">Aucun événement à venir</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($evenements, 0, 3) as $event): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?= $event['titre'] ?></h6>
                                                <p class="text-muted mb-0">
                                                    <i class="far fa-calendar-alt me-1"></i> <?= isset($event['date_formatee']) ? $event['date_formatee'] : formatDate($event['date_debut'], 'd/m/Y') ?>
                                                    <i class="far fa-clock ms-2 me-1"></i> <?= isset($event['heure_formatee']) ? $event['heure_formatee'] : formatDate($event['date_debut'], 'H:i') ?>
                                                </p>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-map-marker-alt me-1"></i> <?= $event['lieu'] ?? 'Non précisé' ?>
                                                </p>
                                            </div>
                                            <a href="inscription-evenement.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline-success">S'inscrire</a>
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
                            <div class="col-md-3">
                                <a href="recherche-prestations.php" class="btn btn-primary d-block py-3">
                                    <i class="fas fa-search me-2"></i>Rechercher prestations
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="signaler-situation.php" class="btn btn-warning d-block py-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Signaler situation
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="conseils-bien-etre.php" class="btn btn-success d-block py-3">
                                    <i class="fas fa-heart me-2"></i>Conseils bien-être
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="defis-sportifs.php" class="btn btn-info d-block py-3">
                                    <i class="fas fa-running me-2"></i>Défis sportifs
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
// inclure le pied de page
include_once __DIR__ . '/../../templates/footer.php';
?>
