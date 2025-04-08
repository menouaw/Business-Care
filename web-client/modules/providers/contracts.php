<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/providers.php';

requireRole(ROLE_PRESTATAIRE);
$provider_id = $_SESSION['user_id'];

$pageTitle = "Mon Engagement - Espace Prestataire";

$providerInfo = null;
$providerServices = [];
$providerEngagements = [];
$dbError = null;

try {
    $providerInfo = fetchOne(TABLE_USERS, "id = :id AND role_id = :role", [':id' => $provider_id, ':role' => ROLE_PRESTATAIRE]);

    if (!$providerInfo) {
        throw new Exception("Impossible de récupérer les informations du prestataire (ID: $provider_id). L'utilisateur n'est peut-être pas configuré comme prestataire.");
    }

    $providerServices = getProviderServices($provider_id);

    $providerEngagements = getProviderContracts($provider_id);
} catch (Exception $e) {
    error_log("Error fetching provider engagement info for provider ID $provider_id: " . $e->getMessage());
    $dbError = "Impossible de charger les informations relatives à votre engagement pour le moment. Veuillez réessayer plus tard ou contacter le support.";
    $providerInfo = null;
    $providerServices = [];
    $providerEngagements = [];
}

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="provider-contracts-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2 mb-0"><i class="fas fa-file-signature me-2"></i>Mon Engagement</h1>
                <p class="text-muted mb-0">Informations relatives à votre collaboration avec Business Care.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/providers/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <?php if ($dbError): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($dbError) ?>
            </div>
        <?php endif; ?>

        <?php if ($providerInfo): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Statut de votre compte</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Statut actuel :</strong> <?= getStatusBadge($providerInfo['statut'] ?? 'Inconnu') ?></p>
                            <p class="mb-0"><strong>Membre depuis le :</strong> <?= isset($providerInfo['created_at']) ? formatDate($providerInfo['created_at'], 'd/m/Y') : 'Date inconnue' ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <a href="<?= WEBCLIENT_URL ?>/modules/providers/settings.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-user-edit me-1"></i> Modifier mon profil</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Services Proposés</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($providerServices)): ?>
                        <p class="text-muted">Vous n'avez pas encore réalisé de prestation via la plateforme, ou aucun service spécifique ne vous est actuellement assigné. Veuillez contacter l'administration si vous pensez qu'il s'agit d'une erreur.</p>
                    <?php else: ?>
                        <p class="text-muted">Liste des types de prestations que vous avez réalisées via Business Care :</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Service</th>
                                        <th>Type</th>
                                        <th>Catégorie</th>
                                        <th class="text-end">Prix Indicatif</th>
                                        <th class="text-center">Nb. Prestations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($providerServices as $service): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($service['nom'] ?? 'Service inconnu') ?></td>
                                            <td><?= htmlspecialchars(ucfirst($service['type'] ?? 'N/A')) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($service['categorie'] ?? 'N/A')) ?></td>
                                            <td class="text-end"><?= $service['prix_formate'] ?? 'N/A' ?></td>
                                            <td class="text-center"><?= htmlspecialchars($service['nombre_prestations'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-3 text-muted small">
                            Cette liste est basée sur votre historique. Pour proposer de nouveaux services ou discuter de vos tarifs, veuillez <a href="<?= WEBCLIENT_URL ?>/modules/companies/contact.php">contacter Business Care</a>.
                        </p>
                    <?php endif; ?>
                    <div class="mt-3 text-center">
                        <a href="<?= WEBCLIENT_URL ?>/modules/companies/contact.php?subject=Proposition%20Nouveau%20Service%20Prestataire" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-plus-circle me-1"></i> Proposer un nouveau service
                        </a>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Engagement Actif (par entreprise cliente)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($providerEngagements)): ?>
                        <p class="text-muted">Aucun historique d'engagement actif (prestations dans les 6 derniers mois) trouvé avec des entreprises clientes via la plateforme.</p>
                    <?php else: ?>
                        <p class="text-muted">Voici un aperçu des entreprises pour lesquelles vous avez récemment effectué des prestations (6 derniers mois) :</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Entreprise Cliente</th>
                                        <th>Première Prestation (période)</th>
                                        <th>Dernière Prestation (période)</th>
                                        <th class="text-center">Nb. Prestations</th>
                                        <th class="text-center">Statut Engagement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($providerEngagements as $engagement): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($engagement['entreprise_nom'] ?? 'Inconnue') ?></td>
                                            <td><?= $engagement['date_debut_formatee'] ?? 'N/A' ?></td>
                                            <td><?= $engagement['date_derniere_formatee'] ?? 'N/A' ?></td>
                                            <td class="text-center"><?= htmlspecialchars($engagement['nombre_prestations'] ?? 0) ?></td>
                                            <td class="text-center"><?= getStatusBadge($engagement['statut'] ?? 'inconnu') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Documents et Conditions</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Vous pouvez consulter ici les documents relatifs à votre collaboration.</p>
                    <ul>
                        <li><a href="#" target="_blank">Conditions Générales de Collaboration Prestataire</a> (Lien exemple)</li>
                        <li><a href="#" target="_blank">Charte Qualité Prestataire</a> (Lien exemple)</li>
                    </ul>
                    <p class="text-muted small mt-3">Assurez-vous de bien prendre connaissance de ces documents.</p>
                </div>
            </div>

        <?php elseif (!$dbError): ?>
            <div class="alert alert-warning" role="alert">
                Impossible d'afficher les informations car les données du prestataire n'ont pas pu être chargées correctement. Veuillez vous reconnecter ou contacter le support si le problème persiste.
            </div>
        <?php endif; ?>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>