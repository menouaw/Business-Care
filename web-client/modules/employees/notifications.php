<?php
require_once __DIR__ . '/../../includes/init.php';


require_once __DIR__ . '/../../includes/page_functions/modules/employees/notifications.php';


$viewData = setupEmployeeNotificationsPage();
extract($viewData);

requireRole(ROLE_SALARIE);

$salarie_id = $_SESSION['user_id'] ?? 0;
if ($salarie_id <= 0) {
    flashMessage("Impossible d'identifier l'utilisateur.", "danger");
    redirectTo(WEBCLIENT_URL . '/auth/login.php');
    exit;
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Tableau de Bord
                </a>
            </div>

            <?php echo displayFlashMessages(); ?>

            <?php if (empty($notifications)):

            ?>
                <div class="alert alert-info">Vous n'avez aucune notification pour le moment.</div>
            <?php else:

            ?>
                <div class="list-group">
                    <?php foreach ($notifications as $notif):
                        $itemClass = $notif['lu'] == 0 ? 'list-group-item-warning' : '';
                        $link = !empty($notif['lien']) ? htmlspecialchars($notif['lien']) : '#';
                        $isClickable = $link !== '#';
                        $tag = $isClickable ? 'a' : 'div';
                    ?>
                        <<?= $tag ?> <?= $isClickable ? 'href="' . $link . '"' : '' ?>
                            class="list-group-item list-group-item-action flex-column align-items-start mb-2 border rounded <?= $itemClass ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?= htmlspecialchars($notif['titre'] ?? 'Notification') ?></h5>
                                <small class="text-muted">
                                    <?php
                                    echo htmlspecialchars(formatDate($notif['created_at']));
                                    ?>
                                </small>
                            </div>
                            <p class="mb-1 mt-2"><?= nl2br(htmlspecialchars($notif['message'] ?? '')) ?></p>
                            <small class="text-muted"><?= $notif['lu'] == 0 ? 'Non lue' : 'Lue' ?></small>
                        </<?= $tag ?>>
                    <?php endforeach; ?>
                </div>
            <?php

                if (!empty($pagination) && $pagination['totalPages'] > 1) {

                    if (function_exists('renderPagination')) {
                        echo renderPagination($pagination, WEBCLIENT_URL . '/modules/employees/notifications.php?page={page}');
                    } else {
                        echo "<p class='text-danger'>Erreur: Fonction renderPagination non trouvée.</p>";
                    }
                }
            endif; ?>

        </main>
    </div>
</div>
