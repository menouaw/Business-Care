-- source C:/MAMP/htdocs/Business-Care/database/seeders/sample_data.sql

USE business_care;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE notifications;
TRUNCATE TABLE evaluations;
TRUNCATE TABLE dons;
TRUNCATE TABLE communautes;
TRUNCATE TABLE evenements;
TRUNCATE TABLE rendez_vous;
TRUNCATE TABLE factures;
TRUNCATE TABLE devis;
TRUNCATE TABLE contrats;
TRUNCATE TABLE personnes;
TRUNCATE TABLE prestations;
TRUNCATE TABLE entreprises; 
TRUNCATE TABLE roles;
TRUNCATE TABLE preferences_utilisateurs;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO roles (nom, description) VALUES
('admin', 'Administrateur systeme'),
('salarie', 'Salarie d''une entreprise'),
('prestataire', 'Prestataire de services'),
('entreprise', 'Entreprise cliente');

INSERT INTO entreprises (nom, siret, adresse, code_postal, ville, telephone, email, site_web, logo_url, taille_entreprise, secteur_activite, date_creation) VALUES
('Tech Solutions SA', '12345678901234', '123 Rue de l''Innovation', '75001', 'Paris', '01 23 45 67 89', 'contact@techsolutions.fr', 'www.techsolutions.fr', '/logos/techsolutions.png', '51-200', 'Technologie', '2020-01-15'),
('Sante Plus', '98765432109876', '456 Avenue de la Sante', '75002', 'Paris', '01 98 76 54 32', 'contact@santeplus.fr', 'www.santeplus.fr', '/logos/santeplus.png', '201-500', 'Sante', '2019-06-20'),
('Bien-etre Corp', '45678901234567', '789 Boulevard du Bien-etre', '75003', 'Paris', '01 45 67 89 01', 'contact@bienetrecorp.fr', 'www.bienetrecorp.fr', '/logos/bienetrecorp.png', '11-50', 'Bien-etre', '2021-03-10'),
('Eco Habitat', '23456789012345', '12 Rue Verte', '69001', 'Lyon', '04 56 78 90 12', 'contact@ecohabitat.fr', 'www.ecohabitat.fr', '/logos/ecohabitat.png', '11-50', 'Construction durable', '2018-09-05'),
('Finance Conseil', '34567890123456', '45 Avenue des Finances', '33000', 'Bordeaux', '05 67 89 01 23', 'info@financeconseil.fr', 'www.financeconseil.fr', '/logos/financeconseil.png', '11-50', 'Finance', '2017-11-12'),
('Innovation Digitale', '56789012345678', '78 Rue du Digital', '75004', 'Paris', '01 34 56 78 90', 'contact@innovationdigitale.fr', 'www.innovationdigitale.fr', '/logos/innovationdigitale.png', '51-200', 'Technologie', '2022-01-10'),
('Sante Pro', '67890123456789', '90 Boulevard de la Sante', '75005', 'Paris', '01 56 78 90 12', 'contact@santepro.fr', 'www.santepro.fr', '/logos/santepro.png', '201-500', 'Sante', '2021-06-15'),
('Bien-etre Plus', '78901234567890', '12 Avenue du Bien-etre', '75006', 'Paris', '01 67 89 01 23', 'contact@bienetreplus.fr', 'www.bienetreplus.fr', '/logos/bienetreplus.png', '11-50', 'Bien-etre', '2022-03-20'),
('Eco Solutions', '89012345678901', '34 Rue de l''Ecologie', '69002', 'Lyon', '04 67 89 01 23', 'contact@ecosolutions.fr', 'www.ecosolutions.fr', '/logos/ecosolutions.png', '11-50', 'Construction durable', '2021-09-25'),
('Finance Plus', '90123456789012', '56 Boulevard des Finances', '33001', 'Bordeaux', '05 78 90 12 34', 'info@financeplus.fr', 'www.financeplus.fr', '/logos/financeplus.png', '11-50', 'Finance', '2022-02-15');

