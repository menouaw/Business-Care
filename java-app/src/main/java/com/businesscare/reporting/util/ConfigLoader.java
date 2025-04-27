package com.businesscare.reporting.util;
import com.businesscare.reporting.client.ApiConfig;
/**
 * Classe utilitaire pour charger la configuration de l'application.
 */
public class ConfigLoader {
    private ConfigLoader() {
         
    }
    /**
     * Charge la configuration de l'API à partir des variables d'environnement, avec les valeurs par défaut si elles ne sont pas définies.
     * @return Une instance initialisée de ApiConfig.
     */
    public static ApiConfig loadApiConfig() {
        String baseUrl = System.getenv(Constants.ENV_API_BASE_URL);
        if (baseUrl == null || baseUrl.trim().isEmpty()) {
            baseUrl = Constants.DEFAULT_API_BASE_URL;
             
        }
        String apiUser = System.getenv(Constants.ENV_API_USER);
        if (apiUser == null || apiUser.trim().isEmpty()) {
            apiUser = Constants.DEFAULT_API_USER;
             
        }
        String apiPassword = System.getenv(Constants.ENV_API_PASSWORD);
        if (apiPassword == null ||apiPassword.trim().isEmpty()) {
             
            apiPassword = Constants.DEFAULT_API_PASSWORD;
        }
        return new ApiConfig(baseUrl,apiUser, apiPassword);
    }
}