<?php

/**
 * Page de gestion des contrats pour les entreprises clientes
 * Affiche la liste des contrats OU les détails d'un contrat spécifique.
 */

// Inclure les fonctions nécessaires (base de données, authentification, fonctions spécifiques aux entreprises)
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

// Vérifier que l'utilisateur est authentifié et a le rôle entreprise
requireRole(ROLE_ENTREPRISE);

// Récupérer l'ID de l'entreprise depuis la session
$entrepriseId = $_SESSION['user_entreprise'];

// --- Validation de l'ID entreprise (importante) ---
if (!isset($_SESSION['user_entreprise']) || !filter_var($_SESSION['user_entreprise'], FILTER_VALIDATE_INT) || $_SESSION['user_entreprise'] <= 0) {
    logSystemActivity('error', "ID entreprise manquant ou invalide en session pour user_id: " . ($_SESSION['user_id'] ?? 'inconnu') . " lors de l'accès à contracts.php");
    flashMessage("Impossible de vérifier votre entreprise. Veuillez vous reconnecter.", "danger");
    redirectTo(WEBCLIENT_URL . '/index.php'); // Ou page de connexion
    exit;
}

// Vérifier si un ID de contrat spécifique est demandé
$contractId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ==================================================================
// ===          AFFICHAGE DES DÉTAILS D'UN CONTRAT                ===
// ==================================================================
if ($contractId > 0) {

    // Récupérer les détails du contrat spécifique
    $contract = getCompanyContractDetails($entrepriseId, $contractId);

    // Vérifier que le contrat existe et appartient bien à l'entreprise
    if (!$contract) {
        flashMessage("Contrat introuvable ou accès non autorisé.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/companies/contracts.php'); // Retour à la liste
        exit;
    }

    // --- Gestion des actions (ex: téléchargement PDF) ---
    // TODO: Ajouter ici la logique pour d'autres actions si nécessaire (ex: renouvellement)

    // Définir le titre de la page pour la vue détaillée
    $pageTitle = "Détails du contrat #" . htmlspecialchars($contract['reference'] ?? $contractId);

    // Inclure l'en-tête
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

        <?php echo displayFlashMessages(); // Afficher les messages flash (ex: erreur PDF) 
        ?>

        <!-- Informations Générales -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Informations générales</h5>
                <div class="btn-group">
                    <?php /* Placeholder pour bouton Renouvellement
                    if ($contract['statut'] === 'actif' && $contract['date_fin']): ?>
                        <button type="button" class="btn btn-sm btn-outline-success ms-2" data-bs-toggle="modal" data-bs-target="#renewModal">
                            <i class="fas fa-sync-alt me-1"></i> Demander un renouvellement
                        </button>
                    <?php endif; */ ?>
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
                <?php if (!empty($contract['conditions_particulieres'])): ?>
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
                <?php if (!empty($contract['services'])): // Assumant que getCompanyContractDetails récupère les services 
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
                                <?php foreach ($contract['services'] as $service): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['nom'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($service['description'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($service['categorie'] ?? '-')); ?></td>
                                        <td><?php echo htmlspecialchars($service['prix_formate'] ?? 'N/A'); // Assumant formatage dans la fonction
                                            ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center my-3">Aucun service spécifique n'est listé pour ce contrat.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Factures Liées (Exemple - nécessite la fonction getContractInvoices) -->
        <?php
        // $factures = getContractInvoices($contractId); // Activer si la fonction existe
        $factures = []; // Placeholder pour l'instant
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Factures liées (Exemple)</h5>
                <a href="invoices.php" class="btn btn-sm btn-outline-primary">
                    Voir toutes les factures
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($factures)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>N° Facture</th>
                                    <th>Date</th>
                                    <th>Montant Total</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($factures, 0, 5) as $facture): // Limiter l'affichage 
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($facture['numero_facture'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($facture['date_emission_formatee'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($facture['montant_total_formate'] ?? 'N/A'); ?></td>
                                        <td><?php echo $facture['statut_badge'] ?? 'N/A'; ?></td>
                                        <td>
                                            <a href="invoice.php?id=<?php echo $facture['id']; ?>" class="btn btn-sm btn-outline-info" title="Voir la facture">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php // TODO: Bouton payer ? 
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center my-3">Aucune facture récente spécifiquement liée à ce contrat n'est affichée ici (fonctionnalité à implémenter).</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historique du contrat (Optionnel - Commenté) -->
        <?php /*
         <div class="card shadow-sm"> ... Historique ... </div>
         */ ?>

        <!-- Modal de renouvellement (Optionnel - Commenté) -->
        <?php /*
         <div class="modal fade" id="renewModal" ...> ... Formulaire ... </div>
         */ ?>

    </main>
<?php

    // Inclure le pied de page pour la vue détaillée
    include_once __DIR__ . '/../../templates/footer.php';

    // Arrêter l'exécution du script ici pour ne pas afficher la liste en dessous
    exit;

    // ==================================================================
    // ===           AFFICHAGE DE LA LISTE DES CONTRATS               ===
    // ==================================================================
} else {

    // Récupérer tous les contrats de l'entreprise (actifs, inactifs, etc.)
    $contrats = getCompanyContracts($entrepriseId, null); // Le second paramètre 'null' pour récupérer tous les statuts

    // Définir le titre de la page pour la vue liste
    $pageTitle = "Mes Contrats - Espace Entreprise";

    // Inclure l'en-tête pour la vue liste
    include_once __DIR__ . '/../../templates/header.php';
?>

    <main class="container py-4">
        <h1 class="mb-4">Gestion des Contrats</h1>

        <?php echo displayFlashMessages(); ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Liste de vos contrats</h5>
                <!-- Ajouter ici des options de filtrage si nécessaire -->
                <!--càd filtre par statut, date de début, date de fin, etc. -->
            </div>
            <div class="card-body">
                <?php if (empty($contrats)): ?>
                    <p class="text-center text-muted my-5">Vous n'avez aucun contrat pour le moment.</p>
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
                                <?php foreach ($contrats as $contratItem): // Variable renommée pour éviter conflit 
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($contratItem['reference'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars(ucfirst($contratItem['type_contrat'])) ?></td>
                                        <td><?= formatDate($contratItem['date_debut'], 'd/m/Y') ?></td>
                                        <td><?= $contratItem['date_fin'] ? formatDate($contratItem['date_fin'], 'd/m/Y') : 'Indéterminée' ?></td>
                                        <td><?= getStatusBadge($contratItem['statut']) ?></td>
                                        <td>
                                            <a href="contracts.php?id=<?= $contratItem['id'] ?>" class="btn btn-sm btn-info me-1" title="Voir les détails"><i class="fas fa-eye"></i></a>
                                            <!-- Ajouter d'autres boutons d'action si nécessaire -->
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Ajouter ici la pagination si nécessaire -->
                <?php endif; ?>
            </div>
        </div>
    </main>

<?php
    // Inclure le pied de page pour la vue liste
    include_once __DIR__ . '/../../templates/footer.php';
} // Fin du else (vue liste)
?>