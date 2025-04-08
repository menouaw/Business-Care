<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php'; 

requireRole(ROLE_SALARIE);

$pageTitle = "Assistant Virtuel - Espace Salarié";

include_once __DIR__ . '/../../templates/header.php';

$csrfToken = $_SESSION['csrf_token'] ?? '';

?>

<main class="employee-chatbot-page py-4">
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h2 mb-0"><i class="fas fa-robot me-2"></i>Assistant Virtuel (Chatbot)</h1>
                <p class="text-muted mb-0">Posez vos questions à notre assistant virtuel.</p>
            </div>
            <div class="col-auto">
                <a href="<?= WEBCLIENT_URL ?>/modules/employees/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour Dashboard
                </a>
            </div>
        </div>

        <?php echo displayFlashMessages(); ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body" style="height: 500px; display: flex; flex-direction: column;">
                <div class="chat-history mb-3 p-3 bg-light rounded" style="flex-grow: 1; overflow-y: auto;">
                    <div class="d-flex mb-2">
                        <div class="bg-secondary text-white rounded p-2 me-auto" style="max-width: 70%;">
                            <strong>Assistant:</strong> Bonjour ! Comment puis-je vous aider aujourd'hui ? (Fonctionnalité en cours de développement)
                        </div>
                    </div>
                </div>

                <form action="" method="POST" id="chatbot-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <div class="input-group">
                        <input type="text" class="form-control" name="user_message" placeholder="Tapez votre message ici..." aria-label="Message utilisateur" required disabled>
                        <button class="btn btn-primary" type="submit" disabled>
                            <i class="fas fa-paper-plane"></i> Envoyer
                        </button>
                    </div>
                    <small class="text-muted">La fonctionnalité d'envoi de message est actuellement désactivée.</small>
                </form>
            </div>
            <div class="card-footer bg-white text-muted small">
                Note: Le chatbot est un outil d'assistance et ne remplace pas un avis professionnel qualifié pour des questions complexes ou médicales.
            </div>
        </div>

    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>