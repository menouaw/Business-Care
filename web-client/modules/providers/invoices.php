<?php

require_once __DIR__ . '/../../../shared/web-client/auth.php';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers/invoices.php';

requireRole(ROLE_PRESTATAIRE); 

$viewData = setupInvoicesPage(); 
extract($viewData); 
include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle ?? 'Mes Factures') ?></h1>
            </div>

            <?php displayFlashMessages(); ?>
            <?php if (isset($factureId) && $factureId > 0 && isset($facture) && $facture !== null): ?>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Facture n° <?= htmlspecialchars($facture['numero_facture']) ?></h2>
                    </div>
                    <div class="card-body">
                        <p><strong>Date de Facturation :</strong> <?= formatDate($facture['date_facture']) ?></p>
                        <p><strong>Période :</strong> <?= formatDate($facture['periode_debut']) ?> - <?= formatDate($facture['periode_fin']) ?></p>
                        <p><strong>Montant Total :</strong> <span class="badge bg-primary fs-6"><?= formatMoney($facture['montant_total']) ?></span></p>
                        <p><strong>Statut :</strong> <span class="badge bg-secondary"><?= htmlspecialchars($facture['statut']) ?></span></p>

                        <h3 class="mt-4 h5">Détails des Prestations</h3>
                        <?php if (isset($lignes) && count($lignes) > 0): ?>
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-end">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lignes as $ligne): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ligne['description']) ?></td>
                                            <td class="text-end"><?= formatMoney($ligne['montant']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted">Aucune prestation associée à cette facture.</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="invoices.php" class="btn btn-secondary">Retour à la liste des factures</a>
                    </div>
                </div>

            <?php else: ?>
                
                <?php if (isset($factures) && count($factures) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                             <h2 class="h5 mb-0">Liste des Factures</h2>
                        </div>
                        <div class="card-body p-0"> 
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Numéro de Facture</th>
                                        <th>Date de Facturation</th>
                                        <th>Période</th>
                                        <th class="text-end">Montant Total</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($factures as $facture_item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($facture_item['numero_facture']) ?></td>
                                            <td><?= formatDate($facture_item['date_facture']) ?></td>
                                            <td><?= formatDate($facture_item['periode_debut']) ?> - <?= formatDate($facture_item['periode_fin']) ?></td>
                                            <td class="text-end"><?= formatMoney($facture_item['montant_total']) ?></td>
                                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($facture_item['statut']) ?></span></td>
                                            <td>
                                                <a href="invoices.php?id=<?= $facture_item['id'] ?>" class="btn btn-primary btn-sm">Voir</a>
                                                <?php /* if (file_exists("/path/to/invoices/{$facture_item['numero_facture']}.pdf")): ?>
                                                    <a href="/path/to/invoices/<?= $facture_item['numero_facture'] ?>.pdf" download class="btn btn-success btn-sm ms-1">Télécharger</a>
                                                <?php endif; */ ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        Aucune facture disponible pour le moment.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>