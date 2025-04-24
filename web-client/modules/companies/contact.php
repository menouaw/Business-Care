<?php
require_once __DIR__ . '/../../includes/init.php';
// require_once __DIR__ . '/../../includes/page_functions/companies/contact.php'; // On ne l'utilise pas

requireRole(ROLE_ENTREPRISE);

$entreprise_id = $_SESSION['user_entreprise'] ?? null;
$personne_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Utilisateur';
$user_email = $_SESSION['user_email'] ?? '';

$pageTitle = "Nous Contacter";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $sujet = filter_input(INPUT_POST, 'sujet', FILTER_SANITIZE_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($personne_id && !empty($sujet) && !empty($message)) {
        // ON NE SAUVEGARDE PAS EN BASE DE DONNEES
        // $ticketId = saveContactMessage($entreprise_id, $personne_id, $sujet, $message);

        // Simuler l'envoi (juste un message flash)
        flashMessage("Votre message a bien été envoyé (simulation). Notre équipe vous répondra dès que possible.", "success");
        // TODO: Implémenter l'envoi réel par email si nécessaire

    } else {
        flashMessage("Veuillez remplir tous les champs obligatoires.", "warning");
    }
    // Recharger la page pour afficher le message flash
    redirectTo(WEBCLIENT_URL . '/modules/companies/contact.php');
    exit;
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <a href="<?= WEBCLIENT_URL ?>/modules/companies/dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de Bord
                </a>
            </div>

            <?php echo displayFlashMessages(); ?>

            <!-- Nouvelle structure en deux colonnes -->
            <div class="row">
                <!-- Colonne Google Maps (Gauche) -->
                <div class="col-md-5 mb-4 mb-md-0">
                    <h5>Notre Siège Social</h5>
                    <p>110 Rue de Rivoli, 75001 Paris, France</p>
                    <div class="ratio ratio-16x9">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.815915847293!2d2.33941291567496!3d48.86191427928787!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66e23079e1d6b%3A0xcf34bd3f5d1e5a87!2s110%20Rue%20de%20Rivoli%2C%2075001%20Paris!5e0!3m2!1sfr!2sfr!4v1678886047123!5m2!1sfr!2sfr"
                            width="100%"
                            height="350"
                            style="border:0; border-radius: 0.375rem;" /* Ajout arrondi */
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>

                <!-- Colonne Formulaire (Droite) -->
                <div class="col-md-7">
                    <h5>Envoyez-nous un message</h5>
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="<?= WEBCLIENT_URL ?>/modules/companies/contact.php">
                                <input type="hidden" name="send_message" value="1">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()); ?>">

                                <div class="mb-3">
                                    <label for="user_info" class="form-label">Vos informations</label>
                                    <input type="text" class="form-control bg-light" id="user_info" value="<?= htmlspecialchars($user_name . ' (' . $user_email . ')') ?>" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="sujet" class="form-label">Sujet <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="sujet" name="sujet" required maxlength="255">
                                </div>

                                <div class="mb-3">
                                    <label for="message" class="form-label">Votre message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="message" name="message" rows="6" required></textarea> <!-- Réduit un peu la hauteur -->
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i> Envoyer le message
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div> <!-- Fin de la row -->

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>