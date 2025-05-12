package com.businesscare.reporting.client;

import com.businesscare.reporting.exception.ApiException;
import com.businesscare.reporting.model.AllData;
import com.businesscare.reporting.model.AuthResponse;
import com.businesscare.reporting.model.*;
import com.businesscare.reporting.util.ConfigLoader;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.io.IOException;
import java.util.List;

public class ApiDataFetcher {

    private static final Logger logger = LoggerFactory.getLogger(ApiDataFetcher.class);
    private static final String LOG_CONFIG_LOADED = "Configuration API chargée pour URL: {}";
    private static final String LOG_AUTH_ATTEMPT = "Tentative d'authentification auprès de {}...";
    private static final String LOG_AUTH_SUCCESS = "Authentification réussie pour {}";
    private static final String LOG_FETCH_DATA_START = "Récupération des données depuis l'API...";
    private static final String LOG_FETCH_DATA_END = "Données récupérées: {} entreprises, {} contrats, {} devis, {} factures, {} évènements, {} prestations.";

    public AllData fetchAllData() throws ApiException, IOException {
        ApiConfig config = loadConfiguration();
        try (ApiClient apiClient = new ApiClient(config.getBaseUrl())) {
            authenticate(apiClient, config);
            return fetchData(apiClient);
        }
    }

    private ApiConfig loadConfiguration() {
        ApiConfig config = ConfigLoader.loadApiConfig();
        logger.info(LOG_CONFIG_LOADED, config.getBaseUrl());
        return config;
    }

    private void authenticate(ApiClient client, ApiConfig config) throws ApiException {
        logger.info(LOG_AUTH_ATTEMPT, config.getBaseUrl());
        AuthResponse auth = client.login(config.getApiUser(), config.getApiPassword());
        logger.info(LOG_AUTH_SUCCESS, config.getBaseUrl());
    }

    private AllData fetchData(ApiClient client) throws ApiException {
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
} 