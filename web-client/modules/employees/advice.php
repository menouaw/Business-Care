<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireRole(ROLE_SALARIE);

$userId = $_SESSION['user_id'];

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_STRING) ?: null;
$searchTerm = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?: null;
$limit = 6;
if (!function_exists('getConseils')) {
    die("Erreur: La fonction getConseils n'est pas définie. Vérifiez le fichier includes/page_functions/modules/employees.php");
}
if (!function_exists('getConseilCategories')) {
    die("Erreur: La fonction getConseilCategories n'est pas définie. Vérifiez le fichier includes/page_functions/modules/employees.php");
}


$conseilsData = getConseils($page, $limit, $category, $searchTerm);
$conseils = $conseilsData['conseils'];
$paginationHtml = $conseilsData['pagination_html'];
$totalConseils = $conseilsData['pagination']['totalItems'];

$categories = getConseilCategories();

$pageTitle = "Conseils Bien-être";
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

    <?php
    // DEBUG: Afficher les données de pagination
    // var_dump($conseilsData['pagination']); 
    ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="search" class="form-label">Rechercher un conseil</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($searchTerm ?? '') ?>" placeholder="Mot-clé, titre...">
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
            <?php if ($searchTerm || $category): ?>
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
                                <!-- Bouton pour ouvrir la modale -->
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#adviceModal"
                                    data-conseil-titre="<?= htmlspecialchars($conseil['titre']) ?>"
                                    data-conseil-contenu="<?= htmlspecialchars($conseil['contenu']) // Attention: peut être long, préférer un appel AJAX si contenu très lourd 
                                                            ?>"
                                    data-conseil-image="<?= htmlspecialchars($conseil['image_url'] ?? '') ?>"
                                    data-conseil-categorie="<?= htmlspecialchars($conseil['categorie'] ?? '') ?>"
                                    data-conseil-date="<?= $conseil['date_publication_formatee'] ?>"
                                    data-conseil-auteur="<?= !empty($conseil['auteur_nom_personne']) ? htmlspecialchars($conseil['auteur_prenom_personne'] . ' ' . $conseil['auteur_nom_personne']) : htmlspecialchars($conseil['auteur_nom'] ?? '') ?>">
                                    Lire la suite <i class="fas fa-arrow-right ms-1"></i>
                                </button>
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

<!-- Modal pour afficher le conseil -->
<div class="modal fade" id="adviceModal" tabindex="-1" aria-labelledby="adviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adviceModalLabel">Détail du Conseil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <img src="" id="adviceModalImage" class="img-fluid rounded mb-3" alt="Image du conseil" style="display: none; max-height: 300px; width: 100%; object-fit: cover;">
                <h3 id="adviceModalTitle" class="mb-3"></h3>
                <div class="mb-3">
                    <span id="adviceModalCategory" class="badge bg-primary me-2"></span>
                    <span class="text-muted small">Publié le <span id="adviceModalDate"></span> par <span id="adviceModalAuthor"></span></span>
                </div>
                <div id="adviceModalContent">
                    <!-- Le contenu complet du conseil sera injecté ici -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>


<?php
include_once __DIR__ . '/../../templates/footer.php';
?>

<style>
    /* Optionnel: Ajoute un petit effet au survol des cartes */
    .conseil-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    /* Réduire les espacements internes pour des cartes plus compactes */
    .conseil-card .card-body {
        padding: 0.8rem;
        /* Réduire le padding général du corps */
    }

    .conseil-card .card-title {
        font-size: 1rem;
        /* Réduire taille du titre */
        margin-bottom: 0.4rem;
        /* Réduire marge sous le titre */
    }

    .conseil-card .card-text {
        font-size: 0.85rem;
        /* Réduire taille du texte */
        margin-bottom: 0.6rem;
        /* Ajuster marge sous le texte */
    }

    .conseil-card .badge {
        font-size: 0.7rem;
        /* Réduire taille du badge catégorie */
        margin-bottom: 0.5rem !important;
    }

    .conseil-card .btn {
        padding: 0.25rem 0.5rem;
        /* Rendre le bouton plus petit */
        font-size: 0.8rem;
    }

    .conseil-card .card-footer {
        padding: 0.5rem 0.8rem;
        /* Réduire padding du pied de carte */
        font-size: 0.75rem;
        /* Réduire taille texte pied de carte */
    }

    /* Optionnel: réduire légèrement la hauteur de l'image */
    .conseil-card .card-img-top {
        height: 180px;
        /* Ex: passer de 200px à 180px */
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var adviceModal = document.getElementById('adviceModal');
        adviceModal.addEventListener('show.bs.modal', function(event) {
            // Bouton qui a déclenché la modale
            var button = event.relatedTarget;

            // Extraction des informations depuis les attributs data-*
            var titre = button.getAttribute('data-conseil-titre');
            var contenu = button.getAttribute('data-conseil-contenu'); // Récupère le contenu HTML-escapé
            var image = button.getAttribute('data-conseil-image');
            var categorie = button.getAttribute('data-conseil-categorie');
            var date = button.getAttribute('data-conseil-date');
            var auteur = button.getAttribute('data-conseil-auteur');

            // Récupération des éléments de la modale
            var modalTitle = adviceModal.querySelector('#adviceModalLabel'); // Titre en haut
            var modalBodyTitle = adviceModal.querySelector('#adviceModalTitle'); // Titre dans le corps
            var modalContent = adviceModal.querySelector('#adviceModalContent');
            var modalImage = adviceModal.querySelector('#adviceModalImage');
            var modalCategory = adviceModal.querySelector('#adviceModalCategory');
            var modalDate = adviceModal.querySelector('#adviceModalDate');
            var modalAuthor = adviceModal.querySelector('#adviceModalAuthor');

            // Mise à jour du contenu de la modale
            modalTitle.textContent = titre;
            modalBodyTitle.textContent = titre;
            modalContent.innerHTML = contenu.replace(/\n/g, '<br>'); // Remplace les sauts de ligne par <br> pour l'affichage HTML

            // Gestion de l'image
            if (image) {
                modalImage.src = image;
                modalImage.style.display = 'block';
                modalImage.alt = titre; // Ajouter un alt descriptif
            } else {
                modalImage.style.display = 'none';
            }

            // Gestion de la catégorie
            if (categorie) {
                modalCategory.textContent = categorie.charAt(0).toUpperCase() + categorie.slice(1); // Met la première lettre en majuscule
                modalCategory.style.display = 'inline-block';
            } else {
                modalCategory.style.display = 'none';
            }

            modalDate.textContent = date || 'N/A';
            modalAuthor.textContent = auteur || 'Anonyme';
        });
    });
</script>