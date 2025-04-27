<?php
require_once '../../includes/page_functions/modules/financial.php';


requireRole(ROLE_ADMIN);


$summary = financialGetDashboardSummary();


$pageTitle = "Gestion des finances";
include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($pageTitle); ?></h1>
                 <div class="btn-toolbar mb-2 mb-md-0">
                    
                    <a href="<?php echo JAVA_URL; ?>/output/report.pdf" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-file-pdf me-2"></i> Voir Rapport d'activité (PDF)
                        
                    </a>
                </div>
            </div>

            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-header">Revenus en attente</div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo formatMoney($summary['total_pending_revenue']); ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-header">Paiements récents (<?php echo FINANCIAL_RECENT_PAYMENT_DAYS; ?> jours)</div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo formatMoney($summary['recent_payments_amount']); ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger mb-3">
                         <div class="card-header">Factures en retard</div>
                        <div class="card-body">
                             <h5 class="card-title"><?php echo formatMoney($summary['overdue_amount']); ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-dark bg-warning mb-3">
                         <div class="card-header">Paiements prestataires dus</div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo formatMoney($summary['pending_payouts_amount']); ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <h5 class="card-title">Rapport de revenus</h5>
                            <p class="card-text">Voir les revenus détaillés par période.</p>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/financial/revenue_report.php" class="btn btn-sm btn-outline-primary mt-auto">Consulter</a>
                        </div>
                    </div>
                </div>
                 <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <h5 class="card-title">Statut facturation client</h5>
                            <p class="card-text">Suivre les factures clients en retard.</p>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/financial/billing_status.php" class="btn btn-sm btn-outline-primary mt-auto">Consulter</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <h5 class="card-title">Analyse des devis</h5>
                            <p class="card-text">Analyser les taux de conversion des devis.</p>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/financial/quotes_analysis.php" class="btn btn-sm btn-outline-primary mt-auto">Consulter</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <h5 class="card-title">Paiements prestataires</h5>
                            <p class="card-text">Voir les montants dus aux prestataires.</p>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/financial/provider_payouts.php" class="btn btn-sm btn-outline-primary mt-auto">Consulter</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <h5 class="card-title">Résumé fiscal (TVA)</h5>
                            <p class="card-text">Consulter le résumé de la TVA collectée.</p>
                            <a href="<?php echo WEBADMIN_URL; ?>/modules/financial/tax_summary.php" class="btn btn-sm btn-outline-primary mt-auto">Consulter</a>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                           Graphiques d'activité
                        </div>
                        <div class="card-body">
                           
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <h6>Statistiques clients</h6>
                                    
                                </div>
                                <div class="col-md-4">
                                    <h6>Statistiques évènements</h6>
                                    
                                </div>
                                <div class="col-md-4">
                                     <h6>Statistiques prestations</h6>
                                     
                                </div>
                            </div>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Les graphiques détaillés sont générés périodiquement et consultables via le rapport PDF.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
