<?php
session_start();

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';


$personne_id = $_SESSION['user_id'] ?? null;

$pageTitle = "Chatbot - Business Care";

require_once __DIR__ . '/../../templates/header.php';
?>

<main class="container mt-4 mb-5"> <!-- Ajout de marges Bootstrap -->
    <h1><?php echo $pageTitle; ?></h1>

    <p>Posez vos questions ou faites un signalement anonyme.</p>
    <!-- TODO: Afficher le quota restant si applicable -->

    <div id="chat-container" class="card shadow-sm mb-4"> <!-- Style carte Bootstrap -->
        <div class="card-body">
            <div id="chatbox" class="border rounded p-3 mb-3" style="height: 400px; overflow-y: scroll;">
                <div class="message bot-message bg-light p-2 rounded mb-2" style="max-width: 80%;">Bonjour ! Comment puis-je vous aider aujourd'hui ?</div>
                <!-- Les messages apparaîtront ici -->
            </div>
            <div id="input-area" class="input-group">
                <input type="text" id="userInput" class="form-control" placeholder="Tapez votre message...">
                <button id="sendButton" class="btn btn-primary">Envoyer</button>
            </div>
        </div>
    </div>

    <button class="signalement-btn btn btn-warning d-block mx-auto mb-4" id="signalementBtn">Faire un signalement anonyme</button>

    <!-- Formulaire de signalement (modal Bootstrap serait mieux) -->
    <div id="signalementModal" class="card shadow-sm" style="display:none;">
        <div class="card-body">
            <h2 class="card-title">Signalement Anonyme</h2>
            <p>Votre signalement sera transmis de manière anonyme. Décrivez la situation :</p>
            <div class="mb-3">
                <textarea id="signalementDescription" class="form-control" rows="5"></textarea>
            </div>
            <button id="sendSignalementBtn" class="btn btn-danger me-2">Envoyer le Signalement</button>
            <button id="cancelSignalementBtn" class="btn btn-secondary">Annuler</button>
        </div>
    </div>

</main>

<?php
// Inclusion du footer
require_once __DIR__ . '/../../templates/footer.php';
?>