<?php
require_once '../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ADMIN);

$companyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($companyId <= 0) {
    flashMessage('Identifiant entreprise invalide.', 'danger');
    redirectTo(WEBADMIN_URL . '/modules/companies/index.php');
}

$company = companiesGetDetails($companyId);
if (!$company) {
    flashMessage("Entreprise non trouvée.", 'danger');
    redirectTo(WEBADMIN_URL . '/modules/companies/index.php');
}

$sizes = companiesGetSizes(); 

$errors = [];
$formData = $company;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleCsrfFailureRedirect($companyId, 'companies', 'modification entreprise');
    } else {
        $submittedData = [
            'nom'               => trim($_POST['nom'] ?? ''),
            'siret'             => trim($_POST['siret'] ?? ''),
            'adresse'           => trim($_POST['adresse'] ?? ''),
            'code_postal'       => trim($_POST['code_postal'] ?? ''),
            'ville'             => trim($_POST['ville'] ?? ''),
            'telephone'         => trim($_POST['telephone'] ?? ''),
            'email'             => trim($_POST['email'] ?? ''),
            'site_web'          => trim($_POST['site_web'] ?? ''),
            'taille_entreprise' => $_POST['taille_entreprise'] ?? null,
            'secteur_activite'  => trim($_POST['secteur_activite'] ?? ''),
            'date_creation'     => $_POST['date_creation'] ?? null
        ];
        
        $formData = array_merge($formData, $submittedData);

        if (empty($errors)) {
            $result = companiesSave($submittedData, $companyId);

            if ($result['success']) {
                flashMessage($result['message'], 'success');
                redirectTo(WEBADMIN_URL . "/modules/companies/view.php?id=" . $companyId);
            } else {
                $errors = array_merge($errors, $result['errors']);
            }
        }
    }
    if (!empty($errors)) {
        flashMessage($errors, 'danger');
    }
}

$pageTitle = "Modifier l'entreprise";
include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php echo displayFlashMessages(); ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view.php?id=<?php echo $companyId; ?>" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-eye"></i> Voir l'entreprise
                    </a>
                     <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Informations sur l'entreprise</div>
                <div class="card-body">
                    <form action="edit.php?id=<?php echo $companyId; ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($formData['nom'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="siret" class="form-label">SIRET <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="siret" name="siret" value="<?php echo htmlspecialchars($formData['siret'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="adresse" class="form-label">Adresse <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="adresse" name="adresse" rows="2"><?php echo htmlspecialchars($formData['adresse'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="code_postal" class="form-label">Code postal <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($formData['code_postal'] ?? ''); ?>">
                            </div>
                            <div class="col-md-8">
                                <label for="ville" class="form-label">Ville <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ville" name="ville" value="<?php echo htmlspecialchars($formData['ville'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Telephone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($formData['telephone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="site_web" class="form-label">Site web <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" id="site_web" name="site_web" value="<?php echo htmlspecialchars($formData['site_web'] ?? ''); ?>">
                            </div>
                             <div class="col-md-6">
                                <label for="date_creation" class="form-label">Date de creation <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date_creation" name="date_creation" value="<?php echo htmlspecialchars($formData['date_creation'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="taille_entreprise" class="form-label">Taille de l'entreprise <span class="text-danger">*</span></label>
                                <select class="form-select" id="taille_entreprise" name="taille_entreprise">
                                    <option value="">Selectionnez...</option>
                                    <?php
                                    foreach ($sizes as $size) {
                                        $selected = (isset($formData['taille_entreprise']) && $formData['taille_entreprise'] === $size) ? 'selected' : '';
                                        echo "<option value=\"$size\" $selected>" . htmlspecialchars($size) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="secteur_activite" class="form-label">Secteur d'activite <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="secteur_activite" name="secteur_activite" value="<?php echo htmlspecialchars($formData['secteur_activite'] ?? ''); ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        <a href="view.php?id=<?php echo $companyId; ?>" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
