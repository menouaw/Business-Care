package com.businesscare.reporting.pdf;

import com.businesscare.reporting.model.ClientStats;
import com.itextpdf.io.image.ImageDataFactory;
import com.itextpdf.kernel.colors.ColorConstants;
import com.itextpdf.kernel.font.PdfFont;
import com.itextpdf.kernel.font.PdfFontFactory;
import com.itextpdf.kernel.geom.PageSize;
import com.itextpdf.kernel.pdf.PdfDocument;
import com.itextpdf.kernel.pdf.PdfWriter;
import com.itextpdf.kernel.pdf.canvas.PdfCanvas;
import com.itextpdf.layout.Document;
import com.itextpdf.layout.element.Image;
import com.itextpdf.layout.element.Paragraph;
import com.itextpdf.layout.element.Text;
import com.itextpdf.layout.properties.TextAlignment;
import com.itextpdf.layout.properties.UnitValue;
import org.jfree.chart.JFreeChart;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.awt.Graphics2D;
import java.awt.geom.Rectangle2D;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.util.List;

public class PdfGenerator {

    private static final Logger logger = LoggerFactory.getLogger(PdfGenerator.class);

    
    private static final float MARGIN = 50;
    private static final float CHART_WIDTH = 250;
    private static final float CHART_HEIGHT = 200;
    private static final float FONT_SIZE_TITLE = 18;
    private static final float FONT_SIZE_SUBTITLE = 14;
    private static final float FONT_SIZE_NORMAL = 10;
    private static final float LEADING_NORMAL = 14f;

