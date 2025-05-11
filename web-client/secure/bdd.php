<?php

$fake_users = [
    ['id' => 1, 'prenom' => 'Lucie', 'nom' => 'Manarin', 'email' => 'lucie@hotmail.com', 'password' => 'pass123'],
    ['id' => 2, 'prenom' => 'Menoua', 'nom' => 'Khatchatrian', 'email' => 'enoua@hotmail.com', 'password' => 'pass456'],
    ['id' => 3, 'prenom' => 'Frédéric', 'nom' => 'Sananes', 'email' => 'frederic@hotmail.com', 'password' => 'pass789'],
    ['id' => 4, 'prenom' => 'Christophe', 'nom' => 'Delon', 'email' => 'christophe@hotmail.com', 'password' => 'pass101'],
];


if (isset($_GET['search'])) {
    $search = $_GET['search'];

    
    file_put_contents('logs.txt', "Tentative d'injection: " . $search . "\n", FILE_APPEND);


    
    foreach ($fake_users as $user) {
        echo "<p>ID: " . $user['id'] . " - Prénom: " . $user['prenom'] . " - Nom: " . $user['nom'] . " - Email: " . $user['email'] . " - Mot de passe: " . $user['password'] . "</p>";
    }

    echo "<p><strong>Pour se connecter, allez sur: localhost/client/secure/login.php</strong></p>";
    
}
