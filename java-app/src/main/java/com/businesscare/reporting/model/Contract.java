package com.businesscare.reporting.model;

import com.businesscare.reporting.model.enums.ContractStatus;
import com.fasterxml.jackson.annotation.JsonFormat;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.time.LocalDate;

/**
 * Représente une entité de contrat, correspondant à la structure de l'API et de la base de données.
 */
@JsonIgnoreProperties(ignoreUnknown = true)
public class Contract {

    private int id;

    @JsonProperty("entreprise_id")
    private int entrepriseId;

    @JsonProperty("service_id")
    private int serviceId;

    @JsonProperty("date_debut")
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "yyyy-MM-dd")
    private LocalDate dateDebut;

    @JsonProperty("date_fin")
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "yyyy-MM-dd")
    private LocalDate dateFin;

    @JsonProperty("nombre_salaries")
    private Integer nombreSalaries;

    private ContractStatus statut;

    @JsonProperty("conditions_particulieres")
    private String conditionsParticulieres;

    
    @JsonProperty("nom_entreprise")
    private String nomEntreprise;

    @JsonProperty("nom_service")
    private String nomService;

    
    @JsonProperty("service_details")
    private Service serviceDetails; 

    
    public Contract() {
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

    public int getServiceId() {
        return serviceId;
    }

    public void setServiceId(int serviceId) {
        this.serviceId = serviceId;
    }

    public LocalDate getDateDebut() {
        return dateDebut;
    }

    public void setDateDebut(LocalDate dateDebut) {
        this.dateDebut = dateDebut;
    }

    public LocalDate getDateFin() {
        return dateFin;
    }

    public void setDateFin(LocalDate dateFin) {
        this.dateFin = dateFin;
    }

    public Integer getNombreSalaries() {
        return nombreSalaries;
    }

    public void setNombreSalaries(Integer nombreSalaries) {
        this.nombreSalaries = nombreSalaries;
    }

    public ContractStatus getStatut() {
        return statut;
    }

    public void setStatut(ContractStatus statut) {
        this.statut = statut;
    }

    public String getConditionsParticulieres() {
        return conditionsParticulieres;
    }

    public void setConditionsParticulieres(String conditionsParticulieres) {
        this.conditionsParticulieres = conditionsParticulieres;
    }

    public String getNomEntreprise() {
        return nomEntreprise;
    }

    public void setNomEntreprise(String nomEntreprise) {
        this.nomEntreprise = nomEntreprise;
    }

    public String getNomService() {
        return nomService;
    }

    public void setNomService(String nomService) {
        this.nomService = nomService;
    }

    public Service getServiceDetails() {
        return serviceDetails;
    }

    public void setServiceDetails(Service serviceDetails) {
        this.serviceDetails = serviceDetails;
    }

    @Override
    public String toString() {
        return "Contract{" +
               "id=" + id +
               ", entrepriseId=" + entrepriseId +
               ", serviceId=" + serviceId +
               ", dateDebut=" + dateDebut +
               ", dateFin=" + dateFin +
               ", statut=" + statut +
               ", nomEntreprise='" + nomEntreprise + '\'' +
               '}';
    }
}
