<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3 sidebar-sticky">
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Global</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Accueil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/users/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/users/">
                    <i class="fas fa-users me-2"></i>
                    Utilisateurs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/companies/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/companies/">
                    <i class="fas fa-building me-2"></i>
                    Entreprises
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/contracts/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/contracts/">
                    <i class="fas fa-file-contract me-2"></i>
                    Contrats
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/services/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/services/">
                    <i class="fas fa-concierge-bell me-2"></i>
                    Services
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/appointments/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/appointments/">
                    <i class="fas fa-calendar-check me-2"></i>
                    Rendez-vous
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Analyse</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/financial/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/financial/">
                    <i class="fas fa-money-bill-alt me-2"></i>
                    Finance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/reports/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/reports/">
                    <i class="fas fa-chart-bar me-2"></i>
                    Rapports
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Gestion</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/billing/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/billing/">
                    <i class="fas fa-file-invoice-dollar me-2"></i>
                    Facturation
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/providers/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/providers/">
                    <i class="fas fa-user-tie me-2"></i>
                    Prestataires
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/quotes/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/quotes/">
                    <i class="fas fa-file-alt me-2"></i>
                    Devis
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Organisation</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/conferences/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/conferences/">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    Conférences
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/events/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/events/">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Evènements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/webinars/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/webinars/">
                    <i class="fas fa-desktop me-2"></i>
                    Webinars
                </a>
            </li>
             <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/challenges/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/challenges/">
                    <i class="fas fa-trophy me-2"></i>
                    Défis Sportifs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['SCRIPT_NAME'], '/modules/newsletter/') !== false ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/modules/newsletter/">
                    <i class="fas fa-newspaper me-2"></i>
                    Newsletter
                </a>
            </li>
        </ul>

        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Administration</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'settings.php' ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/settings.php">
                    <i class="fas fa-cog me-2"></i>
                    Parametres
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'logs.php' ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/logs.php">
                    <i class="fas fa-history me-2"></i>
                    Journal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'backup.php' ? 'active' : ''; ?>" href="<?php echo WEBADMIN_URL; ?>/backup.php">
                    <i class="fas fa-database me-2"></i>
                    Sauvegarde
                </a>
            </li>
        </ul>
    </div>
</nav> 