USE business_care;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE facture_prestataire_lignes;
TRUNCATE TABLE factures_prestataires;
TRUNCATE TABLE habilitations;
TRUNCATE TABLE prestataires_prestations;
TRUNCATE TABLE prestataires_disponibilites;
TRUNCATE TABLE evenement_inscriptions;
TRUNCATE TABLE communautes;
TRUNCATE TABLE communaute_membres;
TRUNCATE TABLE communaute_messages;
TRUNCATE TABLE support_tickets;
TRUNCATE TABLE signalements;
TRUNCATE TABLE remember_me_tokens;
TRUNCATE TABLE api_tokens;
TRUNCATE TABLE devis_prestations;
TRUNCATE TABLE consultation_creneaux;
TRUNCATE TABLE evaluations;
TRUNCATE TABLE dons;
TRUNCATE TABLE rendez_vous;
TRUNCATE TABLE factures;
TRUNCATE TABLE devis;
TRUNCATE TABLE contrats_prestations;
TRUNCATE TABLE contrats;
TRUNCATE TABLE preferences_utilisateurs;
TRUNCATE TABLE logs;
TRUNCATE TABLE notifications;
TRUNCATE TABLE personnes;
TRUNCATE TABLE sites;
TRUNCATE TABLE entreprises;
TRUNCATE TABLE prestations;
TRUNCATE TABLE evenements;
TRUNCATE TABLE conseils;
TRUNCATE TABLE associations;
TRUNCATE TABLE services;
TRUNCATE TABLE roles;
TRUNCATE TABLE interets_utilisateurs;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO roles (nom, description) VALUES
('admin', 'Administrateur systeme'),
('salarie', 'Salarie d''une entreprise'),
('prestataire', 'Prestataire de services / Praticien'),
('entreprise', 'Representant Entreprise cliente');

INSERT INTO entreprises (id, nom, siret, adresse, code_postal, ville, telephone, email, site_web, logo_url, taille_entreprise, secteur_activite, date_creation) VALUES
(1, 'Tech Solutions SA', '12345678901234', '123 Rue de l''Innovation', '75001', 'Paris', '01 23 45 67 89', 'contact@techsolutions.fr', 'www.techsolutions.fr', '/logos/techsolutions.png', '51-200', 'Technologie', '2020-01-15'),
(2, 'Sante Plus', '98765432109876', '456 Avenue de la Sante', '75002', 'Paris', '01 98 76 54 32', 'contact@santeplus.fr', 'www.santeplus.fr', '/logos/santeplus.png', '201-500', 'Sante', '2019-06-20'),
(3, 'Bien-etre Corp', '45678901234567', '789 Boulevard du Bien-etre', '75003', 'Paris', '01 45 67 89 01', 'contact@bienetrecorp.fr', 'www.bienetrecorp.fr', '/logos/bienetrecorp.png', '11-50', 'Bien-etre', '2021-03-10'),
(4, 'Finance Conseil', '11223344556677', '1 Place de la Bourse', '75002', 'Paris', '01 11 22 33 44', 'contact@financeconseil.fr', 'www.financeconseil.fr', '/logos/financeconseil.png', '1-10', 'Finance', '2022-05-01'),
(5, 'Retail Express', '22334455667788', '50 Avenue des Champs-elysees', '75008', 'Paris', '01 22 33 44 55', 'info@retailexpress.com', 'www.retailexpress.com', '/logos/retailexpress.png', '500+', 'Retail', '2018-11-11'),
(6, 'EduForma', '33445566778899', '10 Rue des ecoles', '69007', 'Lyon', '04 12 34 56 78', 'contact@eduforma.org', 'www.eduforma.org', '/logos/eduforma.png', '51-200', 'Education', '2017-09-01'),
(7, 'BuildInnov', '44556677889900', 'Zone Industrielle Sud', '31000', 'Toulouse', '05 98 76 54 32', 'info@buildinnov.fr', 'www.buildinnov.fr', '/logos/buildinnov.png', '201-500', 'Construction', '2015-02-28'),
(8, 'GreenTech Energy', '55667788990011', '25 Rue de l''Avenir', '69002', 'Lyon', '04 11 22 33 44', 'contact@greentechenergy.com', 'www.greentechenergy.com', '/logos/greentech.png', '51-200', 'Energie', '2019-07-15'),
(9, 'AgriBio Solutions', '66778899001122', 'Domaine de la Ferme', '34000', 'Montpellier', '04 22 33 44 55', 'info@agribiosolutions.fr', 'www.agribiosolutions.fr', '/logos/agribio.png', '11-50', 'Agriculture', '2020-03-20'),
(10, 'TransportRapide SARL', '77889900112233', '15 Boulevard Logistique', '13015', 'Marseille', '04 33 44 55 66', 'contact@transportrapide.fr', 'www.transportrapide.fr', '/logos/transportrapide.png', '201-500', 'Transport', '2016-10-05'),
(11, 'GastroDelice Catering', '88990011223344', '9 Rue Gourmande', '75011', 'Paris', '01 44 55 66 77', 'info@gastrodelice.com', 'www.gastrodelice.com', '/logos/gastrodelice.png', '1-10', 'Retail', '2021-11-30'),
(12, 'MediaPulse Agency', '99001122334455', '30 Avenue de la Creation', '75017', 'Paris', '01 55 66 77 88', 'hello@mediapulse.agency', 'www.mediapulse.agency', '/logos/mediapulse.png', '11-50', 'Marketing', '2018-04-12'),
(13, 'CodeCraft Software', '00112233445566', 'Silicon Alley 5', '75009', 'Paris', '01 66 77 88 99', 'dev@codecraft.io', 'www.codecraft.io', '/logos/codecraft.png', '51-200', 'Technologie', '2019-01-01'),
(14, 'PharmaSecure Labs', '11224344556677', 'Parc Scientifique Lavoisier', '69008', 'Lyon', '04 77 88 99 00', 'contact@pharmasecure.com', 'www.pharmasecure.com', '/logos/pharmasecure.png', '500+', 'Pharmaceutique', '2014-08-19'),
(15, 'AutoMotive Solutions', '22334455667789', 'Pole Automobile Nord', '59000', 'Lille', '03 88 99 00 11', 'info@automotive-solutions.fr', 'www.automotive-solutions.fr', '/logos/automotive.png', '201-500', 'Automobile', '2017-06-22'),
(16, 'EcoHabitat Construction', '33445566778890', '12 Impasse Verte', '44000', 'Nantes', '02 99 00 11 22', 'contact@ecohabitat.build', 'www.ecohabitat.build', '/logos/ecohabitat.png', '11-50', 'Construction', '2020-09-14'),
(17, 'Voyages Evasion', '44556677889901', '7 Rue des Explorateurs', '33000', 'Bordeaux', '05 00 11 22 33', 'info@voyagesevasion.com', 'www.voyagesevasion.com', '/logos/voyagesevasion.png', '1-10', 'Evenementiel', '2022-01-05'),
(18, 'InnovHealth Devices', '55667788990012', 'Campus Medical Sud', '31400', 'Toulouse', '05 11 22 33 44', 'sales@innovhealth.dev', 'www.innovhealth.dev', '/logos/innovhealth.png', '51-200', 'Sante', '2018-12-01'),
(19, 'FinTech Global', '66778899001123', 'La Defense Plaza', '92800', 'Puteaux', '01 77 88 99 00', 'contact@fintechglobal.com', 'www.fintechglobal.com', '/logos/fintechglobal.png', '201-500', 'Finance', '2017-03-10'),
(20, 'ConsultPro Advisory', '77889900112234', '8 Avenue des Consultants', '75008', 'Paris', '01 88 99 00 11', 'partner@consultpro.com', 'www.consultpro.com', '/logos/consultpro.png', '11-50', 'Finance', '2021-02-15'),
(21, 'Artisan Bois Creation', '88990011223345', 'Atelier du Savoir Faire', '67000', 'Strasbourg', '03 99 00 11 22', 'contact@artisanbois.fr', 'www.artisanbois.fr', '/logos/artisanbois.png', '1-10', 'Construction', '2019-05-20'),
(22, 'LogiStream Solutions', '99001122334456', 'Plateforme Logistique Est', '54000', 'Nancy', '03 00 11 22 33', 'info@logistream.eu', 'www.logistream.eu', '/logos/logistream.png', '500+', 'Transport', '2015-11-01'),
(23, 'DigitalWave Marketing', '00112233445567', '18 Rue de la Pub', '75002', 'Paris', '01 11 33 55 77', 'contact@digitalwave.mkt', 'www.digitalwave.mkt', '/logos/digitalwave.png', '11-50', 'Marketing', '2020-07-07'),
(24, 'CleanEnergy Solutions', '11223344556678', 'Parc Eolien Ouest', '29200', 'Brest', '02 22 44 66 88', 'solutions@cleanenergy.fr', 'www.cleanenergy.fr', '/logos/cleanenergy.png', '51-200', 'Energie', '2018-06-18'),
(25, 'SecureNet Systems', '22334455667790', 'Cyber Hub Center', '75015', 'Paris', '01 33 55 77 99', 'info@securenet.sys', 'www.securenet.sys', '/logos/securenet.png', '201-500', 'Technologie', '2016-04-25'),
(26, 'GourmetBio Market', '33445566778891', 'Halle Bio Locale', '35000', 'Rennes', '02 44 66 88 00', 'contact@gourmetbio.fr', 'www.gourmetbio.fr', '/logos/gourmetbio.png', '1-10', 'Retail', '2021-09-10'),
(27, 'LegalEase Partners', '44556677889902', '20 Place Vendome', '75001', 'Paris', '01 55 77 99 11', 'contact@legalease.law', 'www.legalease.law', '/logos/legalease.png', '11-50', 'Conseil', '2019-10-30'),
(28, 'EventPro Organisers', '55667788990013', '100 Boulevard Haussmann', '75008', 'Paris', '01 66 88 00 22', 'planner@eventpro.org', 'www.eventpro.org', '/logos/eventpro.png', '1-10', 'Evenementiel', '2022-03-01'),
(29, 'ImmoPrestige Agency', '66778899001124', '5 Avenue Montaigne', '75008', 'Paris', '01 77 99 11 33', 'info@immoprestige.com', 'www.immoprestige.com', '/logos/immoprestige.png', '11-50', 'Finance', '2017-12-12'),
(30, 'HumanRessources Plus', '77889900112235', 'Tour Oxygene', '69003', 'Lyon', '04 88 00 22 44', 'contact@hrplus.fr', 'www.hrplus.fr', '/logos/hrplus.png', '51-200', 'Conseil', '2018-01-20');

