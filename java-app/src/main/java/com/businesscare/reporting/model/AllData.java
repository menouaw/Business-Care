package com.businesscare.reporting.model;

import java.util.List;

import com.businesscare.reporting.model.Company;
import com.businesscare.reporting.model.Contract;
import com.businesscare.reporting.model.Quote;
import com.businesscare.reporting.model.Invoice;
import com.businesscare.reporting.model.Event;
import com.businesscare.reporting.model.Prestation;

/** Regroupe toutes les données brutes récupérées de l'API. */
public class AllData {
    public final List<Company> companies;
    public final List<Contract> contracts;
    public final List<Quote> quotes;
    public final List<Invoice> invoices;
    public final List<Event> events;
    public final List<Prestation> prestations;

    public AllData(List<Company> c, List<Contract> co, List<Quote> q, List<Invoice> i, List<Event> e, List<Prestation> p) {
        this.companies = c; this.contracts = co; this.quotes = q;
        this.invoices = i; this.events = e; this.prestations = p;
    }

    
    public List<Company> getCompanies() { return companies; }
    public List<Contract> getContracts() { return contracts; }
    public List<Quote> getQuotes() { return quotes; }
    public List<Invoice> getInvoices() { return invoices; }
    public List<Event> getEvents() { return events; }
    public List<Prestation> getPrestations() { return prestations; }
} 