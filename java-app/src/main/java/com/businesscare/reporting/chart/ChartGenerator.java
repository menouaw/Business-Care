package com.businesscare.reporting.chart;

import com.businesscare.reporting.model.ClientStats;
import com.businesscare.reporting.model.EventStats;
import com.businesscare.reporting.model.PrestationStats;
import com.businesscare.reporting.model.enums.*;
import org.jfree.chart.ChartFactory;
import org.jfree.chart.JFreeChart;
import org.jfree.chart.axis.CategoryAxis;
import org.jfree.chart.axis.ValueAxis;
import org.jfree.chart.plot.CategoryPlot;
import org.jfree.chart.plot.PiePlot;
import org.jfree.chart.plot.PlotOrientation;
import org.jfree.chart.renderer.category.BarRenderer;
import org.jfree.chart.renderer.category.StandardBarPainter;
import org.jfree.chart.title.TextTitle;
import org.jfree.data.category.DefaultCategoryDataset;
import org.jfree.data.general.DefaultPieDataset;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.awt.Color;
import java.awt.Font;
import java.math.BigDecimal;
import java.util.Collections;
import java.util.List;
import java.util.Map;
import java.util.Objects;

/**
 * Classe utilitaire pour générer des objets JFreeChart basés sur les statistiques.
 */
public class ChartGenerator {

    private static final Logger logger = LoggerFactory.getLogger(ChartGenerator.class);

    
    private static final String TITLE_CONTRACT_STATUS = "Répartition des contrats par statut";
    private static final String TITLE_CLIENT_SECTOR = "Répartition des clients par secteur";
    private static final String TITLE_CLIENT_SIZE = "Répartition des clients par taille";
    private static final String TITLE_CLIENT_REVENUE = "Répartition du revenu total";
    private static final String TITLE_EVENT_TYPE_PIE = "Répartition par type d'évènement";
    private static final String TITLE_EVENT_TYPE_BAR = "Nombre d'évènements par type";
    private static final String TITLE_PRESTATION_TYPE = "Répartition des prestations par type";
    private static final String TITLE_PRESTATION_CATEGORY = "Répartition des prestations par Catégorie";
    
    private static final String TITLE_TOP_EVENTS = "Top 5 évènements par popularité (inscriptions)";
    private static final String TITLE_EVENT_FREQUENCY = "Fréquence des évènements (total inscriptions par titre)";
    private static final String TITLE_TOP_PRESTATIONS = "Top 5 prestations par fréquence";
    private static final String TITLE_PRESTATION_FREQUENCY = "Fréquence des prestations par nom";

    private static final String AXIS_SECTOR = "Secteur d'activité";
    private static final String AXIS_SIZE = "Taille de l'entreprise";
    private static final String AXIS_CLIENT_COUNT = "Nombre de clients";
    private static final String AXIS_EVENT_TYPE = "Type d'évènement";
    private static final String AXIS_EVENT_COUNT = "Nombre";
    private static final String AXIS_PRESTATION_TYPE = "Type de prestation";
    private static final String AXIS_PRESTATION_CATEGORY = "Catégorie de prestation";
    private static final String AXIS_PRESTATION_COUNT = "Nombre";
    private static final String AXIS_EVENT_TITLE = "Titre de l'évènement";
    private static final String AXIS_INSCRIPTION_COUNT = "Nombre d'inscriptions";
    private static final String AXIS_PRESTATION_NAME = "Nom de la prestation";

    private static final String SERIES_CLIENTS = "Clients";
    private static final String SERIES_REVENUE_TOP5 = "Revenu des 5 premiers clients";
    private static final String SERIES_REVENUE_OTHERS = "Revenu des autres clients";
    private static final String SERIES_EVENT_COUNT = "Nombre";
    private static final String SERIES_PRESTATION_COUNT = "Nombre";

