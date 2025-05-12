package com.businesscare.reporting.jsonmain;

import com.businesscare.reporting.client.ApiClient;
import com.businesscare.reporting.client.ApiConfig;
import com.businesscare.reporting.exception.ApiException;
import com.businesscare.reporting.model.AllData;
import com.businesscare.reporting.model.AuthResponse;
import com.businesscare.reporting.model.ProcessedStats;
import com.businesscare.reporting.service.ReportService;
import com.businesscare.reporting.util.ConfigLoader;
import com.businesscare.reporting.util.Constants;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.SerializationFeature;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.util.List;
import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

import com.businesscare.reporting.model.*;

public class JsonReportLauncher {

    private static final Logger logger = LoggerFactory.getLogger(JsonReportLauncher.class);

    private static final String DATE_FORMAT_PATTERN = "dd-MM-yyyy";
    private static final String JSON_FILENAME_PREFIX = "report_";
    private static final String JSON_FILENAME_SUFFIX = ".json";
    private static final String JSON_OUTPUT_SUBDIRECTORY = "json";
    private static final String LOG_CREATE_DIR_SUCCESS = "Répertoire de sortie JSON créé : {}";
    private static final String LOG_ERR_CREATE_DIR = "Impossible de créer le répertoire de sortie JSON : {}";
    private static final String ERR_CREATE_DIR = "Impossible de créer le répertoire de sortie JSON: ";
    private static final String LOG_ERR_JSON_IO = "Erreur I/O lors de la génération du fichier JSON : {} ({})";

    public static void main(String[] args) {
        logger.info("JSON reporting application started.");
        ReportService reportService = new ReportService();

        try {
            ApiConfig config = loadConfiguration();

            try (ApiClient apiClient = new ApiClient(config.getBaseUrl())) {
                authenticate(apiClient, config);
                
                AllData data = fetchData(apiClient);
                
                ProcessedStats stats = processData(reportService, data);

                generateJsonOutput(stats);

            } 

        } catch (ApiException e) {
            logger.error("API error: {} ({})", e.getMessage(), e.getCause() != null ? e.getCause().getMessage() : "N/A", e);
        } catch (JsonProcessingException jpe) {
             logger.error("Error processing JSON: {} ({})", jpe.getMessage(), jpe.getClass().getSimpleName(), jpe);
        } catch (IOException ioe) {
             logger.error(LOG_ERR_JSON_IO, "N/A", ioe.getMessage(), ioe); // N/A as file path is determined later
        } catch (Exception e) {
            logger.error("Unexpected error in application: {} ({})", e.getMessage(), e.getClass().getSimpleName(), e);
        }

        logger.info("JSON reporting application finished.");
    }

    private static ApiConfig loadConfiguration() {
        logger.info("Loading API configuration...");
        ApiConfig config = ConfigLoader.loadApiConfig();
        logger.info("API configuration loaded for URL: {}", config.getBaseUrl());
        return config;
    }

    private static void authenticate(ApiClient client, ApiConfig config) throws ApiException {
        logger.info("Attempting authentication with {}...", config.getBaseUrl());
        AuthResponse auth = client.login(config.getApiUser(), config.getApiPassword());
        // The AuthResponse object contains the token, which is handled internally by ApiClient
        logger.info("Authentication successful for {}", config.getBaseUrl());
    }

    private static AllData fetchData(ApiClient client) throws ApiException {
        logger.info("Fetching data from API...");
        List<Company> companies = client.getCompanies();
        List<Contract> contracts = client.getContracts();
        List<Quote> quotes = client.getQuotes();
        List<Invoice> invoices = client.getInvoices();
        List<Event> events = client.getEvents();
        List<Prestation> prestations = client.getPrestations();
        logger.info("Data fetched: {} companies, {} contracts, {} quotes, {} invoices, {} events, {} prestations.",
                    companies.size(), contracts.size(), quotes.size(), invoices.size(), events.size(), prestations.size());
        return new AllData(companies, contracts, quotes, invoices, events, prestations);
    }

    private static ProcessedStats processData(ReportService service, AllData data) {
        logger.info("Processing client financial data...");
        ClientStats clientStats = service.processClientFinancialData(data.companies, data.contracts, data.invoices);
        logger.info("Client financial data processing finished.");

        logger.info("Processing event data...");
        EventStats eventStats = service.processEventData(data.events);
        logger.info("Event data processing finished.");

        logger.info("Processing prestation data...");
        PrestationStats prestationStats = service.processPrestationData(data.prestations);
        logger.info("Prestation data processing finished.");

        return new ProcessedStats(clientStats, eventStats, prestationStats);
    }

    private static void generateJsonOutput(ProcessedStats stats) throws JsonProcessingException, IOException {
        logger.info("Generating JSON output...");
        ObjectMapper objectMapper = new ObjectMapper();
        objectMapper.enable(SerializationFeature.INDENT_OUTPUT);

        String jsonString = objectMapper.writeValueAsString(stats);

        // Determine output path and create directory
        LocalDate today = LocalDate.now();
        DateTimeFormatter formatter = DateTimeFormatter.ofPattern(DATE_FORMAT_PATTERN);
        String formattedDate = today.format(formatter);
        String dynamicFilename = JSON_FILENAME_PREFIX + formattedDate + JSON_FILENAME_SUFFIX;
        String outputDirectory = Constants.OUTPUT_DIRECTORY + File.separator + JSON_OUTPUT_SUBDIRECTORY;
        String outputPath = outputDirectory + File.separator + dynamicFilename;

        createOutputDirectory(outputDirectory);

        // Write JSON to file
        try (FileWriter fileWriter = new FileWriter(outputPath)) {
            fileWriter.write(jsonString);
        }

        logger.info("JSON output generated and saved to {}.", outputPath);
        System.out.println("JSON output (also saved to " + outputPath + "):");
        System.out.println(jsonString);
    }

    private static void createOutputDirectory(String directoryPath) throws IOException {
        File outputDir = new File(directoryPath);
        if (!outputDir.exists()) {
            if (outputDir.mkdirs()) {
                logger.info(LOG_CREATE_DIR_SUCCESS, directoryPath);
            } else {
                logger.error(LOG_ERR_CREATE_DIR, directoryPath);
                throw new IOException(ERR_CREATE_DIR + directoryPath);
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