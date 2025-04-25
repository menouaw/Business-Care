package com.businesscare.reporting.model;

import com.businesscare.reporting.model.enums.CompanySize;
import com.fasterxml.jackson.annotation.JsonFormat;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.time.LocalDate;
import java.util.List;

/**
 * Représente une entité Entreprise.
 */
@JsonIgnoreProperties(ignoreUnknown = true)
public class Company {

    private int id;
    private String nom;
    private String siret;
    private String adresse;

    @JsonProperty("code_postal")
    private String codePostal;

    private String ville;
    private String telephone;
    private String email;

    @JsonProperty("site_web")
    private String siteWeb;

    @JsonProperty("logo_url")
    private String logoUrl;

    @JsonProperty("taille_entreprise")
    private CompanySize tailleEntreprise;

    @JsonProperty("secteur_activite")
    private String secteurActivite;

    @JsonProperty("date_creation")
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "yyyy-MM-dd")
    private LocalDate dateCreation;

    
    private List<Integer> contracts;
    private List<Integer> quotes;
    private List<Integer> invoices;

    public Company() {
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

    public String getSiret() {
        return siret;
    }

    public void setSiret(String siret) {
        this.siret = siret;
    }

    public String getAdresse() {
        return adresse;
    }

    public void setAdresse(String adresse) {
        this.adresse = adresse;
    }

    public String getCodePostal() {
        return codePostal;
    }

    public void setCodePostal(String codePostal) {
        this.codePostal = codePostal;
    }

    public String getVille() {
        return ville;
    }

    public void setVille(String ville) {
        this.ville = ville;
    }

    public String getTelephone() {
        return telephone;
    }

    public void setTelephone(String telephone) {
        this.telephone = telephone;
    }

    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    public String getSiteWeb() {
        return siteWeb;
    }

    public void setSiteWeb(String siteWeb) {
        this.siteWeb = siteWeb;
    }

    public String getLogoUrl() {
        return logoUrl;
    }

    public void setLogoUrl(String logoUrl) {
        this.logoUrl = logoUrl;
    }

    public CompanySize getTailleEntreprise() {
        return tailleEntreprise;
    }

    public void setTailleEntreprise(CompanySize tailleEntreprise) {
        this.tailleEntreprise = tailleEntreprise;
    }

    public String getSecteurActivite() {
        return secteurActivite;
    }

    public void setSecteurActivite(String secteurActivite) {
        this.secteurActivite = secteurActivite;
    }

    public LocalDate getDateCreation() {
        return dateCreation;
    }

    public void setDateCreation(LocalDate dateCreation) {
        this.dateCreation = dateCreation;
    }

    public List<Integer> getContracts() {
        return contracts;
    }

    public void setContracts(List<Integer> contracts) {
        this.contracts = contracts;
    }

    public List<Integer> getQuotes() {
        return quotes;
    }

    public void setQuotes(List<Integer> quotes) {
        this.quotes = quotes;
    }

    public List<Integer> getInvoices() {
        return invoices;
    }

    public void setInvoices(List<Integer> invoices) {
        this.invoices = invoices;
    }

    @Override
    public String toString() {
        return "Company{" +
               "id=" + id +
               ", nom='" + nom + '\'' +
               ", ville='" + ville + '\'' +
               '}';
    }
}
