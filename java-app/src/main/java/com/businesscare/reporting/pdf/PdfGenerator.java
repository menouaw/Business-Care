package com.businesscare.reporting.pdf;

import com.businesscare.reporting.model.ClientStats;
import com.businesscare.reporting.model.EventStats;
import com.businesscare.reporting.model.PrestationStats;
import com.itextpdf.io.font.constants.StandardFonts;
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
import com.itextpdf.layout.properties.VerticalAlignment;
import org.jfree.chart.JFreeChart;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.awt.Graphics2D;
import java.awt.geom.Rectangle2D;
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.util.List;
import java.util.Objects;

/**
 * Classe responsable de la génération du rapport PDF.
 */
public class PdfGenerator {

    private static final Logger logger = LoggerFactory.getLogger(PdfGenerator.class);

    
    private static final float FONT_SIZE_REPORT_TITLE = 24;
    private static final float FONT_SIZE_TITLE = 18;
    private static final float FONT_SIZE_SUBTITLE = 14;
    private static final float FONT_SIZE_NORMAL = 10;
    private static final float LEADING_NORMAL = 14f;
    private static final float MARGIN_BOTTOM_TITLE = 15f;
    private static final float MARGIN_BOTTOM_LIST_TITLE = 20f;
    private static final float CHART_VERTICAL_SPACE = 30f; 

    
    private static final String REPORT_TITLE_PREFIX = "Business Care\nRapport du ";
    private static final String CLIENT_TOP5_TITLE = "Top 5 des clients (par revenu total des factures payées)";
    private static final String EVENT_TOP5_TITLE = "Top 5 des évènements (par popularité/inscriptions)";
    private static final String PRESTATION_TOP5_TITLE = "Top 5 des prestations (par fréquence d'utilisation)";
    private static final String UNKNOWN_ENTITY_NAME = "(Inconnu)";
    private static final String CLIENT_TOP5_FORMAT = "%d. %s - Revenu: %.2f €";
    private static final String EVENT_TOP5_FORMAT = "%d. %s - Popularité: %d";
    private static final String PRESTATION_TOP5_FORMAT = "%d. %s - Fréquence: %d";
    private static final String NO_DATA_CLIENTS = "Aucune donnée de revenu disponible pour classer les clients.";
    private static final String NO_DATA_EVENTS = "Aucune donnée de popularité disponible pour classer les évènements.";
    private static final String NO_DATA_PRESTATIONS = "Aucune donnée de fréquence disponible pour classer les prestations.";
    private static final String ERR_ADDING_CHART = "Erreur lors de l'ajout du graphique '{}' au PDF";
    private static final String ERR_CONVERTING_CHART = "Erreur lors de la conversion ou de l'ajout du graphique: ";

    
    private PdfFont fontHelveticaBold;
    private PdfFont fontHelvetica;

    public PdfGenerator() {
        try {
            fontHelveticaBold = PdfFontFactory.createFont(StandardFonts.HELVETICA_BOLD);
            fontHelvetica = PdfFontFactory.createFont(StandardFonts.HELVETICA);
        } catch (IOException e) {
            
            logger.error("Impossible de créer les polices PDF standard", e);
            throw new RuntimeException("Erreur critique lors de l'initialisation des polices PDF", e);
        }
    }

    /**
     * Génère la page de titre du rapport.
     *
     * @param document      Le document iText principal.
     * @param formattedDate La date formatée (JJ-MM-AAAA) à inclure dans le titre.
     */
    public void generateTitlePage(Document document, String formattedDate) {
        logger.info("Génération de la page de titre...");
        PageSize pageSize = document.getPdfDocument().getDefaultPageSize();
        float pageHeight = pageSize.getHeight();

        Paragraph title = new Paragraph(REPORT_TITLE_PREFIX + formattedDate)
                .setFont(fontHelveticaBold)
                .setFontSize(FONT_SIZE_REPORT_TITLE)
                .setTextAlignment(TextAlignment.CENTER)
                .setMarginTop(pageHeight / 2 - FONT_SIZE_REPORT_TITLE * 2); 

        document.add(title);
        logger.info("Page de titre générée.");
    }

