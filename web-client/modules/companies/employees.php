<?php

require_once __DIR__ . '/../../includes/init.php';

require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ENTREPRISE);

$entrepriseId = $_SESSION['user_entreprise'];


$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'list';
$employeeId = null;
if (($action === 'modify' || $action === 'view') && isset($_GET['id'])) {
    $employeeId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
}

//pour les erreurs et les messages de succès
$errors = [];
$submittedData = [];
$employeeToEdit = null;
$employeeToView = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedData = sanitizeInput($_POST);


    if ($action === 'add') {
        if (empty($submittedData['nom'])) $errors[] = "Le nom est obligatoire.";
        if (empty($submittedData['prenom'])) $errors[] = "Le prénom est obligatoire.";
        if (empty($submittedData['email']) || !filter_var($submittedData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Une adresse email valide est obligatoire.";

        // Appel de la fonction de validation centralisée
        validateBirthDate($submittedData['date_naissance'] ?? null, $errors);

        if (empty($errors)) {
            $employeeData = [
                'nom' => $submittedData['nom'],
                'prenom' => $submittedData['prenom'],
                'email' => $submittedData['email'],
                'telephone' => $submittedData['telephone'] ?? null,
                'date_naissance' => !empty($submittedData['date_naissance']) ? $submittedData['date_naissance'] : null,
                'genre' => $submittedData['genre'] ?? null,
                'statut' => $submittedData['statut'] ?? 'actif'
            ];
            $newEmployeeId = addCompanyEmployee($entrepriseId, $employeeData);

            if ($newEmployeeId) {


                redirectTo('employees.php');
            } else {
                $flashMessages = getFlashMessages();
                $errorMessage = "Une erreur technique est survenue lors de l'ajout de l'employé.";
                if (!empty($flashMessages)) {
                    $lastMessage = end($flashMessages);
                    if (isset($lastMessage['message']) && ($lastMessage['type'] === 'danger' || $lastMessage['type'] === 'error')) {
                        $errorMessage = $lastMessage['message'];
                    }
                }
                $errors[] = $errorMessage;
            }
        }
    } elseif ($action === 'modify' && $employeeId) {
        if (empty($submittedData['nom'])) $errors[] = "Le nom est obligatoire.";
        if (empty($submittedData['prenom'])) $errors[] = "Le prénom est obligatoire.";
        if (empty($submittedData['email']) || !filter_var($submittedData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Une adresse email valide est obligatoire.";

        // Appel de la fonction de validation centralisée
        validateBirthDate($submittedData['date_naissance'] ?? null, $errors);

        if (empty($errors)) {
            $updateData = [
                'nom' => $submittedData['nom'],
                'prenom' => $submittedData['prenom'],
                'email' => $submittedData['email'],
                'telephone' => $submittedData['telephone'] ?? null,
                'date_naissance' => !empty($submittedData['date_naissance']) ? $submittedData['date_naissance'] : null,
                'genre' => $submittedData['genre'] ?? null,
                'statut' => $submittedData['statut'] ?? 'actif'
            ];
            $updateSuccess = updateCompanyEmployee($entrepriseId, $employeeId, $updateData);
            if ($updateSuccess) {
                redirectTo('employees.php?action=view&id=' . $employeeId);
            }
        }
        $employeeToEdit = getCompanyEmployeeDetails($entrepriseId, $employeeId);
        if (!$employeeToEdit) {
            flashMessage("Employé à modifier non trouvé lors du POST.", "danger");
            redirectTo('employees.php');
        }

        $submittedData = array_merge($employeeToEdit, $submittedData);
    }
}


$salaries = [];
$paginationHtml = '';
$statusFilter = isset($_GET['statut']) ? sanitizeInput($_GET['statut']) : 'actif';


if ($action === 'modify' && $employeeId && !$employeeToEdit) {
    $employeeToEdit = getCompanyEmployeeDetails($entrepriseId, $employeeId);
    if (!$employeeToEdit) {
        flashMessage("Employé à modifier non trouvé.", "danger");
        redirectTo('employees.php');
    }
    if (empty($submittedData)) $submittedData = $employeeToEdit;
} elseif ($action === 'view' && $employeeId) {
    $employeeToView = getCompanyEmployeeDetails($entrepriseId, $employeeId);
    if (!$employeeToView) {

        redirectTo('employees.php');
    }
} elseif ($action === 'list') {
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $statusFilter = isset($_GET['statut']) ? sanitizeInput($_GET['statut']) : 'actif';
    $limit = 15;

    $validStatusFilters = ['actif', 'inactif', 'suspendu', 'tous'];
    if (!in_array($statusFilter, $validStatusFilters)) $statusFilter = 'actif';

    $employeesData = getCompanyEmployees($entrepriseId, $currentPage, $limit, '', $statusFilter);
    $salaries = $employeesData['employees'] ?? [];
    $paginationHtml = $employeesData['pagination_html'] ?? '';
}


if ($action === 'add') {
    $pageTitle = "Ajouter un Salarié";
} elseif ($action === 'modify' && $employeeToEdit) {
    $pageTitle = "Modifier l'Employé : " . htmlspecialchars($employeeToEdit['prenom'] . ' ' . $employeeToEdit['nom']);
} elseif ($action === 'view' && $employeeToView) {
    $pageTitle = "Détails de l'Employé : " . htmlspecialchars($employeeToView['prenom'] . ' ' . $employeeToView['nom']);
} else {
    $pageTitle = "Gestion des Salariés";
}


include_once __DIR__ . '/../../templates/header.php';
?>

<main class="container py-4">

    <?php

    if ($action === 'add'):
    ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Ajouter un Nouveau Salarié</h1>
            <a href="employees.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Annuler et retourner à la liste
            </a>
        </div>

        <?php displayFlashMessages(); ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Erreur(s) :</strong>
                <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="employees.php?action=add" method="POST">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($submittedData['nom'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($submittedData['prenom'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($submittedData['email'] ?? '') ?>" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($submittedData['telephone'] ?? '') ?>" placeholder="Ex: 0612345678">
                        </div>
                        <div class="col-md-6">
                            <label for="date_naissance" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($submittedData['date_naissance'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="genre" class="form-label">Genre</label>
                            <select class="form-select" id="genre" name="genre">
                                <option value="" <?= empty($submittedData['genre']) ? 'selected' : '' ?>>Non spécifié</option>
                                <option value="M" <?= ($submittedData['genre'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin</option>
                                <option value="F" <?= ($submittedData['genre'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin</option>
                                <option value="Autre" <?= ($submittedData['genre'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="statut" class="form-label">Statut initial</label>
                            <select class="form-select" id="statut" name="statut">
                                <option value="actif" <?= (!isset($submittedData['statut']) || $submittedData['statut'] === 'actif') ? 'selected' : '' ?>>Actif</option>
                                <option value="inactif" <?= ($submittedData['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                                <option value="suspendu" <?= ($submittedData['statut'] ?? '') === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                            </select>
                            <div class="form-text">Le statut détermine si l'employé peut se connecter.</div>
                        </div>
                    </div>
                    <p class="form-text">Le mot de passe sera généré automatiquement...</p>
                    <div class="mt-4 d-flex justify-content-end">
                        <a href="employees.php" class="btn btn-secondary me-2">Annuler</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Ajouter le Salarié</button>
                    </div>
                </form>
            </div>
        </div>

    <?php

    elseif ($action === 'modify' && $employeeToEdit):
    ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Modifier l'Employé : <?= htmlspecialchars($employeeToEdit['prenom'] . ' ' . $employeeToEdit['nom']) ?></h1>
            <a href="employees.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Annuler et retourner à la liste
            </a>
        </div>

        <?php displayFlashMessages(); ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Erreur(s) de validation :</strong>
                <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="employees.php?action=modify&id=<?= $employeeId ?>" method="POST">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($submittedData['nom'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($submittedData['prenom'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($submittedData['email'] ?? '') ?>" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($submittedData['telephone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="date_naissance" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($submittedData['date_naissance'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="genre" class="form-label">Genre</label>
                            <select class="form-select" id="genre" name="genre">
                                <option value="" <?= empty($submittedData['genre']) ? 'selected' : '' ?>>Non spécifié</option>
                                <option value="M" <?= ($submittedData['genre'] ?? '') === 'M' ? 'selected' : '' ?>>Masculin</option>
                                <option value="F" <?= ($submittedData['genre'] ?? '') === 'F' ? 'selected' : '' ?>>Féminin</option>
                                <option value="Autre" <?= ($submittedData['genre'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <option value="actif" <?= ($submittedData['statut'] ?? '') === 'actif' ? 'selected' : '' ?>>Actif</option>
                                <option value="inactif" <?= ($submittedData['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                                <option value="suspendu" <?= ($submittedData['statut'] ?? '') === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                            </select>
                        </div>
                    </div>
                    <p class="form-text">Le mot de passe ne peut pas être modifié ici.</p>
                    <div class="mt-4 d-flex justify-content-end">
                        <a href="employees.php" class="btn btn-secondary me-2">Annuler</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>

    <?php

    elseif ($action === 'view' && $employeeToView):
    ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Détails de l'Employé</h1>
            <div>
                <a href="employees.php?action=modify&id=<?= $employeeId ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit me-1"></i> Modifier
                </a>
                <a href="employees.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                </a>
            </div>
        </div>

        <?php displayFlashMessages(); ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informations Générales</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><strong>Nom:</strong> <?= htmlspecialchars($employeeToView['nom']) ?></div>
                    <div class="col-md-6 mb-3"><strong>Prénom:</strong> <?= htmlspecialchars($employeeToView['prenom']) ?></div>
                    <div class="col-md-6 mb-3"><strong>Email:</strong> <?= htmlspecialchars($employeeToView['email']) ?></div>
                    <div class="col-md-6 mb-3"><strong>Téléphone:</strong> <?= htmlspecialchars($employeeToView['telephone'] ?? 'N/A') ?></div>
                    <div class="col-md-6 mb-3"><strong>Date de naissance:</strong> <?= $employeeToView['date_naissance_formatee'] ?? 'N/A' ?></div>
                    <div class="col-md-6 mb-3"><strong>Genre:</strong> <?= $employeeToView['genre_formate'] ?? 'N/A' ?></div>
                    <div class="col-md-6 mb-3"><strong>Statut:</strong> <?= $employeeToView['statut_badge'] ?? 'N/A' ?></div>
                    <div class="col-md-6 mb-3"><strong>Dernière connexion:</strong> <?= $employeeToView['derniere_connexion_formatee'] ?></div>
                    <div class="col-md-6 mb-3"><strong>Membre depuis:</strong> <?= isset($employeeToView['created_at']) ? formatDate($employeeToView['created_at'], 'd/m/Y') : 'N/A' ?></div>
                    <div class="col-md-6 mb-3"><strong>Dernière mise à jour:</strong> <?= isset($employeeToView['updated_at']) ? formatDate($employeeToView['updated_at']) : 'N/A' ?></div>
                </div>
            </div>
        </div>

    <?php

    else:
    ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Gestion des Salariés</h1>
            <div>
                <a href="index.php" class="btn btn-sm btn-outline-secondary ms-2">
                    <i class="fas fa-arrow-left me-1"></i> Retour au Tableau de bord
                </a>
            </div>
        </div>

        <?php displayFlashMessages(); ?>


        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form action="employees.php" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label for="statut" class="form-label">Filtrer par statut :</label>
                        <select name="statut" id="statut" class="form-select" onchange="this.form.submit()">
                            <option value="actif" <?= ($statusFilter === 'actif') ? 'selected' : '' ?>>Actifs</option>
                            <option value="inactif" <?= ($statusFilter === 'inactif') ? 'selected' : '' ?>>Inactifs</option>
                            <option value="suspendu" <?= ($statusFilter === 'suspendu') ? 'selected' : '' ?>>Suspendus</option>
                            <option value="tous" <?= ($statusFilter === 'tous') ? 'selected' : '' ?>>Tous</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>


        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <?php
                $tableTitle = "Liste des salariés";
                if ($statusFilter === 'actif') $tableTitle = "Liste des salariés actifs";
                elseif ($statusFilter === 'inactif') $tableTitle = "Liste des salariés inactifs";
                elseif ($statusFilter === 'suspendu') $tableTitle = "Liste des salariés suspendus";
                elseif ($statusFilter === 'tous') $tableTitle = "Liste de tous les salariés";
                ?>
                <h5 class="mb-0"><?= htmlspecialchars($tableTitle) ?></h5>
                <a href="employees.php?action=add" class="btn btn-success btn-sm">
                    <i class="fas fa-user-plus me-1"></i> Ajouter un Salarié
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($salaries)): ?>
                    <?php
                    $messageVide = "Aucun salarié correspondant au filtre trouvé.";

                    ?>
                    <p class="text-center text-muted my-5"><?= htmlspecialchars($messageVide) ?></p>
                <?php else: ?>
                    <?php if (!empty($paginationHtml) && isset($employeesData['pagination']['total'])) {
                        $startItem = (($employeesData['pagination']['current'] - 1) * $employeesData['pagination']['limit']) + 1;
                        $endItem = min($startItem + $employeesData['pagination']['limit'] - 1, $employeesData['pagination']['total']);
                        echo "<p class=\"text-muted mb-3\">Affichage des salariés " . $startItem . " à " . $endItem . " sur " . $employeesData['pagination']['total'] . "</p>";
                    } ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th class="text-center">Statut</th>
                                    <th class="text-center">Dern. Connexion</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salaries as $salarie): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($salarie['nom']) ?></td>
                                        <td><?= htmlspecialchars($salarie['prenom']) ?></td>
                                        <td><?= htmlspecialchars($salarie['email']) ?></td>
                                        <td><?= htmlspecialchars($salarie['telephone'] ?? 'N/A') ?></td>
                                        <td class="text-center"><?= $salarie['statut_badge'] ?? getStatusBadge($salarie['statut']) ?></td>
                                        <td class="text-center"><span title="<?= $salarie['derniere_connexion'] ?? 'Jamais' ?>">
                                                <?= $salarie['derniere_connexion_formatee'] ?? 'Jamais' ?>
                                            </span></td>
                                        <td class="text-center">
                                            <a href="employees.php?action=view&id=<?= $salarie['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir détails"><i class="fas fa-eye"></i></a>
                                            <a href="employees.php?action=modify&id=<?= $salarie['id'] ?>" class="btn btn-sm btn-outline-warning ms-1" title="Modifier"><i class="fas fa-edit"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($paginationHtml)): ?>
                        <nav> <?= $paginationHtml ?> </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif;
    ?>
</main>

<?php
include_once __DIR__ . '/../../templates/footer.php';
?>