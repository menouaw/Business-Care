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
    $user_found = false;

    foreach ($fake_users as $user) {
        if ($user['email'] == $email && $user['password'] == $password) {
            $user_found = true;
            $ip = $_SERVER['REMOTE_ADDR'];
            $host = gethostbyaddr($ip);
            $log = "Tentative de connexion réussie : IP: $ip, Host: $host, Utilisateur: " . $user['prenom'] . " " . $user['nom'] . "\n";
            file_put_contents("logs.txt", $log, FILE_APPEND);

            $title = "Piégé !";
            include 'header.php';
            echo "
            <div class='container text-center'>
                <div class='card p-4 my-4 shadow'>
                    <h1 class='display-4 text-danger mb-3'>Haha, tu as été piégé !</h1>
                    <p class='lead'><strong>Nous avons tes infos ! Tu devrais quitter cette page maintenant.</strong></p>
                    <div class='image-container d-flex justify-content-center flex-wrap my-3'>
                        <img src='images/poney.jpeg' alt='Poney piégé' class='img-fluid' />
                        <img src='images/image_honte.png' alt='Image de la honte' class='img-fluid' />
                    </div>
                </div>
            </div>";
            include 'footer.php';
            exit;
        }
    }

    if (!$user_found) {
        $title = "Erreur de connexion";
        include 'header.php';
        echo "
        <div class='container text-center'>
            <div class='card p-4 my-4 shadow'>
                <h1 class='text-danger'>Erreur : Identifiants incorrects.</h1>
                <a href='#' onclick='window.location.reload(); return false;' class='btn btn-primary mt-3'>Réessayer</a>
            </div>
        </div>";
        include 'footer.php';
    }
} else {
    $title = "Connexion";
    include 'header.php';
    echo "
    <div class='container'>
        <div class='card p-4 my-4 shadow'>
            <h2 class='text-center mb-4'>Page de Connexion Sécurisée</h2>
            <form method='POST'>
                <div class='form-group'>
                    <label for='email'>Email: </label><input type='email' name='email' id='email' class='form-control' required>
                </div>
                <div class='form-group'>
                    <label for='password'>Mot de passe: </label><input type='password' name='password' id='password' class='form-control' required>
                </div>
                <button type='submit' class='btn btn-primary btn-block'>Se connecter</button>
            </form>
        </div>
    </div>";
    include 'footer.php';
}
