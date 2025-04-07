<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireEmployeeLogin();

$conseil_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$conseil_id) {
    flashMessage('ID de conseil invalide.', 'danger');
    redirectTo('advice.php'); // Rediriger vers la liste des conseils
}

$conseil = getConseilDetails($conseil_id);

$pageTitle = $conseil ? $conseil['titre'] : "Conseil introuvable";
$pageDescription = $conseil ? htmlspecialchars(substr($conseil['contenu'], 0, 160)) . '...' : "Le conseil demandé n'a pas pu être trouvé.";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="container py-5">

    <?php if (!$conseil): ?>
        <div class="alert alert-warning">
            Le conseil demandé n'existe pas ou n'est plus disponible.
        </div>
        <a href="advice.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Retour aux conseils</a>
    <?php else: ?>
        <!-- Affichage du détail du conseil -->
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <a href="advice.php" class="btn btn-outline-secondary btn-sm mb-4">
                    <i class="fas fa-arrow-left me-1"></i> Retour à la liste des conseils
                </a>

                <article class="card shadow-sm">
                    <?php if (!empty($conseil['image_url'])):
                    ?>
                        <img src="<?= htmlspecialchars($conseil['image_url']) ?>" class="card-img-top advice-detail-img" alt="<?= htmlspecialchars($conseil['titre']) ?>">
                    <?php endif; ?>

                    <div class="card-body">
                        <h1 class="card-title display-6 mb-3"><?= htmlspecialchars($conseil['titre']) ?></h1>

                        <div class="mb-4 text-muted border-bottom pb-3">
                            <?php if (!empty($conseil['categorie'])):
                            ?>
                                <span class="badge bg-primary me-2"><?= htmlspecialchars(ucfirst($conseil['categorie'])) ?></span>
                            <?php endif; ?>
                            Publié le <?= htmlspecialchars($conseil['date_publication_formatee']) ?>
                            <?php
                            $auteur = "Auteur inconnu";
                            if (!empty($conseil['auteur_prenom_personne'])) {
                                $auteur = htmlspecialchars($conseil['auteur_prenom_personne'] . ' ' . $conseil['auteur_nom_personne']);
                            } elseif (!empty($conseil['auteur_nom_personne'])) { // Cas où seul le nom est dispo (après notre logique dans getConseilDetails)
                                $auteur = htmlspecialchars($conseil['auteur_nom_personne']);
                            } elseif (!empty($conseil['auteur_nom'])) { // Fallback sur auteur_nom si pas de personne liée
                                $auteur = htmlspecialchars($conseil['auteur_nom']);
                            }
                            ?>
                            par <?= $auteur ?>
                        </div>

                        <div class="advice-content">
                            <?= nl2br(htmlspecialchars($conseil['contenu'])) // nl2br pour conserver les sauts de ligne, htmlspecialchars pour la sécurité 
                            ?>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="advice.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Retour à la liste des conseils
                        </a>
                    </div>
                </article>
            </div>
        </div>
    <?php endif; ?>

</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>