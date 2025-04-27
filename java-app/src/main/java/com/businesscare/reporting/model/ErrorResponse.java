package com.businesscare.reporting.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

/**
 * Classe de base pour les réponses API contenant des champs d'erreur et de message.
 */
@JsonIgnoreProperties(ignoreUnknown = true)
public class ErrorResponse {

    private boolean error;
    private String message;

    public ErrorResponse() {
    }

    public boolean isError() {
        return error;
    }

    public void setError(boolean error) {
        this.error = error;
    }

    public String getMessage() {
        return message;
    }

    public void setMessage(String message) {
        this.message = message;
    }
} 