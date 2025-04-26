package com.businesscare.reporting.model;

import com.businesscare.reporting.model.enums.QuoteStatus;
import com.fasterxml.jackson.annotation.JsonFormat;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.util.List;

/**
 * Représente une entité Devis.
 */
@JsonIgnoreProperties(ignoreUnknown = true)
public class Quote {

    private int id;

    @JsonProperty("entreprise_id")
    private int entrepriseId;

    @JsonProperty("service_id")
    private Integer serviceId;

    @JsonProperty("nombre_salaries_estimes")
    private Integer nombreSalariesEstimes;

    @JsonProperty("est_personnalise")
    private Boolean estPersonnalise;

    @JsonProperty("notes_negociation")
    private String notesNegociation;

    @JsonProperty("date_creation")
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "yyyy-MM-dd")
    private LocalDate dateCreation;

    @JsonProperty("date_validite")
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "yyyy-MM-dd")
    private LocalDate dateValidite;

    @JsonProperty("montant_total")
    private BigDecimal montantTotal;

    @JsonProperty("montant_ht")
    private BigDecimal montantHt;

    @JsonProperty("tva")
    private BigDecimal tva;

    private QuoteStatus statut;

    @JsonProperty("conditions_paiement")
    private String conditionsPaiement;

    @JsonProperty("delai_paiement")
    private Integer delaiPaiement;

    @JsonProperty("prestations")
    private List<QuotePrestation> prestations;

    public Quote() {
    }

    
    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public int getEntrepriseId() {
        return entrepriseId;
    }

    public void setEntrepriseId(int entrepriseId) {
        this.entrepriseId = entrepriseId;
    }

    public Integer getServiceId() {
        return serviceId;
    }

    public void setServiceId(Integer serviceId) {
        this.serviceId = serviceId;
    }

    public Integer getNombreSalariesEstimes() {
        return nombreSalariesEstimes;
    }

    public void setNombreSalariesEstimes(Integer nombreSalariesEstimes) {
        this.nombreSalariesEstimes = nombreSalariesEstimes;
    }

    public Boolean getEstPersonnalise() {
        return estPersonnalise;
    }

    public void setEstPersonnalise(Boolean estPersonnalise) {
        this.estPersonnalise = estPersonnalise;
    }

    public String getNotesNegociation() {
        return notesNegociation;
    }

    public void setNotesNegociation(String notesNegociation) {
        this.notesNegociation = notesNegociation;
    }

    public LocalDate getDateCreation() {
        return dateCreation;
    }

    public void setDateCreation(LocalDate dateCreation) {
        this.dateCreation = dateCreation;
    }

    public LocalDate getDateValidite() {
        return dateValidite;
    }

    public void setDateValidite(LocalDate dateValidite) {
        this.dateValidite = dateValidite;
    }

    public BigDecimal getMontantTotal() {
        return montantTotal;
    }

    public void setMontantTotal(BigDecimal montantTotal) {
        this.montantTotal = montantTotal;
    }

    public BigDecimal getMontantHt() {
        return montantHt;
    }

    public void setMontantHt(BigDecimal montantHt) {
        this.montantHt = montantHt;
    }

    public BigDecimal getTva() {
        return tva;
    }

    public void setTva(BigDecimal tva) {
        this.tva = tva;
    }

    public QuoteStatus getStatut() {
        return statut;
    }

    public void setStatut(QuoteStatus statut) {
        this.statut = statut;
    }

    public String getConditionsPaiement() {
        return conditionsPaiement;
    }

    public void setConditionsPaiement(String conditionsPaiement) {
        this.conditionsPaiement = conditionsPaiement;
    }

    public Integer getDelaiPaiement() {
        return delaiPaiement;
    }

    public void setDelaiPaiement(Integer delaiPaiement) {
        this.delaiPaiement = delaiPaiement;
    }

    public List<QuotePrestation> getPrestations() {
        return prestations;
    }

    public void setPrestations(List<QuotePrestation> prestations) {
        this.prestations = prestations;
    }

    @Override
    public String toString() {
        return "Quote{" +
               "id=" + id +
               ", entrepriseId=" + entrepriseId +
               ", statut=" + statut +
               ", montantTotal=" + montantTotal +
               '}';
    }
}
