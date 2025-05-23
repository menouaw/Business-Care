<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees/communities.php';


$viewData = setupCommunitiesPage();


extract($viewData);


include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= WEBCLIENT_URL ?>/modules/employees/dashboard.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de Bord
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <?php if ($viewMode === 'list'): ?>

                <h3 class="mt-4 mb-3"><i class="fas fa-star text-warning me-2"></i>Pour vous</h3>
                <?php if (empty($preferredCommunities)): ?>
                    <div class="alert alert-light text-muted">Aucune communauté correspondant à vos intérêts ou préférences n'a été trouvée pour le moment.</div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-0 mb-4 community-card-row">
                        <?php foreach ($preferredCommunities as $community): ?>
                            <div class="col">
                                <?php renderCommunityCard($community, $userMemberCommunityIds, $csrf_token, 'primary');
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <hr class="my-5">

                <h3 class="mt-4 mb-3">Autres communautés</h3>
                <?php if (empty($otherCommunities)): ?>
                    <div class="alert alert-light text-muted">Aucune autre communauté disponible pour le moment.</div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-0 mb-4 community-card-row">
                        <?php foreach ($otherCommunities as $community): ?>
                            <div class="col">
                                <?php renderCommunityCard($community, $userMemberCommunityIds, $csrf_token, 'secondary');
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>



            <?php elseif ($viewMode === 'detail' && isset($community)): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?= htmlspecialchars($community['nom']) ?>
                            <small class="text-muted ms-2">(<?= htmlspecialchars(ucfirst($community['type'] ?? 'N/D')) ?>)</small>
                        </h6>
                        <div class="d-flex align-items-center">
                            <a href="<?= WEBCLIENT_URL ?>/modules/employees/communities.php" class="btn btn-sm btn-outline-secondary me-2" title="Retour à la liste des communautés">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <?php
                            $csrfToken = $csrf_token ?? ($_SESSION['csrf_token'] ?? '');

                            if ($isMember):
                            ?>
                                <form action="<?= WEBCLIENT_URL ?>/modules/employees/communities.php" method="POST" class="d-inline" onsubmit="return confirm('Voulez-vous vraiment quitter la communauté \'<?= htmlspecialchars(addslashes($community['nom'])) ?>\' ?');">
                                    <input type="hidden" name="action" value="leave">
                                    <input type="hidden" name="id" value="<?= $community['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-sign-out-alt me-1"></i> Quitter la communauté
                                    </button>
                                </form>
                            <?php else:
                            ?>
                                <form action="<?= WEBCLIENT_URL ?>/modules/employees/communities.php" method="POST" class="d-inline" onsubmit="return confirm('Voulez-vous vraiment rejoindre la communauté \'<?= htmlspecialchars(addslashes($community['nom'])) ?>\' ?');">
                                    <input type="hidden" name="action" value="join">
                                    <input type="hidden" name="id" value="<?= $community['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-plus me-1"></i> Rejoindre la communauté
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="mb-4"><?= nl2br(htmlspecialchars($community['description'] ?? 'Pas de description.')) ?></p>

                        <hr>

                        <div class="row mb-4">
                            <div class="col-md-8">
                                <h5 class="mb-3">Messages</h5>
                                <?php if ($isMember): ?>
                                    <?php
                                    ?>
                                    <div class="mb-4">
                                        <form action="<?= WEBCLIENT_URL ?>/modules/employees/communities.php?action=post_message&id=<?= $community['id'] ?>" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <div class="mb-3">
                                                <textarea class="form-control" id="messageContent" name="message_content" rows="3" placeholder="Écrire un message..." required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Envoyer</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Vous devez être membre de cette communauté pour poster des messages.</div>
                                <?php endif; ?>

                                <?php
                                ?>
                                <div class="list-group">
                                    <?php if (empty($messages)): ?>
                                        <p class="text-muted">Aucun message dans cette communauté pour le moment.</p>
                                    <?php else: ?>
                                        <?php foreach ($messages as $message): ?>
                                            <div class="list-group-item list-group-item-action flex-column align-items-start mb-2 border rounded">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><i class="fas fa-user me-2"></i><?= htmlspecialchars($message['auteur_nom'] ?? 'Utilisateur inconnu') ?></h6>
                                                    <div>
                                                        <small class="text-muted me-3"><?= htmlspecialchars(formatDate($message['created_at'], 'd/m/Y H:i'))
                                                                                        ?></small>
                                                        <?php

                                                        $currentUserId = $_SESSION['user_id'] ?? 0;
                                                        if (isset($message['personne_id']) && $message['personne_id'] == $currentUserId):
                                                        ?>
                                                            <form action="<?= WEBCLIENT_URL ?>/modules/employees/communities.php" method="POST" class="d-inline" style="margin-left: 5px;" onsubmit="return confirm('Voulez-vous vraiment supprimer ce message ?');">
                                                                <input type="hidden" name="action" value="delete_message">
                                                                <input type="hidden" name="id" value="<?= $community['id'] ?? 0 ?>">
                                                                <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Supprimer le message">
                                                                    <i class="fas fa-trash-alt fa-xs"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <p class="mb-1 mt-2"><?= nl2br(htmlspecialchars($message['message'] ?? '')) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php
                                        ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="m-0 font-weight-bold"><i class="fas fa-users me-2"></i>Membres (<?= $memberCount ?? 0 ?>)</h6>
                                    </div>
                                    <div class="card-body p-2" style="max-height: 400px; overflow-y: auto;">
                                        <?php if (empty($members)): ?>
                                            <p class="text-muted small px-2">Aucun membre pour le moment.</p>
                                        <?php else: ?>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($members as $member): ?>
                                                    <li class="list-group-item py-1 px-2 small d-flex align-items-center">
                                                        <i class="fas fa-user fa-xs me-2 text-secondary"></i>
                                                        <?= htmlspecialchars($member['prenom'] . ' ' . $member['nom']) ?>
                                                        <?php if (isset($member['id']) && $member['id'] == ($_SESSION['user_id'] ?? 0)): ?>
                                                            <span class="badge bg-success ms-auto">Vous</span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-warning">Impossible d'afficher la vue demandée.</div>
            <?php endif; ?>



        </main>
    </div>
