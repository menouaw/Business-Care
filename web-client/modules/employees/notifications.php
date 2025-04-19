<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

requireRole(ROLE_SALARIE);

$employee_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

handleNotificationPageActions($action, $employee_id);


$pageData = displayNotifications();
$notifications = $pageData['notifications'] ?? [];
$paginationHtml = $pageData['pagination_html'] ?? '';
$csrfToken = $_SESSION['csrf_token'] ?? generateToken();

$pageTitle = "Mes Notifications - Espace Salarié";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="employee-notifications-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2">Mes Notifications</h1>
                <p class="text-muted">Retrouvez ici tous les messages et alertes importants.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Dashboard
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Liste des notifications</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($notifications)) : ?>
                    <p class="text-center text-muted my-5">Vous n'avez aucune notification pour le moment.</p>
                <?php else : ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notif) : ?>
                            <div class="list-group-item px-3 py-3 <?= $notif['lu'] ? 'bg-light text-muted' : '' ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 <?= $notif['lu'] ? '' : 'fw-bold' ?>">
                                        <i class="<?= getNotificationIcon($notif['type'] ?? 'info') ?> me-2"></i>
                                        <?= htmlspecialchars($notif['titre'] ?? 'Notification') ?>
                                    </h6>
                                    <small class="text-nowrap <?= $notif['lu'] ? 'text-muted' : '' ?>" title="<?= htmlspecialchars(formatDate($notif['created_at'])) ?>">
                                        <?= htmlspecialchars(timeAgo(strtotime($notif['created_at']))) ?>
                                    </small>
                                </div>
                                <p class="mb-1 ms-4"><?= nl2br(htmlspecialchars($notif['message'] ?? '')) ?></p>
                                <div class="d-flex justify-content-end align-items-center mt-2">
                                    <?php if (!empty($notif['lien'])) : ?>
                                        <a href="notifications.php?action=mark_read_and_redirect&id=<?= $notif['id'] ?>&csrf_token=<?= $csrfToken ?>" class="btn btn-sm btn-outline-primary me-2">
                                            <i class="fas fa-eye me-1"></i> Voir
                                        </a>
                                    <?php endif; ?>
                                    <?php /* if (!$notif['lu']) : ?>
                                    <?php endif; */ ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($paginationHtml)) : ?>
                        <div class="card-footer bg-white">
                            <?= $paginationHtml ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php

include_once __DIR__ . '/../../templates/footer.php';
?>