<?php


require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ENTREPRISE);

$entrepriseId = $_SESSION['user_entreprise'];
$personneId = $_SESSION['user_id'];

if (!isset($_SESSION['user_entreprise']) || !filter_var($_SESSION['user_entreprise'], FILTER_VALIDATE_INT) || $_SESSION['user_entreprise'] <= 0) {
    logSystemActivity('error', "ID entreprise manquant ou invalide en session pour user_id: " . ($personneId ?? 'inconnu') . " lors de l'accès à contracts.php");
    flashMessage("Impossible de vérifier votre entreprise. Veuillez vous reconnecter.", "danger");
    redirectTo(WEBCLIENT_URL . '/index.php');
    exit;
}

$contractId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($contractId > 0) {

    $contract = getCompanyContractDetails($entrepriseId, $contractId);

    if (!$contract) {
        flashMessage("Contrat introuvable ou accès non autorisé.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/companies/contracts.php');
        exit;
    }

    $pageTitle = "Détails du contrat #" . htmlspecialchars($contract['reference'] ?? $contractId);

    include_once __DIR__ . '/../../templates/header.php';

?>
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo $pageTitle; ?></h1>
            <div>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>


        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Informations générales</h5>
                <div class="btn-group">
                    <?php  ?>
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


        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Prestations incluses</h5>
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
                    <p class="text-muted text-center my-3">Aucune prestation spécifique n'est listée pour ce contrat.</p>
                <?php endif; ?>
            </div>
        </div>



    </main>
<?php

    include_once __DIR__ . '/../../templates/footer.php';

    exit;
} else {

    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    $mainLimit = 10;
    $contractsData = getCompanyContracts($entrepriseId, null, $currentPage, $mainLimit);
    $contrats = $contractsData['contracts'];
    $mainPaginationInfo = $contractsData['pagination'];

    $mainUrlPattern = "?page={page}";

    $mainPaginationDataForRender = [
        'currentPage' => $mainPaginationInfo['current'],
        'totalPages' => $mainPaginationInfo['totalPages'],
        'totalItems' => $mainPaginationInfo['total'],
        'perPage' => $mainPaginationInfo['limit']
    ];

    $mainPaginationHtmlCorrected = renderPagination($mainPaginationDataForRender, $mainUrlPattern);

    $pageTitle = "Vos Contrats - Espace Entreprise";

    include_once __DIR__ . '/../../templates/header.php';
?>

    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Vos Contrats</h1>
            <div>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="card-title mb-0 me-3">Tous vos contrats</h5>
            </div>
            <div class="card-body">
                <?php if (empty($contrats)): ?>
                    <p class="text-center text-muted my-5">Vous n'avez aucun contrat enregistré pour le moment.</p>
                    <div class="text-center">
                        <a href="quotes.php" class="btn btn-primary">Demander un nouveau devis</a>
                    </div>
                <?php else: ?>
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
                                <?php foreach ($contrats as $contratItem): ?>
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
                    <?= $mainPaginationHtmlCorrected ?>
                <?php endif; ?>
            </div>
        </div>

    </main>

<?php

    include_once __DIR__ . '/../../templates/footer.php';
}
?>