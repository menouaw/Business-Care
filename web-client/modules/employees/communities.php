<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

$community_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$current_employee_id = $_SESSION['user_id'] ?? null;
$csrfToken = $_SESSION['csrf_token'] ?? '';

// Handle POST requests using the dedicated function
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleCommunityPostRequest($_POST, $current_employee_id, $csrfToken);
    // The handleCommunityPostRequest function includes exit() after redirection
}

$pageTitle = "Communautés - Espace Salarié";

$communityData = null;
$posts = [];
$recommendedCommunities = [];
$otherCommunities = [];
$memberCommunityIds = [];
$viewMode = 'list'; // Default view mode
$dbError = null;    // Pour afficher un message si erreur DB

if ($community_id) {
    // Attempt to load community details
    $pageData = displayCommunityDetailsPageData($community_id);
    $communityData = $pageData['community'] ?? null;

    if ($communityData) {
        $posts = $pageData['posts'] ?? [];
        $pageTitle = "Communauté : " . htmlspecialchars($communityData['nom']);
        $viewMode = 'detail';
        $memberCommunityIds = $pageData['memberCommunityIds'] ?? [];
    } else {
        // Community not found or error loading
        if (!headers_sent()) {
            // Only redirect if headers haven't been sent
            flashMessage("Communauté non trouvée.", "warning");
            redirectTo(WEBCLIENT_URL . '/modules/employees/communities.php');
            exit;
        }
        // If headers sent, just set error state for display
        $pageTitle = "Erreur Communauté";
        $viewMode = 'error';
    }
} else {
    // Load the list of all communities
    $pageData = displayEmployeeCommunitiesPage();
    $recommendedCommunities = $pageData['recommendedCommunities'] ?? [];
    $otherCommunities = $pageData['otherCommunities'] ?? [];
    $memberCommunityIds = $pageData['memberCommunityIds'] ?? [];
    $dbError = $pageData['dbError'] ?? null;    // Récupérer message d'erreur DB éventuel
    $viewMode = 'list';
}


include_once __DIR__ . '/../../templates/header.php';

?>

