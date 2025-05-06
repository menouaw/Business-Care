<?php

require_once __DIR__ . '/../../../init.php';


use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Exception\ApiErrorException;

/**
 * Valide le type de don.
 *
 * @param string $type Le type de don soumis.
 * @return string|null Message d'erreur si invalide, sinon null.
 */
function _validateDonationType(string $type): ?string
{
    if (!in_array($type, ['financier', 'materiel'])) {
        return "Type de don invalide.";
    }
    return null;
}

/**
 * Valide l'association sélectionnée pour le don.
 *
 * @param int|null $associationId ID de l'association soumise.
 * @return string|null Message d'erreur si invalide, sinon null.
 */
function _validateDonationAssociation(?int $associationId): ?string
{
    if (!$associationId) {
        return "Veuillez sélectionner une association.";
    }
    $assoExists = fetchOne('associations', 'id = ?', [$associationId]);
    if (!$assoExists) {
        return "L'association sélectionnée n'est pas valide.";
    }
    return null;
}

/**
 * Valide les détails spécifiques à un don financier (montant).
 *
 * @param array $formData Données du formulaire.
 * @return string|null Message d'erreur si invalide, sinon null.
 */
function _validateFinancialDonationDetails(array $formData): ?string
{
    $montantInput = str_replace(',', '.', $formData['montant'] ?? '');
    $montant = filter_var($montantInput, FILTER_VALIDATE_FLOAT);
    if ($montant === false || $montant < 0.01) {
        return "Montant invalide pour un don financier (minimum 0.01€).";
    }
    return null;
}

/**
 * Valide les détails spécifiques à un don matériel (description).
 *
 * @param array $formData Données du formulaire.
 * @return string|null Message d'erreur si invalide, sinon null.
 */
function _validateMaterialDonationDetails(array $formData): ?string
{
    $description = trim($formData['description'] ?? '');
    if (empty($description)) {
        return "Veuillez fournir une description pour un don matériel.";
    }
    return null;
}

/**
 * Valide les données du formulaire de don.
 * Refactorisé pour utiliser des fonctions d'aide dédiées.
 * 
 * @param array $formData Données du formulaire POST.
 * @return array Tableau des messages d'erreur (vide si valide).
 */
function validateDonationData(array $formData): array
{
    $errors = [];
    $type = $formData['donation_type'] ?? '';
    $associationId = filter_var($formData['association_id'] ?? '', FILTER_VALIDATE_INT);


    $typeError = _validateDonationType($type);
    if ($typeError) {
        $errors[] = $typeError;
    }


    $assoError = _validateDonationAssociation($associationId);
    if ($assoError) {
        $errors[] = $assoError;
    }


    if (!$typeError) {
        if ($type === 'financier') {
            $financialError = _validateFinancialDonationDetails($formData);
            if ($financialError) {
                $errors[] = $financialError;
            }
        } elseif ($type === 'materiel') {
            $materialError = _validateMaterialDonationDetails($formData);
            if ($materialError) {
                $errors[] = $materialError;
            }
        }
    }

    return $errors;
}

/**
 * Inserts a material donation into the database.
 */
function processMaterialDonation(int $userId, int $associationId, string $description): void
{
    try {
        $success = insertRow('dons', [
            'personne_id' => $userId,
            'association_id' => $associationId,
            'montant' => null,
            'type' => 'materiel',
            'description' => $description,
            'date_don' => date('Y-m-d'),
            'statut' => 'enregistré'
        ]);

        if ($success) {
            flashMessage("Votre don matériel a été enregistré. Merci !", 'success');

            createNotification($userId, "Don matériel enregistré", "Votre don matériel a été soumis.", 'info', WEBCLIENT_URL . '/modules/employees/donations.php');
        } else {
            throw new Exception("Erreur lors de l'insertion du don matériel.");
        }
    } catch (Exception $e) {
        error_log("Erreur enregistrement don matériel pour user ID $userId: " . $e->getMessage());
        flashMessage("Erreur lors de l'enregistrement de votre don matériel.", 'danger');
    }
    redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
}

/**
 * Prepares data and redirects to Stripe checkout for a financial donation.
 * Refactored to fetch association details internally.
 *
 * @param int    $userId         ID du donneur.
 * @param int    $associationId  ID de l'association.
 * @param float  $montant        Montant du don.
 * @param string $description    Description du don.
 */
