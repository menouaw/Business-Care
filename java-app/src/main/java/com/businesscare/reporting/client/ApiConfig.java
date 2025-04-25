package com.businesscare.reporting.client;

/**
 * Fichier pour la configuration de l'API
 */
public class ApiConfig {

    private final String baseUrl;
    private final String apiUser;
    private final String apiPassword;

    /**
     * Constructeur pour l'instance ApiConfig.
     * @param baseUrl L'URL de base de l'API Business Care Admin.
     * @param apiUser L'adresse email de l'utilisateur pour l'authentification API.
     * @param apiPassword Le mot de passe pour l'authentification API.
     */
    public ApiConfig(String baseUrl, String apiUser, String apiPassword) {
        this.baseUrl = baseUrl;
        this.apiUser = apiUser;
        this.apiPassword = apiPassword;
    }

    public String getBaseUrl() {
        return baseUrl;
    }

    public String getApiUser() {
        return apiUser;
    }

    public String getApiPassword() {
        return apiPassword;
    }
}
