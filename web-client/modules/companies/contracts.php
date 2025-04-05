<?php


// Includes
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

// Accès réservé aux entreprises
requireRole(ROLE_ENTREPRISE);

// ID Entreprise (Session)
$entrepriseId = $_SESSION['user_entreprise'];
$personneId = $_SESSION['user_id'];

// Validation ID Entreprise
if (!isset($_SESSION['user_entreprise']) || !filter_var($_SESSION['user_entreprise'], FILTER_VALIDATE_INT) || $_SESSION['user_entreprise'] <= 0) {
    logSystemActivity('error', "ID entreprise manquant ou invalide en session pour user_id: " . ($personneId ?? 'inconnu') . " lors de l'accès à contracts.php");
    flashMessage("Impossible de vérifier votre entreprise. Veuillez vous reconnecter.", "danger");
    redirectTo(WEBCLIENT_URL . '/index.php');
    exit;
}

// Paramètres GET
$contractId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// === Vue Détails Contrat ===
if ($contractId > 0) {

    // Récupérer données contrat
    $contract = getCompanyContractDetails($entrepriseId, $contractId);

    // Vérifier existence et accès
    if (!$contract) {
        flashMessage("Contrat introuvable ou accès non autorisé.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/companies/contracts.php');
        exit;
    }

    // Titre page
    $pageTitle = "Détails du contrat #" . htmlspecialchars($contract['reference'] ?? $contractId);

    // Header
    include_once __DIR__ . '/../../templates/header.php';

?>
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo $pageTitle; ?></h1>
            <div>
                <a href="contracts.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <!-- Informations Générales -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Informations générales</h5>
                <div class="btn-group">
                    <?php /* Placeholder: Bouton Renouvellement ? */ ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Référence:</strong> <?php echo htmlspecialchars($contract['reference'] ?? 'N/A'); ?></p>
                        <p class="mb-1"><strong>Type de contrat:</strong> <?php echo htmlspecialchars(ucfirst($contract['type_contrat'] ?? 'N/A')); ?></p>
                        <p class="mb-1"><strong>Date de début:</strong> <?php echo isset($contract['date_debut']) ? formatDate($contract['date_debut'], 'd/m/Y') : 'N/A'; ?></p>
                        <p class="mb-1"><strong>Date de fin:</strong> <?php echo isset($contract['date_fin']) && $contract['date_fin'] ? formatDate($contract['date_fin'], 'd/m/Y') : 'Indéterminée'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Statut:</strong> <?php echo isset($contract['statut']) ? getStatusBadge($contract['statut']) : 'N/A'; ?></p>
                        <p class="mb-1"><strong>Montant mensuel:</strong> <?php echo isset($contract['montant_mensuel']) ? formatMoney($contract['montant_mensuel']) : 'N/A'; ?></p>
                        <p class="mb-1"><strong>Nombre de salariés couverts:</strong> <?php echo htmlspecialchars($contract['nombre_salaries'] ?? 'Non spécifié'); ?></p>
                        <p class="mb-1"><strong>Date de création:</strong> <?php echo isset($contract['created_at']) ? formatDate($contract['created_at'], 'd/m/Y H:i') : 'N/A'; ?></p>
                    </div>
                </div>
                <?php if (!empty($contract['conditions_particulieres'])):
                ?>
                    <div class="mt-4">
                        <h6>Conditions particulières:</h6>
                        <div class="p-3 bg-light rounded border">
                            <?php echo nl2br(htmlspecialchars($contract['conditions_particulieres'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Services Inclus -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Services inclus</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($contract['services'])):
                ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Description</th>
                                    <th>Catégorie</th>
                                    <th>Prix indicatif</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contract['services'] as $service):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['nom'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($service['description'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($service['categorie'] ?? '-')); ?></td>
                                        <td><?php echo htmlspecialchars($service['prix_formate'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else:
                ?>
                    <p class="text-muted text-center my-3">Aucun service spécifique n'est listé pour ce contrat.</p>
                <?php endif; ?>
            </div>
        </div>



    </main>
<?php

    // Footer
    include_once __DIR__ . '/../../templates/footer.php';

    // Fin script vue détail
    exit;
} else {

    // === Vue Liste Contrats (Dashboard) ===

    $filter_status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'actif';
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $currentHistoryPage = isset($_GET['hpage']) ? (int)$_GET['hpage'] : 1; // Page pour l'historique

    // Récupérer la liste principale des contrats avec pagination
    $mainLimit = 5; // Limite à 5
    $contractsData = getCompanyContracts($entrepriseId, $filter_status, $currentPage, $mainLimit);
    $contrats = $contractsData['contracts'];
    $mainPaginationInfo = $contractsData['pagination']; // Récupérer infos

    // Construire le bon URL Pattern pour la pagination principale
    $mainUrlPattern = "?status=" . urlencode($filter_status) . "&page={page}";

    // Préparer les données pour renderPagination (principale)
    $mainPaginationDataForRender = [
        'currentPage' => $mainPaginationInfo['current'],
        'totalPages' => $mainPaginationInfo['totalPages'],
        'totalItems' => $mainPaginationInfo['total'],
        'perPage' => $mainPaginationInfo['limit']
    ];

    // Générer le HTML de pagination correct pour la liste principale
    $mainPaginationHtmlCorrected = renderPagination($mainPaginationDataForRender, $mainUrlPattern);

    // Récupérer l'historique (contrats expirés et résiliés) AVEC pagination
    $historyLimit = 5; // Limite à 5
    $historyContractsData = getCompanyContracts($entrepriseId, 'history', $currentHistoryPage, $historyLimit);
    $historyContracts = $historyContractsData['contracts'];
    $historyPaginationInfo = $historyContractsData['pagination']; // Récupérer juste les infos

    // Construire le bon URL Pattern pour la pagination de l'historique
    $historyUrlPattern = "?status=" . urlencode($filter_status) . "&hpage={page}";

    // Préparer les données pour renderPagination (pour l'historique)
    $historyPaginationDataForRender = [
        'currentPage' => $historyPaginationInfo['current'],
        'totalPages' => $historyPaginationInfo['totalPages'],
        'totalItems' => $historyPaginationInfo['total'],
        'perPage' => $historyPaginationInfo['limit']
    ];

    // Générer le HTML de pagination correct pour l'historique
    $historyPaginationHtmlCorrected = renderPagination($historyPaginationDataForRender, $historyUrlPattern);

    // Assurer le formatage pour l'historique
    foreach ($historyContracts as &$hist) { // Utiliser $historyContracts directement
        if (!isset($hist['reference'])) $hist['reference'] = 'CT-' . str_pad($hist['id'], 6, '0', STR_PAD_LEFT);
        if (!isset($hist['date_debut_formatee']) && isset($hist['date_debut'])) $hist['date_debut_formatee'] = formatDate($hist['date_debut'], 'd/m/Y');
        if (!isset($hist['date_fin_formatee']) && isset($hist['date_fin'])) $hist['date_fin_formatee'] = formatDate($hist['date_fin'], 'd/m/Y');
        if (!isset($hist['statut_badge']) && isset($hist['statut'])) $hist['statut_badge'] = getStatusBadge($hist['statut']);
    }
    unset($hist); // Détacher la référence

    // Titre page
    $pageTitle = "Mes Contrats - Espace Entreprise";

    // Header
    include_once __DIR__ . '/../../templates/header.php';
?>

    <main class="container py-4">
        <h1 class="mb-4">Gestion des Contrats</h1>

        <?php echo displayFlashMessages(); ?>

        <!-- Liste Principale des Contrats -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="card-title mb-0 me-3">Liste de vos contrats</h5>

                <form action="contracts.php" method="GET" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="statusFilter" class="col-form-label">Filtrer par statut:</label>
                    </div>
                    <div class="col-auto">
                        <select name="status" id="statusFilter" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="actif" <?= ($filter_status === 'actif') ? 'selected' : '' ?>>Actifs</option>
                            <option value="en_attente" <?= ($filter_status === 'en_attente') ? 'selected' : '' ?>>En attente</option>
                            <option value="expire" <?= ($filter_status === 'expire') ? 'selected' : '' ?>>Expirés</option>
                            <option value="resilie" <?= ($filter_status === 'resilie') ? 'selected' : '' ?>>Résiliés</option>
                            <option value="all" <?= ($filter_status === 'all') ? 'selected' : '' ?>>Tous</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($contrats) && $filter_status === 'actif'): // Affiche seulement s'il n'y a pas de contrat *actif*
                ?>
                    <p class="text-center text-muted my-5">Vous n'avez aucun contrat actif pour le moment.</p>
                    <div class="text-center">
                        <a href="quotes.php" class="btn btn-primary">Demander un nouveau devis</a>
                    </div>
                <?php elseif (empty($contrats)):
                ?>
                    <p class="text-center text-muted my-5">Aucun contrat trouvé pour le statut "<?= htmlspecialchars(ucfirst($filter_status)) ?>".</p>
                <?php else:
                ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Type de Contrat</th>
                                    <th>Date de Début</th>
                                    <th>Date de Fin</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contrats as $contratItem):
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($contratItem['reference'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars(ucfirst($contratItem['type_contrat'])) ?></td>
                                        <td><?= formatDate($contratItem['date_debut'], 'd/m/Y') ?></td>
                                        <td><?= $contratItem['date_fin'] ? formatDate($contratItem['date_fin'], 'd/m/Y') : 'Indéterminée' ?></td>
                                        <td><?= getStatusBadge($contratItem['statut']) ?></td>
                                        <td>
                                            <a href="contracts.php?id=<?= $contratItem['id'] ?>" class="btn btn-sm btn-info me-1" title="Voir les détails"><i class="fas fa-eye"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?= $mainPaginationHtmlCorrected // Afficher les liens de pagination pour la liste principale 
                    ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historique des Contrats Précédents (Expirés/Résiliés) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Historique des contrats précédents</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($historyContracts)):
                ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Réf.</th>
                                    <th>Type</th>
                                    <th>Début</th>
                                    <th>Fin</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historyContracts as $hist):
                                ?>
                                    <tr>
                                        <td><a href="contracts.php?id=<?= $hist['id'] ?>"><?= htmlspecialchars($hist['reference']) ?></a></td>
                                        <td><?= htmlspecialchars(ucfirst($hist['type_contrat'])) ?></td>
                                        <td><?= $hist['date_debut_formatee'] ?? 'N/A' ?></td>
                                        <td><?= $hist['date_fin_formatee'] ?? 'N/A' ?></td>
                                        <td><?= $hist['statut_badge'] ?? 'N/A' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?= $historyPaginationHtmlCorrected // Afficher les liens de pagination pour l'historique 
                    ?>
                <?php else:
                ?>
                    <p class="text-muted text-center my-3">Aucun contrat expiré ou résilié trouvé.</p>
                <?php endif; ?>
            </div>
        </div>

    </main>

<?php
    // Footer
    include_once __DIR__ . '/../../templates/footer.php';
}
?>