package com.businesscare.reporting.main;

import com.businesscare.reporting.chart.ChartGenerator;
import com.businesscare.reporting.client.ApiClient;
import com.businesscare.reporting.client.ApiConfig;
import com.businesscare.reporting.exception.ApiException;
import com.businesscare.reporting.exception.ReportGenerationException;
import com.businesscare.reporting.model.*;
import com.businesscare.reporting.pdf.PdfGenerator;
import com.businesscare.reporting.service.ReportService;
import com.businesscare.reporting.util.ConfigLoader;
import com.businesscare.reporting.util.Constants;

import com.itextpdf.kernel.pdf.PdfDocument;
import com.itextpdf.kernel.pdf.PdfWriter;
import com.itextpdf.layout.Document;

import org.jfree.chart.JFreeChart;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.io.File;
import java.io.IOException;
import java.util.List;
import java.util.Map;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

/**
 * Classe principale de l'application de reporting.
 * Orchestre la récupération des données, le traitement, et la génération du PDF.
 */
public class ReportApplication {

    private static final Logger logger = LoggerFactory.getLogger(ReportApplication.class);

    
    private static final String DATE_FORMAT_PATTERN = "dd-MM-yyyy";
    private static final String REPORT_FILENAME_PREFIX = "report_";
    private static final String REPORT_FILENAME_SUFFIX = ".pdf";
    private static final float PDF_MARGIN_TOP = 50f;
    private static final float PDF_MARGIN_RIGHT = 50f;
    private static final float PDF_MARGIN_BOTTOM = 50f;
    private static final float PDF_MARGIN_LEFT = 50f;

    private static final String CHART_TITLE_CONTRACT_STATUS = "Répartition des contrats par statut";
    private static final String CHART_TITLE_CLIENT_SECTOR = "Répartition des clients par secteur";
    private static final String CHART_TITLE_CLIENT_SIZE = "Répartition des clients par taille";
    private static final String CHART_TITLE_CLIENT_REVENUE = "Répartition des revenus par client";
    private static final String CHART_TITLE_EVENT_TYPE_PIE = "Répartition des évènements par type (camembert)";
    private static final String CHART_TITLE_EVENT_TYPE_BAR = "Répartition des évènements par type (barres)";
    private static final String CHART_TITLE_PRESTATION_TYPE = "Répartition des prestations par type";
    private static final String CHART_TITLE_PRESTATION_CATEGORY = "Répartition des prestations par catégorie";

    private static final String LOG_APP_START = "Application de reporting démarrée.";
    private static final String LOG_APP_END = "Application de reporting terminée.";
    private static final String LOG_CONFIG_LOADED = "Configuration API chargée pour URL: {}";
    private static final String LOG_AUTH_ATTEMPT = "Tentative d'authentification auprès de {}...";
    private static final String LOG_AUTH_SUCCESS = "Authentification réussie pour {}";
    private static final String LOG_FETCH_DATA_START = "Récupération des données depuis l'API...";
    private static final String LOG_FETCH_DATA_END = "Données récupérées: {} entreprises, {} contrats, {} devis, {} factures, {} évènements, {} prestations.";
    private static final String LOG_PROCESS_CLIENT_START = "Traitement des données financières client...";
    private static final String LOG_PROCESS_CLIENT_END = "Traitement des données financières terminé.";
    private static final String LOG_PROCESS_EVENT_START = "Traitement des données d'évènements...";
    private static final String LOG_PROCESS_EVENT_END = "Traitement des données d'évènements terminé.";
    private static final String LOG_PROCESS_PRESTATION_START = "Traitement des données de prestations...";
    private static final String LOG_PROCESS_PRESTATION_END = "Traitement des données de prestations terminé.";
    private static final String LOG_GEN_CHARTS_CLIENT_START = "Génération des graphiques financiers...";
    private static final String LOG_GEN_CHARTS_CLIENT_END = "{} graphiques financiers générés.";
    private static final String LOG_GEN_CHARTS_EVENT_START = "Génération des graphiques d'évènements...";
    private static final String LOG_GEN_CHARTS_EVENT_END = "{} graphiques d'évènements générés.";
    private static final String LOG_GEN_CHARTS_PRESTATION_START = "Génération des graphiques de prestations...";
    private static final String LOG_GEN_CHARTS_PRESTATION_END = "{} graphiques de prestations générés.";
    private static final String LOG_GEN_PDF_START = "Génération du rapport PDF : {}";
    private static final String LOG_GEN_PDF_SUCCESS = "Rapport PDF généré avec succès ({} pages).";
    private static final String LOG_CREATE_DIR_SUCCESS = "Répertoire de sortie créé : {}";
    private static final String LOG_ERR_CREATE_DIR = "Impossible de créer le répertoire de sortie : {}";
    private static final String ERR_CREATE_DIR = "Impossible de créer le répertoire de sortie: ";
    private static final String LOG_ERR_PDF_IO = "Erreur I/O lors de la génération du PDF : {} ({})";
    private static final String LOG_ERR_API = "Erreur API: {} ({})";
    private static final String LOG_ERR_UNEXPECTED = "Erreur inattendue dans l'application: {} ({})";
    private static final String LOG_ERR_PDF_IO = "Erreur I/O lors de la génération du PDF : {} ({})";

