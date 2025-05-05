<?php
require_once __DIR__ . '/../../../init.php';



/**
 * Récupère la liste des factures pour une entreprise donnée.
 *
 * @param int $entreprise_id L'ID de l'entreprise.
 * @return array La liste des factures (ou un tableau vide si aucune).
 */
function getCompanyInvoices(int $entreprise_id): array
{
    if ($entreprise_id <= 0) {
        return [];
    }



    $sql = "SELECT 
                id, 
                numero_facture, 
                date_emission, 
                date_echeance,
                montant_total, 
                statut,
                date_paiement
                
            FROM 
                factures 
            WHERE 
                entreprise_id = :entreprise_id 
            ORDER BY 
                date_emission DESC";

    $stmt = executeQuery($sql, [
        ':entreprise_id' => $entreprise_id
    ]);

    return $stmt->fetchAll();
}

/**
 * Récupère les détails complets d'une facture spécifique appartenant à une entreprise.
 *
 * @param int $invoice_id L'ID de la facture.
 * @param int $company_id L'ID de l'entreprise pour vérification.
 * @return array|false Les détails de la facture ou false si non trouvée ou n'appartient pas à l'entreprise.
 */
function getInvoiceDetails(int $invoice_id, int $company_id): array|false
{
    if ($invoice_id <= 0 || $company_id <= 0) {
        return false;
    }



    return fetchOne('factures', 'id = :invoice_id AND entreprise_id = :company_id', [
        ':invoice_id' => $invoice_id,
        ':company_id' => $company_id
    ]);
}

/**
 * Génère le PDF d'une facture et l'envoie au navigateur.
 * Termine le script après l'envoi ou en cas d'erreur majeure.
 *
 * @param array $invoiceData Données de la facture récupérées de la base.
 * @return bool True si le PDF a été généré et envoyé avec succès, false en cas d'erreur.
 */
function generateInvoicePdf(array $invoiceData): bool
{




    global $WEBCLIENT_URL;
    $invoice_id = $invoiceData['id'] ?? 0;

    ob_start();
    try {

        /*
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        */


        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(defined('PDF_MARGIN_LEFT') ? PDF_MARGIN_LEFT : 15, defined('PDF_MARGIN_TOP') ? PDF_MARGIN_TOP : 15, defined('PDF_MARGIN_RIGHT') ? PDF_MARGIN_RIGHT : 15);
        $pdf->SetAutoPageBreak(TRUE, defined('PDF_MARGIN_BOTTOM') ? PDF_MARGIN_BOTTOM : 15);
        $pdf->AddPage();


        $pdf->SetFont('helvetica', '', 10);

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'FACTURE', 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, 'Numéro de Facture:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, htmlspecialchars($invoiceData['numero_facture'] ?? $invoiceData['id']), 0, 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, "Date d'Émission:", 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, htmlspecialchars(date('d/m/Y', strtotime($invoiceData['date_emission']))), 0, 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, "Date d'Échéance:", 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, $invoiceData['date_echeance'] ? htmlspecialchars(date('d/m/Y', strtotime($invoiceData['date_echeance']))) : 'N/A', 0, 1);

        $pdf->Ln(5);

        $currency = defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'EUR';

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, 'Montant HT:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, htmlspecialchars(number_format($invoiceData['montant_ht'] ?? 0, 2, ',', ' ')) . ' ' . $currency, 0, 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, 'TVA (' . htmlspecialchars($invoiceData['tva'] ?? '0') . '%):', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, htmlspecialchars(number_format(($invoiceData['montant_total'] ?? 0) - ($invoiceData['montant_ht'] ?? 0), 2, ',', ' ')) . ' ' . $currency, 0, 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, 'Montant Total TTC:', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, htmlspecialchars(number_format($invoiceData['montant_total'] ?? 0, 2, ',', ' ')) . ' ' . $currency, 0, 1);

        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, 'Statut:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, htmlspecialchars(ucfirst($invoiceData['statut'])), 0, 1);


        ob_end_clean();
        $pdf->Output('invoice_' . ($invoiceData['numero_facture'] ?? $invoiceData['id']) . '.pdf', 'D');
        exit;
    } catch (Exception $e) {
    }


    if (ob_get_level()) {
        ob_end_clean();
    }
    return false;
}