INSERT INTO sites (id, nom, adresse, code_postal, ville, entreprise_id) VALUES
(1, 'Siege Social Tech Solutions', '123 Rue de l''Innovation', '75001', 'Paris', 1),
(2, 'Agence Sante Plus Centre', '456 Avenue de la Sante', '75002', 'Paris', 2),
(3, 'Bureau Bien-etre Corp', '789 Boulevard du Bien-etre', '75003', 'Paris', 3),
(4, 'Agence Troyes Bien-etre Corp', '1 Rue de la Republique', '10000', 'Troyes', 3),
(5, 'Finance Conseil Paris Bourse', '1 Place de la Bourse', '75002', 'Paris', 4),
(6, 'Retail Express Champs-Elysees', '50 Avenue des Champs-elysees', '75008', 'Paris', 5),
(7, 'Retail Express Lyon Centre', '2 Place Bellecour', '69002', 'Lyon', 5),
(8, 'Retail Express Marseille Vieux-Port', '1 Quai des Belges', '13001', 'Marseille', 5),
(9, 'EduForma Lyon Campus', '10 Rue des ecoles', '69007', 'Lyon', 6),
(10, 'EduForma Paris Antenne', '5 Boulevard Saint-Michel', '75005', 'Paris', 6),
(11, 'BuildInnov Siege Toulouse', 'Zone Industrielle Sud', '31000', 'Toulouse', 7),
(12, 'GreenTech Energy Lyon HQ', '25 Rue de l''Avenir', '69002', 'Lyon', 8),
(13, 'AgriBio Solutions Montpellier', 'Domaine de la Ferme', '34000', 'Montpellier', 9),
(14, 'TransportRapide Hub Marseille', '15 Boulevard Logistique', '13015', 'Marseille', 10),
(15, 'TransportRapide Plateforme Lyon', 'Zone Cargo Aeroport St Exupery', '69125', 'Colombier-Saugnieu', 10),
(16, 'GastroDelice Paris Atelier', '9 Rue Gourmande', '75011', 'Paris', 11),
(17, 'MediaPulse Paris Office', '30 Avenue de la Creation', '75017', 'Paris', 12),
(18, 'CodeCraft Software Paris HQ', 'Silicon Alley 5', '75009', 'Paris', 13),
(19, 'PharmaSecure Labs Lyon', 'Parc Scientifique Lavoisier', '69008', 'Lyon', 14),
(20, 'AutoMotive Solutions Lille', 'Pole Automobile Nord', '59000', 'Lille', 15),
(21, 'EcoHabitat Construction Nantes', '12 Impasse Verte', '44000', 'Nantes', 16),
(22, 'Voyages Evasion Bordeaux', '7 Rue des Explorateurs', '33000', 'Bordeaux', 17),
(23, 'InnovHealth Devices Toulouse', 'Campus Medical Sud', '31400', 'Toulouse', 18),
(24, 'FinTech Global La Defense', 'La Defense Plaza', '92800', 'Puteaux', 19),
(25, 'ConsultPro Advisory Paris', '8 Avenue des Consultants', '75008', 'Paris', 20),
(26, 'Artisan Bois Creation Strasbourg', 'Atelier du Savoir Faire', '67000', 'Strasbourg', 21),
(27, 'LogiStream Solutions Nancy', 'Plateforme Logistique Est', '54000', 'Nancy', 22),
(28, 'DigitalWave Marketing Paris', '18 Rue de la Pub', '75002', 'Paris', 23),
(29, 'CleanEnergy Solutions Brest', 'Parc Eolien Ouest', '29200', 'Brest', 24),
(30, 'SecureNet Systems Paris', 'Cyber Hub Center', '75015', 'Paris', 25);


