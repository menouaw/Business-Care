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
('Admin', 'System', 'admin@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '', 1, NULL, 'actif', '2026-03-17 18:30:00'),
('Salarie', 'Test', 'salarie@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '', 2, NULL, 'actif', '2026-03-17 18:30:00'),
('Prestataire', 'Test', 'prestataire@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '', 3, NULL, 'actif', '2026-03-17 18:30:00'),
('Entreprise', 'Test', 'entreprise@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '', 4, NULL, 'actif', '2026-03-17 18:30:00'),
('Dupont', 'Marie', 'marie.dupont@techsolutions.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 12 34 56 78', '1990-05-15', 'F', '', 2, 1, 'actif', '2026-03-17 14:30:00'),
('Durand', 'Sophie', 'sophie.durand@prestataire.com', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0600000001', '1988-03-15', 'F', '', 3, NULL, 'actif', NOW()),
('Leroy', 'Isabelle', 'isabelle.leroy@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0123456701', '1980-04-12', 'F', '', 4, 1, 'actif', NOW());

INSERT INTO contrats (entreprise_id, date_debut, date_fin, montant_mensuel, nombre_salaries, type_contrat, statut, conditions_particulieres) VALUES
(1, '2026-01-01', '2026-12-31', 5000.00, 150, 'premium', 'actif', 'Acces a toutes les prestations premium'),
(2, '2026-02-01', '2025-01-31', 7500.00, 300, 'entreprise', 'actif', 'Acces illimite aux prestations'),
(3, '2026-03-01', '2026-08-31', 2500.00, 35, 'standard', 'actif', 'Acces aux prestations de base');

INSERT INTO devis (entreprise_id, date_creation, date_validite, montant_total, montant_ht, tva, statut, conditions_paiement, delai_paiement) VALUES
(1, '2026-01-15', '2026-02-15', 1500.00, 1250.00, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(2, '2026-02-01', '2026-03-01', 2000.00, 1666.67, 20.00, 'accepte', 'Paiement a 45 jours', 45),
(3, '2026-02-15', '2026-03-15', 1800.00, 1500.00, 20.00, 'refuse', 'Paiement a 30 jours', 30);

INSERT INTO factures (entreprise_id, devis_id, numero_facture, date_emission, date_echeance, montant_total, montant_ht, tva, statut, mode_paiement) VALUES
(1, 1, 'FACT-2026-001', '2026-01-20', '2026-02-20', 1500.00, 1250.00, 20.00, 'payee', 'virement'),
(2, 2, 'FACT-2026-002', '2026-02-05', '2026-03-22', 2000.00, 1666.67, 20.00, 'payee', 'carte'),
(3, NULL, 'FACT-2026-015', '2026-04-25', '2026-05-25', 1200.00, 1000.00, 20.00, 'annulee', 'virement');

-- Rendez-vous pour Marie Dupont (ID 5) avec Sophie Durand (ID 6)
INSERT INTO rendez_vous (personne_id, prestation_id, praticien_id, date_rdv, duree, lieu, type_rdv, statut, notes) VALUES
-- Rendez-vous passés
(5, 1, 6, '2026-03-10 09:00:00', 45, 'Bureau 1', 'presentiel', 'termine', 'Consultation initiale - Bon contact.'),
(5, 2, 6, '2026-03-15 18:00:00', 60, 'Salle Yoga', 'presentiel', 'termine', 'Yoga détente après travail.'),
-- Rendez-vous futurs
(5, 1, 6, '2026-04-05 14:00:00', 45, 'En ligne via Zoom', 'visio', 'confirme', 'Consultation de suivi.'),
(5, 3, 6, '2026-04-10 10:30:00', 90, 'Lien Webinar', 'visio', 'planifie', 'Inscription au webinar Gestion du stress.'),
(5, 2, 6, '2026-04-12 12:00:00', 60, 'Salle Yoga', 'presentiel', 'annule', 'Empêchement de dernière minute signalée.'),
(5, 1, 6, '2026-05-01 11:00:00', 45, 'En ligne via Teams', 'visio', 'planifie', 'Point rapide avant les vacances.');

INSERT INTO evaluations (personne_id, prestation_id, note, commentaire, date_evaluation) VALUES
(5, 1, 5, 'Excellent service, tres professionnel', '2026-04-21'),
(5, 2, 4, 'Très à l\'écoute, m\'a beaucoup aidé.', '2026-04-23');

INSERT INTO evenements (titre, description, date_debut, date_fin, lieu, type, capacite_max, niveau_difficulte, materiel_necessaire, prerequis) VALUES
('Conference sur le Bien-etre au Travail', 'Une conference interactive sur les bonnes pratiques', '2026-04-01 09:00:00', '2026-04-01 11:00:00', 'Salle de Conference Paris', 'conference', 100, 'intermediaire', 'Aucun', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', '2026-04-15 14:00:00', '2026-04-15 15:30:00', 'En ligne', 'webinar', 50, 'debutant', 'Ordinateur, connexion internet', 'Aucun');

INSERT INTO communautes (nom, description, type, niveau, capacite_max) VALUES
('Yoga & Meditation', 'Groupe de pratique du yoga et de la meditation', 'bien_etre', 'debutant', 20),
('Running Club', 'Club de course a pied pour tous niveaux', 'sport', 'intermediaire', 30);

INSERT INTO dons (personne_id, montant, type, description, date_don, statut) VALUES
(5, 50.00, 'financier', 'Don pour le programme de bien-etre', '2026-03-01', 'valide'),
(5, NULL, 'materiel', 'Don de materiel informatique (ecran)', '2026-03-15', 'valide');

INSERT INTO notifications (personne_id, titre, message, type, lien, lu, date_lecture) VALUES
(5, 'Nouveau Rendez-vous', 'Votre rendez-vous a ete confirme', 'success', '/rendez-vous/1', false, NULL),
(5, 'Paiement Reçu', 'Votre paiement a ete reçu avec succes', 'success', '/factures/1', true, '2026-03-17 10:30:00');

INSERT INTO contrats_prestations (contrat_id, prestation_id) VALUES
(1, 1),
(2, 2),
(3, 3);

INSERT INTO services (nom, description, actif, ordre) VALUES
('Starter Pack', 'Pour les petites équipes (jusqu\'à 30 salariés)', TRUE, 10),
('Basic Pack', 'Solution équilibrée (jusqu\'à 250 salariés)', TRUE, 20),
('Premium Pack', 'Offre complète pour grandes entreprises (251+ salariés)', TRUE, 30);

INSERT INTO associations (nom) VALUES
('Restos du Coeur'),
('Fondation Abbé Pierre'),
('Secours Populaire Français');