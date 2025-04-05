<?php

// Charger la bibliothèque TCPDF
// Assurez-vous que TCPDF est installé (via Composer ou téléchargement)
// Si téléchargé manuellement, ajustez le chemin ci-dessous
require_once __DIR__ . '/../../../vendor/tecnickcom/tcpdf/tcpdf.php'; // Chemin typique si installé via Composer

class PDFGenerator extends TCPDF {

    // Vous pouvez personnaliser l'en-tête et le pied de page si nécessaire
    public function Header() {
        // Logo (optionnel)
        // $image_file = K_PATH_IMAGES.'logo_example.jpg';
        // $this->Image($image_file, 10, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        // Police
        $this->SetFont('helvetica', 'B', 15);
        // Titre
        $this->Cell(0, 15, 'Contrat de Prestation de Services', 0, true, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(5); // Saut de ligne après l'en-tête
    }

    // Pied de page personnalisé
    public function Footer() {
        // Positionnement à 15 mm du bas
        $this->SetY(-15);
        // Police
        $this->SetFont('helvetica', 'I', 8);
        // Numéro de page
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

    /**
     * Génère le PDF pour un contrat donné et le propose au téléchargement.
     *
     * @param array $contract Données du contrat (incluant infos entreprise et services)
     */
    public function generateContractPDF($contract) {
        // Définir les métadonnées du document
        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor('Business Care');
        $this->SetTitle('Contrat ' . ($contract['reference'] ?? $contract['id']));
        $this->SetSubject('Détails du contrat de prestation de services');
        $this->SetKeywords('Business Care, Contrat, Prestation, Service');

        // Définir les marges
        $this->SetMargins(PDF_MARGIN_LEFT, 40, PDF_MARGIN_RIGHT); // Marge haute augmentée pour l'en-tête
        $this->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);

        // Définir les sauts de page automatiques
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // Définir la police
        $this->SetFont('helvetica', '', 10);

        // Ajouter une page
        $this->AddPage();

        // ---- Contenu du contrat ----

        $html = '<style>
                    h1 { font-size: 16pt; font-weight: bold; text-align: center; margin-bottom: 20px; }
                    h2 { font-size: 12pt; font-weight: bold; margin-top: 15px; margin-bottom: 5px; border-bottom: 1px solid #cccccc; padding-bottom: 2px; }
                    p { line-height: 1.4; margin-bottom: 8px; }
                    strong { font-weight: bold; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    th { background-color: #f2f2f2; border: 1px solid #dddddd; text-align: left; padding: 6px; font-weight: bold; }
                    td { border: 1px solid #dddddd; text-align: left; padding: 6px; }
                    .info-block { margin-bottom: 15px; }
                    .info-label { font-weight: bold; width: 150px; display: inline-block; }
                 </style>';

        // Informations sur le Contrat
        $html .= '<h2>Informations Générales du Contrat</h2>';
        $html .= '<div class="info-block">';
        $html .= '<p><span class="info-label">Référence Contrat:</span> ' . htmlspecialchars($contract['reference'] ?? 'N/A') . '</p>';
        $html .= '<p><span class="info-label">Type de Contrat:</span> ' . htmlspecialchars(ucfirst($contract['type_contrat'] ?? 'N/A')) . '</p>';
        $html .= '<p><span class="info-label">Date de Début:</span> ' . (isset($contract['date_debut']) ? formatDate($contract['date_debut'], 'd/m/Y') : 'N/A') . '</p>';
        $html .= '<p><span class="info-label">Date de Fin:</span> ' . (isset($contract['date_fin']) && $contract['date_fin'] ? formatDate($contract['date_fin'], 'd/m/Y') : 'Indéterminée') . '</p>';
        $html .= '<p><span class="info-label">Montant Mensuel:</span> ' . (isset($contract['montant_mensuel']) ? formatMoney($contract['montant_mensuel']) : 'N/A') . '</p>';
        $html .= '<p><span class="info-label">Nombre de Salariés:</span> ' . htmlspecialchars($contract['nombre_salaries'] ?? 'Non spécifié') . '</p>';
        $html .= '<p><span class="info-label">Statut:</span> ' . htmlspecialchars(ucfirst($contract['statut'] ?? 'N/A')) . '</p>'; // Utiliser texte simple pour PDF
        $html .= '</div>';

        // Informations sur l'Entreprise Cliente
        $html .= '<h2>Informations sur l'Entreprise Cliente</h2>';
        $html .= '<div class="info-block">';
        $html .= '<p><span class="info-label">Nom:</span> ' . htmlspecialchars($contract['entreprise_nom'] ?? 'N/A') . '</p>';
        $html .= '<p><span class="info-label">SIRET:</span> ' . htmlspecialchars($contract['entreprise_siret'] ?? 'N/A') . '</p>';
        $html .= '<p><span class="info-label">Adresse:</span> ' . htmlspecialchars($contract['entreprise_adresse'] ?? '') . ', ' . htmlspecialchars($contract['entreprise_code_postal'] ?? '') . ' ' . htmlspecialchars($contract['entreprise_ville'] ?? '') . '</p>';
        $html .= '</div>';

        // Services Inclus
        if (!empty($contract['services'])) {
            $html .= '<h2>Services Inclus dans le Contrat</h2>';
            $html .= '<table>';
            $html .= '<thead><tr><th>Service</th><th>Description</th><th>Catégorie</th><th>Prix Indicatif</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($contract['services'] as $service) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($service['nom'] ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($service['description'] ?? '-') . '</td>';
                $html .= '<td>' . htmlspecialchars(ucfirst($service['categorie'] ?? '-')) . '</td>';
                $html .= '<td>' . htmlspecialchars($service['prix_formate'] ?? 'N/A') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        } else {
             $html .= '<h2>Services Inclus dans le Contrat</h2>';
             $html .= '<p>Aucun service spécifique listé pour ce contrat.</p>';
        }
        $html .= '<br><br>'; // Espace avant les conditions

        // Conditions Particulières
        if (!empty($contract['conditions_particulieres'])) {
            $html .= '<h2>Conditions Particulières</h2>';
            $html .= '<div>' . nl2br(htmlspecialchars($contract['conditions_particulieres'])) . '</div>';
        }

        // Ajouter le contenu HTML au PDF
        $this->writeHTML($html, true, false, true, false, '');

        // ---- Fin du contenu ----

        // Fermer et générer le document PDF
        // 'D' force le téléchargement
        $filename = 'Contrat_' . ($contract['reference'] ?? $contract['id']) . '.pdf';
        $this->Output($filename, 'D');
        exit; // Important pour s'assurer que rien d'autre n'est envoyé après le PDF
    }
} 