<main class="employee-communities-page py-4">
    <div class="container">

        <?php echo displayFlashMessages(); ?>

        <?php if ($viewMode === 'detail'): ?>
            <div class="row mb-5 align-items-center"> <!-- Increased mb -->
                <div class="col-md mb-3 mb-md-0"> <!-- Responsive margin bottom -->
                    <h1 class="h2 mb-1"><i class="<?= getCommunityIcon($communityData['type'] ?? 'autre') ?> me-2"></i><?= htmlspecialchars($communityData['nom']) ?></h1>
                    <p class="text-muted mb-0"><?= htmlspecialchars($communityData['description'] ?? 'Pas de description.') ?></p>
                </div>
                <div class="col-md-auto d-flex gap-2"> <!-- Use flex and gap for buttons -->
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/communities.php" class="btn btn-outline-secondary">
                        <i class="fas fa-chevron-left me-1"></i> Retour à la liste
                    </a>
                    <?php
                    $isMemberOfThisCommunity = $community_id && !empty($memberCommunityIds) && in_array($community_id, $memberCommunityIds);
                    if ($isMemberOfThisCommunity):
                    ?>
                        <form action="<?= WEBCLIENT_URL ?>/modules/employees/communities.php" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir quitter cette communauté ?');">
                            <input type="hidden" name="community_id" value="<?= $community_id ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="action" value="leave_community">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt me-1"></i> Quitter
                            </button>
                        </form>
                    <?php else: // If not a member, show Join button 
                    ?>
                        <form action="<?= WEBCLIENT_URL ?>/modules/employees/communities.php" method="POST">
                            <input type="hidden" name="community_id" value="<?= $community_id ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="action" value="join_community">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i> Rejoindre cette communauté
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Post message form -->
            <?php if ($isMemberOfThisCommunity): // Only show post form if member 
            ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0">Poster un message</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="<?= WEBCLIENT_URL ?>/modules/employees/communities.php?id=<?= $community_id ?>" method="POST">
                            <input type="hidden" name="community_id" value="<?= $community_id ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="action" value="post_message">
                            <div class="mb-3">
                                <label for="messageContent" class="form-label">Votre message :</label>
                                <textarea class="form-control" id="messageContent" name="message" rows="3" required placeholder="Écrivez votre message ici..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i> Envoyer</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light py-3"> <!-- Lighter bg, more padding -->
                    <h5 class="mb-0">Fil de discussion</h5> <!-- Changed title -->
                </div>
                <div class="card-body p-3 p-md-4"> <!-- Responsive padding -->
                    <?php if (empty($posts)): ?>
                        <p class="text-center text-muted py-4">Aucun message dans cette communauté pour le moment. Soyez le premier !</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($posts as $post): ?>
                                <div class="list-group-item px-0 py-3 border-bottom"> <!-- Added border-bottom -->
                                    <div class="d-flex justify-content-between align-items-start w-100"> <!-- Ensured width 100% -->
                                        <div class="me-3 flex-grow-1"> <!-- flex-grow-1 for message content -->
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="fas fa-user text-muted me-2"></i>
                                                <h6 class="mb-0 me-2 fw-bold"><?= htmlspecialchars($post['auteur_nom'] ?? 'Auteur inconnu') ?></h6>
                                                <small class="text-muted" title="<?= htmlspecialchars($post['created_at'] ?? '') ?>">(<?= htmlspecialchars($post['created_at_formatted'] ?? 'Date inconnue') ?>)</small>
                                            </div>
                                            <p class="mb-0 ms-4 ps-1"> <!-- Indent message -->
                                                <?= nl2br(htmlspecialchars($post['message'] ?? '')) ?>
                                            </p>
                                        </div>
                                        <div class="text-nowrap flex-shrink-0"> <!-- flex-shrink-0 for delete button -->
                                            <?php if (($post['personne_id'] ?? null) === $current_employee_id): ?>
                                                <form action="<?= WEBCLIENT_URL ?>/modules/employees/communities.php?id=<?= $community_id ?>" method="post" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="action" value="delete_community_post">
                                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                                    <input type="hidden" name="community_id" value="<?= $community_id ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Supprimer ce message">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($viewMode === 'list'): ?>
            <div class="row mb-5 align-items-center"> <!-- Increased mb -->
                <div class="col">
                    <h1 class="h2"><?= $pageTitle ?></h1>
                    <p class="text-muted">Découvrez les communautés qui pourraient vous intéresser et explorez les autres groupes.</p>
                </div>
                <div class="col-auto">
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-chevron-left me-1"></i> Retour
                    </a>
                </div>
            </div>

            <?php if ($dbError): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($dbError) ?>
                </div>
            <?php else: ?>
                <!-- Section Recommandations -->
                <section class="mb-5">
                    <h3 class="mb-4 fw-light border-bottom pb-2"><i class="fas fa-star text-warning me-2"></i>Recommandations pour vous</h3>

                    <?php if (empty($recommendedCommunities)): ?>
                        <div class="alert alert-light text-center" role="alert">
                            Nous n'avons pas de recommandations spécifiques pour vous pour le moment.
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($recommendedCommunities as $community): ?>
                                <div class="col-md-6 col-lg-4 d-flex align-items-stretch">
                                    <div class="card border-0 shadow-sm h-100 community-card">
                                        <div class="card-body d-flex flex-column p-4">
                                            <div class="d-flex align-items-start mb-3 gap-3"> <!-- Added gap -->
                                                <div class="icon-box bg-primary bg-opacity-10 text-primary rounded p-3 flex-shrink-0"> <!-- Changed color -->
                                                    <i class="<?= getCommunityIcon($community['type'] ?? 'autre') ?> fa-2x"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h5 class="card-title mb-1">
                                                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/communities.php?id=<?= $community['id'] ?>" class="text-decoration-none stretched-link text-dark">
                                                            <?= htmlspecialchars($community['nom'] ?? 'Communauté sans nom') ?>
                                                        </a>
                                                    </h5>
                                                    <span class="badge bg-primary bg-opacity-75 me-1"><?= htmlspecialchars(ucfirst($community['type'] ?? 'Autre')) ?></span> <!-- Changed color -->
                                                    <?php if (!empty($community['niveau'])) : ?>
                                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars(ucfirst($community['niveau'])) ?></span> <!-- Added border -->
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <p class="card-text text-muted small flex-grow-1 mb-3"><?= nl2br(htmlspecialchars(substr($community['description'] ?? 'Pas de description.', 0, 100))) . (strlen($community['description'] ?? '') > 100 ? '...' : '') ?></p>
                                            <div class="mt-auto d-flex justify-content-start align-items-center border-top pt-3">
                                                <a href="<?= WEBCLIENT_URL ?>/modules/employees/communities.php?id=<?= $community['id'] ?>" class="btn btn-sm btn-primary"> <!-- Changed button style -->
                                                    <i class="fas fa-eye me-1"></i> Voir les détails
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Section Autres Communautés -->
                <section>
                    <h3 class="mb-4 fw-light border-bottom pb-2"><i class="fas fa-users text-secondary me-2"></i>Autres Communautés</h3> <!-- Changed color -->
                    <?php if (empty($otherCommunities)): ?>
                        <?php if (empty($recommendedCommunities)): // Si les deux listes sont vides
                        ?>
                            <div class="alert alert-info text-center" role="alert">
                                Aucune communauté n'est disponible pour le moment.
                            </div>
                        <?php else: // Si seulement "autres" est vide
                        ?>
                            <div class="alert alert-light text-center" role="alert">
                                Toutes les communautés disponibles correspondent à vos préférences ou sont listées ci-dessus !
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($otherCommunities as $community): ?>
                                <div class="col-md-6 col-lg-4 d-flex align-items-stretch">
                                    <div class="card border-0 shadow-sm h-100 community-card">
                                        <div class="card-body d-flex flex-column p-4">
                                            <div class="d-flex align-items-start mb-3 gap-3"> <!-- Added gap -->
                                                <div class="icon-box bg-secondary bg-opacity-10 text-secondary rounded p-3 flex-shrink-0"> <!-- Changed color -->
                                                    <i class="<?= getCommunityIcon($community['type'] ?? 'autre') ?> fa-2x"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h5 class="card-title mb-1">
                                                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/communities.php?id=<?= $community['id'] ?>" class="text-decoration-none stretched-link text-dark">
                                                            <?= htmlspecialchars($community['nom'] ?? 'Communauté sans nom') ?>
                                                        </a>
                                                    </h5>
                                                    <span class="badge bg-secondary me-1"><?= htmlspecialchars(ucfirst($community['type'] ?? 'Autre')) ?></span> <!-- Changed color -->
                                                    <?php if (!empty($community['niveau'])) : ?>
                                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars(ucfirst($community['niveau'])) ?></span> <!-- Added border -->
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <p class="card-text text-muted small flex-grow-1 mb-3"><?= nl2br(htmlspecialchars(substr($community['description'] ?? 'Pas de description.', 0, 100))) . (strlen($community['description'] ?? '') > 100 ? '...' : '') ?></p>
                                            <div class="mt-auto d-flex justify-content-start align-items-center border-top pt-3">
                                                <a href="<?= WEBCLIENT_URL ?>/modules/employees/communities.php?id=<?= $community['id'] ?>" class="btn btn-sm btn-outline-secondary"> <!-- Changed button style -->
                                                    <i class="fas fa-eye me-1"></i> Voir les détails
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; // Fin $dbError check
            ?>
        <?php else: // Cas $viewMode === 'error' ou autre
        ?>
            <div class="d-flex flex-column align-items-center justify-content-center min-vh-50"> <!-- Centering content -->
                <div class="row mb-4 align-items-center w-100">
                    <div class="col">
                        <h1 class="h2 text-danger"><?= $pageTitle ?></h1> <!-- Error color -->
                    </div>
                    <div class="col-auto">
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="alert alert-danger text-center w-75" role="alert"> <!-- Width 75% -->
                    <i class="fas fa-exclamation-triangle me-2"></i>La communauté demandée n'a pas pu être chargée ou n'existe pas.
                </div>
                <div class="text-center mt-3">
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/communities.php" class="btn btn-primary">
                        <i class="fas fa-list me-1"></i> Retour à la liste des communautés
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>