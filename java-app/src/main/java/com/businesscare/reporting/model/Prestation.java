package com.businesscare.reporting.model;

import com.businesscare.reporting.model.enums.PrestationDifficultyLevel;
import com.businesscare.reporting.model.enums.PrestationType;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.math.BigDecimal;
import java.util.List; 

/**
 * Représente une entité Prestation, correspondant à la table 'prestations'.
 */
@JsonIgnoreProperties(ignoreUnknown = true)
public class Prestation {

    private int id;
    private String nom;
    private String description;
    private BigDecimal prix;
    private Integer duree; 
    private PrestationType type;
    private String categorie;

    @JsonProperty("niveau_difficulte")
    private PrestationDifficultyLevel niveauDifficulte;

    @JsonProperty("capacite_max")
    private Integer capaciteMax;

    @JsonProperty("materiel_necessaire")
    private String materielNecessaire;

    private String prerequis;

    
    @JsonProperty("associated_events")
    private List<Integer> associatedEvents;

    
    public Prestation() {
    }

    

    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getNom() {
        return nom;
    }

    public void setNom(String nom) {
        this.nom = nom;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public BigDecimal getPrix() {
        return prix;
    }

    public void setPrix(BigDecimal prix) {
        this.prix = prix;
    }

    public Integer getDuree() {
        return duree;
    }

    public void setDuree(Integer duree) {
        this.duree = duree;
    }

    public PrestationType getType() {
        return type;
    }

    public void setType(PrestationType type) {
        this.type = type;
    }

    public String getCategorie() {
        return categorie;
    }

    public void setCategorie(String categorie) {
        this.categorie = categorie;
    }

    public PrestationDifficultyLevel getNiveauDifficulte() {
        return niveauDifficulte;
    }

    public void setNiveauDifficulte(PrestationDifficultyLevel niveauDifficulte) {
        this.niveauDifficulte = niveauDifficulte;
    }

    public Integer getCapaciteMax() {
        return capaciteMax;
    }

    public void setCapaciteMax(Integer capaciteMax) {
        this.capaciteMax = capaciteMax;
    }

    public String getMaterielNecessaire() {
        return materielNecessaire;
    }

    public void setMaterielNecessaire(String materielNecessaire) {
        this.materielNecessaire = materielNecessaire;
    }

    public String getPrerequis() {
        return prerequis;
    }

    public void setPrerequis(String prerequis) {
        this.prerequis = prerequis;
    }

    public List<Integer> getAssociatedEvents() {
        return associatedEvents;
    }

    public void setAssociatedEvents(List<Integer> associatedEvents) {
        this.associatedEvents = associatedEvents;
    }

    @Override
    public String toString() {
        return "Prestation{" +
               "id=" + id +
               ", nom='" + nom + '\'' +
               ", type=" + type +
               ", prix=" + prix +
               '}';
    }
}
