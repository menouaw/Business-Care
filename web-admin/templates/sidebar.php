<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Tableau de bord
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/users/') !== false ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/users/">
                    <i class="fas fa-users me-2"></i>
                    Utilisateurs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/companies/') !== false ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/companies/">
                    <i class="fas fa-building me-2"></i>
                    Entreprises
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/contracts/') !== false ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/contracts/">
                    <i class="fas fa-file-contract me-2"></i>
                    Contrats
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/services/') !== false ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/services/">
                    <i class="fas fa-concierge-bell me-2"></i>
                    Services
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/financial/') !== false ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/financial/">
                    <i class="fas fa-money-bill-alt me-2"></i>
                    Finance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/reports/') !== false ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/modules/reports/">
                    <i class="fas fa-chart-bar me-2"></i>
                    Rapports
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Administration</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'settings.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/settings.php">
                    <i class="fas fa-cog me-2"></i>
                    Parametres
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'logs.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/logs.php">
                    <i class="fas fa-history me-2"></i>
                    Journal d'activite
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>/backup.php">
                    <i class="fas fa-database me-2"></i>
                    Sauvegarde & Restauration
                </a>
            </li>
        </ul>
    </div>
</nav> 