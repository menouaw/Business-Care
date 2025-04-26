package com.businesscare.reporting.model;

import com.businesscare.reporting.model.enums.ContractStatus;

import java.math.BigDecimal;
import java.util.List;
import java.util.Map;

import java.util.ArrayList;
import java.util.EnumMap;
import java.util.HashMap;

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
     * Classe interne simple pour lier une entreprise à son revenu pour le classement.
     */
    public static class CompanyRevenue implements Comparable<CompanyRevenue> {
        private Company company;
        private BigDecimal revenue;

        public CompanyRevenue(Company company, BigDecimal revenue) {
            this.company = company;
            this.revenue = revenue != null ? revenue : BigDecimal.ZERO;
        }

        public Company getCompany() {
            return company;
        }

        public BigDecimal getRevenue() {
            return revenue;
        }

        @Override
        public int compareTo(CompanyRevenue other) {
            
            return other.revenue.compareTo(this.revenue);
        }

        @Override
        public String toString() {
            return "CompanyRevenue{" +
                   "companyName=" + (company != null ? company.getNom() : "null") +
                   ", revenue=" + revenue +
                   '}';
        }
    }
}
