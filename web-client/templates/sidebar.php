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
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage('/modules/employees/dashboard.php') ?>"
                        href="<?= WEBCLIENT_URL ?>/modules/employees/dashboard.php">
                        <i class="fas fa-tachometer-alt fa-fw me-2"></i>Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage('/modules/employees/services.php') ?>"
                        href="<?= WEBCLIENT_URL ?>/modules/employees/services.php">
                        <i class="fas fa-concierge-bell fa-fw me-2"></i>Catalogue Services
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage('/modules/employees/appointments.php') ?>"
                        href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Rendez-vous
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage('/modules/employees/communities.php') ?>"
                        href="<?= WEBCLIENT_URL ?>/modules/employees/communities.php">
                        <i class="fas fa-users me-2"></i>
                        Communautés

                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage('/modules/employees/events.php') ?>"
                        href="<?= WEBCLIENT_URL ?>/modules/employees/events.php">
                        <i class="fas fa-calendar-alt fa-fw me-2"></i>Événements & Ateliers
                    </a>
                </li>
            </ul>

            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-uppercase fw-semibold">
                <span>Ressources</span>
            </h6>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage('/modules/employees/counsel.php') ?>"
                        href="<?= WEBCLIENT_URL ?>/modules/employees/counsel.php">
                        <i class="fas fa-heartbeat fa-fw me-2"></i>Conseils Bien-être
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage('/modules/employees/donations.php') ?>"
                        href="<?= WEBCLIENT_URL ?>/modules/employees/donations.php">
                        <i class="fas fa-hand-holding-heart fa-fw me-2"></i>Faire un don
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage('/modules/employees/chatbot.php') ?>"
                        href="<?= WEBCLIENT_URL ?>/modules/employees/chatbot.php">
                        <i class="fas fa-robot fa-fw me-2"></i>Assistance IA
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage('/modules/employees/signalements.php') ?>"
                        href="<?= WEBCLIENT_URL ?>/modules/employees/signalements.php">
                        <i class="fas fa-exclamation-triangle fa-fw me-2"></i>Signalement
                    </a>
                </li>
            </ul>

            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-uppercase fw-semibold">
                <span>Mon Compte</span>
            </h6>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage('/modules/employees/settings.php') ? 'active' : '' ?>" href="<?= WEBCLIENT_URL ?>/modules/employees/settings.php">
                        <i class="fas fa-user-cog fa-fw me-2"></i>Mon Profil & Paramètres
                    </a>
                </li>
            </ul>


        <?php


        elseif ($userRoleId === ROLE_PRESTATAIRE):
            // Définir l'URL de base pour les modules prestataire
            $providerModuleUrl = defined('WEBCLIENT_URL') ? WEBCLIENT_URL . '/modules/providers' : '/modules/providers';
        ?>
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-uppercase fw-semibold">
                <span>Mon Activité</span>
            </h6>
            <ul class="nav flex-column nav-fill mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage($providerModuleUrl . '/dashboard.php') ? 'active' : '' ?>" aria-current="page" href="<?= $providerModuleUrl ?>/dashboard.php">
                        <i class="fas fa-tachometer-alt fa-fw me-2"></i> Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage($providerModuleUrl . '/appointments.php') ? 'active' : '' ?>" href="<?= $providerModuleUrl ?>/appointments.php">
                        <i class="fas fa-calendar-check fa-fw me-2"></i> Mes Rendez-vous
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage($providerModuleUrl . '/availabilities.php') ? 'active' : '' ?>" href="<?= $providerModuleUrl ?>/availabilities.php">
                        <i class="fas fa-calendar-alt fa-fw me-2"></i> Mes Disponibilités
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage($providerModuleUrl . '/habilitations.php') ? 'active' : '' ?>" href="<?= $providerModuleUrl ?>/habilitations.php">
                        <i class="fas fa-stamp fa-fw me-2"></i> Mes Habilitations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage($providerModuleUrl . '/evaluations.php') ? 'active' : '' ?>" href="<?= $providerModuleUrl ?>/evaluations.php">
                        <i class="fas fa-star-half-alt fa-fw me-2"></i> Mes Évaluations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage($providerModuleUrl . '/invoices.php') ? 'active' : '' ?>" href="<?= $providerModuleUrl ?>/invoices.php">
                        <i class="fas fa-file-invoice-dollar fa-fw me-2"></i> Mes Factures BC
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage($providerModuleUrl . '/interventions.php') ? 'active' : '' ?>" href="<?= $providerModuleUrl ?>/interventions.php">
                        <i class="fas fa-tasks fa-fw me-2"></i> Mes Interventions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage($providerModuleUrl . '/services.php') ? 'active' : '' ?>" href="<?= $providerModuleUrl ?>/services.php">
                        <i class="fas fa-concierge-bell fa-fw me-2"></i> Mes Services
                    </a>
                </li>
            </ul>
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-uppercase fw-semibold">
                <span>Mon Compte</span>
            </h6>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= isActivePage($providerModuleUrl . '/settings.php') ? 'active' : '' ?>" href="<?= $providerModuleUrl ?>/settings.php">
                        <i class="fas fa-cog fa-fw me-2"></i> Paramètres
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= isActivePage('/modules/companies/contact.php') ? 'active' : '' ?>" href="<?= WEBCLIENT_URL ?>/modules/companies/contact.php">
                        <i class="fas fa-headset fa-fw me-2"></i> Support BC
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
                    <a class="nav-link d-inline-flex align-items-center" href="<?php echo WEBCLIENT_URL; ?>/logout.php">
                        <i class="fas fa-sign-out-alt fa-fw me-2"></i>Déconnexion
                    </a>
                </li>
            </ul>
        <?php endif; ?>

    </div>
</nav>