<?php
require_once '../../includes/page_functions/modules/billing.php';

requireRole(ROLE_ADMIN);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = $_GET['type'] ?? 'client'; 

if ($id <= 0 || !in_array($type, ['client', 'provider'])) {
    flashMessage('Identifiant ou type de facture invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/billing/index.php');
}

$invoice = null;
$pageTitle = "Informations de la facture";

if ($type === 'client') {
    $invoice = billingGetClientInvoiceDetails($id);
} elseif ($type === 'provider') {
    $invoice = billingGetProviderInvoiceDetails($id);
}

if (!$invoice) {
    flashMessage("Facture non trouvée.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/billing/index.php?type=' . $type);
}


include_once '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                     <button class="btn btn-sm btn-secondary me-2" onclick="window.print();" data-bs-toggle="tooltip" title="Imprimer / PDF">
                        <i class="fas fa-print"></i> Imprimer / PDF
                    </button>
                    <a href="<?php echo WEBADMIN_URL; ?>/modules/billing/index.php?type=<?php echo $type; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retourner à la liste">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-file-invoice-dollar me-1"></i> Informations
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if ($type === 'client'): ?>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">N° Facture</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($invoice['numero_facture']); ?></dd>

                                    <dt class="col-sm-4">Entreprise</dt>
                                    <dd class="col-sm-8">
                                        <?php if ($invoice['entreprise_id']): ?>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/companies/view.php?id=<?php echo $invoice['entreprise_id']; ?>">
                                            <?php echo htmlspecialchars($invoice['nom_entreprise'] ?? 'N/A'); ?>
                                        </a>
                                        <?php else: echo 'N/A'; endif; ?>
                                    </dd>

                                     <dt class="col-sm-4">Devis</dt>
                                    <dd class="col-sm-8">
                                        <?php if ($invoice['devis_id']): ?>
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/quotes/view.php?id=<?php echo $invoice['devis_id']; ?>">Voir devis #<?php echo $invoice['devis_id']; ?></a>
                                        <?php else: echo '-'; endif; ?>
                                    </dd>

                                    <dt class="col-sm-4">Émission</dt>
                                    <dd class="col-sm-8"><?php echo formatDate($invoice['date_emission'], 'd/m/Y'); ?></dd>

                                    <dt class="col-sm-4">Échéance</dt>
                                    <dd class="col-sm-8"><?php echo formatDate($invoice['date_echeance'], 'd/m/Y'); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                 <dl class="row">
                                    <dt class="col-sm-4">Montant HT</dt>
                                    <dd class="col-sm-8"><?php echo formatMoney($invoice['montant_ht']); ?></dd>

                                    <dt class="col-sm-4">TVA (<?php echo ($invoice['tva']); ?>%)</dt>
                                    <dd class="col-sm-8"><?php echo formatMoney($invoice['montant_total'] - $invoice['montant_ht']); ?></dd>
                                    
                                    <dt class="col-sm-4">Montant TTC</dt>
                                    <dd class="col-sm-8 fw-bold"><?php echo formatMoney($invoice['montant_total']); ?></dd>

                                    <dt class="col-sm-4">Statut</dt>
                                    <dd class="col-sm-8"><?php echo billingGetInvoiceStatusBadge($invoice['statut'], 'client'); ?></dd>

                                    <dt class="col-sm-4">Paiement</dt>
                                    <dd class="col-sm-8"><?php echo $invoice['mode_paiement'] ? ucfirst($invoice['mode_paiement']) : '-'; ?></dd>
                                    
                                     <dt class="col-sm-4">Date</dt>
                                    <dd class="col-sm-8"><?php echo $invoice['date_paiement'] ? formatDate($invoice['date_paiement'], 'd/m/Y H:i') : '-'; ?></dd>
                                </dl>
                            </div>
                        <?php elseif ($type === 'provider'): ?>
                             <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">N° Facture</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($invoice['numero_facture']); ?></dd>

                                    <dt class="col-sm-4">Prestataire</dt>
                                    <dd class="col-sm-8">
                                         <?php if ($invoice['prestataire_id']): ?>
                                        <a href="<?php echo WEBADMIN_URL; ?>/modules/providers/view.php?id=<?php echo $invoice['prestataire_id']; ?>">
                                            <?php echo htmlspecialchars($invoice['nom_prestataire'] ?? 'N/A'); ?>
                                        </a>
                                        <?php else: echo 'N/A'; endif; ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Date</dt>
                                    <dd class="col-sm-8"><?php echo formatDate($invoice['date_facture'], 'd/m/Y'); ?></dd>

                                     <dt class="col-sm-4">Période</dt>
                                    <dd class="col-sm-8"><?php echo formatDate($invoice['periode_debut'], 'd/m/Y'); ?> - <?php echo formatDate($invoice['periode_fin'], 'd/m/Y'); ?></dd>
                                </dl>
                             </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">Montant TTC</dt>
                                    <dd class="col-sm-8 fw-bold"><?php echo formatMoney($invoice['montant_total']); ?></dd>

                                    <dt class="col-sm-4">Statut</dt>
                                    <dd class="col-sm-8"><?php echo billingGetInvoiceStatusBadge($invoice['statut'], 'provider'); ?></dd>
                                    
                                     <dt class="col-sm-4">Date</dt>
                                    <dd class="col-sm-8"><?php echo $invoice['date_paiement'] ? formatDate($invoice['date_paiement'], 'd/m/Y H:i') : '-'; ?></dd>
                                </dl>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($type === 'client' && $invoice['statut'] !== INVOICE_STATUS_PAID && $invoice['statut'] !== INVOICE_STATUS_CANCELLED):
                        $clientPaymentModes = billingGetClientInvoicePaymentModes();
                    ?>
                        <hr>
                        <h5>Marquer comme payée</h5>
                        <form action="<?php echo WEBADMIN_URL; ?>/modules/billing/actions.php" method="POST" class="row g-3 align-items-end">
                             <input type="hidden" name="action" value="update_client_status">
                            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo INVOICE_STATUS_PAID; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                            <div class="col-md-4">
                                <label for="payment_mode" class="form-label">Paiement</label>
                                <select name="payment_mode" id="payment_mode" class="form-select form-select-sm" required>
                                    <option value="">Choisir...</option>
                                    <?php foreach ($clientPaymentModes as $mode): ?>
                                        <option value="<?php echo $mode; ?>"><?php echo ucfirst($mode); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Confirmer</button>
                            </div>
                        </form>
                    <?php elseif ($type === 'provider' && $invoice['statut'] === PRACTITIONER_INVOICE_STATUS_UNPAID):
                         $providerStatuses = billingGetProviderInvoiceStatuses();
                    ?>
                         <hr>
                        <h5>Changer le statut</h5>
                        <form action="<?php echo WEBADMIN_URL; ?>/modules/billing/actions.php" method="POST" class="row g-3 align-items-end">
                             <input type="hidden" name="action" value="update_provider_status">
                            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                            <div class="col-md-4">
                                <label for="new_status" class="form-label">Nouveau Statut</label>
                                <select name="new_status" id="new_status" class="form-select form-select-sm" required>
                                    <?php foreach ($providerStatuses as $s): 
                                        if($s === $invoice['statut']) continue; 
                                    ?>
                                        <option value="<?php echo $s; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $s))); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div class="col-md-4">
                                <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-sync-alt"></i> Mettre à jour</button>
                            </div>
                        </form>
                     <?php endif; ?>
                </div>
            </div>

            <?php if ($type === 'provider' && !empty($invoice['lines'])): ?>
            <div class="card mb-4">
                 <div class="card-header">
                    <i class="fas fa-list-ul me-1"></i> Détails
                </div>
                <div class="card-body">
                     <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Date</th>
                                    <th>Prestation</th>
                                    <th class="text-end">Montant</th>
                                </tr>
                            </thead>
                             <tbody>
                                <?php foreach ($invoice['lines'] as $line): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($line['description']); ?></td>
                                    <td><?php echo formatDate($line['date_rdv'], 'd/m/Y'); ?></td>
                                    <td>
                                        <?php if ($line['rendez_vous_id']): ?>
                                            <a href="<?php echo WEBADMIN_URL; ?>/modules/appointments/view.php?id=<?php echo $line['rendez_vous_id']; ?>">
                                                <?php echo htmlspecialchars($line['nom_prestation'] ?? 'Voir RDV'); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($line['nom_prestation'] ?? '-'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?php echo formatMoney($line['montant']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                             </tbody>
                             <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total</td>
                                    <td class="text-end fw-bold"><?php echo formatMoney($invoice['montant_total']); ?></td>
                                </tr>
                             </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php include_once '../../templates/footer.php'; ?>
        </main>
    </div>
</div>


<script>
</script>
