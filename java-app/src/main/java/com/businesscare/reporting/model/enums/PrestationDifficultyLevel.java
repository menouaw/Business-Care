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
    
    private static final String UNKNOWN_VALUE_MSG = "Niveau de difficulté inconnu : ";

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
        if (value == null) {
            
            
            return DEBUTANT; 
        }
        return Arrays.stream(PrestationDifficultyLevel.values())
                .filter(level -> level.value.equalsIgnoreCase(value))
                .findFirst()
                .orElseThrow(() -> new IllegalArgumentException(UNKNOWN_VALUE_MSG + value)); 
    }

    @Override
    public String toString() {
        return this.value;
    }
} 