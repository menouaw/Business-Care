<?php

if (!isset($pageTitle)) {
    $pageTitle = "Business Care";
}

if (!isset($isLoggedIn)) {
    $isLoggedIn = isAuthenticated();
}

if (!isset($userRole) && $isLoggedIn) {
    if (isEntrepriseUser()) {
        $userRole = 'entreprise';
    } elseif (isSalarieUser()) {
        $userRole = 'salarie';
    } elseif (isPrestataireUser()) {
        $userRole = 'prestataire';
    } else {
        $userRole = 'visiteur';
    }
}

$userNotifications = [];
?>
<!DOCTYPE html>
<html lang="<?= isset($_SESSION['user_language']) ? $_SESSION['user_language'] : 'fr' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo/noBgBlack.png" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Text:ital@0;1&family=Markazi+Text:wght@400..700&family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/client.css">
</head>

<body>
    <?php if (!isset($hideNavbar) || !$hideNavbar): ?>
        <nav class="navbar navbar-expand-lg <?= isset($transparentNav) && $transparentNav ? 'navbar-dark' : 'navbar-light bg-white' ?> fixed-top">
            <div class="container">
                <?php
                $dashboard_url = WEBCLIENT_URL;
                if (isset($_SESSION['user_role'])) {
                    switch ($_SESSION['user_role']) {
                        case ROLE_ENTREPRISE:
                            $dashboard_url = WEBCLIENT_URL . '/modules/companies/dashboard.php';
                            break;
                        case ROLE_SALARIE:
                            $dashboard_url = WEBCLIENT_URL . '/modules/employees/dashboard.php';
                            break;
                        case ROLE_PRESTATAIRE:
                            $dashboard_url = WEBCLIENT_URL . '/modules/providers/dashboard.php';
                            break;
                    }
                }
                ?>
                <a class="navbar-brand" href="<?= htmlspecialchars($dashboard_url) ?>">
                    <img src="<?= ASSETS_URL ?>/images/logo/<?= isset($transparentNav) && $transparentNav ? 'noBgWhite' : 'noBgBlack' ?>.png"
                        alt="Business Care" height="40">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
                    aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto">
                        <?php if (!$isLoggedIn): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= WEBCLIENT_URL ?>">Accueil</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= WEBCLIENT_URL ?>/index.php#services">Services</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= WEBCLIENT_URL ?>/index.php#offres">Tarifs</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= WEBCLIENT_URL ?>/modules/companies/contact.php">Contact</a>
                            </li>
                        <?php else: ?>
                            <?php if ($userRole === 'entreprise'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= WEBCLIENT_URL ?>/modules/companies/contact.php">Contact</a>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Entreprise
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/companies/dashboard.php">Tableau de bord</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/companies/invoices.php">Factures</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/companies/contracts.php">Contrats</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/companies/employees/index.php">Gestion des salariés</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/companies/quotes.php">Demander un devis</a></li>
                                    </ul>
                                </li>
                            <?php elseif ($userRole === 'salarie'): ?>

                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Salarié
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/employees/index.php"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/employees/appointments.php"><i class="fas fa-calendar-check me-2"></i>Mes Rendez-vous</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/employees/calendar.php"><i class="fas fa-calendar-day me-2"></i>Mon Planning</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/employees/services.php"><i class="fas fa-concierge-bell me-2"></i>Catalogue Services</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/employees/communities.php"><i class="fas fa-users me-2"></i>Communautés</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/employees/counsel.php"><i class="fas fa-heartbeat me-2"></i>Conseils Bien-être</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/employees/donations.php"><i class="fas fa-hand-holding-heart me-2"></i>Faire un don</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/employees/settings.php"><i class="fas fa-cog me-2"></i>Mes Paramètres</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= WEBCLIENT_URL ?>/modules/companies/contact.php">Contact</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= WEBCLIENT_URL ?>/modules/employees/chatbot.php"><i class="fas fa-robot me-1"></i>Assistance</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= WEBCLIENT_URL ?>/modules/employees/signalements.php"><i class="fas fa-exclamation-triangle me-1"></i>Signalement</a>
                                </li>
                            <?php elseif ($userRole === 'prestataire'): ?>

                                <li class="nav-item">
                                    <a class="nav-link" href="<?= WEBCLIENT_URL ?>/modules/companies/contact.php"><i class="fas fa-headset me-1"></i>Contact</a>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-briefcase me-1"></i>Mon Espace Prestataire
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/providers/index.php"><i class="fas fa-tachometer-alt me-2"></i>Tableau de bord</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/providers/calendar.php"><i class="fas fa-calendar-alt me-2"></i>Mon Calendrier</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/providers/interventions.php"><i class="fas fa-tasks me-2"></i>Mes Interventions</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/providers/services.php"><i class="fas fa-concierge-bell me-2"></i>Mes Services</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/providers/evaluations.php"><i class="fas fa-star-half-alt me-2"></i>Mes Évaluations</a></li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/providers/invoices.php"><i class="fas fa-file-invoice-dollar me-2"></i>Ma Facturation</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/providers/settings.php"><i class="fas fa-cog me-2"></i>Mes Paramètres</a></li>
                                    </ul>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        <?php if ($isLoggedIn): ?>

                            <li class="nav-item dropdown me-3 d-flex align-items-center">
                                <?php
                                $unread_count = getUnreadNotificationCount($_SESSION['user_id']);
                                ?>
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdownNotifications" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="height: 100%; padding-top: 0; padding-bottom: 0;">
                                    <i class="fas fa-bell"></i>
                                    <?php if ($unread_count > 0): ?>
                                        <span class="badge rounded-pill bg-danger ms-1" style="position: relative; top: -0.25rem;"><?= $unread_count ?></span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownNotifications">
                                    <li>
                                        <h6 class="dropdown-header">Notifications</h6>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>

                                    <?php
                                    $notifications_link = WEBCLIENT_URL . '/notifications.php';
                                    if (isset($userRole)) {
                                        switch ($userRole) {
                                            case 'entreprise':
                                                $notifications_link = WEBCLIENT_URL . '/modules/companies/notifications.php';
                                                break;
                                            case 'salarie':
                                                $notifications_link = WEBCLIENT_URL . '/modules/employees/notifications.php';
                                                break;
                                            case 'prestataire':
                                                $notifications_link = WEBCLIENT_URL . '/modules/providers/notifications.php';
                                                break;
                                        }
                                    }
                                    ?>
                                    <li><a class="dropdown-item text-center text-muted small" href="<?= $notifications_link ?>">Voir toutes les notifications</a></li>
                                </ul>
                            </li>


                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php if (isset($_SESSION['user_photo']) && !empty($_SESSION['user_photo'])): ?>
                                        <img src="<?= $_SESSION['user_photo'] ?>" alt="<?= $_SESSION['user_name'] ?>" class="rounded-circle avatar-sm">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle"></i>
                                    <?php endif; ?>

                                    <span class="d-none d-lg-inline-block ms-1"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Mon compte', ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php
                                    $settings_link = WEBCLIENT_URL . '/profile.php';
                                    if (isset($userRole)) {
                                        switch ($userRole) {
                                            case 'entreprise':
                                                $settings_link = WEBCLIENT_URL . '/modules/companies/settings.php';
                                                break;
                                            case 'salarie':
                                                $settings_link = WEBCLIENT_URL . '/modules/employees/settings.php';
                                                break;
                                            case 'prestataire':
                                                $settings_link = WEBCLIENT_URL . '/modules/providers/settings.php';
                                                break;
                                        }
                                    }
                                    ?>
                                    <li><a class="dropdown-item" href="<?= $settings_link ?>"><i class="fas fa-cog me-2"></i>Paramètres</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= WEBCLIENT_URL ?>/login.php">
                                    <i class="fas fa-sign-in-alt me-1"></i> Connexion
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <main role="main" class="flex-shrink-0 <?= $mainClass ?? '' ?>">
    </main>
</body>

</html>