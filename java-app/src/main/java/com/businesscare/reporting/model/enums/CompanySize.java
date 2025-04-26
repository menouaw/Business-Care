package com.businesscare.reporting.model.enums;

import com.fasterxml.jackson.annotation.JsonCreator;
import com.fasterxml.jackson.annotation.JsonValue;

import java.util.Arrays;

/**
 * Représente la taille d'une entreprise, correspondant à l'enum de la base de données.
 */
public enum CompanySize {
    SIZE_1_10("1-10"),
    SIZE_11_50("11-50"),
    SIZE_51_200("51-200"),
    SIZE_201_500("201-500"),
    SIZE_500_PLUS("500+");

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
                .orElseThrow(() -> new IllegalArgumentException("Unknown CompanySize value: " + value));
    }
}
