<?php
require_once 'includes/init.php';

// Deconnexion de l'utilisateur
logout();

// affiche un message de succes
flashMessage('Vous avez ete deconnecte avec succes.', 'success');
redirectTo('login.php');
exit; 