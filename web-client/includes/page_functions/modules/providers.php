<?php
/**
 * fonctions pour la gestion des prestataires
 *
 * ce fichier contient les fonctions nécessaires pour gérer les prestataires de services
 */

// récupère la liste des prestataires
function getProvidersList($category = null, $page = 1, $limit = 20, $search = '') {
    
}

// récupère les détails d'un prestataire
function getProviderDetails($provider_id) {
    
}

// recherche de prestataires selon critères
function searchProviders($criteria) {
    
}

// récupère le calendrier de disponibilité d'un prestataire
function getProviderCalendar($provider_id, $start_date, $end_date) {
    
}

// récupère les évaluations d'un prestataire
function getProviderRatings($provider_id, $page = 1, $limit = 10) {
    
}

// récupère les catégories de prestataires
function getProviderCategories() {
    
}

// récupère les contrats d'un prestataire
function getProviderContracts($provider_id, $status = 'active') {
    
}

// récupère les services proposés par un prestataire
function getProviderServices($provider_id) {
    
}

// récupère les factures d'un prestataire
function getProviderInvoices($provider_id, $start_date = null, $end_date = null) {
    
}

// met à jour les paramètres d'un prestataire
function updateProviderSettings($provider_id, $settings) {
    
}
?>
