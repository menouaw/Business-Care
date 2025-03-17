<?php
if (!defined('APP_NAME')) {
    require_once '../includes/config.php';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="<?php echo APP_URL; ?>/">
            <?php echo APP_NAME; ?>
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="col-md-9 col-lg-10 ms-sm-auto">
            <div class="d-flex justify-content-between align-items-center">
                <input class="form-control form-control-dark" type="text" placeholder="Rechercher..." aria-label="Search">
                <div class="navbar-nav flex-row">
                    <div class="nav-item text-nowrap">
                        <a class="nav-link px-3" href="<?php echo APP_URL; ?>/notifications.php">
                            <i class="fas fa-bell"></i>
                        </a>
                    </div>
                    <div class="nav-item text-nowrap dropdown user-dropdown">
                        <a class="nav-link px-3 dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/profile.php">Profil</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/settings.php">Paramètres</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php">Deconnexion</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <?php
    // affiche le message flash s'il existe
    $flashMessage = getFlashMessage();
    if ($flashMessage) {
        echo '<div class="alert alert-' . $flashMessage['type'] . ' alert-dismissible fade show" role="alert">
            ' . $flashMessage['message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
    ?>
</body>
</html> 