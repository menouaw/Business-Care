<?php

require_once __DIR__ . '/../../../shared/web-client/auth.php';
require_once __DIR__ . '/../../web-client/page_functions/modules/providers/invoices.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Factures</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
</head>
<body>
    <header>
        <h1>Mes Factures</h1>
    </header>

    <main>
        <?php if ($factureId && isset($facture)): ?>
            <!-- Affichage des détails d'une facture -->
            <h2>Facture n° <?= htmlspecialchars($facture['numero_facture']) ?></h2>
            <p>Date de Facturation : <?= formatDate($facture['date_facture']) ?></p>
            <p>Période : <?= formatDate($facture['periode_debut']) ?> - <?= formatDate($facture['periode_fin']) ?></p>
            <p>Montant Total : <?= formatMoney($facture['montant_total']) ?></p>
            <p>Statut : <?= htmlspecialchars($facture['statut']) ?></p>

            <h3>Détails des Prestations</h3>
            <?php if (count($lignes) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lignes as $ligne): ?>
                            <tr>
                                <td><?= htmlspecialchars($ligne['description']) ?></td>
                                <td><?= formatMoney($ligne['montant']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aucune prestation associée à cette facture.</p>
            <?php endif; ?>

            <p><a href="invoices.php">Retour à la liste des factures</a></p>

        <?php else: ?>
            <!-- Affichage de la liste des factures -->
            <?php if (count($factures) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Numéro de Facture</th>
                            <th>Date de Facturation</th>
                            <th>Période</th>
                            <th>Montant Total</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($factures as $facture): ?>
                            <tr>
                                <td><?= htmlspecialchars($facture['numero_facture']) ?></td>
                                <td><?= formatDate($facture['date_facture']) ?></td>
                                <td><?= formatDate($facture['periode_debut']) ?> - <?= formatDate($facture['periode_fin']) ?></td>
                                <td><?= formatMoney($facture['montant_total']) ?></td>
                                <td><?= htmlspecialchars($facture['statut']) ?></td>
                                <td>
                                    <a href="invoices.php?id=<?= $facture['id'] ?>">Voir</a>
                                    <?php if (file_exists("/path/to/invoices/{$facture['numero_facture']}.pdf")): ?>
                                        <a href="/path/to/invoices/<?= $facture['numero_facture'] ?>.pdf" download>Télécharger</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aucune facture disponible.</p>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>