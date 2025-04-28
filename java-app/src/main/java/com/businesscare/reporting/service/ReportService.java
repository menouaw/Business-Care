package com.businesscare.reporting.service;

import com.businesscare.reporting.model.*;
import com.businesscare.reporting.model.enums.*;
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
    private static final int TOP_N_LIMIT = 5;
    private static final String NA_STRING = "N/A";

    /**
     * Traite les données des clients, contrats et factures pour générer des statistiques financières.
     *
     * @param companies La liste des entreprises.
     * @param contracts La liste des contrats.
     * @param invoices  La liste des factures.
     * @return Un objet ClientStats contenant les statistiques agrégées.
     */
    public ClientStats processClientFinancialData(List<Company> companies, List<Contract> contracts, List<Invoice> invoices) {
        logger.info("Traitement des données financières clients : {} entreprises, {} contrats, {} factures",
                    companies.size(), contracts.size(), invoices.size());

        ClientStats stats = new ClientStats();

        Map<Integer, Company> companyMap = companies.stream()
                .collect(Collectors.toMap(Company::getId, Function.identity()));

        stats.setTotalClients(companies.size());
        stats.setTotalContracts(contracts.size());

        Map<Integer, BigDecimal> revenueByClientId = invoices.stream()
                .filter(inv -> inv.getStatut() == InvoiceStatus.payee && inv.getMontantTotal() != null)
                .collect(Collectors.groupingBy(
                        Invoice::getEntrepriseId,
                        Collectors.mapping(Invoice::getMontantTotal, Collectors.reducing(BigDecimal.ZERO, BigDecimal::add))
                ));
        stats.setTotalRevenueByClientId(revenueByClientId);

        BigDecimal totalRevenue = revenueByClientId.values().stream()
                                        .reduce(BigDecimal.ZERO, BigDecimal::add);
        stats.setTotalRevenueOverall(totalRevenue);

        long paidInvoicesCount = invoices.stream().filter(inv -> inv.getStatut() == InvoiceStatus.payee).count();
        stats.setTotalPaidInvoices(paidInvoicesCount);

        Map<String, Long> countBySector = companies.stream()
                .filter(c -> c.getSecteurActivite() != null)
                .collect(Collectors.groupingBy(c -> c.getSecteurActivite().name(), Collectors.counting()));
        stats.setClientCountBySector(countBySector);

        Map<String, Long> countBySize = companies.stream()
                .filter(c -> c.getTailleEntreprise() != null)
                .collect(Collectors.groupingBy(c -> c.getTailleEntreprise().name(), Collectors.counting()));
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
                .limit(TOP_N_LIMIT)
                .collect(Collectors.toList());
        stats.setTop5ClientsByRevenue(companyRevenues);

        logger.info("Traitement des données financières clients terminé.");
        logger.debug("Statistiques ClientStats générées: Total revenus={}, Revenu client principal={}, Nb clients/secteur={}, Nb contrats/statut={}",
                     stats.getTotalRevenueOverall(),
                     companyRevenues.isEmpty() ? NA_STRING : companyRevenues.get(0).getRevenue(),
                     stats.getClientCountBySector().size(),
                     stats.getContractCountByStatus().size());

        return stats;
    }

    /**
     * Traite la liste des évènements pour générer des statistiques agrégées.
     *
     * @param events La liste des évènements.
     * @return Un objet EventStats contenant les statistiques agrégées.
     */
    public EventStats processEventData(List<Event> events) {
        logger.info("Traitement des données évènements : {} évènements", events.size());

        EventStats stats = new EventStats();
        stats.setTotalEvents(events.size());

        if (events.isEmpty()) {
            logger.warn("Aucun évènement à traiter.");
            stats.setEventCountByType(Collections.emptyMap());
            stats.setEventFrequency(Collections.emptyMap());
            stats.setTop5EventsByPopularity(Collections.emptyList());
            return stats;
        }

        Map<EventType, Long> countByType = events.stream()
                .filter(e -> e.getType() != null)
                .collect(Collectors.groupingBy(Event::getType, Collectors.counting()));
        stats.setEventCountByType(countByType);

        
        Map<Event, Long> inscriptionsCountByEvent = events.stream()
                .collect(Collectors.toMap(
                        Function.identity(),
                        event -> (long) (event.getInscriptions() != null ? event.getInscriptions().size() : 0)
                ));

        
         Map<String, Long> frequencyByTitle = inscriptionsCountByEvent.entrySet().stream()
             .filter(entry -> entry.getKey().getTitre() != null && !entry.getKey().getTitre().isBlank())
             .collect(Collectors.groupingBy(entry -> entry.getKey().getTitre(),
                                              Collectors.summingLong(Map.Entry::getValue)));
        stats.setEventFrequency(frequencyByTitle);

        
        List<EventStats.EventPopularity> eventPopularities = inscriptionsCountByEvent.entrySet().stream()
                .map(entry -> new EventStats.EventPopularity(entry.getKey(), entry.getValue()))
                .sorted()
                .limit(TOP_N_LIMIT)
                .collect(Collectors.toList());
        stats.setTop5EventsByPopularity(eventPopularities);

        logger.info("Traitement des données évènements terminé.");
        logger.debug("EventStats générées: Total Évènements={}, Évènements/Type={}, Popularité Top Évènement={}",
                     stats.getTotalEvents(),
                     stats.getEventCountByType().size(),
                     eventPopularities.isEmpty() ? NA_STRING : eventPopularities.get(0).getPopularityMetric());

        return stats;
    }

    /**
     * Traite la liste des prestations pour générer des statistiques agrégées.
     *
     * @param prestations La liste des prestations.
     * @return Un objet PrestationStats contenant les statistiques agrégées.
     */
    public PrestationStats processPrestationData(List<Prestation> prestations) {
        logger.info("Traitement des données prestations : {} prestations", prestations.size());

        PrestationStats stats = new PrestationStats();
        stats.setTotalPrestations(prestations.size());

        if (prestations.isEmpty()) {
            logger.warn("Aucune prestation à traiter.");
            stats.setPrestationCountByType(Collections.emptyMap());
            stats.setPrestationCountByCategory(Collections.emptyMap());
            stats.setPrestationCountByName(Collections.emptyMap());
            stats.setTop5PrestationsByFrequency(Collections.emptyList());
            return stats;
        }

        Map<PrestationType, Long> countByType = prestations.stream()
                .filter(p -> p.getType() != null)
                .collect(Collectors.groupingBy(Prestation::getType, Collectors.counting()));
        stats.setPrestationCountByType(countByType);

        Map<String, Long> countByCategory = prestations.stream()
                .filter(p -> p.getCategorie() != null && !p.getCategorie().isBlank())
                .collect(Collectors.groupingBy(Prestation::getCategorie, Collectors.counting()));
        stats.setPrestationCountByCategory(countByCategory);

        Map<String, Long> countByName = prestations.stream()
                .filter(p -> p.getNom() != null && !p.getNom().isBlank())
                .collect(Collectors.groupingBy(Prestation::getNom, Collectors.counting()));
        stats.setPrestationCountByName(countByName);

        List<PrestationStats.PrestationFrequency> top5ByFrequency = countByName.entrySet().stream()
                .map(entry -> new PrestationStats.PrestationFrequency(entry.getKey(), entry.getValue()))
                .sorted()
                .limit(TOP_N_LIMIT)
                .collect(Collectors.toList());
        stats.setTop5PrestationsByFrequency(top5ByFrequency);

        logger.info("Traitement des données prestations terminé.");
        logger.debug("PrestationStats générées: Total={}, Nb/Type={}, Nom Top Fréquence={}",
                     stats.getTotalPrestations(),
                     stats.getPrestationCountByType().size(),
                     top5ByFrequency.isEmpty() ? NA_STRING : top5ByFrequency.get(0).getPrestationName());

        return stats;
    }
}