    private static final String MSG_NO_DATA = "Aucune donnée disponible";
    private static final String MSG_MISSING_DATA = "Données manquantes";

    
    private static final Color COLOR_PLOT_BACKGROUND = Color.WHITE;
    private static final Color COLOR_GRIDLINE = Color.LIGHT_GRAY;
    private static final Color COLOR_BAR_CLIENT_SECTOR = new Color(79, 129, 189); 
    private static final Color COLOR_BAR_CLIENT_SIZE = new Color(155, 187, 89); 
    private static final Color COLOR_BAR_EVENT_TYPE = new Color(128, 100, 162); 
    private static final Color COLOR_BAR_PRESTATION_CAT = new Color(75, 172, 198); 
    private static final Color COLOR_BAR_PRESTATION_FREQ = new Color(247, 150, 70); 
    private static final Font FONT_PIE_LABEL = new Font("SansSerif", Font.PLAIN, 10);
    private static final Font FONT_TITLE = new Font("SansSerif", Font.BOLD, 14);
    private static final Font FONT_AXIS_LABEL = new Font("SansSerif", Font.PLAIN, 12);

    /**
     * Crée un graphique camembert pour la distribution des statuts de contrat.
     */
    public static JFreeChart createContractStatusChart(ClientStats stats) {
        logger.debug("Création du diagramme : {}", TITLE_CONTRACT_STATUS);
        DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
        if (stats.getContractCountByStatus() != null) {
            stats.getContractCountByStatus().forEach((status, count) -> {
                if (status != null && count != null && count > 0) {
                    dataset.setValue(status.name(), count);
                }
            });
        } else {
            logger.warn("Données de statut de contrat manquantes pour le graphique.");
        }
        if (dataset.getItemCount() == 0) {
            dataset.setValue(MSG_MISSING_DATA, 1);
        }

        JFreeChart pieChart = ChartFactory.createPieChart(TITLE_CONTRACT_STATUS, dataset, true, true, false);
        stylePiePlot((PiePlot<String>) pieChart.getPlot());
        pieChart.setTitle(new TextTitle(TITLE_CONTRACT_STATUS, FONT_TITLE));
        return pieChart;
    }

    /**
     * Crée un diagramme à barres pour la distribution des clients par secteur.
     */
    public static JFreeChart createClientDistributionBySectorChart(ClientStats stats) {
        logger.debug("Création du diagramme : {}", TITLE_CLIENT_SECTOR);
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
        if (stats.getClientCountBySector() != null) {
            stats.getClientCountBySector().forEach((sector, count) -> {
                if (isValidCategory(sector) && isValidValue(count)) {
                    dataset.addValue(count, SERIES_CLIENTS, sector);
                }
            });
        } else {
            logger.warn("Données de secteur client manquantes pour le graphique.");
        }

        JFreeChart barChart = ChartFactory.createBarChart(
                TITLE_CLIENT_SECTOR, AXIS_SECTOR, AXIS_CLIENT_COUNT, dataset,
                PlotOrientation.VERTICAL, false, true, false);
        styleBarPlot(barChart.getCategoryPlot(), COLOR_BAR_CLIENT_SECTOR);
        barChart.setTitle(new TextTitle(TITLE_CLIENT_SECTOR, FONT_TITLE));
        return barChart;
    }

    /**
     * Crée un diagramme à barres pour la distribution des clients par taille.
     */
    public static JFreeChart createClientDistributionBySizeChart(ClientStats stats) {
        logger.debug("Création du diagramme : {}", TITLE_CLIENT_SIZE);
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
        if (stats.getClientCountBySize() != null) {
            stats.getClientCountBySize().forEach((size, count) -> {
                if (isValidCategory(size) && isValidValue(count)) {
                    dataset.addValue(count, SERIES_CLIENTS, size);
                }
            });
        } else {
            logger.warn("Données de taille client manquantes pour le graphique.");
        }

        JFreeChart barChart = ChartFactory.createBarChart(
                TITLE_CLIENT_SIZE, AXIS_SIZE, AXIS_CLIENT_COUNT, dataset,
                PlotOrientation.VERTICAL, false, true, false);
        styleBarPlot(barChart.getCategoryPlot(), COLOR_BAR_CLIENT_SIZE);
        barChart.setTitle(new TextTitle(TITLE_CLIENT_SIZE, FONT_TITLE));
        return barChart;
    }

