package com.businesscare.reporting.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

/**
 * Représente la réponse de l'API lors d'une tentative d'authentification réussie.
 */
@JsonIgnoreProperties(ignoreUnknown = true)
public class AuthResponse extends ErrorResponse {

    private String token;
    private User user;

    public AuthResponse() {
        super(); 
    }

    
    public String getToken() {
        return token;
    }

    public void setToken(String token) {
        this.token = token;
    }

    public User getUser() {
        return user;
    }

    public void setUser(User user) {
        this.user = user;
    }

    @Override
    public String toString() {
        return "AuthResponse{" +
               "error=" + isError() +
               ", message='" + getMessage() + '\'' +
               ", token='" + (token != null ? "******" : "null") + '\'' +
               ", user=" + user +
               '}';
    }
}
