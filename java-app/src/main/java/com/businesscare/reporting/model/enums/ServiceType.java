package com.businesscare.reporting.model.enums;

/**
 * Représente le type de service (pack), correspondant à l'enum de la base de données.
 */
public enum ServiceType {
    STARTER_PACK("Starter Pack"),
    BASIC_PACK("Basic Pack"),
    PREMIUM_PACK("Premium Pack");

    private final String displayName;

    ServiceType(String displayName) {
        this.displayName = displayName;
    }

    
    public String getDisplayName() {
        return displayName;
    }
}