    /**
     * Crée un graphique camembert pour la distribution du revenu entre le Top 5 et les autres.
     */
    public static JFreeChart createClientRevenueDistributionChart(ClientStats stats) {
        logger.debug("Création du diagramme : {}", TITLE_CLIENT_REVENUE);
        DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
        BigDecimal top5Revenue = BigDecimal.ZERO;
        BigDecimal otherRevenue = BigDecimal.ZERO;
        BigDecimal totalRevenue = Objects.requireNonNullElse(stats.getTotalRevenueOverall(), BigDecimal.ZERO);

        if (stats.getTop5ClientsByRevenue() != null) {
            top5Revenue = stats.getTop5ClientsByRevenue().stream()
                    .map(ClientStats.CompanyRevenue::getRevenue)
                    .filter(Objects::nonNull)
                    .reduce(BigDecimal.ZERO, BigDecimal::add);
        }

        if (totalRevenue.compareTo(BigDecimal.ZERO) > 0) {
            otherRevenue = totalRevenue.subtract(top5Revenue);
        }

        if (isValidValue(top5Revenue)) {
            dataset.setValue(SERIES_REVENUE_TOP5, top5Revenue);
        }
        if (isValidValue(otherRevenue)) {
            dataset.setValue(SERIES_REVENUE_OTHERS, otherRevenue);
        }
        if (dataset.getItemCount() == 0) {
            logger.warn("Aucune donnée de revenu disponible pour le graphique.");
            dataset.setValue(MSG_MISSING_DATA, 1);
        }

        JFreeChart pieChart = ChartFactory.createPieChart(TITLE_CLIENT_REVENUE, dataset, true, true, false);
        stylePiePlot((PiePlot<String>) pieChart.getPlot());
        pieChart.setTitle(new TextTitle(TITLE_CLIENT_REVENUE, FONT_TITLE));
        return pieChart;
    }

    /**
     * Crée un graphique camembert pour la distribution des types d'évènements.
     */
    public static JFreeChart createEventTypeDistributionChart(EventStats stats) {
        logger.debug("Création du diagramme : {}", TITLE_EVENT_TYPE_PIE);
        DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
        if (stats.getEventCountByType() != null) {
            stats.getEventCountByType().forEach((type, count) -> {
                if (type != null && isValidValue(count)) {
                    dataset.setValue(type.name(), count);
                }
            });
        } else {
            logger.warn("Données de type d'évènement manquantes pour le graphique.");
        }
        if (dataset.getItemCount() == 0) {
            dataset.setValue(MSG_MISSING_DATA, 1);
        }

        JFreeChart pieChart = ChartFactory.createPieChart(TITLE_EVENT_TYPE_PIE, dataset, true, true, false);
        stylePiePlot((PiePlot<String>) pieChart.getPlot());
        pieChart.setTitle(new TextTitle(TITLE_EVENT_TYPE_PIE, FONT_TITLE));
        return pieChart;
    }

    /**
     * Crée un diagramme à barres pour la distribution des types d'évènements.
     */
    public static JFreeChart createEventTypeDistributionBarChart(EventStats stats) {
        logger.debug("Création du diagramme : {}", TITLE_EVENT_TYPE_BAR);
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
        if (stats.getEventCountByType() != null) {
            stats.getEventCountByType().forEach((type, count) -> {
                if (type != null && isValidValue(count)) {
                    dataset.addValue(count, SERIES_EVENT_COUNT, type.name());
                }
            });
        } else {
            logger.warn("Données de type d'évènement manquantes pour le graphique.");
        }

        JFreeChart barChart = ChartFactory.createBarChart(
                TITLE_EVENT_TYPE_BAR, AXIS_EVENT_TYPE, AXIS_EVENT_COUNT, dataset,
                PlotOrientation.VERTICAL, false, true, false);
        styleBarPlot(barChart.getCategoryPlot(), COLOR_BAR_EVENT_TYPE);
        barChart.setTitle(new TextTitle(TITLE_EVENT_TYPE_BAR, FONT_TITLE));
        return barChart;
    }

