package com.businesscare.reporting.model;

import com.businesscare.reporting.model.enums.EventType;

import java.util.List;
import java.util.Map;
import java.util.Objects;

/**
 * Contient les statistiques agrégées sur les évènements pour le rapport.
 */
public class EventStats {

    private Map<EventType, Long> eventCountByType; 
    private Map<String, Long> eventFrequency;      
    private List<EventPopularity> top5EventsByPopularity; 
    private long totalEvents; 

    

    public EventStats() {
        
    }

    

    public Map<EventType, Long> getEventCountByType() {
        return eventCountByType;
    }

    public void setEventCountByType(Map<EventType, Long> eventCountByType) {
        this.eventCountByType = eventCountByType;
    }

    public Map<String, Long> getEventFrequency() {
        return eventFrequency;
    }

    public void setEventFrequency(Map<String, Long> eventFrequency) {
        this.eventFrequency = eventFrequency;
    }

    public List<EventPopularity> getTop5EventsByPopularity() {
        return top5EventsByPopularity;
    }

    public void setTop5EventsByPopularity(List<EventPopularity> top5EventsByPopularity) {
        this.top5EventsByPopularity = top5EventsByPopularity;
    }

    public long getTotalEvents() {
        return totalEvents;
    }

    public void setTotalEvents(long totalEvents) {
        this.totalEvents = totalEvents;
    }

    /**
     * Classe interne pour lier un évènement à sa popularité (ex: nombre d'inscriptions).
     * Implémente Comparable pour trier par popularité (décroissant).
     */
    public static class EventPopularity implements Comparable<EventPopularity> {
        private static final String NULL_EVENT_TITLE = "Sans titre";
        private final Event event;
        private final long popularityMetric; 

        public EventPopularity(Event event, long popularityMetric) {
            this.event = Objects.requireNonNull(event, "L\'évènement ne peut pas être null");
            this.popularityMetric = popularityMetric;
        }

        public Event getEvent() {
            return event;
        }

        public long getPopularityMetric() {
            return popularityMetric;
        }

        public String getEventTitle() {
            return event.getTitre() != null ? event.getTitre() : NULL_EVENT_TITLE;
        }

        @Override
        public int compareTo(EventPopularity other) {
            
            return Long.compare(other.popularityMetric, this.popularityMetric);
        }

        @Override
        public String toString() {
            return "EventPopularity{" +
                   "titreEvenement='" + getEventTitle() + '\'' +
                   ", metriquePopularite=" + popularityMetric +
                   '}';
        }
    }
}