function processFinancialDonation(int $userId, int $associationId, float $montant, string $description): void
{

    $association = fetchOne('associations', 'id = ?', [$associationId]);
    if (!$association) {

        error_log("FinancialDonation Error: Association not found. Association ID: " . $associationId . ", User ID: " . $userId);
        flashMessage('Error: Association details could not be retrieved.', 'danger');
        unset($_SESSION['pending_donation']);
        redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
        exit();
    }



    /*
    if (!isset($_SESSION['pending_donation'])) {
        $_SESSION['pending_donation'] = [
            'user_id' => $userId,
            'association_id' => $associationId,
            'montant' => $montant,
            'description' => $description,
            'type' => 'financier'
        ];
    }
    */


    Stripe::setApiKey(STRIPE_SECRET_KEY);


    $checkout_session = StripeCheckoutSession::create([
        'payment_method_types' => ['card', 'sepa_debit', 'bancontact'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Don à ' . htmlspecialchars($association['nom']),
                    'description' => $description ?: 'Don financier',
                ],
                'unit_amount' => intval($montant * 100),
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => WEBCLIENT_URL . '/modules/employees/donations.php?stripe_result=success&token=' . urlencode(generateToken('stripe_success')),
        'cancel_url' => WEBCLIENT_URL . '/modules/employees/donations.php?stripe_result=cancel&token=' . urlencode(generateToken('stripe_cancel')),
        'metadata' => [
            'donation_user_id' => $userId,
            'donation_association_id' => $associationId,
            'donation_type' => 'financier',
            'donation_description' => substr($description, 0, 500),
            'donation_amount_decimal' => sprintf('%.2f', $montant)
        ],
        'customer_email' => $_SESSION['user_email'] ?? null,
    ]);


    header("Location: " . $checkout_session->url);
    exit();
}

/**
 * Valide la requête de soumission de don (CSRF, données).
 *
 * @param array $postData Les données $_POST.
 * @return array|null Tableau d'erreurs si invalide, null si valide.
 */
function _validateDonationRequest(array $postData): ?array
{
    if (!validateToken($postData['csrf_token'] ?? '')) {
        return ["Action invalide ou jeton de sécurité expiré."];
    }

    $formData = sanitizeInput($postData);
    $errors = validateDonationData($formData);

    return !empty($errors) ? $errors : null;
}

/**
 * Traite un don après validation des données.
 *
 * @param int $userId ID de l'utilisateur.
 * @param array $formData Données du formulaire validées.
 */
function _processValidDonation(int $userId, array $formData): void
{
    $type = $formData['donation_type'];
    $associationId = (int)$formData['association_id'];
    $description = trim($formData['description'] ?? '');
    $montant = null;


    if ($type === 'financier') {
        $montantInput = str_replace(',', '.', $formData['montant']);
        $montant = (float)$montantInput;
        $descriptionForProcessing = $description ?: "Don financier";


        $_SESSION['pending_donation'] = [
            'user_id' => $userId,
            'association_id' => $associationId,
            'montant' => $montant,
            'description' => $description,
            'type' => 'financier'
        ];


        processFinancialDonation($userId, $associationId, $montant, $descriptionForProcessing);
    } elseif ($type === 'materiel') {

        processMaterialDonation($userId, $associationId, $description);
    }
}

/**
 * Gère la soumission du formulaire de nouveau don.
 * Refactorisé pour utiliser des fonctions d'aide pour la validation et le traitement.
 */
function handleNewDonation()
{

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    if (!isset($_POST['submit_donation'])) {
        return;
    }


    requireRole(ROLE_SALARIE);
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) {
        flashMessage("Erreur d'identification utilisateur pour le don.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
        exit;
    }


    $validationErrors = _validateDonationRequest($_POST);
    if ($validationErrors !== null) {
        foreach ($validationErrors as $error) {
            flashMessage($error, 'danger');
        }
        redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
        exit;
    }


    $formData = sanitizeInput($_POST);
    _processValidDonation($userId, $formData);
}