INSERT INTO prestations (nom, description, prix, duree, type, categorie, niveau_difficulte, capacite_max, materiel_necessaire, prerequis) VALUES
('Consultation Psychologique', 'Seance individuelle de 45 minutes avec un psychologue qualifie', 80.00, 45, 'consultation', 'Sante mentale', NULL, 1, 'Aucun', 'Aucun'),
('Yoga en Entreprise', 'Seance de yoga adaptee au milieu professionnel', 120.00, 60, 'atelier', 'Bien-etre physique', 'debutant', 20, 'Tapis de yoga', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', 150.00, 90, 'webinar', 'Formation', 'debutant', 50, 'Ordinateur, connexion internet', 'Aucun'),
('Consultation Nutritionniste', 'Bilan et conseils personnalises avec un nutritionniste', 90.00, 60, 'consultation', 'Nutrition', NULL, 1, 'Aucun', 'Aucun'),
('Meditation Pleine Conscience', 'Atelier pratique de meditation', 100.00, 60, 'atelier', 'Bien-etre mental', 'debutant', 15, 'Coussin (optionnel)', 'Aucun'), 
('Formation Ergonomie Bureau', 'Adapter son poste de travail', 250.00, 120, 'atelier', 'Formation', 'debutant', 12, 'Aucun', 'Aucun'), 
('Coaching de Vie Individuel', 'Accompagnement personnalise objectifs', 150.00, 60, 'consultation', 'Developpement personnel', NULL, 1, 'Aucun', 'Aucun'), 
('Atelier Communication Assertive', 'Mieux communiquer ses besoins', 180.00, 120, 'atelier', 'Developpement personnel', 'intermediaire', 15, 'Aucun', 'Aucun'), 
('Sophrologie Relaxation', 'Techniques de relaxation profonde', 70.00, 50, 'consultation', 'Bien-etre mental', NULL, 1, 'Aucun', 'Aucun'), 
('Conference Sommeil Reparateur', 'Comprendre et ameliorer son sommeil', 300.00, 90, 'conference', 'Bien-etre mental', 'debutant', 100, 'Aucun', 'Aucun'), 
('Massage Amma Assis', 'Massage court sur chaise', 25.00, 20, 'consultation', 'Bien-etre physique', NULL, 1, 'Aucun', 'Aucun'),
('Atelier Gestion du Temps', 'Optimiser son organisation quotidienne', 170.00, 120, 'atelier', 'Developpement personnel', 'debutant', 18, 'Papier, Stylo', 'Aucun'),
('Pilates pour le Dos', 'Renforcement musculaire doux pour soulager les douleurs dorsales', 110.00, 60, 'atelier', 'Bien-etre physique', 'debutant', 15, 'Tapis de sol', 'Aucun'),
('Webinar Cybersecurite au Quotidien', 'Les bonnes pratiques pour se proteger en ligne', 200.00, 90, 'webinar', 'Formation', 'debutant', 75, 'Ordinateur, connexion internet', 'Aucun'),
('Consultation Arret Tabac', 'Accompagnement individuel pour arreter de fumer', 85.00, 50, 'consultation', 'Sante', NULL, 1, 'Aucun', 'Motivation'),
('Conference Nutrition Sportive', 'Adapter son alimentation a sa pratique sportive', 280.00, 75, 'conference', 'Nutrition', 'intermediaire', 80, 'Aucun', 'Aucun'),
('Atelier Cuisine Saine et Rapide', 'Preparer des repas equilibres en peu de temps', 140.00, 90, 'atelier', 'Nutrition', 'debutant', 12, 'Acces a une cuisine', 'Aucun'),
('Consultation Coaching Professionnel', 'Developper son potentiel et atteindre ses objectifs', 160.00, 60, 'consultation', 'Developpement personnel', NULL, 1, 'Aucun', 'Aucun'),
('Formation Secourisme en Entreprise', 'Gestes qui sauvent sur le lieu de travail', 350.00, 240, 'atelier', 'Formation', 'debutant', 10, 'Mannequin de secourisme', 'Aucun'),
('Webinar Communication Non Violente', 'Ameliorer ses relations professionnelles et personnelles', 190.00, 120, 'webinar', 'Communication', 'intermediaire', 60, 'Ordinateur, connexion internet', 'Aucun'),
('Reflexologie Plantaire', 'Stimulation des points reflexes pour la relaxation', 75.00, 50, 'consultation', 'Bien-etre physique', NULL, 1, 'Aucun', 'Aucun'),
('Atelier Gestion des Conflits', 'Resoudre les desaccords de maniere constructive', 220.00, 180, 'atelier', 'Developpement personnel', 'intermediaire', 15, 'Tableau blanc', 'Aucun'),
('Conference Equilibre Vie Pro/Vie Perso', 'Trouver l''harmonie entre travail et vie privee', 300.00, 90, 'conference', 'Bien-etre mental', 'debutant', 120, 'Aucun', 'Aucun'),
('Consultation Dietetique Personnalisee', 'Plan alimentaire adapte a vos besoins specifiques', 95.00, 60, 'consultation', 'Nutrition', NULL, 1, 'Aucun', 'Bilan sanguin recent (optionnel)'),
('Atelier Creativite et Innovation', 'Liberer son potentiel creatif au travail', 180.00, 120, 'atelier', 'Developpement personnel', 'debutant', 20, 'Materiel de dessin/ecriture', 'Aucun'),
('Webinar Prevention Burn-out', 'Reconnaître les signes et prevenir l''epuisement professionnel', 210.00, 90, 'webinar', 'Sante mentale', 'debutant', 80, 'Ordinateur, connexion internet', 'Aucun'),
('Seance d''Hypnose Relaxation', 'Atteindre un etat de relaxation profonde par l''hypnose', 100.00, 60, 'consultation', 'Bien-être mental', NULL, 1, 'Fauteuil confortable', 'Aucun'),
('Formation Management Bienveillant', 'Diriger ses equipes avec empathie et efficacite', 400.00, 360, 'atelier', 'Formation', 'intermediaire', 12, 'Support de cours', 'Être manager'),
('Conference Impact du Numerique sur le Bien-être', 'Comprendre les enjeux de l''hyperconnexion', 250.00, 75, 'conference', 'Sante mentale', 'debutant', 100, 'Aucun', 'Aucun'),
('Atelier Auto-massage Do-In', 'Techniques simples pour soulager les tensions', 90.00, 60, 'atelier', 'Bien-être physique', 'debutant', 15, 'Aucun', 'Aucun'),
('Consultation Orientation Scolaire/Professionnelle', 'Aide a la definition d''un projet d''avenir', 120.00, 90, 'consultation', 'Developpement personnel', NULL, 1, 'Aucun', 'Jeunes/Adultes en questionnement'),
('Webinar Initiation a l''Investissement Financier', 'Comprendre les bases pour gerer son epargne', 150.00, 90, 'webinar', 'Formation', 'debutant', 50, 'Ordinateur, connexion internet', 'Aucun'),
('Atelier Prise de Parole en Public', 'Gagner en aisance et en impact a l''oral', 250.00, 180, 'atelier', 'Formation', 'intermediaire', 12, 'Camera (optionnel)', 'Aucun'),
('Consultation Sexologie', 'Aborder les questions liees a la sexualite', 90.00, 60, 'consultation', 'Sante', NULL, 1, 'Aucun', 'Aucun'),
('Conference Addictions (ecrans, jeux...)', 'Comprendre et prevenir les dependances comportementales', 280.00, 90, 'conference', 'Sante mentale', 'debutant', 90, 'Aucun', 'Aucun'),
('Atelier Jardinage Urbain Anti-Stress', 'Cultiver des plantes pour se detendre', 130.00, 120, 'atelier', 'Bien-être mental', 'debutant', 10, 'Petits pots, terreau, graines', 'Aucun'),
('Seance de Musicotherapie Receptive', 'ecoute musicale guidee pour la relaxation', 60.00, 45, 'consultation', 'Bien-être mental', NULL, 1, 'Casque audio', 'Aucun'),
('Formation Risques Psycho-Sociaux (RPS)', 'Identifier et prevenir les RPS en entreprise', 450.00, 420, 'atelier', 'Formation', 'intermediaire', 15, 'Support de cours', 'Managers/RH'),
('Webinar Decouverte de l''Art-therapie', 'Explorer son potentiel creatif pour le bien-être', 160.00, 90, 'webinar', 'Bien-être mental', 'debutant', 40, 'Feuilles, crayons/peinture', 'Aucun'),
('Consultation Conseil en Image Professionnelle', 'Valoriser son image au travail', 140.00, 75, 'consultation', 'Developpement personnel', NULL, 1, 'Aucun', 'Aucun'),
('Atelier Initiation Tai Chi Chuan', 'Art martial doux pour l''equilibre et la serenite', 100.00, 60, 'atelier', 'Bien-être physique', 'debutant', 15, 'Tenue souple', 'Aucun');

INSERT INTO personnes (nom, prenom, email, mot_de_passe, telephone, date_naissance, genre, photo_url, role_id, entreprise_id, site_id, statut, derniere_connexion) VALUES
('Admin', 'System', 'admin@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0000000000', '1990-01-01', 'Autre', '/assets/images/icons/default-user.png', 1, NULL, NULL, 'actif', NOW()), 
('Salarie', 'Test', 'salarie@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0000000000', '1990-01-01', 'Autre', '/assets/images/icons/default-user.png', 2, NULL, NULL, 'actif', NOW()), 
('Prestataire', 'Test', 'prestataire@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0000000000', '1990-01-01', 'Autre', '/assets/images/icons/default-user.png', 3, NULL, NULL, 'actif', NOW()), 
('Entreprise', 'Test', 'entreprise@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0000000000', '1990-01-01', 'Autre', '/assets/images/icons/default-user.png', 4, NULL, NULL, 'actif', NOW()), 
('Dupont', 'Marie', 'marie.dupont@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0612345678', '1990-05-15', 'F', '/assets/images/icons/default-user.png', 2, 1, 1, 'actif', NOW() - INTERVAL 1 DAY), 
('Martin', 'Jean', 'jean.martin@santeplus.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0623456789', '1985-08-20', 'M', '/assets/images/icons/default-user.png', 2, 2, 2, 'actif', NOW() - INTERVAL 2 DAY), 
('Bernard', 'Chloe', 'chloe.bernard@bienetrecorp.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0634567890', '1992-03-10', 'F', '/assets/images/icons/default-user.png', 2, 3, 4, 'actif', NOW() - INTERVAL 3 DAY), 
('Dubois', 'Pierre', 'pierre.dubois@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0645678901', '1988-12-05', 'M', '/assets/images/icons/default-user.png', 2, 1, 1, 'actif', NOW() - INTERVAL 4 DAY), 
('Robert', 'Lucas', 'lucas.robert@santeplus.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0667890123', '1991-04-15', 'M', '/assets/images/icons/default-user.png', 2, 2, 2, 'actif', NOW() - INTERVAL 5 DAY), 
('Richard', 'Julie', 'julie.richard@bienetrecorp.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0678901234', '1987-09-30', 'F', '/assets/images/icons/default-user.png', 2, 3, 3, 'actif', NOW() - INTERVAL 6 DAY), 
('Durand', 'Sophie', 'sophie.durand@santeplus.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0689012345', '1993-02-20', 'F', '/assets/images/icons/default-user.png', 3, NULL, NULL, 'actif', NOW() - INTERVAL 7 DAY), 
('Moreau', 'Hugo', 'hugo.moreau@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0690123456', '1996-06-25', 'M', '/assets/images/icons/default-user.png', 2, 1, 1, 'actif', NOW() - INTERVAL 8 DAY), 
('Duamel', 'Heloise', 'duamelle.heloise@bienetrecorp.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0612345677', '1995-03-15', 'F', '/assets/images/icons/default-user.png', 2, 3, 3, 'actif', NOW() - INTERVAL 9 DAY), 
('Dupois', 'Jacques', 'jacques.dupois@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0612345666', '1995-03-15', 'M', '/assets/images/icons/default-user.png', 2, 1, 1, 'inactif', NOW() - INTERVAL 10 DAY),
('Representant', 'SantePlus', 'rep.santeplus@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0000000001', '1980-01-01', 'Autre', '/assets/images/icons/default-user.png', 4, 2, 2, 'actif', NOW()),
('Lefevre', 'Camille', 'camille.lefevre@santeplus.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0611223344', '1994-11-25', 'F', '/assets/images/icons/default-user.png', 2, 2, 2, 'actif', NOW() - INTERVAL 1 DAY),
('Girard', 'Thomas', 'thomas.girard@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0622334455', '1989-07-12', 'M', '/assets/images/icons/default-user.png', 2, 1, 1, 'actif', NOW() - INTERVAL 2 DAY),
('Bonnet', 'Elodie', 'elodie.bonnet@bienetrecorp.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0633445566', '1991-01-30', 'F', '/assets/images/icons/default-user.png', 2, 3, 3, 'actif', NOW() - INTERVAL 3 DAY),
('Roux', 'Antoine', 'antoine.roux@financeconseil.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0644556677', '1987-03-18', 'M', '/assets/images/icons/default-user.png', 2, 4, 5, 'actif', NOW() - INTERVAL 4 DAY),
('David', 'Laura', 'laura.david@retailexpress.com', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0655667788', '1993-09-05', 'F', '/assets/images/icons/default-user.png', 2, 5, 7, 'actif', NOW() - INTERVAL 5 DAY),
('Morel', 'Nicolas', 'nicolas.morel@eduforma.org', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0666778899', '1990-06-15', 'M', '/assets/images/icons/default-user.png', 2, 6, 9, 'actif', NOW() - INTERVAL 6 DAY),
('Fournier', 'Manon', 'manon.fournier@buildinnov.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0677889900', '1995-12-01', 'F', '/assets/images/icons/default-user.png', 2, 7, 11, 'actif', NOW() - INTERVAL 7 DAY),
('Garcia', 'Alexandre', 'alexandre.garcia@greentechenergy.com', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0688990011', '1986-08-22', 'M', '/assets/images/icons/default-user.png', 2, 8, 12, 'actif', NOW() - INTERVAL 8 DAY),
('Rodriguez', 'Pauline', 'pauline.rodriguez@agribiosolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0699001122', '1992-04-10', 'F', '/assets/images/icons/default-user.png', 2, 9, 13, 'inactif', NOW() - INTERVAL 9 DAY),
('Martinez', 'Julien', 'julien.martinez@transportrapide.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0600112233', '1988-10-28', 'M', '/assets/images/icons/default-user.png', 2, 10, 15, 'actif', NOW() - INTERVAL 10 DAY),
('Blanc', 'Mathilde', 'mathilde.blanc@psychologue.pro', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0711223344', '1985-02-14', 'F', '/assets/images/icons/default-user.png', 3, NULL, NULL, 'actif', NOW() - INTERVAL 1 DAY),
('Petit', 'Romain', 'romain.petit@coach-bienetre.com', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0722334455', '1980-11-03', 'M', '/assets/images/icons/default-user.png', 3, NULL, NULL, 'actif', NOW() - INTERVAL 2 DAY),
('Sanchez', 'Emma', 'emma.sanchez@nutritionniste-conseil.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0733445566', '1990-09-19', 'F', '/assets/images/icons/default-user.png', 3, NULL, NULL, 'actif', NOW() - INTERVAL 3 DAY),
('Mercier', 'Leo', 'leo.mercier@sophrologue-paris.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0744556677', '1978-05-05', 'M', '/assets/images/icons/default-user.png', 3, NULL, NULL, 'en_attente', NOW() - INTERVAL 4 DAY),
('Chevalier', 'Ines', 'ines.chevalier@reflexologue-lyon.com', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0755667788', '1983-03-29', 'F', '/assets/images/icons/default-user.png', 3, NULL, NULL, 'actif', NOW() - INTERVAL 5 DAY),
('Perez', 'Hugo', 'hugo.perez@formateur-secourisme.org', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0766778899', '1986-12-11', 'M', '/assets/images/icons/default-user.png', 3, NULL, NULL, 'actif', NOW() - INTERVAL 6 DAY),
('Andre', 'Louise', 'louise.andre@hypnotherapeute.net', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0777889900', '1975-01-20', 'F', '/assets/images/icons/default-user.png', 3, NULL, NULL, 'actif', NOW() - INTERVAL 7 DAY),
('Fernandez', 'Gabriel', 'gabriel.fernandez@coach-image.pro', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0788990011', '1989-04-08', 'M', '/assets/images/icons/default-user.png', 3, NULL, NULL, 'suspendu', NOW() - INTERVAL 8 DAY),
('Gauthier', 'Alice', 'alice.gauthier@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0612121212', '1996-02-18', 'F', '/assets/images/icons/default-user.png', 2, 1, 1, 'actif', NOW() - INTERVAL 1 DAY),
('Henry', 'Raphael', 'raphael.henry@santeplus.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0623232323', '1992-10-01', 'M', '/assets/images/icons/default-user.png', 2, 2, 2, 'actif', NOW() - INTERVAL 2 DAY),
('Rousseau', 'Eva', 'eva.rousseau@bienetrecorp.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0634343434', '1997-05-11', 'F', '/assets/images/icons/default-user.png', 2, 3, 4, 'actif', NOW() - INTERVAL 3 DAY),
('Nicolas', 'Adam', 'adam.nicolas@financeconseil.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0645454545', '1994-08-07', 'M', '/assets/images/icons/default-user.png', 2, 4, 5, 'actif', NOW() - INTERVAL 4 DAY),
('Leclerc', 'Clara', 'clara.leclerc@retailexpress.com', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0656565656', '1991-03-22', 'F', '/assets/images/icons/default-user.png', 2, 5, 6, 'actif', NOW() - INTERVAL 5 DAY),
('Perrin', 'Louis', 'louis.perrin@eduforma.org', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0678787878', '1988-11-16', 'M', '/assets/images/icons/default-user.png', 2, 6, 10, 'inactif', NOW() - INTERVAL 6 DAY),
('Morin', 'Zoe', 'zoe.morin@buildinnov.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0689898989', '1993-01-09', 'F', '/assets/images/icons/default-user.png', 2, 7, 11, 'actif', NOW() - INTERVAL 7 DAY),
('Mathieu', 'Arthur', 'arthur.mathieu@greentechenergy.com', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0690909090', '1995-07-31', 'M', '/assets/images/icons/default-user.png', 2, 8, 12, 'actif', NOW() - INTERVAL 8 DAY),
('Clement', 'Rose', 'rose.clement@agribiosolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0601010101', '1998-04-20', 'F', '/assets/images/icons/default-user.png', 2, 9, 13, 'actif', NOW() - INTERVAL 9 DAY),
('Guerin', 'Theo', 'theo.guerin@transportrapide.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0613131313', '1990-12-24', 'M', '/assets/images/icons/default-user.png', 2, 10, 14, 'actif', NOW() - INTERVAL 10 DAY),
('Representant', 'TechSolutions', 'rep.techsolutions@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0000000002', '1975-05-10', 'Autre', '/assets/images/icons/default-user.png', 4, 1, 1, 'actif', NOW()),
('Representant', 'RetailExpress', 'rep.retailexpress@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0000000003', '1982-09-15', 'Autre', '/assets/images/icons/default-user.png', 4, 5, 6, 'suspendu', NOW());

INSERT INTO services (id, type, description, actif, ordre, tarif_annuel_par_salarie, prix_base_indicatif) VALUES
(1, 'Starter Pack', 'Pour les petites equipes (jusqu''a 30 salaries)', TRUE, 10, 180.00, 100.00),
(2, 'Basic Pack', 'Solution equilibree (jusqu''a 250 salaries)', TRUE, 20, 150.00, 500.00),
(3, 'Premium Pack', 'Offre complete pour grandes entreprises (251+ salaries)', TRUE, 30, 100.00, 1000.00);

INSERT INTO contrats (entreprise_id, service_id, date_debut, date_fin, nombre_salaries, statut, conditions_particulieres) VALUES
(1, 3, '2024-01-01', '2025-12-31', 150, 'actif', 'Acces a toutes les prestations premium'),
(2, 2, '2024-02-01', NULL, 300, 'actif', 'Acces illimite aux prestations'),
(3, 1, '2024-03-01', '2025-08-31', 35, 'actif', 'Acces aux prestations de base'),
(4, 1, '2023-11-01', '2024-10-31', 8, 'actif', 'Pack demarrage'),
(5, 3, '2024-04-01', NULL, 600, 'actif', 'Grand compte, support dedie'),
(6, 2, '2023-09-01', '2024-08-31', 180, 'expire', 'Conditions standard'),
(7, 2, '2024-05-01', NULL, 250, 'actif', 'Option ateliers creatifs'),
(8, 2, '2023-01-15', '2024-01-14', 100, 'expire', ''),
(9, 1, '2024-06-01', '2025-05-31', 45, 'actif', ''),
(10, 3, '2023-12-01', NULL, 400, 'actif', 'Gestion multi-sites'),
(13, 2, '2024-02-15', '2025-02-14', 190, 'actif', 'Accompagnement specifique R&D'),
(14, 3, '2023-07-01', '2024-06-30', 750, 'resilie', 'Resiliation anticipee suite a restructuration'),
(15, 2, '2024-07-01', NULL, 350, 'actif', ''),
(16, 1, '2024-01-01', '2024-12-31', 25, 'actif', 'Option bien-etre physique'),
(18, 2, '2023-10-01', NULL, 120, 'actif', 'Acces prioritaire consultations sante'),
(19, 3, '2024-03-15', NULL, 480, 'actif', 'Partenariat strategique'),
(22, 3, '2023-05-01', '2024-04-30', 550, 'expire', 'Logistique complexe'),
(25, 2, '2024-06-10', NULL, 300, 'en_attente', 'Validation en cours');

INSERT INTO contrats_prestations (contrat_id, prestation_id) VALUES
(1, 4), (1, 5), (1, 7), (1, 11), 
(2, 6), (2, 9), (2, 10), (2, 12), (2, 14), 
(3, 13), (3, 17), 
(4, 1), (4, 2), (4, 13), 
(5, 1), (5, 2), (5, 3), (5, 6), (5, 7), (5, 8), (5, 10), (5, 11), 
(7, 1), (7, 2), (7, 5), (7, 15), (7, 20), 
(9, 3), (9, 16), 
(10, 1), (10, 4), (10, 5), (10, 9), (10, 22), 
(13, 2), (13, 8), (13, 18), 
(15, 1), (15, 6), (15, 12), (15, 21), 
(16, 3), (16, 11), 
(18, 1), (18, 4), (18, 9), 
(16, 1), (16, 5), (16, 7), (16, 10), (16, 14), (16, 25), (16, 30);

INSERT INTO devis (entreprise_id, service_id, nombre_salaries_estimes, date_creation, date_validite, montant_total, montant_ht, tva, statut, conditions_paiement, delai_paiement) VALUES
(1, NULL, NULL, '2024-01-15', '2024-02-15', 1500.00, 1250.00, 20.00, 'accepte', 'Paiement a 30 jours', 30), 
(2, NULL, NULL, '2024-02-01', '2024-03-01', 2000.00, 1666.67, 20.00, 'accepte', 'Paiement a 45 jours', 45), 
(3, NULL, NULL, '2024-02-15', '2024-03-15', 1800.00, 1500.00, 20.00, 'refuse', 'Paiement a 30 jours', 30), 
(1, NULL, NULL, '2024-01-10', '2024-02-10', 3200.00, 2666.67, 20.00, 'accepte', 'Paiement a 15 jours', 15), 
(2, NULL, NULL, '2024-02-20', '2024-03-20', 2500.00, 2083.33, 20.00, 'en_attente', 'Paiement a 30 jours', 30), 
(3, NULL, NULL, '2024-01-25', '2024-02-25', 2800.00, 2333.33, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(4, 1, 10, '2024-03-05', '2024-04-05', 950.00, 791.67, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(5, 3, 550, '2024-03-10', '2024-04-10', 55000.00, 45833.33, 20.00, 'accepte', 'Paiement 50% commande, solde a 30j', 30),
(6, 2, 150, '2024-03-15', '2024-04-15', 22500.00, 18750.00, 20.00, 'en_attente', 'Paiement a 45 jours', 45),
(7, 2, 260, '2024-03-20', '2024-04-20', 39000.00, 32500.00, 20.00, 'accepte', 'Paiement a 60 jours', 60),
(8, 2, 110, '2024-03-25', '2024-04-25', 16500.00, 13750.00, 20.00, 'refuse', 'Paiement a 30 jours', 30),
(9, 1, 50, '2024-04-01', '2024-05-01', 4500.00, 3750.00, 20.00, 'accepte', 'Paiement a reception', 0),
(10, 3, 420, '2024-04-05', '2024-05-05', 42000.00, 35000.00, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(11, 1, 5, '2024-04-10', '2024-05-10', 650.00, 541.67, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(12, 1, 30, '2024-04-15', '2024-05-15', 2700.00, 2250.00, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(13, 2, 200, '2024-04-20', '2024-05-20', 30000.00, 25000.00, 20.00, 'accepte', 'Paiement a 45 jours', 45),
(15, 2, 380, '2024-04-25', '2024-05-25', 57000.00, 47500.00, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(16, 1, 28, '2024-05-01', '2024-06-01', 2520.00, 2100.00, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(17, 1, 7, '2024-05-05', '2024-06-05', 800.00, 666.67, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(18, 2, 130, '2024-05-10', '2024-06-10', 19500.00, 16250.00, 20.00, 'accepte', 'Paiement a 30 jours', 30),
(20, 1, 40, '2024-05-15', '2024-06-15', 3600.00, 3000.00, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(21, 1, 9, '2024-05-20', '2024-06-20', 900.00, 750.00, 20.00, 'expire', 'Paiement a 30 jours', 30),
(23, 1, 45, '2024-05-25', '2024-06-25', 4050.00, 3375.00, 20.00, 'accepte', 'Paiement a reception', 0),
(24, 2, 180, '2024-06-01', '2024-07-01', 27000.00, 22500.00, 20.00, 'accepte', 'Paiement a 60 jours', 60),
(27, 1, 35, '2024-06-05', '2024-07-05', 3150.00, 2625.00, 20.00, 'en_attente', 'Paiement a 30 jours', 30),
(30, 2, 150, '2024-06-10', '2024-07-10', 22500.00, 18750.00, 20.00, 'en_attente', 'Paiement a 45 jours', 45),
(19, 3, 480, '2024-03-10', '2024-04-09', 48000.00, 40000.00, 20.00, 'accepte', 'Paiement a 30 jours', 30), 
(22, 3, 550, '2024-04-20', '2024-05-20', 60000.00, 50000.00, 20.00, 'accepte', 'Paiement a 30 jours', 30), 
(25, 2, 300, '2024-06-05', '2024-07-05', 42000.00, 35000.00, 20.00, 'accepte', 'Paiement a 30 jours', 30), 
(26, 1, 5, '2024-05-15', '2024-06-15', 550.00, 458.33, 20.00, 'accepte', 'Paiement a 30 jours', 30), 
(28, 1, 8, '2024-02-20', '2024-03-20', 1800.00, 1500.00, 20.00, 'accepte', 'Paiement a 30 jours', 30), 
(29, 1, 40, '2024-06-15', '2024-07-15', 4500.00, 3750.00, 20.00, 'accepte', 'Paiement a 30 jours', 30); 

INSERT INTO evaluations (personne_id, prestation_id, note, commentaire, date_evaluation) VALUES
(5, 1, 5, 'Excellent service, tres professionnel', '2024-04-21'),
(6, 1, 4, 'Tres a l''ecoute, m''a beaucoup aide.', '2024-04-23'),
(8, 6, 5, 'Formation ergo tres claire et pratique.', '2024-04-24'),
(7, 3, 4, 'Bon webinar, contenu pertinent.', '2024-04-25'),
(9, 5, 5, 'Atelier meditation tres relaxant.', '2024-04-26'),
(10, 7, 4, 'Le coaching m''aide a avancer.', '2024-04-28'),
(12, 2, 3, 'Le yoga en entreprise, bonne idee mais salle un peu petite.', '2024-04-29'),
(17, 13, 5, 'Atelier gestion du temps tres utile, formatrice dynamique.', '2024-05-02'),
(18, 14, 4, 'Bien pour le dos, exercices efficaces.', '2024-05-03'),
(19, 15, 4, 'Webinar cybersecurite clair et accessible.', '2024-05-05'),
(20, 16, 5, 'Accompagnement arret tabac serieux.', '2024-05-06'),
(21, 18, 4, 'Atelier cuisine sympa et recettes faciles.', '2024-05-08'),
(22, 19, 5, 'Coaching pro tres personnalise, je recommande.', '2024-05-10'),
(23, 20, 4, 'Formation secourisme complete.', '2024-05-12'),
(24, 21, 3, 'Webinar CNV interessant mais un peu theorique.', '2024-05-13'),
(25, 22, 5, 'Reflexologie plantaire incroyable, tres relaxant!', '2024-05-15'),
(26, 23, 4, 'Bonnes techniques pour gerer les conflits.', '2024-05-16'),
(28, 26, 4, 'Consultation dietetique constructive.', '2024-05-18'),
(29, 27, 4, 'Atelier creativite amusant et stimulant.', '2024-05-20'),
(30, 28, 5, 'Webinar prevention burn-out necessaire et bien fait.', '2024-05-21'),
(31, 29, 4, 'Seance hypnose etonnante et relaxante.', '2024-05-23'),
(33, 31, 4, 'Conference interessante sur l''equilibre vie pro/perso.', '2024-05-24'),
(34, 32, 5, 'Atelier auto-massage facile a reproduire chez soi.', '2024-05-27'),
(35, 33, 3, 'Orientation utile mais j''aurais aime plus de pistes concretes.', '2024-05-28'),
(36, 34, 4, 'Webinar investissement clair pour un debutant.', '2024-05-30'),
(37, 35, 5, 'Atelier prise de parole tres efficace, j''ai gagne en confiance.', '2024-06-01'),
(39, 38, 4, 'Atelier jardinage original et apaisant.', '2024-06-03'),
(40, 39, 4, 'Musicotherapie, une belle decouverte.', '2024-06-05'),
(42, 41, 5, 'Initiation Tai Chi, professeur patient et pedagogue.', '2024-06-07');

INSERT INTO evenements (titre, description, date_debut, date_fin, lieu, type, capacite_max, niveau_difficulte, materiel_necessaire, prerequis, site_id) VALUES
('Conference Bien-etre Paris', 'Conference interactive sur site', NOW() + INTERVAL 1 WEEK, NOW() + INTERVAL 1 WEEK + INTERVAL 2 HOUR, 'Salle Paris A', 'conference', 100, 'intermediaire', 'Aucun', 'Aucun', 1),
('Webinar Gestion du Stress', 'Formation en ligne accessible a tous', NOW() + INTERVAL 2 WEEK, NOW() + INTERVAL 2 WEEK + INTERVAL 90 MINUTE, 'En ligne', 'webinar', 50, 'debutant', 'PC', 'Aucun', NULL),
('Atelier Ergonomie Sante+', 'Amenager son espace de travail', NOW() + INTERVAL 3 WEEK, NOW() + INTERVAL 3 WEEK + INTERVAL 2 HOUR, 'Centre Sante+ Salle B', 'atelier', 30, 'debutant', 'Aucun', 'Aucun', 2),
('Defi Sportif Inter-Entreprises', 'Competition amicale de course a pied', NOW() + INTERVAL 4 WEEK, NOW() + INTERVAL 4 WEEK + INTERVAL 3 HOUR, 'Parc de la Tete d''Or, Lyon', 'defi_sportif', 200, 'intermediaire', 'Tenue de sport', 'Inscription prealable', NULL),
('Atelier Initiation Sophrologie', 'Decouverte des techniques de base', NOW() + INTERVAL 5 WEEK, NOW() + INTERVAL 5 WEEK + INTERVAL 60 MINUTE, 'Salle Zen Bien-etre Corp Troyes', 'atelier', 15, 'debutant', 'Aucun', 'Aucun', 4),
('Conference Nutrition et Performance', 'Optimiser son alimentation pour le travail', NOW() + INTERVAL 6 WEEK, NOW() + INTERVAL 6 WEEK + INTERVAL 90 MINUTE, 'Auditorium Tech Solutions', 'conference', 150, 'debutant', 'Aucun', 'Aucun', 1),
('Webinar Communication Assertive', 'Mieux s''exprimer au quotidien', NOW() + INTERVAL 7 WEEK, NOW() + INTERVAL 7 WEEK + INTERVAL 1 HOUR, 'En ligne', 'webinar', 80, 'intermediaire', 'PC, Micro', 'Aucun', NULL),
('Atelier Yoga du Rire', 'Se detendre et booster son moral', NOW() + INTERVAL 8 WEEK, NOW() + INTERVAL 8 WEEK + INTERVAL 45 MINUTE, 'Espace Detente Retail Express Lyon', 'atelier', 25, 'debutant', 'Aucun', 'Aucun', 7),
('Conference Gestion des Emotions', 'Comprendre et maitriser ses emotions', NOW() + INTERVAL 9 WEEK, NOW() + INTERVAL 9 WEEK + INTERVAL 2 HOUR, 'Amphitheatre EduForma Paris', 'conference', 120, 'intermediaire', 'Aucun', 'Aucun', 10),
('Defi Bien-etre : Semaine sans ecran', 'Challenge collectif pour deconnecter', NOW() + INTERVAL 10 WEEK, NOW() + INTERVAL 11 WEEK, 'A distance', 'defi_sportif', NULL, 'debutant', 'Volonte', 'Engagement', NULL),
('Atelier Prevention TMS', 'Gestes et postures pour eviter les troubles musculo-squelettiques', NOW() + INTERVAL 11 WEEK, NOW() + INTERVAL 11 WEEK + INTERVAL 90 MINUTE, 'Salle Formation BuildInnov Toulouse', 'atelier', 20, 'debutant', 'Aucun', 'Aucun', 11),
('Webinar Sommeil Reparateur', 'Conseils pratiques pour mieux dormir', NOW() + INTERVAL 12 WEEK, NOW() + INTERVAL 12 WEEK + INTERVAL 1 HOUR, 'En ligne', 'webinar', 100, 'debutant', 'PC', 'Aucun', NULL),
('Conference Sante Mentale au Travail', 'Briser les tabous et promouvoir le bien-etre psychologique', NOW() + INTERVAL 13 WEEK, NOW() + INTERVAL 13 WEEK + INTERVAL 2 HOUR, 'Centre de Conference Marseille', 'conference', 180, 'avance', 'Aucun', 'Aucun', NULL),
('Atelier Meditation Pleine Conscience Avance', 'Approfondir sa pratique', NOW() + INTERVAL 14 WEEK, NOW() + INTERVAL 14 WEEK + INTERVAL 75 MINUTE, 'Salle Calme Sante Plus Paris', 'atelier', 10, 'avance', 'Coussin (optionnel)', 'Pratique reguliere', 2),
('Webinar Gestion de Projet Agile', 'Introduction aux methodes agiles', NOW() + INTERVAL 15 WEEK, NOW() + INTERVAL 15 WEEK + INTERVAL 2 HOUR, 'En ligne', 'webinar', 60, 'intermediaire', 'PC', 'Notions gestion projet', NULL),
('Defi Sportif : Challenge Velotaf', 'Promouvoir le velo pour aller au travail', NOW() + INTERVAL 16 WEEK, NOW() + INTERVAL 17 WEEK, 'Trajets domicile-travail', 'defi_sportif', NULL, 'debutant', 'Velo', 'Habiter a distance cyclable', NULL),
('Atelier Cuisine Vegetarienne', 'Decouvrir des recettes savoureuses et equilibrees', NOW() + INTERVAL 17 WEEK, NOW() + INTERVAL 17 WEEK + INTERVAL 2 HOUR, 'Cuisine partagee Bien-etre Corp Paris', 'atelier', 12, 'debutant', 'Ingredients fournis', 'Aucun', 3),
('Conference Leadership Positif', 'Inspirer et motiver ses equipes', NOW() + INTERVAL 18 WEEK, NOW() + INTERVAL 18 WEEK + INTERVAL 90 MINUTE, 'Siege Social Finance Conseil Paris', 'conference', 50, 'avance', 'Aucun', 'Managers', 5),
('Webinar Ergonomie a Domicile', 'Amenager son poste de teletravail', NOW() + INTERVAL 19 WEEK, NOW() + INTERVAL 19 WEEK + INTERVAL 1 HOUR, 'En ligne', 'webinar', 150, 'debutant', 'PC, Webcam (optionnel)', 'Aucun', NULL),
('Atelier Auto-Hypnose', 'Apprendre a utiliser l''auto-hypnose pour le bien-etre', NOW() + INTERVAL 20 WEEK, NOW() + INTERVAL 20 WEEK + INTERVAL 2 HOUR, 'Salle Detente Tech Solutions Paris', 'atelier', 15, 'intermediaire', 'Fauteuil confortable', 'Aucun', 1),
('Conference Securite Informatique PME', 'Proteger son entreprise des cybermenaces', NOW() + INTERVAL 21 WEEK, NOW() + INTERVAL 21 WEEK + INTERVAL 2 HOUR, 'CCI Lyon Metropole', 'conference', 70, 'intermediaire', 'Aucun', 'Dirigeants/Responsables IT', NULL),
('Webinar Developpement Durable en Entreprise', 'Initiatives et bonnes pratiques RSE', NOW() + INTERVAL 22 WEEK, NOW() + INTERVAL 22 WEEK + INTERVAL 1 HOUR, 'En ligne', 'webinar', 90, 'debutant', 'PC', 'Aucun', NULL),
('Atelier Gestion Financiere Personnelle', 'Mieux gerer son budget et son epargne', NOW() + INTERVAL 23 WEEK, NOW() + INTERVAL 23 WEEK + INTERVAL 2 HOUR, 'Salle Formation Sante Plus Paris', 'atelier', 25, 'debutant', 'Calculatrice (optionnel)', 'Aucun', 2),
('Conference Neurosciences et Apprentissage', 'Comment le cerveau apprend', NOW() + INTERVAL 24 WEEK, NOW() + INTERVAL 24 WEEK + INTERVAL 90 MINUTE, 'Universite Lyon 2', 'conference', 200, 'avance', 'Aucun', 'Aucun', NULL),
('Defi Solidaire : Collecte de Dons', 'Mobilisation pour une association partenaire', NOW() + INTERVAL 25 WEEK, NOW() + INTERVAL 26 WEEK, 'Multi-sites / En ligne', 'defi_sportif', NULL, 'debutant', 'Generosite', 'Aucun', NULL),
('Atelier Communication Interculturelle', 'Comprendre et s''adapter aux differences culturelles', NOW() + INTERVAL 26 WEEK, NOW() + INTERVAL 26 WEEK + INTERVAL 3 HOUR, 'Centre International de Conference Paris', 'atelier', 30, 'intermediaire', 'Aucun', 'Travail international (optionnel)', NULL);

INSERT INTO communautes (nom, description, type, niveau, capacite_max) VALUES
('Yoga & Meditation', 'Groupe de pratique du yoga et de la meditation', 'bien_etre', 'debutant', 20), 
('Running Club', 'Club de course a pied pour tous niveaux', 'sport', 'intermediaire', 30); 

INSERT INTO associations (id, nom) VALUES
(1, 'Association Bienfaiteurs'),
(2, 'Aide et Partage'),
(3, 'Sourire pour Tous');

INSERT INTO dons (personne_id, association_id, montant, type, description, date_don, statut) VALUES
(5, 1, 50.00, 'financier', 'Don pour le programme de bien-etre', '2024-03-01', 'valide'),
(6, 2, NULL, 'materiel', 'Don de materiel informatique (ecran)', '2024-03-15', 'valide'),
(7, 3, 100.00, 'financier', 'Soutien au programme de sante mentale', '2024-03-20', 'valide'),
(2, 1, 25.00, 'financier', 'Petit don pour Salarie Test', '2024-05-12', 'valide');

INSERT INTO notifications (personne_id, titre, message, type, lien, lu, date_lecture) VALUES
(5, 'Nouveau Rendez-vous', 'Votre rendez-vous a ete confirme', 'success', '/rendez-vous/1', false, NULL),
(6, 'Paiement Reçu', 'Votre paiement a ete reçu avec succes', 'success', '/factures/1', true, NOW() - INTERVAL 5 DAY),
(7, 'Nouvel evenement', 'Un nouvel evenement est disponible', 'info', '/evenements/2', false, NULL),
(2, 'Bienvenue Salarie Test!', 'Votre compte est pret.', 'info', '/mon-profil.php', false, NULL);

INSERT INTO logs (personne_id, action, details, ip_address, created_at) VALUES
(5, 'login', 'Connexion reussie', '192.168.1.10', NOW() - INTERVAL 5 DAY),
(6, 'rdv_creation', 'Creation RDV ID 2 (Yoga)', '192.168.1.11', NOW() - INTERVAL 4 DAY),
(7, 'profile_update', 'Mise a jour du numero de telephone', '192.168.1.12', NOW() - INTERVAL 3 DAY),
(1, 'admin_action', 'Desactivation contrat ID 3', '127.0.0.1', NOW() - INTERVAL 2 DAY),
(2, 'login', 'Connexion reussie', '192.168.1.20', NOW() - INTERVAL 1 HOUR);

INSERT INTO conseils (titre, icone, resume, categorie, contenu) VALUES
('Gestion du stress au travail', '', 'Apprenez des techniques pour mieux gerer la pression et preserver votre equilibre.', 'Stress', 
'Le stress chronique peut avoir des effets nefaste sur votre sante physique et mentale.\n\nVoici quelques techniques simples :\n1. Respiration profonde : Inspirez lentement par le nez, retenez quelques secondes, expirez lentement par la bouche. Repetez 5 fois pour calmer le systeme nerveux.\n2. Pause active : Levez-vous et marchez quelques minutes toutes les heures. Changer d\'environnement aide a clarifier les idees.\n3. Priorisation : Utilisez la matrice d\'Eisenhower (urgent/important) pour organiser vos taches et vous concentrer sur l\'essentiel.\n4. Communication : Exprimez vos difficulte a votre manager ou a un collegue de confiance. Ne restez pas isole.\n5. Deconnexion : Definissez des limites claires entre travail et vie personnelle. Essayez de vous deconnecter (notifications, emails) en dehors des heures de bureau.\n\nN\'oubliez pas de faire des pauses regulières, meme courtes, pour deconnecter et recharger vos batteries.'),
('Ameliorer son sommeil', '', 'Des conseils pratiques pour retrouver un sommeil reparateur et une meilleure energie.', 'Sommeil',
'Un bon sommeil est crucial pour la concentration, l\'humeur et la sante generale.\n\nConseils :\n- Regularite : Couchez-vous et levez-vous a heures regulieres, meme le week-end, pour stabiliser votre horloge biologique.\n- Environnement : Creez un environnement propice au sommeil : chambre sombre, calme et fraiche (idealement entre 18-20°C).\n- Ecrans : Evitez les ecrans (telephone, tablette, ordinateur) au moins 30 a 60 minutes avant le coucher. La lumiere bleue perturbe la production de melatonine.\n- Alimentation et hydratation : Limitez la cafeine et l\'alcool, surtout en fin de journee. Evitez les repas lourds ou de trop boire juste avant de dormir.\n- Relaxation : Pratiquez une activite relaxante avant de dormir (lecture apaisante, musique douce, bain chaud, meditation legere).'),
(''Alimentation equilibree au bureau', '', 'Comment bien manger au travail pour maintenir votre energie et votre concentration.', 'Nutrition',
'Manger sainement au bureau est possible et essentiel ! Cela booste votre energie, votre concentration et votre bien-etre general.\n\nIdees :\n- Preparation : Preparez vos dejeuners la veille : salades composees, soupes, plats maison rechauffes. C\'est plus sain et economique.\n- Snacks sains : Anticipez les petites faims avec des fruits frais, yaourts nature, oléagineux (amandes, noix), legumes croquants (carottes, concombre).\n- Hydratation : Buvez de l\'eau regulierement tout au long de la journee (visez 1,5 L). Une gourde sur le bureau aide !\n- Eviter les pieges : Limitez les distributeurs automatiques (souvent riches en sucre/sel) et les fast-foods trop frequents.\n- Pleine conscience : Prenez le temps de manger assis, loin de votre ecran. Machez lentement et savourez votre repas pour une meilleure digestion et sante.\n\nRecette rapide : Salade Quinoa-Poulet-Avocat\nIngrédients : Quinoa cuit, blanc de poulet grille coupe en des, 1/2 avocat en tranches, tomates cerises coupees en deux, quelques feuilles d\'epinards frais, vinaigrette legere (huile d\'olive, jus de citron, sel, poivre).\nMelangez le tout dans une boite hermetique. Simple, sain et delicieux !'),
('5 minutes de meditation guidee', '', 'Une courte pause meditative pour recentrer votre esprit et apaiser le mental.', 'Stress',
'Installez-vous confortablement (chaise ou sol), fermez les yeux ou fixez un point devant vous avec un regard doux.\nPortez votre attention sur votre respiration. Sentez l\'air entrer par le nez et sortir. Observez le mouvement de votre ventre ou de votre poitrine.\nQuand des pensees, emotions ou sensations arrivent, c\'est normal. Observez-les sans jugement, comme des nuages passant dans le ciel, puis ramenez doucement votre attention a votre souffle.\nRestez ainsi pendant 5 minutes. Si cela semble long au debut, commencez par 2 ou 3 minutes.\nTerminez en reprenant conscience de votre corps et de la piece autour de vous. Sentez le calme qui s\'est installe.\nCette simple pratique reguliere peut reduire significativement le stress et ameliorer la concentration.'),
('Hydratation : pourquoi et comment ?', '', 'L\'importance vitale de boire de l\'eau et des astuces simples pour y parvenir.', 'Nutrition',
'Notre corps est compose majoritairement d\'eau (environ 60 %). Une bonne hydratation est vitale pour le fonctionnement de nos organes, notre niveau d\'energie, notre clarte mentale et la sante de notre peau.\n\nPourquoi est-ce si important ?\n- Transporte les nutriments essentiels aux cellules.\n- Elimine les toxines et les dechets metaboliques.\n- Regule la temperature corporelle (transpiration).\n- Lubrifie les articulations.\n- Aide a la concentration et previent les maux de tete.\n\nComment boire suffisamment ?\n- Gourde a portee de main : Gardez une bouteille ou une gourde d\'eau sur votre bureau et remplissez-la regulierement.\n- Boire avant la soif : N\'attendez pas d\'avoir soif, c\'est deja un signe de deshydratation legere.\n- Varier les plaisirs : Alternez avec des tisanes non sucrees, de l\'eau infusee (citron, menthe, concombre) pour changer.\n- Aliments riches en eau : Consommez des fruits et legumes comme le concombre, la pasteque, l\'orange, la salade.\n- Adapter selon les besoins : Augmentez votre apport en cas d\'activite physique, de forte chaleur ou de fievre.\nObjectif moyen : environ 1,5 a 2 L d\'eau pure par jour, a adapter individuellement.'),
('Communication non violente (CNV) : introduction', '', 'Les bases pour communiquer avec plus d\'empathie, de clarte et d\'efficacite.', 'Communication',
'La Communication Non Violente (CNV) est une approche developpee par Marshall Rosenberg qui aide a creer des relations basees sur le respect mutuel et la cooperation.\n\nElle repose sur 4 etapes cles pour exprimer ce qui se passe en nous et entendre l\'autre avec empathie :\n1. Observation (O) : Decrire les faits concrets et specifiques que nous observons, sans jugement ni interpretation. (Ex. : "Quand je vois des dossiers non classes sur le bureau commun...")\n2. Sentiment (S) : Exprimer l\'emotion ressenti face a cette observation. Utiliser "Je me sens..." (Ex. : "...je me sens un peu frustre(e)...")\n3. Besoin (B) : Identifier le besoin fondamental (autonomie, respect, clarte, ordre, soutien...) qui est satisfait ou insatisfait et qui est a l\'origine du sentiment. (Ex. : "...car j\'ai besoin d\'ordre et de clarte dans notre espace de travail partage.")\n4. Demande (D) : Formuler une demande concrete, positive, realisable et negociable, visant a satisfaire le besoin identifie. Preferer une demande a une exigence. (Ex. : "Serais-tu d\'accord pour que nous prenions 5 minutes ensemble pour decider comment organiser cet espace ?")\n\nEcoute empathique : La CNV s\'applique a l\'ecoute. Tentez de deviner les sentiments et besoins de l\'autre derriere ses mots, meme s\'ils sont exprimes maladroitement.\n\nPratiquer la CNV demande de l\'entrainement mais ameliore significativement la qualite des relations professionnelles et personnelles.');

INSERT INTO consultation_creneaux (prestation_id, praticien_id, start_time, end_time, is_booked, site_id) VALUES
(1, 3, NOW() + INTERVAL 2 DAY + INTERVAL '09:00' HOUR_MINUTE, NOW() + INTERVAL 2 DAY + INTERVAL '09:45' HOUR_MINUTE, TRUE, 1), 
(1, 3, NOW() + INTERVAL 2 DAY + INTERVAL '10:00' HOUR_MINUTE, NOW() + INTERVAL 2 DAY + INTERVAL '10:45' HOUR_MINUTE, FALSE, 1), 
(1, 11, NOW() + INTERVAL 3 DAY + INTERVAL '14:00' HOUR_MINUTE, NOW() + INTERVAL 3 DAY + INTERVAL '14:45' HOUR_MINUTE, TRUE, 2), 
(1, 11, NOW() + INTERVAL 3 DAY + INTERVAL '15:00' HOUR_MINUTE, NOW() + INTERVAL 3 DAY + INTERVAL '15:45' HOUR_MINUTE, FALSE, 2), 
(4, 11, NOW() + INTERVAL 4 DAY + INTERVAL '10:00' HOUR_MINUTE, NOW() + INTERVAL 4 DAY + INTERVAL '11:00' HOUR_MINUTE, TRUE, 2), 
(4, 11, NOW() + INTERVAL 4 DAY + INTERVAL '11:15' HOUR_MINUTE, NOW() + INTERVAL 4 DAY + INTERVAL '12:15' HOUR_MINUTE, FALSE, 2), 
(4, 3, NOW() + INTERVAL 5 DAY + INTERVAL '09:00' HOUR_MINUTE, NOW() + INTERVAL 5 DAY + INTERVAL '10:00' HOUR_MINUTE, FALSE, 1), 
(9, 3, NOW() + INTERVAL 6 DAY + INTERVAL '16:00' HOUR_MINUTE, NOW() + INTERVAL 6 DAY + INTERVAL '16:50' HOUR_MINUTE, TRUE, 1), 
(9, 3, NOW() + INTERVAL 6 DAY + INTERVAL '17:00' HOUR_MINUTE, NOW() + INTERVAL 6 DAY + INTERVAL '17:50' HOUR_MINUTE, FALSE, NULL), 
(7, 3, NOW() + INTERVAL 2 DAY + INTERVAL '11:00' HOUR_MINUTE, NOW() + INTERVAL 2 DAY + INTERVAL '12:00' HOUR_MINUTE, FALSE, 1), 
(7, 11, NOW() + INTERVAL 2 DAY + INTERVAL '12:15' HOUR_MINUTE, NOW() + INTERVAL 2 DAY + INTERVAL '13:15' HOUR_MINUTE, FALSE, 2),
(7, 3, NOW() + INTERVAL 5 DAY + INTERVAL '14:00' HOUR_MINUTE, NOW() + INTERVAL 5 DAY + INTERVAL '15:00' HOUR_MINUTE, TRUE, NULL), 
(11, 28, NOW() + INTERVAL 8 DAY + INTERVAL '13:00' HOUR_MINUTE, NOW() + INTERVAL 8 DAY + INTERVAL '13:20' HOUR_MINUTE, TRUE, NULL);

INSERT INTO interets_utilisateurs (nom, description) VALUES
('Sante Mentale', 'Conseils et ressources pour le bien-être psychologique'),
('Nutrition', 'Informations et astuces pour une alimentation saine'),
('Activite Physique', 'Motivation et idées pour rester actif'),
('Gestion du Stress', 'Techniques pour gérer la pression et l\'anxiete'),
('Sommeil', 'Améliorer la qualité et la quantité de sommeil'),
('Communication', 'Développer des compétences relationnelles efficaces'),
('Developpement Personnel', 'Ressources pour la croissance et l\'epanouissement personnel');

INSERT INTO prestataires_prestations (prestataire_id, prestation_id) VALUES
(3, 1), (3, 9), (3, 7), 
(11, 1), (11, 4), (11, 15), 
(27, 1), (27, 29), 
(28, 2), (28, 5), (28, 11), (28, 30), (28, 41), 
(29, 4), (29, 17), (29, 24), 
(30, 9), (30, 27), 
(31, 21), 
(32, 19), 
(33, 29), (33, 39), 
(34, 41); 


INSERT INTO habilitations (prestataire_id, type, nom_document, organisme_emission, date_obtention, date_expiration, statut) VALUES
(3, 'diplome', 'Master Psychologie Clinique', 'Universite Paris Cite', '2010-06-15', NULL, 'verifiee'),
(11, 'diplome', 'DUT Nutrition', 'IUT Lyon 1', '2012-07-01', NULL, 'verifiee'),
(27, 'certification', 'Certification Hypnotherapeute Ericksonienne', 'ARCHE Hypnose', '2018-11-20', '2028-11-19', 'verifiee'),
(28, 'certification', 'Professeur de Yoga 200h', 'Yoga Alliance', '2015-03-10', NULL, 'verifiee'),
(32, 'agrement', 'Formateur SST', 'INRS', '2020-01-25', '2026-01-24', 'en_attente_validation');


INSERT INTO prestataires_disponibilites (prestataire_id, type, date_debut, date_fin, heure_debut, heure_fin, jour_semaine, recurrence_fin, notes) VALUES
(3, 'recurrente', NULL, NULL, '09:00:00', '18:00:00', 1, NULL, 'Disponible les lundis'), 
(3, 'recurrente', NULL, NULL, '09:00:00', '12:00:00', 3, NULL, 'Disponible les mercredis matin'), 
(11, 'recurrente', NULL, NULL, '10:00:00', '17:00:00', 2, '2025-12-31', 'Disponible les mardis jusqu''a fin 2025'), 
(28, 'specifique', NOW() + INTERVAL 10 DAY, NOW() + INTERVAL 10 DAY + INTERVAL 1 DAY, NULL, NULL, NULL, NULL, 'Disponible pour atelier Yoga du Rire (Evenement ID 8)'),
(3, 'indisponible', NOW() + INTERVAL 1 MONTH, NOW() + INTERVAL 1 MONTH + INTERVAL 7 DAY, NULL, NULL, NULL, NULL, 'Conges annuels');


INSERT INTO rendez_vous (personne_id, prestation_id, praticien_id, date_rdv, duree, lieu, type_rdv, statut, notes) VALUES
(5, 1, 3, NOW() + INTERVAL 2 DAY + INTERVAL '09:00' HOUR_MINUTE, 45, 'Bureau Presta Test / Visio', 'visio', 'confirme', 'Premier RDV'), 
(6, 4, 11, NOW() + INTERVAL 4 DAY + INTERVAL '10:00' HOUR_MINUTE, 60, 'Cabinet Sante+ / Tel', 'telephone', 'planifie', 'Bilan nutritionnel'), 
(8, 7, 3, NOW() + INTERVAL 5 DAY + INTERVAL '14:00' HOUR_MINUTE, 60, 'Visio', 'visio', 'termine', 'Seance coaching OK'), 
(10, 9, 3, NOW() + INTERVAL 6 DAY + INTERVAL '16:00' HOUR_MINUTE, 50, 'Bureau Presta Leo / Site ID 1', 'presentiel', 'planifie', 'Correction praticien_id 30 -> 3'), 
(12, 11, 28, NOW() + INTERVAL 8 DAY + INTERVAL '13:00' HOUR_MINUTE, 20, 'Entreprise Tech Solutions', 'presentiel', 'confirme', 'Massage Amma sur site'), 
(17, 1, 11, NOW() + INTERVAL 3 DAY + INTERVAL '14:00' HOUR_MINUTE, 45, 'Bureau Sante Plus', 'presentiel', 'annule', 'Annule par le salarie'); 


INSERT INTO factures (entreprise_id, devis_id, numero_facture, date_emission, date_echeance, montant_total, montant_ht, tva, statut, mode_paiement, date_paiement) VALUES
(1, 1, 'FACT-2024-001', '2024-02-16', '2024-03-17', 1500.00, 1250.00, 20.00, 'payee', 'virement', '2024-03-10 10:00:00'),
(2, 2, 'FACT-2024-002', '2024-03-02', '2024-04-16', 2000.00, 1666.67, 20.00, 'payee', 'prelevement', '2024-04-15 11:00:00'),
(1, 4, 'FACT-2024-003', '2024-02-11', '2024-02-26', 3200.00, 2666.67, 20.00, 'retard', 'virement', NULL),
(3, 6, 'FACT-2024-004', '2024-02-26', '2024-03-27', 2800.00, 2333.33, 20.00, 'en_attente', 'carte', NULL),
(5, 8, 'FACT-2024-005', '2024-04-11', '2024-05-11', 55000.00, 45833.33, 20.00, 'en_attente', 'virement', NULL),
(7, 10, 'FACT-2024-006', '2024-04-21', '2024-06-20', 39000.00, 32500.00, 20.00, 'en_attente', 'virement', NULL),
(9, 12, 'FACT-2024-007', '2024-05-02', '2024-05-02', 4500.00, 3750.00, 20.00, 'payee', 'carte', NOW() - INTERVAL 3 DAY),
(11, 14, 'FACT-2024-008', '2024-05-11', '2024-06-10', 650.00, 541.67, 20.00, 'en_attente', 'prelevement', NULL),
(12, 15, 'FACT-2024-009', '2024-05-16', '2024-06-15', 2700.00, 2250.00, 20.00, 'annulee', NULL, NULL),
(13, 16, 'FACT-2024-010', '2024-05-21', '2024-07-05', 30000.00, 25000.00, 20.00, 'payee', 'virement', NOW() - INTERVAL 1 DAY),
(15, 17, 'FACT-2024-011', '2024-05-26', '2024-06-25', 57000.00, 47500.00, 20.00, 'impayee', 'virement', NULL),
(16, 18, 'FACT-2024-012', '2024-06-02', '2024-07-02', 2520.00, 2100.00, 20.00, 'en_attente', 'carte', NULL),
(18, 20, 'FACT-2024-013', '2024-06-11', '2024-07-11', 19500.00, 16250.00, 20.00, 'retard', 'prelevement', NULL),
(23, 23, 'FACT-2024-014', '2024-05-26', '2024-05-26', 4050.00, 3375.00, 20.00, 'payee', 'carte', NOW() - INTERVAL 2 HOUR),
(24, 24, 'FACT-2024-015', '2024-07-02', '2024-08-31', 27000.00, 22500.00, 20.00, 'en_attente', 'virement', NULL),
(4, 7, 'FACT-2024-016', '2024-04-06', '2024-05-06', 950.00, 791.67, 20.00, 'payee', 'virement', '2024-05-01 14:30:00'),
(19, 27, 'FACT-2024-017', '2024-03-15', '2024-04-14', 5000.00, 4166.67, 20.00, 'en_attente', 'virement', NULL),
(20, 21, 'FACT-2024-018', '2024-02-20', '2024-03-21', 1200.00, 1000.00, 20.00, 'payee', 'carte', NOW() - INTERVAL 10 DAY),
(21, 22, 'FACT-2024-019', '2024-05-25', '2024-06-24', 800.00, 666.67, 20.00, 'retard', 'virement', NULL),
(22, 28, 'FACT-2024-020', '2024-05-05', '2024-06-04', 60000.00, 50000.00, 20.00, 'impayee', 'prelevement', NULL),
(25, 29, 'FACT-2024-021', '2024-06-15', '2024-07-15', 42000.00, 35000.00, 20.00, 'payee', 'virement', NOW() - INTERVAL 5 DAY),
(26, 30, 'FACT-2024-022', '2024-06-01', '2024-07-01', 550.00, 458.33, 20.00, 'en_attente', 'carte', NULL),
(27, 25, 'FACT-2024-023', '2024-06-06', '2024-07-06', 3150.00, 2625.00, 20.00, 'annulee', NULL, NULL),
(28, 31, 'FACT-2024-024', '2024-03-05', '2024-04-04', 1800.00, 1500.00, 20.00, 'payee', 'prelevement', NOW() - INTERVAL 2 DAY),
(29, 32, 'FACT-2024-025', '2024-07-01', '2024-07-31', 4500.00, 3750.00, 20.00, 'en_attente', 'virement', NULL),
(30, 26, 'FACT-2024-026', '2024-06-12', '2024-07-27', 22500.00, 18750.00, 20.00, 'retard', 'prelevement', NULL);


INSERT INTO devis_prestations (devis_id, prestation_id, quantite, prix_unitaire_devis, description_specifique) VALUES
(1, 1, 10, 75.00, '10 consultations psy incluses'),
(1, 2, 5, 110.00, '5 ateliers Yoga'),
(2, 4, 15, 85.00, '15 consultations nutritionniste'),
(4, 6, 2, 250.00, '2 formations Ergonomie'),
(4, 7, 8, 140.00, '8 coachings de vie'),
(5, 11, 50, 20.00, '50 massages Amma prevus sur site'),
(8, 1, 500, 60.00, 'Pack 500 consultations psy Premium'),
(8, 11, 500, 15.00, 'Pack 500 massages Amma Premium');


INSERT INTO factures_prestataires (prestataire_id, numero_facture, date_facture, periode_debut, periode_fin, montant_total, statut, date_paiement) VALUES
(3, 'FP-2024-03-001', '2024-04-05', '2024-03-01', '2024-03-31', 225.00, 'impayee', NULL), 
(11, 'FP-2024-03-002', '2024-04-05', '2024-03-01', '2024-03-31', 85.00, 'payee', NOW() - INTERVAL 5 DAY), 
(28, 'FP-2024-04-001', '2024-05-05', '2024-04-01', '2024-04-30', 25.00, 'generation_attendue', NULL), 
(30, 'FP-2024-04-002', '2024-05-05', '2024-04-01', '2024-04-30', 70.00, 'impayee', NULL); 



INSERT INTO facture_prestataire_lignes (facture_prestataire_id, rendez_vous_id, description, montant) VALUES
(1, 1, 'Consultation Psychologique - M. Dupont', 75.00),
(1, 3, 'Coaching de Vie - P. Dubois', 150.00),
(2, 2, 'Consultation Nutritionniste - J. Martin', 85.00),
(3, 5, 'Massage Amma Assis - H. Moreau', 25.00),
(4, 4, 'Sophrologie Relaxation - J. Richard', 70.00);


INSERT INTO evenement_inscriptions (personne_id, evenement_id, statut) VALUES
(5, 1, 'inscrit'), 
(8, 1, 'inscrit'), 
(6, 2, 'inscrit'), 
(12, 4, 'inscrit'), 
(7, 5, 'annule'), 
(18, 6, 'inscrit'); 


INSERT INTO communaute_messages (communaute_id, personne_id, message) VALUES
(1, 5, 'Quelqu''un a essaye le cours de Yoga Avance ?'),
(2, 6, 'Motivation pour le semi-marathon ce week-end ! Qui court ?'),
(1, 8, 'La seance de meditation de ce matin etait top !'),
(2, 12, 'Nouveau record perso sur 10km ! :-)');

INSERT INTO communaute_messages (communaute_id, personne_id, message) VALUES
(1, 7, 'Des conseils pour tenir la posture de l\'arbre plus longtemps ?'),
(2, 10, 'Quelqu\'un a une bonne appli pour suivre ses parcours de course ?'),
(1, 9, 'La séance de méditation guidée d\'hier soir était vraiment apaisante.'),
(2, 18, 'Entraînement fractionné ce soir, qui est partant ?'),
(1, 17, 'Est-ce qu\'il y a un cours de Yoga prévu la semaine prochaine sur le site Sante+ ?'),
(2, 20, 'Besoin de motivation pour sortir courir avec ce temps ! Des astuces ?');

INSERT INTO support_tickets (entreprise_id, personne_id, sujet, message, statut) VALUES
(1, 5, 'Probleme connexion espace salarie', 'Bonjour, je n''arrive pas a me connecter depuis ce matin.', 'en_cours'),
(NULL, 7, 'Question sur une prestation', 'Est-ce que l''atelier Yoga est adapte aux grands debutants ?', 'nouveau'),
(3, NULL, 'Demande information contrat', 'Pouvez-vous nous renvoyer les details de notre contrat Bien-etre Corp ?', 'resolu'),
(NULL, NULL, 'Bug affichage page evenements', 'La liste des evenements ne s''affiche pas correctement sur mobile.', 'nouveau');


INSERT INTO signalements (sujet, description, statut) VALUES
('Commentaire inaproprie communaute', 'Un utilisateur a poste un message deplace dans le Running Club.', 'en_cours'),
('Lien mort page Conseils', 'Le lien vers l''article sur le sommeil ne fonctionne pas.', 'clos');

