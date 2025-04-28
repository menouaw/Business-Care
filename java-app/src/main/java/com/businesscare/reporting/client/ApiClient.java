package com.businesscare.reporting.client;

import com.businesscare.reporting.exception.ApiException;
import com.businesscare.reporting.model.*;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.datatype.jsr310.JavaTimeModule;
import org.apache.hc.client5.http.classic.methods.HttpGet;
import org.apache.hc.client5.http.classic.methods.HttpPost;
import org.apache.hc.client5.http.impl.classic.CloseableHttpClient;
import org.apache.hc.client5.http.impl.classic.HttpClients;
import org.apache.hc.core5.http.ContentType;
import org.apache.hc.core5.http.io.entity.EntityUtils;
import org.apache.hc.core5.http.io.entity.StringEntity;

import java.io.IOException;
import java.util.List;
import java.util.Map;

/**
 * Client pour interagir avec l'API Admin de Business Care.
 */
public class ApiClient {

    private final String baseUrl;
    private final ObjectMapper objectMapper;
    private String authToken = null;
    private final CloseableHttpClient httpClient;

    public ApiClient(String baseUrl) {
        this.baseUrl = baseUrl.endsWith("/") ? baseUrl : baseUrl + "/";
        this.objectMapper = new ObjectMapper();
        this.objectMapper.registerModule(new JavaTimeModule());
        this.httpClient = HttpClients.createDefault();
    }

    
    public ApiClient(String baseUrl, CloseableHttpClient httpClient, ObjectMapper objectMapper) {
        this.baseUrl = baseUrl.endsWith("/") ? baseUrl : baseUrl + "/";
        this.httpClient = httpClient;
        this.objectMapper = objectMapper;
        this.objectMapper.registerModule(new JavaTimeModule());
    }

    /**
     * S'authentifie auprès de l'API en utilisant l'email et le mot de passe.
     *
     * @param email    Email de l'administrateur.
     * @param password Mot de passe de l'administrateur.
     * @return AuthResponse contenant le token et les informations utilisateur, ou null en cas d'échec.
     * @throws IOException          Si la communication échoue.
     * @throws ApiException Si l'API retourne une erreur.
     */
    public AuthResponse login(String email, String password) throws IOException, ApiException {
        HttpPost post = new HttpPost(baseUrl + "auth.php");
        Map<String, String> credentials = Map.of("email", email, "password", password);
        String jsonBody = objectMapper.writeValueAsString(credentials);
        post.setEntity(new StringEntity(jsonBody, ContentType.APPLICATION_JSON));

        return httpClient.execute(post, response -> {
            int statusCode = response.getCode();
            String responseBody = EntityUtils.toString(response.getEntity());

            if (statusCode >= 200 && statusCode < 300) {
                AuthResponse authResponse = objectMapper.readValue(responseBody, AuthResponse.class);
                if (authResponse != null && !authResponse.isError() && authResponse.getToken() != null) {
                    this.authToken = authResponse.getToken();
                    return authResponse;
                } else {
                    
                    throw new ApiException("API authentication failed: " + (authResponse != null ? authResponse.getMessage() : "Invalid response format"));
                }
            } else {
                
                ErrorResponse errorResponse = null;
                try {
                     errorResponse = objectMapper.readValue(responseBody, ErrorResponse.class);
                } catch (Exception ignored) { }
                String errorMessage = (errorResponse != null && errorResponse.getMessage() != null) ? errorResponse.getMessage() : "HTTP Error: " + statusCode;
                throw new ApiException("API authentication failed: " + errorMessage);
            }
        });
    }

    /**
     * Récupère la liste des entreprises depuis l'API.
     *
     * @return Une liste d'objets Company.
     * @throws IOException          Si la communication échoue.
     * @throws ApiException Si non authentifié ou si l'API retourne une erreur.
     */
    public List<Company> getCompanies() throws IOException, ApiException {
        if (this.authToken == null) {
            throw new ApiException("Non authentifié. Appeler login() d'abord.");
        }

        HttpGet get = new HttpGet(baseUrl + "companies.php");
        get.setHeader("Authorization", "Bearer " + this.authToken);

        return httpClient.execute(get, response -> {
            int statusCode = response.getCode();
            String responseBody = EntityUtils.toString(response.getEntity());

            if (statusCode >= 200 && statusCode < 300) {
                 
                 ApiResponse<List<Company>> apiResponse = objectMapper.readValue(responseBody,
                         objectMapper.getTypeFactory().constructParametricType(ApiResponse.class, objectMapper.getTypeFactory().constructCollectionType(List.class, Company.class)));

                 if (apiResponse != null && !apiResponse.isError()) {
                    return apiResponse.getData();
                 } else {
                     throw new ApiException("Échec de la récupération des entreprises: " + (apiResponse != null ? apiResponse.getMessage() : "Format de réponse invalide"));
                 }
            } else {
                 ErrorResponse errorResponse = null;
                try {
                     errorResponse = objectMapper.readValue(responseBody, ErrorResponse.class);
                } catch (Exception ignored) { }
                 String errorMessage = (errorResponse != null && errorResponse.getMessage() != null) ? errorResponse.getMessage() : "HTTP Error: " + statusCode;
                throw new ApiException("Échec de la récupération des entreprises: " + errorMessage);
            }
        });
    }

