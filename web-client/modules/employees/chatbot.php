<?php
require_once __DIR__ . '/../../includes/page_functions/modules/employees/ia.php';

requireRole(ROLE_SALARIE);
if (!isset($_SESSION['chatbot_conversation'])) {
    $_SESSION['chatbot_conversation'] = [];
}

$pageTitle = "Assistance";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = sanitizeInput($_POST['form_action'] ?? '');

    if ($formAction === 'send_message') {
        $userMessageContent = sanitizeInput($_POST['user_message'] ?? '');
        if (!empty($userMessageContent)) {
            $_SESSION['chatbot_conversation'][] = ['role' => 'user', 'text_content' => $userMessageContent];
            $assistantReply = sendUserMessageToChatbot($_SESSION['chatbot_conversation']);

            if ($assistantReply !== null) {
                $_SESSION['chatbot_conversation'][] = ['role' => 'assistant', 'text_content' => $assistantReply];
            } else {
                flashMessage("Désolé, une erreur est survenue lors de la communication avec le chatbot. Veuillez réessayer.", "danger");
                array_pop($_SESSION['chatbot_conversation']);
            }
        } else {
            flashMessage("Veuillez entrer un message.", "warning");
        }
    } elseif ($formAction === 'clear_chat') {
        $_SESSION['chatbot_conversation'] = [];
        flashMessage("L'historique de la conversation a été effacé.", "info");
    }

    redirectTo('chatbot.php');
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <div>
                    <form method="POST" action="chatbot.php" style="display: inline;">
                        <input type="hidden" name="form_action" value="clear_chat">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-trash-alt me-1"></i> Effacer la conversation
                        </button>
                    </form>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body chatbot-container">
                    <?php if (empty($_SESSION['chatbot_conversation'])): ?>
                        <p class="text-center text-muted mt-3">Aucun message pour le moment. Commencez la conversation ci-dessous.</p>
                    <?php else: ?>
                        <?php foreach ($_SESSION['chatbot_conversation'] as $message): ?>
                            <div class="chat-message mb-3 p-2 rounded shadow-sm <?php echo ($message['role'] === 'user') ? 'bg-primary text-white ms-auto' : 'bg-light text-dark me-auto border'; ?>">
                                <strong class="d-block mb-1">
                                    <?php 
                                        $displayName = '';
                                        if ($message['role'] === 'user') {
                                            $displayName = 'Moi';
                                        } elseif ($message['role'] === 'assistant') {
                                            $displayName = 'Assistant';
                                        } else {
                                            $displayName = ucfirst($message['role']);
                                        }
                                        echo htmlspecialchars($displayName);
                                    ?>:
                                </strong>
                                <div class="chat-message-text-content"><?php echo nl2br(htmlspecialchars($message['text_content'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <form method="POST" action="chatbot.php">
                        <input type="hidden" name="form_action" value="send_message">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <div class="input-group">
                            <textarea name="user_message" class="form-control" placeholder="Écrivez votre message ici..." rows="3" required autofocus></textarea>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-paper-plane me-1"></i> Envoyer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const chatContainer = document.querySelector('.chatbot-container');
                    if (chatContainer) {
                        chatContainer.scrollTop = chatContainer.scrollHeight;
                    }
                });
            </script>

        </main>
    </div>
</div>