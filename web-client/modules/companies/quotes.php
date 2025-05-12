<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies/quotes.php';

requireRole(ROLE_ENTREPRISE);


handleQuoteRequestPost();



$entreprise_id = $_SESSION['user_entreprise'] ?? 0;

if ($entreprise_id <= 0) {
    flashMessage("Impossible d'identifier votre entreprise.", "danger");
    redirectTo(WEBCLIENT_URL . '/modules/companies/dashboard.php');
    exit;
}


$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'list';
$quote_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);



switch ($action) {
    case 'view':
        $quote = null;
        if ($quote_id) {
            $quote = getQuoteDetails($quote_id, $entreprise_id);
            if (!$quote) {
                flashMessage("Devis non trouvé ou accès refusé.", "warning");
                redirectTo(WEBCLIENT_URL . '/modules/companies/quotes.php');
                exit;
            }
            $pageTitle = "Détails du Devis #" . htmlspecialchars($quote['id']);
        } else {
            flashMessage("ID de devis manquant pour la visualisation.", "warning");
            redirectTo(WEBCLIENT_URL . '/modules/companies/quotes.php');
            exit;
        }
        break;

    case 'request':
        $pageTitle = "Demander un Devis";
        $detailedServicePacks = getDetailedServicePacks();
        $availablePrestations = getAvailablePrestationsWithPrices();
        break;

    case 'list':
    default:
        $action = 'list';
        $pageTitle = "Mes Devis";
        $quotes = getCompanyQuotes($entreprise_id);
        break;
}


include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">

            <?php echo displayFlashMessages(); ?>

            <?php if ($action === 'view' && isset($quote)): ?>
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

                <div class="mb-4">
                    <h5>Nos offres standards :</h5>
                    <?php if (empty($detailedServicePacks)): ?>
                        <p>Aucun pack de service n'est actuellement disponible.</p>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($detailedServicePacks as $pack): ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <div class="card-header bg-light-grey-pack-header">
                                            <h5 class="card-title mb-0"><?= htmlspecialchars($pack['type']) ?></h5>
                                        </div>
                                        <div class="card-body d-flex flex-column">
                                            <!-- Optionnel : Récupérer une description plus courte si elle existe ou générer un résumé -->
                                            <!-- <?php if (!empty($pack['description'])): ?>
                                                <p class="card-text flex-grow-1"><small><?= htmlspecialchars($pack['description']) ?></small></p>
                                            <?php endif; ?> -->

                                            <h6 class="mt-3 mb-2">Ce pack inclut :</h6>
                                            <ul class="list-group list-group-flush list-group-small flex-grow-1">
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><i class="fas fa-users me-2 text-primary"></i>Effectif max</span>
                                                    <span class="badge bg-light text-dark rounded-pill">
                                                        <?= isset($pack['max_effectif_inferieur_egal']) ? '&le; ' . htmlspecialchars($pack['max_effectif_inferieur_egal']) : '251+' ?>
                                                    </span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><i class="fas fa-running me-2 text-success"></i>Activités (participation BC)</span>
                                                    <span class="badge bg-success rounded-pill"><?= htmlspecialchars($pack['activites_incluses'] ?? 0) ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><i class="fas fa-stethoscope me-2 text-info"></i>RDV médicaux inclus</span>
                                                    <span class="badge bg-info rounded-pill"><?= htmlspecialchars($pack['rdv_medicaux_inclus'] ?? 0) ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><i class="fas fa-headset me-2 text-danger"></i>RDV médicaux suppl.</span>
                                                    <span class="badge bg-light text-dark rounded-pill">
                                                        <?php
                                                        $rdv_supp_cost = 'N/A';
                                                        if ($pack['type'] === 'Premium Pack') $rdv_supp_cost = '50 euros/rdv';
                                                        elseif ($pack['type'] === 'Starter Pack' || $pack['type'] === 'Basic Pack') $rdv_supp_cost = '75 euros/rdv';
                                                        echo $rdv_supp_cost;
                                                        ?>
                                                    </span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><i class="fas fa-robot me-2 text-secondary"></i>Questions Chatbot /mois</span>
                                                    <span class="badge bg-secondary rounded-pill">
                                                        <?= isset($pack['chatbot_questions_limite']) ? htmlspecialchars($pack['chatbot_questions_limite']) : 'Illimité' ?>
                                                    </span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><i class="fas fa-book-open me-2 text-dark"></i>Accès Fiches Pratiques</span>
                                                    <span class="badge bg-dark rounded-pill">Illimité</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><i class="fas fa-bullhorn me-2 text-warning"></i>Conseils hebdomadaires</span>
                                                    <span class="badge bg-warning text-dark rounded-pill">
                                                        <?= $pack['conseils_hebdo_personnalises'] ? 'Personnalises' : 'Non personnalises' ?>
                                                    </span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><i class="fas fa-calendar-alt me-2 text-muted"></i>Événements / Communautés</span>
                                                    <span class="badge bg-light text-dark rounded-pill">Accès illimité</span>
                                                </li>
                                            </ul>
                                            <div class="mt-auto text-center pt-3">
                                                <span class="h5 fw-bold"><?= htmlspecialchars(number_format($pack['tarif_annuel_par_salarie'] ?? 0, 0)) ?> euros</span>
                                                <small class="text-muted">/ an / salarié</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <hr>
                <form method="POST" action="<?= WEBCLIENT_URL ?>/modules/companies/quotes.php" id="quote-request-form">
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
                    </div>

                    <div class="mb-3" id="nombre-salaries-container" style="display: none;">
                        <label for="nombre_salaries" class="form-label">Nombre de salariés concernés pour ce pack</label>
                        <input type="number" class="form-control" id="nombre_salaries" name="nombre_salaries" min="1" value="">
                        <div class="form-text">Ce nombre sera utilisé pour calculer le coût total du pack sélectionné.</div>
                    </div>

                    <div id="pack-specific-action-btn-container" class="mb-3" style="display: none;">
                        <div class="mb-3">
                            <label for="notes" class="form-label">Vos besoins et demandes spécifiques <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="notes" name="notes" rows="6" required></textarea>
                            <div class="form-text">Si vous n'avez pas choisi de pack, décrivez ici vos besoins (nombre salariés, prestations souhaitées...). Si vous avez choisi un pack, vous pouvez ajouter ici des questions ou précisions.</div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Envoyer ma demande
                        </button>
                    </div>
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