-- source C:/MAMP/htdocs/Business-Care/database/seeders/sample_data.sql

USE business_care;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE logs;
TRUNCATE TABLE notifications;
TRUNCATE TABLE evaluations;
TRUNCATE TABLE dons;
TRUNCATE TABLE communautes;
TRUNCATE TABLE evenements;
TRUNCATE TABLE rendez_vous;
TRUNCATE TABLE factures;
TRUNCATE TABLE devis;
TRUNCATE TABLE devis_prestations;
TRUNCATE TABLE contrats;
TRUNCATE TABLE preferences_utilisateurs;
TRUNCATE TABLE remember_me_tokens;
TRUNCATE TABLE personnes;
TRUNCATE TABLE prestations;
TRUNCATE TABLE entreprises; 
TRUNCATE TABLE roles;
TRUNCATE TABLE contrats_prestations;
TRUNCATE TABLE services;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO roles (nom, description) VALUES
('admin', 'Administrateur systeme'),
('salarie', 'Salarie d''une entreprise'),
('prestataire', 'Prestataire de services'),
('entreprise', 'Entreprise cliente');

INSERT INTO entreprises (nom, siret, adresse, code_postal, ville, telephone, email, site_web, logo_url, taille_entreprise, secteur_activite, date_creation) VALUES
('Tech Solutions SA', '12345678901234', '123 Rue de l''Innovation', '75001', 'Paris', '01 23 45 67 89', 'contact@techsolutions.fr', 'www.techsolutions.fr', '/logos/techsolutions.png', '51-200', 'Technologie', '2020-01-15'),
('Sante Plus', '98765432109876', '456 Avenue de la Sante', '75002', 'Paris', '01 98 76 54 32', 'contact@santeplus.fr', 'www.santeplus.fr', '/logos/santeplus.png', '201-500', 'Sante', '2019-06-20'),
('Bien-etre Corp', '45678901234567', '789 Boulevard du Bien-etre', '75003', 'Paris', '01 45 67 89 01', 'contact@bienetrecorp.fr', 'www.bienetrecorp.fr', '/logos/bienetrecorp.png', '11-50', 'Bien-etre', '2021-03-10');

