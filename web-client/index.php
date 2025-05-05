<?php

require_once __DIR__ . '/includes/init.php';

$isLoggedIn = isAuthenticated();

$pageTitle = "Business Care - Bien-être et cohésion en milieu professionnel";

$userRole = null;
if ($isLoggedIn) {
    if (isEntrepriseUser()) {
        $userRole = 'entreprise';
    } elseif (isSalarieUser()) {
        $userRole = 'salarie';
        redirectTo(WEBCLIENT_URL . '/modules/employees/dashboard.php');
        exit;
    } elseif (isPrestataireUser()) {
        $userRole = 'prestataire';
    }
}

$available_services_for_pricing = [];
try {
    $sql_debug = "SELECT id, type, description FROM services WHERE actif = 1 ORDER BY ordre";
    $stmt = executeQuery($sql_debug);
    $available_services_for_pricing = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des services pour la page d'accueil: " . $e->getMessage());
}

$serviceCategories = [
    [
        'title' => 'Santé & Bien-être',
        'description' => 'Séances individuelles, formations, webinars et ateliers pour le bien-être physique et psychologique de vos collaborateurs.',
        'icon' => 'fa-heartbeat'
    ],
    [
        'title' => 'Cohésion & Activités d\'équipe',
        'description' => 'Conseils hebdomadaires, défis sportifs, séances de yoga et évènements de team building pour renforcer les liens.',
        'icon' => 'fa-users'
    ],
    [
        'title' => 'Formation & Développement',
        'description' => 'Formations sur la gestion du stress, le leadership, la communication, l\'ergonomie et autres compétences clés.',
        'icon' => 'fa-chalkboard-teacher'
    ]

];

include_once __DIR__ . '/templates/header.php';
?>

<main class="landing-page">

    <section class="hero bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4">Améliorez la qualité de vie au travail</h1>
                    <p class="lead">Business Care propose des solutions pour améliorer la santé, le bien-être et la cohésion en milieu professionnel.</p>
                    <?php if (!$isLoggedIn): ?>
                        <div class="mt-4">
                            <a href="<?= WEBCLIENT_URL ?>/login.php" class="btn btn-light btn-lg me-2">Connexion</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-end">
                    <img src="<?= ASSETS_URL ?>/images/logo/noBgWhite.png" alt="Business Care" class="img-fluid hero-image">
                </div>
            </div>
        </div>
    </section>


    <section id="services" class="services py-5">
        <div class="container">
            <div class="section-title text-center mb-5">
                <h2>Nos services</h2>
                <p class="lead">Des solutions adaptées à tous les besoins</p>
            </div>

            <div class="row g-4">
                <?php foreach ($serviceCategories as $category): ?>
                    <div class="col-md-4">
                        <div class="card h-100 service-card">
                            <div class="card-body text-center">
                                <div class="icon-wrapper mb-3">
                                    <i class="fas <?php echo htmlspecialchars($category['icon']); ?> fa-3x text-primary"></i>
                                </div>
                                <h3 class="card-title"><?php echo htmlspecialchars($category['title']); ?></h3>
                                <p class="card-text"><?php echo htmlspecialchars($category['description']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <section id="offres" class="pricing py-5 bg-light">
        <div class="container">
            <div class="section-title text-center mb-5">
                <h2>Nos offres</h2>
                <p class="lead">Des formules adaptées à toutes les entreprises</p>
            </div>

            <div class="row g-4">

                <?php if (!empty($available_services_for_pricing)): ?>
                    <?php foreach ($available_services_for_pricing as $index => $service): ?>
                        <?php
                        $cardClass = 'pricing-card';
                        $buttonClass = 'btn btn-outline-primary';
                        $badge = '';



                        $pricingText = "Prix sur devis";
                        $features = [
                            'Accès aux services de base',
                            'Support client',
                            'Interface intuitive'
                        ];
                        if ($service['id'] == 1) {
                            $pricingText = "À partir de 20€ <small class=\"text-muted\">/ salarié / an</small>";
                            $features = [
                                'Conseils hebdomadaires',
                                '2 conférences par an',
                                'Accès webinars collectifs',
                                '2 RDV médicaux / salarié'
                            ];
                        } elseif ($service['id'] == 2) {
                            $pricingText = "À partir de 35€ <small class=\"text-muted\">/ salarié / an</small>";
                            $features = [
                                'Avantages Starter',
                                '4 conférences par an',
                                'Accès défis sportifs',
                                '4 RDV médicaux / salarié',
                                'Programme personnalisé'
                            ];
                        } elseif ($service['id'] == 3) {
                            $pricingText = "À partir de 50€ <small class=\"text-muted\">/ salarié / an</small>";
                            $features = [
                                'Avantages Basic',
                                '6 conférences par an',
                                'Communautés exclusives',
                                'RDV médicaux illimités',
                                'Chatbot signalement',
                                'Service sur mesure'
                            ];
                        }

                        ?>
                        <div class="col-md-4">
                            <div class="card h-100 <?php echo $cardClass; ?>">
                                <div class="card-header text-center bg-primary text-white">
                                    <h3 class="my-0"><?php echo htmlspecialchars($service['type']); ?></h3>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h4 class="card-title pricing-card-title text-center"><?php echo $pricingText; ?></h4>
                                    <ul class="list-unstyled mt-4 mb-4">
                                        <?php foreach ($features as $feature): ?>
                                            <li><i class="fas fa-check text-success me-2"></i> <?php echo htmlspecialchars($feature); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <div class="text-center mt-auto">
                                        <a href="modules/companies/quotes.php?offer=<?php echo $service['id']; ?>" class="<?php echo $buttonClass; ?>">Demander un devis</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col">
                        <p class="text-center">Aucune offre disponible pour le moment.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </section>


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


    <section class="cta py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 text-lg-start text-center">
                    <h2>Prêt à améliorer le bien-être au travail ?</h2>
                    <p class="lead mb-0">Contactez-nous dès aujourd'hui pour discuter de vos besoins spécifiques.</p>
                </div>
                <div class="col-lg-4 text-lg-end text-center mt-3 mt-lg-0">
                    <a href="modules/companies/contact.php" class="btn btn-light btn-lg">Nous contacter</a>
                </div>
            </div>
        </div>
    </section>

    <?php if ($isLoggedIn): ?>

        <section class="dashboard py-5">
            <div class="container">
                <div class="section-title text-center mb-5">
                    <h2>Votre espace personnel</h2>
                    <p class="lead">Accédez rapidement à vos fonctionnalités</p>
                </div>

                <div class="row g-4 <?php if ($userRole === 'entreprise') echo 'justify-content-center'; ?>">
                    <?php if ($userRole === 'entreprise'): ?>

                        <div class="col-md-4">
                            <div class="card dashboard-card h-100">
                                <div class="card-body">
                                    <h3 class="card-title"><i class="fas fa-file-contract text-primary me-2"></i> Contrats</h3>
                                    <p class="card-text">Gérez vos contrats et abonnements</p>
                                    <a href="modules/companies/contracts.php" class="btn btn-sm btn-outline-primary">Accéder</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card dashboard-card h-100">
                                <div class="card-body">
                                    <h3 class="card-title"><i class="fas fa-users text-primary me-2"></i> Salariés</h3>
                                    <p class="card-text">Gérez les accès de vos collaborateurs</p>
                                    <a href="modules/companies/employees.php" class="btn btn-sm btn-outline-primary">Accéder</a>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($userRole === 'salarie'): ?>

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

include_once __DIR__ . '/templates/footer.php';
?>