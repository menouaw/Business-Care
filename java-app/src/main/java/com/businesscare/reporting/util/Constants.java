package com.businesscare.reporting.util;

public final class Constants {

    private Constants() {
         
    }

    
    public static final String ENV_API_BASE_URL = "API_BASE_URL";
    public static final String ENV_API_USER = "API_USER";
    public static final String ENV_API_PASSWORD = "API_API_PASSWORD";

    
    public static final String DEFAULT_API_BASE_URL = "http://192.168.213.22/api/admin";
    public static final String DEFAULT_API_USER = "admin@businesscare.fr";
    public static final String DEFAULT_API_PASSWORD = "admin123"; 

    
    public static final String OUTPUT_DIRECTORY = "output";

    
    public static final String DATE_FORMAT_PATTERN = "dd-MM-yyyy";

    
    public static final String JSON_FILENAME_PREFIX = "report_";
    public static final String JSON_FILENAME_SUFFIX = ".json";
    public static final String JSON_OUTPUT_SUBDIRECTORY = "json";

    
    public static final String REPORT_FILENAME_PREFIX = "report_";
    public static final String REPORT_FILENAME_SUFFIX = ".pdf";

    
    public static final float PDF_MARGIN_TOP = 50f;
    public static final float PDF_MARGIN_RIGHT = 50f;
    public static final float PDF_MARGIN_BOTTOM = 50f;
    public static final float PDF_MARGIN_LEFT = 50f;

    
    public static final String LOG_APP_START = "Application de reporting démarrée.";
    public static final String LOG_APP_END = "Application de reporting terminée.";
    public static final String LOG_ERR_API = "Erreur API: {} ({})";
    public static final String LOG_ERR_UNEXPECTED = "Erreur inattendue dans l'application: {} ({})";

}
