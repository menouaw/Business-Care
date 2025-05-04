<?php
require_once __DIR__ . '/../includes/init.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php APP_NAME . 'Admin'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow" style="position: fixed;">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 d-flex align-items-center" href="<?php echo WEBADMIN_URL; ?>/" title="<?php echo APP_NAME; ?>">
            <img src="<?php echo ASSETS_URL; ?>/images/logo/noBgBlack.png" alt="<?php echo APP_NAME; ?>" height="30">
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="col-md-9 col-lg-10 ms-sm-auto">
            <div class="d-flex justify-content-between align-items-center">
                <div class="navbar-nav flex-row ms-auto">
                    <div class="nav-item text-nowrap">
                        <button type="button" class="nav-link px-3 btn btn-link" data-bs-toggle="modal" data-bs-target="#tutorialModal" title="Voir le tutoriel">
                            <i class="fas fa-question-circle"></i>
                        </button>
                    </div>
                    <div class="nav-item text-nowrap dropdown user-dropdown">
                        <a class="nav-link px-3 dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo WEBADMIN_URL; ?>/profile.php">Profil</a></li>
                            <li><a class="dropdown-item" href="<?php echo WEBADMIN_URL; ?>/settings.php">Parametres</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo WEBADMIN_URL; ?>/logout.php">Deconnexion</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div style="padding-top: var(--navbar-height);"></div>

    
    <div class="modal fade" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div style="position:relative;padding-bottom:56.25%;"> 
              <iframe style="width:100%;height:100%;position:absolute;left:0px;top:0px;border-radius: 0 0 .375rem .375rem" src="<?php echo getTutorialVideoUrl(); ?>" title="Comment utiliser les pages principales?" frameborder="0" referrerpolicy="unsafe-url" allowfullscreen="true" allow="clipboard-write" sandbox="allow-popups allow-popups-to-escape-sandbox allow-scripts allow-forms allow-same-origin allow-presentation"></iframe> 
            </div>
          </div>
        </div>
      </div>
    </div>
    

</body>
</html>

