<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireRole(ROLE_SALARIE);
$current_employee_id = $_SESSION['user_id'];
$csrfToken = generateToken(); // Génère ou récupère le token CSRF

// --- Traitement du formulaire de préférences --- 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_preferences') {
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        logSecurityEvent($current_employee_id, 'csrf_failure', '[SECURITY FAILURE] Tentative MAJ préférences conseils avec jeton invalide');
        flashMessage("Erreur de sécurité (jeton invalide).", "danger");
    } else {
        // Appel de la nouvelle fonction pour gérer la mise à jour
        handleCounselPreferencesUpdate($_POST, $current_employee_id);
    }
    // Rediriger pour éviter resoumission du formulaire (PRG pattern)
    redirectTo(WEBCLIENT_URL . '/modules/employees/counsel.php');
    exit;
}

// --- Récupération des données pour l'affichage ---
// Appel de la nouvelle fonction pour récupérer les données
$pageData = getCounselPageData($current_employee_id);

// Extraction des données pour un accès plus facile dans la vue
$personalizedTopics = $pageData['personalizedTopics'];
$generalTopics = $pageData['generalTopics'];
$availableCategories = $pageData['availableCategories'];
$userPreferences = $pageData['userPreferences'];
$dbError = $pageData['dbError'];

$pageTitle = "Conseils Bien-être - Espace Salarié";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="employee-counsel-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2 mb-0"><i class="fas fa-heartbeat me-2"></i>Conseils Bien-être</h1>
                <p class="text-muted mb-0">Retrouvez ici des articles et conseils pour améliorer votre qualité de vie au travail et personnelle.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Dashboard
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <?php if ($dbError): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($dbError) ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire de Préférences -->
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Vos Préférences de Conseils</h5>
            </div>
            <div class="card-body">
                <?php if (empty($availableCategories) && !$dbError): ?>
                    <p class="text-muted">Aucune catégorie de conseil n'est disponible pour définir des préférences pour le moment.</p>
                <?php elseif (!$dbError): ?>
                    <form action="<?= WEBCLIENT_URL ?>/modules/employees/counsel.php" method="POST">
                        <input type="hidden" name="action" value="update_preferences">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <p>Sélectionnez les catégories de conseils qui vous intéressent le plus :</p>
                        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-2 mb-3">
                            <?php foreach ($availableCategories as $category): ?>
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categories[]"
                                            value="<?= htmlspecialchars($category) ?>"
                                            id="cat-<?= htmlspecialchars(strtolower(str_replace(' ', '-', $category))) ?>"
                                            <?= in_array($category, $userPreferences) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="cat-<?= htmlspecialchars(strtolower(str_replace(' ', '-', $category))) ?>">
                                            <?= htmlspecialchars(ucfirst($category)) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Enregistrer mes préférences</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section Conseils Personnalisés -->
        <?php if (!empty($userPreferences)): ?>
            <section class="mb-5">
                <h3 class="mb-3">Vos Conseils Personnalisés</h3>
                <div class="row g-4">
                    <?php if (empty($personalizedTopics) && !$dbError): ?>
                        <div class="col-12">
                            <div class="alert alert-light text-center" role="alert">
                                Aucun conseil correspondant à vos préférences actuelles. Explorez les autres conseils ci-dessous !
                            </div>
                        </div>
                    <?php elseif (!empty($personalizedTopics)): ?>
                        <?php // Boucle pour afficher les conseils personnalisés 
                        ?>
                        <?php foreach ($personalizedTopics as $topic): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="icon-box bg-primary bg-opacity-10 text-primary rounded p-3 me-3 flex-shrink-0">
                                                <i class="<?= htmlspecialchars($topic['icone'] ?? 'fas fa-info-circle') ?> fa-2x"></i>
                                            </div>
                                            <div>
                                                <h5 class="card-title mb-1"><?= htmlspecialchars($topic['titre'] ?? 'Conseil sans titre') ?></h5>
                                                <span class="badge bg-primary text-white"><?= htmlspecialchars($topic['categorie'] ?? 'Général') ?></span>
                                            </div>
                                        </div>
                                        <p class="card-text text-muted flex-grow-1"><?= htmlspecialchars($topic['resume'] ?? 'Pas de résumé.') ?></p>
                                        <p class="card-text text-muted small flex-grow-1">
                                            <i><?= htmlspecialchars(substr($topic['contenu'] ?? '', 0, 150)) . (strlen($topic['contenu'] ?? '') > 150 ? '...' : '') ?></i>
                                        </p>
                                        <div class="mt-auto">
                                            <a href="conseil_detail.php?id=<?= htmlspecialchars($topic['id']) ?>" class="btn btn-sm btn-outline-primary">Lire la suite</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Section Autres Conseils (remplace l'ancienne section) -->
        <section>
            <h3 class="mb-3"><?= empty($userPreferences) ? 'Tous les Conseils Disponibles' : 'Autres Conseils Disponibles' ?></h3>
            <div class="row g-4">
                <?php if (empty($generalTopics) && !$dbError): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center" role="alert">
                            <?= empty($userPreferences) ? 'Aucun conseil disponible pour le moment.' : 'Aucun autre conseil disponible pour le moment.' ?>
                        </div>
                    </div>
                <?php elseif (!empty($generalTopics)): ?>
                    <?php // Boucle pour afficher les conseils généraux 
                    ?>
                    <?php foreach ($generalTopics as $topic): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="icon-box bg-success bg-opacity-10 text-success rounded p-3 me-3 flex-shrink-0">
                                            <i class="<?= htmlspecialchars($topic['icone'] ?? 'fas fa-info-circle') ?> fa-2x"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1"><?= htmlspecialchars($topic['titre'] ?? 'Conseil sans titre') ?></h5>
                                            <?php if (!empty($topic['categorie'])): ?>
                                                <span class="badge bg-light text-dark"><?= htmlspecialchars($topic['categorie']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Général</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted flex-grow-1"><?= htmlspecialchars($topic['resume'] ?? 'Pas de résumé.') ?></p>
                                    <p class="card-text text-muted small flex-grow-1">
                                        <i><?= htmlspecialchars(substr($topic['contenu'] ?? '', 0, 150)) . (strlen($topic['contenu'] ?? '') > 150 ? '...' : '') ?></i>
                                    </p>
                                    <div class="mt-auto">
                                        <a href="conseil_detail.php?id=<?= htmlspecialchars($topic['id']) ?>" class="btn btn-sm btn-outline-success">Lire la suite</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>