    public static void main(String[] args) {
        logger.info(LOG_APP_START);
        ReportService reportService = new ReportService();
        PdfGenerator pdfGenerator = new PdfGenerator();

        try {
            ApiConfig config = loadConfiguration();

            try (ApiClient apiClient = new ApiClient(config.getBaseUrl())) {
                authenticate(apiClient, config);

                
                AllData data = fetchData(apiClient);

                
                ProcessedStats stats = processData(reportService, data);

                
                Map<String, JFreeChart> clientCharts = generateClientCharts(stats.clientStats);
                Map<String, JFreeChart> eventCharts = generateEventCharts(stats.eventStats);
                Map<String, JFreeChart> prestationCharts = generatePrestationCharts(stats.prestationStats);

                
                generatePdfReport(pdfGenerator, stats, clientCharts, eventCharts, prestationCharts);

            } 

        } catch (ApiException e) {
            logger.error(LOG_ERR_API, e.getMessage(), e.getCause() != null ? e.getCause().getMessage() : "N/A", e);
        } catch (ReportGenerationException rge) {
            logger.error(LOG_ERR_PDF_IO, rge.getMessage(), rge.getCause() != null ? rge.getCause().getMessage() : "N/A", rge);
        } catch (Exception e) {
            logger.error(LOG_ERR_UNEXPECTED, e.getMessage(), e.getClass().getSimpleName(), e);
        }

        logger.info(LOG_APP_END);
    }

    

    private static ApiConfig loadConfiguration() {
        ApiConfig config = ConfigLoader.loadApiConfig();
        logger.info(LOG_CONFIG_LOADED, config.getBaseUrl());
        return config;
    }

    private static void authenticate(ApiClient client, ApiConfig config) throws ApiException {
        logger.info(LOG_AUTH_ATTEMPT, config.getBaseUrl());
        AuthResponse auth = client.login(config.getApiUser(), config.getApiPassword());
        
        
    }

    private static AllData fetchData(ApiClient client) throws ApiException {
        logger.info(LOG_FETCH_DATA_START);
        List<Company> companies = client.getCompanies();
        List<Contract> contracts = client.getContracts();
        List<Quote> quotes = client.getQuotes(); 
        List<Invoice> invoices = client.getInvoices();
        List<Event> events = client.getEvents();
        List<Prestation> prestations = client.getPrestations();
        logger.info(LOG_FETCH_DATA_END, companies.size(), contracts.size(), quotes.size(), invoices.size(), events.size(), prestations.size());
        return new AllData(companies, contracts, quotes, invoices, events, prestations);
    }

    private static ProcessedStats processData(ReportService service, AllData data) {
        logger.info(LOG_PROCESS_CLIENT_START);
        ClientStats clientStats = service.processClientFinancialData(data.companies, data.contracts, data.invoices);
        logger.info(LOG_PROCESS_CLIENT_END);

        logger.info(LOG_PROCESS_EVENT_START);
        EventStats eventStats = service.processEventData(data.events);
        logger.info(LOG_PROCESS_EVENT_END);

        logger.info(LOG_PROCESS_PRESTATION_START);
        PrestationStats prestationStats = service.processPrestationData(data.prestations);
        logger.info(LOG_PROCESS_PRESTATION_END);

        return new ProcessedStats(clientStats, eventStats, prestationStats);
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
        DateTimeFormatter formatter = DateTimeFormatter.ofPattern(DATE_FORMAT_PATTERN);
        String formattedDate = today.format(formatter);
        String dynamicFilename = REPORT_FILENAME_PREFIX + formattedDate + REPORT_FILENAME_SUFFIX;
        String outputPath = Constants.OUTPUT_DIRECTORY + File.separator + dynamicFilename;

        logger.info(LOG_GEN_PDF_START, outputPath);

        createOutputDirectory(); 

        try (PdfWriter writer = new PdfWriter(outputPath);
             PdfDocument pdfDoc = new PdfDocument(writer);
             Document document = new Document(pdfDoc)) {

            document.setMargins(PDF_MARGIN_TOP, PDF_MARGIN_RIGHT, PDF_MARGIN_BOTTOM, PDF_MARGIN_LEFT);

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
            
            throw new ReportGenerationException(LOG_ERR_PDF_IO, outputPath, ioe.getMessage(), ioe);
        }
    }

    private static void createOutputDirectory() throws ReportGenerationException {
        File outputDir = new File(Constants.OUTPUT_DIRECTORY);
        if (!outputDir.exists()) {
            if (outputDir.mkdirs()) {
                logger.info(LOG_CREATE_DIR_SUCCESS, Constants.OUTPUT_DIRECTORY);
            } else {
                logger.error(LOG_ERR_CREATE_DIR, Constants.OUTPUT_DIRECTORY);
                
                throw new ReportGenerationException(ERR_CREATE_DIR + Constants.OUTPUT_DIRECTORY);
            }
        }
    }

    

    /** Regroupe toutes les données brutes récupérées de l'API. */
    private static class AllData {
        final List<Company> companies;
        final List<Contract> contracts;
        final List<Quote> quotes;
        final List<Invoice> invoices;
        final List<Event> events;
        final List<Prestation> prestations;

        AllData(List<Company> c, List<Contract> co, List<Quote> q, List<Invoice> i, List<Event> e, List<Prestation> p) {
            this.companies = c; this.contracts = co; this.quotes = q;
            this.invoices = i; this.events = e; this.prestations = p;
        }
    }

    /** Regroupe toutes les statistiques calculées. */
    private static class ProcessedStats {
        final ClientStats clientStats;
        final EventStats eventStats;
        final PrestationStats prestationStats;

        ProcessedStats(ClientStats cs, EventStats es, PrestationStats ps) {
            this.clientStats = cs; this.eventStats = es; this.prestationStats = ps;
        }
    }
}
