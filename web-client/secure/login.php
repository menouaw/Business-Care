<?php
$fake_users = [
    ['id' => 1, 'prenom' => 'Lucie', 'nom' => 'Manarin', 'email' => 'lucie@hotmail.com', 'password' => 'pass123'],
    ['id' => 2, 'prenom' => 'Menoua', 'nom' => 'Khatchatrian', 'email' => 'menoua@hotmail.com', 'password' => 'pass456'],
    ['id' => 3, 'prenom' => 'Frédéric', 'nom' => 'Sananes', 'email' => 'frederic@hotmail.com', 'password' => 'pass789'],
    ['id' => 4, 'prenom' => 'Christophe', 'nom' => 'Delon', 'email' => 'christophe@hotmail.com', 'password' => 'pass101'],
];

function display_page_start($title)
{
    return "
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>$title</title>
    <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; background-color: #f8f9fa; }
        .card { width: 100%; max-width: 900px; }
        .image-container img { margin: 10px; max-width: 300px; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
";
}

function display_page_end()
{
    return "
    <script src='https://code.jquery.com/jquery-3.5.1.slim.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js'></script>
    <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js'></script>
</body>
</html>
";
}

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

            echo display_page_start("Piégé !");
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
            echo display_page_end();
            exit;
        }
    }

    if (!$user_found) {
        echo display_page_start("Erreur de connexion");
        echo "
        <div class='container text-center'>
            <div class='card p-4 my-4 shadow'>
                <h1 class='text-danger'>Erreur : Identifiants incorrects.</h1>
                <a href='#' onclick='window.location.reload(); return false;' class='btn btn-primary mt-3'>Réessayer</a>
            </div>
        </div>";
        echo display_page_end();
    }
} else {
    echo display_page_start("Connexion");
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
    echo display_page_end();
}
