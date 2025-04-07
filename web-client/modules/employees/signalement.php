<?php
require_once __DIR__ . '/../../includes/init.php'; // Inclut config, session_start, fonctions de base

requireEmployeeLogin(); // Vérifie que l'employé est connecté

$pageTitle = generatePageTitle('Faire un signalement'); // Définit le titre de la page

$signalement_message = null;
if (isset($_SESSION['signalement_message'])) {
    $signalement_message = $_SESSION['signalement_message'];
    unset($_SESSION['signalement_message']); // Supprimer après lecture
}

$existing_categories = [];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT DISTINCT categorie FROM signalements WHERE categorie IS NOT NULL AND categorie != '' ORDER BY categorie");
    $existing_categories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Récupère seulement la colonne 'categorie'
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des catégories de signalement: " . $e->getMessage());
    // Gérer l'erreur si nécessaire, par exemple afficher un message ou utiliser une liste par défaut
    // Pour l'instant, on continue avec une liste vide en cas d'erreur.
}

include_once __DIR__ . '/../../templates/header.php'; // Inclut le header HTML
?>

<main class="signalement-page py-4">
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h2">Faire un signalement</h1>
                <p class="text-muted">Vous pouvez utiliser ce formulaire pour signaler anonymement ou non une situation préoccupante, un problème ou toute autre information que vous jugez importante de remonter.</p>
            </div>
        </div>

        <?php if ($signalement_message): ?>
            <div id="signalement-response" class="alert alert-<?php echo htmlspecialchars($signalement_message['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($signalement_message['text']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form id="signalementForm" method="post" action="/Business-Care/api/client/signalements.php">
                    <div class="mb-3">
                        <label for="categorie" class="form-label">Catégorie (facultatif)</label>
                        <select class="form-select" id="categorie" name="categorie">
                            <option value="">-- Sélectionner une catégorie existante --</option>
                            <?php foreach ($existing_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars(ucfirst($cat)); // Met la première lettre en majuscule pour l'affichage 
                                                                                        ?></option>
                            <?php endforeach; ?>
                            <!-- On pourrait ajouter une option "Autre" statique si on veut permettre de nouvelles catégories -->
                            <!-- <option value="autre">Autre (préciser dans la description)</option> -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description détaillée *</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required aria-required="true"></textarea>
                        <div class="form-text">Veuillez décrire la situation le plus précisément possible.</div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="1" id="anonyme" name="anonyme">
                        <label class="form-check-label" for="anonyme">
                            Je souhaite faire ce signalement de manière anonyme.
                        </label>
                        <div class="form-text">Si vous cochez cette case, vos informations personnelles ne seront pas associées à ce signalement.</div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="submitBtn">Envoyer le signalement</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php'; // Inclut le footer HTML
?>