    /**
     * Génère la première page du rapport PDF (Statistiques Clients) en utilisant iText 7.
     *
     * @param pdfDoc     Le document PDF iText auquel ajouter la page.
     * @param stats      Les statistiques client agrégées.
     * @param clientCharts La liste des graphiques JFreeChart à inclure (devrait en contenir 4).
     * @throws IOException Si une erreur I/O se produit pendant la création du PDF.
     */
    public void generateClientFinancialPage(PdfDocument pdfDoc, ClientStats stats, List<JFreeChart> clientCharts) throws IOException {
        logger.info("Génération de la page des statistiques financières des clients (Page 1)");
        if (clientCharts == null || clientCharts.size() != 4) {
            logger.warn("Attendu 4 graphiques clients pour la génération du PDF, mais reçu {}. La page pourrait être incomplète.",
                        clientCharts == null ? 0 : clientCharts.size());
            
             if (clientCharts == null) return; 
        }

        
        PageSize pageSize = PageSize.A4;
        Document document = new Document(pdfDoc, pageSize);
        document.setMargins(MARGIN, MARGIN, MARGIN, MARGIN);

        PdfFont fontRegular = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA);
        PdfFont fontBold = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA_BOLD);

        
        Paragraph title = new Paragraph(new Text("Page 1: Statistiques des comptes clients"))
                .setFont(fontBold)
                .setFontSize(FONT_SIZE_TITLE)
                .setTextAlignment(TextAlignment.CENTER)
                .setMarginBottom(20f);
        document.add(title);

        
        
        float availableWidth = pageSize.getWidth() - 2 * MARGIN;
        float availableHeight = pageSize.getHeight() - 2 * MARGIN - 50; 
        float colWidth = availableWidth / 2f;
        float x1 = MARGIN;
        float x2 = MARGIN + colWidth;
        float y1 = pageSize.getHeight() - MARGIN - FONT_SIZE_TITLE - 30; 
        float y2 = y1 - CHART_HEIGHT - 30; 

        
        PdfCanvas canvas = new PdfCanvas(pdfDoc.addNewPage());

        
        if (clientCharts.size() > 0) addChartToCanvas(canvas, clientCharts.get(0), x1, y1 - CHART_HEIGHT, CHART_WIDTH, CHART_HEIGHT);
        if (clientCharts.size() > 1) addChartToCanvas(canvas, clientCharts.get(1), x2, y1 - CHART_HEIGHT, CHART_WIDTH, CHART_HEIGHT);
        if (clientCharts.size() > 2) addChartToCanvas(canvas, clientCharts.get(2), x1, y2 - CHART_HEIGHT, CHART_WIDTH, CHART_HEIGHT);
        if (clientCharts.size() > 3) addChartToCanvas(canvas, clientCharts.get(3), x2, y2 - CHART_HEIGHT, CHART_WIDTH, CHART_HEIGHT);


        
        
        float top5YPos = y2 - CHART_HEIGHT - 30; 

        Paragraph top5Title = new Paragraph(new Text("Top 5 des clients (par revenu total des factures payées):"))
                .setFont(fontBold)
                .setFontSize(FONT_SIZE_SUBTITLE)
                .setFixedPosition(pdfDoc.getNumberOfPages(), MARGIN, top5YPos, availableWidth) 
                .setMarginBottom(10f);
         document.add(top5Title);

        float currentListY = top5YPos - LEADING_NORMAL; 

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

        
        

        
        
         
          logger.info("Page 1: Statistiques des comptes clients générée.");
    }

    /**
     * Méthode utilitaire pour ajouter un graphique JFreeChart au canvas PDF en utilisant iText 7.
     */
    private void addChartToCanvas(PdfCanvas canvas, JFreeChart chart, float x, float y, float width, float height) throws IOException {
        try (ByteArrayOutputStream chartOut = new ByteArrayOutputStream()) {
            
            
            java.awt.image.BufferedImage bufferedImage = new java.awt.image.BufferedImage((int)width, (int)height, java.awt.image.BufferedImage.TYPE_INT_ARGB);
            Graphics2D g2 = bufferedImage.createGraphics();

            
            chart.draw(g2, new Rectangle2D.Double(0, 0, width, height));
            g2.dispose();

            
            Image chartImage = new Image(ImageDataFactory.create(bufferedImage, null));

            
            canvas.addImageFittedIntoRectangle(chartImage.getImageData(), new com.itextpdf.kernel.geom.Rectangle(x, y, width, height), false);
            logger.debug("Chart added to PDF canvas at x={}, y={}", x, y);

        } catch (Exception e) {
            logger.error("Erreur lors de l'ajout du graphique au canvas PDF", e);
            
            if (e instanceof IOException) throw (IOException)e;
            throw new IOException("Erreur lors de la conversion du graphique en image PDF", e);
        }
    }

    /**
     * Génère la deuxième page du rapport PDF (Statistiques Événements) en utilisant iText 7.
     *
     * @param pdfDoc     Le document PDF iText auquel ajouter la page.
     * @param stats      Les statistiques événement agrégées.
     * @param eventCharts La liste des graphiques JFreeChart à inclure (devrait en contenir 4).
     * @throws IOException Si une erreur I/O se produit pendant la création du PDF.
     */
    public void generateEventStatsPage(PdfDocument pdfDoc, EventStats stats, List<JFreeChart> eventCharts) throws IOException {
        logger.info("Génération de la page des statistiques d'événements (Page 2)");
        if (eventCharts == null || eventCharts.size() != 4) {
            logger.warn("Attendu 4 graphiques événements pour la génération du PDF, mais reçu {}. La page pourrait être incomplète.",
                        eventCharts == null ? 0 : eventCharts.size());
            if (eventCharts == null) return;
        }

        
        pdfDoc.addNewPage();
        PageSize pageSize = pdfDoc.getDefaultPageSize();
        Document document = new Document(pdfDoc, pageSize);
        document.setMargins(MARGIN, MARGIN, MARGIN, MARGIN);

        PdfFont fontRegular = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA);
        PdfFont fontBold = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA_BOLD);

        
        Paragraph title = new Paragraph(new Text("Page 2: Statistiques des Événements"))
                .setFont(fontBold)
                .setFontSize(FONT_SIZE_TITLE)
                .setTextAlignment(TextAlignment.CENTER)
                .setMarginBottom(20f);
        document.add(title);

        
        float availableWidth = pageSize.getWidth() - 2 * MARGIN;
        float x1 = MARGIN;
        float x2 = MARGIN + (availableWidth / 2f);
        float y1 = pageSize.getHeight() - MARGIN - FONT_SIZE_TITLE - 30; 
        float y2 = y1 - CHART_HEIGHT - 30; 

        
        PdfCanvas canvas = new PdfCanvas(pdfDoc.getLastPage());

        if (eventCharts.size() > 0) addChartToCanvas(canvas, eventCharts.get(0), x1, y1 - CHART_HEIGHT, CHART_WIDTH, CHART_HEIGHT);
        if (eventCharts.size() > 1) addChartToCanvas(canvas, eventCharts.get(1), x2, y1 - CHART_HEIGHT, CHART_WIDTH, CHART_HEIGHT);
        if (eventCharts.size() > 2) addChartToCanvas(canvas, eventCharts.get(2), x1, y2 - CHART_HEIGHT, CHART_WIDTH, CHART_HEIGHT);
        if (eventCharts.size() > 3) addChartToCanvas(canvas, eventCharts.get(3), x2, y2 - CHART_HEIGHT, CHART_WIDTH, CHART_HEIGHT);

        
        float top5YPos = y2 - CHART_HEIGHT - 30; 

        Paragraph top5Title = new Paragraph(new Text("Top 5 des Événements (par popularité/inscriptions):"))
                .setFont(fontBold)
                .setFontSize(FONT_SIZE_SUBTITLE)
                .setFixedPosition(pdfDoc.getNumberOfPages(), MARGIN, top5YPos, availableWidth) 
                .setMarginBottom(10f);
        document.add(top5Title);

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
            top5List.add(new Text("Aucune donnée de popularité disponible pour classer les événements.")).add("\n");
        }
        document.add(top5List); 

        logger.info("Page 2: Statistiques des Événements générée.");
    }

    /**
     * Génère la troisième page du rapport PDF (Statistiques Prestations) en utilisant iText 7.
     *
     * @param pdfDoc             Le document PDF iText auquel ajouter la page.
     * @param stats              Les statistiques prestation agrégées.
     * @param prestationCharts   La liste des graphiques JFreeChart à inclure (devrait en contenir 4).
     * @throws IOException Si une erreur I/O se produit pendant la création du PDF.
     */
    public void generatePrestationStatsPage(PdfDocument pdfDoc, PrestationStats stats, List<JFreeChart> prestationCharts) throws IOException {
        logger.info("Génération de la page des statistiques de prestations (Page 3)");
        if (prestationCharts == null || prestationCharts.size() != 4) {
            logger.warn("Attendu 4 graphiques prestations pour la génération du PDF, mais reçu {}. La page pourrait être incomplète.",
                        prestationCharts == null ? 0 : prestationCharts.size());
            if (prestationCharts == null) return;
        }

        
        pdfDoc.addNewPage();
        PageSize pageSize = pdfDoc.getDefaultPageSize();
        Document document = new Document(pdfDoc, pageSize);
        document.setMargins(MARGIN, MARGIN, MARGIN, MARGIN);

        PdfFont fontRegular = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA);
        PdfFont fontBold = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA_BOLD);

        
        Paragraph title = new Paragraph(new Text("Page 3: Statistiques des Prestations"))
                .setFont(fontBold)
                .setFontSize(FONT_SIZE_TITLE)
                .setTextAlignment(TextAlignment.CENTER)
                .setMarginBottom(20f);
        document.add(title);

        
        float availableWidth = pageSize.getWidth() - 2 * MARGIN;
        float x1 = MARGIN;
        float x2 = MARGIN + (availableWidth / 2f);
        float y1 = pageSize.getHeight() - MARGIN - FONT_SIZE_TITLE - 30; 
        float y2 = y1 - CHART_HEIGHT - 30; 

        
        PdfCanvas canvas = new PdfCanvas(pdfDoc.getLastPage());

        if (prestationCharts.size() > 0) addChartToCanvas(canvas, prestationCharts.get(0), x1, y1 - CHART_HEIGHT, CHART_WIDTH, CHART_HEIGHT);
        if (prestationCharts.size() > 1) addChartToCanvas(canvas, prestationCharts.get(1), x2, y1 - CHART_HEIGHT, CHART_WIDTH, CHART_HEIGHT);
        if (prestationCharts.size() > 2) addChartToCanvas(canvas, prestationCharts.get(2), x1, y2 - CHART_HEIGHT, CHART_WIDTH, CHART_HEIGHT);
        if (prestationCharts.size() > 3) addChartToCanvas(canvas, prestationCharts.get(3), x2, y2 - CHART_HEIGHT, CHART_WIDTH, CHART_HEIGHT);

        
        float top5YPos = y2 - CHART_HEIGHT - 30; 

        Paragraph top5Title = new Paragraph(new Text("Top 5 des Prestations (par fréquence):"))
                .setFont(fontBold)
                .setFontSize(FONT_SIZE_SUBTITLE)
                .setFixedPosition(pdfDoc.getNumberOfPages(), MARGIN, top5YPos, availableWidth) 
                .setMarginBottom(10f);
        document.add(top5Title);

        Paragraph top5List = new Paragraph()
                .setFont(fontRegular)
                .setFontSize(FONT_SIZE_NORMAL)
                .setFixedLeading(LEADING_NORMAL);

        if (stats.getTop5PrestationsByFrequency() != null && !stats.getTop5PrestationsByFrequency().isEmpty()) {
            int rank = 1;
            for (PrestationStats.PrestationFrequency pf : stats.getTop5PrestationsByFrequency()) {
                String line = String.format("%d. %s - Fréquence: %d",
                        rank++,
                        pf.getPrestationName() != null ? pf.getPrestationName() : "(Inconnu)",
                        pf.getFrequency()
                );
                top5List.add(new Text(line)).add("\n");
            }
        } else {
            top5List.add(new Text("Aucune donnée de fréquence disponible pour classer les prestations.")).add("\n");
        }
        document.add(top5List); 

        logger.info("Page 3: Statistiques des Prestations générée.");
    }

}
