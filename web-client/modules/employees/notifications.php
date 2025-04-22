<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

// Ensure the user is logged in as an employee
requireRole(ROLE_SALARIE);

$employee_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Fetch notifications data (this function also marks them as read)
$pageData = displayEmployeeNotificationsPage($employee_id, $page);

$notifications = $pageData['notifications'] ?? [];
$pagination_html = $pageData['pagination_html'] ?? '';

$pageTitle = "Mes Notifications";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="employee-notifications-page py-5">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2 mb-0"><?= $pageTitle ?></h1>
                <p class="text-muted">Historique de toutes vos notifications.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-chevron-left me-1"></i> Retour au tableau de bord
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light py-3">
                <h5 class="mb-0">Historique des notifications</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                    <div class="text-center p-5 text-muted">
                        <i class="fas fa-bell-slash fa-3x mb-3"></i>
                        <p>Vous n'avez aucune notification pour le moment.</p>
                    </div>
                <?php else:
                    // We use list-group-flush to remove outer borders and padding
                ?>
                    <div class="list-group list-group-flush">
                        <?php
                        // Define the target URL for employee notification details
                        $detailsUrl = WEBCLIENT_URL . '/modules/employees/appointments.php'; // Or '/mon-planning.php' if that's the correct page

                        foreach ($notifications as $notification): ?>
                            <div class="list-group-item px-4 py-3 <?= htmlspecialchars($notification['bg_class']) ?> <?= !$notification['is_read'] ? 'fw-bold' : '' ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <div class="me-3">
                                        <i class="<?= htmlspecialchars($notification['icon_class']) ?> me-2"></i>
                                        <strong class="mb-1"><?= htmlspecialchars($notification['titre'] ?? 'Notification') ?></strong>
                                    </div>
                                    <small class="text-muted" title="<?= htmlspecialchars($notification['created_at'] ?? '') ?>">
                                        <?= htmlspecialchars($notification['created_at_formatted'] ?? '') ?>
                                    </small>
                                </div>
                                <p class="mb-1 mt-1 ms-4 ps-1">
                                    <?= nl2br(htmlspecialchars($notification['message'] ?? '')) ?>
                                </p>
                                <?php
                                // Check if a link was originally intended, but always point to the defined details URL
                                if (!empty($notification['lien'])):
                                ?>
                                    <small class="ms-4 ps-1">
                                        <a href="<?= htmlspecialchars($detailsUrl) ?>" class="stretched-link text-decoration-none">Voir les détails <i class="fas fa-arrow-right fa-xs ms-1"></i></a>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($pagination_html)): ?>
                <div class="card-footer bg-light border-top-0">
                    <nav aria-label="Page navigation">
                        <?= $pagination_html ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>