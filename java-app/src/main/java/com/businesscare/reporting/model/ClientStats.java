package com.businesscare.reporting.model;

import com.businesscare.reporting.model.enums.ContractStatus;

import java.math.BigDecimal;
import java.util.List;
import java.util.Map;
import java.util.Objects;

/**
 * Contient les statistiques agrégées sur les clients pour le rapport.
 */
public class ClientStats {

    private Map<String, Long> clientCountBySector;
    private Map<String, Long> clientCountBySize;
    private Map<ContractStatus, Long> contractCountByStatus;
    private Map<Integer, BigDecimal> totalRevenueByClientId;
    private List<CompanyRevenue> top5ClientsByRevenue;
    private BigDecimal totalRevenueOverall;
    private long totalClients;
    private long totalContracts;
    private long totalPaidInvoices;

    public ClientStats() {
        
    }

    public Map<String, Long> getClientCountBySector() {
        return clientCountBySector;
    }

    public void setClientCountBySector(Map<String, Long> clientCountBySector) {
        this.clientCountBySector = clientCountBySector;
    }

    public Map<String, Long> getClientCountBySize() {
        return clientCountBySize;
    }

    public void setClientCountBySize(Map<String, Long> clientCountBySize) {
        this.clientCountBySize = clientCountBySize;
    }

    public Map<ContractStatus, Long> getContractCountByStatus() {
        return contractCountByStatus;
    }

    public void setContractCountByStatus(Map<ContractStatus, Long> contractCountByStatus) {
        this.contractCountByStatus = contractCountByStatus;
    }

    public Map<Integer, BigDecimal> getTotalRevenueByClientId() {
        return totalRevenueByClientId;
    }

    public void setTotalRevenueByClientId(Map<Integer, BigDecimal> totalRevenueByClientId) {
        this.totalRevenueByClientId = totalRevenueByClientId;
    }

    public List<CompanyRevenue> getTop5ClientsByRevenue() {
        return top5ClientsByRevenue;
    }

    public void setTop5ClientsByRevenue(List<CompanyRevenue> top5ClientsByRevenue) {
        this.top5ClientsByRevenue = top5ClientsByRevenue;
    }

    public BigDecimal getTotalRevenueOverall() {
        return totalRevenueOverall;
    }

    public void setTotalRevenueOverall(BigDecimal totalRevenueOverall) {
        this.totalRevenueOverall = totalRevenueOverall;
    }

    public long getTotalClients() {
        return totalClients;
    }

    public void setTotalClients(long totalClients) {
        this.totalClients = totalClients;
    }

    public long getTotalContracts() {
        return totalContracts;
    }

    public void setTotalContracts(long totalContracts) {
        this.totalContracts = totalContracts;
    }

    public long getTotalPaidInvoices() {
        return totalPaidInvoices;
    }

    public void setTotalPaidInvoices(long totalPaidInvoices) {
        this.totalPaidInvoices = totalPaidInvoices;
    }

    /**
     * Classe interne pour lier une entreprise à son revenu pour le classement.
     * Implémente Comparable pour trier par revenu (décroissant).
     */
    public static class CompanyRevenue implements Comparable<CompanyRevenue> {
        private static final String NULL_COMPANY_NAME = "Inconnu";
        private final Company company;
        private final BigDecimal revenue;

        public CompanyRevenue(Company company, BigDecimal revenue) {
            this.company = company; 
            this.revenue = Objects.requireNonNullElse(revenue, BigDecimal.ZERO);
        }

        public Company getCompany() {
            return company;
        }

        public BigDecimal getRevenue() {
            return revenue;
        }

        public String getCompanyName() {
            return company != null ? company.getNom() : NULL_COMPANY_NAME;
        }

        @Override
        public int compareTo(CompanyRevenue other) {
            
            return other.revenue.compareTo(this.revenue);
        }

        @Override
        public String toString() {
            return "CompanyRevenue{" +
                   "nomEntreprise='" + getCompanyName() + '\'' +
                   ", revenu=" + revenue +
                   '}';
        }
    }
}
