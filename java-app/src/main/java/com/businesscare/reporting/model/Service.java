package com.businesscare.reporting.model;

import com.businesscare.reporting.model.enums.ServiceType;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.math.BigDecimal;

/**
 * Représente une entité Service (Pack) telle que définie dans la base de données.
 * Utilisé souvent comme objet imbriqué dans Contract.
 */
@JsonIgnoreProperties(ignoreUnknown = true)
public class Service {

    private int id;
    private ServiceType type;
    private String description;
    private Boolean actif;
    private Integer ordre;

    @JsonProperty("max_effectif_inferieur_egal")
    private Integer maxEffectifInferieurEgal;

    @JsonProperty("activites_incluses")
    private Integer activitesIncluses;

    @JsonProperty("rdv_medicaux_inclus")
    private Integer rdvMedicauxInclus;

    @JsonProperty("chatbot_questions_limite")
    private Integer chatbotQuestionsLimite;

    @JsonProperty("conseils_hebdo_personnalises")
    private Boolean conseilsHebdoPersonnalises;

    @JsonProperty("tarif_annuel_par_salarie")
    private BigDecimal tarifAnnuelParSalarie;

    public Service() {
    }

    
    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public ServiceType getType() {
        return type;
    }

    public void setType(ServiceType type) {
        this.type = type;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public Boolean getActif() {
        return actif;
    }

    public void setActif(Boolean actif) {
        this.actif = actif;
    }

    public Integer getOrdre() {
        return ordre;
    }

    public void setOrdre(Integer ordre) {
        this.ordre = ordre;
    }

    public Integer getMaxEffectifInferieurEgal() {
        return maxEffectifInferieurEgal;
    }

    public void setMaxEffectifInferieurEgal(Integer maxEffectifInferieurEgal) {
        this.maxEffectifInferieurEgal = maxEffectifInferieurEgal;
    }

    public Integer getActivitesIncluses() {
        return activitesIncluses;
    }

    public void setActivitesIncluses(Integer activitesIncluses) {
        this.activitesIncluses = activitesIncluses;
    }

    public Integer getRdvMedicauxInclus() {
        return rdvMedicauxInclus;
    }

    public void setRdvMedicauxInclus(Integer rdvMedicauxInclus) {
        this.rdvMedicauxInclus = rdvMedicauxInclus;
    }

    public Integer getChatbotQuestionsLimite() {
        return chatbotQuestionsLimite;
    }

    public void setChatbotQuestionsLimite(Integer chatbotQuestionsLimite) {
        this.chatbotQuestionsLimite = chatbotQuestionsLimite;
    }

    public Boolean getConseilsHebdoPersonnalises() {
        return conseilsHebdoPersonnalises;
    }

    public void setConseilsHebdoPersonnalises(Boolean conseilsHebdoPersonnalises) {
        this.conseilsHebdoPersonnalises = conseilsHebdoPersonnalises;
    }

    public BigDecimal getTarifAnnuelParSalarie() {
        return tarifAnnuelParSalarie;
    }

    public void setTarifAnnuelParSalarie(BigDecimal tarifAnnuelParSalarie) {
        this.tarifAnnuelParSalarie = tarifAnnuelParSalarie;
    }

    @Override
    public String toString() {
        return "Service{" +
               "id=" + id +
               ", type=" + type +
               '}';
    }
}
