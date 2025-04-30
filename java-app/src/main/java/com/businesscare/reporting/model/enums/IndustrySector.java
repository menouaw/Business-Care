package com.businesscare.reporting.model.enums;

import com.fasterxml.jackson.annotation.JsonCreator;
import com.fasterxml.jackson.annotation.JsonValue;

import java.util.Arrays;
import java.util.Objects;

/**
 * Représente le secteur d'activité d'une entreprise.
 * (Les valeurs réelles peuvent varier)
 */
public enum IndustrySector {
    TECHNOLOGIE("Technologie"),
    SANTE("Santé"),
    FINANCE("Finance"),
    COMMERCE("Commerce"),
    INDUSTRIE("Industrie"),
    SERVICES("Services"),
    EDUCATION("Éducation"),
    
    AUTRE("Autre"); 

    private final String displayName;

    IndustrySector(String displayName) {
        this.displayName = displayName;
    }

    @JsonValue
    public String getDisplayName() {
        return displayName;
    }

    @JsonCreator
    public static IndustrySector fromString(String text) {
        if (text == null) {
            return AUTRE; 
        }
        return Arrays.stream(IndustrySector.values())
                .filter(sector -> sector.displayName.equalsIgnoreCase(text))
                .findFirst()
                .orElse(AUTRE); 
    }

    @Override
    public String toString() {
        return this.displayName;
    }
} 