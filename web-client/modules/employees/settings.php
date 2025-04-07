<?php

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/employees.php';

// Vérifier si l'utilisateur est connecté et est un salarié
requireEmployeeLogin();

$employee_id = $_SESSION['user_id'];
$pageTitle = generatePageTitle('Mes Paramètres');

$employeeDetails = getEmployeeDetails($employee_id);
if (!$employeeDetails) {
    flashMessage("Impossible de récupérer les informations de l'utilisateur.", "danger");
    redirectTo(WEBCLIENT_URL . '/modules/employees/index.php');
}

$profileErrors = [];
$passwordErrors = [];
$profileSubmittedData = [
    'nom' => $employeeDetails['nom'] ?? '',
    'prenom' => $employeeDetails['prenom'] ?? '',
    'email' => $employeeDetails['email'] ?? '',
    'telephone' => $employeeDetails['telephone'] ?? '',
    'genre' => $employeeDetails['genre'] ?? '',
    'date_naissance' => $employeeDetails['date_naissance'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    if (isset($_POST['update_profile'])) {
        $profileSubmittedData = sanitizeInput($_POST);

        // validation
        if (empty($profileSubmittedData['nom'])) {
            $profileErrors[] = "le nom est obligatoire.";
        }
        if (empty($profileSubmittedData['prenom'])) {
            $profileErrors[] = "le prénom est obligatoire.";
        }
        if (empty($profileSubmittedData['email']) || !filter_var($profileSubmittedData['email'], FILTER_VALIDATE_EMAIL)) {
            $profileErrors[] = "une adresse email valide est obligatoire.";
        }
        if (!empty($profileSubmittedData['telephone']) && !preg_match('/^(0|\+33)[1-9]([-. ]?[0-9]{2}){4}$/', $profileSubmittedData['telephone'])) {
            $profileErrors[] = "le format du numéro de téléphone est invalide.";
        }
        if (!empty($profileSubmittedData['genre']) && !in_array($profileSubmittedData['genre'], ['F', 'M'])) {
            $profileErrors[] = "le genre doit être 'F' ou 'M'.";
        }

        if (empty($profileErrors)) {
            $updateData = [
                'nom' => $profileSubmittedData['nom'],
                'prenom' => $profileSubmittedData['prenom'],
                'email' => $profileSubmittedData['email'],
                'telephone' => $profileSubmittedData['telephone'],
                'genre' => $profileSubmittedData['genre'],
                'date_naissance' => $profileSubmittedData['date_naissance']
            ];

            $updateSuccess = updateEmployeeProfile($employee_id, $updateData);

            if ($updateSuccess) {
                $_SESSION['user_name'] = $profileSubmittedData['prenom'] . ' ' . $profileSubmittedData['nom'];
                $_SESSION['user_email'] = $profileSubmittedData['email'];
                redirectTo('settings.php');
            } else {
                $profileErrors[] = "erreur lors de la mise à jour du profil.";
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $passwordData = sanitizeInput($_POST);

        $passwordErrors = []; // Réinitialiser les erreurs spécifiques à cette soumission
        // validation
        if (empty($passwordData['current_password'])) {
            $passwordErrors[] = "le mot de passe actuel est obligatoire.";
        }
        if (empty($passwordData['new_password'])) {
            $passwordErrors[] = "le nouveau mot de passe est obligatoire.";
        }
        if ($passwordData['new_password'] !== $passwordData['confirm_password']) {
            $passwordErrors[] = "les nouveaux mots de passe ne correspondent pas.";
        }
        if ($passwordData['current_password'] === $passwordData['new_password']) {
            $passwordErrors[] = "le nouveau mot de passe doit être différent de l'ancien.";
        }

        if (!empty($passwordErrors)) {
            flashMessage(implode('<br>', $passwordErrors), 'danger');
            redirectTo('settings.php');
        } else {
            $changeSuccess = changeUserPassword($employee_id, $passwordData['current_password'], $passwordData['new_password']);
            redirectTo('settings.php');
        }
    } elseif (isset($_POST['update_settings'])) {
        $settingsData = sanitizeInput($_POST);

        $settings = [
            'langue' => $settingsData['langue'] ?? 'fr',
            'notif_email' => isset($settingsData['notif_email']) ? 1 : 0,
            'theme' => $settingsData['theme'] ?? 'light'
        ];

        $updateSuccess = updateEmployeeSettings($employee_id, $settings);

        if ($updateSuccess) {
            redirectTo('settings.php');
        } else {
            flashMessage("erreur lors de la mise à jour des préférences.", 'danger');
        }
    }
}

$preferences = [];
if (isset($employeeDetails['preferences'])) {
    $preferences = $employeeDetails['preferences'];
}

$pageTitle = "paramètres - espace salarié";
include_once __DIR__ . '/../../templates/header.php';
$csrfToken = generateCsrfToken(); // Générer le token une seule fois
?>

<main class="container py-4">
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
            unset($_SESSION['flash_messages']);
        endif;
        ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">paramètres</h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> retour
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
                    <h5 class="mb-0">informations de l'entreprise</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($employeeDetails['entreprise_id']) && !empty($employeeDetails['entreprise_nom'])): ?>
                        <p><strong>entreprise :</strong> <?= htmlspecialchars($employeeDetails['entreprise_nom']) ?></p>
                        <p class="small text-muted">pour toute question concernant votre entreprise, veuillez contacter votre responsable RH.</p>
                    <?php else: ?>
                        <p class="text-muted">vous n'êtes actuellement rattaché à aucune entreprise.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">mon profil</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($profileErrors)): ?>
                        <div class="alert alert-danger">
                            <strong>erreur(s) :</strong>
                            <ul class="mb-0"><?php foreach ($profileErrors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <form action="settings.php" method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($profileSubmittedData['prenom'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($profileSubmittedData['nom'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($profileSubmittedData['email'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="telephone" class="form-label">téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($profileSubmittedData['telephone'] ?? '') ?>">
                            <small class="form-text text-muted">format: 0XXXXXXXXX ou +33XXXXXXXXX</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="genre" class="form-label">genre</label>
                                <select class="form-select" id="genre" name="genre">
                                    <option value="" <?= empty($profileSubmittedData['genre']) ? 'selected' : '' ?>>non spécifié</option>
                                    <option value="F" <?= ($profileSubmittedData['genre'] ?? '') === 'F' ? 'selected' : '' ?>>femme</option>
                                    <option value="M" <?= ($profileSubmittedData['genre'] ?? '') === 'M' ? 'selected' : '' ?>>homme</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_naissance" class="form-label">date de naissance</label>
                                <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($profileSubmittedData['date_naissance'] ?? '') ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">enregistrer les modifications</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">changer le mot de passe</h5>
                </div>
                <div class="card-body">
                    <form action="settings.php" method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <div class="mb-3">
                            <label for="current_password" class="form-label">mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-warning">changer le mot de passe</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- préférences -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">préférences</h5>
                </div>
                <div class="card-body">
                    <form action="settings.php" method="POST">
                        <input type="hidden" name="update_settings" value="1">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <div class="mb-3">
                            <label for="langue" class="form-label">langue préférée</label>
                            <select class="form-select" id="langue" name="langue">
                                <option value="fr" <?= (($preferences['langue'] ?? 'fr') === 'fr') ? 'selected' : '' ?>>français</option>
                                <option value="en" <?= (($preferences['langue'] ?? '') === 'en') ? 'selected' : '' ?>>english</option>
                                <option value="es" <?= (($preferences['langue'] ?? '') === 'es') ? 'selected' : '' ?>>español</option>
                            </select>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="notif_email" name="notif_email" value="1" <?= (($preferences['notif_email'] ?? 0) == 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notif_email">recevoir des notifications par email</label>
                        </div>

                        <button type="submit" class="btn btn-success">enregistrer les préférences</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>