<?php
require_once '../../includes/init.php'; 
require_once '../../includes/page_functions/modules/quotes.php'; 

requireRole(ROLE_ADMIN);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    flashMessage('Identifiant de devis invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/quotes/index.php');
}

$result = quotesGetDetails($id);

if (!$result) {
    flashMessage("Devis non trouvé.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/quotes/index.php');
}

$quote = $result['quote'];
$lines = $result['lines'];

$tvaAmount = $quote['montant_total'] - $quote['montant_ht'];

$pageTitle = "Informations du devis";
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
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/quotes/edit.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="Modifier ce devis">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <?php 
                    $deleteParams = ['action' => 'delete', 'id' => $quote['id'], 'csrf_token' => generateToken()];
                    $deleteUrl = WEBADMIN_URL . '/modules/quotes/index.php?' . http_build_query($deleteParams);
                    ?>
                    <a href="<?php echo $deleteUrl; ?>" 
                       class="btn btn-sm btn-danger me-2 btn-delete"
                       data-bs-toggle="tooltip" title="Supprimer ce devis"
                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce devis (ID: <?php echo $quote['id']; ?>) ?');">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/quotes/index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste des devis">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                     <!-- TODO: Add Generate PDF button? -->
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card mb-4">
                        <div class="card-header">
                           <i class="fas fa-file-invoice-dollar me-1"></i> Informations
                        </div>
                        <div class="card-body">
                             <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <small class="text-muted d-block">ID</small>
                                        <strong>#<?php echo $quote['id']; ?></strong>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Entreprise cliente</small>
                                        <strong>
                                            <?php if ($quote['entreprise_id']): ?>
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/companies/view.php?id=<?php echo $quote['entreprise_id']; ?>">
                                                <?php echo htmlspecialchars($quote['nom_entreprise']); ?>
                                            </a>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </strong>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Statut</small>
                                        <?php echo getStatusBadge($quote['statut']); ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                     <div class="mb-3">
                                        <small class="text-muted d-block">Date de Création</small>
                                        <strong><?php echo formatDate($quote['date_creation'], 'd/m/Y'); ?></strong>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Date de validité</small>
                                        <strong><?php echo formatDate($quote['date_validite'], 'd/m/Y'); ?></strong>
                                    </div>
                                     <div class="mb-3">
                                        <small class="text-muted d-block">Délai de paiement</small>
                                        <strong><?php echo $quote['delai_paiement'] ? $quote['delai_paiement'] . ' jours' : '-'; ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                     <div class="mb-3">
                                        <small class="text-muted d-block">Montant HT</small>
                                        <strong><?php echo formatMoney($quote['montant_ht']); ?></strong>
                                    </div>
                                     <div class="mb-3">
                                        <small class="text-muted d-block">TVA (<?php echo htmlspecialchars($quote['tva']); ?>%)</small>
                                        <strong><?php echo formatMoney($tvaAmount); ?></strong>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Montant TTC</small>
                                        <strong class="fs-5"><?php echo formatMoney($quote['montant_total']); ?></strong>
                                    </div>
                                </div>
                             </div>
                             <?php if ($quote['conditions_paiement']): ?>
                                <hr>
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <small class="text-muted d-block">Conditions de paiement</small>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($quote['conditions_paiement'])); ?></p>
                                    </div>
                                </div>
                             <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

             <div class="row">
                <div class="col-lg-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <?php if ($quote['est_personnalise']): ?>
                                <i class="fas fa-cogs me-1"></i> Programme Personnalisé
                            <?php elseif ($quote['service_id']): ?>
                                <i class="fas fa-concierge-bell me-1"></i> Détails du Service Standard
                            <?php else: ?>
                                <i class="fas fa-tasks me-1"></i> Lignes de prestations
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if ($quote['est_personnalise']): // Display Personalized Program Details ?>
                                <?php if ($quote['service_id']): // Check if based on a standard service ?>
                                <p class="fst-italic mb-2">Basé sur le service: 
                                    <a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $quote['service_id']; ?>" title="Voir le service standard">
                                        <?php echo htmlspecialchars($quote['nom_service'] ?? 'Inconnu'); ?>
                                    </a>
                                </p>
                                <?php endif; ?>
                                
                                <div class="row mb-3">
                                     <div class="col-md-6">
                                        <small class="text-muted d-block">Nombre de salariés estimés</small>
                                        <strong><?php echo htmlspecialchars($quote['nombre_salaries_estimes'] ?? 'N/A'); ?></strong>
                                    </div>
                                     <div class="col-md-6">
                                        <small class="text-muted d-block">Tarif Négocié (Annuel/Salarié HT)</small>
                                        <?php 
                                            // Attempt to calculate negotiated rate if possible, otherwise show N/A
                                            $negociatedRate = null;
                                            if (!empty($quote['nombre_salaries_estimes']) && $quote['nombre_salaries_estimes'] > 0 && isset($quote['montant_ht'])) {
                                                $negociatedRate = $quote['montant_ht'] / $quote['nombre_salaries_estimes'];
                                            }
                                        ?>
                                        <strong><?php echo ($negociatedRate !== null) ? formatMoney($negociatedRate) : 'N/A'; ?></strong>
                                        <?php if ($quote['service_id'] && isset($quote['tarif_annuel_par_salarie']) && $negociatedRate !== null && $negociatedRate != $quote['tarif_annuel_par_salarie']): ?>
                                            <small class="text-muted">(Standard: <?php echo formatMoney($quote['tarif_annuel_par_salarie']); ?>)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($quote['notes_negociation'])): ?>
                                    <div class="mb-3 p-3 bg-light border rounded">
                                         <small class="text-muted d-block fw-bold">Notes Internes sur la Négociation</small>
                                        <?php echo nl2br(htmlspecialchars($quote['notes_negociation'])); ?>
                                    </div>
                                <?php endif; ?>

                                <hr>
                                <div class="row">
                                    <div class="col-md-8 text-end"><strong>Total HT Négocié:</strong></div>
                                    <div class="col-md-4 text-end"><strong><?php echo formatMoney($quote['montant_ht']); ?></strong></div>
                                </div>
                                 <div class="row">
                                    <div class="col-md-8 text-end"><strong>TVA (<?php echo htmlspecialchars($quote['tva']); ?>%):</strong></div>
                                    <div class="col-md-4 text-end"><strong><?php echo formatMoney($tvaAmount); ?></strong></div>
                                </div>
                                 <div class="row mt-1">
                                    <div class="col-md-8 text-end fs-5"><strong>Total TTC Négocié:</strong></div>
                                    <div class="col-md-4 text-end fs-5"><strong><?php echo formatMoney($quote['montant_total']); ?></strong></div>
                                </div>

                                <?php if (!empty($lines)): // Display additional specific lines if present ?>
                                <h5 class="mt-4">Prestations Spécifiques Ajoutées</h5>
                                 <div class="table-responsive">
                                    <table class="table table-striped table-hover table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 30%;">Prestation</th>
                                                <th>Description Spécifique</th>
                                                <th class="text-end">Qté</th>
                                                <th class="text-end">Prix Unitaire (HT)</th>
                                                <th class="text-end">Total Ligne (HT)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lines as $line): 
                                                $lineTotal = $line['quantite'] * $line['prix_unitaire_devis'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $line['prestation_id']; ?>" title="Voir la prestation">
                                                        <?php echo htmlspecialchars($line['nom_prestation']); ?>
                                                    </a>
                                                </td>
                                                 <td><?php echo nl2br(htmlspecialchars($line['description_specifique'] ?? '-')); ?></td>
                                                <td class="text-end"><?php echo $line['quantite']; ?></td>
                                                <td class="text-end"><?php echo formatMoney($line['prix_unitaire_devis']); ?></td>
                                                <td class="text-end"><?php echo formatMoney($lineTotal); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                 </div>
                                <?php endif; ?>

                            <?php elseif ($quote['service_id']): ?>
                                <div class="row">
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Service selectionné</small>
                                        <strong>
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $quote['service_id']; ?>" title="Voir le service">
                                                <?php echo htmlspecialchars($quote['nom_service'] ?? 'Inconnu'); ?>
                                            </a>
                                        </strong>
                                    </div>
                                     <div class="col-md-4">
                                        <small class="text-muted d-block">Nombre de salariés estimés</small>
                                        <strong><?php echo htmlspecialchars($quote['nombre_salaries_estimes'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Tarif standard (Annuel/Salarié HT)</small>
                                        <strong><?php echo formatMoney($quote['tarif_annuel_par_salarie'] ?? 0); ?></strong>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-8 text-end"><strong>Total HT (Service x Nb Salariés):</strong></div>
                                    <div class="col-md-4 text-end"><strong><?php echo formatMoney($quote['montant_ht']); ?></strong></div>
                                </div>
                                 <div class="row">
                                    <div class="col-md-8 text-end"><strong>TVA (<?php echo htmlspecialchars($quote['tva']); ?>%):</strong></div>
                                    <div class="col-md-4 text-end"><strong><?php echo formatMoney($tvaAmount); ?></strong></div>
                                </div>
                                 <div class="row mt-1">
                                    <div class="col-md-8 text-end fs-5"><strong>Total TTC:</strong></div>
                                    <div class="col-md-4 text-end fs-5"><strong><?php echo formatMoney($quote['montant_total']); ?></strong></div>
                                </div>

                                <?php if (!empty($lines)):  ?>
                                <h5 class="mt-4">Prestations additionnelles</h5>
                                 <div class="table-responsive">
                                    <table class="table table-striped table-hover table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 30%;">Prestation</th>
                                                <th>Description Spécifique</th>
                                                <th class="text-end">Qté</th>
                                                <th class="text-end">Prix Unitaire (HT)</th>
                                                <th class="text-end">Total Ligne (HT)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lines as $line): 
                                                $lineTotal = $line['quantite'] * $line['prix_unitaire_devis'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $line['prestation_id']; ?>" title="Voir la prestation">
                                                        <?php echo htmlspecialchars($line['nom_prestation']); ?>
                                                    </a>
                                                </td>
                                                 <td><?php echo nl2br(htmlspecialchars($line['description_specifique'] ?? '-')); ?></td>
                                                <td class="text-end"><?php echo $line['quantite']; ?></td>
                                                <td class="text-end"><?php echo formatMoney($line['prix_unitaire_devis']); ?></td>
                                                <td class="text-end"><?php echo formatMoney($lineTotal); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                 </div>
                                <?php endif; ?>

                            <?php elseif (empty($lines)): ?>
                                <div class="text-center text-muted fst-italic">
                                    Ce devis ne contient ni service, ni prestation spécifique.
                                </div>
                             <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 30%;">Prestation</th>
                                                <th>Description Spécifique</th>
                                                <th class="text-end">Qté</th>
                                                <th class="text-end">Prix Unitaire (HT)</th>
                                                <th class="text-end">Total Ligne (HT)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lines as $line): 
                                                $lineTotal = $line['quantite'] * $line['prix_unitaire_devis'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo WEBADMIN_URL; ?>/modules/services/view.php?id=<?php echo $line['prestation_id']; ?>" title="Voir la prestation">
                                                        <?php echo htmlspecialchars($line['nom_prestation']); ?>
                                                    </a>
                                                </td>
                                                 <td><?php echo nl2br(htmlspecialchars($line['description_specifique'] ?? '-')); ?></td>
                                                <td class="text-end"><?php echo $line['quantite']; ?></td>
                                                <td class="text-end"><?php echo formatMoney($line['prix_unitaire_devis']); ?></td>
                                                <td class="text-end"><?php echo formatMoney($lineTotal); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="text-end fw-bold">Total HT</td>
                                                <td class="text-end fw-bold"><?php echo formatMoney($quote['montant_ht']); ?></td>
                                            </tr>
                                             <tr>
                                                <td colspan="4" class="text-end fw-bold">TVA (<?php echo htmlspecialchars($quote['tva']); ?>%)</td>
                                                <td class="text-end fw-bold"><?php echo formatMoney($tvaAmount); ?></td>
                                            </tr>
                                             <tr>
                                                <td colspan="4" class="text-end fw-bold fs-5">Total TTC</td>
                                                <td class="text-end fw-bold fs-5"><?php echo formatMoney($quote['montant_total']); ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
             </div>


            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>

</rewritten_file>
