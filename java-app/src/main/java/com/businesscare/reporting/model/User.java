package com.businesscare.reporting.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

/**
 * Représente les informations utilisateur retournées par l'API lors de l'authentification.
 */
@JsonIgnoreProperties(ignoreUnknown = true)
public class User {

    public int id;
    public String nom;
    public String prenom;
    public String email;
    public int role_id;

    @JsonProperty("role_id")
    private int roleId;

    public User() {
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

    public String getPrenom() {
        return prenom;
    }

    public void setPrenom(String prenom) {
        this.prenom = prenom;
    }

    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    public int getRoleId() {
        return roleId;
    }

    public void setRoleId(int roleId) {
        this.roleId = roleId;
    }

    @Override
    public String toString() {
        return "User{" +
               "id=" + id +
               ", nom='" + nom + '\'' +
               ", prenom='" + prenom + '\'' +
               ", email='" + email + '\'' +
               '}';
    }
}
