package com.businesscare.reporting.model;

import com.businesscare.reporting.model.enums.EventType;

import java.util.List;
import java.util.Map;

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
     * Classe interne simple pour lier un évènement à sa popularité (ex: nombre d'inscriptions).
     */
    public static class EventPopularity implements Comparable<EventPopularity> {
        private Event event;
        private long popularityMetric; 

        public EventPopularity(Event event, long popularityMetric) {
            this.event = event;
            this.popularityMetric = popularityMetric;
        }

        public Event getEvent() {
            return event;
        }

        public long getPopularityMetric() {
            return popularityMetric;
        }

        @Override
        public int compareTo(EventPopularity other) {
            
            return Long.compare(other.popularityMetric, this.popularityMetric);
        }

        @Override
        public String toString() {
            return "EventPopularity{" +
                   "eventName=" + (event != null ? event.getTitre() : "null") +
                   ", popularityMetric=" + popularityMetric +
                   '}';
        }
    }
}
