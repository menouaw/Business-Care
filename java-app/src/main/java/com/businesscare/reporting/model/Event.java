package com.businesscare.reporting.model;

import com.businesscare.reporting.model.enums.EventType;
import com.fasterxml.jackson.annotation.JsonFormat;
import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.time.LocalDateTime;
import java.util.List;

/**
 * Représente une entité Événement, correspondant à la structure de l'API et de la base de données.
 */
@JsonIgnoreProperties(ignoreUnknown = true)
public class Event {

    private int id;
    private String titre;
    private String description;

    @JsonProperty("date_debut")
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "yyyy-MM-dd HH:mm:ss")
    private LocalDateTime dateDebut;

    @JsonProperty("date_fin")
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "yyyy-MM-dd HH:mm:ss")
    private LocalDateTime dateFin;

    private String lieu;
    private EventType type;

    @JsonProperty("capacite_max")
    private Integer capaciteMax;

    
    @JsonProperty("associated_services")
    private List<Integer> associatedServices; 

    private List<EventInscription> inscriptions;

    
    public Event() {
    }

    
    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getTitre() {
        return titre;
    }

    public void setTitre(String titre) {
        this.titre = titre;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public LocalDateTime getDateDebut() {
        return dateDebut;
    }

    public void setDateDebut(LocalDateTime dateDebut) {
        this.dateDebut = dateDebut;
    }

    public LocalDateTime getDateFin() {
        return dateFin;
    }

    public void setDateFin(LocalDateTime dateFin) {
        this.dateFin = dateFin;
    }

    public String getLieu() {
        return lieu;
    }

    public void setLieu(String lieu) {
        this.lieu = lieu;
    }

    public EventType getType() {
        return type;
    }

    public void setType(EventType type) {
        this.type = type;
    }

    public Integer getCapaciteMax() {
        return capaciteMax;
    }

    public void setCapaciteMax(Integer capaciteMax) {
        this.capaciteMax = capaciteMax;
    }

    public List<Integer> getAssociatedServices() {
        return associatedServices;
    }

    public void setAssociatedServices(List<Integer> associatedServices) {
        this.associatedServices = associatedServices;
    }

    public List<EventInscription> getInscriptions() {
        return inscriptions;
    }

    public void setInscriptions(List<EventInscription> inscriptions) {
        this.inscriptions = inscriptions;
    }

    @Override
    public String toString() {
        return "Event{" +
               "id=" + id +
               ", titre='" + titre + '\'' +
               ", type=" + type +
               ", dateDebut=" + dateDebut +
               '}';
    }
}
