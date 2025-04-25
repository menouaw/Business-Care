package com.businesscare.reporting.service;

import com.businesscare.reporting.model.*;
import com.businesscare.reporting.model.enums.ContractStatus;
import com.businesscare.reporting.model.enums.InvoiceStatus;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.math.BigDecimal;
import java.util.*;
import java.util.function.Function;
import java.util.stream.Collectors;

/**
 * Service pour traiter les données brutes et générer des statistiques agrégées.
 */
public class ReportService {

    private static final Logger logger = LoggerFactory.getLogger(ReportService.class);

    /**
     * Traite les données des clients, contrats et factures pour générer des statistiques financières.
     *
     * @param companies La liste des entreprises.
     * @param contracts La liste des contrats.
     * @param invoices  La liste des factures.
     * @return Un objet ClientStats contenant les statistiques agrégées.
     */
    public ClientStats processClientFinancialData(List<Company> companies, List<Contract> contracts, List<Invoice> invoices) {
        logger.info("Traitement des données financières des clients: {} entreprises, {} contrats, {} factures",
                    companies.size(), contracts.size(), invoices.size());

        ClientStats stats = new ClientStats();

        
        Map<Integer, Company> companyMap = companies.stream()
                .collect(Collectors.toMap(Company::getId, Function.identity()));

        stats.setTotalClients(companies.size());
        stats.setTotalContracts(contracts.size());

        
        Map<Integer, BigDecimal> revenueByClientId = invoices.stream()
                .filter(inv -> inv.getStatut() == InvoiceStatus.PAYEE && inv.getMontantTotal() != null)
                .collect(Collectors.groupingBy(
                        Invoice::getEntrepriseId,
                        Collectors.reducing(BigDecimal.ZERO, Invoice::getMontantTotal, BigDecimal::add)
                ));
        stats.setTotalRevenueByClientId(revenueByClientId);

        
        BigDecimal totalRevenue = revenueByClientId.values().stream()
                                        .reduce(BigDecimal.ZERO, BigDecimal::add);
        stats.setTotalRevenueOverall(totalRevenue);

        
        long paidInvoicesCount = invoices.stream().filter(inv -> inv.getStatut() == InvoiceStatus.PAYEE).count();
        stats.setTotalPaidInvoices(paidInvoicesCount);

        
        Map<String, Long> countBySector = companies.stream()
                .filter(c -> c.getSecteurActivite() != null && !c.getSecteurActivite().isBlank())
                .collect(Collectors.groupingBy(Company::getSecteurActivite, Collectors.counting()));
        stats.setClientCountBySector(countBySector);

        
        Map<String, Long> countBySize = companies.stream()
                .filter(c -> c.getTailleEntreprise() != null && !c.getTailleEntreprise().isBlank())
                .collect(Collectors.groupingBy(Company::getTailleEntreprise, Collectors.counting()));
        stats.setClientCountBySize(countBySize);

        
        Map<ContractStatus, Long> countByContractStatus = contracts.stream()
                .filter(c -> c.getStatut() != null)
                .collect(Collectors.groupingBy(Contract::getStatut, Collectors.counting()));
        stats.setContractCountByStatus(countByContractStatus);

        
        List<ClientStats.CompanyRevenue> companyRevenues = revenueByClientId.entrySet().stream()
                .map(entry -> {
                    Company company = companyMap.get(entry.getKey());
                    
                    return company != null ? new ClientStats.CompanyRevenue(company, entry.getValue()) : null;
                })
                .filter(Objects::nonNull) 
                .sorted() 
                .limit(5)
                .collect(Collectors.toList());
        stats.setTop5ClientsByRevenue(companyRevenues);

        logger.info("Traitement des données financières des clients terminé.");
        logger.debug("Statistiques ClientStats générées: Total des revenus={}, Revenu du client principal={}, Nombre de clients par secteur={}, Nombre de contrats par statut={}",
                     stats.getTotalRevenueOverall(),
                     companyRevenues.isEmpty() ? "N/A" : companyRevenues.get(0).getRevenue(),
                     stats.getClientCountBySector().size(),
                     stats.getContractCountByStatus().size());


        return stats;
    }
}
