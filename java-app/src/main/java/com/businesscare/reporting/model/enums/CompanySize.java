package com.businesscare.reporting.model.enums;

import com.fasterxml.jackson.annotation.JsonCreator;
import com.fasterxml.jackson.annotation.JsonValue;

import java.util.Arrays;

/**
 * Représente la taille d'une entreprise, correspondant à l'enum de la base de données.
 */
public enum CompanySize {
    S1_10("1-10"),
    S11_50("11-50"),
    S51_200("51-200"),
    S201_500("201-500"),
    S500_PLUS("500+");

    private static final String UNKNOWN_VALUE_MSG = "Valeur CompanySize inconnue : ";

    private final String value;

    CompanySize(String value) {
        this.value = value;
    }

    @JsonValue 
    public String getValue() {
        return value;
    }

    @JsonCreator 
    public static CompanySize fromValue(String value) {
        return Arrays.stream(CompanySize.values())
                .filter(size -> size.value.equalsIgnoreCase(value))
                .findFirst()
                .orElseThrow(() -> new IllegalArgumentException(UNKNOWN_VALUE_MSG + value));
    }
}
