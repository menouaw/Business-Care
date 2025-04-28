package com.businesscare.reporting.client;

import com.businesscare.reporting.exception.ApiException;
import com.businesscare.reporting.model.*;
import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.JavaType;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.datatype.jsr310.JavaTimeModule;
import org.apache.hc.client5.http.classic.methods.HttpGet;
import org.apache.hc.client5.http.classic.methods.HttpPost;
import org.apache.hc.client5.http.classic.methods.HttpUriRequestBase;
import org.apache.hc.client5.http.impl.classic.CloseableHttpClient;
import org.apache.hc.client5.http.impl.classic.HttpClients;
import org.apache.hc.core5.http.ClassicHttpResponse;
import org.apache.hc.core5.http.ContentType;
import org.apache.hc.core5.http.HttpEntity;
import org.apache.hc.core5.http.HttpStatus;
import org.apache.hc.core5.http.io.HttpClientResponseHandler;
import org.apache.hc.core5.http.io.entity.EntityUtils;
import org.apache.hc.core5.http.io.entity.StringEntity;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.io.IOException;
import java.util.List;
import java.util.Map;

/**
 * Client pour interagir avec l'API Admin de Business Care.
 * Implémente AutoCloseable pour gérer la fermeture du client HTTP.
 */
public class ApiClient implements AutoCloseable {

    private static final Logger logger = LoggerFactory.getLogger(ApiClient.class);

    
    private static final String AUTH_ENDPOINT = "auth.php";
    private static final String COMPANIES_ENDPOINT = "companies.php";
    private static final String CONTRACTS_ENDPOINT = "contracts.php";
    private static final String QUOTES_ENDPOINT = "quotes.php";
    private static final String INVOICES_ENDPOINT = "invoices.php";
    private static final String EVENTS_ENDPOINT = "events.php";
    private static final String PRESTATIONS_ENDPOINT = "services.php"; 

    private static final String AUTH_HEADER = "Authorization";
    private static final String BEARER_PREFIX = "Bearer ";

    private static final String ERR_NOT_AUTHENTICATED = "Non authentifié. Appeler login() d'abord.";
    private static final String ERR_AUTH_FAILED = "Échec de l'authentification API : ";
    private static final String ERR_INVALID_RESPONSE = "Format de réponse invalide";
    private static final String ERR_HTTP_ERROR = "Erreur HTTP : ";
    private static final String ERR_FETCH_FAILED = "Échec de la récupération des ";

    private final String baseUrl;
    private final ObjectMapper objectMapper;
    private final CloseableHttpClient httpClient;
    private String authToken = null;

    /**
     * Constructeur principal.
     * @param baseUrl URL de base de l'API Admin.
     */
    public ApiClient(String baseUrl) {
        this(baseUrl, HttpClients.createDefault(), createDefaultObjectMapper());
    }

    /**
     * Constructeur pour les tests, permettant l'injection de dépendances.
     * @param baseUrl URL de base.
     * @param httpClient Client HTTP à utiliser.
     * @param objectMapper Mapper JSON à utiliser.
     */
    public ApiClient(String baseUrl, CloseableHttpClient httpClient, ObjectMapper objectMapper) {
        this.baseUrl = baseUrl.endsWith("/") ? baseUrl : baseUrl + "/"; 
        this.httpClient = httpClient;
        this.objectMapper = objectMapper;
    }

    private static ObjectMapper createDefaultObjectMapper() {
        ObjectMapper mapper = new ObjectMapper();
        mapper.registerModule(new JavaTimeModule());
        
        return mapper;
    }

    /**
     * S'authentifie auprès de l'API en utilisant l'email et le mot de passe.
     *
     * @param email    Email de l'administrateur.
     * @param password Mot de passe de l'administrateur.
     * @return AuthResponse contenant le token et les informations utilisateur.
     * @throws ApiException Si l'authentification échoue ou en cas d'erreur de communication.
     */
    public AuthResponse login(String email, String password) throws ApiException {
        HttpPost post = new HttpPost(baseUrl + AUTH_ENDPOINT);
        Map<String, String> credentials = Map.of("email", email, "password", password);
        try {
            String jsonBody = objectMapper.writeValueAsString(credentials);
            post.setEntity(new StringEntity(jsonBody, ContentType.APPLICATION_JSON));

            return httpClient.execute(post, (HttpClientResponseHandler<AuthResponse>) response -> {
                int statusCode = response.getCode();
                HttpEntity entity = response.getEntity();
                String responseBody = (entity != null) ? EntityUtils.toString(entity) : null;

                if (statusCode >= HttpStatus.SC_OK && statusCode < HttpStatus.SC_MULTIPLE_CHOICES) {
                    if (responseBody == null) {
                        throw new ApiException(ERR_AUTH_FAILED + ERR_INVALID_RESPONSE + " (corps vide)");
                    }
                    AuthResponse authResponse = objectMapper.readValue(responseBody, AuthResponse.class);
                    if (authResponse != null && !authResponse.isError() && authResponse.getToken() != null) {
                        this.authToken = authResponse.getToken();
                        logger.info("Authentification réussie pour l'utilisateur: {}", authResponse.getUser() != null ? authResponse.getUser().getEmail() : "N/A");
                        return authResponse;
                    } else {
                        String apiMessage = (authResponse != null && authResponse.getMessage() != null) ? authResponse.getMessage() : ERR_INVALID_RESPONSE;
                        throw new ApiException(ERR_AUTH_FAILED + apiMessage);
                    }
                } else {
                    handleApiError(statusCode, responseBody, ERR_AUTH_FAILED);
                    return null; 
                }
            });
        } catch (IOException e) {
            throw new ApiException("Erreur de communication lors de la tentative de login", e);
        }
    }

    

