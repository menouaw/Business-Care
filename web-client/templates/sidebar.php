<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse border-end shadow">
    <div class="position-sticky pt-3 sidebar-sticky">

        <?php

        $userRoleId = $_SESSION['user_role'] ?? null;

        if ($userRoleId === ROLE_ENTREPRISE): ?>
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-uppercase fw-semibold">
                <span>Gestion Entreprise</span>
            </h6>
            <ul class="nav flex-column nav-fill mb-3 sidebar-entreprise">
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage(WEBCLIENT_URL . '/modules/companies/dashboard.php') ? 'active' : '' ?>" href="<?= WEBCLIENT_URL ?>/modules/companies/dashboard.php">
                        <i class="fas fa-tachometer-alt fa-fw me-2"></i>Tableau de bord
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= isActivePage(WEBCLIENT_URL . '/modules/companies/employees/index.php') ? 'active' : '' ?>" href="<?= WEBCLIENT_URL ?>/modules/companies/employees/index.php">
                        <i class="fas fa-users-cog fa-fw me-2"></i>Salariés
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage(WEBCLIENT_URL . '/modules/companies/contracts.php') ? 'active' : '' ?>" href="<?= WEBCLIENT_URL ?>/modules/companies/contracts.php">
                        <i class="fas fa-file-contract fa-fw me-2"></i>Mes Contrats
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage(WEBCLIENT_URL . '/modules/companies/quotes.php') ? 'active' : '' ?>" href="<?= WEBCLIENT_URL ?>/modules/companies/quotes.php">
                        <i class="fas fa-file-alt me-2"></i>Mes Devis
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage(WEBCLIENT_URL . '/modules/companies/invoices.php') ? 'active' : '' ?>"
                        href="<?= WEBCLIENT_URL ?>/modules/companies/invoices.php">
                        <i class="fas fa-file-invoice-dollar fa-fw me-2"></i>Mes Factures
                    </a>
                </li>


            </ul>

        <?php
        elseif ($userRoleId === ROLE_SALARIE): ?>
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-uppercase fw-semibold">
                <span>Mon Espace Bien-être</span>
            </h6>
            <ul class="nav flex-column nav-fill mb-3">
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#"> <!-- TODO: Link -->
                        <i class="fas fa-tachometer-alt fa-fw me-2"></i>Tableau de bord
                    </a>
                </li>
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#"> <!-- TODO: Link -->
                        <i class="fas fa-concierge-bell fa-fw me-2"></i>Catalogue Services
                    </a>
                </li>
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#"> <!-- TODO: Link -->
                        <i class="fas fa-calendar-check fa-fw me-2"></i>Mes Rendez-vous
                    </a>
                </li>
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#"> <!-- TODO: Link -->
                        <i class="fas fa-calendar-alt fa-fw me-2"></i>Événements & Ateliers
                    </a>
                </li>
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#"> <!-- TODO: Link -->
                        <i class="fas fa-users fa-fw me-2"></i>Communautés
                    </a>
                </li>
            </ul>

            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-uppercase fw-semibold">
                <span>Ressources</span>
            </h6>
            <ul class="nav flex-column mb-3">
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#"> <!-- TODO: Link -->
                        <i class="fas fa-heartbeat fa-fw me-2"></i>Conseils Bien-être
                    </a>
                </li>
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#"> <!-- TODO: Link -->
                        <i class="fas fa-hand-holding-heart fa-fw me-2"></i>Faire un don
                    </a>
                </li>
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#"> <!-- TODO: Link -->
                        <i class="fas fa-robot fa-fw me-2"></i>Assistance IA
                    </a>
                </li>
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#"> <!-- TODO: Link -->
                        <i class="fas fa-exclamation-triangle fa-fw me-2"></i>Signalement
                    </a>
                </li>
            </ul>

            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-uppercase fw-semibold">
                <span>Mon Compte</span>
            </h6>
            <ul class="nav flex-column mb-3">
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#"> <!-- TODO: Link -->
                        <i class="fas fa-user-cog fa-fw me-2"></i>Mon Profil
                    </a>
                </li>

            </ul>


        <?php


        elseif ($userRoleId === ROLE_PRESTATAIRE): ?>
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-uppercase fw-semibold">
                <span>Mon Activité</span>
            </h6>
            <ul class="nav flex-column nav-fill mb-3">
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#">
                        <i class="fas fa-tachometer-alt fa-fw me-2"></i>Tableau de bord
                    </a>
                </li>
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#">
                        <i class="fas fa-calendar-alt fa-fw me-2"></i>Planning & Dispos
                    </a>
                </li>
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#">
                        <i class="fas fa-calendar-check fa-fw me-2"></i>Interventions
                    </a>
                </li>
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#">
                        <i class="fas fa-concierge-bell fa-fw me-2"></i>Mes Prestations
                    </a>
                </li>
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#">
                        <i class="fas fa-star-half-alt fa-fw me-2"></i>Évaluations Reçues
                    </a>
                </li>
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#">
                        <i class="fas fa-file-invoice-dollar fa-fw me-2"></i>Facturation
                    </a>
                </li>
            </ul>
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-uppercase fw-semibold">
                <span>Mon Compte</span>
            </h6>
            <ul class="nav flex-column mb-3">
                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="#">
                        <i class="fas fa-user-tie fa-fw me-2"></i>Mon Profil
                    </a>
                </li>

            </ul>

        <?php endif;
        ?>


        <?php
        if (isset($_SESSION['user_id'])): ?>
            <hr>
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-uppercase fw-semibold">
                <span>Support & Compte</span>
            </h6>
            <ul class="nav flex-column mb-3">

                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="<?php echo WEBCLIENT_URL; ?>/modules/companies/settings.php">
                        <i class="fas fa-cog fa-fw me-2"></i>Paramètres
                    </a>
                </li>

                <li class="nav-item border-bottom">
                    <a class="nav-link d-inline-flex align-items-center" href="<?php echo WEBCLIENT_URL; ?>/logout.php">
                        <i class="fas fa-sign-out-alt fa-fw me-2"></i>Déconnexion
                    </a>
                </li>
            </ul>
        <?php endif; ?>

    </div>
</nav>