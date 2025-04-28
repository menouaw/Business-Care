package com.businesscare.reporting.pdf;

import com.businesscare.reporting.model.ClientStats;
import com.businesscare.reporting.model.EventStats;
import com.businesscare.reporting.model.PrestationStats;
import com.itextpdf.io.image.ImageData;
import com.itextpdf.io.image.ImageDataFactory;
import com.itextpdf.kernel.colors.ColorConstants;
import com.itextpdf.kernel.font.PdfFont;
import com.itextpdf.kernel.font.PdfFontFactory;
import com.itextpdf.kernel.geom.PageSize;
import com.itextpdf.kernel.pdf.PdfDocument;
import com.itextpdf.kernel.pdf.PdfWriter;
import com.itextpdf.layout.Document;
import com.itextpdf.layout.element.AreaBreak;
import com.itextpdf.layout.element.Image;
import com.itextpdf.layout.element.Paragraph;
import com.itextpdf.layout.element.Text;
import com.itextpdf.layout.properties.AreaBreakType;
import com.itextpdf.layout.properties.TextAlignment;
import com.itextpdf.layout.properties.UnitValue;
import org.jfree.chart.JFreeChart;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.awt.Graphics2D;
import java.awt.geom.Rectangle2D;
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.util.List;

public class PdfGenerator {

    private static final Logger logger = LoggerFactory.getLogger(PdfGenerator.class);

    
    private static final float MARGIN = 50;
    private static final float FONT_SIZE_TITLE = 18;
    private static final float FONT_SIZE_SUBTITLE = 14;
    private static final float FONT_SIZE_NORMAL = 10;
    private static final float LEADING_NORMAL = 14f;

    /**
     * Ajoute un seul graphique JFreeChart à une nouvelle page du document PDF.
     * Le graphique est mis à l'échelle pour occuper la majeure partie de la page.
     *
     * @param document Le document iText principal auquel ajouter le contenu.
     * @param chart    Le graphique JFreeChart à ajouter.
     * @param titleText Le titre à afficher au-dessus du graphique.
     * @throws IOException Si une erreur I/O se produit.
     */
    public void addChartToNewPage(Document document, JFreeChart chart, String titleText) throws IOException {
        logger.info("Ajout du graphique '{}' sur une nouvelle page...", titleText);
        
        PageSize pageSize = document.getPdfDocument().getDefaultPageSize();

        
        document.add(new AreaBreak(AreaBreakType.NEXT_PAGE));

        
        

        PdfFont fontBold = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA_BOLD);

        
        Paragraph title = new Paragraph(new Text(titleText))
                .setFont(fontBold)
                .setFontSize(FONT_SIZE_SUBTITLE)
                .setTextAlignment(TextAlignment.CENTER)
                .setMarginBottom(15f);
        document.add(title);

        
        float availableWidth = pageSize.getWidth() - document.getLeftMargin() - document.getRightMargin();
        float availableHeight = pageSize.getHeight() - document.getTopMargin() - document.getBottomMargin() - FONT_SIZE_SUBTITLE - 30; 

