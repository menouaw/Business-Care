<?php

require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

$community_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$current_employee_id = $_SESSION['user_id'] ?? null;
$csrfToken = $_SESSION['csrf_token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $posted_community_id = isset($_POST['community_id']) ? filter_var($_POST['community_id'], FILTER_VALIDATE_INT) : null;
    $submitted_csrf_token = $_POST['csrf_token'] ?? null;

    $redirectUrl = WEBCLIENT_URL . '/modules/employees/communities.php';
    if ($posted_community_id) {
        $redirectUrl = WEBCLIENT_URL . '/modules/employees/communities.php?id=' . $posted_community_id;
    }

    if (!validateToken($submitted_csrf_token)) {
        logSecurityEvent($current_employee_id, 'csrf_failure', '[SECURITY FAILURE] Tentative POST avec jeton invalide sur communities.php');
        flashMessage("Erreur de sécurité (jeton invalide).", "danger");
    } else {
        requireRole(ROLE_SALARIE);

        switch ($action) {
            case 'join_community':
                handleJoinCommunityRequest($current_employee_id, $posted_community_id);
                break;

            case 'post_message':
                if ($posted_community_id) {
                    $message_content = $_POST['message'] ?? null;
                    handleNewCommunityPost($posted_community_id, $current_employee_id, $message_content);
                } else {
                    flashMessage("ID de communauté manquant pour poster un message.", "danger");
                }
                break;

            default:
                flashMessage("Action invalide ou données manquantes.", "danger");
                break;
        }
    }

    redirectTo($redirectUrl);
    exit;
}


$pageTitle = "Communautés - Espace Salarié";

$communityData = null;
$posts = [];
$communitiesList = [];
$viewMode = 'list';

if ($community_id) {
    $pageData = displayCommunityDetailsPageData($community_id);
    $communityData = $pageData['community'] ?? null;

    if ($communityData) {
        $posts = $pageData['posts'] ?? [];
        $pageTitle = "Communauté : " . htmlspecialchars($communityData['nom']);
        $viewMode = 'detail';
    } else {

        if (!headers_sent()) {

            flashMessage("Communauté non trouvée.", "warning");
            redirectTo(WEBCLIENT_URL . '/modules/employees/communities.php');
            exit;
        }
        $pageTitle = "Erreur Communauté";
        $viewMode = 'error';
    }
} else {
    $pageData = displayEmployeeCommunitiesPage();
    $communitiesList = $pageData['communities'] ?? [];
    $viewMode = 'list';
}


include_once __DIR__ . '/../../templates/header.php';

?>

<main class="employee-communities-page py-4">
    <div class="container">

        <?php echo displayFlashMessages(); ?>

        <?php if ($viewMode === 'detail'): ?>
            <?php
            ?>
            <div class="row mb-4 align-items-center">
                <div class="col">
                    <h1 class="h2 mb-0"><i class="<?= getCommunityIcon($communityData['type'] ?? 'autre') ?> me-2"></i><?= htmlspecialchars($communityData['nom']) ?></h1>
                    <p class="text-muted mb-0"><?= htmlspecialchars($communityData['description'] ?? 'Pas de description.') ?></p>
                </div>
                <div class="col-auto">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4"> 
                <div class="card-header bg-white">
                    <h5 class="mb-0">Poster un message</h5>
                </div>
                <div class="card-body">
                    <form action="<?= WEBCLIENT_URL ?>/modules/employees/communities.php?id=<?= $community_id ?>" method="POST">
                        <input type="hidden" name="community_id" value="<?= $community_id ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="post_message">
                        <div class="mb-3">
                            <label for="messageContent" class="form-label">Votre message :</label>
                            <textarea class="form-control" id="messageContent" name="message" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i> Envoyer</button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Messages</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($posts)): ?>
                        <p class="text-center text-muted">Aucun message dans cette communauté pour le moment. Soyez le premier !</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($posts as $post): ?>
                                <div class="list-group-item border-0 px-0 py-3">
                                    <div class="d-flex w-100 justify-content-between mb-1">
                                        <h6 class="mb-1"><i class="fas fa-user me-2"></i><?= htmlspecialchars($post['auteur_nom'] ?? 'Auteur inconnu') ?></h6>
                                        <small class="text-muted" title="<?= htmlspecialchars($post['created_at'] ?? '') ?>"><?= htmlspecialchars($post['created_at_formatted'] ?? 'Date inconnue') ?></small>
                                    </div>
                                    <p class="mb-1 ms-4"><?= nl2br(htmlspecialchars($post['message'] ?? '')) ?></p>
                                    <?php if (($post['personne_id'] ?? null) === $current_employee_id): ?>

                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($viewMode === 'list'): ?>
            <?php
            ?>
            <div class="row mb-4 align-items-center">
                <div class="col">
                    <h1 class="h2"><?= $pageTitle ?></h1>
                    <p class="text-muted">Rejoignez des groupes, partagez vos passions et organisez des événements.</p>
                </div>
                <div class="col-auto">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour
                    </a>
                </div>
            </div>

            <?php if (empty($communitiesList)) : ?>
                <div class="alert alert-info text-center" role="alert">
                    Aucune communauté n'est disponible pour le moment.
                </div>
            <?php else : ?>
                <div class="row g-4">
                    <?php foreach ($communitiesList as $community) : ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="icon-box bg-info bg-opacity-10 text-info rounded p-3 me-3 flex-shrink-0">
                                            <i class="<?= getCommunityIcon($community['type'] ?? 'autre') ?> fa-2x"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1"><?= htmlspecialchars($community['nom'] ?? 'Communauté sans nom') ?></h5>
                                            <span class="badge bg-secondary me-1"><?= htmlspecialchars(ucfirst($community['type'] ?? 'Autre')) ?></span>
                                            <?php if (!empty($community['niveau'])) : ?>
                                                <span class="badge bg-light text-dark"><?= htmlspecialchars(ucfirst($community['niveau'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="card-text text-muted flex-grow-1"><?= nl2br(htmlspecialchars($community['description'] ?? 'Pas de description.')) ?></p>
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <!-- Option 1: Link to detail page -->
                                        <a href="<?= WEBCLIENT_URL ?>/modules/employees/communities.php?id=<?= $community['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            Voir / Rejoindre
                                        </a>
                                        <?php if (!empty($community['capacite_max'])) : ?>
                                            <small class="text-muted"><i class="fas fa-users me-1"></i> <?= htmlspecialchars($community['capacite_max']) ?> max</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else:
        ?>
            <div class="row mb-4 align-items-center">
                <div class="col">
                    <h1 class="h2"><?= $pageTitle ?></h1>
                </div>
                <div class="col-auto">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour
                    </a>
                </div>
            </div>
            <div class="alert alert-danger text-center" role="alert">
                La communauté demandée n'a pas pu être chargée ou n'existe pas.
            </div>
            <div class="text-center">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/communities.php" class="btn btn-primary">
                    Retour à la liste des communautés
                </a>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>