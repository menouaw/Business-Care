<?php

// Inclure les fonctions nécessaires
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

requireRole(ROLE_ENTREPRISE);

$entrepriseId = $_SESSION['user_entreprise'];

$preselectedOfferKey = isset($_GET['offer']) ? trim($_GET['offer']) : null;

$available_services = [];
$query = "SELECT id, nom, description FROM services WHERE actif = 1 ORDER BY ordre";

try {
    $stmt = executeQuery($query);
    $servicesResult = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des services: " . $e->getMessage());
    flashMessage("Une erreur est survenue lors de la récupération des services disponibles. Veuillez réessayer plus tard.", "danger");
    $servicesResult = [];
}

if ($servicesResult) {
    foreach ($servicesResult as $service) {
        $available_services[$service['id']] = $service['nom'] . (isset($service['description']) && !empty($service['description']) ? ' - ' . $service['description'] : '');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = getFormData();

    $formData['entreprise_id'] = $entrepriseId;

    $result = requestCompanyQuote($formData);

    if ($result['success']) {
        $successMessage = urlencode($result['message']);
        $redirectUrl = WEBCLIENT_URL . '/modules/companies/quotes.php?quote_success=' . $successMessage;

        redirectTo($redirectUrl);
    } else {
        flashMessage($result['message'], 'danger');
        $submittedData = $formData;
    }
}

$pageTitle = "Demander un devis - Espace Entreprise";

include_once __DIR__ . '/../../templates/header.php';
?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Demander un devis</h1>
        <a href="#" onclick="history.back(); return false;" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour
        </a>
    </div>

    <?php

    if (isset($_GET['quote_success'])) {
        $successMessageDecoded = urldecode($_GET['quote_success']);
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
            . htmlspecialchars($successMessageDecoded)
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            . '</div>';
    }

    displayFlashMessages();
    ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Formuler votre demande</h5>
        </div>
        <div class="card-body">
            <form method="post" action="quotes.php">
                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="service_souhaite" class="form-label">Type de service/contrat souhaité*</label>
                        <select class="form-select" id="service_souhaite" name="service_souhaite" required>
                            <option value="" disabled <?php if (!$preselectedOfferKey && !isset($submittedData['service_souhaite'])) echo 'selected'; ?>>Sélectionnez une option...</option>
                            <?php foreach ($available_services as $key => $description): ?>
                                <?php
                                $isSelected = false;
                                if ($preselectedOfferKey == $key) {
                                    $isSelected = true;
                                } elseif (isset($submittedData['service_souhaite']) && $submittedData['service_souhaite'] == $key) {
                                    $isSelected = true;
                                }
                                ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" <?php if ($isSelected) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($description); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="nombre_salaries" class="form-label">Nombre approximatif de salariés concernés</label>
                        <input type="number" class="form-control" id="nombre_salaries" name="nombre_salaries" min="1" value="<?php echo htmlspecialchars($submittedData['nombre_salaries'] ?? ''); ?>" placeholder="Ex: 50">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description_besoin" class="form-label">Description détaillée de votre besoin*</label>
                    <textarea class="form-control" id="description_besoin" name="description_besoin" rows="5" required placeholder="Décrivez précisément vos attentes, les services spécifiques souhaités, la durée envisagée, etc."><?php echo htmlspecialchars($submittedData['description_besoin'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="contact_personne" class="form-label">Personne à contacter</label>
                    <input type="text" class="form-control" id="contact_personne" name="contact_personne" value="<?php echo htmlspecialchars($submittedData['contact_personne'] ?? $_SESSION['user_name']); ?>" placeholder="Nom et Prénom">
                </div>

                <div class="mb-3">
                    <label for="contact_email" class="form-label">Email de contact</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($submittedData['contact_email'] ?? $_SESSION['user_email']); ?>" placeholder="adresse@email.com">
                </div>

                <div class="mb-3">
                    <label for="contact_telephone" class="form-label">Téléphone de contact</label>
                    <input type="tel" class="form-control" id="contact_telephone" name="contact_telephone" pattern="^(0|\+33)[1-9]([-. ]?[0-9]{2}){4}$" value="<?php echo htmlspecialchars($submittedData['contact_telephone'] ?? ''); ?>" placeholder="01 23 45 67 89">
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Envoyer la demande de devis</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../../templates/footer.php';
?>