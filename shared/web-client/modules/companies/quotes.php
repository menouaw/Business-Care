<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/companies/quotes.php';

requireRole(ROLE_ENTREPRISE);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_quote'])) {

    $entreprise_id = $_SESSION['user_entreprise'] ?? 0;
    $personne_id = $_SESSION['user_id'] ?? 0;
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_SPECIAL_CHARS);
    $selected_service_id = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);


    $service_id_to_log = (!empty($selected_service_id) && $selected_service_id > 0) ? $selected_service_id : null;


    if ($entreprise_id > 0 && $personne_id > 0 && !empty($notes)) {
        $newQuoteId = saveQuoteRequest($entreprise_id, [
            'notes' => $notes,
            'service_id' => $service_id_to_log
        ]);

        if ($newQuoteId) {
            flashMessage("Votre demande de devis (N°{$newQuoteId}) a bien été enregistrée avec le statut 'en attente'. Notre équipe la traitera prochainement.", "success");
        } else {
            flashMessage("Une erreur est survenue lors de l'enregistrement de votre demande. Veuillez réessayer.", "danger");
        }
    } else {
        flashMessage("Erreur lors de l\'envoi de la demande. Veuillez remplir tous les champs nécessaires.", "danger");
    }

    redirectTo(WEBCLIENT_URL . '/modules/companies/quotes.php');
    exit;
}




if (!function_exists('getQuoteStatusBadgeClass')) { /* ... */
}

$entreprise_id = $_SESSION['user_entreprise'] ?? 0;


if ($entreprise_id <= 0) {
    flashMessage("Impossible d'identifier votre entreprise.", "danger");
    redirectTo(WEBCLIENT_URL . '/modules/companies/dashboard.php');
    exit;
}

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);

if (empty($action) || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = 'list';
}
$quote_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$pageTitle = "Gestion des Devis";
$quote = null;
$quotes = [];
$servicePacks = [];

