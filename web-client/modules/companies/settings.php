<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';


requireRole(ROLE_ENTREPRISE);

$entrepriseId = $_SESSION['user_entreprise'];
$userId = $_SESSION['user_id'];

$companyDetails = getCompanyDetails($entrepriseId);
if (!$companyDetails) {
    flashMessage("Impossible de récupérer les informations de l'entreprise.", 'danger');
    redirectTo('index.php');
}

$currentUser = getUserById($userId);
if (!$currentUser) {
    flashMessage("Impossible de récupérer les informations de l'utilisateur.", 'danger');
    redirectTo('index.php'); // Ou une page d'erreur
}

$profileErrors = [];
$passwordErrors = [];
$profileSubmittedData = [
    'nom' => $currentUser['nom'] ?? '',
    'prenom' => $currentUser['prenom'] ?? '',
    'email' => $currentUser['email'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifie le token CSRF. La fonction redirige et arrête le script en cas d'échec.
    verifyCsrfToken();

    if (isset($_POST['update_profile'])) { // Identifier le formulaire soumis
        $profileSubmittedData = sanitizeInput($_POST);

        // Validation
        if (empty($profileSubmittedData['nom'])) {
            $profileErrors[] = "Le nom est obligatoire.";
        }
        if (empty($profileSubmittedData['prenom'])) {
            $profileErrors[] = "Le prénom est obligatoire.";
        }
        if (empty($profileSubmittedData['email']) || !filter_var($profileSubmittedData['email'], FILTER_VALIDATE_EMAIL)) {
            $profileErrors[] = "Une adresse email valide est obligatoire.";
        }

        if (empty($profileErrors)) {
            // Préparer les données pour la mise à jour
            $updateData = [
                'nom' => $profileSubmittedData['nom'],
                'prenom' => $profileSubmittedData['prenom'],
                'email' => $profileSubmittedData['email']
            ];
            $updateSuccess = updateUserProfile($userId, $updateData);

            if ($updateSuccess) {
                $_SESSION['user_name'] = $profileSubmittedData['prenom'] . ' ' . $profileSubmittedData['nom'];
                $_SESSION['user_email'] = $profileSubmittedData['email'];

                $successMessage = urlencode('Profil mis à jour avec succès.');
                $redirectUrl = 'settings.php?profile_success=' . $successMessage;
                redirectTo($redirectUrl);
            } else {
                $profileErrors[] = "Erreur lors de la mise à jour du profil.";
            }
        }
    } elseif (isset($_POST['change_password'])) { // Identifier le formulaire soumis
        $passwordData = sanitizeInput($_POST);
        $passwordErrors = []; // Réinitialiser les erreurs spécifiques à cette soumission

        // Validation
        if (empty($passwordData['current_password'])) {
            $passwordErrors[] = "Le mot de passe actuel est obligatoire.";
        }
        if (empty($passwordData['new_password'])) {
            $passwordErrors[] = "Le nouveau mot de passe est obligatoire.";
        }
        if ($passwordData['new_password'] !== $passwordData['confirm_password']) {
            $passwordErrors[] = "Les nouveaux mots de passe ne correspondent pas.";
        }
        if ($passwordData['current_password'] === $passwordData['new_password']) {
            $passwordErrors[] = "Le nouveau mot de passe doit être différent de l'ancien.";
        }

        if (!empty($passwordErrors)) {
            // Erreurs de validation: stocker dans flash et rediriger
            flashMessage(implode('<br>', $passwordErrors), 'danger');
            redirectTo('settings.php');
        } else {
            // Aucune erreur de validation, tenter de changer le mot de passe
            $changeSuccess = changeUserPassword($userId, $passwordData['current_password'], $passwordData['new_password']);
            // Rediriger dans tous les cas (succès ou échec, changeUserPassword a mis le flash)
            redirectTo('settings.php');
        }
    }
}

$pageTitle = "Paramètres - Espace Entreprise";
include_once __DIR__ . '/../../templates/header.php';

?>

<main class="container py-4">
    <!-- Zone d'affichage des messages flash -->
    <div class="container mt-0 mb-3 p-0">
        <?php
        if (isset($_SESSION['flash_messages']) && is_array($_SESSION['flash_messages'])):
            foreach ($_SESSION['flash_messages'] as $message):
                $type = $message['type'] ?? 'info'; // Type par défaut : info
                $text = $message['message'] ?? 'Message non défini';
                // Assurer que le type est valide pour les classes Bootstrap
                $valid_types = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
                if (!in_array($type, $valid_types)) {
                    $type = 'info';
                }
        ?>
                <div class="alert alert-<?= htmlspecialchars($type) ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($text) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
        <?php endforeach;
            // Vider les messages flash après affichage
            unset($_SESSION['flash_messages']);
        endif;
        ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Paramètres</h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
    </div>

    <style>
        #back-to-top {
            display: none !important;
        }
    </style>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Informations de l'Entreprise</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nom :</strong> <?= htmlspecialchars($companyDetails['nom'] ?? 'N/A') ?></p>
                    <p><strong>SIRET :</strong> <?= htmlspecialchars($companyDetails['siret'] ?? 'N/A') ?></p>
                    <p><strong>Adresse :</strong></p>
                    <address class="text-muted">
                        <?= !empty($companyDetails['adresse_ligne1']) ? htmlspecialchars($companyDetails['adresse_ligne1']) . '<br>' : '' ?>
                        <?= !empty($companyDetails['adresse_ligne2']) ? htmlspecialchars($companyDetails['adresse_ligne2']) . '<br>' : '' ?>
                        <?= htmlspecialchars($companyDetails['code_postal'] ?? '') ?> <?= htmlspecialchars($companyDetails['ville'] ?? '') ?><br>
                        <?= htmlspecialchars($companyDetails['pays'] ?? '') ?>
                    </address>
                    <p class="small text-muted">Pour modifier ces informations, veuillez contacter votre administrateur.</p>
                </div>
            </div>
        </div>

        <!-- Section Mon Profil -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Mon Profil</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($profileErrors)): ?>
                        <div class="alert alert-danger">
                            <strong>Erreur(s) :</strong>
                            <ul><?php foreach ($profileErrors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <form action="settings.php" method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($profileSubmittedData['prenom'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($profileSubmittedData['nom'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($profileSubmittedData['email'] ?? '') ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Section Changer Mot de Passe -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Changer le Mot de Passe</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($passwordErrors)): ?>
                        <div class="alert alert-danger">
                            <strong>Erreur(s) :</strong>
                            <ul><?php foreach ($passwordErrors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <form action="settings.php" method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.</div>

                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-warning">Changer le mot de passe</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Préférences</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Section à venir pour les préférences de notification, etc.</p>
                    <?php // TODO: Ajouter formulaire pour les préférences 
                    ?>
                </div>
            </div>
        </div>

    </div>

</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>