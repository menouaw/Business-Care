<?php
session_start();

// inclure les fichiers partagés
require_once __DIR__ . '/../../shared/web-client/config.php';
require_once __DIR__ . '/../../shared/web-client/db.php';
require_once __DIR__ . '/../../shared/web-client/functions.php';
require_once __DIR__ . '/../../shared/web-client/auth.php';

/**
 * Génère et stocke un jeton CSRF dans la session si nécessaire.
 *
 * @return string Le jeton CSRF.
 */
function generateCsrfToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    // echo "Generating/Returning Token: " . $_SESSION['csrf_token'] . "<br>"; 
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le jeton CSRF pour les requêtes POST.
 * En cas d'échec, affiche un message flash et redirige.
 */
function verifyCsrfToken()
{
    // Vérifie uniquement pour les requêtes POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // echo "Verifying Token - SESSION: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . " | POST: " . ($_POST['csrf_token'] ?? 'NOT SET') . "<br>";
        // Vérifie si le token est absent ou ne correspond pas
        if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals(trim($_SESSION['csrf_token']), trim($_POST['csrf_token']))) {
            // Log l'erreur pour le débogage peut être utile ici
            error_log("CSRF token validation failed. SESSION: " . ($_SESSION['csrf_token'] ?? 'Not Set') . " POST: " . ($_POST['csrf_token'] ?? 'Not Set'));

            // Nettoyer le token potentiellement invalide en session ? Non, car cela pourrait bloquer des re-soumissions légitimes après erreur.
            // unset($_SESSION['csrf_token']); 

            flashMessage("Erreur de sécurité (jeton invalide). Veuillez réessayer.", "danger");
            // Tente de rediriger vers la page précédente, sinon vers l'accueil.
            redirectTo($_SERVER['HTTP_REFERER'] ?? WEBCLIENT_URL . '/index.php');
            exit; // Arrête l'exécution pour empêcher le traitement du formulaire
        }
        // Optionnel: Invalider le token après une vérification réussie pour le rendre à usage unique
        // unset($_SESSION['csrf_token']);
    }
    // Pour les méthodes autres que POST, la vérification n'est pas effectuée par cette fonction.
}

// Assure la génération du token pour chaque chargement de page où init.php est inclus.
generateCsrfToken();
