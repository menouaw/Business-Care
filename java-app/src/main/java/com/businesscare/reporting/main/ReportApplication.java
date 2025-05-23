package com.businesscare.reporting.main;

import com.businesscare.reporting.chart.ChartGenerator;
import com.businesscare.reporting.client.ApiDataFetcher;
import com.businesscare.reporting.exception.ApiException;
import com.businesscare.reporting.exception.ReportGenerationException;
import com.businesscare.reporting.model.*;
import com.businesscare.reporting.model.AllData;
import com.businesscare.reporting.model.ProcessedStats;
import com.businesscare.reporting.pdf.PdfGenerator;
import com.businesscare.reporting.service.ReportService;
import com.businesscare.reporting.util.Constants;
import com.businesscare.reporting.util.OutputDirectoryUtil;

import com.itextpdf.kernel.pdf.PdfDocument;
import com.itextpdf.kernel.pdf.PdfWriter;
import com.itextpdf.layout.Document;

import org.jfree.chart.JFreeChart;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.io.File;
import java.io.IOException;
import java.util.Map;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

/**
 * Classe principale de l'application de reporting.
 * Orchestre la récupération des données, le traitement, et la génération du PDF.
 */
public class ReportApplication {

    private static final Logger logger = LoggerFactory.getLogger(ReportApplication.class);



    private static final String CHART_TITLE_CONTRACT_STATUS = "Répartition des contrats par statut";
    private static final String CHART_TITLE_CLIENT_SECTOR = "Répartition des clients par secteur";
    private static final String CHART_TITLE_CLIENT_SIZE = "Répartition des clients par taille";
    private static final String CHART_TITLE_CLIENT_REVENUE = "Répartition des revenus par client";
    private static final String CHART_TITLE_EVENT_TYPE_PIE = "Répartition des évènements par type (camembert)";
    private static final String CHART_TITLE_EVENT_TYPE_BAR = "Répartition des évènements par type (barres)";
    private static final String CHART_TITLE_PRESTATION_TYPE = "Répartition des prestations par type";
    private static final String CHART_TITLE_PRESTATION_CATEGORY = "Répartition des prestations par catégorie";

    private static final String LOG_GEN_CHARTS_CLIENT_START = "Génération des graphiques financiers...";
    private static final String LOG_GEN_CHARTS_CLIENT_END = "{} graphiques financiers générés.";
    private static final String LOG_GEN_CHARTS_EVENT_START = "Génération des graphiques d'évènements...";
    private static final String LOG_GEN_CHARTS_EVENT_END = "{} graphiques d'évènements générés.";
    private static final String LOG_GEN_CHARTS_PRESTATION_START = "Génération des graphiques de prestations...";
    private static final String LOG_GEN_CHARTS_PRESTATION_END = "{} graphiques de prestations générés.";
    private static final String LOG_GEN_PDF_START = "Génération du rapport PDF : {}";
    private static final String LOG_GEN_PDF_SUCCESS = "Rapport PDF généré avec succès ({} pages).";
    private static final String LOG_ERR_PDF_IO = "Erreur I/O lors de la génération du PDF : {} ({})";

    public static void main(String[] args) {
        logger.info(Constants.LOG_APP_START);
        ReportService reportService = new ReportService();
        PdfGenerator pdfGenerator = new PdfGenerator();

        try {
            ApiDataFetcher dataFetcher = new ApiDataFetcher();
            AllData data = dataFetcher.fetchAllData();

            
            ProcessedStats stats = reportService.processData(data);

            
            Map<String, JFreeChart> clientCharts = generateClientCharts(stats.clientStats);
            Map<String, JFreeChart> eventCharts = generateEventCharts(stats.eventStats);
            Map<String, JFreeChart> prestationCharts = generatePrestationCharts(stats.prestationStats);

            
            generatePdfReport(pdfGenerator, stats, clientCharts, eventCharts, prestationCharts);

        } catch (ApiException e) {
            logger.error(Constants.LOG_ERR_API, e.getMessage(), e.getCause() != null ? e.getCause().getMessage() : "N/A", e);
        } catch (ReportGenerationException rge) {
            logger.error(LOG_ERR_PDF_IO, rge.getMessage(), rge.getCause() != null ? rge.getCause().getMessage() : "N/A", rge);
        } catch (Exception e) {
            logger.error(Constants.LOG_ERR_UNEXPECTED, e.getMessage(), e.getClass().getSimpleName(), e);
        }

        logger.info(Constants.LOG_APP_END);
    }

