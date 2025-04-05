<?php

/**
 * Espace Entreprise - Demande de Devis (Module Entreprise)
 *
 * Ce fichier gère le formulaire de demande de devis pour les entreprises clientes.
 * Il permet à une entreprise connectée de :
 * - Visualiser un formulaire pour spécifier ses besoins (type de service, nb salariés, description).
 * - Soumettre une demande de devis qui sera traitée par la fonction `requestCompanyQuote`.
 *
 * Processus :
 * 1. Vérifie l'authentification et le rôle ROLE_ENTREPRISE.
 * 2. Récupère l'ID de l'entreprise depuis la session.
 * 3. Définit les services disponibles pour la sélection (actuellement en dur).
 * 4. Traite la soumission du formulaire (POST) :
 *    - Valide les données.
 *    - Appelle `requestCompanyQuote` pour enregistrer la demande.
 *    - Affiche un message de succès ou d'erreur via flash messages.
 *    - Redirige en cas de succès.
 * 5. Affiche le formulaire de demande de devis (GET ou après échec POST).
 *
 * Accès restreint aux utilisateurs avec le rôle ROLE_ENTREPRISE.
 */

// Inclure les fonctions nécessaires
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/modules/companies.php';

// Vérifier que l'utilisateur est authentifié et a le rôle entreprise
requireRole(ROLE_ENTREPRISE);

// Récupérer l'ID de l'entreprise depuis la session
$entrepriseId = $_SESSION['user_entreprise'];

// Récupérer la liste des types de contrats ou services disponibles pour un devis
// (Simplifié ici, pourrait être récupéré depuis la DB)
$available_services = [
    'Starter Pack' => 'Pour les petites équipes (jusqu\'à 30 salariés)',
    'Basic Pack' => 'Solution équilibrée (jusqu\'à 250 salariés)',
    'Premium Pack' => 'Offre complète pour grandes entreprises (251+ salariés)',
    'Consultation Ponctuelle' => 'Besoin spécifique hors contrat',
    'Événement Sur Mesure' => 'Organisation d\'un événement spécifique'
];

// Traitement du formulaire de demande de devis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $formData = getFormData();

    // Ajouter l'ID de l'entreprise aux données
    $formData['entreprise_id'] = $entrepriseId;

    // Appeler la fonction pour traiter la demande de devis
    $result = requestCompanyQuote($formData);

    if ($result['success']) {
        flashMessage($result['message'], 'success');
        // Rediriger vers une page de confirmation ou la liste des devis
        redirectTo(WEBCLIENT_URL . '/modules/companies/quotes.php'); // Ou une page listant les devis
    } else {
        // Afficher les erreurs
        flashMessage($result['message'], 'danger');
        // Les données soumises peuvent être ré-affichées dans le formulaire si nécessaire
        $submittedData = $formData;
    }
}

// Définir le titre de la page
$pageTitle = "Demander un devis - Espace Entreprise";

// Inclure l'en-tête
include_once __DIR__ . '/../../templates/header.php';
?>

<main class="container py-4">
    <h1 class="mb-4">Demander un devis</h1>

    <?php echo displayFlashMessages(); ?>

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
                            <option value="" disabled selected>Sélectionnez une option...</option>
                            <?php foreach ($available_services as $key => $description): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (isset($submittedData['service_souhaite']) && $submittedData['service_souhaite'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($key); ?> (<?php echo htmlspecialchars($description); ?>)
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
                    <input type="tel" class="form-control" id="contact_telephone" name="contact_telephone" value="<?php echo htmlspecialchars($submittedData['contact_telephone'] ?? ''); ?>" placeholder="01 23 45 67 89">
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