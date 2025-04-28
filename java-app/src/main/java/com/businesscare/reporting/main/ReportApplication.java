package com.businesscare.reporting.main;

import com.businesscare.reporting.chart.ChartGenerator;
import com.businesscare.reporting.client.ApiClient;
import com.businesscare.reporting.client.ApiConfig;
import com.businesscare.reporting.exception.ApiException;
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
import java.io.FileNotFoundException;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

public class ReportApplication {

    private static final Logger logger = LoggerFactory.getLogger(ReportApplication.class);

    public static void main(String[] args) {
        logger.info("Application de reporting démarrée.");

        ApiClient apiClient = null;
        ReportService reportService = new ReportService();
        PdfGenerator pdfGenerator = new PdfGenerator();

        LocalDate today = LocalDate.now();
        DateTimeFormatter formatter = DateTimeFormatter.ofPattern("dd-MM-yyyy");
        String formattedDate = today.format(formatter);
        String dynamicFilename = "report_" + formattedDate + ".pdf";
        String outputPath = Constants.OUTPUT_DIRECTORY + File.separator + dynamicFilename;

        try {
            ApiConfig config = ConfigLoader.loadApiConfig();
            logger.info("Configuration API chargée pour URL: {}", config.getBaseUrl());

            apiClient = new ApiClient(config.getBaseUrl());

            logger.info("Tentative d'authentification auprès de {}...", config.getBaseUrl());
            AuthResponse auth = apiClient.login(config.getApiUser(), config.getApiPassword());
            logger.info("Authentification réussie pour {}", auth.getUser() != null ? auth.getUser().email : "N/A");

            logger.info("Récupération des données depuis l'API...");
            List<Company> companies = apiClient.getCompanies();
            List<Contract> contracts = apiClient.getContracts();
            List<Quote> quotes = apiClient.getQuotes();
            List<Invoice> invoices = apiClient.getInvoices();
            List<Event> events = apiClient.getEvents();
            List<Prestation> prestations = apiClient.getPrestations();
            logger.info("Données récupérées: {} entreprises, {} contrats, {} devis, {} factures, {} évènements, {} prestations.",
                    companies.size(), contracts.size(), quotes.size(), invoices.size(), events.size(), prestations.size());

            logger.info("Traitement des données financières client...");
            ClientStats clientStats = reportService.processClientFinancialData(companies, contracts, invoices);
            logger.info("Traitement des données financières terminé.");

            logger.info("Traitement des données d'évènements...");
            EventStats eventStats = reportService.processEventData(events);
            logger.info("Traitement des données d'évènements terminé.");

            logger.info("Traitement des données de prestations...");
            PrestationStats prestationStats = reportService.processPrestationData(prestations);
            logger.info("Traitement des données de prestations terminé.");

            logger.info("Génération des graphiques financiers...");
            Map<String, JFreeChart> clientCharts = Map.of(
                "Répartition des Contrats par Statut", ChartGenerator.createContractStatusChart(clientStats),
                "Répartition des Clients par Secteur", ChartGenerator.createClientDistributionBySectorChart(clientStats),
                "Répartition des Clients par Taille", ChartGenerator.createClientDistributionBySizeChart(clientStats),
                "Répartition des Revenus par Client", ChartGenerator.createClientRevenueDistributionChart(clientStats)
            );
            logger.info("{} graphiques financiers générés.", clientCharts.size());

            logger.info("Génération des graphiques d'évènements...");
            Map<String, JFreeChart> eventCharts = Map.of(
                "Répartition des Évènements par Type (Camembert)", ChartGenerator.createEventTypeDistributionChart(eventStats),
                "Répartition des Évènements par Type (Barres)", ChartGenerator.createEventTypeDistributionBarChart(eventStats)
            );
            logger.info("{} graphiques d'évènements générés.", eventCharts.size());

            logger.info("Génération des graphiques de prestations...");
            Map<String, JFreeChart> prestationCharts = Map.of(
                "Répartition des Prestations par Type", ChartGenerator.createPrestationTypeDistributionChart(prestationStats),
                "Répartition des Prestations par Catégorie", ChartGenerator.createPrestationCategoryDistributionChart(prestationStats)
            );
            logger.info("{} graphiques de prestations générés.", prestationCharts.size());

            logger.info("Génération du rapport PDF : {}", outputPath);

            File outputDir = new File(Constants.OUTPUT_DIRECTORY);
            if (!outputDir.exists()) {
                if(outputDir.mkdirs()) {
                    logger.info("Répertoire de sortie créé : {}", Constants.OUTPUT_DIRECTORY);
                } else {
                    logger.error("Impossible de créer le répertoire de sortie : {}", Constants.OUTPUT_DIRECTORY);
                    throw new IOException("Impossible de créer le répertoire de sortie: " + Constants.OUTPUT_DIRECTORY);
                }
            }

            
            try (PdfWriter writer = new PdfWriter(outputPath);
                 PdfDocument pdfDoc = new PdfDocument(writer);
                 Document document = new Document(pdfDoc)) {

                 document.setMargins(50, 50, 50, 50); 

                 pdfGenerator.generateTitlePage(document, formattedDate);

                 for (Map.Entry<String, JFreeChart> entry : clientCharts.entrySet()) {
                     pdfGenerator.addChartToNewPage(document, entry.getValue(), entry.getKey());
                 }
                 
                 pdfGenerator.generateClientTop5Page(document, clientStats);

                 for (Map.Entry<String, JFreeChart> entry : eventCharts.entrySet()) {
                     pdfGenerator.addChartToNewPage(document, entry.getValue(), entry.getKey());
                 }
                 
                 pdfGenerator.generateEventTop5Page(document, eventStats);

                 for (Map.Entry<String, JFreeChart> entry : prestationCharts.entrySet()) {
                     pdfGenerator.addChartToNewPage(document, entry.getValue(), entry.getKey());
                 }
                 
                 logger.info("Rapport PDF généré avec succès ({} pages).", pdfDoc.getNumberOfPages());

            } catch (FileNotFoundException fnfe) {
                logger.error("Impossible de créer ou d'écrire dans le fichier PDF : {} ({})", outputPath, fnfe.getMessage(), fnfe);
            } catch (IOException ioe) {
                logger.error("Erreur I/O lors de la génération du PDF : {} ({})", outputPath, ioe.getMessage(), ioe);
            }

        } catch (ApiException e) {
            logger.error("Erreur API: {} ({})", e.getMessage(), e.getCause() != null ? e.getCause().getMessage() : "N/A", e);
        } catch (Exception e) {
            logger.error("Erreur inattendue dans l'application: {} ({})", e.getMessage(), e.getClass().getSimpleName(), e);
        } finally {
            logger.info("Application de reporting terminée.");
            if (apiClient instanceof AutoCloseable) {
                try {
                     ((AutoCloseable) apiClient).close();
                     logger.debug("HttpClient fermé.");
                } catch (Exception e) {
                     logger.error("Erreur lors de la fermeture du HttpClient", e);
                }
            }
        }
    }
}
