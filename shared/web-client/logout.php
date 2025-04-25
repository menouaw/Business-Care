<?php
/**
 * script de déconnexion
 *
 * ce script déconnecte l'utilisateur et le redirige vers la page d'accueil
 */

require_once __DIR__ . '/includes/init.php';

// Si l'utilisateur n'est pas connecté, rediriger vers la page d'accueil
if (!isAuthenticated()) {
    redirectTo(WEBCLIENT_URL);
}

// Déconnecter l'utilisateur
logout();

// Ajouter un message flash de confirmation
flashMessage('Vous avez été déconnecté avec succès', 'success');

// Rediriger vers la page d'accueil
redirectTo(WEBCLIENT_URL);
?>
