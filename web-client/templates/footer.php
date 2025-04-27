    <footer class="rounded-top">
        <div class="container-fluid py-3">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5>Business Care</h5>
                    <p class="mt-3">Solutions complètes pour améliorer la santé, le bien-être et la cohésion en milieu professionnel.</p>
                    <div class="mt-4 footer-contact">
                        <p><i class="fas fa-map-marker-alt me-2"></i> 110, rue de Rivoli, 75001 Paris</p>
                        <p><i class="fas fa-phone me-2"></i> 01 23 45 67 89</p>
                        <p><i class="fas fa-envelope me-2"></i> contact@business-care.fr</p>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 col-6">
                    <h5>Liens rapides</h5>
                    <ul class="mt-3 list-unstyled">
                        <li><a href="<?= WEBCLIENT_URL ?>">Accueil</a></li>
                        <li><a href="<?= WEBCLIENT_URL ?>/services.php">Services</a></li>
                        <li><a href="<?= WEBCLIENT_URL ?>/tarifs.php">Tarifs</a></li>
                        <li><a href="<?= WEBCLIENT_URL ?>/contact.php">Contact</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-6 col-6">
                    <h5>Ressources</h5>
                    <ul class="mt-3 list-unstyled">
                        <li><a href="<?= WEBCLIENT_URL ?>/blog.php">Blog</a></li>
                        <li><a href="<?= WEBCLIENT_URL ?>/faq.php">FAQ</a></li>
                        <li><a href="<?= WEBCLIENT_URL ?>/evenements.php">Évènements</a></li>
                        <li><a href="<?= WEBCLIENT_URL ?>/devenir-prestataire.php">Devenir prestataire</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-6">
                    <h5>Newsletter</h5>
                    <p class="mt-3">Recevez nos dernières actualités et offres spéciales.</p>
                    <form class="mt-3 newsletter-form">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Votre adresse email" aria-label="Votre adresse email">
                            <button class="btn btn-primary" type="submit">S'abonner</button>
                        </div>
                    </form>
                    <div class="footer-social mt-4">
                        <a href="#" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" target="_blank" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom py-2">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="mb-0">&copy; <?= date('Y') ?> Business Care. Tous droits réservés.</p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item"><a href="<?= WEBCLIENT_URL ?>/mentions-legales.php">Mentions légales</a></li>
                            <li class="list-inline-item"><a href="<?= WEBCLIENT_URL ?>/confidentialite.php">Politique de confidentialité</a></li>
                            <li class="list-inline-item"><a href="<?= WEBCLIENT_URL ?>/cgv.php">CGV</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="<?= ASSETS_URL ?>/js/client.js"></script>

    <?php if (isset($pageScripts) && is_array($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    </body>

    </html>