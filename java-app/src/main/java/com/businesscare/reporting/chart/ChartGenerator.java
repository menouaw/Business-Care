package com.businesscare.reporting.chart;

import com.businesscare.reporting.model.ClientStats;
import com.businesscare.reporting.model.EventStats;
import com.businesscare.reporting.model.PrestationStats;
import com.businesscare.reporting.model.enums.ContractStatus;
import com.businesscare.reporting.model.enums.EventType;
import org.jfree.chart.ChartFactory;
import org.jfree.chart.JFreeChart;
import org.jfree.chart.plot.PiePlot;
import org.jfree.chart.plot.PlotOrientation;
import org.jfree.chart.plot.CategoryPlot; 
import org.jfree.chart.renderer.category.BarRenderer; 
import org.jfree.data.category.DefaultCategoryDataset;
import org.jfree.data.general.DefaultPieDataset;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.awt.Color;
import java.awt.Font;
import java.math.BigDecimal;
import java.util.List;
import java.util.Map;

public class ChartGenerator {

    private static final Logger logger = LoggerFactory.getLogger(ChartGenerator.class);

    

    /**
     * Crée un graphique camembert pour la distribution des statuts de contrat.
     */
    public static JFreeChart createContractStatusChart(ClientStats stats) {
        logger.debug("Création du diagramme des statuts de contrat");
        DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
        if (stats.getContractCountByStatus() != null) {
            stats.getContractCountByStatus().forEach((status, count) -> {
                if (status != null && count != null && count > 0) {
                    dataset.setValue(status.name(), count);
                }
            });
        } else {
            logger.warn("Données de comptage des statuts de contrat manquantes.");
            dataset.setValue("Données manquantes", 1);
        }

        JFreeChart pieChart = ChartFactory.createPieChart(
                "Répartition des contrats par statut",
                dataset,
                true, 
                true, 
                false 
        );

        
        PiePlot<String> plot = (PiePlot<String>) pieChart.getPlot();
        plot.setBackgroundPaint(Color.WHITE);
        plot.setLabelFont(new Font("SansSerif", Font.PLAIN, 10));
        plot.setNoDataMessage("Aucune donnée disponible");
        plot.setCircular(true);
        

        return pieChart;
    }

    /**
     * Crée un diagramme à barres pour la distribution des clients par secteur.
     */
    public static JFreeChart createClientDistributionBySectorChart(ClientStats stats) {
        logger.debug("Création du diagramme de distribution des clients par secteur");
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
         if (stats.getClientCountBySector() != null) {
            stats.getClientCountBySector().forEach((sector, count) -> {
                 if (sector != null && !sector.isBlank() && count != null && count > 0) {
                     dataset.addValue(count, "Clients", sector);
                 } else {
                     logger.trace("Données de secteur invalides: secteur={}, compte={}", sector, count);
                 }
            });
        } else {
             logger.warn("Données de comptage des secteurs de clients manquantes.");
        }

        JFreeChart barChart = ChartFactory.createBarChart(
                "Répartition des clients par secteur",
                "Secteur d'activité", 
                "Nombre de clients",  
                dataset,
                PlotOrientation.VERTICAL,
                false, 
                true,  
                false  
        );

         
         CategoryPlot plot = barChart.getCategoryPlot();
         plot.setBackgroundPaint(Color.WHITE);
         plot.setRangeGridlinePaint(Color.LIGHT_GRAY);
         ((BarRenderer) plot.getRenderer()).setBarPainter(new org.jfree.chart.renderer.category.StandardBarPainter()); 
         plot.getRenderer().setSeriesPaint(0, Color.BLUE); 

        return barChart;
    }

    /**
     * Crée un diagramme à barres pour la distribution des clients par taille.
     */
    public static JFreeChart createClientDistributionBySizeChart(ClientStats stats) {
        logger.debug("Création du diagramme de distribution des clients par taille");
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
         if (stats.getClientCountBySize() != null) {
             stats.getClientCountBySize().forEach((size, count) -> {
                 if (size != null && !size.isBlank() && count != null && count > 0) {
                    dataset.addValue(count, "Clients", size);
                 } else {
                     logger.trace("Données de taille invalides: taille={}, compte={}", size, count);
                 }
             });
        } else {
            logger.warn("Données de comptage des tailles de clients manquantes.");
        }

        JFreeChart barChart = ChartFactory.createBarChart(
                "Répartition des clients par Taille",
                "Taille de l'entreprise", 
                "Nombre de clients",    
                dataset,
                PlotOrientation.VERTICAL,
                false, 
                true,  
                false  
        );

         
         CategoryPlot plot = barChart.getCategoryPlot();
         plot.setBackgroundPaint(Color.WHITE);
         plot.setRangeGridlinePaint(Color.LIGHT_GRAY);
         ((BarRenderer) plot.getRenderer()).setBarPainter(new org.jfree.chart.renderer.category.StandardBarPainter());
         plot.getRenderer().setSeriesPaint(0, Color.GREEN); 

        return barChart;
    }

