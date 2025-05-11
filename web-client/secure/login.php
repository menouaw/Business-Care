<?php
$fake_users = [
    ['id' => 1, 'prenom' => 'Lucie', 'nom' => 'Manarin', 'email' => 'lucie@hotmail.com', 'password' => 'pass123'],
    ['id' => 2, 'prenom' => 'Menoua', 'nom' => 'Khatchatrian', 'email' => 'menoua@hotmail.com', 'password' => 'pass456'],
    ['id' => 3, 'prenom' => 'Frédéric', 'nom' => 'Sananes', 'email' => 'frederic@hotmail.com', 'password' => 'pass789'],
    ['id' => 4, 'prenom' => 'Christophe', 'nom' => 'Delon', 'email' => 'christophe@hotmail.com', 'password' => 'pass101'],
];

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    foreach ($fake_users as $user) {
        if ($user['email'] == $email && $user['password'] == $password) {

            // Enregistrer l'IP, le nom de la machine et d'autres infos dans logs.txt
            $ip = $_SERVER['REMOTE_ADDR'];
            $host = gethostbyaddr($ip);
            $log = "Tentative de connexion réussie : IP: $ip, Host: $host, Utilisateur: " . $user['prenom'] . " " . $user['nom'] . "\n";
            file_put_contents("logs.txt", $log, FILE_APPEND);

            // Afficher le message et l'image
            echo "<h1>Haha, tu as été piégé !</h1>";
            echo "<p><strong>Nous avons tes infos ! Tu devrais quitter cette page maintenant.</strong></p>";
            echo "<img src='images/poney.jpeg' alt='Poney piégé' />";
            echo "<img src='images/image_honte.png' alt='Image de la honte' />";

            exit;
        }
    }

    echo "<h1>Erreur : Identifiants incorrects.</h1>";
} else {
    echo '<form method="POST">
            <label for="email">Email: </label><input type="text" name="email" id="email" required><br>
            <label for="password">Mot de passe: </label><input type="password" name="password" id="password" required><br>
            <input type="submit" value="Se connecter">
          </form>';
}