</div>

<?php

function renderCommunityCard($community, $userMemberCommunityIds, $csrf_token, $colorTheme = 'secondary')
{
    $isMember = in_array($community['id'], $userMemberCommunityIds);
    $communityUrl = WEBCLIENT_URL . '/modules/employees/communities.php?view_id=' . $community['id'];
    $icon = getIconForCommunityType($community['type'] ?? 'default');
?>
    <div class="card h-100 shadow-sm community-card border-start border-4 border-<?= $colorTheme ?>">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title"><i class="<?= $icon ?> me-2 text-<?= $colorTheme ?>"></i><?= htmlspecialchars($community['nom']) ?></h5>
            <p class="card-text small text-muted flex-grow-1"><?= htmlspecialchars(truncateText($community['description'] ?? 'Pas de description.', 100)) ?></p>
            <div class="d-flex justify-content-between align-items-center mt-auto">
                <a href="<?= $communityUrl ?>" class="btn btn-sm btn-outline-primary">Voir</a>
                <?php if ($isMember): ?>
                    <form action="<?= WEBCLIENT_URL ?>/modules/employees/communities.php" method="POST" class="d-inline">
                        <input type="hidden" name="action" value="leave">
                        <input type="hidden" name="id" value="<?= $community['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Quitter</button>
                    </form>
                <?php else: ?>
                    <form action="<?= WEBCLIENT_URL ?>/modules/employees/communities.php" method="POST" class="d-inline">
                        <input type="hidden" name="action" value="join">
                        <input type="hidden" name="id" value="<?= $community['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <button type="submit" class="btn btn-sm btn-success">Rejoindre</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php
}


function getIconForCommunityType(string $type): string
{
    switch ($type) {
        case 'sport':
            return 'fas fa-running';
        case 'bien_etre':
            return 'fas fa-spa';
        case 'professionnel':
            return 'fas fa-briefcase';
        case 'loisir':
            return 'fas fa-gamepad';
        default:
            return 'fas fa-users';
    }
}

?>