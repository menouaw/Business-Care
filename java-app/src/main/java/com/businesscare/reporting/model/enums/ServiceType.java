package com.businesscare.reporting.model.enums;

import java.util.Arrays;

/**
 * Représente le type de service (pack), correspondant à l'enum de la base de données.
 */
public enum ServiceType {
    STARTER_PACK("Starter Pack"),
    BASIC_PACK("Basic Pack"),
    PREMIUM_PACK("Premium Pack"),
    UNKNOWN("Inconnu"); 

    private static final String UNKNOWN_VALUE_MSG = "Type de service inconnu : ";
    private final String displayName;

    ServiceType(String displayName) {
        this.displayName = displayName;
    }

    public String getDisplayName() {
        return displayName;
    }

    
    public static ServiceType fromDisplayName(String displayName) {
        if (displayName == null) {
            return UNKNOWN;
        }
        return Arrays.stream(ServiceType.values())
                .filter(type -> type.displayName.equalsIgnoreCase(displayName))
                .findFirst()
                .orElse(UNKNOWN); 
                
    }

    @Override
    public String toString() {
        return this.displayName;
    }
}