if ($action === 'view' && $quote_id) {
    $quote = getQuoteDetails($quote_id, $entreprise_id);
    if (!$quote) {
        flashMessage("Devis non trouvé ou accès refusé.", "warning");
        redirectTo(WEBCLIENT_URL . '/modules/companies/quotes.php');
        exit;
    }
    $pageTitle = "Détails du Devis #" . $quote['id'];
} elseif ($action === 'request') {
    $pageTitle = "Demander un Devis";
    $servicePacks = getAvailableServicePacks();
} else {
    $action = 'list';
    $pageTitle = "Mes Devis";
    $quotes = getCompanyQuotes($entreprise_id);
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">

            <?php echo displayFlashMessages(); ?>

            <?php if ($action === 'view' && $quote): ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= WEBCLIENT_URL ?>/modules/companies/dashboard.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                        </a>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        Informations générales
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">ID Devis:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($quote['id']) ?></dd>

                            <dt class="col-sm-3">Date de Création:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(date('d/m/Y', strtotime($quote['date_creation']))) ?></dd>

                            <dt class="col-sm-3">Date de Validité:</dt>
                            <dd class="col-sm-9"><?= $quote['date_validite'] ? htmlspecialchars(date('d/m/Y', strtotime($quote['date_validite']))) : 'N/A' ?></dd>

                            <dt class="col-sm-3">Service Principal (si applicable):</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($quote['service_nom'] ?? 'Personnalisé') ?></dd>

                            <dt class="col-sm-3">Montant HT:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(number_format($quote['montant_ht'] ?? 0, 2, ',', ' ')) ?> <?= DEFAULT_CURRENCY ?></dd>

                            <dt class="col-sm-3">TVA (<?= htmlspecialchars($quote['tva'] ?? 0) ?>%):</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars(number_format(($quote['montant_total'] ?? 0) - ($quote['montant_ht'] ?? 0), 2, ',', ' ')) ?> <?= DEFAULT_CURRENCY ?></dd>

                            <dt class="col-sm-3">Montant Total TTC:</dt>
                            <dd class="col-sm-9 fw-bold"><?= htmlspecialchars(number_format($quote['montant_total'] ?? 0, 2, ',', ' ')) ?> <?= DEFAULT_CURRENCY ?></dd>

                            <dt class="col-sm-3">Statut:</dt>
                            <dd class="col-sm-9">
                                <span class="badge bg-<?= getQuoteStatusBadgeClass($quote['statut']) ?>">
                                    <?= htmlspecialchars(ucfirst($quote['statut'])) ?>
                                </span>
                            </dd>

                            <dt class="col-sm-3">Conditions Paiement:</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($quote['conditions_paiement'] ?? 'N/D') ?></dd>

                            <dt class="col-sm-3">Délai Paiement (jours):</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($quote['delai_paiement'] ?? 'N/D') ?></dd>

                            <dt class="col-sm-3">Notes / Négociation:</dt>
                            <dd class="col-sm-9">
                                <pre><?= htmlspecialchars($quote['notes_negociation'] ?? 'Aucune') ?></pre>
                            </dd>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        Prestations incluses
                    </div>
                    <div class="card-body">
                        <?php if (empty($quote['prestations'])): ?>
                            <p>Aucune prestation spécifique listée pour ce devis.</p>
                        <?php else: ?>
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nom Prestation</th>
                                        <th>Quantité</th>
                                        <th>Prix Unitaire (Devis)</th>
                                        <th>Description Spécifique</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quote['prestations'] as $prestation): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($prestation['prestation_nom']) ?></td>
                                            <td><?= htmlspecialchars($prestation['quantite']) ?></td>
                                            <td class="text-end"><?= htmlspecialchars(number_format($prestation['prix_unitaire_devis'], 2, ',', ' ')) ?> <?= DEFAULT_CURRENCY ?></td>
                                            <td><?= htmlspecialchars($prestation['description_specifique'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($action === 'request'): ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                    <a href="<?= WEBCLIENT_URL ?>/modules/companies/quotes.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Annuler et retour à la liste
                    </a>
                </div>
                <p>Vous pouvez sélectionner un pack de services standard ou décrire vos besoins pour une proposition sur mesure dans la zone de texte.</p>

                <form method="POST" action="<?= WEBCLIENT_URL ?>/modules/companies/quotes.php">
                    <input type="hidden" name="request_quote" value="1"> <?php
                                                                            ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()); ?>">

                    <div class="mb-3">
                        <label for="service_id" class="form-label">Choisir un Pack Standard (Optionnel)</label>
                        <select class="form-select" id="service_id" name="service_id">
                            <option value="">-- Demande Personnalisée (décrire ci-dessous) --</option> <?php
                                                                                                        ?>
                            <?php foreach ($servicePacks as $pack): ?>
                                <option value="<?= htmlspecialchars($pack['id']) ?>">
                                    <?= htmlspecialchars($pack['type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Laissez ce champ sur "Demande Personnalisée" si aucun pack ne correspond.</div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Vos besoins et demandes spécifiques <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="notes" name="notes" rows="6" required></textarea>
                        <div class="form-text">Si vous n'avez pas choisi de pack, décrivez ici vos besoins (nombre salariés, prestations souhaitées...). Si vous avez choisi un pack, vous pouvez ajouter ici des questions ou précisions.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Envoyer ma demande
                    </button>
                </form>

            <?php else:
            ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= WEBCLIENT_URL ?>/modules/companies/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de Bord
                        </a>
                        <a href="<?= WEBCLIENT_URL ?>/modules/companies/quotes.php?action=request" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i> Demander un Devis 
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date Création</th>
                                <th>Date Validité</th>
                                <th>Montant TTC</th>
                                <th>Statut</th>
                                <th>Service</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($quotes)): ?>
                                <tr>
                                    <td colspan="7">Aucun devis trouvé.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($quotes as $quote_item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($quote_item['id']) ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($quote_item['date_creation']))) ?></td>
                                        <td><?= $quote_item['date_validite'] ? htmlspecialchars(date('d/m/Y', strtotime($quote_item['date_validite']))) : '' ?></td>
                                        <td class="text-end"><?= htmlspecialchars(number_format($quote_item['montant_total'] ?? 0, 2, ',', ' ')) ?> <?= DEFAULT_CURRENCY ?></td>
                                        <td>
                                            <span class="badge bg-<?= getQuoteStatusBadgeClass($quote_item['statut']) ?>">
                                                <?= htmlspecialchars(ucfirst($quote_item['statut'])) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($quote_item['service_nom'] ?? 'Personnalisé') ?></td>
                                        <td>
                                            <a href="<?= WEBCLIENT_URL ?>/modules/companies/quotes.php?action=view&id=<?= $quote_item['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir Détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>