    /**
     * Récupère la liste des contrats depuis l'API.
     *
     * @return Une liste d'objets Contract.
     * @throws IOException  Si la communication échoue.
     * @throws ApiException Si non authentifié ou si l'API retourne une erreur.
     */
    public List<Contract> getContracts() throws IOException, ApiException {
        if (this.authToken == null) {
            throw new ApiException("Non authentifié. Appeler login() d'abord.");
        }

        HttpGet get = new HttpGet(baseUrl + "contracts.php");
        get.setHeader("Authorization", "Bearer " + this.authToken);

        return httpClient.execute(get, response -> {
            int statusCode = response.getCode();
            String responseBody = EntityUtils.toString(response.getEntity());

            if (statusCode >= 200 && statusCode < 300) {
                 ApiResponse<List<Contract>> apiResponse = objectMapper.readValue(responseBody,
                         objectMapper.getTypeFactory().constructParametricType(ApiResponse.class, objectMapper.getTypeFactory().constructCollectionType(List.class, Contract.class)));

                 if (apiResponse != null && !apiResponse.isError()) {
                    return apiResponse.getData();
                 } else {
                     throw new ApiException("Échec de la récupération des contrats: " + (apiResponse != null ? apiResponse.getMessage() : "Format de réponse invalide"));
                 }
            } else {
                 ErrorResponse errorResponse = parseErrorResponse(responseBody);
                 String errorMessage = (errorResponse != null && errorResponse.getMessage() != null) ? errorResponse.getMessage() : "HTTP Error: " + statusCode;
                throw new ApiException("Échec de la récupération des contrats: " + errorMessage);
            }
        });
    }

     /**
     * Récupère la liste des devis depuis l'API.
     *
     * @return Une liste d'objets Quote.
     * @throws IOException  Si la communication échoue.
     * @throws ApiException Si non authentifié ou si l'API retourne une erreur.
     */
    public List<Quote> getQuotes() throws IOException, ApiException {
        if (this.authToken == null) {
            throw new ApiException("Non authentifié. Appeler login() d'abord.");
        }

        HttpGet get = new HttpGet(baseUrl + "quotes.php");
        get.setHeader("Authorization", "Bearer " + this.authToken);

        return httpClient.execute(get, response -> {
            int statusCode = response.getCode();
            String responseBody = EntityUtils.toString(response.getEntity());

            if (statusCode >= 200 && statusCode < 300) {
                 ApiResponse<List<Quote>> apiResponse = objectMapper.readValue(responseBody,
                         objectMapper.getTypeFactory().constructParametricType(ApiResponse.class, objectMapper.getTypeFactory().constructCollectionType(List.class, Quote.class)));

                 if (apiResponse != null && !apiResponse.isError()) {
                    return apiResponse.getData();
                 } else {
                     throw new ApiException("Échec de la récupération des devis: " + (apiResponse != null ? apiResponse.getMessage() : "Format de réponse invalide"));
                 }
            } else {
                 ErrorResponse errorResponse = parseErrorResponse(responseBody);
                 String errorMessage = (errorResponse != null && errorResponse.getMessage() != null) ? errorResponse.getMessage() : "HTTP Error: " + statusCode;
                throw new ApiException("Échec de la récupération des devis: " + errorMessage);
            }
        });
    }

