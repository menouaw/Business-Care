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


import org.jfree.chart.JFreeChart;


import org.slf4j.Logger;
import org.slf4j.LoggerFactory;


import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;

public class ReportApplication {

    private static final Logger logger = LoggerFactory.getLogger(ReportApplication.class);

    public static void main(String[] args) {
        logger.info("Application de reporting démarrée.");

        
        ApiClient apiClient = null;
        ReportService reportService = new ReportService();
        PdfGenerator pdfGenerator = new PdfGenerator();

        String outputPath = Constants.OUTPUT_DIRECTORY + File.separator + Constants.REPORT_FILENAME;

        try {
            
            ApiConfig config = ConfigLoader.loadApiConfig();
            logger.info("Configuration API chargée pour URL: {}", config.getBaseUrl());

            
            apiClient = new ApiClient(config.getBaseUrl());

            
            logger.info("Tentative d'authentification auprès de {}...", config.getBaseUrl());
            apiClient.login(config.getApiUser(), config.getApiPassword());
            logger.info("Authentification réussie.");

            
            logger.info("Récupération des données depuis l'API...");
            List<Company> companies = apiClient.getCompanies();
            List<Contract> contracts = apiClient.getContracts();
            List<Invoice> invoices = apiClient.getInvoices();
            
            
            logger.info("Données récupérées: {} entreprises, {} contrats, {} factures.",
                    companies.size(), contracts.size(), invoices.size());

            
            

            
            logger.info("Traitement des données financières client...");
            ClientStats clientStats = reportService.processClientFinancialData(companies, contracts, invoices);
            logger.info("Traitement des données financières terminé.");

            
            logger.info("Génération des graphiques financiers (Page 1)..." );
            List<JFreeChart> clientCharts = new ArrayList<>();
            
            clientCharts.add(ChartGenerator.createContractStatusChart(clientStats));
            clientCharts.add(ChartGenerator.createClientDistributionBySectorChart(clientStats));
            clientCharts.add(ChartGenerator.createClientDistributionBySizeChart(clientStats));
            clientCharts.add(ChartGenerator.createClientRevenueDistributionChart(clientStats));
            logger.info("{} graphiques financiers générés.", clientCharts.size());

            
            logger.warn("Génération des graphiques pour les événements (Page 2) non implémentée.");

            
            logger.warn("Génération des graphiques pour les services (Page 3) non implémentée.");

            
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
                 PdfDocument pdfDoc = new PdfDocument(writer)) {

                
                pdfGenerator.generateClientFinancialPage(pdfDoc, clientStats, clientCharts);

                
                
                logger.warn("Génération de la page PDF pour les événements (Page 2) non implémentée.");

                
                
                logger.warn("Génération de la page PDF pour les services (Page 3) non implémentée.");

                
                logger.info("Rapport PDF généré avec succès.");

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
