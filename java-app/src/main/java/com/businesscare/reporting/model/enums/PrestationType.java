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
        
        return Arrays.stream(PrestationType.values())
                .filter(type -> type.value.equalsIgnoreCase(value))
                .findFirst()
                
                .orElse(AUTRE);
    }
} 