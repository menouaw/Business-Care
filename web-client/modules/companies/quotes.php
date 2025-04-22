<?php


require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ENTREPRISE);

$entrepriseId = $_SESSION['user_entreprise'];

$action = $_GET['action'] ?? 'list';
$quoteId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]) : 1;

$submittedData = [];

if ($action === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = getFormData();
    $submissionResult = processQuoteRequestSubmission($formData, $entrepriseId);

    if (!$submissionResult['success']) {
        flashMessage($submissionResult['message'], 'danger');
        $submittedData = $submissionResult['submittedData'] ?? $formData;
    } else {
        // Redirect on success
        $successMessage = urlencode($submissionResult['message']);
        $redirectUrl = WEBCLIENT_URL . '/modules/companies/quotes.php?action=list&quote_success=' . $successMessage;
        redirectTo($redirectUrl);
        exit;
    }
}

$viewData = prepareQuotesViewData($action, $entrepriseId, $quoteId, $page, $submittedData);

if (isset($viewData['redirectUrl'])) {
    redirectTo($viewData['redirectUrl']);
    exit;
}

$pageTitle = $viewData['pageTitle'];
$quotesData = $viewData['quotesData'];
$quoteDetails = $viewData['quoteDetails'];
$paginationHtml = $viewData['paginationHtml'];
$available_services = $viewData['available_services'];
$submittedData = $viewData['submittedData'];


$csrfToken = generateToken();

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0"><?= $pageTitle ?></h1>
        <div>
            <?php if ($action === 'list'): ?>
                <a href="?action=request" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Nouvelle Demande de Devis
                </a>
            <?php endif; ?>
            <?php if ($action === 'view' || $action === 'request'): ?>
                <a href="?action=list" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-list me-1"></i> Retour à la Liste
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-sm btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>
    </div>

    <?php
    if (isset($_GET['quote_success'])) {
        $successMessageDecoded = urldecode($_GET['quote_success']);
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
            . htmlspecialchars($successMessageDecoded)
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            . '</div>';
    }
    echo displayFlashMessages();
    ?>

    <?php if ($action === 'list'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?php if (empty($quotesData)): ?>
                    <div class="alert alert-info text-center">Vous n'avez pas encore soumis de demande de devis.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Numéro Devis</th>
                                    <th>Date Création</th>
                                    <th>Montant Total (Estimé)</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quotesData as $quote): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($quote['numero_devis']) ?></td>
                                        <td><?= htmlspecialchars($quote['date_creation_formatee']) ?></td>
                                        <td><?= $quote['montant_total_formate'] 
                                            ?></td>
                                        <td><?= $quote['statut_badge'] 
                                            ?></td>
                                        <td class="text-end">
                                            <a href="?action=view&id=<?= $quote['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir Détails"><i class="fas fa-eye"></i></a>
                                            <?php if ($quote['statut'] === 'en_attente'): ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 d-flex justify-content-center">
                        <?= $paginationHtml ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($action === 'view' && $quoteDetails): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Détails du Devis N° <?= htmlspecialchars($quoteDetails['numero_devis']) ?></h5>
                <span>Statut: <?= $quoteDetails['statut_badge'] ?></span>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Numéro Devis</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($quoteDetails['numero_devis']) ?></dd>

                    <dt class="col-sm-3">Date de Création</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($quoteDetails['date_creation_formatee']) ?></dd>

                    <dt class="col-sm-3">Date de Validité</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($quoteDetails['date_validite_formatee']) ?></dd>

                    <dt class="col-sm-3">Statut</dt>
                    <dd class="col-sm-9"><?= $quoteDetails['statut_badge'] ?></dd>

                    <dt class="col-sm-3">Montant HT (Estimé)</dt>
                    <dd class="col-sm-9"><?= $quoteDetails['montant_ht_formate'] ?></dd>

                    <dt class="col-sm-3">TVA (Estimée)</dt>
                    <dd class="col-sm-9"><?= $quoteDetails['tva_formate'] ?> (<?= $quoteDetails['tva_taux'] ?>)</dd>

                    <dt class="col-sm-3">Montant Total TTC (Estimé)</dt>
                    <dd class="col-sm-9"><strong><?= $quoteDetails['montant_total_formate'] ?></strong></dd>

                    <dt class="col-sm-3">Conditions / Demande initiale</dt>
                    <dd class="col-sm-9">
                        <pre style="white-space: pre-wrap; word-wrap: break-word;"><?= htmlspecialchars($quoteDetails['conditions_paiement'] ?? 'N/A') ?></pre>
                    </dd>

                    <dt class="col-sm-3">Délai Paiement</dt>
                    <dd class="col-sm-9"><?= isset($quoteDetails['delai_paiement']) ? htmlspecialchars($quoteDetails['delai_paiement']) . ' jours' : 'Non spécifié' ?></dd>

                </dl>
            </div>
            <div class="card-footer bg-white text-end">
                <a href="?action=list" class="btn btn-secondary">Retour à la Liste</a>
                <?php if ($quoteDetails['statut'] === 'en_attente'): ?>
                <?php elseif ($quoteDetails['statut'] === 'accepte'): ?>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($action === 'request'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Formuler votre demande</h5>
            </div>
            <div class="card-body">
                <form method="post" action="quotes.php?action=request">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken; ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="service_souhaite" class="form-label">Type de service/contrat souhaité*</label>
                            <select class="form-select" id="service_souhaite" name="service_souhaite" required>
                                <option value="" disabled <?= !isset($submittedData['service_souhaite']) ? 'selected' : ''; ?>>Sélectionnez une option...</option>
                                <?php foreach ($available_services as $key => $description): ?>
                                    <option value="<?= htmlspecialchars($key); ?>"
                                        <?= (isset($submittedData['service_souhaite']) && $submittedData['service_souhaite'] == $key) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($description); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="nombre_salaries" class="form-label">Nombre approximatif de salariés concernés</label>
                            <input type="number" class="form-control" id="nombre_salaries" name="nombre_salaries" min="1" value="<?= htmlspecialchars($submittedData['nombre_salaries'] ?? ''); ?>" placeholder="Ex: 50">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description_besoin" class="form-label">Description détaillée de votre besoin*</label>
                        <textarea class="form-control" id="description_besoin" name="description_besoin" rows="5" required placeholder="Décrivez précisément vos attentes, les services spécifiques souhaités, la durée envisagée, etc."><?= htmlspecialchars($submittedData['description_besoin'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="contact_personne" class="form-label">Personne à contacter</label>
                        <input type="text" class="form-control" id="contact_personne" name="contact_personne" value="<?= htmlspecialchars($submittedData['contact_personne'] ?? $_SESSION['user_name']); ?>" placeholder="Nom et Prénom">
                    </div>

                    <div class="mb-3">
                        <label for="contact_email" class="form-label">Email de contact</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= htmlspecialchars($submittedData['contact_email'] ?? $_SESSION['user_email']); ?>" placeholder="adresse@email.com">
                    </div>

                    <div class="mb-3">
                        <label for="contact_telephone" class="form-label">Téléphone de contact</label>
                        <input type="tel" class="form-control" id="contact_telephone" name="contact_telephone" pattern="^(0|\+33)[1-9]([-. ]?[0-9]{2}){4}$" value="<?= htmlspecialchars($submittedData['contact_telephone'] ?? ''); ?>" placeholder="01 23 45 67 89">
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Envoyer la demande de devis</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>