    private static Map<String, JFreeChart> generateClientCharts(ClientStats stats) {
        logger.info(LOG_GEN_CHARTS_CLIENT_START);
        Map<String, JFreeChart> charts = Map.of(
                CHART_TITLE_CONTRACT_STATUS, ChartGenerator.createContractStatusChart(stats),
                CHART_TITLE_CLIENT_SECTOR, ChartGenerator.createClientDistributionBySectorChart(stats),
                CHART_TITLE_CLIENT_SIZE, ChartGenerator.createClientDistributionBySizeChart(stats),
                CHART_TITLE_CLIENT_REVENUE, ChartGenerator.createClientRevenueDistributionChart(stats)
        );
        logger.info(LOG_GEN_CHARTS_CLIENT_END, charts.size());
        return charts;
    }

    private static Map<String, JFreeChart> generateEventCharts(EventStats stats) {
        logger.info(LOG_GEN_CHARTS_EVENT_START);
        Map<String, JFreeChart> charts = Map.of(
                CHART_TITLE_EVENT_TYPE_PIE, ChartGenerator.createEventTypeDistributionChart(stats),
                CHART_TITLE_EVENT_TYPE_BAR, ChartGenerator.createEventTypeDistributionBarChart(stats)
        );
        logger.info(LOG_GEN_CHARTS_EVENT_END, charts.size());
        return charts;
    }

    private static Map<String, JFreeChart> generatePrestationCharts(PrestationStats stats) {
        logger.info(LOG_GEN_CHARTS_PRESTATION_START);
        Map<String, JFreeChart> charts = Map.of(
                CHART_TITLE_PRESTATION_TYPE, ChartGenerator.createPrestationTypeDistributionChart(stats),
                CHART_TITLE_PRESTATION_CATEGORY, ChartGenerator.createPrestationCategoryDistributionChart(stats)
        );
        logger.info(LOG_GEN_CHARTS_PRESTATION_END, charts.size());
        return charts;
    }

    private static void generatePdfReport(PdfGenerator pdfGenerator, ProcessedStats stats,
                                        Map<String, JFreeChart> clientCharts,
                                        Map<String, JFreeChart> eventCharts,
                                        Map<String, JFreeChart> prestationCharts) throws ReportGenerationException {

        LocalDate today = LocalDate.now();
        DateTimeFormatter formatter = DateTimeFormatter.ofPattern(Constants.DATE_FORMAT_PATTERN);
        String formattedDate = today.format(formatter);
        String dynamicFilename = Constants.REPORT_FILENAME_PREFIX + formattedDate + Constants.REPORT_FILENAME_SUFFIX;
        String outputPath = Constants.OUTPUT_DIRECTORY + File.separator + dynamicFilename;

        logger.info(LOG_GEN_PDF_START, outputPath);

        OutputDirectoryUtil.createOutputDirectory(Constants.OUTPUT_DIRECTORY);

        try (PdfWriter writer = new PdfWriter(outputPath);
             PdfDocument pdfDoc = new PdfDocument(writer);
             Document document = new Document(pdfDoc)) {

            document.setMargins(Constants.PDF_MARGIN_TOP, Constants.PDF_MARGIN_RIGHT, Constants.PDF_MARGIN_BOTTOM, Constants.PDF_MARGIN_LEFT);

            pdfGenerator.generateTitlePage(document, formattedDate);

            
            for (Map.Entry<String, JFreeChart> entry : clientCharts.entrySet()) {
                pdfGenerator.addChartToNewPage(document, entry.getValue(), entry.getKey());
            }
            pdfGenerator.generateClientTop5Page(document, stats.clientStats);

            
            for (Map.Entry<String, JFreeChart> entry : eventCharts.entrySet()) {
                pdfGenerator.addChartToNewPage(document, entry.getValue(), entry.getKey());
            }

            
            for (Map.Entry<String, JFreeChart> entry : prestationCharts.entrySet()) {
                pdfGenerator.addChartToNewPage(document, entry.getValue(), entry.getKey());
            }
            
            
            

            logger.info(LOG_GEN_PDF_SUCCESS, pdfDoc.getNumberOfPages());

        } catch (IOException ioe) {
            logger.error(LOG_ERR_PDF_IO, outputPath, ioe.getMessage(), ioe);
            
            throw new ReportGenerationException(LOG_ERR_PDF_IO, ioe);
        }
    }
}