INSERT INTO prestations (nom, description, prix, duree, type, categorie, niveau_difficulte, capacite_max, materiel_necessaire, prerequis) VALUES
('Consultation Psychologique', 'Seance individuelle de 45 minutes avec un psychologue qualifie', 80.00, 45, 'consultation', 'Sante mentale', 'intermediaire', 1, 'Aucun', 'Aucun'),
('Yoga en Entreprise', 'Seance de yoga adaptee au milieu professionnel', 120.00, 60, 'atelier', 'Bien-etre physique', 'debutant', 20, 'Tapis de yoga', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', 150.00, 90, 'webinar', 'Formation', 'debutant', 50, 'Ordinateur, connexion internet', 'Aucun'),
('Conference Bien-etre', 'Conference sur les bonnes pratiques de bien-etre', 200.00, 120, 'conference', 'Formation', 'intermediaire', 100, 'Aucun', 'Aucun'),
('Defi Sportif Mensuel', 'Programme d''activites physiques sur un mois', 180.00, 30, 'evenement', 'Sport', 'avance', 30, 'Tenue de sport', 'Niveau intermediaire'),
('Meditation en Groupe', 'Seance de meditation collective pour reduire le stress', 100.00, 60, 'atelier', 'Bien-etre mental', 'debutant', 15, 'Coussin de meditation', 'Aucun'),
('Coaching Nutritionnel', 'Consultation personnalisee sur l''alimentation saine', 90.00, 45, 'consultation', 'Nutrition', 'intermediaire', 1, 'Aucun', 'Aucun'),
('Atelier Ergonomie', 'Formation sur l''amenagement du poste de travail', 160.00, 90, 'atelier', 'Ergonomie', 'debutant', 25, 'Aucun', 'Aucun'),
('Webinar Sommeil', 'Formation en ligne sur l''amelioration du sommeil', 130.00, 60, 'webinar', 'Bien-etre', 'debutant', 40, 'Ordinateur, connexion internet', 'Aucun'),
('Conference Leadership', 'Conference sur le leadership bienveillant', 250.00, 120, 'conference', 'Management', 'avance', 80, 'Aucun', 'Experience en management');


INSERT INTO personnes (nom, prenom, email, mot_de_passe, telephone, date_naissance, genre, photo_url, role_id, entreprise_id, statut, derniere_connexion) VALUES
('Admin', 'System', 'admin@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '/photos/admin.jpg', 1, NULL, 'actif', '2024-03-17 18:30:00'),
('Salarie', 'Test', 'salarie@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '/photos/salarie.jpg', 2, NULL, 'actif', '2024-03-17 18:30:00'),
('Prestataire', 'Test', 'prestataire@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '/photos/prestataire.jpg', 3, NULL, 'actif', '2024-03-17 18:30:00'),
('Entreprise', 'Test', 'entreprise@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '/photos/entreprise.jpg', 4, NULL, 'actif', '2024-03-17 18:30:00'),
('Dupont', 'Marie', 'marie.dupont@techsolutions.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 12 34 56 78', '1990-05-15', 'F', '/photos/marie.dupont.jpg', 2, 1, 'actif', '2024-03-17 14:30:00'),
('Martin', 'Jean', 'jean.martin@santeplus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 23 45 67 89', '1985-08-20', 'M', '/photos/jean.martin.jpg', 2, 2, 'actif', '2024-03-17 15:45:00'),
('Petit', 'Sophie', 'sophie.petit@bienetrecorp.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 34 56 78 90', '1992-03-10', 'F', '/photos/sophie.petit.jpg', 2, 3, 'actif', '2024-03-17 16:20:00'),
('Dubois', 'Pierre', 'pierre.dubois@ecohabitat.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 45 67 89 01', '1988-12-05', 'M', '/photos/pierre.dubois.jpg', 2, 4, 'actif', '2024-03-17 17:10:00'),
('Bernard', 'Emma', 'emma.bernard@financeconseil.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 56 78 90 12', '1995-07-25', 'F', '/photos/emma.bernard.jpg', 2, 5, 'actif', '2024-03-17 18:00:00'),
('Robert', 'Lucas', 'lucas.robert@innovationdigitale.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 67 89 01 23', '1991-04-15', 'M', '/photos/lucas.robert.jpg', 2, 6, 'actif', '2024-03-17 19:15:00'),
('Richard', 'Julie', 'julie.richard@santepro.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 78 90 12 34', '1987-09-30', 'F', '/photos/julie.richard.jpg', 2, 7, 'actif', '2024-03-17 20:30:00'),
('Petit', 'Thomas', 'thomas.petit@bienetreplus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 89 01 23 45', '1993-02-20', 'M', '/photos/thomas.petit.jpg', 2, 8, 'actif', '2024-03-17 21:45:00'),
('Durand', 'Lea', 'lea.durand@ecosolutions.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 90 12 34 56', '1994-11-10', 'F', '/photos/lea.durand.jpg', 2, 9, 'actif', '2024-03-17 22:15:00'),
('Moreau', 'Hugo', 'hugo.moreau@financeplus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 01 23 45 67', '1996-06-25', 'M', '/photos/hugo.moreau.jpg', 2, 10, 'actif', '2024-03-17 23:00:00');

INSERT INTO contrats (entreprise_id, date_debut, date_fin, montant_mensuel, nombre_salaries, type_contrat, statut, conditions_particulieres) VALUES
(1, '2024-01-01', '2024-12-31', 5000.00, 150, 'premium', 'actif', 'Acces a toutes les prestations premium'),
(2, '2024-02-01', '2024-12-31', 7500.00, 300, 'entreprise', 'actif', 'Acces illimite aux prestations'),
(3, '2024-03-01', '2024-12-31', 2500.00, 35, 'standard', 'en_attente', 'Acces aux prestations de base'),
(4, '2024-01-15', '2024-12-31', 3200.00, 80, 'standard', 'actif', 'Acces aux prestations de base et ateliers'),
(5, '2024-02-10', '2024-12-31', 4500.00, 120, 'premium', 'actif', 'Acces a toutes les prestations avec tarifs preferentiels'),
(6, '2024-01-20', '2024-12-31', 6000.00, 180, 'premium', 'actif', 'Acces a toutes les prestations premium avec support prioritaire'),
(7, '2024-02-15', '2024-12-31', 8500.00, 350, 'entreprise', 'actif', 'Acces illimite aux prestations avec formation personnalisee'),
(8, '2024-03-01', '2024-12-31', 2800.00, 45, 'standard', 'en_attente', 'Acces aux prestations de base et ateliers'),
(9, '2024-01-25', '2024-12-31', 3800.00, 90, 'standard', 'actif', 'Acces aux prestations de base et ateliers'),
(10, '2024-02-20', '2024-12-31', 4200.00, 100, 'premium', 'actif', 'Acces a toutes les prestations avec tarifs preferentiels');

INSERT INTO devis (entreprise_id, date_creation, date_validite, montant_total, montant_ht, tva, statut, conditions_paiement, delai_paiement) VALUES
(1, '2024-01-15', '2024-02-15', 1500.00, 1250.00, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(2, '2024-02-01', '2024-03-01', 2000.00, 1666.67, 20.00, 'en_attente', 'Paiement a 45 jours', 45),
(3, '2024-02-15', '2024-03-15', 1800.00, 1500.00, 20.00, 'refuse', 'Paiement a 30 jours', 30),
(4, '2024-01-10', '2024-02-10', 3200.00, 2666.67, 20.00, 'accepte', 'Paiement a 15 jours', 15),
(5, '2024-02-20', '2024-03-20', 2500.00, 2083.33, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(6, '2024-01-25', '2024-02-25', 2800.00, 2333.33, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(7, '2024-02-10', '2024-03-10', 3500.00, 2916.67, 20.00, 'en_attente', 'Paiement a 45 jours', 45),
(8, '2024-02-20', '2024-03-20', 2200.00, 1833.33, 20.00, 'refuse', 'Paiement a 30 jours', 30),
(9, '2024-01-30', '2024-02-28', 2600.00, 2166.67, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(10, '2024-02-25', '2024-03-25', 2400.00, 2000.00, 20.00, 'en_attente', 'Paiement a 30 jours', 30);

INSERT INTO factures (entreprise_id, devis_id, numero_facture, date_emission, date_echeance, montant_total, montant_ht, tva, statut, mode_paiement) VALUES
(1, 1, 'FACT-2024-001', '2024-01-20', '2024-02-20', 1500.00, 1250.00, 20.00, 'payee', 'virement'),
(2, 2, 'FACT-2024-002', '2024-02-05', '2024-03-05', 2000.00, 1666.67, 20.00, 'en_attente', 'virement'),
(4, 4, 'FACT-2024-003', '2024-02-20', '2024-03-20', 3200.00, 2666.67, 20.00, 'payee', 'carte'),
(5, 5, 'FACT-2024-004', '2024-03-01', '2024-04-01', 2500.00, 2083.33, 20.00, 'en_attente', 'virement'),
(6, 6, 'FACT-2024-005', '2024-02-01', '2024-03-01', 2800.00, 2333.33, 20.00, 'payee', 'virement'),
(7, 7, 'FACT-2024-006', '2024-02-15', '2024-03-15', 3500.00, 2916.67, 20.00, 'en_attente', 'virement'),
(9, 9, 'FACT-2024-007', '2024-02-05', '2024-03-05', 2600.00, 2166.67, 20.00, 'payee', 'carte'),
(10, 10, 'FACT-2024-008', '2024-03-05', '2024-04-05', 2400.00, 2000.00, 20.00, 'en_attente', 'virement');

INSERT INTO rendez_vous (personne_id, prestation_id, date_rdv, duree, lieu, type_rdv, statut, notes) VALUES
(5, 1, '2024-03-20 10:00:00', 45, 'Cabinet 101', 'presentiel', 'planifie', 'Premiere consultation'),
(5, 2, '2024-03-21 14:00:00', 60, 'Salle de yoga', 'presentiel', 'confirme', 'Seance de groupe'),
(5, 3, '2024-03-22 15:00:00', 90, 'En ligne', 'visio', 'planifie', 'Webinar en groupe'),
(5, 4, '2024-03-23 09:30:00', 60, 'Bureau 202', 'presentiel', 'confirme', 'Suivi mensuel'),
(5, 5, '2024-03-24 11:00:00', 45, 'Salle de reunion', 'presentiel', 'planifie', 'Bilan trimestriel'),
(6, 6, '2024-03-25 10:30:00', 60, 'Salle de meditation', 'presentiel', 'confirme', 'Seance de groupe'),
(7, 7, '2024-03-26 14:00:00', 45, 'Cabinet 303', 'presentiel', 'planifie', 'Premiere consultation'),
(8, 8, '2024-03-27 09:00:00', 90, 'Salle de formation', 'presentiel', 'confirme', 'Formation groupe'),
(9, 9, '2024-03-28 15:00:00', 60, 'En ligne', 'visio', 'planifie', 'Webinar individuel'),
(10, 10, '2024-03-29 11:30:00', 120, 'Salle de conference', 'presentiel', 'confirme', 'Conference annuelle');

INSERT INTO evenements (titre, description, date_debut, date_fin, lieu, type, capacite_max, niveau_difficulte, materiel_necessaire, prerequis) VALUES
('Conference sur le Bien-etre au Travail', 'Une conference interactive sur les bonnes pratiques', '2024-04-01 09:00:00', '2024-04-01 11:00:00', 'Salle de Conference Paris', 'conference', 100, 'intermediaire', 'Aucun', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', '2024-04-15 14:00:00', '2024-04-15 15:30:00', 'En ligne', 'webinar', 50, 'debutant', 'Ordinateur, connexion internet', 'Aucun'),
('Atelier Ergonomie au Bureau', 'Apprenez a amenager votre espace de travail', '2024-04-20 10:00:00', '2024-04-20 12:00:00', 'Centre d''affaires Lyon', 'atelier', 30, 'debutant', 'Aucun', 'Aucun'),
('Seminaire Leadership Bienveillant', 'Developper un leadership positif et efficace', '2024-05-05 09:00:00', '2024-05-06 17:00:00', 'Hotel Mercure Bordeaux', 'atelier', 40, 'avance', 'Carnet de notes', 'Experience en management'),
('Journee Detox Digitale', 'Une journee pour apprendre a se deconnecter', '2024-05-10 09:00:00', '2024-05-10 17:00:00', 'Espace Zen Marseille', 'autre', 25, 'debutant', 'Tenue confortable', 'Aucun'),
('Atelier Meditation en Groupe', 'Seance de meditation collective', '2024-04-25 14:00:00', '2024-04-25 15:30:00', 'Centre de bien-etre Paris', 'atelier', 20, 'debutant', 'Coussin de meditation', 'Aucun'),
('Webinar Nutrition Equilibree', 'Formation sur l''alimentation saine', '2024-05-15 10:00:00', '2024-05-15 11:30:00', 'En ligne', 'webinar', 40, 'intermediaire', 'Ordinateur, connexion internet', 'Aucun'),
('Conference Ergonomie Avancee', 'Conference sur l''ergonomie au travail', '2024-05-20 09:00:00', '2024-05-20 11:00:00', 'Centre de formation Lyon', 'conference', 60, 'avance', 'Aucun', 'Base en ergonomie'),
('Atelier Sommeil Reparateur', 'Techniques pour un meilleur sommeil', '2024-05-25 15:00:00', '2024-05-25 16:30:00', 'Espace bien-etre Bordeaux', 'atelier', 25, 'debutant', 'Tenue confortable', 'Aucun'),
('Seminaire Leadership Feminin', 'Developper son leadership au feminin', '2024-06-01 09:00:00', '2024-06-01 17:00:00', 'Hotel Pullman Paris', 'atelier', 35, 'intermediaire', 'Carnet de notes', 'Experience en management');

INSERT INTO communautes (nom, description, type, niveau, capacite_max) VALUES
('Yoga & Meditation', 'Groupe de pratique du yoga et de la meditation', 'bien_etre', 'debutant', 20),
('Running Club', 'Club de course a pied pour tous niveaux', 'sport', 'intermediaire', 30),
('Nutrition & Sante', 'Groupe d''echange sur la nutrition et la sante', 'sante', 'intermediaire', 25),
('Mindfulness au Travail', 'Groupe de pratique de la pleine conscience en milieu professionnel', 'bien_etre', 'debutant', 30),
('Club de Lecture Sante', 'Discussions autour de livres sur la sante et le bien-etre', 'autre', 'intermediaire', 15),
('Pilates & Stretching', 'Groupe de pratique du pilates et du stretching', 'bien_etre', 'debutant', 20),
('Cycling Club', 'Club de cyclisme en salle', 'sport', 'intermediaire', 25),
('Nutrition Sportive', 'Groupe d''echange sur la nutrition sportive', 'sante', 'avance', 20),
('Meditation Avancee', 'Groupe de meditation pour pratiquants confirmes', 'bien_etre', 'avance', 15),
('Bien-etre au Travail', 'Echanges sur le bien-etre en milieu professionnel', 'autre', 'intermediaire', 30);

INSERT INTO dons (personne_id, montant, type, description, date_don, statut) VALUES
(5, 50.00, 'financier', 'Don pour le programme de bien-etre', '2024-03-01', 'valide'),
(5, NULL, 'materiel', 'Don de materiel informatique', '2024-03-15', 'en_attente'),
(5, 100.00, 'financier', 'Soutien au programme de sante mentale', '2024-03-20', 'valide'),
(5, 75.00, 'financier', 'Don pour les ateliers de bien-etre', '2024-03-25', 'valide'),
(5, NULL, 'materiel', 'Don de mobilier ergonomique', '2024-04-01', 'valide'),
(6, 150.00, 'financier', 'Don pour le programme sportif', '2024-03-05', 'valide'),
(7, NULL, 'materiel', 'Don de materiel de sport', '2024-03-10', 'en_attente'),
(8, 200.00, 'financier', 'Soutien aux ateliers de bien-etre', '2024-03-15', 'valide'),
(9, 80.00, 'financier', 'Don pour les conferences', '2024-03-20', 'valide'),
(10, NULL, 'materiel', 'Don de materiel de bureau', '2024-03-25', 'valide');

INSERT INTO evaluations (personne_id, prestation_id, note, commentaire, date_evaluation) VALUES
(5, 1, 5, 'Excellent service, tres professionnel', '2024-03-10'),
(5, 2, 4, 'Tres bonne seance, a recommander', '2024-03-12'),
(5, 3, 5, 'Formation tres enrichissante', '2024-03-15'),
(5, 4, 4, 'Atelier tres instructif et bien organise', '2024-03-18'),
(5, 5, 5, 'Excellente consultation, tres a l''ecoute', '2024-03-20'),
(6, 6, 4, 'Seance de meditation tres relaxante', '2024-03-11'),
(7, 7, 5, 'Consultation nutritionnelle tres utile', '2024-03-13'),
(8, 8, 4, 'Formation ergonomie tres pratique', '2024-03-16'),
(9, 9, 5, 'Webinar tres interessant', '2024-03-19'),
(10, 10, 4, 'Conference tres enrichissante', '2024-03-21');

INSERT INTO notifications (personne_id, titre, message, type, lien, lu, date_lecture) VALUES
(5, 'Nouveau Rendez-vous', 'Votre rendez-vous a ete confirme', 'success', '/rendez-vous/1', false, NULL),
(5, 'Paiement Reçu', 'Votre paiement a ete reçu avec succes', 'success', '/factures/1', true, '2024-03-17 10:30:00'),
(5, 'Nouvel evenement', 'Un nouvel evenement est disponible', 'info', '/evenements/2', false, NULL),
(5, 'Rappel de rendez-vous', 'Votre rendez-vous est prevu demain', 'warning', '/rendez-vous/3', false, NULL),
(5, 'Nouvelle evaluation', 'Vous avez reçu une nouvelle evaluation', 'info', '/evaluations/5', true, '2024-03-25 14:45:00'),
(6, 'Nouveau Rendez-vous', 'Votre rendez-vous a ete confirme', 'success', '/rendez-vous/6', false, NULL),
(7, 'Paiement Reçu', 'Votre paiement a ete reçu avec succes', 'success', '/factures/2', true, '2024-03-18 11:20:00'),
(8, 'Nouvel evenement', 'Un nouvel evenement est disponible', 'info', '/evenements/3', false, NULL),
(9, 'Rappel de rendez-vous', 'Votre rendez-vous est prevu demain', 'warning', '/rendez-vous/9', false, NULL),
(10, 'Nouvelle evaluation', 'Vous avez reçu une nouvelle evaluation', 'info', '/evaluations/10', true, '2024-03-26 15:30:00');

INSERT INTO contrats_prestations (contrat_id, prestation_id) VALUES
(11, 10); -- Contrat 1 -> Conférence leadership