/**
 * Retourne la classe CSS Bootstrap pour le badge de statut de la facture.
 *
 * @param string $status Le statut de la facture.
 * @return string La classe CSS du badge.
 */
function getInvoiceStatusBadgeClass(string $status): string
{
    $status = strtolower($status);
    return match ($status) {
        INVOICE_STATUS_PAID => 'success',
        INVOICE_STATUS_PENDING => 'warning',
        INVOICE_STATUS_LATE => 'danger',
        INVOICE_STATUS_UNPAID => 'danger',
        INVOICE_STATUS_CANCELLED => 'secondary',
        default => 'light',
    };
}

/**
 * Génère un numéro de facture unique basé sur la date et un compteur.
 *
 * @return string Le numéro de facture généré (ex: F-YYYYMMDD-XXXX).
 */
function generateInvoiceNumber(): string
{
    $date = date('Ymd');
    $prefix = defined('INVOICE_PREFIX') ? INVOICE_PREFIX : 'F';


    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(numero_facture, '-', -1) AS UNSIGNED)) AS last_id
            FROM factures
            WHERE numero_facture LIKE :pattern";

    $stmt = executeQuery($sql, ['pattern' => "{$prefix}-{$date}-%"]);
    $result = $stmt->fetch();

    $lastId = $result['last_id'] ?? 0;
    $nextId = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

    return "{$prefix}-{$date}-{$nextId}";
}



/**
 * Tente de créer une session Stripe Checkout pour une facture donnée.
 * Gère les erreurs Stripe et logue les problèmes.
 *
 * @param array $invoice Les détails de la facture.
 * @param string $success_url L'URL de succès.
 * @param string $cancel_url L'URL d'annulation.
 * @param int $entreprise_id L'ID de l'entreprise (pour les métadonnées).
 * @return \Stripe\Checkout\Session|null L'objet session Stripe ou null en cas d'erreur.
 */
function createStripeCheckoutSessionForInvoice(array $invoice, string $success_url, string $cancel_url, int $entreprise_id): ?\Stripe\Checkout\Session
{




    try {
        $currency = strtolower(defined('DEFAULT_CURRENCY_CODE') ? DEFAULT_CURRENCY_CODE : 'eur');
        $amount_cents = (int) round(($invoice['montant_total'] ?? 0) * 100);

        if ($amount_cents <= 0) {
            throw new \Exception("Le montant de la facture est invalide pour le paiement.");
        }

        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => 'Facture ' . ($invoice['numero_facture'] ?? $invoice['id']),
                        'description' => 'Paiement facture Business Care #' . ($invoice['numero_facture'] ?? $invoice['id']),
                    ],
                    'unit_amount' => $amount_cents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'metadata' => [
                'invoice_id' => $invoice['id'] ?? null,
                'company_id' => $entreprise_id,
            ],
        ]);

        return $checkout_session;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        flashMessage("Erreur lors de l'initialisation du paiement : " . htmlspecialchars($e->getMessage()), "danger");
    } catch (\Exception $e) {
        flashMessage("Une erreur technique est survenue lors de la préparation du paiement.", "danger");
    }

    return null;
}

/**
 * Gère la création d'une session de paiement Stripe pour une facture. (Refactorisé)
 * Redirige vers Stripe ou affiche un message d'erreur et redirige.
 *
 * @param int $invoice_id L'ID de la facture.
 * @param int $entreprise_id L'ID de l'entreprise.
 * @return void
 */
