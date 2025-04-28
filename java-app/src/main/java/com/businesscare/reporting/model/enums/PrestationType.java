package com.businesscare.reporting.model.enums;

import com.fasterxml.jackson.annotation.JsonCreator;
import com.fasterxml.jackson.annotation.JsonValue;

import java.util.Arrays;

/**
 * Représente le type d'une prestation.
 * Correspond aux valeurs de l'enum `prestations`.`type`
 */
public enum PrestationType {
    CONFERENCE("conference"),
    WEBINAR("webinar"),
    ATELIER("atelier"),
    CONSULTATION("consultation"),
    EVENEMENT("evenement"),
    AUTRE("autre"); 

    private static final String UNKNOWN_VALUE_MSG = "Type de prestation inconnu : ";

    private final String value;

    PrestationType(String value) {
        this.value = value;
    }

    @JsonValue
    public String getValue() {
        return value;
    }

    @JsonCreator
    public static PrestationType fromValue(String value) {
        if (value == null) {
            
            return AUTRE; 
        }
        return Arrays.stream(PrestationType.values())
                .filter(type -> type.value.equalsIgnoreCase(value))
                .findFirst()
                
                .orElseThrow(() -> new IllegalArgumentException(UNKNOWN_VALUE_MSG + value));
    }

    @Override
    public String toString() {
        return this.value;
    }
} 