<?php
require_once 'includes/page_functions/login.php';


/*
if (isAuthenticated() && !(isset($_GET['error']) && $_GET['error'] == 'permission_denied')) {
    $redirectUrl = $_SESSION['redirect_after_login'] ?? 'index.php';
    unset($_SESSION['redirect_after_login']);
    
    $baseUrl = defined('WEBADMIN_URL') ? WEBADMIN_URL : '';
    redirectTo($baseUrl . '/' . $redirectUrl);
}
*/


/*
$loginResult = processLoginForm();
$error = $loginResult['error'];

if ($loginResult['success']) {
    $redirectUrl = $_SESSION['redirect_after_login'] ?? 'index.php';
    unset($_SESSION['redirect_after_login']);
    redirectTo($redirectUrl);
}
*/

$error = null; 


if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = 'Votre session a expire. Veuillez vous reconnecter.';
} elseif (isset($_GET['error']) && $_GET['error'] == 'permission_denied') {
    $error = 'Accès refusé.';
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
    <style>
        .js-error-message {
            display: none;
        }
    </style>
</head>
<body class="text-center login-page">
    <main class="form-signin">
        
        <form id="login-form" method="post" novalidate> 
            <img class="mb-4" src="<?php echo ASSETS_URL; ?>/images/logo/goldOnWhite.jpg" alt="Logo" width="72" height="72">
            <h1 class="h3 mb-3 fw-normal">Business Care Admin</h1>

            
            <?php if ($error): ?>
                <div class="alert alert-danger php-error-message"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            
            <div id="js-error-output" class="alert alert-danger js-error-message" role="alert">
                
            </div>

            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="nom@exemple.com" required>
                <label for="email">Adresse email</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                <label for="password">Mot de passe</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary" type="submit">Connexion</button>
            <p class="mt-3"><a href="forgot-password.php">Mot de passe oublie ?</a></p>
            <p class="mt-5 mb-3 text-muted">&copy; <?php echo date('Y'); ?> Business Care</p>
        </form>
    </main>

    
    
    
    
    
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    
    
    <script type="module">
        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('login-form');
            const errorOutputDiv = document.getElementById('js-error-output');

            if (loginForm) {
                loginForm.addEventListener('submit', async (event) => {
                    event.preventDefault(); 

                    const emailInput = document.getElementById('email');
                    const passwordInput = document.getElementById('password');
                    const email = emailInput.value;
                    const password = passwordInput.value;

                    
                    if (!email || !password) {
                        showError("Veuillez entrer l'email et le mot de passe.");
                        return;
                    }

                    
                    hideError();

                    console.log(`Attempting Firebase sign-in for ${email}...`);

                    if (!window.firebaseAuth) {
                        console.error("Firebase Auth functions not available.");
                        showError("Erreur d'initialisation de l'authentification. Réessayez.");
                        return;
                    }

                    try {
                        const userCredential = await window.firebaseAuth.signIn(email, password);
                        console.log("Firebase sign-in successful:", userCredential.user.uid);
                        
                        
                        
                        

                    } catch (error) {
                        console.error("Firebase Sign-in failed:", error.code, error.message);
                        
                        let friendlyErrorMessage = "Échec de la connexion. Vérifiez vos identifiants.";
                        switch (error.code) {
                            case 'auth/invalid-credential':
                            case 'auth/user-not-found': 
                            case 'auth/wrong-password': 
                                friendlyErrorMessage = "Email ou mot de passe incorrect.";
                                break;
                            case 'auth/invalid-email':
                                friendlyErrorMessage = "Le format de l'adresse email est invalide.";
                                break;
                            case 'auth/user-disabled':
                                friendlyErrorMessage = "Ce compte utilisateur a été désactivé.";
                                break;
                            case 'auth/too-many-requests':
                                friendlyErrorMessage = "Trop de tentatives de connexion. Réessayez plus tard.";
                                break;
                            
                            default:
                                friendlyErrorMessage = `Une erreur s'est produite: ${error.message}`;
                        }
                        showError(friendlyErrorMessage);
                    }
                });
            }

            function showError(message) {
                if (errorOutputDiv) {
                    errorOutputDiv.textContent = message;
                    errorOutputDiv.style.display = 'block';
                }
            }

            function hideError() {
                if (errorOutputDiv) {
                    errorOutputDiv.style.display = 'none';
                    errorOutputDiv.textContent = '';
                }
            }
        });
    </script>
     -->

    
    <script type="module" src="<?php echo ASSETS_URL; ?>/js/login.js"></script>

    <?php require_once 'templates/footer.php';  ?>
</body>
</html> 