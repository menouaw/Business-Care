<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/donations.php'; 

$viewData = setupDonationsPage();
extract($viewData); 

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                </a>
            </div>

            <?php echo displayFlashMessages(); ?>

            
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Proposer un nouveau don</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($associations)): ?>
                        <div class="alert alert-warning">Impossible de proposer un don car aucune association n'est enregistrée.</div>
                    <?php else: ?>
                        <form action="donations.php" method="post" id="donation-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                            <div class="row g-3">
                                <div class="col-md-6 mb-3">
                                    <label for="donation_type" class="form-label">Type de don*</label>
                                    <select class="form-select" id="donation_type" name="donation_type" required>
                                        <option value="" selected disabled>Choisir...</option>
                                        <option value="financier">Financier</option>
                                        <option value="materiel">Matériel</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="association_id" class="form-label">Association bénéficiaire*</label>
                                    <select class="form-select" id="association_id" name="association_id" required>
                                        <option value="" selected disabled>Choisir...</option>
                                        <?php foreach ($associations as $asso): ?>
                                            <option value="<?= $asso['id'] ?>"><?= htmlspecialchars($asso['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                
                                <div class="col-md-6 mb-3">
                                    <label for="montant" class="form-label">Montant (€)</label>
                                    <input type="number" class="form-control" id="montant" name="montant" step="0.01" min="0.01" placeholder="Pour don financier">
                                    <small class="form-text text-muted">Requis uniquement si le type est "Financier".</small>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Si don matériel, décrivez l'objet. Requis pour ce type. Si don financier, optionnel."></textarea>
                                    <small class="form-text text-muted">Requis si le type est "Matériel".</small>
                                </div>
                            </div>

                            <button type="submit" name="submit_donation" class="btn btn-primary">
                                <i class="fas fa-hand-holding-heart me-1"></i> Proposer mon don
                            </button>
                        </form>
                    <?php endif; 
                    ?>
                </div>
            </div>

            
            <h3 class="mt-5 mb-3">Historique de mes dons</h3>
            <?php if (empty($donations)): ?>
                <div class="alert alert-secondary">Vous n'avez pas encore effectué de don via la plateforme.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Montant / Description</th>
                                <th>Association</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donations as $don):
                                $typeDon = ucfirst(htmlspecialchars($don['type']));
                                $montantDesc = '';
                                if ($don['type'] === 'financier') {
                                    $montantDesc = formatMoney($don['montant'] ?? 0);
                                } else {
                                    $montantDesc = htmlspecialchars($don['description'] ?? 'N/A');
                                }
                                $assoNom = htmlspecialchars($don['association_nom'] ?? 'N/A');
                                $dateDon = formatDate($don['date_don'], 'd/m/Y');

                                
                                $statutText = ucfirst(htmlspecialchars($don['statut']));
                                $statutBadgeClass = 'bg-secondary'; 
                                if ($don['statut'] === 'enregistre') {
                                    $statutBadgeClass = 'bg-success'; 
                                }
                                
                                
                            ?>
                                <tr>
                                    <td><?= $dateDon ?></td>
                                    <td><?= $typeDon ?></td>
                                    <td><?= $montantDesc ?></td>
                                    <td><?= $assoNom ?></td>
                                    <td>
                                        <span class="badge <?= $statutBadgeClass ?>"><?= $statutText ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>
