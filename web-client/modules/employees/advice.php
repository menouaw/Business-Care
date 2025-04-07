<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireEmployeeLogin();

$employee_id = $_SESSION['user_id'];
$pageTitle = generatePageTitle('Conseils Bien-être');

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
$category = filter_input(INPUT_GET, 'category');
$search = filter_input(INPUT_GET, 'search');
$limit = 6;
if (!function_exists('getConseils')) {
    die("Erreur: La fonction getConseils n'est pas définie. Vérifiez le fichier includes/page_functions/modules/employees.php");
}
if (!function_exists('getConseilCategories')) {
    die("Erreur: La fonction getConseilCategories n'est pas définie. Vérifiez le fichier includes/page_functions/modules/employees.php");
}


$conseilsData = getConseils($page, $limit, $category, $search);
$conseils = $conseilsData['conseils'];
$paginationHtml = $conseilsData['pagination_html'];
$totalConseils = $conseilsData['pagination']['totalItems'];

$categories = getConseilCategories();

$pageDescription = "Retrouvez tous nos conseils pour améliorer votre bien-être au quotidien et au travail.";

include_once __DIR__ . '/../../templates/header.php';

?>

<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="display-5 mb-1"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="lead text-muted"><?= htmlspecialchars($pageDescription) ?></p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
        </a>
    </div>

    <?php displayFlashMessages(); ?>



    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="search" class="form-label">Rechercher un conseil</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Mot-clé, titre...">
                </div>
                <div class="col-md-5">
                    <label for="category" class="form-label">Filtrer par catégorie</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">Toutes les catégories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($category === $cat) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($cat)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Affichage des conseils -->
    <?php if (empty($conseils)): ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fas fa-info-circle fa-3x mb-3"></i><br>
            <?php if ($search || $category): ?>
                Aucun conseil ne correspond à vos critères de recherche ou de filtre.
            <?php else: ?>
                Aucun conseil n'est disponible pour le moment. Revenez bientôt !
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="text-muted mb-3"><?= $totalConseils ?> conseil(s) trouvé(s).</p>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($conseils as $conseil): ?>
                <div class="col">
                    <div class="card shadow-sm border-0 conseil-card">
                        <?php if (!empty($conseil['image_url'])): ?>
                            <img src="<?= htmlspecialchars($conseil['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($conseil['titre']) ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-heartbeat fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($conseil['titre']) ?></h5>
                            <?php if (!empty($conseil['categorie'])): ?>
                                <span class="badge bg-primary bg-opacity-75 mb-2 align-self-start"><?= htmlspecialchars(ucfirst($conseil['categorie'])) ?></span>
                            <?php endif; ?>
                            <p class="card-text small text-muted flex-grow-1">
                                <?= htmlspecialchars(substr($conseil['resume'] ?? $conseil['contenu'], 0, 150)) ?>...
                            </p>
                            <div class="mt-auto">
                                <!-- Modifier le bouton en lien vers la page de détails -->
                                <a href="advice_detail.php?id=<?= $conseil['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    Lire la suite <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 text-muted small">
                            Publié le <?= $conseil['date_publication_formatee'] ?>
                            <?php if (!empty($conseil['auteur_nom_personne'])): ?>
                                par <?= htmlspecialchars($conseil['auteur_prenom_personne'] . ' ' . $conseil['auteur_nom_personne']) ?>
                            <?php elseif (!empty($conseil['auteur_nom'])): ?>
                                par <?= htmlspecialchars($conseil['auteur_nom']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($paginationHtml): ?>
            <div class="d-flex justify-content-center mt-5">
                <?= $paginationHtml ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>