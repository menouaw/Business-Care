-- source C:/MAMP/htdocs/Business-Care/database/schemas/views.sql


USE business_care;

CREATE VIEW v_prestations_populaires AS
SELECT 
    p.id,
    p.nom,
    p.type,
    p.prix,
    COUNT(e.id) as nombre_evaluations,
    AVG(e.note) as note_moyenne
FROM prestations p
LEFT JOIN evaluations e ON p.id = e.prestation_id
GROUP BY p.id
ORDER BY nombre_evaluations DESC;

CREATE VIEW v_rendez_vous_du_jour AS
SELECT 
    rv.id,
    rv.date_rdv,
    p_client.nom as nom_client,
    p_client.prenom as prenom_client,
    pr.nom as nom_prestation,
    p_praticien.nom as nom_praticien,
    p_praticien.prenom as prenom_praticien,
    rv.statut
FROM rendez_vous rv
JOIN personnes p_client ON rv.personne_id = p_client.id
JOIN prestations pr ON rv.prestation_id = pr.id
LEFT JOIN personnes p_praticien ON rv.praticien_id = p_praticien.id
WHERE DATE(rv.date_rdv) = CURDATE()
ORDER BY rv.date_rdv;

CREATE VIEW v_factures_en_attente AS
SELECT 
    f.id,
    f.numero_facture,
    f.date_emission,
    f.date_echeance,
    f.montant_total,
    e.nom as nom_entreprise,
    DATEDIFF(CURDATE(), f.date_echeance) as jours_retard
FROM factures f
JOIN entreprises e ON f.entreprise_id = e.id
WHERE f.statut = 'en_attente'
ORDER BY f.date_echeance;

CREATE VIEW v_evenements_a_venir AS
SELECT 
    e.id,
    e.titre,
    e.date_debut,
    e.date_fin,
    e.type,
    e.capacite_max,
    e.niveau_difficulte,
    e.lieu,
    e.organise_par_bc
FROM evenements e
WHERE e.date_debut >= CURDATE()
ORDER BY e.date_debut;

CREATE VIEW v_evaluations_prestations AS
SELECT 
    p.nom as nom_prestation,
    p.type,
    COUNT(e.id) as nombre_evaluations,
    AVG(e.note) as note_moyenne,
    MIN(e.note) as note_min,
    MAX(e.note) as note_max
FROM prestations p
LEFT JOIN evaluations e ON p.id = e.prestation_id
GROUP BY p.id
ORDER BY note_moyenne DESC;


CREATE VIEW v_factures_prestataires_impayees AS
SELECT 
    fp.id,
    fp.numero_facture,
    fp.date_facture,
    fp.periode_debut,
    fp.periode_fin,
    fp.montant_total,
    fp.statut,
    p.id as prestataire_id,
    CONCAT(p.prenom, ' ', p.nom) as nom_prestataire,
    p.email as email_prestataire
FROM factures_prestataires fp
JOIN personnes p ON fp.prestataire_id = p.id
WHERE fp.statut = 'impayee'
ORDER BY fp.date_facture DESC;


CREATE VIEW v_details_facture_prestataire AS
SELECT
    fp.id as facture_id,
    fp.numero_facture,
    fp.date_facture,
    fp.periode_debut,
    fp.periode_fin,
    fp.montant_total as facture_montant_total,
    fp.statut as facture_statut,
    fp.date_paiement,
    p_prest.id as prestataire_id,
    CONCAT(p_prest.prenom, ' ', p_prest.nom) as nom_prestataire,
    p_prest.email as email_prestataire,
    fpl.id as ligne_id,
    fpl.description as ligne_description,
    fpl.montant as ligne_montant,
    fpl.rendez_vous_id,
    rdv.date_rdv,
    prest.nom as nom_prestation
FROM factures_prestataires fp
JOIN personnes p_prest ON fp.prestataire_id = p_prest.id
LEFT JOIN facture_prestataire_lignes fpl ON fp.id = fpl.facture_prestataire_id
LEFT JOIN rendez_vous rdv ON fpl.rendez_vous_id = rdv.id
LEFT JOIN prestations prest ON rdv.prestation_id = prest.id;


CREATE VIEW v_habilitations_details AS
SELECT
    h.id as habilitation_id,
    h.prestataire_id,
    CONCAT(p.prenom, ' ', p.nom) as nom_prestataire,
    p.email as email_prestataire,
    h.type,
    h.nom_document,
    h.organisme_emission,
    h.date_obtention,
    h.date_expiration,
    h.statut,
    h.document_url,
    h.notes,
    h.created_at,
    h.updated_at
FROM habilitations h
JOIN personnes p ON h.prestataire_id = p.id
WHERE p.role_id = 3; 
CREATE VIEW v_habilitations_pending AS
SELECT *
FROM v_habilitations_details
WHERE statut = 'en_attente_validation';

CREATE VIEW v_habilitations_expired AS
SELECT *
FROM v_habilitations_details
WHERE statut != 'expiree' 
  AND date_expiration IS NOT NULL
  AND date_expiration < CURDATE();


CREATE VIEW v_provider_services_details AS
SELECT
    pp.prestataire_id,
    CONCAT(p_prov.prenom, ' ', p_prov.nom) as nom_prestataire,
    pp.prestation_id,
    prest.nom as nom_prestation,
    prest.type as type_prestation,
    prest.prix as prix_prestation_base,
    pp.created_at as date_assignation
FROM prestataires_prestations pp
JOIN personnes p_prov ON pp.prestataire_id = p_prov.id
JOIN prestations prest ON pp.prestation_id = prest.id
WHERE p_prov.role_id = 3; 


CREATE VIEW v_provider_availability_details AS
SELECT
    pa.id as availability_id,
    pa.prestataire_id,
    CONCAT(p.prenom, ' ', p.nom) as nom_prestataire,
    pa.type,
    pa.date_debut,
    pa.date_fin,
    pa.heure_debut,
    pa.heure_fin,
    pa.jour_semaine,
    pa.recurrence_fin,
    pa.notes
FROM prestataires_disponibilites pa
JOIN personnes p ON pa.prestataire_id = p.id
WHERE p.role_id = 3; 