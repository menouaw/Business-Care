<?php
require_once 'includes/init.php';

logout();

flashMessage('Vous avez ete deconnecte avec succes.', 'success');
redirectTo('login.php');
exit; 