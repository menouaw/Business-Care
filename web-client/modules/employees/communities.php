<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

$page_title = "Espace communautaire";
$page_description = "Rejoignez des communautés et participez à des activités avec vos collègues";

// Vérifier si l'utilisateur est connecté et est un salarié
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != ROLE_SALARIE) {
    // Rediriger vers la page de connexion si non connecté ou non salarié
    if (function_exists('flashMessage')) {
        flashMessage("Vous devez être connecté en tant que salarié pour accéder à cette page", "warning");
    }
    header('Location: ' . ROOT_URL . '/common/connexion/');
    exit;
}

// Récupérer l'ID du salarié depuis la session
$employee_id = $_SESSION['user_id'];

// Traitement des paramètres de l'URL
$community_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?: '';
$type_filter = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING) ?: '';

// Traitement des actions POST
handlePostActions($employee_id);

// Inclusion du header
include_once __DIR__ . '/../../templates/header.php';

/**
 * Gère les actions POST (rejoindre/quitter une communauté, ajouter un message)
 * 
 * @param int $employee_id ID du salarié
 * @return void
 */
function handlePostActions($employee_id)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    // Action rejoindre une communauté
    if (isset($_POST['join_community']) && !empty($_POST['community_id'])) {
        $join_community_id = filter_input(INPUT_POST, 'community_id', FILTER_VALIDATE_INT);
        if (joinCommunity($employee_id, $join_community_id)) {
            // Redirection pour éviter le problème de re-soumission du formulaire
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    if (isset($_POST['leave_community']) && !empty($_POST['community_id'])) {
        $leave_community_id = filter_input(INPUT_POST, 'community_id', FILTER_VALIDATE_INT);
        if (leaveCommunity($employee_id, $leave_community_id)) {
            // Redirection pour éviter le problème de re-soumission du formulaire
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    if (isset($_POST['add_message']) && !empty($_POST['message']) && !empty($_POST['community_id'])) {
        $msg_community_id = filter_input(INPUT_POST, 'community_id', FILTER_VALIDATE_INT);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
        if (addCommunityMessage($employee_id, $msg_community_id, $message)) {
            // Redirection pour éviter le problème de re-soumission du formulaire
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

/**
 * Affiche un bouton pour rejoindre ou quitter une communauté
 * 
 * @param int $community_id ID de la communauté
 * @param bool $is_member Si l'utilisateur est déjà membre
 * @param bool $is_detail Si on est dans la vue détaillée d'une communauté
 * @return void
 */
function displayJoinLeaveButton($community_id, $is_member, $is_detail = false)
{
    if ($is_member) {
        $btn_class = $is_detail ? 'btn-outline-danger' : 'btn-sm btn-outline-danger';
        $icon = $is_detail ? 'fa-sign-out-alt me-2' : 'fa-sign-out-alt me-1';
        $text = $is_detail ? 'Quitter cette communauté' : 'Quitter';
    } else {
        $btn_class = $is_detail ? 'btn-success' : 'btn-sm btn-success';
        $icon = $is_detail ? 'fa-plus-circle me-2' : 'fa-plus-circle me-1';
        $text = $is_detail ? 'Rejoindre cette communauté' : 'Rejoindre';
    }
?>
    <form method="post" class="d-inline">
        <input type="hidden" name="community_id" value="<?= $community_id ?>">
        <button type="submit" name="<?= $is_member ? 'leave_community' : 'join_community' ?>" class="btn <?= $btn_class ?>">
            <i class="fas <?= $icon ?>"></i><?= $text ?>
        </button>
    </form>
<?php
}

/**
 * Affiche l'avatar d'un utilisateur
 * 
 * @param string|null $photo_url URL de la photo de profil
 * @param int $size Taille de l'avatar
 * @return void
 */
function displayAvatar($photo_url, $size = 30)
{
    $avatar_url = !empty($photo_url)
        ? $photo_url
        : ROOT_URL . '/assets/images/avatar-placeholder.png';
?>
    <img src="<?= $avatar_url ?>" class="rounded-circle me-2" width="<?= $size ?>" height="<?= $size ?>" alt="Avatar">
<?php
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        <?= $page_title ?>
                    </h4>
                    <p class="mb-0 small"><?= $page_description ?></p>
                </div>
                <div class="card-body">
                    <?php
                    // Afficher les messages flash s'il y en a
                    if (function_exists('displayFlashMessages')) {
                        displayFlashMessages();
                    }

                    // Détail d'une communauté spécifique
                    if ($community_id) {
                        displayCommunityDetail($community_id, $employee_id);
                    } else {
                        displayCommunitiesList($employee_id, $page, $search, $type_filter);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Affiche les détails d'une communauté
 * 
 * @param int $community_id ID de la communauté
 * @param int $employee_id ID du salarié
 * @return void
 */
function displayCommunityDetail($community_id, $employee_id)
{
    // Récupérer les détails de la communauté
    $community = getCommunityDetails($community_id, $employee_id);

    if (!$community) {
        echo '<div class="alert alert-danger">La communauté demandée n\'existe pas ou a été supprimée.</div>';
        return;
    }
?>
    <div class="row">
        <div class="col-md-8">
            <h2 class="h4"><?= htmlspecialchars($community['nom']) ?></h2>
            <span class="badge <?= $community['type_class'] ?> mb-3">
                <?= htmlspecialchars(ucfirst($community['type'])) ?>
            </span>

            <p class="lead">
                <?= nl2br(htmlspecialchars($community['description'])) ?>
            </p>

            <div class="mb-4">
                <p>
                    <i class="fas fa-users me-2"></i>
                    <strong><?= $community['nombre_membres'] ?></strong> membres
                    <?php if (!empty($community['capacite_max'])): ?>
                        (capacité max: <?= $community['capacite_max'] ?>)
                    <?php endif; ?>
                </p>

                <?php displayJoinLeaveButton($community_id, $community['est_membre'], true); ?>

                <a href="?page=1" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                </a>
            </div>

            <h3 class="h5 mt-4 mb-3">Messages de la communauté</h3>

            <?php displayCommunityMessages($community_id, $community['est_membre']); ?>
        </div>

        <div class="col-md-4">
            <?php
            displayCommunityEvents($community);
            displayCommunityMembers($community_id);
            ?>
        </div>
    </div>
    <?php
}

/**
 * Affiche les messages d'une communauté avec pagination
 * 
 * @param int $community_id ID de la communauté
 * @param bool $is_member Si l'utilisateur est membre
 * @return void
 */
function displayCommunityMessages($community_id, $is_member)
{
    // Récupérer les messages avec pagination
    $messages_page = filter_input(INPUT_GET, 'messages_page', FILTER_VALIDATE_INT) ?: 1;
    $messages_result = getCommunityMessages($community_id, $messages_page);

    if (empty($messages_result['messages'])) {
        echo '<div class="alert alert-info">Aucun message dans cette communauté pour le moment.</div>';
    } else {
        foreach ($messages_result['messages'] as $message) {
    ?>
            <div class="card mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <?php displayAvatar($message['photo_url']); ?>
                        <strong>
                            <?= htmlspecialchars($message['auteur_prenom'] . ' ' . $message['auteur_nom']) ?>
                        </strong>
                        <?= $message['role_badge'] ?>
                    </div>
                    <small class="text-muted">
                        <?= $message['date_creation_formatted'] ?>
                    </small>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        <?= nl2br(htmlspecialchars($message['contenu'])) ?>
                    </p>
                </div>
            </div>
        <?php
        }

        // Afficher la pagination pour les messages
        echo $messages_result['pagination_html'];
    }

    // Formulaire pour ajouter un message (seulement pour les membres)
    if ($is_member) {
        ?>
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h4 class="h6 mb-0">Publier un message</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="community_id" value="<?= $community_id ?>">
                    <div class="form-group mb-3">
                        <textarea name="message" class="form-control" rows="3" placeholder="Partagez votre message avec la communauté..." required></textarea>
                        <small class="form-text text-muted">Les messages sont soumis à une modération automatique.</small>
                    </div>
                    <button type="submit" name="add_message" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Publier
                    </button>
                </form>
            </div>
        </div>
    <?php
    } else {
        echo '<div class="alert alert-info mt-4">Rejoignez cette communauté pour pouvoir publier des messages.</div>';
    }
}

/**
 * Affiche les événements à venir d'une communauté
 * 
 * @param array $community Détails de la communauté
 * @return void
 */
function displayCommunityEvents($community)
{
    ?>
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h4 class="h6 mb-0">Événements à venir</h4>
        </div>
        <div class="card-body">
            <?php
            if (empty($community['evenements'])) {
                echo '<p class="text-muted">Aucun événement à venir.</p>';
            } else {
                echo '<ul class="list-group list-group-flush">';
                foreach ($community['evenements'] as $event) {
            ?>
                    <li class="list-group-item">
                        <h5 class="h6"><?= htmlspecialchars($event['titre']) ?></h5>
                        <p class="small text-muted mb-1">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?= $event['date_debut_formatted'] ?>
                        </p>
                        <p class="small text-muted mb-0">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?= htmlspecialchars($event['lieu']) ?>
                        </p>
                    </li>
            <?php
                }
                echo '</ul>';
            }
            ?>
        </div>
    </div>
<?php
}

/**
 * Affiche les membres d'une communauté avec pagination
 * 
 * @param int $community_id ID de la communauté
 * @return void
 */
function displayCommunityMembers($community_id)
{
?>
    <div class="card">
        <div class="card-header bg-light">
            <h4 class="h6 mb-0">Membres de la communauté</h4>
        </div>
        <div class="card-body">
            <?php
            // Récupérer les membres avec pagination
            $members_page = filter_input(INPUT_GET, 'members_page', FILTER_VALIDATE_INT) ?: 1;
            $members_result = getCommunityMembers($community_id, $members_page, 5);

            if (empty($members_result['members'])) {
                echo '<p class="text-muted">Aucun membre pour le moment.</p>';
            } else {
                echo '<ul class="list-group list-group-flush">';
                foreach ($members_result['members'] as $member) {
            ?>
                    <li class="list-group-item d-flex align-items-center">
                        <?php displayAvatar($member['photo_url'], 36); ?>
                        <div>
                            <p class="mb-0">
                                <?= htmlspecialchars($member['prenom'] . ' ' . $member['nom']) ?>
                                <?= $member['role_badge'] ?>
                            </p>
                            <small class="text-muted">
                                Depuis le <?= $member['date_inscription_formatted'] ?>
                            </small>
                        </div>
                    </li>
            <?php
                }
                echo '</ul>';

                // Afficher la pagination pour les membres
                echo $members_result['pagination_html'];
            }
            ?>
        </div>
    </div>
<?php
}

/**
 * Affiche la liste des communautés avec filtres et pagination
 * 
 * @param int 
 * @param int 
 * @param string 
 * @param string 
 * @return void
 */
function displayCommunitiesList($employee_id, $page, $search, $type_filter)
{
?>
    <div class="row mb-4">
        <div class="col-md-8">
            <a href="../../modules/employees/index.php" class="btn btn-secondary mb-3">
                <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
            </a>
            <h3 class="h5 mb-3">Découvrez les communautés</h3>
        </div>
        <div class="col-md-4">
            <form method="get" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
                    <select name="type" class="form-select">
                        <option value="">Tous les types</option>
                        <option value="sport" <?= $type_filter === 'sport' ? 'selected' : '' ?>>Sport</option>
                        <option value="bien_etre" <?= $type_filter === 'bien_etre' ? 'selected' : '' ?>>Bien-être</option>
                        <option value="sante" <?= $type_filter === 'sante' ? 'selected' : '' ?>>Santé</option>
                        <option value="autre" <?= $type_filter === 'autre' ? 'selected' : '' ?>>Autre</option>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Récupérer la liste des communautés avec pagination et filtres
    $communities_result = getCommunities($employee_id, $page, 10, $search, $type_filter);

    if (empty($communities_result['communities'])) {
        echo '<div class="alert alert-info">Aucune communauté ne correspond à votre recherche.</div>';
        return;
    }

    echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">';

    foreach ($communities_result['communities'] as $community) {
        displayCommunityCard($community);
    }

    echo '</div>';

    // Afficher la pagination
    echo '<div class="mt-4">';
    echo $communities_result['pagination_html'];
    echo '</div>';
}

/**
 * Affiche une carte pour une communauté
 * 
 * @param array $community Détails de la communauté
 * @return void
 */

function displayCommunityCard($community)
{
    ?>
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-header <?= $community['type_class'] ?> text-white">
                <h5 class="mb-0"><?= htmlspecialchars($community['nom']) ?></h5>
            </div>
            <div class="card-body">
                <p class="card-text">
                    <?php
                    $description = $community['description'] ?? '';
                    echo strlen($description) > 100 ?
                        htmlspecialchars(substr($description, 0, 100)) . '...' :
                        htmlspecialchars($description);
                    ?>
                </p>
                <p class="text-muted small">
                    <i class="fas fa-users me-1"></i> <?= $community['nombre_membres'] ?> membres
                    <?php if (!empty($community['capacite_max'])): ?>
                        (max: <?= $community['capacite_max'] ?>)
                    <?php endif; ?>
                </p>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="?id=<?= $community['id'] ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-info-circle me-1"></i>Détails
                </a>

                <?php displayJoinLeaveButton($community['id'], $community['est_membre']); ?>
            </div>
        </div>
    </div>
<?php
}

include_once __DIR__ . '/../../templates/footer.php';
?>