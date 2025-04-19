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
if ($isLoggedIn && isset($_SESSION['user_id'])) {
}
?>
<!DOCTYPE html>
<html lang="<?= isset($_SESSION['user_language']) ? $_SESSION['user_language'] : 'fr' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Favicon -->
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo/noBgBlack.png" type="image/png">

    <!-- CSS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/client.css">
</head>

<body>
    <a id="back-to-top" class="d-none d-lg-block">
        <i class="fas fa-arrow-up"></i>
    </a>

    <nav class="navbar navbar-expand-lg <?= isset($transparentNav) && $transparentNav ? 'navbar-dark' : 'navbar-light bg-white' ?> fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?= WEBCLIENT_URL ?>">
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
                                    <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/companies/index.php">Tableau de bord</a></li>
                                    <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/companies/invoices.php">Factures</a></li>
                                    <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/companies/contracts.php">Contrats</a></li>
                                    <li><a class="dropdown-item" href="<?= WEBCLIENT_URL ?>/modules/companies/employees.php">Gestion des salariés</a></li>
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
                                <a class="nav-link" href="<?= WEBCLIENT_URL ?>/modules/employees/signalement.php"><i class="fas fa-exclamation-triangle me-1"></i>Signalement</a>
                            </li>
                        <?php elseif ($userRole === 'prestataire'): ?>
                            <!-- Prestataire Links -->
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
                        <li class="nav-item dropdown">
                            <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <?php if (!empty($userNotifications)): ?>
                                    <span class="badge rounded-pill bg-danger"><?= count($userNotifications) ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                                <div class="notification-header d-flex justify-content-between align-items-center p-3">
                                    <h6 class="m-0">Notifications</h6>
                                    <?php if (!empty($userNotifications)): ?>
                                        <a href="#" class="text-decoration-none small">Marquer comme lues</a>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-body">
                                    <?php if (!empty($userNotifications)): ?>
                                        <?php foreach ($userNotifications as $notification): ?>
                                            <a href="<?= $notification['lien'] ?>" class="dropdown-item notification-item p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <i class="fas <?= $notification['icon'] ?? 'fa-info-circle' ?> fa-lg text-<?= $notification['type'] ?? 'primary' ?>"></i>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <p class="mb-1 fw-bold"><?= htmlspecialchars($notification['titre']) ?></p>
                                                        <p class="mb-0 small"><?= htmlspecialchars($notification['message']) ?></p>
                                                        <small class="text-muted"><?= $notification['date_formatee'] ?></small>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center p-3">
                                            <p class="mb-0 text-muted">Aucune notification</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-footer text-center p-2 border-top">
                                    <a href="<?= WEBCLIENT_URL ?>/notifications.php" class="text-decoration-none small">Voir toutes les notifications</a>
                                </div>
                            </div>
                        </li>

                        <!-- Profil utilisateur -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if (isset($_SESSION['user_photo']) && !empty($_SESSION['user_photo'])): ?>
                                    <img src="<?= $_SESSION['user_photo'] ?>" alt="<?= $_SESSION['user_name'] ?>" class="rounded-circle avatar-sm">
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                                <span class="d-none d-lg-inline-block ms-1"><?= $_SESSION['user_name'] ?? 'Mon compte' ?></span>
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
                        <li class="nav-item">
                            <a class="btn btn-primary" href="<?= WEBCLIENT_URL ?>/inscription.php">
                                <i class="fas fa-user-plus me-1"></i> Inscription
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div style="padding-top: 76px;"></div>

</body>

</html>