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
    p.nom as nom_personne,
    p.prenom as prenom_personne,
    pr.nom as nom_prestation,
    rv.statut
FROM rendez_vous rv
JOIN personnes p ON rv.personne_id = p.id
JOIN prestations pr ON rv.prestation_id = pr.id
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
    e.lieu
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