    public List<Company> getCompanies() throws ApiException {
        return executeGetRequest(COMPANIES_ENDPOINT, "entreprises", new TypeReference<ApiResponse<List<Company>>>() {});
    }

    public List<Contract> getContracts() throws ApiException {
        return executeGetRequest(CONTRACTS_ENDPOINT, "contrats", new TypeReference<ApiResponse<List<Contract>>>() {});
    }

    public List<Quote> getQuotes() throws ApiException {
        return executeGetRequest(QUOTES_ENDPOINT, "devis", new TypeReference<ApiResponse<List<Quote>>>() {});
    }

    public List<Invoice> getInvoices() throws ApiException {
        return executeGetRequest(INVOICES_ENDPOINT, "factures", new TypeReference<ApiResponse<List<Invoice>>>() {});
    }

    public List<Event> getEvents() throws ApiException {
        return executeGetRequest(EVENTS_ENDPOINT, "évènements", new TypeReference<ApiResponse<List<Event>>>() {});
    }

    public List<Prestation> getPrestations() throws ApiException {
        return executeGetRequest(PRESTATIONS_ENDPOINT, "prestations", new TypeReference<ApiResponse<List<Prestation>>>() {});
    }

    

    private <T> T executeGetRequest(String endpoint, String entityName, TypeReference<ApiResponse<T>> typeReference) throws ApiException {
        if (this.authToken == null) {
            throw new ApiException(ERR_NOT_AUTHENTICATED);
        }

        HttpGet get = new HttpGet(baseUrl + endpoint);
        get.setHeader(AUTH_HEADER, BEARER_PREFIX + this.authToken);

        try {
            logger.debug("Exécution de la requête GET sur l'endpoint : {}", endpoint);
            return httpClient.execute(get, (HttpClientResponseHandler<T>) response -> {
                int statusCode = response.getCode();
                HttpEntity entity = response.getEntity();
                String responseBody = (entity != null) ? EntityUtils.toString(entity) : null;

                if (statusCode >= HttpStatus.SC_OK && statusCode < HttpStatus.SC_MULTIPLE_CHOICES) {
                    if (responseBody == null) {
                        throw new ApiException(ERR_FETCH_FAILED + entityName + " : " + ERR_INVALID_RESPONSE + " (corps vide)");
                    }
                    
                    ApiResponse<T> apiResponse = objectMapper.readValue(responseBody, typeReference);

                    if (apiResponse != null && !apiResponse.isError()) {
                        logger.debug("Récupération réussie des {}", entityName);
                        return apiResponse.getData();
                    } else {
                        String apiMessage = (apiResponse != null && apiResponse.getMessage() != null) ? apiResponse.getMessage() : ERR_INVALID_RESPONSE;
                        throw new ApiException(ERR_FETCH_FAILED + entityName + " : " + apiMessage);
                    }
                } else {
                    handleApiError(statusCode, responseBody, ERR_FETCH_FAILED + entityName);
                    return null; 
                }
            });
        } catch (IOException e) {
            throw new ApiException("Erreur de communication lors de la récupération des " + entityName, e);
        }
    }

    

    private void handleApiError(int statusCode, String responseBody, String errorPrefix) throws ApiException {
        ErrorResponse errorResponse = parseErrorResponse(responseBody);
        String errorMessage = (errorResponse != null && errorResponse.getMessage() != null)
                ? errorResponse.getMessage()
                : ERR_HTTP_ERROR + statusCode + (responseBody != null ? " - " + responseBody.substring(0, Math.min(responseBody.length(), 100)) : "");
        logger.error("{} : Code {} - {}", errorPrefix, statusCode, errorMessage);
        throw new ApiException(errorPrefix + " : " + errorMessage);
    }

    private ErrorResponse parseErrorResponse(String responseBody) {
        if (responseBody == null || responseBody.isEmpty()) {
            return null;
        }
        try {
            return objectMapper.readValue(responseBody, ErrorResponse.class);
        } catch (IOException ignored) {
            
            logger.warn("Impossible de parser la réponse d'erreur JSON: {}", responseBody.substring(0, Math.min(responseBody.length(), 100)));
            return null;
        }
    }

    @Override
    public void close() throws IOException {
        if (httpClient != null) {
            httpClient.close();
            logger.info("Client HTTP fermé.");
        }
    }
}
