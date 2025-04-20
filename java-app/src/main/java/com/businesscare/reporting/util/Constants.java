package com.businesscare.reporting.util;

public class Constants {

    /**
     * Base URL for the Business Care Admin API.
     * Reads from the "API_BASE_URL" environment variable.
     * Defaults to "http://localhost/api/admin" if the environment variable is not set (for local testing).
     */
    public static final String API_BASE_URL = System.getenv().getOrDefault("API_BASE_URL", "http://localhost/api/admin");

    /**
     * Output path for the generated PDF report relative to the application's working directory.
     */
    public static final String PDF_OUTPUT_PATH = "output/report.pdf";

    

}
