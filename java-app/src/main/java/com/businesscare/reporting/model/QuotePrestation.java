package com.businesscare.reporting.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.math.BigDecimal;

/**
 * Représente une ligne de prestation dans un devis.
 */
@JsonIgnoreProperties(ignoreUnknown = true)
public class QuotePrestation {

    @JsonProperty("prestation_id")
    private int prestationId;

    private int quantite;

    @JsonProperty("prix_unitaire_devis")
    private BigDecimal prixUnitaireDevis;

    @JsonProperty("description_specifique")
    private String descriptionSpecifique;

    public QuotePrestation() {
    }

    
    public int getPrestationId() {
        return prestationId;
    }

    public void setPrestationId(int prestationId) {
        this.prestationId = prestationId;
    }

    public int getQuantite() {
        return quantite;
    }

    public void setQuantite(int quantite) {
        this.quantite = quantite;
    }

    public BigDecimal getPrixUnitaireDevis() {
        return prixUnitaireDevis;
    }

    public void setPrixUnitaireDevis(BigDecimal prixUnitaireDevis) {
        this.prixUnitaireDevis = prixUnitaireDevis;
    }

    public String getDescriptionSpecifique() {
        return descriptionSpecifique;
    }

    public void setDescriptionSpecifique(String descriptionSpecifique) {
        this.descriptionSpecifique = descriptionSpecifique;
    }

    @Override
    public String toString() {
        return "QuotePrestation{" +
               "prestationId=" + prestationId +
               ", quantite=" + quantite +
               ", prixUnitaireDevis=" + prixUnitaireDevis +
               '}';
    }
} 