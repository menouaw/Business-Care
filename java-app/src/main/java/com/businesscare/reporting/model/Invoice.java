package com.businesscare.reporting.model;

import com.businesscare.reporting.model.enums.InvoiceStatus;
import com.fasterxml.jackson.annotation.JsonFormat;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.List;

/**
 * Représente une entité Facture.
 */
@JsonIgnoreProperties(ignoreUnknown = true)
public class Invoice {

    private int id;

    @JsonProperty("entreprise_id")
    private int entrepriseId;

    @JsonProperty("devis_id")
    private Integer devisId;

    @JsonProperty("numero_facture")
    private String numeroFacture;

    @JsonProperty("date_emission")
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "yyyy-MM-dd")
    private LocalDate dateEmission;

    @JsonProperty("date_echeance")
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "yyyy-MM-dd")
    private LocalDate dateEcheance;

    @JsonProperty("montant_total")
    private BigDecimal montantTotal;

    @JsonProperty("montant_ht")
    private BigDecimal montantHt;

    private BigDecimal tva; 

    private InvoiceStatus statut;

    @JsonProperty("mode_paiement")
    private String modePaiement; 

    @JsonProperty("date_paiement")
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "yyyy-MM-dd HH:mm:ss")
    private LocalDateTime datePaiement;

    
    @JsonProperty("line_items")
    private List<QuotePrestation> lineItems;

    public Invoice() {
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

    public Integer getDevisId() {
        return devisId;
    }

    public void setDevisId(Integer devisId) {
        this.devisId = devisId;
    }

    public String getNumeroFacture() {
        return numeroFacture;
    }

    public void setNumeroFacture(String numeroFacture) {
        this.numeroFacture = numeroFacture;
    }

    public LocalDate getDateEmission() {
        return dateEmission;
    }

    public void setDateEmission(LocalDate dateEmission) {
        this.dateEmission = dateEmission;
    }

    public LocalDate getDateEcheance() {
        return dateEcheance;
    }

    public void setDateEcheance(LocalDate dateEcheance) {
        this.dateEcheance = dateEcheance;
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

    public InvoiceStatus getStatut() {
        return statut;
    }

    public void setStatut(InvoiceStatus statut) {
        this.statut = statut;
    }

    public String getModePaiement() {
        return modePaiement;
    }

    public void setModePaiement(String modePaiement) {
        this.modePaiement = modePaiement;
    }

    public LocalDateTime getDatePaiement() {
        return datePaiement;
    }

    public void setDatePaiement(LocalDateTime datePaiement) {
        this.datePaiement = datePaiement;
    }

    public List<QuotePrestation> getLineItems() {
        return lineItems;
    }

    public void setLineItems(List<QuotePrestation> lineItems) {
        this.lineItems = lineItems;
    }

    @Override
    public String toString() {
        return "Invoice{" +
               "id=" + id +
               ", numeroFacture='" + numeroFacture + '\'' +
               ", statut=" + statut +
               ", montantTotal=" + montantTotal +
               '}';
    }
}
