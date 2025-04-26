package com.businesscare.reporting.model.enums;

import com.fasterxml.jackson.annotation.JsonCreator;
import com.fasterxml.jackson.annotation.JsonValue;

import java.util.Arrays;

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

    private final String value;

    IndustrySector(String value) {
        this.value = value;
    }

    @JsonValue
    public String getValue() {
        return value;
    }

    @JsonCreator
    public static IndustrySector fromValue(String value) {
        
        return Arrays.stream(IndustrySector.values())
                .filter(sector -> sector.value.equalsIgnoreCase(value))
                .findFirst()
                
                .orElse(AUTRE); 
                
                
    }
} 