/**
 * Gère la logique de retour après une tentative de paiement Stripe.
 * Valide le token, traite le succès ou l'annulation, et gère les messages flash.
 * @return bool True si une redirection a été effectuée (retour Stripe traité), false sinon.
 */
function _handleStripeReturn(): bool
{
    if (!isset($_GET['stripe_result'], $_GET['token'])) {
        return false;
    }

    $result = $_GET['stripe_result'];
    $token = $_GET['token'];
    $tokenType = ($result === 'success') ? 'stripe_success' : 'stripe_cancel';

    if (!validateToken($token)) {
        flashMessage("URL de retour invalide ou expirée.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
        exit;
    }


    $redirectUrl = WEBCLIENT_URL . '/modules/employees/donations.php';

    if ($result === 'success' && isset($_SESSION['pending_donation'])) {
        $pendingDon = $_SESSION['pending_donation'];
        unset($_SESSION['pending_donation']);

        try {
            $success = insertRow('dons', [
                'personne_id' => $pendingDon['user_id'],
                'association_id' => $pendingDon['association_id'],
                'montant' => $pendingDon['montant'],
                'type' => $pendingDon['type'],
                'description' => $pendingDon['description'],
                'date_don' => date('Y-m-d'),
                'statut' => 'enregistre'
            ]);

            if ($success) {
                flashMessage("Votre don financier a été enregistré avec succès. Merci !", 'success');
                if (function_exists('createNotification')) {
                    createNotification(
                        $pendingDon['user_id'],
                        "Don enregistré",
                        "Votre don financier de " . formatMoney($pendingDon['montant']) . " a bien été enregistré.",
                        'success',
                        WEBCLIENT_URL . '/modules/employees/donations.php'
                    );
                }
            } else {
                throw new Exception("Erreur DB lors de l'insertion du don après retour Stripe.");
            }
        } catch (Exception $e) {
            error_log("Erreur enregistrement don post-Stripe pour user ID {$pendingDon['user_id']}: " . $e->getMessage());
            flashMessage("Une erreur est survenue lors de l'enregistrement final de votre don.", 'danger');
        }
    } elseif ($result === 'cancel') {
        flashMessage("Le processus de paiement du don a été annulé.", 'warning');
        unset($_SESSION['pending_donation']);
    } else {

        flashMessage("État de retour de paiement inconnu ou session expirée.", "warning");
        unset($_SESSION['pending_donation']);
    }


    redirectTo($redirectUrl);
    exit;
}

/**
 * Récupère les données pour l'affichage de la page des dons.
 *
 * @param int $userId ID de l'utilisateur.
 * @return array Données pour la vue (dons, associations).
 */
function _fetchDonationsPageData(int $userId): array
{
    $donations = [];
    $associations = [];
    try {
        $sql = "SELECT d.*, a.nom as association_nom
                FROM dons d
                LEFT JOIN associations a ON d.association_id = a.id
                WHERE d.personne_id = :user_id
                ORDER BY d.date_don DESC, d.created_at DESC";
        $stmt = executeQuery($sql, [':user_id' => $userId]);
        $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $associations = fetchAll('associations', '', 'nom ASC');
        if (empty($associations)) {
            flashMessage("Aucune association n'est disponible pour recevoir des dons actuellement.", "warning");
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des données pour la page dons (Utilisateur ID $userId): " . $e->getMessage());
        flashMessage("Impossible de charger les données de la page des dons.", "danger");

        $donations = [];
        $associations = [];
    }
    return ['donations' => $donations, 'associations' => $associations];
}

/**
 * Prépare les données nécessaires pour la page des dons (historique + formulaire).
 * Refactorisé pour extraire la gestion du retour Stripe et la récupération des données.
 *
 * @return array Données pour la vue.
 */
function setupDonationsPage()
{

    _handleStripeReturn();




    handleNewDonation();




    requireRole(ROLE_SALARIE);
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) {
        flashMessage("Utilisateur non identifié.", "danger");
        redirectTo(WEBCLIENT_URL . '/');
        exit;
    }

    $pageTitle = "Faire un Don / Mon Historique";
    $pageData = _fetchDonationsPageData($userId);

    return [
        'pageTitle' => $pageTitle,
        'donations' => $pageData['donations'],
        'associations' => $pageData['associations'],
        'csrf_token' => generateToken()
    ];
}
