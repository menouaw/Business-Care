<?php
require_once __DIR__ . '/../../includes/init.php';

$pageTitle = "Nous Contacter - Business Care";

// Traitement potentiel du formulaire (à implémenter dans un script séparé ou ici)
$formMessage = '';
$formMessageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: Implémenter la logique d'envoi de l'email ou d'enregistrement du message
    // Exemple simple de message de succès (sans traitement réel pour l'instant)
    $formMessage = "Votre message a bien été envoyé. Nous vous répondrons bientôt.";
    $formMessageType = 'success';

    // Pour un vrai traitement, vous collecteriez les données $_POST['name'], $_POST['email'], etc.
    // les valideriez, et utiliseriez une fonction mail() ou une librairie comme PHPMailer.
}

// Inclure l'en-tête
include_once __DIR__ . '/../../templates/header.php';
?>

<main class="container py-5">
    <h1 class="text-center mb-5">Contactez-nous</h1>

    <div class="row g-5">
        <!-- Colonne Informations de contact -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h3 class="card-title mb-4"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Nos Coordonnées</h3>

                    <h5 class="mt-4">Siège Social (Paris 1er)</h5>
                    <p class="mb-1">
                        <i class="fas fa-location-dot me-2 text-muted"></i>
                        110, rue de Rivoli<br>
                        75001 Paris, France
                    </p>

                    <h5 class="mt-4">Contact Général</h5>
                    <p class="mb-1">
                        <i class="fas fa-phone me-2 text-muted"></i>
                        <a href="tel:+33123456789">+33 1 23 45 67 89</a> (Exemple)
                    </p>
                    <p class="mb-1">
                        <i class="fas fa-envelope me-2 text-muted"></i>
                        <a href="mailto:contact@businesscare.fr">contact@businesscare.fr</a> (Exemple)
                    </p>

                    <h5 class="mt-4">Horaires d'ouverture</h5>
                    <p>
                        <i class="fas fa-clock me-2 text-muted"></i>
                        Lundi - Vendredi : 9h00 - 18h00
                    </p>

                    <!-- Optionnel: Ajouter d'autres adresses (Troyes, Nice...) si pertinent -->
                    <!-- 
                    <h5 class="mt-4">Bureau de Troyes</h5>
                    <p class="mb-1"><i class="fas fa-location-dot me-2 text-muted"></i>13 rue Antoine Parmentier, Troyes</p>
                    <h5 class="mt-4">Bureau de Nice</h5>
                    <p class="mb-1"><i class="fas fa-location-dot me-2 text-muted"></i>8 rue Beaumont, Nice</p>
                     -->

                    <!-- Optionnel: Carte Google Maps -->
                    <div class="mt-4">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.816000156017!2d2.34300861567458!3d48.86169197928779!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66e1e11a5a0f7%3A0x4a5c3b8f3b6b4e8!2s110%20Rue%20de%20Rivoli%2C%2075001%20Paris!5e0!3m2!1sfr!2sfr!4v1678886543210!5m2!1sfr!2sfr" width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne Formulaire de contact -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h3 class="card-title mb-4"><i class="fas fa-paper-plane me-2 text-primary"></i>Envoyez-nous un message</h3>

                    <?php if ($formMessage): ?>
                        <div class="alert alert-<?= htmlspecialchars($formMessageType) ?>" role="alert">
                            <?= htmlspecialchars($formMessage) ?>
                        </div>
                    <?php endif; ?>

                    <form action="contact.php" method="post"> <!-- Soumet à la même page pour l'instant -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Votre Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Votre Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Sujet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Votre Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Envoyer le message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../../templates/footer.php';
?>