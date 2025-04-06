<?php
require_once '../../includes/page_functions/modules/companies.php'; 

requireRole(ROLE_ADMIN);

$pageTitle = "Ajouter une entreprise";
$errors = [];
$company = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateToken($_POST['csrf_token'] ?? '')) {
        handleCsrfFailureRedirect(0, 'companies', 'création entreprise');
    } else {
        $formData = getFormData();
        $data = $formData;

        if (empty($data['nom'])) {
            $errors[] = "Le nom de l'entreprise est obligatoire.";
        }
        
        if (!empty($data['siret'])) {
            if (!preg_match('/^[0-9]{14}$/', $data['siret'])) {
                $errors[] = "Le numéro SIRET doit contenir exactement 14 chiffres.";
            } else {
                 $existingCompany = fetchOne(TABLE_COMPANIES, 'siret = ?', '', [$data['siret']]);
                 if ($existingCompany) {
                     $errors[] = "Ce numéro SIRET est déjà utilisé par une autre entreprise.";
                 }
            }
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }
        
        if (!empty($data['date_creation']) && !strtotime($data['date_creation'])) {
             $errors[] = "La date de création n'est pas valide.";
        }

        if (empty($errors)) {
            $saveResult = companiesSave($data, 0);

            if ($saveResult['success']) {
                flashMessage($saveResult['message'] ?? 'Entreprise ajoutée avec succès !', 'success');
                redirectTo('view.php?id=' . $saveResult['newId']);
            } else {
                $errors = array_merge($errors, $saveResult['errors'] ?? ['Une erreur technique est survenue lors de l\'ajout.']);
                logSystemActivity('company_add_failure', '[ERROR] Échec ajout entreprise: ' . implode(', ', $errors));
                $company = $data;
            }
        }
    }

     if (!empty($errors)) {
        flashMessage($errors, 'danger');
     }
}

include '../../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../../templates/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $pageTitle; ?></h1>
                 <a href="index.php" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Retour à la liste">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>

             <?php echo displayFlashMessages(); ?>

            <div class="card">
                 <div class="card-header">
                    Informations sur l'entreprise
                </div>
                <div class="card-body">
                     <form action="add.php" method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($company['nom'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="siret" class="form-label">SIRET</label>
                            <input type="text" class="form-control" id="siret" name="siret" maxlength="14" placeholder="12345678901234" value="<?php echo htmlspecialchars($company['siret'] ?? ''); ?>">
                            <small class="form-text text-muted">14 chiffres.</small>
                        </div>

                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="3"><?php echo htmlspecialchars($company['adresse'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="code_postal" class="form-label">Code Postal</label>
                                <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($company['code_postal'] ?? ''); ?>">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="ville" class="form-label">Ville</label>
                                <input type="text" class="form-control" id="ville" name="ville" value="<?php echo htmlspecialchars($company['ville'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                             <div class="col-md-6 mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="0123456789" value="<?php echo htmlspecialchars($company['telephone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="contact@entreprise.com" value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_web" class="form-label">Site Web</label>
                            <input type="url" class="form-control" id="site_web" name="site_web" placeholder="https://www.entreprise.com" value="<?php echo htmlspecialchars($company['site_web'] ?? ''); ?>">
                        </div>
                        
                        <div class="row">
                             <div class="col-md-6 mb-3">
                                <label for="taille_entreprise" class="form-label">Taille de l'entreprise</label>
                                <select class="form-select" id="taille_entreprise" name="taille_entreprise">
                                    <option value="">-- Sélectionner une taille --</option>
                                    <?php foreach (COMPANY_SIZES as $size): ?>
                                    <option value="<?php echo $size; ?>" <?php echo ($company['taille_entreprise'] == $size) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($size); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="secteur_activite" class="form-label">Secteur d'activité</label>
                                <input type="text" class="form-control" id="secteur_activite" name="secteur_activite" value="<?php echo htmlspecialchars($company['secteur_activite'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="date_creation" class="form-label">Date de création</label>
                            <input type="date" class="form-control" id="date_creation" name="date_creation" value="<?php echo htmlspecialchars($company['date_creation'] ?? ''); ?>">
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">
                           <i class="fas fa-plus-circle me-1"></i> Ajouter l'entreprise
                        </button>
                         <a href="index.php" class="btn btn-secondary">
                            Annuler
                         </a>
                    </form>
                </div>
            </div>

            <?php include '../../templates/footer.php'; ?>
        </main>
    </div>
</div>
