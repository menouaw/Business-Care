<?php

/**
 * page d'accueil
 *
 * cette page présente le tableau de bord principal et un aperçu des fonctionnalités principales
 */

require_once __DIR__ . '/includes/init.php';

// vérifier si l'utilisateur est connecté
$isLoggedIn = isAuthenticated();

// générer le titre de la page
$pageTitle = "Business Care - Bien-être et cohésion en milieu professionnel";

// déterminer le rôle de l'utilisateur connecté s'il y en a un
$userRole = null;
if ($isLoggedIn) {
    if (isEntrepriseUser()) {
        $userRole = 'entreprise';
    } elseif (isSalarieUser()) {
        $userRole = 'salarie';
    } elseif (isPrestataireUser()) {
        $userRole = 'prestataire';
    }
}

// inclure l'en-tête
include_once __DIR__ . '/templates/header.php';
?>

<main class="landing-page">
    <!-- section héro -->
    <section class="hero bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4">Améliorez la qualité de vie au travail</h1>
                    <p class="lead">Business Care propose des solutions pour améliorer la santé, le bien-être et la cohésion en milieu professionnel.</p>
                    <?php if (!$isLoggedIn): ?>
                        <div class="mt-4">
                            <a href="login.php" class="btn btn-light btn-lg me-2">Connexion</a>
                            <a href="inscription.php" class="btn btn-outline-light btn-lg">Inscription</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-end">
                    <img src="<?= ASSETS_URL ?>/images/logo/noBgWhite.png" alt="Business Care" class="img-fluid hero-image">
                </div>
            </div>
        </div>
    </section>

    <!-- section services -->
    <section class="services py-5">
        <div class="container">
            <div class="section-title text-center mb-5">
                <h2>Nos services</h2>
                <p class="lead">Des solutions adaptées à tous les besoins</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 service-card">
                        <div class="card-body text-center">
                            <div class="icon-wrapper mb-3">
                                <i class="fas fa-brain fa-3x text-primary"></i>
                            </div>
                            <h3 class="card-title">Prévention en santé mentale</h3>
                            <p class="card-text">Séances individuelles, formations et webinars pour le bien-être psychologique de vos collaborateurs.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 service-card">
                        <div class="card-body text-center">
                            <div class="icon-wrapper mb-3">
                                <i class="fas fa-users fa-3x text-primary"></i>
                            </div>
                            <h3 class="card-title">Cohésion d'équipe</h3>
                            <p class="card-text">Conseils hebdomadaires, défis sportifs, séances de yoga et activités de groupe pour renforcer les liens.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 service-card">
                        <div class="card-body text-center">
                            <div class="icon-wrapper mb-3">
                                <i class="fas fa-hand-holding-heart fa-3x text-primary"></i>
                            </div>
                            <h3 class="card-title">Engagement associatif</h3>
                            <p class="card-text">Dons financiers, dons matériels et participation bénévole à des actions proposées par nos associations partenaires.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- section offres -->
    <section class="pricing py-5 bg-light">
        <div class="container">
            <div class="section-title text-center mb-5">
                <h2>Nos offres</h2>
                <p class="lead">Des formules adaptées à toutes les entreprises</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 pricing-card">
                        <div class="card-header text-center bg-primary text-white">
                            <h3 class="my-0">Starter</h3>
                        </div>
                        <div class="card-body">
                            <h4 class="card-title pricing-card-title text-center">À partir de 20€ <small class="text-muted">/ salarié / an</small></h4>
                            <ul class="list-unstyled mt-4 mb-4">
                                <li><i class="fas fa-check text-success me-2"></i> Conseils hebdomadaires</li>
                                <li><i class="fas fa-check text-success me-2"></i> 2 conférences par an</li>
                                <li><i class="fas fa-check text-success me-2"></i> Accès aux webinars collectifs</li>
                                <li><i class="fas fa-check text-success me-2"></i> 2 rendez-vous médicaux par salarié</li>
                            </ul>
                            <div class="text-center mt-auto">
                                <a href="modules/companies/devis.php" class="btn btn-outline-primary">Demander un devis</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 pricing-card border-primary">
                        <div class="card-header text-center bg-primary text-white">
                            <h3 class="my-0">Basic</h3>
                            <span class="badge bg-warning text-dark">Populaire</span>
                        </div>
                        <div class="card-body">
                            <h4 class="card-title pricing-card-title text-center">À partir de 35€ <small class="text-muted">/ salarié / an</small></h4>
                            <ul class="list-unstyled mt-4 mb-4">
                                <li><i class="fas fa-check text-success me-2"></i> Tous les avantages Starter</li>
                                <li><i class="fas fa-check text-success me-2"></i> 4 conférences par an</li>
                                <li><i class="fas fa-check text-success me-2"></i> Accès aux défis sportifs</li>
                                <li><i class="fas fa-check text-success me-2"></i> 4 rendez-vous médicaux par salarié</li>
                                <li><i class="fas fa-check text-success me-2"></i> Programme personnalisé</li>
                            </ul>
                            <div class="text-center mt-auto">
                                <a href="modules/companies/devis.php" class="btn btn-primary">Demander un devis</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 pricing-card">
                        <div class="card-header text-center bg-primary text-white">
                            <h3 class="my-0">Premium</h3>
                        </div>
                        <div class="card-body">
                            <h4 class="card-title pricing-card-title text-center">À partir de 50€ <small class="text-muted">/ salarié / an</small></h4>
                            <ul class="list-unstyled mt-4 mb-4">
                                <li><i class="fas fa-check text-success me-2"></i> Tous les avantages Basic</li>
                                <li><i class="fas fa-check text-success me-2"></i> 6 conférences par an</li>
                                <li><i class="fas fa-check text-success me-2"></i> Accès aux communautés exclusives</li>
                                <li><i class="fas fa-check text-success me-2"></i> Rendez-vous médicaux illimités</li>
                                <li><i class="fas fa-check text-success me-2"></i> Chatbot de signalement anonyme</li>
                                <li><i class="fas fa-check text-success me-2"></i> Service sur mesure</li>
                            </ul>
                            <div class="text-center mt-auto">
                                <a href="modules/companies/devis.php" class="btn btn-outline-primary">Demander un devis</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- section témoignages -->
    <section class="testimonials py-5">
        <div class="container">
            <div class="section-title text-center mb-5">
                <h2>Ils nous font confiance</h2>
                <p class="lead">Ce que nos clients disent de nous</p>
            </div>

            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <div class="card testimonial-card">
                                    <div class="card-body text-center">
                                        <p class="testimonial-text">"Depuis que nous avons fait appel à Business Care, nous avons constaté une amélioration significative de la cohésion d'équipe et du bien-être général de nos collaborateurs."</p>
                                        <div class="testimonial-author mt-3">
                                            <h5 class="mb-0">Sophie Durand</h5>
                                            <p class="text-muted">DRH, Entreprise Tech</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <div class="card testimonial-card">
                                    <div class="card-body text-center">
                                        <p class="testimonial-text">"Les séances individuelles et les webinars ont été très appréciés par nos équipes. Business Care nous a permis de réduire significativement le stress au travail."</p>
                                        <div class="testimonial-author mt-3">
                                            <h5 class="mb-0">Thomas Martin</h5>
                                            <p class="text-muted">CEO, PME Service</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="carousel-item">
                                <div class="card testimonial-card">
                                    <div class="card-body text-center">
                                        <p class="testimonial-text">"Le programme personnalisé de Business Care répond parfaitement à nos besoins spécifiques. Nos employés sont plus engagés et plus satisfaits de leur environnement de travail."</p>
                                        <div class="testimonial-author mt-3">
                                            <h5 class="mb-0">Julie Lefèvre</h5>
                                            <p class="text-muted">Responsable RH, Grande Entreprise</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Précédent</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Suivant</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- section contact/cta -->
    <section class="cta py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 text-lg-start text-center">
                    <h2>Prêt à améliorer le bien-être au travail ?</h2>
                    <p class="lead mb-0">Contactez-nous dès aujourd'hui pour discuter de vos besoins spécifiques.</p>
                </div>
                <div class="col-lg-4 text-lg-end text-center mt-3 mt-lg-0">
                    <a href="contact.php" class="btn btn-light btn-lg">Nous contacter</a>
                </div>
            </div>
        </div>
    </section>

    <?php if ($isLoggedIn): ?>
        <!-- section tableau de bord (si connecté) -->
        <section class="dashboard py-5">
            <div class="container">
                <div class="section-title text-center mb-5">
                    <h2>Votre espace personnel</h2>
                    <p class="lead">Accédez rapidement à vos fonctionnalités</p>
                </div>

                <div class="row g-4">
                    <?php if ($userRole === 'entreprise'): ?>
                        <!-- dashboard entreprise -->
                        <div class="col-md-4">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <h3 class="card-title"><i class="fas fa-file-contract text-primary me-2"></i> Contrats</h3>
                                    <p class="card-text">Gérez vos contrats et abonnements</p>
                                    <a href="modules/companies/contracts.php" class="btn btn-sm btn-outline-primary">Accéder</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <h3 class="card-title"><i class="fas fa-users text-primary me-2"></i> Salariés</h3>
                                    <p class="card-text">Gérez les accès de vos collaborateurs</p>
                                    <a href="modules/companies/employees.php" class="btn btn-sm btn-outline-primary">Accéder</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <h3 class="card-title"><i class="fas fa-chart-line text-primary me-2"></i> Statistiques</h3>
                                    <p class="card-text">Suivez l'utilisation des services</p>
                                    <a href="modules/companies/stats.php" class="btn btn-sm btn-outline-primary">Accéder</a>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($userRole === 'salarie'): ?>
                        <!-- dashboard salarié -->
                        <div class="col-md-4">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <h3 class="card-title"><i class="fas fa-calendar-check text-primary me-2"></i> Réservations</h3>
                                    <p class="card-text">Réservez des prestations et services</p>
                                    <a href="modules/employees/reservations.php" class="btn btn-sm btn-outline-primary">Accéder</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <h3 class="card-title"><i class="fas fa-running text-primary me-2"></i> Défis sportifs</h3>
                                    <p class="card-text">Participez à des challenges sportifs</p>
                                    <a href="modules/employees/challenges.php" class="btn btn-sm btn-outline-primary">Accéder</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <h3 class="card-title"><i class="fas fa-comments text-primary me-2"></i> Communautés</h3>
                                    <p class="card-text">Échangez avec d'autres salariés</p>
                                    <a href="modules/employees/communities.php" class="btn btn-sm btn-outline-primary">Accéder</a>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($userRole === 'prestataire'): ?>
                        <!-- dashboard prestataire -->
                        <div class="col-md-4">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <h3 class="card-title"><i class="fas fa-calendar-alt text-primary me-2"></i> Planning</h3>
                                    <p class="card-text">Gérez votre calendrier et disponibilités</p>
                                    <a href="modules/providers/calendar.php" class="btn btn-sm btn-outline-primary">Accéder</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <h3 class="card-title"><i class="fas fa-clipboard-list text-primary me-2"></i> Prestations</h3>
                                    <p class="card-text">Suivez vos prestations en cours</p>
                                    <a href="modules/providers/services.php" class="btn btn-sm btn-outline-primary">Accéder</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <h3 class="card-title"><i class="fas fa-file-invoice-dollar text-primary me-2"></i> Facturation</h3>
                                    <p class="card-text">Consultez vos factures et paiements</p>
                                    <a href="modules/providers/invoices.php" class="btn btn-sm btn-outline-primary">Accéder</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
// inclure le pied de page
include_once __DIR__ . '/templates/footer.php';
?>