    /**
     * Crée un graphique camembert pour la distribution du revenu entre le Top 5 et les autres.
     * (Alternativement, pourrait être un bar chart du revenu du Top 5)
     */
    public static JFreeChart createClientRevenueDistributionChart(ClientStats stats) {
        logger.debug("Création du diagramme de distribution du revenu entre le Top 5 et les autres");
        DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
        BigDecimal top5Revenue = BigDecimal.ZERO;
        BigDecimal otherRevenue = BigDecimal.ZERO;
        BigDecimal totalRevenue = stats.getTotalRevenueOverall() != null ? stats.getTotalRevenueOverall() : BigDecimal.ZERO;

        if (stats.getTop5ClientsByRevenue() != null) {
            top5Revenue = stats.getTop5ClientsByRevenue().stream()
                            .map(ClientStats.CompanyRevenue::getRevenue)
                            .reduce(BigDecimal.ZERO, BigDecimal::add);
        }

        
        if (totalRevenue.compareTo(BigDecimal.ZERO) > 0) {
            otherRevenue = totalRevenue.subtract(top5Revenue);
        }

        if (top5Revenue.compareTo(BigDecimal.ZERO) > 0) {
             dataset.setValue("Revenu des 5 premiers clients", top5Revenue);
        }
         if (otherRevenue.compareTo(BigDecimal.ZERO) > 0) {
            dataset.setValue("Revenu des autres clients", otherRevenue);
        }
        if (dataset.getItemCount() == 0) {
            logger.warn("Aucune donnée de revenu disponible pour le diagramme de distribution.");
             dataset.setValue("Données manquantes", 1);
        }

        JFreeChart pieChart = ChartFactory.createPieChart(
                "Répartition du revenu total",
                dataset,
                true, 
                true, 
                false 
        );

        
        PiePlot<String> plot = (PiePlot<String>) pieChart.getPlot();
        plot.setBackgroundPaint(Color.WHITE);
        plot.setLabelFont(new Font("SansSerif", Font.PLAIN, 10));
        plot.setNoDataMessage("Aucune donnée de revenu disponible");

        return pieChart;
    }

    

    /**
     * Crée un graphique camembert pour la distribution des types d'événements.
     */
    public static JFreeChart createEventTypeDistributionChart(EventStats stats) {
        logger.debug("Création du diagramme de distribution des types d'événements");
        DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
        if (stats.getEventCountByType() != null) {
            stats.getEventCountByType().forEach((type, count) -> {
                if (type != null && count != null && count > 0) {
                    dataset.setValue(type.name(), count);
                }
            });
        } else {
            logger.warn("Données de comptage des types d'événements manquantes.");
            dataset.setValue("Données manquantes", 1);
        }

        JFreeChart pieChart = ChartFactory.createPieChart(
                "Répartition par Type d'Événement",
                dataset,
                true, true, false);

        PiePlot<String> plot = (PiePlot<String>) pieChart.getPlot();
        plot.setBackgroundPaint(Color.WHITE);
        plot.setLabelFont(new Font("SansSerif", Font.PLAIN, 10));
        plot.setNoDataMessage("Aucun type d'événement disponible");

        return pieChart;
    }

    /**
     * Crée un diagramme à barres montrant la popularité (inscriptions) du Top 5 des événements.
     */
    public static JFreeChart createTop5EventsByPopularityChart(EventStats stats) {
        logger.debug("Création du diagramme des 5 événements les plus populaires");
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
        if (stats.getTop5EventsByPopularity() != null) {
            stats.getTop5EventsByPopularity().forEach(ep -> {
                if (ep.getEvent() != null && ep.getEvent().getTitre() != null && !ep.getEvent().getTitre().isBlank()) {
                    dataset.addValue(ep.getPopularityMetric(), "Inscriptions", ep.getEvent().getTitre());
                }
            });
        } else {
            logger.warn("Données du Top 5 des événements manquantes.");
        }

        JFreeChart barChart = ChartFactory.createBarChart(
                "Top 5 Événements par Popularité (Inscriptions)",
                "Événement",
                "Nombre d'Inscriptions",
                dataset,
                PlotOrientation.VERTICAL,
                false, true, false);

        CategoryPlot plot = barChart.getCategoryPlot();
        plot.setBackgroundPaint(Color.WHITE);
        plot.setRangeGridlinePaint(Color.LIGHT_GRAY);
         ((BarRenderer) plot.getRenderer()).setBarPainter(new org.jfree.chart.renderer.category.StandardBarPainter());
         plot.getRenderer().setSeriesPaint(0, Color.MAGENTA);

        return barChart;
    }

