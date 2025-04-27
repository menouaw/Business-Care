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



requireRole(ROLE_ENTREPRISE);


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



$entreprise_id = $_SESSION['user_entreprise'] ?? 0;