function handleInvoiceCheckoutSession(int $invoice_id, int $entreprise_id): void
{


    $invoice = getInvoiceDetails($invoice_id, $entreprise_id);
    $payable_statuses = [INVOICE_STATUS_PENDING, INVOICE_STATUS_LATE, INVOICE_STATUS_UNPAID];


    if (!$invoice || !in_array($invoice['statut'], $payable_statuses)) {
        flashMessage("Facture non trouvée, déjà payée, ou non payable.", "warning");
        redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
        exit;
    }


    if (!defined('STRIPE_SECRET_KEY') || empty(STRIPE_SECRET_KEY)) {
        flashMessage("La configuration pour le paiement en ligne est incomplète. Veuillez contacter le support.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php?action=view&id=' . $invoice_id);
        exit;
    }



    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);


    $success_url = WEBCLIENT_URL . '/modules/companies/invoices.php?payment=success&session_id={CHECKOUT_SESSION_ID}';
    $cancel_url = WEBCLIENT_URL . '/modules/companies/invoices.php?payment=cancelled';


    $checkout_session = createStripeCheckoutSessionForInvoice($invoice, $success_url, $cancel_url, $entreprise_id);


    if ($checkout_session !== null && isset($checkout_session->url)) {

        header("HTTP/1.1 303 See Other");
        header("Location: " . $checkout_session->url);
        exit;
    } else {


        redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
        exit;
    }
}

/**
 * Gère la demande de téléchargement PDF d'une facture.
 * Tente de générer le PDF et l'envoie, sinon redirige avec une erreur.
 *
 * @param int $invoice_id L'ID de la facture.
 * @param int $entreprise_id L'ID de l'entreprise.
 * @return void
 */
function handleInvoiceDownload(int $invoice_id, int $entreprise_id): void
{


    $invoice = getInvoiceDetails($invoice_id, $entreprise_id);
    if (!$invoice) {
        flashMessage("Facture non trouvée ou accès refusé pour la génération PDF.", "warning");
        redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
        exit;
    }

    $pdfGenerated = generateInvoicePdf($invoice);


    if ($pdfGenerated === false) {
        flashMessage("Une erreur est survenue lors de la génération du fichier PDF. Veuillez réessayer ou contacter le support.", "danger");
        redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
        exit;
    }
}

/**
 * Récupère les données nécessaires pour l'affichage détaillé d'une facture.
 *
 * @param int $invoice_id L'ID de la facture.
 * @param int $entreprise_id L'ID de l'entreprise.
 * @return array|null Les données de la facture ou null si non trouvée/accès refusé.
 */
function getViewInvoiceData(int $invoice_id, int $entreprise_id): ?array
{
    $invoice = getInvoiceDetails($invoice_id, $entreprise_id);
    if (!$invoice) {
        flashMessage("Facture non trouvée ou accès refusé.", "warning");
        return null;
    }
    return $invoice;
}

/**
 * Récupère les données pour l'affichage de la liste des factures.
 *
 * @param int $entreprise_id L'ID de l'entreprise.
 * @return array La liste des factures.
 */
function getListInvoicesData(int $entreprise_id): array
{
    return getCompanyInvoices($entreprise_id);
}

/**
 * Gère le retour de Stripe après une tentative de paiement.
 * Vérifie le statut dans l'URL, affiche un message flash et redirige
 * pour nettoyer l'URL. Cette fonction termine le script via redirectTo().
 *
 * @return void
 */
function handleStripeReturn(): void
{
    $payment_status = filter_input(INPUT_GET, 'payment', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($payment_status) { // Si un statut de paiement est présent
        if ($payment_status === 'success') {
            // Note: Idéalement, vérifier la session_id ici via un webhook ou une query API avant de confirmer
            flashMessage("Votre tentative de paiement a été initiée. Le statut de la facture sera mis à jour après confirmation.", "info");
        } elseif ($payment_status === 'cancelled') {
            flashMessage("Le processus de paiement a été annulé.", "warning");
        }
        // Rediriger pour nettoyer l'URL des paramètres de paiement
        // Assurez-vous que WEBCLIENT_URL est défini comme constante globale
        redirectTo(WEBCLIENT_URL . '/modules/companies/invoices.php');
        // redirectTo contient exit(), donc le script s'arrête ici si $payment_status est trouvé.
    }
    // Si aucun statut de paiement n'est trouvé, la fonction ne fait rien et le script continue.
}
