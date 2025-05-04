<?php

require_once __DIR__ . '/../../../init.php';


use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Exception\ApiErrorException;

/**
 * Valide les données du formulaire de don.
 * @param array $formData Données du formulaire POST.
 * @return array Tableau des messages d'erreur (vide si valide).
 */
function validateDonationData(array $formData): array
{
    $errors = [];
    $type = $formData['donation_type'] ?? '';
    $associationId = filter_var($formData['association_id'] ?? '', FILTER_VALIDATE_INT);
    $description = trim($formData['description'] ?? '');

    if (!in_array($type, ['financier', 'materiel'])) {
        $errors[] = "Type de don invalide.";
    }
    if (!$associationId) {
        $errors[] = "Veuillez sélectionner une association.";
    } else {

        $assoExists = fetchOne('associations', 'id = ?', [$associationId]);
        if (!$assoExists) {
            $errors[] = "L'association sélectionnée n'est pas valide.";
        }
    }

    if ($type === 'financier') {
        $montantInput = str_replace(',', '.', $formData['montant'] ?? '');
        $montant = filter_var($montantInput, FILTER_VALIDATE_FLOAT);
        if ($montant === false || $montant <= 0) {
            $errors[] = "Montant invalide pour un don financier (minimum 0.01€).";
        }
    } elseif ($type === 'materiel') {
        if (empty($description)) {
            $errors[] = "Veuillez fournir une description pour un don matériel.";
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
 */
function processFinancialDonation(int $userId, int $associationId, float $montant, string $description, array $assoExists): void
{
    try {
        $_SESSION['pending_donation'] = [
            'user_id' => $userId,
            'association_id' => $associationId,
            'montant' => $montant,
            'description' => $description,
            'type' => 'financier'
        ];

        Stripe::setApiKey(STRIPE_SECRET_KEY);
        $checkout_session = StripeCheckoutSession::create([
            'payment_method_types' => ['card', 'sepa_debit', 'bancontact'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Don à ' . htmlspecialchars($assoExists['nom']),
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
    } catch (ApiErrorException $e) {
        error_log("Erreur API Stripe don pour user ID $userId: " . $e->getMessage());
        flashMessage("Impossible de procéder au paiement [API].", 'danger');
        unset($_SESSION['pending_donation']);
        redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
    } catch (Exception $e) {
        error_log("Erreur préparation paiement don pour user ID $userId: " . $e->getMessage());
        flashMessage("Impossible de procéder au paiement [Serveur].", 'danger');
        unset($_SESSION['pending_donation']);
        redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
    }
}

/**
 * Gère la soumission du formulaire de nouveau don.
 */
function handleNewDonation()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit_donation'])) return;
    requireRole(ROLE_SALARIE);
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) { /* handle error */
        redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
        return;
    }
    if (!validateToken($_POST['csrf_token'] ?? '')) { /* handle error */
        redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
        return;
    }

    $formData = getFormData();
    $errors = validateDonationData($formData);

    if (!empty($errors)) {
        foreach ($errors as $error) {
            flashMessage($error, 'danger');
        }
        redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
        return;
    }


    $type = $formData['donation_type'];
    $associationId = (int)$formData['association_id'];
    $description = trim($formData['description'] ?? '');
    $montant = null;


    $assoExists = fetchOne('associations', 'id = ?', [$associationId]);

    if (!$assoExists) {
        flashMessage('Erreur interne: Association non trouvée après validation.', 'danger');
        redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
        return;
    }

    if ($type === 'financier') {
        $montantInput = str_replace(',', '.', $formData['montant']);
        $montant = (float)$montantInput;
        $description = $description ?: "Don financier";


        processFinancialDonation($userId, $associationId, $montant, $description, $assoExists);
    } elseif ($type === 'materiel') {

        processMaterialDonation($userId, $associationId, $description);
    }


    redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
}

/**
 * Prépare les données nécessaires pour la page des dons (historique + formulaire).
 * Gère aussi les messages de retour Stripe.
 *
 * @return array Données pour la vue.
 */
function setupDonationsPage()
{

    if (isset($_GET['stripe_result'], $_GET['token'])) {
        $tokenType = $_GET['stripe_result'] === 'success' ? 'stripe_success' : 'stripe_cancel';
        if (!validateToken($_GET['token'])) {
            flashMessage("URL de retour invalide ou expirée.", "danger");
            redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
            exit;
        }
    }

    if (isset($_GET['stripe_result'])) {
        $result = $_GET['stripe_result'];
        unset($_GET['stripe_result']);

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
                    createNotification(
                        $pendingDon['user_id'],
                        "Don enregistré",
                        "Votre don financier de " . formatMoney($pendingDon['montant']) . " a bien été enregistré.",
                        'success',
                        WEBCLIENT_URL . '/modules/employees/donations.php'
                    );
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
        }

        redirectTo(WEBCLIENT_URL . '/modules/employees/donations.php');
        exit;
    }



    handleNewDonation();


    requireRole(ROLE_SALARIE);
    $userId = $_SESSION['user_id'] ?? 0;

    if ($userId <= 0) {
        flashMessage("Utilisateur non identifié.", "danger");
        redirectTo(WEBCLIENT_URL . '/');
        return [];
    }

    $pageTitle = "Faire un Don / Mon Historique";
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
        flashMessage("Impossible de charger la page des dons.", "danger");
        $donations = [];
        $associations = [];
    }

    return [
        'pageTitle' => $pageTitle,
        'donations' => $donations,
        'associations' => $associations,
        'csrf_token' => generateToken()
    ];
}
