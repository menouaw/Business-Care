package com.businesscare.reporting.model;

/** Regroupe toutes les statistiques calculées. */
public class ProcessedStats {
    public final ClientStats clientStats;
    public final EventStats eventStats;
    public final PrestationStats prestationStats;

    public ProcessedStats(ClientStats cs, EventStats es, PrestationStats ps) {
        this.clientStats = cs; this.eventStats = es; this.prestationStats = ps;
    }

    public ClientStats getClientStats() {
        return clientStats;
    }

    public EventStats getEventStats() {
        return eventStats;
    }

    public PrestationStats getPrestationStats() {
        return prestationStats;
    }
} 