    /**
     * Ajoute un seul graphique JFreeChart à une nouvelle page du document PDF.
     *
     * @param document  Le document iText principal.
     * @param chart     Le graphique JFreeChart à ajouter.
     * @param titleText Le titre à afficher au-dessus du graphique.
     * @throws IOException Si une erreur I/O se produit lors de la conversion/écriture de l'image.
     */
    public void addChartToNewPage(Document document, JFreeChart chart, String titleText) throws IOException {
        logger.info("Ajout du graphique '{}' sur une nouvelle page...", titleText);
        PageSize pageSize = document.getPdfDocument().getDefaultPageSize();

        document.add(new AreaBreak(AreaBreakType.NEXT_PAGE));

        Paragraph title = new Paragraph(new Text(titleText))
                .setFont(fontHelveticaBold)
                .setFontSize(FONT_SIZE_SUBTITLE)
                .setTextAlignment(TextAlignment.CENTER)
                .setMarginBottom(MARGIN_BOTTOM_TITLE);
        document.add(title);

        
        float availableWidth = pageSize.getWidth() - document.getLeftMargin() - document.getRightMargin();
        float availableHeight = pageSize.getHeight() - document.getTopMargin() - document.getBottomMargin() - FONT_SIZE_SUBTITLE - CHART_VERTICAL_SPACE;

        try {
            
            BufferedImage bufferedImage = chart.createBufferedImage((int) availableWidth, (int) availableHeight, BufferedImage.TYPE_INT_ARGB, null);
            ImageData imageData = ImageDataFactory.create(bufferedImage, null); 
            Image pdfImage = new Image(imageData);

            pdfImage.setAutoScale(true); 
            document.add(pdfImage);
            logger.debug("Graphique '{}' ajouté avec succès à la page {}.", titleText, document.getPdfDocument().getNumberOfPages());

        } catch (Exception e) {
            
            logger.error(ERR_ADDING_CHART, titleText, e);
            
            if (e instanceof IOException) throw (IOException) e;
            
            throw new IOException(ERR_CONVERTING_CHART + titleText, e);
        }
    }

    /**
     * Génère une page dédiée à la liste Top 5 des clients.
     *
     * @param document Le document iText principal.
     * @param stats    Les statistiques client contenant le Top 5.
     */
    public void generateClientTop5Page(Document document, ClientStats stats) {
        logger.info("Génération de la page Top 5 clients...");
        List<ClientStats.CompanyRevenue> topList = stats.getTop5ClientsByRevenue();

        generateTopListPage(document, CLIENT_TOP5_TITLE, topList, (item, rank) ->
                String.format(CLIENT_TOP5_FORMAT,
                        rank,
                        item.getCompanyName(), 
                        item.getRevenue()),
                NO_DATA_CLIENTS);
        logger.info("Contenu Top 5 clients ajouté (Page {}).", document.getPdfDocument().getNumberOfPages());
    }

    /**
     * Génère une page dédiée à la liste Top 5 des évènements.
     *
     * @param document Le document iText principal.
     * @param stats    Les statistiques évènement contenant le Top 5.
     */
    public void generateEventTop5Page(Document document, EventStats stats) {
        logger.info("Génération de la page Top 5 évènements...");
        List<EventStats.EventPopularity> topList = stats.getTop5EventsByPopularity();

        generateTopListPage(document, EVENT_TOP5_TITLE, topList, (item, rank) ->
                String.format(EVENT_TOP5_FORMAT,
                        rank,
                        item.getEventTitle(), 
                        item.getPopularityMetric()),
                NO_DATA_EVENTS);
        logger.info("Contenu Top 5 évènements ajouté (Page {}).", document.getPdfDocument().getNumberOfPages());
    }

    /**
     * Génère une page standardisée pour afficher une liste Top 5.
     *
     * @param document      Le document PDF.
     * @param titleText     Le titre de la page.
     * @param topList       La liste des éléments à afficher (doit avoir au plus 5 éléments).
     * @param itemFormatter Fonction pour formater chaque élément de la liste en String.
     * @param noDataMessage Message à afficher si la liste est vide.
     * @param <T>           Le type des éléments dans la liste.
     */
    private <T> void generateTopListPage(Document document, String titleText, List<T> topList,
                                         ListItemFormatter<T> itemFormatter, String noDataMessage) {

        document.add(new AreaBreak(AreaBreakType.NEXT_PAGE));

        Paragraph title = new Paragraph(new Text(titleText))
                .setFont(fontHelveticaBold)
                .setFontSize(FONT_SIZE_SUBTITLE)
                .setTextAlignment(TextAlignment.CENTER)
                .setMarginBottom(MARGIN_BOTTOM_LIST_TITLE);
        document.add(title);

        Paragraph listParagraph = new Paragraph()
                .setFont(fontHelvetica)
                .setFontSize(FONT_SIZE_NORMAL)
                .setFixedLeading(LEADING_NORMAL);

        if (topList != null && !topList.isEmpty()) {
            int rank = 1;
            
            for (T item : topList.subList(0, Math.min(topList.size(), 5))) {
                String line = itemFormatter.format(item, rank++);
                listParagraph.add(new Text(line)).add("\n");
            }
        } else {
            listParagraph.add(new Text(noDataMessage)).add("\n");
        }
        document.add(listParagraph);
    }

    /**
     * Interface fonctionnelle pour le formatage d'éléments de liste.
     */
    @FunctionalInterface
    private interface ListItemFormatter<T> {
        String format(T item, int rank);
    }
}
