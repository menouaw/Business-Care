package com.businesscare.reporting.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

/**
 * Représente les détails d'une inscription à un évènement, tels que retournés par l'API.
 */
@JsonIgnoreProperties(ignoreUnknown = true)
public class EventInscription {

    @JsonProperty("personne_id")
    private int personneId;

    private String statut;

    @JsonProperty("nom_personne")
    private String nomPersonne;

    @JsonProperty("email_personne")
    private String emailPersonne;

    
    public EventInscription() {
    }

    public int getPersonneId() {
        return personneId;
    }

    public void setPersonneId(int personneId) {
        this.personneId = personneId;
    }

    public String getStatut() {
        return statut;
    }

    public void setStatut(String statut) {
        this.statut = statut;
    }

    public String getNomPersonne() {
        return nomPersonne;
    }

    public void setNomPersonne(String nomPersonne) {
        this.nomPersonne = nomPersonne;
    }

    public String getEmailPersonne() {
        return emailPersonne;
    }

    public void setEmailPersonne(String emailPersonne) {
        this.emailPersonne = emailPersonne;
    }

    @Override
    public String toString() {
        return "EventInscription{" +
               "personneId=" + personneId +
               ", statut='" + statut + '\'' +
               ", nomPersonne='" + nomPersonne + '\'' +
               '}';
    }
} 