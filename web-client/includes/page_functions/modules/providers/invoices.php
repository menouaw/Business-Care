<?php

require_once __DIR__ . '/../../../../includes/init.php';

/**
 * Récupère les factures associées à un prestataire, avec pagination.
 *
 * @param int $provider_id L'ID du prestataire.
 * @param int $limit Nombre d'éléments par page.
 * @param int $offset Décalage pour la pagination.
 * @return array Contient ['invoices' => array, 'total' => int].
 */
function getProviderInvoices(int $provider_id, int $limit = 10, int $offset = 0): array
{
    $result = ['invoices' => [], 'total' => 0];
    if ($provider_id <= 0) {
        return $result;
    }


    $sql_count = "SELECT COUNT(*) FROM factures_prestataires WHERE prestataire_id = :provider_id";
    $stmt_count = executeQuery($sql_count, [':provider_id' => $provider_id]);
    $total_invoices = $stmt_count ? (int)$stmt_count->fetchColumn() : 0;
    $result['total'] = $total_invoices;

    if ($total_invoices === 0) {
        return $result;
    }


    $sql = "SELECT 
                id, 
                numero_facture, 
                date_facture, 
                periode_debut, 
                periode_fin, 
                montant_total, 
                statut, 
                date_paiement
            FROM factures_prestataires
            WHERE prestataire_id = :provider_id
            ORDER BY date_facture DESC, id DESC
            LIMIT :limit OFFSET :offset";

    $stmt = executeQuery(
        $sql,
        [
            ':provider_id' => $provider_id,
            ':limit' => (int)$limit,
            ':offset' => (int)$offset
        ]
    );

    $result['invoices'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    return $result;
}

/**
 * Retourne la classe CSS Bootstrap pour le badge de statut de facture prestataire.
 *
 * @param string|null $status Le statut de la facture.
 * @return string La classe CSS du badge.
 */
function getInvoiceStatusBadgeClass(?string $status): string
{
    switch ($status) {
        case 'payee':
            return 'success';
        case 'en_attente':
        case 'generation_attendue':
            return 'warning';
        case 'impayee':
        case 'retard':
            return 'danger';
        case 'annulee':
            return 'secondary';
        default:
            return 'info';
    }
}

/**
 * Formate le statut de la facture prestataire pour l'affichage.
 *
 * @param string|null $status Le statut brut.
 * @return string Le statut formaté.
 */
function formatInvoiceStatus(?string $status): string
{
    if ($status === null) return 'Inconnu';
    return ucfirst(str_replace('_', ' ', $status));
}

/**
 * Récupère les détails complets d'une facture prestataire spécifique.
 *
 * @param int $invoice_id L'ID de la facture prestataire.
 * @param int $provider_id L'ID du prestataire pour vérification.
 * @return array|false Les détails de la facture ou false si non trouvée ou n'appartient pas au prestataire.
 */
function getProviderInvoiceDetails(int $invoice_id, int $provider_id): array|false
{
    if ($invoice_id <= 0 || $provider_id <= 0) {
        return false;
    }
    return fetchOne('factures_prestataires', 'id = :invoice_id AND prestataire_id = :provider_id', [
        ':invoice_id' => $invoice_id,
        ':provider_id' => $provider_id
    ]);
}

/**
 * Génère le PDF d'une facture prestataire et l'envoie au navigateur.
 * Adapté de la fonction pour les factures entreprise.
 *
 * @param array $invoiceData Données de la facture récupérées de la base (`factures_prestataires`).
 * @return bool True si le PDF a été généré et envoyé avec succès, false en cas d'erreur.
 */
function generateProviderInvoicePdf(array $invoiceData): bool
{
    global $WEBCLIENT_URL;
    $invoice_id = $invoiceData['id'] ?? 0;
    $numero_facture = $invoiceData['numero_facture'] ?? 'FP-' . $invoice_id;

    ob_start();
    try {



        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(defined('PDF_MARGIN_LEFT') ? PDF_MARGIN_LEFT : 15, defined('PDF_MARGIN_TOP') ? PDF_MARGIN_TOP : 15, defined('PDF_MARGIN_RIGHT') ? PDF_MARGIN_RIGHT : 15);
        $pdf->SetAutoPageBreak(TRUE, defined('PDF_MARGIN_BOTTOM') ? PDF_MARGIN_BOTTOM : 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 10);


        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'FACTURE PRESTATAIRE', 0, 1, 'C');
        $pdf->Ln(10);


        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, 'Numéro de Facture:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, htmlspecialchars($numero_facture), 0, 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, "Date de Facture:", 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, $invoiceData['date_facture'] ? htmlspecialchars(date('d/m/Y', strtotime($invoiceData['date_facture']))) : 'N/A', 0, 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, "Période Concernée:", 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $periode = ($invoiceData['periode_debut'] ? htmlspecialchars(date('d/m/Y', strtotime($invoiceData['periode_debut']))) : 'N/A')
            . ' - ' .
            ($invoiceData['periode_fin'] ? htmlspecialchars(date('d/m/Y', strtotime($invoiceData['periode_fin']))) : 'N/A');
        $pdf->Cell(0, 6, $periode, 0, 1);

        $pdf->Ln(5);


        $currency = defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'EUR';
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(40, 8, 'Montant Total:', 0, 0);
        $pdf->Cell(0, 8, htmlspecialchars(number_format($invoiceData['montant_total'] ?? 0, 2, ',', ' ')) . ' ' . $currency, 0, 1);

        $pdf->Ln(5);


        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, 'Statut:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, htmlspecialchars(formatInvoiceStatus($invoiceData['statut'])), 0, 1);

        if ($invoiceData['statut'] === 'payee' && !empty($invoiceData['date_paiement'])) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(40, 6, 'Payée le:', 0, 0);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, htmlspecialchars(date('d/m/Y', strtotime($invoiceData['date_paiement']))), 0, 1);
        }










        ob_end_clean();
        $pdf->Output('facture_prestataire_' . $numero_facture . '.pdf', 'D');
        exit;
    } catch (Exception $e) {
    }


    if (ob_get_level()) {
        ob_end_clean();
    }

    echo "Erreur lors de la génération du PDF.";
    exit;
}

/**
 * Gère la requête de téléchargement d'une facture prestataire.
 * Vérifie les paramètres GET, les permissions, et appelle la génération PDF si nécessaire.
 * Contient des exit() pour arrêter le script en cas de téléchargement ou d'erreur.
 *
 * @param int $provider_id L'ID du prestataire connecté.
 * @return void
 */
function handleProviderInvoiceDownloadRequest(int $provider_id)
{
    
    if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['id'])) {
        $invoice_id_to_download = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);


        if (!$invoice_id_to_download || $invoice_id_to_download <= 0) {

            die('ID de facture invalide pour le téléchargement.');
        }
        if ($provider_id <= 0) {

            die('Erreur : Prestataire non identifié.');
        }


        $invoiceData = getProviderInvoiceDetails($invoice_id_to_download, $provider_id);

        if ($invoiceData) {

            if (!generateProviderInvoicePdf($invoiceData)) {
            }

            exit;
        } else {


            die('Facture non trouvée ou accès non autorisé.');
        }
    }
}
