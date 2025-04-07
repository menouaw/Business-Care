<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireEmployeeLogin();

$page_title = "Espace communautaire";
$page_description = "Rejoignez des communautés et participez à des activités avec vos collègues";

$employee_id = $_SESSION['user_id'];

$community_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$search = filter_input(INPUT_GET, 'search') ?: '';
$type_filter = filter_input(INPUT_GET, 'type') ?: '';

handlePostActions($employee_id);

include_once __DIR__ . '/../../templates/header.php';

?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        <?= htmlspecialchars($page_title) ?>
                    </h4>
                    <p class="mb-0 small"><?= htmlspecialchars($page_description) ?></p>
                </div>
                <div class="card-body">
                    <?php
                    if (function_exists('displayFlashMessages')) {
                        displayFlashMessages();
                    }

                    // On affiche le bouton seulement si on est sur la liste des communautés
                    if (!$community_id) : ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <!-- Titre ou autre contenu -->
                            </div>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php
                    if ($community_id) {
                        $community = getCommunityDetails($community_id, $employee_id);

                        if (!$community) {
                            echo '<div class="alert alert-warning">Communauté non trouvée ou ID invalide.</div>';
                            echo '<a href="communities.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Retour à la liste</a>';
                        } else {
                            $messages_page = filter_input(INPUT_GET, 'messages_page', FILTER_VALIDATE_INT) ?: 1;
                            $messages_limit = 5; // Nombre de messages par page
                            $messagesData = getCommunityMessages($community_id, $messages_page, $messages_limit);
                            $messages = $messagesData['messages'];
                            $messages_pagination_html = $messagesData['pagination_html'];

                            $membersData = getCommunityMembers($community_id, 1, 6); // Aperçu des 6 premiers membres
                            $members = $membersData['members'];

                            $logo_url = !empty($community['logo_url'])
                                ? ROOT_URL . $community['logo_url']
                                : ROOT_URL . '/assets/images/default_community.png';

                    ?>
                            <div class="row">
                                <div class="col-lg-8 mb-4 mb-lg-0">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="<?= htmlspecialchars($logo_url) ?>" alt="Logo <?= htmlspecialchars($community['nom']) ?>" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                        <div>
                                            <h2 class="mb-0 display-6"><?= htmlspecialchars($community['nom']) ?></h2>
                                            <span class="badge <?= $community['type_class'] ?> me-2"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $community['type']))) ?></span>
                                            <span class="text-muted small"><i class="fas fa-users me-1"></i><?= $community['nombre_membres'] ?> membre(s)</span>
                                        </div>
                                        <div class="ms-auto">
                                            <a href="communities.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Liste</a>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-3"><?= nl2br(htmlspecialchars($community['description'] ?? 'Aucune description.')) ?></p>

                                    <form method="post" action="communities.php?id=<?= $community_id ?>" class="mb-4">
                                        <input type="hidden" name="community_id" value="<?= $community_id ?>">
                                        <?php if ($community['est_membre']) : ?>
                                            <button type="submit" name="leave_community" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt me-1"></i> Quitter</button>
                                        <?php else : ?>
                                            <button type="submit" name="join_community" class="btn btn-sm btn-success"><i class="fas fa-user-plus me-1"></i> Rejoindre</button>
                                        <?php endif; ?>
                                    </form>

                                    <hr>
                                    <h4 class="mb-3">Mur de la communauté</h4>

                                    <?php if ($community['est_membre']) : ?>
                                        <form method="post" action="communities.php?id=<?= $community_id ?>" class="mb-3 p-3 bg-light rounded">
                                            <input type="hidden" name="community_id" value="<?= $community_id ?>">
                                            <div class="mb-2">
                                                <label for="community_message" class="form-label small">Votre message :</label>
                                                <textarea id="community_message" name="message" class="form-control form-control-sm" rows="3" placeholder="Partagez quelque chose..." required></textarea>
                                            </div>
                                            <button type="submit" name="add_message" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane me-1"></i> Publier</button>
                                        </form>
                                    <?php else : ?>
                                        <div class="alert alert-info small" role="alert">
                                            <i class="fas fa-info-circle me-1"></i> Vous devez être membre pour publier un message.
                                        </div>
                                    <?php endif; ?>

                                    <h5>Messages récents</h5>
                                    <div class="list-group list-group-flush mb-3">
                                        <?php if (empty($messages)) : ?>
                                            <p class="text-muted small mt-2">Aucun message pour le moment.</p>
                                        <?php else : ?>
                                            <?php foreach ($messages as $message) :
                                                $avatar_url = !empty($message['photo_url'])
                                                    ? ROOT_URL . $message['photo_url']
                                                    : ROOT_URL . '/assets/images/default_avatar.png';
                                            ?>
                                                <div class="list-group-item px-0 py-3">
                                                    <div class="d-flex w-100">
                                                        <img src="<?= htmlspecialchars($avatar_url) ?>" alt="Avatar" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <h6 class="mb-0 small">
                                                                    <?= htmlspecialchars($message['auteur_prenom'] . ' ' . $message['auteur_nom']) ?>
                                                                    <?= $message['role_badge'] ?? '' // Afficher le badge de rôle s'il existe 
                                                                    ?>
                                                                </h6>
                                                                <small class="text-muted" title="<?= $message['date_creation'] ?>"><?= htmlspecialchars($message['date_creation_formatted']) ?></small>
                                                            </div>
                                                            <p class="mb-0 mt-1 msg-content small"><?= nl2br(htmlspecialchars($message['contenu'])) ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex justify-content-center">
                                        <?= $messages_pagination_html ?>
                                    </div>

                                </div>

                                <div class="col-lg-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-header bg-light py-2"><i class="fas fa-calendar-alt me-2 text-primary"></i>Événements à venir</div>
                                        <div class="list-group list-group-flush">
                                            <?php if (empty($community['evenements'])) : ?>
                                                <div class="list-group-item small text-muted py-2">Aucun événement prévu.</div>
                                            <?php else : ?>
                                                <?php foreach ($community['evenements'] as $event) : ?>
                                                    <a href="../events/event_details.php?id=<?= $event['id'] // Assurez-vous que ce chemin est correct 
                                                                                            ?>" class="list-group-item list-group-item-action py-2">
                                                        <div class="d-flex w-100 justify-content-between">
                                                            <h6 class="mb-1 small fw-bold"><?= htmlspecialchars($event['titre']) ?></h6>
                                                            <small class="text-muted"><?= htmlspecialchars($event['date_debut_formatted']) ?></small>
                                                        </div>
                                                        <small class="text-muted d-block"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($event['lieu'] ?? 'N/A') ?></small>
                                                    </a>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="card shadow-sm">
                                        <div class="card-header bg-light py-2"><i class="fas fa-users me-2 text-primary"></i>Membres (<?= $community['nombre_membres'] ?>)</div>
                                        <div class="card-body">
                                            <?php if (empty($members)) : ?>
                                                <p class="text-muted small">Aucun membre.</p>
                                            <?php else : ?>
                                                <div class="d-flex flex-wrap">
                                                    <?php foreach ($members as $member) :
                                                        $avatar_url_member = !empty($member['photo_url'])
                                                            ? ROOT_URL . $member['photo_url']
                                                            : ROOT_URL . '/assets/images/default_avatar.png';
                                                        // Extraire le texte du rôle du badge HTML (méthode simple)
                                                        $role_text = strip_tags($member['role_badge'] ?? 'Membre');
                                                    ?>
                                                        <div class="text-center me-2 mb-2" title="<?= htmlspecialchars($member['prenom'] . ' ' . $member['nom'] . ' (' . $role_text . ')') ?>">
                                                            <img src="<?= htmlspecialchars($avatar_url_member) ?>" alt="Avatar" class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover;">
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <?php if ($community['nombre_membres'] > count($members)) : // Afficher +X si plus de membres que l'aperçu 
                                                    ?>
                                                        <div class="text-center me-2 mb-2" title="Et <?= $community['nombre_membres'] - count($members) ?> autre(s)">
                                                            <span class="badge bg-light text-dark rounded-circle d-flex align-items-center justify-content-center border" style="width: 40px; height: 40px;">+<?= $community['nombre_membres'] - count($members) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                    } else {
                        $limit = 12;
                        $communitiesData = getCommunities($employee_id, $page, $limit, $search, $type_filter);
                        $communities = $communitiesData['communities'];
                        $pagination_html = $communitiesData['pagination_html'];

                        $communityTypes = ['sport' => 'Sport', 'bien_etre' => 'Bien-être', 'sante' => 'Santé', 'autre' => 'Autre'];
                        ?>
                        <form method="get" action="" class="mb-4 p-3 bg-light border rounded">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label for="search" class="form-label">Rechercher</label>
                                    <input type="text" id="search" name="search" class="form-control form-control-sm" placeholder="Nom, description..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="type" class="form-label">Type</label>
                                    <select id="type" name="type" class="form-select form-select-sm">
                                        <option value="">Tous les types</option>
                                        <?php foreach ($communityTypes as $key => $value) : ?>
                                            <option value="<?= $key ?>" <?= ($type_filter === $key) ? 'selected' : '' ?>><?= htmlspecialchars($value) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Filtrer</button>
                                </div>
                            </div>
                        </form>

                        <!-- Communities List -->
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php if (empty($communities)) : ?>
                                <div class="col-12">
                                    <p class="text-center text-muted">Aucune communauté trouvée.</p>
                                </div>
                            <?php else : ?>
                                <?php foreach ($communities as $community) : ?>
                                    <div class="col">
                                        <div class="card h-100 shadow-sm community-card">
                                            <?php
                                            // Default or community logo
                                            $logo_url = !empty($community['logo_url'])
                                                ? ROOT_URL . $community['logo_url']
                                                : ROOT_URL . '/assets/images/default_community.png'; // Default image path
                                            ?>
                                            <img src="<?= htmlspecialchars($logo_url) ?>" class="card-img-top community-logo" alt="Logo <?= htmlspecialchars($community['nom']) ?>">
                                            <div class="card-body d-flex flex-column">
                                                <h5 class="card-title"><a href="?id=<?= $community['id'] ?>"><?= htmlspecialchars($community['nom']) ?></a></h5>
                                                <p class="card-text small text-muted flex-grow-1"><?= nl2br(htmlspecialchars(substr($community['description'] ?? '', 0, 100))) . (strlen($community['description'] ?? '') > 100 ? '...' : '') ?></p>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="badge <?= $community['type_class'] ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $community['type']))) ?></span>
                                                    <span class="badge bg-secondary"><i class="fas fa-users me-1"></i><?= $community['nombre_membres'] ?></span>
                                                </div>
                                                <form method="post" action="" class="mt-auto">
                                                    <input type="hidden" name="community_id" value="<?= $community['id'] ?>">
                                                    <?php if ($community['est_membre']) : ?>
                                                        <button type="submit" name="leave_community" class="btn btn-sm btn-outline-danger w-100">Quitter</button>
                                                    <?php else : ?>
                                                        <button type="submit" name="join_community" class="btn btn-sm btn-success w-100">Rejoindre</button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                            <?php if (!empty($community['created_at_formatted'])) : ?>
                                                <div class="card-footer text-muted small">
                                                    Créée le: <?= htmlspecialchars($community['created_at_formatted']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4 d-flex justify-content-center">
                            <?= $pagination_html ?>
                        </div>

                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>