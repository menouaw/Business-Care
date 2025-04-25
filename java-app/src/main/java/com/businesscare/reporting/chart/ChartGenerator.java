package com.businesscare.reporting.chart;

import com.businesscare.reporting.model.ClientStats;
import com.businesscare.reporting.model.enums.ContractStatus;
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

    

}
