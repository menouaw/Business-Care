<?php
require_once __DIR__ . '/../../includes/init.php';

require_once __DIR__ . '/../../includes/page_functions/companies/employees.php';

requireRole(ROLE_ENTREPRISE);

$entreprise_id = $_SESSION['user_entreprise'] ?? 0;
$employee_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$employee_details = null;
$company_sites = [];
$pageTitle = "Modifier Salarié";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $employee_id_post = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);


    if ($employee_id_post) {
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_SPECIAL_CHARS);
        $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_SPECIAL_CHARS);
        $site_id = filter_input(INPUT_POST, 'site_id', FILTER_VALIDATE_INT);

        if ($nom && $prenom && $email) {
            $updateData = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone ?: null,
                'site_id' => $site_id ?: null
            ];

            if (updateEmployeeDetails($employee_id_post, $entreprise_id, $updateData)) {
                flashMessage('Informations du salarié mises à jour avec succès.', 'success');
            } else {
                flashMessage('Erreur lors de la mise à jour ou aucune modification détectée.', 'warning');
            }
        } else {
            flashMessage('Erreur de validation. Veuillez vérifier les champs obligatoires (Nom, Prénom, Email valide).', 'danger');
        }
    } else {
        flashMessage('ID du salarié manquant pour la mise à jour.', 'danger');
    }

    redirectTo(WEBCLIENT_URL . '/modules/companies/employees/index.php');
    exit;
}


if (!$employee_id) {
    flashMessage('ID du salarié manquant ou invalide.', 'danger');
    redirectTo(WEBCLIENT_URL . '/modules/companies/index.php');
    exit;
}

$employee_details = getEmployeeDetails($employee_id, $entreprise_id);

if (!$employee_details) {
    flashMessage('Salarié non trouvé ou n\'appartient pas à votre entreprise.', 'danger');
    redirectTo(WEBCLIENT_URL . '/modules/companies/index.php');
    exit;
}

$company_sites = getCompanySites($entreprise_id);
$pageTitle = "Modifier Salarié : " . htmlspecialchars($employee_details['prenom'] . ' ' . $employee_details['nom']);

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= WEBCLIENT_URL ?>/modules/companies/index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                    </a>
                </div>
            </div>

            <?php echo displayFlashMessages();
            ?>

            <form method="POST" action="<?= WEBCLIENT_URL ?>/modules/companies/edit.php?id=<?= $employee_id ?>">
                <!
                    <input type="hidden" name="employee_id" value="<?= (int)$employee_details['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($employee_details['prenom'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($employee_details['nom'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($employee_details['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($employee_details['telephone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="site_id" class="form-label">Site d'affectation</label>
                            <select class="form-select" id="site_id" name="site_id">
                                <option value="">
                                    <?php foreach ($company_sites as $site): ?>
                                <option value="<?= $site['id'] ?>" <?= ($employee_details['site_id'] == $site['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($site['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Statut</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars(ucfirst($employee_details['statut'] ?? '')) ?>" disabled readonly>
                            <small class="form-text text-muted">Le statut est modifié via les boutons Activer/Désactiver sur la liste.</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <button class="btn btn-primary" type="submit">Enregistrer les modifications</button>
                    <a href="<?= WEBCLIENT_URL ?>/modules/companies/index.php" class="btn btn-secondary">Annuler</a>
                    <!
                        </form>

        </main>
    </div>
</div>

<?php
include __DIR__ . '/../../templates/footer.php';
?>