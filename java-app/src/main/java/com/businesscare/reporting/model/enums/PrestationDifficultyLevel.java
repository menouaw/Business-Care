package com.businesscare.reporting.model.enums;

import com.fasterxml.jackson.annotation.JsonCreator;
import com.fasterxml.jackson.annotation.JsonValue;

import java.util.Arrays;

/**
 * Représente le niveau de difficulté d'une prestation.
 * Correspond aux valeurs de l'enum `prestations`.`niveau_difficulte`
 */
public enum PrestationDifficultyLevel {
    DEBUTANT("debutant"),       
    INTERMEDIAIRE("intermediaire"), 
    AVANCE("avance");           
    
    
    

    private final String value;

    PrestationDifficultyLevel(String value) {
        this.value = value;
    }

    @JsonValue
    public String getValue() {
        return value;
    }

    @JsonCreator
    public static PrestationDifficultyLevel fromValue(String value) {
        
        return Arrays.stream(PrestationDifficultyLevel.values())
                .filter(level -> level.value.equalsIgnoreCase(value))
                .findFirst()
                
                .orElse(DEBUTANT);
                
    }
} 