     /**
     * Récupère la liste des factures depuis l'API.
     *
     * @return Une liste d'objets Invoice.
     * @throws IOException  Si la communication échoue.
     * @throws ApiException Si non authentifié ou si l'API retourne une erreur.
     */
    public List<Invoice> getInvoices() throws IOException, ApiException {
        if (this.authToken == null) {
            throw new ApiException("Non authentifié. Appeler login() d'abord.");
        }

        HttpGet get = new HttpGet(baseUrl + "invoices.php");
        get.setHeader("Authorization", "Bearer " + this.authToken);

        return httpClient.execute(get, response -> {
            int statusCode = response.getCode();
            String responseBody = EntityUtils.toString(response.getEntity());

            if (statusCode >= 200 && statusCode < 300) {
                 ApiResponse<List<Invoice>> apiResponse = objectMapper.readValue(responseBody,
                         objectMapper.getTypeFactory().constructParametricType(ApiResponse.class, objectMapper.getTypeFactory().constructCollectionType(List.class, Invoice.class)));

                 if (apiResponse != null && !apiResponse.isError()) {
                    return apiResponse.getData();
                 } else {
                     throw new ApiException("Échec de la récupération des factures: " + (apiResponse != null ? apiResponse.getMessage() : "Format de réponse invalide"));
                 }
            } else {
                 ErrorResponse errorResponse = parseErrorResponse(responseBody);
                 String errorMessage = (errorResponse != null && errorResponse.getMessage() != null) ? errorResponse.getMessage() : "HTTP Error: " + statusCode;
                throw new ApiException("Échec de la récupération des factures: " + errorMessage);
            }
        });
    }

    /**
     * Récupère la liste des évènements depuis l'API.
     *
     * @return Une liste d'objets Event.
     * @throws IOException  Si la communication échoue.
     * @throws ApiException Si non authentifié ou si l'API retourne une erreur.
     */
    public List<Event> getEvents() throws IOException, ApiException {
        if (this.authToken == null) {
            throw new ApiException("Non authentifié. Appeler login() d'abord.");
        }

        HttpGet get = new HttpGet(baseUrl + "events.php");
        get.setHeader("Authorization", "Bearer " + this.authToken);

        return httpClient.execute(get, response -> {
            int statusCode = response.getCode();
            String responseBody = EntityUtils.toString(response.getEntity());

            if (statusCode >= 200 && statusCode < 300) {
                 ApiResponse<List<Event>> apiResponse = objectMapper.readValue(responseBody,
                         objectMapper.getTypeFactory().constructParametricType(ApiResponse.class, objectMapper.getTypeFactory().constructCollectionType(List.class, Event.class)));

                 if (apiResponse != null && !apiResponse.isError()) {
                    return apiResponse.getData();
                 } else {
                     throw new ApiException("Échec de la récupération des évènements: " + (apiResponse != null ? apiResponse.getMessage() : "Format de réponse invalide"));
                 }
            } else {
                 ErrorResponse errorResponse = parseErrorResponse(responseBody);
                 String errorMessage = (errorResponse != null && errorResponse.getMessage() != null) ? errorResponse.getMessage() : "HTTP Error: " + statusCode;
                throw new ApiException("Échec de la récupération des évènements: " + errorMessage);
            }
        });
    }

    /**
     * Récupère la liste des prestations (services) depuis l'API.
     *
     * @return Une liste d'objets Prestation.
     * @throws IOException  Si la communication échoue.
     * @throws ApiException Si non authentifié ou si l'API retourne une erreur.
     */
    public List<Prestation> getPrestations() throws IOException, ApiException {
        if (this.authToken == null) {
            throw new ApiException("Non authentifié. Appeler login() d'abord.");
        }

        HttpGet get = new HttpGet(baseUrl + "services.php");
        get.setHeader("Authorization", "Bearer " + this.authToken);

        return httpClient.execute(get, response -> {
            int statusCode = response.getCode();
            String responseBody = EntityUtils.toString(response.getEntity());

            if (statusCode >= 200 && statusCode < 300) {
                 
                 ApiResponse<List<Prestation>> apiResponse = objectMapper.readValue(responseBody,
                         objectMapper.getTypeFactory().constructParametricType(ApiResponse.class, objectMapper.getTypeFactory().constructCollectionType(List.class, Prestation.class)));

                 if (apiResponse != null && !apiResponse.isError()) {
                    return apiResponse.getData();
                 } else {
                     throw new ApiException("Échec de la récupération des prestations: " + (apiResponse != null ? apiResponse.getMessage() : "Format de réponse invalide"));
                 }
            } else {
                 ErrorResponse errorResponse = parseErrorResponse(responseBody);
                 String errorMessage = (errorResponse != null && errorResponse.getMessage() != null) ? errorResponse.getMessage() : "HTTP Error: " + statusCode;
                throw new ApiException("Échec de la récupération des prestations: " + errorMessage);
            }
        });
    }

    /**
     * Méthode utilitaire pour analyser les réponses d'erreur.
     * @param responseBody Le corps de la réponse JSON.
     * @return Objet ErrorResponse ou null si l'analyse échoue.
     */
    private ErrorResponse parseErrorResponse(String responseBody) {
         try {
             return objectMapper.readValue(responseBody, ErrorResponse.class);
         } catch (Exception ignored) {
             return null;
         }
    }

}
