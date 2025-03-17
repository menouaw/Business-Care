<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// deconnecte
logout();

// affiche un message de succès
flashMessage('Vous avez ete deconnecte avec succès.', 'success');
redirectTo('login.php');
exit; 