    /**
     * Crée un graphique camembert pour la distribution des types de prestations.
     */
    public static JFreeChart createPrestationTypeDistributionChart(PrestationStats stats) {
        logger.debug("Création du diagramme : {}", TITLE_PRESTATION_TYPE);
        DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
        if (stats.getPrestationCountByType() != null) {
            stats.getPrestationCountByType().forEach((type, count) -> {
                if (type != null && isValidValue(count)) {
                    dataset.setValue(type.name(), count);
                }
            });
        } else {
            logger.warn("Données de type de prestation manquantes pour le graphique.");
        }
        if (dataset.getItemCount() == 0) {
            dataset.setValue(MSG_MISSING_DATA, 1);
        }

        JFreeChart pieChart = ChartFactory.createPieChart(TITLE_PRESTATION_TYPE, dataset, true, true, false);
        stylePiePlot((PiePlot<String>) pieChart.getPlot());
        pieChart.setTitle(new TextTitle(TITLE_PRESTATION_TYPE, FONT_TITLE));
        return pieChart;
    }

    /**
     * Crée un diagramme à barres pour la distribution des prestations par catégorie.
     */
    public static JFreeChart createPrestationCategoryDistributionChart(PrestationStats stats) {
        logger.debug("Création du diagramme : {}", TITLE_PRESTATION_CATEGORY);
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
        if (stats.getPrestationCountByCategory() != null) {
            stats.getPrestationCountByCategory().forEach((category, count) -> {
                if (isValidCategory(category) && isValidValue(count)) {
                    dataset.addValue(count, SERIES_PRESTATION_COUNT, category);
                }
            });
        } else {
            logger.warn("Données de catégorie de prestation manquantes pour le graphique.");
        }

        JFreeChart barChart = ChartFactory.createBarChart(
                TITLE_PRESTATION_CATEGORY, AXIS_PRESTATION_CATEGORY, AXIS_PRESTATION_COUNT, dataset,
                PlotOrientation.VERTICAL, false, true, false);
        styleBarPlot(barChart.getCategoryPlot(), COLOR_BAR_PRESTATION_CAT);

        
        CategoryPlot plot = barChart.getCategoryPlot();
        CategoryAxis domainAxis = plot.getDomainAxis();
        domainAxis.setCategoryLabelPositions(
            org.jfree.chart.axis.CategoryLabelPositions.createUpRotationLabelPositions(1) 
        );

        barChart.setTitle(new TextTitle(TITLE_PRESTATION_CATEGORY, FONT_TITLE));
        return barChart;
    }

    
    
    

    /** Applique un style commun aux graphiques camembert. */
    private static void stylePiePlot(PiePlot<String> plot) {
        plot.setBackgroundPaint(COLOR_PLOT_BACKGROUND);
        plot.setLabelFont(FONT_PIE_LABEL);
        plot.setNoDataMessage(MSG_NO_DATA);
        plot.setCircular(true);
        plot.setLabelGap(0.02);
        
    }

    /** Applique un style commun aux graphiques à barres. */
    private static void styleBarPlot(CategoryPlot plot, Color barColor) {
        plot.setBackgroundPaint(COLOR_PLOT_BACKGROUND);
        plot.setRangeGridlinePaint(COLOR_GRIDLINE);
        ((BarRenderer) plot.getRenderer()).setBarPainter(new StandardBarPainter()); 
        plot.getRenderer().setSeriesPaint(0, barColor); 

        
        CategoryAxis domainAxis = plot.getDomainAxis();
        domainAxis.setLabelFont(FONT_AXIS_LABEL);
        domainAxis.setTickLabelFont(FONT_PIE_LABEL); 

        ValueAxis rangeAxis = plot.getRangeAxis();
        rangeAxis.setLabelFont(FONT_AXIS_LABEL);
        rangeAxis.setTickLabelFont(FONT_AXIS_LABEL);
    }

    /** Vérifie si une catégorie (String) est valide pour l'affichage. */
    private static boolean isValidCategory(String category) {
        return category != null && !category.isBlank();
    }

    /** Vérifie si une valeur numérique (Long) est valide pour l'affichage (> 0). */
    private static boolean isValidValue(Long value) {
        return value != null && value > 0;
    }

    /** Vérifie si une valeur numérique (BigDecimal) est valide pour l'affichage (> 0). */
    private static boolean isValidValue(BigDecimal value) {
        return value != null && value.compareTo(BigDecimal.ZERO) > 0;
    }

}
