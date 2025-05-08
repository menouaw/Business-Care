<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies/notifications.php';

requireRole(ROLE_ENTREPRISE);

$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id <= 0) {
    flashMessage("Impossible d'identifier votre compte.", "danger");
    redirectTo(WEBCLIENT_URL . '/modules/companies/dashboard.php');
    exit;
}

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
$notification_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($action === 'read' && $notification_id > 0) {
    if (markNotificationAsRead($notification_id, $user_id)) {
        flashMessage("Notification marquée comme lue.", "success");
    } else {
        flashMessage("Impossible de marquer la notification comme lue.", "warning");
    }
    redirectTo(WEBCLIENT_URL . '/modules/companies/notifications.php');
    exit;
}

if ($action === 'readall') {
    $count = markAllNotificationsAsRead($user_id);
    flashMessage("$count notification(s) marquée(s) comme lue(s).", "success");
    redirectTo(WEBCLIENT_URL . '/modules/companies/notifications.php');
    exit;
}

$pageTitle = "Mes Notifications";
$notifications = getNotificationsForUser($user_id);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= WEBCLIENT_URL ?>/modules/companies/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Retour
                    </a>

                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="list-group">
                <?php if (empty($notifications)): ?>
                    <p class="text-muted">Vous n'avez aucune notification.</p>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <?php
                        $bg_class = $notif['lu'] ? 'list-group-item-light' : 'list-group-item-' . $notif['type'];
                        $text_muted = $notif['lu'] ? 'text-muted' : '';
                        ?>
                        <div class="list-group-item list-group-item-action <?= $bg_class ?> mb-2 border rounded">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1 <?= $text_muted ?>"><?= htmlspecialchars($notif['titre']) ?></h5>
                                <small class="<?= $text_muted ?>"><?= timeAgo($notif['created_at']) ?></small>
                            </div>
                            <p class="mb-1 <?= $text_muted ?>"><?= nl2br(htmlspecialchars($notif['message'])) ?></p>
                            <small class="<?= $text_muted ?>">
                                <?php if ($notif['lu']): ?>
                                    Lu le <?= date(DEFAULT_DATE_FORMAT . ' H:i', strtotime($notif['date_lecture'])) ?>
                                <?php else: ?>
                                    <a href="<?= WEBCLIENT_URL ?>/modules/companies/notifications.php?action=read&id=<?= $notif['id'] ?>" class="btn btn-sm btn-link p-0">Marquer comme lu</a>
                                <?php endif; ?>

                                <?php if (!empty($notif['redirect_url'])): ?>
                                    <a href="<?= htmlspecialchars($notif['redirect_url']) ?>" class="btn btn-sm btn-outline-primary ms-2 px-1">Noter la prestation</a>
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>