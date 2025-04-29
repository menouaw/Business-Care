<?php
require_once __DIR__ . '/../../includes/init.php';
// Peut-être require_once d'un fichier de fonctions spécifique pour les notifications si besoin

// ---- DEBUGGING START ----
echo "<pre>DEBUG INFO (Notifications Page):\n";
echo "ROLE_SALARIE Constant: " . (defined('ROLE_SALARIE') ? ROLE_SALARIE : 'NOT DEFINED') . "\n";
if (isset($_SESSION['user_role_id'])) { // Assurez-vous que 'user_role_id' est la bonne clé de session
    echo "Session Role ID ('user_role_id'): " . $_SESSION['user_role_id'] . "\n";
    echo "Session User ID ('user_id'): " . ($_SESSION['user_id'] ?? 'Not Set') . "\n";
    echo "Session User Name ('user_name'): " . ($_SESSION['user_name'] ?? 'Not Set') . "\n";
} else {
    echo "SESSION['user_role_id'] NOT SET!\n";
}
echo "</pre>";
// die("Debugging - Arrêt avant requireRole"); // Décommentez pour arrêter ici
// ---- DEBUGGING END ----

// --- C'EST CETTE LIGNE QUI EST IMPORTANTE ---
requireRole(ROLE_SALARIE);
// -----------------------------------------

$salarie_id = $_SESSION['user_id'] ?? 0;
if ($salarie_id <= 0) {
    flashMessage("Impossible d'identifier l'utilisateur.", "danger");
    redirectTo(WEBCLIENT_URL . '/auth/login.php');
    exit;
}

$pageTitle = "Mes Notifications";

// TODO: Récupérer les notifications pour $salarie_id (probablement avec pagination)
// $notificationsData = getAllNotificationsForUser($salarie_id, $currentPage);
// $notifications = $notificationsData['items'];
// $pagination = $notificationsData; // Adapter selon le retour de la fonction

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

            <?php // TODO: Afficher la liste des notifications ici 
            ?>
            <p>Affichage de la liste complète des notifications...</p>
            <?php // TODO: Afficher la pagination ici 
            ?>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>