        try {
            
            BufferedImage bufferedImage = chart.createBufferedImage((int)availableWidth, (int)availableHeight, BufferedImage.TYPE_INT_ARGB, null);
            ImageData imageData = ImageDataFactory.create(bufferedImage, null);
            Image pdfImage = new Image(imageData);

            pdfImage.setAutoScale(true);
            document.add(pdfImage);
            logger.debug("Graphique '{}' ajouté avec succès à la page {}.", titleText, document.getPdfDocument().getNumberOfPages());

        } catch (Exception e) {
            logger.error("Erreur lors de l'ajout du graphique '{}' au PDF", titleText, e);
            if (e instanceof IOException) throw (IOException) e;
            throw new IOException("Erreur lors de la conversion ou de l'ajout du graphique: " + titleText, e);
        }
        
    }


    /**
     * Génère une page dédiée à la liste Top 5 des clients.
     *
     * @param document Le document iText principal auquel ajouter le contenu.
     * @param stats    Les statistiques client contenant le Top 5.
     * @throws IOException Si une erreur I/O se produit.
     */
     public void generateClientTop5Page(Document document, ClientStats stats) throws IOException {
        logger.info("Génération de la page Top 5 Clients...");

        
        document.add(new AreaBreak(AreaBreakType.NEXT_PAGE));

        PdfFont fontRegular = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA);
        PdfFont fontBold = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA_BOLD);

        Paragraph title = new Paragraph(new Text("Top 5 des clients (par revenu total des factures payées)"))
                .setFont(fontBold)
                .setFontSize(FONT_SIZE_SUBTITLE)
                .setTextAlignment(TextAlignment.CENTER)
                .setMarginBottom(20f);
        document.add(title);

        Paragraph top5List = new Paragraph()
                .setFont(fontRegular)
                .setFontSize(FONT_SIZE_NORMAL)
                .setFixedLeading(LEADING_NORMAL);

        if (stats.getTop5ClientsByRevenue() != null && !stats.getTop5ClientsByRevenue().isEmpty()) {
            int rank = 1;
            for (ClientStats.CompanyRevenue cr : stats.getTop5ClientsByRevenue()) {
                String line = String.format("%d. %s - Revenu: %.2f €",
                        rank++,
                        cr.getCompany() != null ? cr.getCompany().getNom() : "(Inconnu)",
                        cr.getRevenue()
                );
                top5List.add(new Text(line)).add("\n");
            }
        } else {
            top5List.add(new Text("Aucune donnée de revenu disponible pour classer les clients.")).add("\n");
        }
        document.add(top5List);
        logger.info("Contenu Top 5 Clients ajouté (Page {}).", document.getPdfDocument().getNumberOfPages());
    }

    /**
     * Génère une page dédiée à la liste Top 5 des évènements.
     *
     * @param document Le document iText principal auquel ajouter le contenu.
     * @param stats    Les statistiques évènement contenant le Top 5.
     * @throws IOException Si une erreur I/O se produit.
     */
    public void generateEventTop5Page(Document document, EventStats stats) throws IOException {
        logger.info("Génération de la page Top 5 Évènements...");

        
        document.add(new AreaBreak(AreaBreakType.NEXT_PAGE));

        PdfFont fontRegular = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA);
        PdfFont fontBold = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA_BOLD);

        Paragraph title = new Paragraph(new Text("Top 5 des Évènements (par popularité/inscriptions)"))
                .setFont(fontBold)
                .setFontSize(FONT_SIZE_SUBTITLE)
                .setTextAlignment(TextAlignment.CENTER)
                .setMarginBottom(20f);
        document.add(title);

        Paragraph top5List = new Paragraph()
                .setFont(fontRegular)
                .setFontSize(FONT_SIZE_NORMAL)
                .setFixedLeading(LEADING_NORMAL);

        if (stats.getTop5EventsByPopularity() != null && !stats.getTop5EventsByPopularity().isEmpty()) {
            int rank = 1;
            for (EventStats.EventPopularity ep : stats.getTop5EventsByPopularity()) {
                String line = String.format("%d. %s - Popularité: %d",
                        rank++,
                        ep.getEvent() != null ? ep.getEvent().getTitre() : "(Inconnu)",
                        ep.getPopularityMetric()
                );
                top5List.add(new Text(line)).add("\n");
            }
        } else {
            top5List.add(new Text("Aucune donnée de popularité disponible pour classer les évènements.")).add("\n");
        }
        document.add(top5List);
        logger.info("Contenu Top 5 Évènements ajouté (Page {}).", document.getPdfDocument().getNumberOfPages());
    }


    /**
     * Génère une page dédiée à la liste Top 5 des prestations.
     *
     * @param document Le document iText principal auquel ajouter le contenu.
     * @param stats    Les statistiques prestation contenant le Top 5.
     * @throws IOException Si une erreur I/O se produit.
     */
    public void generatePrestationTop5Page(Document document, PrestationStats stats) throws IOException {
         logger.info("Génération de la page Top 5 Prestations...");

        
        document.add(new AreaBreak(AreaBreakType.NEXT_PAGE));

        PdfFont fontRegular = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA);
        PdfFont fontBold = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA_BOLD);

        Paragraph title = new Paragraph(new Text("Top 5 des Prestations (par fréquence d'utilisation)"))
                .setFont(fontBold)
                .setFontSize(FONT_SIZE_SUBTITLE)
                .setTextAlignment(TextAlignment.CENTER)
                .setMarginBottom(20f);
        document.add(title);

        Paragraph top5List = new Paragraph()
                .setFont(fontRegular)
                .setFontSize(FONT_SIZE_NORMAL)
                .setFixedLeading(LEADING_NORMAL);

        if (stats.getTop5PrestationsByFrequency() != null && !stats.getTop5PrestationsByFrequency().isEmpty()) {
            int rank = 1;
            for (PrestationStats.PrestationFrequency pf : stats.getTop5PrestationsByFrequency()) {
                String prestationName = pf.getPrestationName() != null ? pf.getPrestationName() : "(Inconnu)";
                String line = String.format("%d. %s - Fréquence: %d",
                        rank++,
                        prestationName,
                        pf.getFrequency()
                );
                top5List.add(new Text(line)).add("\n");
            }
        } else {
            top5List.add(new Text("Aucune donnée de fréquence disponible pour classer les prestations.")).add("\n");
        }
        document.add(top5List);
        logger.info("Contenu Top 5 Prestations ajouté (Page {}).", document.getPdfDocument().getNumberOfPages());
    }

}
