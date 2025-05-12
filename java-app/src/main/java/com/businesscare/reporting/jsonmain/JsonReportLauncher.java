package com.businesscare.reporting.jsonmain;

import com.businesscare.reporting.client.ApiClient;
import com.businesscare.reporting.client.ApiConfig;
import com.businesscare.reporting.exception.ApiException;
import com.businesscare.reporting.model.AuthResponse;
import com.businesscare.reporting.service.ReportService;
import com.businesscare.reporting.util.ConfigLoader;
import com.businesscare.reporting.util.Constants;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.SerializationFeature;
import com.fasterxml.jackson.datatype.jsr310.JavaTimeModule;

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

    
    private static final String LOG_APP_START = "Application de reporting JSON démarrée.";
    private static final String LOG_APP_END = "Application de reporting JSON terminée.";
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
    private static final String LOG_GEN_JSON_START = "Génération de la sortie JSON...";
    private static final String LOG_GEN_JSON_SUCCESS = "Sortie JSON générée et sauvegardée vers {}.";
    private static final String LOG_CREATE_DIR_SUCCESS = "Répertoire de sortie JSON créé : {}";
    private static final String LOG_ERR_CREATE_DIR = "Impossible de créer le répertoire de sortie JSON : {}";
    private static final String ERR_CREATE_DIR = "Impossible de créer le répertoire de sortie JSON: ";
    private static final String LOG_ERR_JSON_IO = "Erreur I/O lors de la génération du fichier JSON : {} ({})";
    private static final String LOG_ERR_API = "Erreur API: {} ({})";
    private static final String LOG_ERR_UNEXPECTED = "Erreur inattendue dans l'application: {} ({})";

    private static final String DATE_FORMAT_PATTERN = "dd-MM-yyyy";
    private static final String JSON_FILENAME_PREFIX = "report_";
    private static final String JSON_FILENAME_SUFFIX = ".json";
    private static final String JSON_OUTPUT_SUBDIRECTORY = "json";

    public static void main(String[] args) {
        logger.info(LOG_APP_START);
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
            logger.error(LOG_ERR_API, e.getMessage(), e.getCause() != null ? e.getCause().getMessage() : "N/A", e);
        } catch (JsonProcessingException jpe) {
             logger.error(LOG_ERR_JSON_IO, jpe.getMessage(), jpe.getClass().getSimpleName(), jpe);
        } catch (IOException ioe) {
             logger.error(LOG_ERR_JSON_IO, "N/A", ioe.getMessage(), ioe);
        } catch (Exception e) {
            logger.error(LOG_ERR_UNEXPECTED, e.getMessage(), e.getClass().getSimpleName(), e);
        }

        logger.info(LOG_APP_END);
    }

    private static ApiConfig loadConfiguration() {
        logger.info(LOG_CONFIG_LOADED);
        ApiConfig config = ConfigLoader.loadApiConfig();
        logger.info(LOG_CONFIG_LOADED, config.getBaseUrl());
        return config;
    }

    private static void authenticate(ApiClient client, ApiConfig config) throws ApiException {
        logger.info(LOG_AUTH_ATTEMPT, config.getBaseUrl());
        AuthResponse auth = client.login(config.getApiUser(), config.getApiPassword());
        logger.info(LOG_AUTH_SUCCESS, config.getBaseUrl());
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

    private static void generateJsonOutput(ProcessedStats stats) throws JsonProcessingException, IOException {
        logger.info(LOG_GEN_JSON_START);
        ObjectMapper objectMapper = new ObjectMapper();
        objectMapper.registerModule(new JavaTimeModule());
        objectMapper.enable(SerializationFeature.INDENT_OUTPUT);

        String jsonString = objectMapper.writeValueAsString(stats);

        
        LocalDate today = LocalDate.now();
        DateTimeFormatter formatter = DateTimeFormatter.ofPattern(DATE_FORMAT_PATTERN);
        String formattedDate = today.format(formatter);
        String dynamicFilename = JSON_FILENAME_PREFIX + formattedDate + JSON_FILENAME_SUFFIX;
        String outputDirectory = Constants.OUTPUT_DIRECTORY + File.separator + JSON_OUTPUT_SUBDIRECTORY;
        String outputPath = outputDirectory + File.separator + dynamicFilename;

        createOutputDirectory(outputDirectory);

        
        try (FileWriter fileWriter = new FileWriter(outputPath)) {
            fileWriter.write(jsonString);
        }

        logger.info(LOG_GEN_JSON_SUCCESS, outputPath);
        System.out.println("Sortie JSON générée et sauvegardée vers " + outputPath + ":");
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

        public ClientStats getClientStats() {
            return clientStats;
        }

        public EventStats getEventStats() {
            return eventStats;
        }

        public PrestationStats getPrestationStats() {
            return prestationStats;
        }
    }
} 