     /**
     * Crée un diagramme (ex: barres) basé sur la fréquence calculée des événements par titre.
     * Note: Ceci peut nécessiter une adaptation si la fréquence est calculée différemment.
     */
     public static JFreeChart createEventFrequencyChart(EventStats stats) {
        logger.debug("Création du diagramme de fréquence des événements par titre");
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
        if (stats.getEventFrequency() != null) {
             
             stats.getEventFrequency().entrySet().stream()
                  .sorted(Map.Entry.<String, Long>comparingByValue().reversed())
                  .limit(10) 
                  .forEach(entry -> {
                      if (entry.getKey() != null && !entry.getKey().isBlank() && entry.getValue() > 0) {
                        dataset.addValue(entry.getValue(), "Fréquence", entry.getKey());
                      }
                  });
        } else {
            logger.warn("Données de fréquence des événements manquantes.");
        }

        JFreeChart barChart = ChartFactory.createBarChart(
                "Fréquence des Événements (Top 10 par Inscriptions Totales)",
                "Titre de l'Événement",
                "Fréquence (Inscriptions)",
                dataset,
                PlotOrientation.HORIZONTAL, 
                false, true, false);

        CategoryPlot plot = barChart.getCategoryPlot();
        plot.setBackgroundPaint(Color.WHITE);
        plot.setRangeGridlinePaint(Color.LIGHT_GRAY);
         ((BarRenderer) plot.getRenderer()).setBarPainter(new org.jfree.chart.renderer.category.StandardBarPainter());
         plot.getRenderer().setSeriesPaint(0, Color.ORANGE);

        return barChart;
    }

     /**
     * Placeholder pour le quatrième graphique d'événement.
     * Pourrait montrer les inscriptions au fil du temps, la capacité vs inscriptions, etc.
     */
     public static JFreeChart createPlaceholderEventChart4(EventStats stats) {
         logger.debug("Création du graphique événement placeholder 4");
         DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
         dataset.setValue("Graphique 4 (Événements) - À Implémenter", 100);

        JFreeChart pieChart = ChartFactory.createPieChart(
                "Événements - Graphique 4",
                dataset,
                false, true, false);

         pieChart.getPlot().setBackgroundPaint(Color.LIGHT_GRAY);
         ((PiePlot)pieChart.getPlot()).setNoDataMessage("Implémentation future");

        return pieChart;
    }

    

    /**
     * Crée un graphique camembert pour la distribution des types de prestations.
     */
    public static JFreeChart createPrestationTypeDistributionChart(PrestationStats stats) {
        logger.debug("Création du diagramme de distribution des types de prestations");
        DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
        if (stats.getPrestationCountByType() != null) {
            stats.getPrestationCountByType().forEach((type, count) -> {
                if (type != null && count != null && count > 0) {
                    dataset.setValue(type.name(), count);
                }
            });
        } else {
            logger.warn("Données de comptage des types de prestations manquantes.");
            dataset.setValue("Données manquantes", 1);
        }

        JFreeChart pieChart = ChartFactory.createPieChart(
                "Répartition par Type de Prestation",
                dataset,
                true, true, false);

        PiePlot<String> plot = (PiePlot<String>) pieChart.getPlot();
        plot.setBackgroundPaint(Color.WHITE);
        plot.setLabelFont(new Font("SansSerif", Font.PLAIN, 10));
        plot.setNoDataMessage("Aucun type de prestation disponible");

        return pieChart;
    }

