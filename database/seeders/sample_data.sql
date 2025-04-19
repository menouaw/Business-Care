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
TRUNCATE TABLE consultation_creneaux;
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
('salarie', 'Salarie d\'une entreprise'),
('prestataire', 'Prestataire de services'),
('entreprise', 'Entreprise cliente');

INSERT INTO entreprises (nom, siret, adresse, code_postal, ville, telephone, email, site_web, logo_url, taille_entreprise, secteur_activite, date_creation) VALUES
('Tech Solutions SA', '12345678901234', '123 Rue de l\'Innovation', '75001', 'Paris', '01 23 45 67 89', 'contact@techsolutions.fr', 'www.techsolutions.fr', '/logos/techsolutions.png', '51-200', 'Technologie', '2020-01-15'),
('Sante Plus', '98765432109876', '456 Avenue de la Sante', '75002', 'Paris', '01 98 76 54 32', 'contact@santeplus.fr', 'www.santeplus.fr', '/logos/santeplus.png', '201-500', 'Sante', '2019-06-20'),
('Bien-etre Corp', '45678901234567', '789 Boulevard du Bien-etre', '75003', 'Paris', '01 45 67 89 01', 'contact@bienetrecorp.fr', 'www.bienetrecorp.fr', '/logos/bienetrecorp.png', '11-50', 'Bien-etre', '2021-03-10');

