<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

$page_title = "Espace communautaire";
$page_description = "Rejoignez des communautés et participez à des activités avec vos collègues";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != ROLE_SALARIE) {
    if (function_exists('flashMessage')) {
        flashMessage("Vous devez être connecté en tant que salarié pour accéder à cette page", "warning");
    }
    header('Location: ' . ROOT_URL . '/common/connexion/');
    exit;
}

$employee_id = $_SESSION['user_id'];

$community_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?: '';
$type_filter = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING) ?: '';

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
                    // Afficher les messages flash
                    if (function_exists('displayFlashMessages')) {
                        displayFlashMessages();
                    }

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
// Inclusion du footer
include_once __DIR__ . '/../../templates/footer.php';
?>