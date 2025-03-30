<?php
require_once 'shared/web-client/config.php';
require_once 'shared/web-client/auth.php';

// Vérifier si l'utilisateur est déjà connecté
if (isAuthenticated()) {
        header('Location: dashboard.php');
        exit;
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    // Validation des champs
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Veuillez entrer votre adresse e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Veuillez entrer une adresse e-mail valide.';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Veuillez entrer votre mot de passe.';
    }
    
    // Si pas d'erreurs, tentative de connexion
    if (empty($errors)) {
        $success = login($email, $password, $rememberMe);
        
        if ($success) {
            // Redirection vers la page demandée ou le tableau de bord
            $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
            unset($_SESSION['redirect_after_login']);
            
            redirectTo($redirect);
        } else {
            $loginError = 'Identifiants incorrects. Veuillez réessayer.';
        }
    }
}

// Titre de la page
$pageTitle = 'Connexion';

// Description de la page pour les métadonnées
$pageDescription = 'Connectez-vous à votre compte Business Care pour accéder à votre espace client et gérer vos services.';

// Classes supplémentaires pour le body
$bodyClass = 'login-page bg-light';

// Inclure l'en-tête
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="<?php echo ASSETS_URL; ?>/images/logo-color.png" alt="Business Care Logo" class="img-fluid mb-3" style="max-width: 200px;">
                        <h1 class="h3 mb-3 fw-normal">Connexion à votre espace</h1>
                        <p class="text-muted">Entrez vos identifiants pour accéder à votre compte</p>
                    </div>
                    
                    <?php if (isset($loginError)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($loginError); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse e-mail</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                       id="email" name="email" placeholder="Votre adresse e-mail" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo htmlspecialchars($errors['email']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <label for="password" class="form-label">Mot de passe</label>
                                <a href="forgot-password.php" class="text-decoration-none small">Mot de passe oublié ?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                       id="password" name="password" placeholder="Votre mot de passe" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo htmlspecialchars($errors['password']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me" 
                                   <?php echo isset($rememberMe) && $rememberMe ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="remember_me">Se souvenir de moi</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary btn-lg" type="submit">Se connecter</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0">Vous n'avez pas de compte ? <a href="register.php" class="text-decoration-none">Créez un compte</a></p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="index.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le pied de page
include_once 'includes/footer.php';
?> 