INSERT INTO prestations (nom, description, prix, duree, type, categorie, niveau_difficulte, capacite_max, materiel_necessaire, prerequis) VALUES
('Consultation Psychologique', 'Seance individuelle de 45 minutes avec un psychologue qualifie', 80.00, 45, 'consultation', 'Sante mentale', NULL, 1, 'Aucun', 'Aucun'),
('Yoga en Entreprise', 'Seance de yoga adaptee au milieu professionnel', 120.00, 60, 'atelier', 'Bien-etre physique', 'debutant', 20, 'Tapis de yoga', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', 150.00, 90, 'webinar', 'Formation', 'debutant', 50, 'Ordinateur, connexion internet', 'Aucun');

INSERT INTO personnes (nom, prenom, email, mot_de_passe, telephone, date_naissance, genre, photo_url, role_id, entreprise_id, statut, derniere_connexion) VALUES
('Admin', 'System', 'admin@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '', 1, NULL, 'actif', '2024-03-17 18:30:00'),
('Salarie', 'Test', 'salarie@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '', 2, NULL, 'actif', '2024-03-17 18:30:00'),
('Prestataire', 'Test', 'prestataire@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '', 3, NULL, 'actif', '2024-03-17 18:30:00'),
('Entreprise', 'Test', 'entreprise@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '', 4, NULL, 'actif', '2024-03-17 18:30:00'),
('Dupont', 'Marie', 'marie.dupont@techsolutions.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 12 34 56 78', '1990-05-15', 'F', '', 2, 1, 'actif', '2024-03-17 14:30:00'),
('Durand', 'Sophie', 'sophie.durand@prestataire.com', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0600000001', '1988-03-15', 'F', '', 3, NULL, 'actif', NOW()),
('Leroy', 'Isabelle', 'isabelle.leroy@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0123456701', '1980-04-12', 'F', '', 4, 1, 'actif', NOW());

INSERT INTO services (nom, description, actif, ordre, max_effectif_inferieur_egal, activites_incluses, rdv_medicaux_inclus, chatbot_questions_limite, conseils_hebdo_personnalises, tarif_annuel_par_salarie) VALUES
('Starter Pack', 'Pour les petites équipes (jusqu\'à 30 salariés)', TRUE, 10, 30, 2, 1, 6, FALSE, 180.00),
('Basic Pack', 'Solution équilibrée (jusqu\'à 250 salariés)', TRUE, 20, 250, 3, 2, 20, FALSE, 150.00),
('Premium Pack', 'Offre complète pour grandes entreprises (251+ salariés)', TRUE, 30, NULL, 4, 3, NULL, TRUE, 100.00);

INSERT INTO contrats (entreprise_id, service_id, date_debut, date_fin, nombre_salaries, statut, conditions_particulieres) VALUES
(1, 3, '2024-01-01', '2024-12-31', 150, 'actif', 'Acces a toutes les prestations premium'),
(2, 3, '2024-02-01', '2025-01-31', 300, 'actif', 'Acces illimite aux prestations'),
(3, 2, '2024-03-01', '2024-08-31', 35, 'actif', 'Acces aux prestations de base');

INSERT INTO devis (entreprise_id, date_creation, date_validite, montant_total, montant_ht, tva, statut, conditions_paiement, delai_paiement) VALUES
(1, '2024-01-15', '2024-02-15', 1500.00, 1250.00, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(2, '2024-02-01', '2024-03-01', 2000.00, 1666.67, 20.00, 'accepte', 'Paiement a 45 jours', 45),
(3, '2024-02-15', '2024-03-15', 1800.00, 1500.00, 20.00, 'refuse', 'Paiement a 30 jours', 30);

INSERT INTO devis_prestations (devis_id, prestation_id, quantite, prix_unitaire_devis, description_specifique) VALUES
(1, 1, 10, 80.00, '10 consultations psychologiques individuelles'), 
(1, 2, 5, 120.00, '5 seances de yoga en entreprise'), 
(1, 3, 1, 100.00, '1 webinar gestion du stress (prix special)'), 

(2, 1, 15, 75.00, '15 consultations psychologiques (prix negocie)'), 
(2, 2, 7, 125.00, '7 seances de yoga (prix premium)'); 

INSERT INTO factures (entreprise_id, devis_id, numero_facture, date_emission, date_echeance, montant_total, montant_ht, tva, statut, mode_paiement) VALUES
(1, 1, 'FACT-2024-001', '2024-01-20', '2024-02-20', 1500.00, 1250.00, 20.00, 'payee', 'virement'),
(2, 2, 'FACT-2024-002', '2024-02-05', '2024-03-22', 2000.00, 1666.67, 20.00, 'payee', 'carte'),
(3, NULL, 'FACT-2024-015', '2024-04-25', '2024-05-25', 1200.00, 1000.00, 20.00, 'annulee', 'virement');

INSERT INTO rendez_vous (personne_id, prestation_id, praticien_id, date_rdv, duree, lieu, type_rdv, statut, notes) VALUES
(5, 1, 3, '2024-03-20 10:00:00', 45, 'Cabinet 101', 'presentiel', 'planifie', 'Premiere consultation'),
(5, 2, 3, '2024-03-21 14:00:00', 60, 'Salle de yoga', 'presentiel', 'confirme', 'Seance de groupe'),
(5, 3, 3, '2024-03-22 15:00:00', 90, 'En ligne', 'visio', 'planifie', 'Webinar en groupe');

INSERT INTO evaluations (personne_id, prestation_id, note, commentaire, date_evaluation) VALUES
(5, 1, 5, 'Excellent service, tres professionnel', '2024-04-21'),
(5, 2, 4, 'Très à l\'écoute, m\'a beaucoup aidé.', '2024-04-23');

INSERT INTO evenements (titre, description, date_debut, date_fin, lieu, type, capacite_max, niveau_difficulte, materiel_necessaire, prerequis) VALUES
('Conference sur le Bien-etre au Travail', 'Une conference interactive sur les bonnes pratiques', '2024-04-01 09:00:00', '2024-04-01 11:00:00', 'Salle de Conference Paris', 'conference', 100, 'intermediaire', 'Aucun', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', '2024-04-15 14:00:00', '2024-04-15 15:30:00', 'En ligne', 'webinar', 50, 'debutant', 'Ordinateur, connexion internet', 'Aucun');

INSERT INTO communautes (nom, description, type, niveau, capacite_max) VALUES
('Yoga & Meditation', 'Groupe de pratique du yoga et de la meditation', 'bien_etre', 'debutant', 20),
('Running Club', 'Club de course a pied pour tous niveaux', 'sport', 'intermediaire', 30);

INSERT INTO dons (personne_id, montant, type, description, date_don, statut) VALUES
(5, 50.00, 'financier', 'Don pour le programme de bien-etre', '2024-03-01', 'valide'),
(5, NULL, 'materiel', 'Don de materiel informatique (ecran)', '2024-03-15', 'valide');

INSERT INTO notifications (personne_id, titre, message, type, lien, lu, date_lecture) VALUES
(5, 'Nouveau Rendez-vous', 'Votre rendez-vous a ete confirme', 'success', '/rendez-vous/1', false, NULL),
(5, 'Paiement Reçu', 'Votre paiement a ete reçu avec succes', 'success', '/factures/1', true, '2024-03-17 10:30:00');

INSERT INTO contrats_prestations (contrat_id, prestation_id) VALUES
(1, 1),
(2, 2),
(3, 3);