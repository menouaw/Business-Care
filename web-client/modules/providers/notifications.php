<?php
require_once __DIR__ . '/../../includes/init.php';

require_once __DIR__ . '/../../includes/page_functions/modules/providers/notifications.php';


requireRole(ROLE_PRESTATAIRE);

$user_id = $_SESSION['user_id'] ?? 0;


if ($user_id <= 0) {
    flashMessage("Impossible d'identifier votre compte.", "danger");

    redirectTo(WEBCLIENT_URL . '/modules/providers/dashboard.php');
    exit;
}



handleProviderNotificationAction($user_id);


$pageTitle = "Mes Notifications";
$notifications = getNotificationsForUser($user_id);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php';
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= WEBCLIENT_URL ?>/modules/providers/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                    </a>
                    <a href="<?= WEBCLIENT_URL ?>/modules/providers/notifications.php?action=readall" class="btn btn-sm btn-outline-info" onclick="return confirm('Marquer toutes les notifications comme lues ?');">
                        <i class="fas fa-check-double me-1"></i> Tout marquer comme lu
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages();
            ?>

            <div class="list-group">
                <?php if (empty($notifications)): ?>
                    <p class="text-muted text-center mt-3">Vous n'avez aucune notification pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <?php


                        $allowed_notification_types = [
                            'info',
                            'success',
                            'warning',
                            'danger',
                            'primary',
                            'secondary',
                            'light',
                            'dark'
                        ];


                        $notification_type_for_class = $notif['type'] ?? 'info';
                        if (!in_array($notification_type_for_class, $allowed_notification_types, true)) {
                            $notification_type_for_class = 'info';
                        }

                        $bg_class = $notif['lu'] ? 'list-group-item-light' : 'list-group-item-' . $notification_type_for_class;
                        $text_muted = $notif['lu'] ? 'text-muted' : '';
                        ?>
                        <div class="list-group-item list-group-item-action <?= $bg_class ?> mb-2 border rounded shadow-sm">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1 <?= $text_muted ?>"><?= htmlspecialchars($notif['titre']) ?></h5>
                                <?php
                                $created_at_display = 'Date invalide'; 
                                if (!empty($notif['created_at'])) {
                                    
                                    $timestamp = strtotime($notif['created_at']);
                                    if ($timestamp !== false) {
                                        
                                        $created_at_display = htmlspecialchars(timeAgo($notif['created_at']));
                                    }
                                }
                                ?>
                                <small class="<?= $text_muted ?>"><?= $created_at_display ?></small>
                            </div>
                            <p class="mb-1 <?= $text_muted ?>"><?= nl2br(htmlspecialchars($notif['message'])) ?></p>
                            <small class="<?= $text_muted ?>">
                                <?php if ($notif['lu']): ?>
                                    Lu le <?= htmlspecialchars(date(DEFAULT_DATE_FORMAT . ' H:i', strtotime($notif['date_lecture']))) ?>
                                <?php else: ?>
                                    <a href="<?= WEBCLIENT_URL ?>/modules/providers/notifications.php?action=read&id=<?= $notif['id'] ?>" class="btn btn-sm btn-link p-0">Marquer comme lu</a>
                                <?php endif; ?>

                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>
