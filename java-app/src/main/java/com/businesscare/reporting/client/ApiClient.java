package com.businesscare.reporting.client;

import com.businesscare.reporting.exception.ApiException;
import com.businesscare.reporting.model.*; 
import com.fasterxml.jackson.databind.ObjectMapper;
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
        
        this.httpClient = HttpClients.createDefault();
    }

    
    public ApiClient(String baseUrl, CloseableHttpClient httpClient, ObjectMapper objectMapper) {
        this.baseUrl = baseUrl.endsWith("/") ? baseUrl : baseUrl + "/";
        this.httpClient = httpClient;
        this.objectMapper = objectMapper;
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
        HttpPost post = new HttpPost(baseUrl + "auth");
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
            throw new ApiException("Not authenticated. Call login() first.");
        }

        HttpGet get = new HttpGet(baseUrl + "companies");
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
                     throw new ApiException("Failed to fetch companies: " + (apiResponse != null ? apiResponse.getMessage() : "Invalid response format"));
                 }
            } else {
                 ErrorResponse errorResponse = null;
                try {
                     errorResponse = objectMapper.readValue(responseBody, ErrorResponse.class);
                } catch (Exception ignored) { }
                 String errorMessage = (errorResponse != null && errorResponse.getMessage() != null) ? errorResponse.getMessage() : "HTTP Error: " + statusCode;
                throw new ApiException("Failed to fetch companies: " + errorMessage);
            }
        });
    }

    
    

    public static class AuthResponse extends ErrorResponse {
        private String token;
        private User user;
        
        public String getToken() { return token; }
        public void setToken(String token) { this.token = token; }
        public User getUser() { return user; }
        public void setUser(User user) { this.user = user; }
    }

     public static class ApiResponse<T> extends ErrorResponse {
        private T data;
        
        public T getData() { return data; }
        public void setData(T data) { this.data = data; }
    }

    public static class ErrorResponse {
        private boolean error;
        private String message;
        
        public boolean isError() { return error; }
        public void setError(boolean error) { this.error = error; }
        public String getMessage() { return message; }
        public void setMessage(String message) { this.message = message; }
    }

    public static class User {
        public int id;
        public String nom;
        public String prenom;
        public String email;
        public int role_id;
        
    }

    public static class Company {
         public int id;
         public String nom;
         public String siret;
         
         
    }
}
