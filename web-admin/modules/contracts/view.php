<?php
require_once '../../includes/page_functions/modules/contracts.php';

requireRole(ROLE_ADMIN);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flashMessage('Identifiant de contrat invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/contracts/index.php');
}

$contract = contractsGetDetails($id);

if (!$contract) {
    flashMessage("Contrat non trouve.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/contracts/index.php');
}

$activeUserCount = contractsGetActiveUserCountForCompany($contract['entreprise_id']);

$pageTitle = "Details du contrat";
include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/edit.php?id=<?php echo $contract['id']; ?>" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="Modifier ce contrat">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/delete.php?id=<?php echo $contract['id']; ?>&csrf_token=<?php echo generateToken(); ?>" class="btn btn-sm btn-danger me-2 btn-delete"
                       data-bs-toggle="tooltip" title="Supprimer ce contrat">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                     <a href="<?php echo WEBADMIN_URL; ?>/modules/contracts/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Contrat</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Entreprise:</strong> <a href="<?php echo WEBADMIN_URL; ?>/modules/companies/view.php?id=<?php echo $contract['entreprise_id']; ?>" data-bs-toggle="tooltip" title="Voir l'entreprise <?php echo htmlspecialchars($contract['nom_entreprise']); ?>"><?php echo htmlspecialchars($contract['nom_entreprise']); ?></a></p>
                            <p><strong>Type de contrat:</strong> <?php echo htmlspecialchars(ucfirst($contract['type_contrat'])); ?></p>
                            <p><strong>Date de debut:</strong> <?php echo formatDate($contract['date_debut']); ?></p>
                            <p><strong>Date de fin:</strong> <?php echo $contract['date_fin'] ? formatDate($contract['date_fin']) : 'Indeterminee'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Montant mensuel:</strong> <?php echo $contract['montant_mensuel'] ? formatCurrency($contract['montant_mensuel']) : 'Non specifie'; ?></p>
                            <p><strong>Nombre de salariés actifs:</strong> <?php echo $activeUserCount; ?> (Contrat: <?php echo $contract['nombre_salaries'] ?: 'N/S'; ?>)</p>
                            <p><strong>Statut:</strong> <?php echo getStatusBadge($contract['statut']); ?></p>
                            <p><strong>Date de creation:</strong> <?php echo formatDate($contract['created_at']); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($contract['conditions_particulieres']): ?>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Conditions particulieres</h5>
                                <p><?php echo nl2br(htmlspecialchars($contract['conditions_particulieres'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Statistiques du contrat</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        try {
                            $dateDebut = new DateTime($contract['date_debut']);
                            $now = new DateTime();
                            $dateFinCalcul = $contract['date_fin'] ? new DateTime($contract['date_fin']) : $now;
                            
                            if ($dateFinCalcul < $dateDebut) {
                                $dateFinCalcul = $dateDebut;
                            }

                            $duree = $dateDebut->diff($dateFinCalcul);
                            $dureeTexte = formatDuration($duree);

                            $montantTotalEstime = 0;
                            if ($contract['montant_mensuel']) {
                                $interval = $dateDebut->diff($dateFinCalcul);
                                $mois = ($interval->y * 12) + $interval->m;
                                if ($interval->d > 0 || ($interval->m == 0 && $interval->y == 0 && $dateDebut->format('d') > $dateFinCalcul->format('d'))) {
                                    $mois += 1;
                                }
                                $montantTotalEstime = $mois * $contract['montant_mensuel'];
                            } else {
                                $montantTotalEstime = null; 
                            }
                        } catch (Exception $e) {
                            $dureeTexte = "Erreur de calcul";
                            $montantTotalEstime = null;
                            logSystemActivity('error', 'Erreur de calcul: ' . $e->getMessage());
                        }
                        ?>
                        <div class="col-md-4 text-center">
                            <h6>Duree <?php echo ($contract['date_fin'] ? 'totale' : 'actuelle'); ?></h6>
                            <p class="h4"><?php echo $dureeTexte; ?></p>
                        </div>
                        <?php if ($contract['montant_mensuel']): ?>
                            <div class="col-md-4 text-center">
                                <h6>Montant mensuel</h6>
                                <p class="h4"><?php echo formatCurrency($contract['montant_mensuel']); ?></p>
                            </div>
                            <div class="col-md-4 text-center">
                                <h6>Montant total <?php echo ($contract['date_fin'] ? 'facture' : 'estime'); ?></h6>
                                <p class="h4"><?php echo $montantTotalEstime !== null ? formatCurrency($montantTotalEstime) : 'N/A'; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>

</rewritten_file>
