<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (isAuthenticated()) {
    $redirectUrl = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
    unset($_SESSION['redirect_after_login']);
    redirectTo($redirectUrl);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez entrer un email et un mot de passe.';
    } else if (login($email, $password, $rememberMe)) {
        $redirectUrl = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
        unset($_SESSION['redirect_after_login']);
        redirectTo($redirectUrl);
    } else {
        $error = 'Email ou mot de passe invalide.';
    }
}

if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = 'Votre session a expire. Veuillez vous reconnecter.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/admin.css">
</head>
<body class="text-center login-page">
    <main class="form-signin">
        <form method="post">
            <img class="mb-4" src="<?php echo ASSETS_URL; ?>/images/logo/goldOnWhite.jpg" alt="Logo" width="72" height="72">
            <h1 class="h3 mb-3 fw-normal">Business Care Admin</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="nom@exemple.com" required>
                <label for="email">Adresse email</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                <label for="password">Mot de passe</label>
            </div>
            
            <div class="checkbox mb-3">
                <label>
                    <input type="checkbox" name="remember_me" value="1"> Se souvenir de moi
                </label>
            </div>
            <button class="w-100 btn btn-lg btn-primary" type="submit">Connexion</button>
            <p class="mt-3"><a href="forgot-password.php">Mot de passe oublie ?</a></p>
            <p class="mt-5 mb-3 text-muted">&copy; <?php echo date('Y'); ?> Business Care</p>
        </form>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 