<?php


require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/page_functions/login.php';

if (isAuthenticated()) {
    if (isEntrepriseUser()) {
        redirectTo(WEBCLIENT_URL . '/modules/companies/index.php');
    } elseif (isSalarieUser()) {
        redirectTo(WEBCLIENT_URL . '/modules/employees/index.php');
    } elseif (isPrestataireUser()) {
        redirectTo(WEBCLIENT_URL . '/modules/providers/index.php');
    } else {
        redirectTo(WEBCLIENT_URL);
    }
}

$email = '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = processLoginForm($_POST);

    if ($result['success']) {
        if (!empty($result['redirect'])) {
            redirectTo($result['redirect']);
        } else {
            if (isEntrepriseUser()) {
                redirectTo(WEBCLIENT_URL . '/modules/companies/index.php');
            } elseif (isSalarieUser()) {
                redirectTo(WEBCLIENT_URL . '/modules/employees/index.php');
            } elseif (isPrestataireUser()) {
                redirectTo(WEBCLIENT_URL . '/modules/providers/index.php');
            } else {
                redirectTo(WEBCLIENT_URL);
            }
        }
    } else {
        $error = $result['message'];
        $email = $_POST['email'] ?? '';
    }
}

$transparentNav = false;

$pageTitle = "Connexion - Business Care";

include_once __DIR__ . '/templates/header.php';
?>

<main class="login-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <img src="<?= ASSETS_URL ?>/images/logo/noBgBlack.png" alt="Business Care" class="img-fluid mb-3" style="max-height: 60px;">
                            <h2 class="fw-bold">Connexion</h2>
                            <p class="text-muted">Accédez à votre espace personnel</p>
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= $success ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?= htmlspecialchars($email) ?>" required
                                        placeholder="Votre adresse email">
                                    <div class="invalid-feedback">
                                        Veuillez saisir une adresse email valide.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password"
                                        required placeholder="Votre mot de passe">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Veuillez saisir votre mot de passe.
                                    </div>
                                </div>
                            </div>

                            <!-- Add CSRF Token Field -->
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <!-- End CSRF Token Field -->

                            <div class="row mb-4">
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                        <label class="form-check-label" for="remember_me">
                                            Se souvenir de moi
                                        </label>
                                    </div>
                                </div>
                                <div class="col text-end">
                                    <a href="reset-password.php" class="text-decoration-none">Mot de passe oublié ?</a>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Connexion
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0">Vous n'avez pas de compte ? <a href="inscription.php" class="text-decoration-none">Créer un compte</a></p>
                        </div>
                    </div>
                </div>

                <div class="card mt-4 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="card-title">Besoin d'aide ?</h5>
                        <p class="card-text mb-0">Notre équipe support est disponible par téléphone au <strong>01 23 45 67 89</strong> ou par email à <a href="mailto:support@business-care.fr">support@business-care.fr</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include_once __DIR__ . '/templates/footer.php';
?>