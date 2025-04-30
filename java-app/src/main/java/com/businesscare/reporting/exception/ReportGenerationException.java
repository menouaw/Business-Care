package com.businesscare.reporting.exception;

/**
 * Exception spécifique aux erreurs survenant lors de la génération du rapport.
 */
public class ReportGenerationException extends RuntimeException {
    public ReportGenerationException(String message) {
        super(message);
    }

    /**
     * Constructeur avec message et cause.
     * @param message Message d'erreur.
     * @param cause Exception originale.
     */
    public ReportGenerationException(String message, Throwable cause) {
        super(message, cause);
    }
}
