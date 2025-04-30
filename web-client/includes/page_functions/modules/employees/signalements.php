<?php

require_once __DIR__ . '/../../../init.php';

/**
 * Traite la soumission du formulaire de nouveau signalement.
 */
function handleNewSignalement(): void
{
   
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit_signalement'])) {
        return;
    }

   
   
    if (!isset($_SESSION['user_id'])) {
       
        flashMessage("Vous devez être connecté pour effectuer un signalement.", "warning");
        redirectTo(WEBCLIENT_URL . '/login.php');
        return;
    }

   
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleClientCsrfFailureRedirect('envoyer un signalement', WEBCLIENT_URL . '/modules/employees/signalements.php');
        return;
    }

   
    $formData = getFormData();
    $sujet = trim($formData['sujet'] ?? '');
    $description = trim($formData['description'] ?? '');

   
    if (empty($description)) {
        flashMessage("La description du signalement ne peut pas être vide.", 'danger');
       
        redirectTo(WEBCLIENT_URL . '/modules/employees/signalements.php');
        return;
    }

   
    try {
        $success = insertRow('signalements', [
            'sujet' => $sujet ?: null,
            'description' => $description,
            'statut' => 'nouveau'
           
        ]);

        if ($success) {
            flashMessage("Votre signalement a été envoyé anonymement avec succès.", 'success');
           
           
        } else {
            throw new Exception("Erreur lors de l'enregistrement du signalement.");
        }
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement d'un signalement anonyme: " . $e->getMessage());
        flashMessage("Une erreur technique est survenue lors de l'envoi de votre signalement.", 'danger');
    }

   
    redirectTo(WEBCLIENT_URL . '/modules/employees/signalements.php');
}

/**
 * Prépare les données nécessaires pour la page des signalements.
 *
 * @return array Données pour la vue (titre, token CSRF).
 */
function setupSignalementPage(): array
{
   
    handleNewSignalement();

   
    if (!isset($_SESSION['user_id'])) {
        redirectTo(WEBCLIENT_URL . '/login.php');
        return [];
    }

   
    return [
        'pageTitle' => "Faire un Signalement Anonyme",
        'csrf_token' => generateToken()
    ];
}