    /**
     * Crée un diagramme à barres pour la distribution des prestations par catégorie.
     */
    public static JFreeChart createPrestationCategoryDistributionChart(PrestationStats stats) {
        logger.debug("Création du diagramme de distribution des prestations par catégorie");
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
        if (stats.getPrestationCountByCategory() != null) {
            stats.getPrestationCountByCategory().entrySet().stream()
                 .sorted(Map.Entry.<String, Long>comparingByValue().reversed()) 
                 .limit(15) 
                 .forEach(entry -> {
                     if (entry.getKey() != null && !entry.getKey().isBlank() && entry.getValue() > 0) {
                         dataset.addValue(entry.getValue(), "Prestations", entry.getKey());
                     }
                 });
        } else {
            logger.warn("Données de comptage des catégories de prestations manquantes.");
        }

        JFreeChart barChart = ChartFactory.createBarChart(
                "Répartition des Prestations par Catégorie (Top 15)",
                "Catégorie",
                "Nombre de Prestations",
                dataset,
                PlotOrientation.HORIZONTAL, 
                false, true, false);

        CategoryPlot plot = barChart.getCategoryPlot();
        plot.setBackgroundPaint(Color.WHITE);
        plot.setRangeGridlinePaint(Color.LIGHT_GRAY);
         ((BarRenderer) plot.getRenderer()).setBarPainter(new org.jfree.chart.renderer.category.StandardBarPainter());
         plot.getRenderer().setSeriesPaint(0, Color.CYAN);

        return barChart;
    }

    /**
     * Crée un diagramme à barres montrant la fréquence du Top 5 des prestations (par nom).
     */
    public static JFreeChart createTop5PrestationsByFrequencyChart(PrestationStats stats) {
        logger.debug("Création du diagramme des 5 prestations les plus fréquentes");
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
        if (stats.getTop5PrestationsByFrequency() != null) {
            stats.getTop5PrestationsByFrequency().forEach(pf -> {
                 if (pf.getPrestationName() != null && !pf.getPrestationName().isBlank()) {
                    dataset.addValue(pf.getFrequency(), "Fréquence", pf.getPrestationName());
                 }
            });
        } else {
            logger.warn("Données du Top 5 des prestations par fréquence manquantes.");
        }

        JFreeChart barChart = ChartFactory.createBarChart(
                "Top 5 Prestations par Fréquence",
                "Nom de la Prestation",
                "Fréquence",
                dataset,
                PlotOrientation.VERTICAL,
                false, true, false);

        CategoryPlot plot = barChart.getCategoryPlot();
        plot.setBackgroundPaint(Color.WHITE);
        plot.setRangeGridlinePaint(Color.LIGHT_GRAY);
         ((BarRenderer) plot.getRenderer()).setBarPainter(new org.jfree.chart.renderer.category.StandardBarPainter());
         plot.getRenderer().setSeriesPaint(0, Color.RED);

        return barChart;
    }

    /**
     * Placeholder pour le quatrième graphique de prestation.
     * Pourrait montrer la distribution des prix, durée moyenne par type, etc.
     */
    public static JFreeChart createPlaceholderPrestationChart4(PrestationStats stats) {
         logger.debug("Création du graphique prestation placeholder 4");
         DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
         dataset.setValue("Graphique 4 (Prestations) - À Implémenter", 100);

        JFreeChart pieChart = ChartFactory.createPieChart(
                "Prestations - Graphique 4",
                dataset,
                false, true, false);

         pieChart.getPlot().setBackgroundPaint(Color.LIGHT_GRAY);
         ((PiePlot)pieChart.getPlot()).setNoDataMessage("Implémentation future");

        return pieChart;
    }

    /**
     * Crée un graphique de participation par mois (à adapter).
     *
     * @param stats Les statistiques des événements.
     * @return Un objet JFreeChart.
     */
    public JFreeChart createEventParticipationByMonthChart(EventStats stats) {
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
        // TODO: Implémenter la logique pour représenter les données correctement par mois si possible.
        Map<String, Long> frequency = stats.getEventFrequency(); // Correction
        if (frequency != null) {
            frequency.forEach((title, count) -> dataset.addValue(count, "Popularité", title)); 
        }

        return ChartFactory.createBarChart(
                "Participation aux événements par mois",
                "Événement",
                "Nombre de participants",
                dataset,
                PlotOrientation.VERTICAL,
                true, true, false);
    }

    /**
     * Crée un graphique de distribution du taux de satisfaction (à adapter).
     *
     * @param stats Les statistiques des événements.
     * @return Un objet JFreeChart.
     */
    public JFreeChart createEventSatisfactionRateChart(EventStats stats) {
        DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
        // TODO: implémenter la logique pour représenter les données de satisfaction si elles existent.
        Map<EventType, Long> countByType = stats.getEventCountByType(); 
        if (countByType != null) {
            countByType.forEach((type, count) -> dataset.setValue(type.name(), count)); 
        }

        return ChartFactory.createPieChart(
                "Taux de satisfaction des événements",
                dataset,
                true, true, false);
    }

}
