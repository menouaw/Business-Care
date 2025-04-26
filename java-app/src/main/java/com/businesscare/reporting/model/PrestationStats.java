package com.businesscare.reporting.model;

import com.businesscare.reporting.model.enums.*;

import java.util.List;
import java.util.Map;
import java.math.BigDecimal;

/**
 * Contient les statistiques agrégées sur les prestations pour le rapport.
 */
public class PrestationStats {

    private Map<String, Long> distributionParCategorie;
    private Map<PrestationType, Long> distributionParType;
    private Map<PrestationDifficultyLevel, Long> distributionParDifficulte;
    private Map<String, BigDecimal> revenuParPrestation;
    private List<Map.Entry<String, Long>> top5Prestations;

    private Map<PrestationType, Long> prestationCountByType; 
    private Map<String, Long> prestationCountByCategory; 
    private List<PrestationFrequency> top5PrestationsByFrequency; 
    private long totalPrestations; 
    private Map<String, Long> prestationCountByName; 

    

    public PrestationStats() {
    }

    

    public Map<String, Long> getDistributionParCategorie() {
        return distributionParCategorie;
    }

    public void setDistributionParCategorie(Map<String, Long> distributionParCategorie) {
        this.distributionParCategorie = distributionParCategorie;
    }

    public Map<PrestationType, Long> getDistributionParType() {
        return distributionParType;
    }

    public void setDistributionParType(Map<PrestationType, Long> distributionParType) {
        this.distributionParType = distributionParType;
    }

    public Map<PrestationDifficultyLevel, Long> getDistributionParDifficulte() {
        return distributionParDifficulte;
    }

    public void setDistributionParDifficulte(Map<PrestationDifficultyLevel, Long> distributionParDifficulte) {
        this.distributionParDifficulte = distributionParDifficulte;
    }

    public Map<String, BigDecimal> getRevenuParPrestation() {
        return revenuParPrestation;
    }

    public void setRevenuParPrestation(Map<String, BigDecimal> revenuParPrestation) {
        this.revenuParPrestation = revenuParPrestation;
    }

    public List<Map.Entry<String, Long>> getTop5Prestations() {
        return top5Prestations;
    }

    public void setTop5Prestations(List<Map.Entry<String, Long>> top5Prestations) {
        this.top5Prestations = top5Prestations;
    }

    public Map<PrestationType, Long> getPrestationCountByType() {
        return prestationCountByType;
    }

    public void setPrestationCountByType(Map<PrestationType, Long> prestationCountByType) {
        this.prestationCountByType = prestationCountByType;
    }

    public Map<String, Long> getPrestationCountByCategory() {
        return prestationCountByCategory;
    }

    public void setPrestationCountByCategory(Map<String, Long> prestationCountByCategory) {
        this.prestationCountByCategory = prestationCountByCategory;
    }

     public Map<String, Long> getPrestationCountByName() {
        return prestationCountByName;
    }

    public void setPrestationCountByName(Map<String, Long> prestationCountByName) {
        this.prestationCountByName = prestationCountByName;
    }

    public List<PrestationFrequency> getTop5PrestationsByFrequency() {
        return top5PrestationsByFrequency;
    }

    public void setTop5PrestationsByFrequency(List<PrestationFrequency> top5PrestationsByFrequency) {
        this.top5PrestationsByFrequency = top5PrestationsByFrequency;
    }

    public long getTotalPrestations() {
        return totalPrestations;
    }

    public void setTotalPrestations(long totalPrestations) {
        this.totalPrestations = totalPrestations;
    }

    /**
     * Classe interne simple pour lier une prestation (par nom) à sa fréquence.
     */
    public static class PrestationFrequency implements Comparable<PrestationFrequency> {
        private String prestationName;
        private long frequency;

        public PrestationFrequency(String prestationName, long frequency) {
            this.prestationName = prestationName;
            this.frequency = frequency;
        }

        public String getPrestationName() {
            return prestationName;
        }

        public long getFrequency() {
            return frequency;
        }

        @Override
        public int compareTo(PrestationFrequency other) {
            
            return Long.compare(other.frequency, this.frequency);
        }

        @Override
        public String toString() {
            return "PrestationFrequency{" +
                   "prestationName='" + prestationName + '\'' +
                   ", frequency=" + frequency +
                   '}';
        }
    }
}
