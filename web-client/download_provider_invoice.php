<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/page_functions/modules/providers/invoices.php';

// Exiger le rôle prestataire
requireRole(ROLE_PRESTATAIRE);

// Récupérer l'ID du prestataire connecté
$provider_id = $_SESSION['user_id'] ?? 0;

// Récupérer et valider l'ID de la facture depuis l'URL
$invoice_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$invoice_id || $invoice_id <= 0) {
    // Gérer l'ID invalide : afficher une erreur ou rediriger
    // Pour la simplicité, on arrête le script ici. Idéalement, rediriger avec un message flash.
    die('ID de facture invalide.');
}

// Récupérer les détails de la facture en s'assurant qu'elle appartient au prestataire connecté
$invoiceData = getProviderInvoiceDetails($invoice_id, $provider_id);

if (!$invoiceData) {
    // Gérer le cas où la facture n'existe pas ou n'appartient pas au prestataire
    // Pour la simplicité, on arrête le script ici. Idéalement, rediriger avec un message flash.
    die('Facture non trouvée ou accès non autorisé.');
}

// Générer et envoyer le PDF
// La fonction generateProviderInvoicePdf gère la sortie et l'arrêt du script en cas de succès.
if (!generateProviderInvoicePdf($invoiceData)) {
    // Gérer l'échec de la génération du PDF (la fonction elle-même affiche un message d'erreur basique)
    // On pourrait logguer l'erreur ici.
}

// Normalement, le script s'arrête dans generateProviderInvoicePdf via exit()
// Si on arrive ici, c'est qu'il y a eu une erreur gérée dans la fonction generate.
exit;
