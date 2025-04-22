package com.businesscare.reporting.util;

public class Constants {

    
    public static final String API_BASE_URL = System.getenv().getOrDefault("API_BASE_URL", "http://localhost/api/admin");

    
    public static final String AUTH_ENDPOINT = "/auth.php"; 
    public static final String COMPANIES_ENDPOINT = "/companies.php"; 
    public static final String CONTRACTS_ENDPOINT = "/contracts.php";
    public static final String QUOTES_ENDPOINT = "/quotes.php";
    public static final String INVOICES_ENDPOINT = "/invoices.php";
    public static final String EVENTS_ENDPOINT = "/events.php";
    public static final String SERVICES_ENDPOINT = "/services.php";

    
    public static final String PDF_OUTPUT_PATH = "output/report.pdf";

}
