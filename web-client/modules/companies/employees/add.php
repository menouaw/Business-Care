<?php
require_once __DIR__ . '/../../../includes/init.php';
require_once __DIR__ . '/../../../includes/page_functions/modules/companies/employees.php';

requireRole(ROLE_ENTREPRISE);

$entreprise_id = $_SESSION['user_entreprise'] ?? 0;
$company_sites = [];
$pageTitle = "Ajouter un Salarié";


if ($entreprise_id <= 0) {
    flashMessage("Impossible d'identifier votre entreprise.", "danger");
    redirectTo(WEBCLIENT_URL . '/modules/companies/dashboard.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_SPECIAL_CHARS);
    $site_id = filter_input(INPUT_POST, 'site_id', FILTER_VALIDATE_INT);

    $employeeData = [
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'telephone' => $telephone,
        'site_id' => $site_id
    ];

    $newEmployeeId = addEmployee($entreprise_id, $employeeData);

    if ($newEmployeeId) {


        redirectTo(WEBCLIENT_URL . '/modules/companies/employees/index.php');
        exit;
    } else {
    }
}


$company_sites = getCompanySites($entreprise_id);


include __DIR__ . '/../../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= WEBCLIENT_URL ?>/modules/companies/employees/index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages(); ?>

            <form method="POST" action="<?= WEBCLIENT_URL ?>/modules/companies/employees/add.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="prenom" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="site_id" class="form-label">Site d'affectation</label>
                        <select class="form-select" id="site_id" name="site_id">
                            <option value="">
                                <?php foreach ($company_sites as $site): ?>
                            <option value="<?= $site['id'] ?>" <?= (isset($_POST['site_id']) && $_POST['site_id'] == $site['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($site['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <hr class="my-4">

                <button class="btn btn-primary" type="submit">Ajouter le Salarié</button>
                <a href="<?= WEBCLIENT_URL ?>/modules/companies/employees/index.php" class="btn btn-secondary">Annuler</a>
            </form>

        </main>
    </div>
</div>

