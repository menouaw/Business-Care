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
('Finance Conseil', '34567890123456', '45 Avenue des Finances', '33000', 'Bordeaux', '05 67 89 01 23', 'info@financeconseil.fr', 'www.financeconseil.fr', '/logos/financeconseil.png', '11-50', 'Finance', '2017-11-12');

INSERT INTO prestations (nom, description, prix, duree, type, categorie, niveau_difficulte, capacite_max, materiel_necessaire, prerequis) VALUES
('Consultation Psychologique', 'Seance individuelle de 45 minutes avec un psychologue qualifie', 80.00, 45, 'consultation', 'Sante mentale', 'intermediaire', 1, 'Aucun', 'Aucun'),
('Yoga en Entreprise', 'Seance de yoga adaptee au milieu professionnel', 120.00, 60, 'atelier', 'Bien-etre physique', 'debutant', 20, 'Tapis de yoga', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', 150.00, 90, 'webinar', 'Formation', 'debutant', 50, 'Ordinateur, connexion internet', 'Aucun'),
('Conference Bien-etre', 'Conference sur les bonnes pratiques de bien-etre', 200.00, 120, 'conference', 'Formation', 'intermediaire', 100, 'Aucun', 'Aucun'),
('Defi Sportif Mensuel', 'Programme d''activites physiques sur un mois', 180.00, 30, 'evenement', 'Sport', 'avance', 30, 'Tenue de sport', 'Niveau intermediaire');

INSERT INTO personnes (nom, prenom, email, mot_de_passe, telephone, date_naissance, genre, photo_url, role_id, entreprise_id, statut, derniere_connexion) VALUES
('Admin', 'System', 'admin@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '/photos/admin.jpg', 1, NULL, 'actif', '2024-03-17 18:30:00'),
('Salarie', 'Test', 'salarie@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '/photos/salarie.jpg', 2, NULL, 'actif', '2024-03-17 18:30:00'),
('Prestataire', 'Test', 'prestataire@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '/photos/prestataire.jpg', 3, NULL, 'actif', '2024-03-17 18:30:00'),
('Entreprise', 'Test', 'entreprise@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '/photos/entreprise.jpg', 4, NULL, 'actif', '2024-03-17 18:30:00'),
('Dupont', 'Marie', 'marie.dupont@techsolutions.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 12 34 56 78', '1990-05-15', 'F', '/photos/marie.dupont.jpg', 2, 1, 'actif', '2024-03-17 14:30:00');

INSERT INTO contrats (entreprise_id, date_debut, date_fin, montant_mensuel, nombre_salaries, type_contrat, statut, conditions_particulieres) VALUES
(1, '2024-01-01', '2024-12-31', 5000.00, 150, 'premium', 'actif', 'Acces a toutes les prestations premium'),
(2, '2024-02-01', '2024-12-31', 7500.00, 300, 'entreprise', 'actif', 'Acces illimite aux prestations'),
(3, '2024-03-01', '2024-12-31', 2500.00, 35, 'standard', 'en_attente', 'Acces aux prestations de base'),
(4, '2024-01-15', '2024-12-31', 3200.00, 80, 'standard', 'actif', 'Acces aux prestations de base et ateliers'),
(5, '2024-02-10', '2024-12-31', 4500.00, 120, 'premium', 'actif', 'Acces a toutes les prestations avec tarifs preferentiels');

INSERT INTO devis (entreprise_id, date_creation, date_validite, montant_total, montant_ht, tva, statut, conditions_paiement, delai_paiement) VALUES
(1, '2024-01-15', '2024-02-15', 1500.00, 1250.00, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(2, '2024-02-01', '2024-03-01', 2000.00, 1666.67, 20.00, 'en_attente', 'Paiement a 45 jours', 45),
(3, '2024-02-15', '2024-03-15', 1800.00, 1500.00, 20.00, 'refuse', 'Paiement a 30 jours', 30),
(4, '2024-01-10', '2024-02-10', 3200.00, 2666.67, 20.00, 'accepte', 'Paiement a 15 jours', 15),
(5, '2024-02-20', '2024-03-20', 2500.00, 2083.33, 20.00, 'en_attente', 'Paiement a 30 jours', 30);

INSERT INTO factures (entreprise_id, devis_id, numero_facture, date_emission, date_echeance, montant_total, montant_ht, tva, statut, mode_paiement) VALUES
(1, 1, 'FACT-2024-001', '2024-01-20', '2024-02-20', 1500.00, 1250.00, 20.00, 'payee', 'virement'),
(2, 2, 'FACT-2024-002', '2024-02-05', '2024-03-05', 2000.00, 1666.67, 20.00, 'en_attente', 'virement'),
(4, 4, 'FACT-2024-003', '2024-02-20', '2024-03-20', 3200.00, 2666.67, 20.00, 'payee', 'carte'),
(5, 5, 'FACT-2024-004', '2024-03-01', '2024-04-01', 2500.00, 2083.33, 20.00, 'en_attente', 'virement');

INSERT INTO rendez_vous (personne_id, prestation_id, date_rdv, duree, lieu, type_rdv, statut, notes) VALUES
(5, 1, '2024-03-20 10:00:00', 45, 'Cabinet 101', 'presentiel', 'planifie', 'Premiere consultation'),
(5, 2, '2024-03-21 14:00:00', 60, 'Salle de yoga', 'presentiel', 'confirme', 'Seance de groupe'),
(5, 3, '2024-03-22 15:00:00', 90, 'En ligne', 'visio', 'planifie', 'Webinar en groupe'),
(5, 4, '2024-03-23 09:30:00', 60, 'Bureau 202', 'presentiel', 'confirme', 'Suivi mensuel'),
(5, 5, '2024-03-24 11:00:00', 45, 'Salle de reunion', 'presentiel', 'planifie', 'Bilan trimestriel');

INSERT INTO evenements (titre, description, date_debut, date_fin, lieu, type, capacite_max, niveau_difficulte, materiel_necessaire, prerequis) VALUES
('Conference sur le Bien-etre au Travail', 'Une conference interactive sur les bonnes pratiques', '2024-04-01 09:00:00', '2024-04-01 11:00:00', 'Salle de Conference Paris', 'conference', 100, 'intermediaire', 'Aucun', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', '2024-04-15 14:00:00', '2024-04-15 15:30:00', 'En ligne', 'webinar', 50, 'debutant', 'Ordinateur, connexion internet', 'Aucun'),
('Atelier Ergonomie au Bureau', 'Apprenez a amenager votre espace de travail', '2024-04-20 10:00:00', '2024-04-20 12:00:00', 'Centre d''affaires Lyon', 'atelier', 30, 'debutant', 'Aucun', 'Aucun'),
('Seminaire Leadership Bienveillant', 'Developper un leadership positif et efficace', '2024-05-05 09:00:00', '2024-05-06 17:00:00', 'Hotel Mercure Bordeaux', 'atelier', 40, 'avance', 'Carnet de notes', 'Experience en management'),
('Journee Detox Digitale', 'Une journee pour apprendre a se deconnecter', '2024-05-10 09:00:00', '2024-05-10 17:00:00', 'Espace Zen Marseille', 'autre', 25, 'debutant', 'Tenue confortable', 'Aucun');

INSERT INTO communautes (nom, description, type, niveau, capacite_max) VALUES
('Yoga & Meditation', 'Groupe de pratique du yoga et de la meditation', 'bien_etre', 'debutant', 20),
('Running Club', 'Club de course a pied pour tous niveaux', 'sport', 'intermediaire', 30),
('Nutrition & Sante', 'Groupe d''echange sur la nutrition et la sante', 'sante', 'intermediaire', 25),
('Mindfulness au Travail', 'Groupe de pratique de la pleine conscience en milieu professionnel', 'bien_etre', 'debutant', 30),
('Club de Lecture Sante', 'Discussions autour de livres sur la sante et le bien-etre', 'autre', 'intermediaire', 15);

INSERT INTO dons (personne_id, montant, type, description, date_don, statut) VALUES
(5, 50.00, 'financier', 'Don pour le programme de bien-etre', '2024-03-01', 'valide'),
(5, NULL, 'materiel', 'Don de materiel informatique', '2024-03-15', 'en_attente'),
(5, 100.00, 'financier', 'Soutien au programme de sante mentale', '2024-03-20', 'valide'),
(5, 75.00, 'financier', 'Don pour les ateliers de bien-etre', '2024-03-25', 'valide'),
(5, NULL, 'materiel', 'Don de mobilier ergonomique', '2024-04-01', 'valide');

INSERT INTO evaluations (personne_id, prestation_id, note, commentaire, date_evaluation) VALUES
(5, 1, 5, 'Excellent service, tres professionnel', '2024-03-10'),
(5, 2, 4, 'Tres bonne seance, a recommander', '2024-03-12'),
(5, 3, 5, 'Formation tres enrichissante', '2024-03-15'),
(5, 4, 4, 'Atelier tres instructif et bien organise', '2024-03-18'),
(5, 5, 5, 'Excellente consultation, tres a l''ecoute', '2024-03-20');

INSERT INTO notifications (personne_id, titre, message, type, lien, lu, date_lecture) VALUES
(5, 'Nouveau Rendez-vous', 'Votre rendez-vous a ete confirme', 'success', '/rendez-vous/1', false, NULL),
(5, 'Paiement Reçu', 'Votre paiement a ete reçu avec succes', 'success', '/factures/1', true, '2024-03-17 10:30:00'),
(5, 'Nouvel evenement', 'Un nouvel evenement est disponible', 'info', '/evenements/2', false, NULL),
(5, 'Rappel de rendez-vous', 'Votre rendez-vous est prevu demain', 'warning', '/rendez-vous/3', false, NULL),
(5, 'Nouvelle evaluation', 'Vous avez reçu une nouvelle evaluation', 'info', '/evaluations/5', true, '2024-03-25 14:45:00');