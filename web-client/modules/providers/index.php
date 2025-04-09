<?php

/**
 * tableau de bord - prestataires
 *
 * page d'accueil du module prestataires
 */

// Inclure les fonctions spécifiques au module prestataires
require_once __DIR__ . '/../../includes/page_functions/modules/providers.php';

// vérifier que l'utilisateur est connecté et a le rôle prestataire
requireRole(ROLE_PRESTATAIRE);

// récupérer les informations de l'utilisateur
$userId = $_SESSION['user_id'];
$user = getProviderDetails($userId);

// récupérer les prestations du prestataire
$prestations = getProviderServices($userId);

// récupérer les rendez-vous à venir (plannifiés et confirmés)
$currentDate = date('Y-m-d H:i:s');
$calendarData = getProviderCalendar($userId, $currentDate, date('Y-m-d H:i:s', strtotime('+1 month')));

// Convertir les données du calendrier en liste de rendez-vous
$rdvs = [];
foreach ($calendarData as $day) {
    if (!empty($day['appointments'])) {
        $rdvs = array_merge($rdvs, $day['appointments']);
    }
}
// Trier par date
usort($rdvs, function ($a, $b) {
    return strtotime($a['date_rdv']) - strtotime($b['date_rdv']);
});

// récupérer les évaluations récentes
$ratingsData = getProviderRatings($userId, 1, 5);
$evaluations = $ratingsData['ratings'] ?? [];

// récupérer les factures récentes
$factures = getProviderInvoices($userId);

// récupérer la note moyenne
$averageRating = getProviderAverageRating($userId);

// définir le titre de la page
$pageTitle = "Tableau de bord - Espace Prestataire";

// inclure l'en-tête
include_once __DIR__ . '/../../templates/header.php';
?>

<main class="dashboard-page py-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h2">Tableau de bord</h1>
                <p class="text-muted">Bienvenue dans votre espace prestataire, <?= $_SESSION['user_name'] ?></p>
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
                                <i class="fas fa-clipboard-list fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Prestations</h6>
                                <h2 class="card-title mb-0"><?= count($prestations) ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="mes-prestations.php" class="btn btn-sm btn-outline-success">Gérer</a>
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
                                <h6 class="card-subtitle text-muted mb-1">Évaluations</h6>
                                <h2 class="card-title mb-0"><?= count($evaluations) ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="mes-evaluations.php" class="btn btn-sm btn-outline-warning">Voir toutes</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-info bg-opacity-10 text-info rounded p-3 me-3">
                                <i class="fas fa-file-invoice-dollar fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle text-muted mb-1">Factures</h6>
                                <h2 class="card-title mb-0"><?= count($factures) ?></h2>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="mes-factures.php" class="btn btn-sm btn-outline-info">Voir toutes</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- planning des rendez-vous -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Planning des rendez-vous</h5>
                        <a href="mes-rendez-vous.php" class="btn btn-sm btn-outline-primary">Gérer mon planning</a>
                    </div>
                    <div class="card-body">
                        <?php
                        echo "<pre>DEBUG: Contents of \$rdvs array:\n"; // Debugging line
                        var_dump($rdvs); // Debugging line
                        echo "</pre>"; // Debugging line
                        ?>
                        <?php if (empty($rdvs)): ?>
                            <p class="text-center text-muted my-5">Aucun rendez-vous planifié</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($rdvs, 0, 5) as $rdv):
                                    // récupérer les détails si nécessaire
                                    $prestation = isset($rdv['prestation']) ? $rdv['prestation'] : (isset($rdv['prestation_id']) ? fetchOne('prestations', "id = {$rdv['prestation_id']}") : null);
                                    $client = isset($rdv['client']) ? $rdv['client'] : (isset($rdv['personne_id']) ? fetchOne('personnes', "id = {$rdv['personne_id']}") : null);
                                ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?= $prestation['nom'] ?? $rdv['prestation_nom'] ?? 'Prestation inconnue' ?></h6>
                                                <p class="mb-1">
                                                    <span class="fw-bold">Client :</span>
                                                    <?= $client ? $client['prenom'] . ' ' . $client['nom'] : ($rdv['client_nom'] ?? 'Client inconnu') ?>
                                                </p>
                                                <p class="text-muted mb-0">
                                                    <i class="far fa-calendar-alt me-1"></i> <?= isset($rdv['date_formatee']) ? $rdv['date_formatee'] : formatDate($rdv['date_rdv'], 'd/m/Y') ?>
                                                    <i class="far fa-clock ms-2 me-1"></i> <?= isset($rdv['heure_formatee']) ? $rdv['heure_formatee'] : formatDate($rdv['date_rdv'], 'H:i') ?>
                                                </p>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-map-marker-alt me-1"></i> <?= $rdv['lieu'] ?? 'Non précisé' ?>
                                                </p>
                                            </div>
                                            <div>
                                                <span class="badge bg-<?= isset($rdv['statut_classe']) ? $rdv['statut_classe'] : ($rdv['statut'] == 'confirme' ? 'success' : 'warning') ?> mb-2 d-block">
                                                    <?= ucfirst($rdv['statut']) ?>
                                                </span>
                                                <a href="details-rdv.php?id=<?= $rdv['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    Détails
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- dernières évaluations -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Dernières évaluations</h5>
                        <a href="mes-evaluations.php" class="btn btn-sm btn-outline-primary">Toutes les évaluations</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($evaluations)): ?>
                            <p class="text-center text-muted my-5">Aucune évaluation disponible</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($evaluations as $evaluation):
                                    // récupérer les détails de la prestation si nécessaire
                                    $prestation = isset($evaluation['prestation']) ? $evaluation['prestation'] : (isset($evaluation['prestation_id']) ? fetchOne('prestations', "id = {$evaluation['prestation_id']}") : null);
                                ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?= $prestation['nom'] ?? $evaluation['prestation_nom'] ?? 'Prestation inconnue' ?></h6>
                                                <div class="mb-2">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?= $i <= $evaluation['note'] ? 'text-warning' : 'text-muted' ?>"></i>
                                                    <?php endfor; ?>
                                                    <span class="ms-2"><?= $evaluation['note'] ?>/5</span>
                                                </div>
                                                <p class="mb-0"><?= $evaluation['commentaire'] ?></p>
                                                <p class="text-muted small mt-1">
                                                    <i class="far fa-calendar-alt me-1"></i> <?= isset($evaluation['date_formatee']) ? $evaluation['date_formatee'] : formatDate($evaluation['date_evaluation'], 'd/m/Y') ?>
                                                </p>
                                            </div>
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
                                <a href="ajouter-prestation.php" class="btn btn-primary d-block py-3">
                                    <i class="fas fa-plus-circle me-2"></i>Ajouter prestation
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="gerer-disponibilites.php" class="btn btn-success d-block py-3">
                                    <i class="fas fa-calendar-plus me-2"></i>Gérer disponibilités
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="mes-contrats.php" class="btn btn-warning d-block py-3">
                                    <i class="fas fa-file-contract me-2"></i>Mes contrats
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="contact-support.php" class="btn btn-info d-block py-3">
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
// inclure le pied de page
include_once __DIR__ . '/../../templates/footer.php';
?>