INSERT INTO prestations (nom, description, prix, duree, type, categorie, niveau_difficulte, capacite_max, materiel_necessaire, prerequis) VALUES
('Consultation Psychologique', 'Seance individuelle de 45 minutes avec un psychologue qualifie', 80.00, 45, 'consultation', 'Sante mentale', NULL, 1, 'Aucun', 'Aucun'),
('Yoga en Entreprise', 'Seance de yoga adaptee au milieu professionnel', 120.00, 60, 'atelier', 'Bien-etre physique', 'debutant', 20, 'Tapis de yoga', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', 150.00, 90, 'webinar', 'Formation', 'debutant', 50, 'Ordinateur, connexion internet', 'Aucun'),
('Consultation Nutritionniste', 'Bilan et conseils personnalisés avec un nutritionniste', 90.00, 60, 'consultation', 'Nutrition', NULL, 1, 'Aucun', 'Aucun');

INSERT INTO personnes (nom, prenom, email, mot_de_passe, telephone, date_naissance, genre, photo_url, role_id, entreprise_id, statut, derniere_connexion) VALUES
('Admin', 'System', 'admin@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '/photos/admin.jpg', 1, NULL, 'actif', '2026-03-17 18:30:00'),
('Salarie', 'Test', 'salarie@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '/photos/salarie.jpg', 2, NULL, 'actif', '2026-03-17 18:30:00'),
('Prestataire', 'Test', 'prestataire@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '/photos/prestataire.jpg', 3, NULL, 'actif', '2026-03-17 18:30:00'),
('Entreprise', 'Test', 'entreprise@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '/photos/entreprise.jpg', 4, NULL, 'actif', '2026-03-17 18:30:00'),
('Dupont', 'Marie', 'marie.dupont@techsolutions.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 12 34 56 78', '1990-05-15', 'F', '/photos/marie.dupont.jpg', 2, 1, 'actif', '2026-03-17 14:30:00'),
('Martin', 'Jean', 'jean.martin@santeplus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 23 45 67 89', '1985-08-20', 'M', '/photos/jean.martin.jpg', 2, 2, 'actif', '2026-03-17 15:45:00'),
('Petit', 'Sophie', 'sophie.petit@bienetrecorp.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 34 56 78 90', '1992-03-10', 'F', '/photos/sophie.petit.jpg', 2, 3, 'actif', '2026-03-17 16:20:00'),
('Dubois', 'Pierre', 'pierre.dubois@ecohabitat.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 45 67 89 01', '1988-12-05', 'M', '/photos/pierre.dubois.jpg', 2, 4, 'actif', '2026-03-17 17:10:00'),
('Bernard', 'Emma', 'emma.bernard@financeconseil.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 56 78 90 12', '1995-07-25', 'F', '/photos/emma.bernard.jpg', 2, 5, 'actif', '2026-03-17 18:00:00'),
('Robert', 'Lucas', 'lucas.robert@innovationdigitale.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 67 89 01 23', '1991-04-15', 'M', '/photos/lucas.robert.jpg', 2, 6, 'actif', '2026-03-17 19:15:00'),
('Richard', 'Julie', 'julie.richard@santepro.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 78 90 12 34', '1987-09-30', 'F', '/photos/julie.richard.jpg', 2, 7, 'actif', '2026-03-17 20:30:00'),
('Petit', 'Thomas', 'thomas.petit@bienetreplus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 89 01 23 45', '1993-02-20', 'M', '/photos/thomas.petit.jpg', 2, 8, 'actif', '2026-03-17 21:45:00'),
('Durand', 'Lea', 'lea.durand@ecosolutions.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 90 12 34 56', '1994-11-10', 'F', '/photos/lea.durand.jpg', 2, 9, 'actif', '2026-03-17 22:15:00'),
('Moreau', 'Hugo', 'hugo.moreau@financeplus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 01 23 45 67', '1996-06-25', 'M', '/photos/hugo.moreau.jpg', 2, 10, 'actif', '2026-03-17 23:00:00'),
('Duamel', 'Heloise', 'duamelle.heloise@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '06 12 34 56 78', '1995-03-15', 'F', '/photos/mdubois.jpg', 2, 10, 'actif', '2026-04-03 09:15:00'),
('Dupois', 'Jacques', 'jacques.dupois@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '06 12 34 56 78', '1995-03-15', 'M', '/photos/mdupois.jpg', 2, 10, 'inactif', '2026-04-03 09:15:00');

INSERT INTO contrats (entreprise_id, date_debut, date_fin, montant_mensuel, nombre_salaries, type_contrat, statut, conditions_particulieres) VALUES
(1, '2026-01-01', '2026-12-31', 5000.00, 150, 'premium', 'actif', 'Acces a toutes les prestations premium'),
(2, '2026-02-01', '2025-01-31', 7500.00, 300, 'entreprise', 'actif', 'Acces illimite aux prestations'),
(3, '2026-03-01', '2026-08-31', 2500.00, 35, 'standard', 'actif', 'Acces aux prestations de base'),
(4, '2026-01-15', '2026-07-14', 3200.00, 80, 'standard', 'actif', 'Acces aux prestations de base et ateliers'),
(5, '2026-02-10', '2025-02-09', 4500.00, 120, 'premium', 'actif', 'Acces a toutes les prestations avec tarifs preferentiels'),
(6, '2026-01-20', '2026-12-31', 6000.00, 180, 'premium', 'actif', 'Acces a toutes les prestations premium avec support prioritaire'),
(7, '2026-02-15', '2026-12-31', 8500.00, 350, 'entreprise', 'actif', 'Acces illimite aux prestations avec formation personnalisee'),
(8, '2026-03-01', '2026-12-31', 2800.00, 45, 'standard', 'en_attente', 'Acces aux prestations de base et ateliers'),
(9, '2026-01-25', '2026-12-31', 3800.00, 90, 'standard', 'actif', 'Acces aux prestations de base et ateliers'),
(10, '2026-02-20', '2026-12-31', 4200.00, 100, 'premium', 'actif', 'Acces a toutes les prestations avec tarifs preferentiels'),
(10, '2026-01-01', '2026-12-31', 5000.00, 150, 'premium', 'actif', 'Acces a toutes les prestations premium'),
(10, '2023-01-01', '2023-12-31', 5000.00, 150, 'premium', 'expire', 'Acces a toutes les prestations premium'),
(7, '2026-02-15', '2025-02-14', 8500.00, 350, 'entreprise', 'actif', 'Acces illimite aux prestations avec formation personnalisee'),
(8, '2026-03-01', NULL, 2800.00, 45, 'standard', 'actif', 'Acces aux prestations de base et ateliers - renouvellement tacite'),
(9, '2026-01-25', '2026-06-30', 3800.00, 90, 'standard', 'resilie', 'Resiliation au 30/06/2026'),
(10, '2026-02-20', '2026-08-19', 4200.00, 100, 'premium', 'expire', 'Contrat expiré non renouvelé'),
(11, '2023-11-01', '2026-10-31', 2000.00, 25, 'standard', 'actif', 'Restauration - bien etre'),
(12, '2026-01-01', '2026-12-31', 5500.00, 170, 'premium', 'actif', 'Logistique - gestion stress'),
(13, '2023-09-01', '2026-08-31', 3000.00, 40, 'standard', 'actif', 'Marketing - creativite'),
(14, '2026-04-01', '2025-03-31', 1500.00, 8, 'standard', 'en_attente', 'Automobile - ergonomie');

INSERT INTO devis (entreprise_id, date_creation, date_validite, montant_total, montant_ht, tva, statut, conditions_paiement, delai_paiement) VALUES
(1, '2026-01-15', '2026-02-15', 1500.00, 1250.00, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(2, '2026-02-01', '2026-03-01', 2000.00, 1666.67, 20.00, 'accepte', 'Paiement a 45 jours', 45),
(3, '2026-02-15', '2026-03-15', 1800.00, 1500.00, 20.00, 'refuse', 'Paiement a 30 jours', 30),
(4, '2026-01-10', '2026-02-10', 3200.00, 2666.67, 20.00, 'accepte', 'Paiement a 15 jours', 15),
(5, '2026-02-20', '2026-03-20', 2500.00, 2083.33, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(6, '2026-01-25', '2026-02-25', 2800.00, 2333.33, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(7, '2026-02-10', '2026-03-10', 3500.00, 2916.67, 20.00, 'en_attente', 'Paiement a 45 jours', 45),
(8, '2026-02-20', '2026-03-20', 2200.00, 1833.33, 20.00, 'expire', 'Paiement a 30 jours', 30),
(9, '2026-01-30', '2026-02-28', 2600.00, 2166.67, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(10, '2026-02-25', '2026-03-25', 2400.00, 2000.00, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(11, '2026-03-05', '2026-04-05', 950.00, 791.67, 20.00, 'en_attente', 'Paiement comptant', 0),
(12, '2026-03-10', '2026-04-10', 3100.00, 2583.33, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(13, '2026-03-15', '2026-04-15', 1600.00, 1333.33, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(14, '2026-03-20', '2026-04-20', 700.00, 583.33, 20.00, 'refuse', 'Paiement comptant', 0),
(15, '2026-03-25', '2026-04-25', 2900.00, 2416.67, 20.00, 'en_attente', 'Paiement a 60 jours', 60),
(16, '2026-04-01', '2026-05-01', 550.00, 458.33, 20.00, 'en_attente', 'Paiement comptant', 0),
(17, '2026-04-05', '2026-05-05', 1950.00, 1625.00, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(18, '2026-04-10', '2026-05-10', 480.00, 400.00, 20.00, 'accepte', 'Paiement comptant', 0),
(19, '2026-04-15', '2026-05-15', 2100.00, 1750.00, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(20, '2026-04-20', '2026-05-20', 880.00, 733.33, 20.00, 'refuse', 'Paiement comptant', 0),
(1, '2026-04-25', '2026-05-25', 1750.00, 1458.33, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(2, '2026-05-01', '2026-06-01', 2200.00, 1833.33, 20.00, 'en_attente', 'Paiement a 45 jours', 45),
(6, '2026-05-05', '2026-06-05', 3000.00, 2500.00, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(7, '2026-05-10', '2026-06-10', 3800.00, 3166.67, 20.00, 'en_attente', 'Paiement a 45 jours', 45),
(12, '2026-05-15', '2026-06-15', 3300.00, 2750.00, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(15, '2026-05-20', '2026-06-20', 3100.00, 2583.33, 20.00, 'en_attente', 'Paiement a 60 jours', 60),
(17, '2026-05-25', '2026-06-25', 2050.00, 1708.33, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(21, '2026-06-01', '2026-07-01', 4000.00, 3333.33, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(25, '2026-06-05', '2026-07-05', 1500.00, 1250.00, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(30, '2026-06-10', '2026-07-10', 8000.00, 6666.67, 20.00, 'en_attente', 'Paiement a 60 jours', 60);

INSERT INTO factures (entreprise_id, devis_id, numero_facture, date_emission, date_echeance, montant_total, montant_ht, tva, statut, mode_paiement) VALUES
(1, 1, 'FACT-2026-001', '2026-01-20', '2026-02-20', 1500.00, 1250.00, 20.00, 'payee', 'virement'),
(2, 2, 'FACT-2026-002', '2026-02-05', '2026-03-22', 2000.00, 1666.67, 20.00, 'payee', 'carte'),
(4, 4, 'FACT-2026-003', '2026-02-20', '2026-03-06', 3200.00, 2666.67, 20.00, 'payee', 'carte'),
(5, 5, 'FACT-2026-004', '2026-03-01', '2026-03-31', 2500.00, 2083.33, 20.00, 'payee', 'prelevement'),
(6, 6, 'FACT-2026-005', '2026-02-01', '2026-03-02', 2800.00, 2333.33, 20.00, 'payee', 'virement'),
(7, 7, 'FACT-2026-006', '2026-02-15', '2026-03-31', 3500.00, 2916.67, 20.00, 'retard', 'virement'),
(9, 9, 'FACT-2026-007', '2026-02-05', '2026-03-06', 2600.00, 2166.67, 20.00, 'payee', 'carte'),
(10, 10, 'FACT-2026-008', '2026-03-05', '2026-04-05', 2400.00, 2000.00, 20.00, 'en_attente', 'virement'),
(12, 12, 'FACT-2026-009', '2026-03-15', '2026-04-15', 3100.00, 2583.33, 20.00, 'en_attente', 'virement'),
(13, 13, 'FACT-2026-010', '2026-03-20', '2026-04-20', 1600.00, 1333.33, 20.00, 'en_attente', 'carte'),
(17, 17, 'FACT-2026-011', '2026-04-10', '2026-05-10', 1950.00, 1625.00, 20.00, 'en_attente', 'prelevement'),
(18, 18, 'FACT-2026-012', '2026-04-15', '2026-04-15', 480.00, 400.00, 20.00, 'payee', 'carte'),
(1, NULL, 'FACT-2026-013', '2026-04-20', '2026-05-20', 500.00, 416.67, 20.00, 'en_attente', 'virement'),
(2, NULL, 'FACT-2026-014', '2026-04-22', '2026-05-22', 750.00, 625.00, 20.00, 'en_attente', 'carte'),
(3, NULL, 'FACT-2026-015', '2026-04-25', '2026-05-25', 1200.00, 1000.00, 20.00, 'annulee', 'virement'),
(4, NULL, 'FACT-2026-016', '2026-04-28', '2026-05-28', 900.00, 750.00, 20.00, 'en_attente', 'prelevement'),
(18, NULL, 'FACT-2026-030', '2026-06-30', '2026-07-30', 300.00, 250.00, 20.00, 'payee', 'virement');

-- Rendez-vous pour Marie Dupont (ID 5) avec Sophie Durand (ID 6)
INSERT INTO rendez_vous (personne_id, prestation_id, praticien_id, date_rdv, duree, lieu, type_rdv, statut, notes) VALUES
(5, 1, 3, '2026-03-20 10:00:00', 45, 'Cabinet 101', 'presentiel', 'planifie', 'Premiere consultation'),
(5, 2, 3, '2026-03-21 14:00:00', 60, 'Salle de yoga', 'presentiel', 'confirme', 'Seance de groupe'),
(5, 3, 3, '2026-03-22 15:00:00', 90, 'En ligne', 'visio', 'planifie', 'Webinar en groupe'),
(5, 4, 3, '2026-03-23 09:30:00', 60, 'Bureau 202', 'presentiel', 'confirme', 'Suivi mensuel'),
(5, 5, 3, '2026-03-24 11:00:00', 45, 'Salle de reunion', 'presentiel', 'planifie', 'Bilan trimestriel'),
(6, 6, 3, '2026-03-25 10:30:00', 60, 'Salle de meditation', 'presentiel', 'confirme', 'Seance de groupe'),
(7, 7, 3, '2026-03-26 14:00:00', 45, 'Cabinet 303', 'presentiel', 'planifie', 'Premiere consultation'),
(8, 8, 3, '2026-03-27 09:00:00', 90, 'Salle de formation', 'presentiel', 'confirme', 'Formation groupe'),
(9, 9, 3, '2026-03-28 15:00:00', 60, 'En ligne', 'visio', 'planifie', 'Webinar individuel'),
(10, 10, 3, '2026-03-29 11:30:00', 120, 'Salle de conference', 'presentiel', 'confirme', 'Conference annuelle'),
(12, 1, 3, '2026-04-20 10:00:00', 45, 'Cabinet 101', 'presentiel', 'termine', 'Premiere consultation effectuée'),
(13, 7, 3, '2026-04-21 11:00:00', 45, 'Cabinet 102', 'visio', 'confirme', 'Coaching nutritionnel'),
(14, 2, 6, '2026-04-28 09:00:00', 60, 'Salle de yoga Entreprise', 'presentiel', 'planifie', 'Yoga sur site'),
(15, 1, 5, '2026-04-22 14:30:00', 45, 'En ligne', 'visio', 'termine', 'Consultation psy'),
(16, 12, 6, '2026-04-29 18:00:00', 60, 'Salle de sport Entreprise', 'presentiel', 'confirme', 'Pilates'),
(2, 1, 3, '2026-05-10 09:00:00', 45, 'Cabinet Virtuel 1', 'visio', 'termine', 'Consultation pour Salarie Test'),
(2, 6, 3, '2026-05-25 16:00:00', 60, 'Salle Zen', 'presentiel', 'planifie', 'Meditation pour Salarie Test'),
(2, 2, 6, '2026-06-05 12:30:00', 60, 'Salle de yoga Entreprise', 'presentiel', 'planifie', 'Yoga pour Salarie Test'),
(5, 7, 3, '2026-06-03 10:00:00', 45, 'Cabinet 102', 'presentiel', 'planifie', 'Suivi nutritionnel'),
(6, 11, 3, '2026-06-04 14:00:00', 60, 'Cabinet 201', 'presentiel', 'confirme', 'Sophrologie détente'),
(7, 16, 5, '2026-06-06 09:00:00', 45, 'Cabinet Osteo A', 'presentiel', 'planifie', 'Consultation Ostéopathie dos'),
(8, 17, 3, '2026-06-07 11:00:00', 90, 'Salle Pégase', 'presentiel', 'planifie', 'Atelier Gestion Temps - Equipe Dubois'),
(9, 21, 6, '2026-06-10 12:30:00', 20, 'Espace Détente Etage 3', 'presentiel', 'confirme', 'Massage Amma'),
(10, 26, 3, '2026-06-11 15:00:00', 60, 'En ligne', 'visio', 'planifie', 'Coaching de vie - Objectifs T3'),
(12, 1, 3, '2026-06-12 16:00:00', 45, 'Cabinet 101', 'presentiel', 'annule', 'Annulé par le patient'),
(13, 2, 6, '2026-06-13 18:00:00', 60, 'Salle de yoga Entreprise', 'presentiel', 'confirme', 'Yoga doux'),
(14, 8, 3, '2026-06-14 09:30:00', 90, 'Salle de formation B', 'presentiel', 'termine', 'Formation Ergonomie effectuée'),
(2, 11, 3, '2026-06-17 14:00:00', 60, 'En ligne', 'visio', 'planifie', 'Sophrologie pour Salarie Test'),
(5, 21, 6, '2026-06-19 12:00:00', 20, 'Espace Détente Etage 1', 'presentiel', 'confirme', 'Massage Amma');

INSERT INTO evaluations (personne_id, prestation_id, note, commentaire, date_evaluation) VALUES
(12, 1, 5, 'Excellent service, tres professionnel', '2026-04-21'),
(14, 1, 4, 'Très à l'écoute, m'a beaucoup aidé.', '2026-04-23'),
(15, 8, 5, 'Formation ergo très claire et pratique.', '2026-04-24'),
(14, 12, 4, 'Cours de pilates dynamique, bon prof.', '2026-05-02'),
(12, 6, 4, 'Bonne séance de méditation, lieu agréable.', '2026-04-26'),
(15, 21, 5, 'Massage assis très relaxant pendant la pause.', '2026-05-01'),
(13, 7, 4, 'Bons conseils nutritionnels, à voir sur la durée.', '2026-04-22'),
(16, 16, 5, 'Soulagement rapide après la séance d'ostéo.', '2026-05-02'),
(14, 2, 4, 'Bonne ambiance au cours de yoga.', '2026-05-19'),
(15, 7, 4, 'Le bilan nutrition était intéressant.', '2026-05-20'),
(16, 6, 5, 'Très bonne méditation, je reviendrai.', '2026-05-21'),
(12, 9, 4, 'Conseils pratiques pour mieux dormir.', '2026-05-03'),
(13, 11, 5, 'Première séance de sophro, très positive.', '2026-05-06'),
(15, 22, 3, 'Webinar un peu trop basique sur l'investissement.', '2026-05-08'),
(12, 16, 4, 'Manipulations douces et efficaces.', '2026-05-17'),
(13, 21, 3, 'Massage trop court.', '2026-05-18'),
(2, 1, 4, 'Consultation utile, praticien à l'écoute.', '2026-05-11');

INSERT INTO evenements (titre, description, date_debut, date_fin, lieu, type, capacite_max, niveau_difficulte, materiel_necessaire, prerequis) VALUES
('Conference sur le Bien-etre au Travail', 'Une conference interactive sur les bonnes pratiques', '2026-04-01 09:00:00', '2026-04-01 11:00:00', 'Salle de Conference Paris', 'conference', 100, 'intermediaire', 'Aucun', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', '2026-04-15 14:00:00', '2026-04-15 15:30:00', 'En ligne', 'webinar', 50, 'debutant', 'Ordinateur, connexion internet', 'Aucun'),
('Atelier Ergonomie au Bureau', 'Apprenez a amenager votre espace de travail', '2026-04-20 10:00:00', '2026-04-20 12:00:00', 'Centre d'affaires Lyon', 'atelier', 30, 'debutant', 'Aucun', 'Aucun'),
('Seminaire Leadership Bienveillant', 'Developper un leadership positif et efficace', '2026-05-05 09:00:00', '2026-05-06 17:00:00', 'Hotel Mercure Bordeaux', 'atelier', 40, 'avance', 'Carnet de notes', 'Experience en management'),
('Journee Detox Digitale', 'Une journee pour apprendre a se deconnecter', '2026-05-10 09:00:00', '2026-05-10 17:00:00', 'Espace Zen Marseille', 'autre', 25, 'debutant', 'Tenue confortable', 'Aucun'),
('Atelier Meditation en Groupe', 'Seance de meditation collective', '2026-04-25 14:00:00', '2026-04-25 15:30:00', 'Centre de bien-etre Paris', 'atelier', 20, 'debutant', 'Coussin de meditation', 'Aucun'),
('Webinar Nutrition Equilibree', 'Formation sur l'alimentation saine', '2026-05-15 10:00:00', '2026-05-15 11:30:00', 'En ligne', 'webinar', 40, 'intermediaire', 'Ordinateur, connexion internet', 'Aucun'),
('Conference Ergonomie Avancee', 'Conference sur l'ergonomie au travail', '2026-05-20 09:00:00', '2026-05-20 11:00:00', 'Centre de formation Lyon', 'conference', 60, 'avance', 'Aucun', 'Base en ergonomie'),
('Atelier Sommeil Reparateur', 'Techniques pour un meilleur sommeil', '2026-05-25 15:00:00', '2026-05-25 16:30:00', 'Espace bien-etre Bordeaux', 'atelier', 25, 'debutant', 'Tenue confortable', 'Aucun'),
('Seminaire Leadership Feminin', 'Developper son leadership au feminin', '2026-06-01 09:00:00', '2026-06-01 17:00:00', 'Hotel Pullman Paris', 'atelier', 35, 'intermediaire', 'Carnet de notes', 'Experience en management'),
('Webinar Cybersécurité pour tous', 'Apprenez les bases pour vous protéger en ligne', '2026-06-10 14:00:00', '2026-06-10 15:00:00', 'En ligne', 'webinar', 100, 'debutant', 'Ordinateur, connexion internet', 'Aucun'),
('Atelier Gestion du Temps Avancé', 'Techniques pour experts en productivité', '2026-06-12 09:30:00', '2026-06-12 11:30:00', 'Centre d'affaires Paris', 'atelier', 20, 'avance', 'Support de cours avancé', 'Avoir suivi Atelier Gestion du Temps'),
('Conférence Nutrition Sportive', 'Optimiser ses performances par l'alimentation', '2026-06-18 18:00:00', '2026-06-18 19:30:00', 'Gymnase FitPlus Lyon', 'conference', 50, 'intermediaire', 'Aucun', 'Pratique sportive régulière'),
('Défi Marche Quotidienne (Juin)', 'Challenge collectif pour bouger plus', '2026-06-01 00:00:00', '2026-06-30 23:59:59', 'Partout', 'defi_sportif', 200, 'debutant', 'Smartphone / Podomètre', 'Aucun'),
('Atelier Cuisine Saine Express', 'Recettes rapides et saines pour le déjeuner', '2026-06-20 12:00:00', '2026-06-20 13:30:00', 'Cuisine Hub Bordeaux', 'atelier', 15, 'debutant', 'Tablier', 'Aucun'),
('Webinar Préparation Retraite', 'Anticiper et préparer sa retraite sereinement', '2026-06-25 11:00:00', '2026-06-25 12:30:00', 'En ligne', 'webinar', 75, 'intermediaire', 'Ordinateur, connexion internet', 'Aucun'),
('Séance Yoga du Rire', 'Libérer les tensions par le rire', '2026-06-28 17:00:00', '2026-06-28 18:00:00', 'Parc Tête d'Or Lyon', 'autre', 30, 'debutant', 'Tenue confortable', 'Aucun'),
('Conférence Impact du Sommeil', 'Le rôle crucial du sommeil sur la santé et la performance', '2026-07-02 09:00:00', '2026-07-02 10:30:00', 'Amphithéâtre SantePlus Paris', 'conference', 80, 'intermediaire', 'Aucun', 'Aucun'),
('Atelier Communication Assertive', 'S'exprimer clairement et avec respect', '2026-07-05 14:00:00', '2026-07-05 16:00:00', 'Salle de Formation TechSolutions', 'atelier', 25, 'intermediaire', 'Support de cours', 'Aucun'),
('Webinar Méditation Guidée', 'Séance de relaxation profonde en ligne', '2026-07-10 19:00:00', '2026-07-10 19:45:00', 'En ligne', 'webinar', 50, 'debutant', 'Casque audio (optionnel)', 'Aucun');

INSERT INTO communautes (nom, description, type, niveau, capacite_max) VALUES
('Yoga & Meditation', 'Groupe de pratique du yoga et de la meditation', 'bien_etre', 'debutant', 20),
('Running Club', 'Club de course a pied pour tous niveaux', 'sport', 'intermediaire', 30);

INSERT INTO dons (personne_id, montant, type, description, date_don, statut) VALUES
(12, 50.00, 'financier', 'Don pour le programme de bien-etre', '2026-03-01', 'valide'),
(13, NULL, 'materiel', 'Don de materiel informatique (ecran)', '2026-03-15', 'valide'),
(14, 100.00, 'financier', 'Soutien au programme de sante mentale', '2026-03-20', 'valide'),
(15, 75.00, 'financier', 'Don pour les ateliers de bien-etre', '2026-03-25', 'valide'),
(16, NULL, 'materiel', 'Don de mobilier ergonomique (chaise)', '2026-04-01', 'valide'),
(2, 25.00, 'financier', 'Petit don pour Salarie Test', '2026-05-12', 'valide');

INSERT INTO notifications (personne_id, titre, message, type, lien, lu, date_lecture) VALUES
(5, 'Nouveau Rendez-vous', 'Votre rendez-vous a ete confirme', 'success', '/rendez-vous/1', false, NULL),
(5, 'Paiement Reçu', 'Votre paiement a ete reçu avec succes', 'success', '/factures/1', true, '2026-03-17 10:30:00'),
(5, 'Nouvel evenement', 'Un nouvel evenement est disponible', 'info', '/evenements/2', false, NULL),
(5, 'Rappel de rendez-vous', 'Votre rendez-vous est prevu demain', 'warning', '/rendez-vous/3', false, NULL),
(5, 'Nouvelle evaluation', 'Vous avez reçu une nouvelle evaluation', 'info', '/evaluations/5', true, '2026-03-25 14:45:00'),
(6, 'Nouveau Rendez-vous', 'Votre rendez-vous a ete confirme', 'success', '/rendez-vous/6', false, NULL),
(7, 'Paiement Reçu', 'Votre paiement a ete reçu avec succes', 'success', '/factures/2', true, '2026-03-18 11:20:00'),
(8, 'Nouvel evenement', 'Un nouvel evenement est disponible', 'info', '/evenements/3', false, NULL),
(9, 'Rappel de rendez-vous', 'Votre rendez-vous est prevu demain', 'warning', '/rendez-vous/9', false, NULL),
(10, 'Nouvelle evaluation', 'Vous avez reçu une nouvelle evaluation', 'info', '/evaluations/10', true, '2026-03-26 15:30:00'),
(2, 'Bienvenue Salarie Test!', 'Votre compte est prêt à être utilisé.', 'info', '/mon-profil.php', false, NULL),
(2, 'Rappel RDV Meditation', 'Votre séance de méditation est bientôt.', 'warning', '/mon-planning.php', false, NULL),
(2, 'Confirmation RDV Yoga', 'Votre séance de Yoga du 05/06/2026 est confirmée.', 'success', '/mon-planning.php', false, NULL);

INSERT INTO contrats_prestations (contrat_id, prestation_id) VALUES
(1, 1),
(2, 2),
(3, 3);

INSERT INTO services (nom, description, actif, ordre) VALUES
('Starter Pack', 'Pour les petites équipes (jusqu'à 30 salariés)', TRUE, 10),
('Basic Pack', 'Solution équilibrée (jusqu'à 250 salariés)', TRUE, 20),
('Premium Pack', 'Offre complète pour grandes entreprises (251+ salariés)', TRUE, 30),
('Consultation Ponctuelle', 'Besoin spécifique hors contrat', TRUE, 40),
('Événement Sur Mesure', 'Organisation d'un événement spécifique', TRUE, 50);

-- Sample Log Data (Ajout pour Salarie Test) --
TRUNCATE TABLE logs; -- Vider les logs avant d'insérer pour éviter les doublons lors des reseedings
INSERT INTO logs (personne_id, action, details, ip_address, created_at) VALUES
(5, 'login', 'Connexion réussie', '192.168.1.10', NOW() - INTERVAL 5 DAY),
(6, 'rdv_creation', 'Création RDV ID 6 (Yoga)', '192.168.1.11', NOW() - INTERVAL 4 DAY),
(7, 'profile_update', 'Mise à jour du numéro de téléphone', '192.168.1.12', NOW() - INTERVAL 3 DAY),
(1, 'admin_action', 'Désactivation contrat ID 9', '127.0.0.1', NOW() - INTERVAL 2 DAY),
(12, 'evaluation_creation', 'Évaluation prestation ID 1', '192.168.1.15', NOW() - INTERVAL 1 DAY),
-- Logs spécifiques pour Salarie Test (personne_id = 2)
(2, 'login', 'Connexion réussie', '192.168.1.20', NOW() - INTERVAL 1 HOUR),
(2, 'rdv_creation', 'Création RDV Yoga (ID: 18)', '192.168.1.20', NOW() - INTERVAL 30 MINUTE),
(2, 'rdv_creation', 'Création RDV Consultation (ID: 17)', '192.168.1.20', NOW() - INTERVAL 2 HOUR),
(2, 'notification_read', 'Notification ID 11 lue', '192.168.1.20', NOW() - INTERVAL 10 MINUTE),
(5, 'rdv_update', 'Modification RDV ID 19 (Nutrition)', '192.168.1.10', NOW() - INTERVAL 6 DAY),
(6, 'rdv_cancel', 'Annulation RDV ID 20 (Sophro)', '192.168.1.11', NOW() - INTERVAL 5 DAY),
(7, 'password_change', 'Changement de mot de passe', '192.168.1.12', NOW() - INTERVAL 4 DAY),
(8, 'event_register', 'Inscription événement ID 11 (Webinar Cyber)', '192.168.1.13', NOW() - INTERVAL 3 DAY),
(9, 'community_join', 'Rejoint communauté ID 2 (Running Club)', '192.168.1.14', NOW() - INTERVAL 2 DAY),
(10, 'donation_create', 'Création don ID 7 (Financier)', '192.168.1.15', NOW() - INTERVAL 1 DAY),
(2, 'profile_update', 'Mise à jour photo de profil', '192.168.1.20', NOW() - INTERVAL 5 HOUR),
(13, 'login_fail', 'Tentative de connexion échouée', '192.168.1.16', NOW() - INTERVAL 4 HOUR),
(14, 'evaluation_update', 'Modification évaluation ID 4', '192.168.1.17', NOW() - INTERVAL 3 HOUR),
(1, 'user_suspend', 'Suspension utilisateur ID 16', '127.0.0.1', NOW() - INTERVAL 2 HOUR),
(2, 'event_register', 'Inscription événement ID 14 (Défi Marche)', '192.168.1.20', NOW() - INTERVAL 1 HOUR);

-- Added from HEAD branch --
INSERT INTO associations (nom) VALUES
('Restos du Coeur'),
('Fondation Abbé Pierre'),
('Secours Populaire Français');

INSERT INTO `conseils` (`titre`, `icone`, `resume`, `categorie`, `contenu`) VALUES
('Gestion du Stress au Travail', 'fas fa-brain', 'Apprenez des techniques pour mieux gérer la pression et le stress quotidiens en milieu professionnel.', 'Stress', 'Le stress chronique peut avoir des effets négatifs sur votre santé et votre performance. Découvrez des méthodes de relaxation comme la respiration profonde et la méditation de pleine conscience. Apprenez à mieux organiser votre temps avec des listes de tâches et la priorisation. Améliorez votre communication pour exprimer vos besoins et refuser des demandes excessives. N'oubliez pas de faire des pauses régulières.'),
('Améliorer son Sommeil', 'fas fa-moon', 'Des conseils pratiques pour retrouver un sommeil réparateur et améliorer votre énergie.', 'Sommeil', 'Un bon sommeil est crucial. Établissez une routine de coucher régulière, même le week-end. Évitez les écrans (téléphone, tablette, ordinateur) au moins une heure avant de dormir. Créez un environnement sombre, calme et frais dans votre chambre. Limitez la caféine et l'alcool, surtout en fin de journée. Si les problèmes persistent, consultez un professionnel.'),
('Alimentation Équilibrée au Bureau', 'fas fa-apple-alt', 'Comment bien manger au travail pour maintenir votre énergie et votre concentration.', 'Nutrition', 'Manger sainement au bureau est possible ! Préparez vos déjeuners à l'avance pour contrôler les ingrédients et les portions. Privilégiez les légumes, les protéines maigres et les grains entiers. Apportez des collations saines comme des fruits, des noix ou du yaourt. Hydratez-vous en buvant de l'eau tout au long de la journée. Évitez les repas trop lourds qui peuvent entraîner une baisse d'énergie l'après-midi.'),
('L'Importance de l'Activité Physique', 'fas fa-running', 'Intégrer l'exercice dans votre routine, même avec un emploi du temps chargé.', 'Activité Physique', 'L'activité physique est essentielle. Essayez de marcher ou de faire du vélo pour aller au travail si possible. Prenez les escaliers au lieu de l'ascenseur. Faites de courtes pauses actives toutes les heures pour vous étirer ou marcher un peu. Planifiez des séances d'exercice régulières, même courtes, dans votre semaine. Trouvez une activité que vous aimez pour rester motivé (danse, natation, randonnée, etc.).');

INSERT INTO consultation_creneaux (prestation_id, praticien_id, start_time, end_time, is_booked) VALUES
(1, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL '09:00' HOUR_MINUTE, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL '09:45' HOUR_MINUTE, FALSE),
(1, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL '10:00' HOUR_MINUTE, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL '10:45' HOUR_MINUTE, FALSE),
(1, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL '11:00' HOUR_MINUTE, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL '11:45' HOUR_MINUTE, TRUE), -- Booked slot
(1, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL '14:00' HOUR_MINUTE, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL '14:45' HOUR_MINUTE, FALSE),

(4, 17, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL '14:00' HOUR_MINUTE, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL '15:00' HOUR_MINUTE, FALSE),
(4, 17, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL '15:15' HOUR_MINUTE, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL '16:15' HOUR_MINUTE, FALSE),
(4, 17, DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL '09:30' HOUR_MINUTE, DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL '10:30' HOUR_MINUTE, FALSE),
(4, 17, DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL '10:45' HOUR_MINUTE, DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL '11:45' HOUR_MINUTE, FALSE)
ON DUPLICATE KEY UPDATE is_booked = VALUES(is_booked);
