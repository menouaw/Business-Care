<?php

/**
 * Espace Entreprise - Paramètres (Module Entreprise)
 *
 * Permet à l'utilisateur représentant l'entreprise de :
 * - Consulter les informations de l'entreprise.
 * - Mettre à jour ses propres informations de profil (nom, email).
 * - Changer son mot de passe.
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';
// Assurez-vous que user_functions.php est inclus si nécessaire (pour getUserById, etc.)
// require_once __DIR__ . '/../../includes/user_functions.php'; 

requireRole(ROLE_ENTREPRISE);

$entrepriseId = $_SESSION['user_entreprise'];
$userId = $_SESSION['user_id'];

// Récupérer les détails de l'entreprise
$companyDetails = getCompanyDetails($entrepriseId);
if (!$companyDetails) {
    flashMessage("Impossible de récupérer les informations de l'entreprise.", 'danger');
    // Rediriger vers l'index ou une page d'erreur appropriée
    redirectTo('index.php');
}

// Récupérer les détails de l'utilisateur courant (simplifié, adaptez selon votre structure)
// Supposons une fonction getUserById ou similaire
// $currentUser = getUserById($userId); 
// Pour l'exemple, utilisons les infos de session
$currentUser = [
    'id' => $userId,
    'nom_utilisateur' => $_SESSION['user_name'], // Adaptez si le nom complet est stocké différemment
    'email' => $_SESSION['user_email']
];

$profileErrors = [];
$passwordErrors = [];
$profileSubmittedData = $currentUser; // Pré-remplir avec les données actuelles

// --- Traitement des formulaires --- 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) { // Identifier le formulaire soumis
        // TODO: Ajouter la vérification CSRF Token
        $profileSubmittedData = sanitizeInput($_POST);

        // Validation
        if (empty($profileSubmittedData['nom_utilisateur'])) {
            $profileErrors[] = "Le nom d'utilisateur est obligatoire.";
        }
        if (empty($profileSubmittedData['email']) || !filter_var($profileSubmittedData['email'], FILTER_VALIDATE_EMAIL)) {
            $profileErrors[] = "Une adresse email valide est obligatoire.";
        }

        if (empty($profileErrors)) {
            // TODO: Appeler la fonction de mise à jour du profil utilisateur
            // $updateSuccess = updateUserProfile($userId, ['nom_utilisateur' => $profileSubmittedData['nom_utilisateur'], 'email' => $profileSubmittedData['email']]);
            $updateSuccess = true; // Simulé pour l'exemple

            if ($updateSuccess) {
                flashMessage('Profil mis à jour avec succès.', 'success');
                // Mettre à jour les infos en session si nécessaire
                $_SESSION['user_name'] = $profileSubmittedData['nom_utilisateur'];
                $_SESSION['user_email'] = $profileSubmittedData['email'];
                redirectTo('settings.php');
            } else {
                $profileErrors[] = "Erreur lors de la mise à jour du profil.";
            }
        }
    } elseif (isset($_POST['change_password'])) { // Identifier le formulaire soumis
        // TODO: Ajouter la vérification CSRF Token
        $passwordData = sanitizeInput($_POST);

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
        // TODO: Ajouter validation de complexité si nécessaire

        if (empty($passwordErrors)) {
            // TODO: Appeler la fonction de changement de mot de passe
            // $changeSuccess = changeUserPassword($userId, $passwordData['current_password'], $passwordData['new_password']);
            // Simulé pour l'exemple:
            if ($passwordData['current_password'] === 'password123') { // Simuler vérification mdp actuel
                $changeSuccess = true;
            } else {
                $changeSuccess = false;
                $passwordErrors[] = "Le mot de passe actuel est incorrect.";
            }

            if ($changeSuccess) {
                flashMessage('Mot de passe modifié avec succès.', 'success');
                redirectTo('settings.php');
            } else {
                if (empty($passwordErrors)) { // Ajouter une erreur générique si aucune spécifique n'a été définie
                    $passwordErrors[] = "Erreur lors de la modification du mot de passe.";
                }
            }
        }
    }
}

$pageTitle = "Paramètres - Espace Entreprise";
include_once __DIR__ . '/../../templates/header.php';

?>

<main class="container py-4">
    <h1 class="mb-4">Paramètres</h1>

    <?php displayFlashMessages(); ?>

    <div class="row g-4">
        <!-- Section Informations Entreprise -->
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
                        <?php // TODO: Ajouter input CSRF token: <input type="hidden" name="csrf_token" value="..."> 
                        ?>

                        <div class="mb-3">
                            <label for="nom_utilisateur" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="nom_utilisateur" name="nom_utilisateur" value="<?= htmlspecialchars($profileSubmittedData['nom_utilisateur'] ?? '') ?>" required>
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
                        <?php // TODO: Ajouter input CSRF token: <input type="hidden" name="csrf_token" value="..."> 
                        ?>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
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

        <!-- Section Préférences (Placeholder) -->
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