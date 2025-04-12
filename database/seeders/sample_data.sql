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
TRUNCATE TABLE factures_prestataires;
TRUNCATE TABLE facture_prestataire_lignes;
TRUNCATE TABLE habilitations;
TRUNCATE TABLE prestataires_prestations;
TRUNCATE TABLE prestataires_disponibilites;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO roles (nom, description) VALUES
('admin', 'Administrateur systeme'),
('salarie', 'Salarie d\'une entreprise'),
('prestataire', 'Prestataire de services'),
('entreprise', 'Entreprise cliente');

INSERT INTO entreprises (nom, siret, adresse, code_postal, ville, telephone, email, site_web, logo_url, taille_entreprise, secteur_activite, date_creation) VALUES
('Tech Solutions SA', '12345678901234', '123 Rue de l\'Innovation', '75001', 'Paris', '01 23 45 67 89', 'contact@techsolutions.fr', 'www.techsolutions.fr', '/logos/techsolutions.png', '51-200', 'Technologie', '2020-01-15'),
('Sante Plus', '98765432109876', '456 Avenue de la Sante', '75002', 'Paris', '01 98 76 54 32', 'contact@santeplus.fr', 'www.santeplus.fr', '/logos/santeplus.png', '201-500', 'Sante', '2019-06-20'),
('Bien-etre Corp', '45678901234567', '789 Boulevard du Bien-etre', '75003', 'Paris', '01 45 67 89 01', 'contact@bienetrecorp.fr', 'www.bienetrecorp.fr', '/logos/bienetrecorp.png', '11-50', 'Bien-etre', '2021-03-10'),
('Entreprise 4', '11111111111111', 'Adresse 4', '75004', 'Paris', '0101010101', 'contact@entreprise4.fr', 'www.entreprise4.fr', '/logos/entreprise4.png', '1-10', 'Consulting', '2022-01-01'),
('Entreprise 5', '22222222222222', 'Adresse 5', '75005', 'Paris', '0202020202', 'contact@entreprise5.fr', 'www.entreprise5.fr', '/logos/entreprise5.png', '11-50', 'Finance', '2022-02-01'),
('InnovCorp', '33333333333333', '1 Tech Park', '75015', 'Paris', '0303030303', 'contact@innovcorp.fr', 'www.innovcorp.fr', '/logos/innovcorp.png', '201-500', 'Technologie', '2018-05-20'),
('Wellness Ltd', '44444444444444', '2 Bien-etre Plaza', '75008', 'Paris', '0404040404', 'contact@wellnessltd.fr', 'www.wellnessltd.fr', '/logos/wellnessltd.png', '51-200', 'Services', '2019-09-01'),
('Synergy Group', '55555555555555', '3 Union Square', '75009', 'Paris', '0505050505', 'contact@synergy.fr', 'www.synergy.fr', '/logos/synergy.png', '500+', 'Consulting', '2017-11-11'),
('EcoBuild', '66666666666666', '4 Green Avenue', '10000', 'Troyes', '0606060606', 'contact@ecobuild.fr', 'www.ecobuild.fr', '/logos/ecobuild.png', '11-50', 'Construction', '2020-07-14'),
('Azur Conseil', '77777777777777', '5 Promenade des Anglais', '06000', 'Nice', '0707070707', 'contact@azurconseil.fr', 'www.azurconseil.fr', '/logos/azurconseil.png', '1-10', 'Conseil', '2021-12-25'),
('BioSante France', '88888888888888', '6 Rue Pasteur', '75006', 'Paris', '0808080808', 'contact@biosante.fr', 'www.biosante.fr', '/logos/biosante.png', '51-200', 'Pharmaceutique', '2015-02-28'),
('Digital Wave', '99999999999999', '7 Silicon Alley', '75013', 'Paris', '0909090909', 'contact@digitalwave.fr', 'www.digitalwave.fr', '/logos/digitalwave.png', '11-50', 'Marketing Digital', '2022-08-10'),
('GastroNomie SARL', '10101010101010', '8 Place du Marche', '75018', 'Paris', '1010101010', 'contact@gastronomie.fr', 'www.gastronomie.fr', '/logos/gastronomie.png', '1-10', 'Restauration', '2023-01-20'),
('SportFit Club', '12121212121212', '9 Stade Olympique', '75016', 'Paris', '1212121212', 'contact@sportfit.fr', 'www.sportfit.fr', '/logos/sportfit.png', '11-50', 'Loisirs', '2019-04-15'),
('LogiTrans Express', '13131313131313', '10 Route Nationale', '10000', 'Troyes', '1313131313', 'contact@logitrans.fr', 'www.logitrans.fr', '/logos/logitrans.png', '51-200', 'Transport', '2016-06-30'),
('EducaForm', '14141414141414', '11 Rue des Ecoles', '75005', 'Paris', '1414141414', 'contact@educaform.fr', 'www.educaform.fr', '/logos/educaform.png', '11-50', 'Formation', '2021-09-01'),
('MediaProd', '15151515151515', '12 Avenue des Medias', '75002', 'Paris', '1515151515', 'contact@mediaprod.fr', 'www.mediaprod.fr', '/logos/mediaprod.png', '1-10', 'Audiovisuel', '2022-11-05'),
('Habitat Vert', '16161616161616', '13 Impasse Jardin', '75020', 'Paris', '1616161616', 'contact@habitatvert.fr', 'www.habitatvert.fr', '/logos/habitatvert.png', '11-50', 'Immobilier', '2018-10-10'),
('AutoPro Services', '17171717171717', '14 Boulevard Industriel', '10000', 'Troyes', '1717171717', 'contact@autopro.fr', 'www.autopro.fr', '/logos/autopro.png', '51-200', 'Automobile', '2017-03-19'),
('FinTech Banque', '18181818181818', '15 Place de la Bourse', '75002', 'Paris', '1818181818', 'contact@fintechbanque.fr', 'www.fintechbanque.fr', '/logos/fintechbanque.png', '201-500', 'Finance', '2020-10-22');

INSERT INTO prestations (nom, description, prix, duree, type, categorie, niveau_difficulte, capacite_max, materiel_necessaire, prerequis) VALUES
('Consultation Psychologique', 'Seance individuelle de 45 minutes avec un psychologue qualifie', 80.00, 45, 'consultation', 'Sante mentale', NULL, 1, 'Aucun', 'Aucun'),
('Yoga en Entreprise', 'Seance de yoga adaptee au milieu professionnel', 120.00, 60, 'atelier', 'Bien-etre physique', 'debutant', 20, 'Tapis de yoga', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', 150.00, 90, 'webinar', 'Formation', 'debutant', 50, 'Ordinateur, connexion internet', 'Aucun'),
('Atelier de Meditation', 'Seance de meditation guidee', 100.00, 60, 'atelier', 'Bien-etre mental', 'debutant', 15, 'Tapis de meditation', 'Aucun'),
('Formation Leadership', 'Developper ses competences en leadership', 200.00, 120, 'atelier', 'Developpement personnel', 'intermediaire', 25, 'Aucun', 'Aucun'),
('Coaching Carriere', 'Accompagnement individuel pour le developpement professionnel', 100.00, 60, 'consultation', 'Developpement personnel', NULL, 1, 'Aucun', 'CV a jour'),
('Atelier Nutrition Equilibree', 'Conseils pratiques pour une alimentation saine au travail', 130.00, 90, 'atelier', 'Sante physique', 'debutant', 18, 'Aucun', 'Aucun'),
('Massage Amma Assis', 'Massage relaxant sur chaise ergonomique (15 min par personne)', 15.00, 15, 'autre', 'Bien-etre physique', NULL, 1, 'Chaise Amma', 'Aucun'),
('Formation Risques Psycho-sociaux (RPS)', 'Sensibilisation et prevention des RPS en entreprise', 250.00, 180, 'atelier', 'Formation', 'intermediaire', 20, 'Support de cours', 'Aucun'),
('Team Building Escape Game', 'Jeu d\'evasion thematique pour renforcer la cohesion', 300.00, 90, 'evenement', 'Cohesion d\'equipe', 'intermediaire', 30, 'Salle equipee', 'Aucun'),
('Consultation Dietetique', 'Bilan nutritionnel personnalise et suivi', 90.00, 50, 'consultation', 'Sante physique', NULL, 1, 'Aucun', 'Aucun'),
('Sophrologie en Groupe', 'Seance collective pour apprendre a gerer le stress', 110.00, 60, 'atelier', 'Bien-etre mental', 'debutant', 12, 'Tenue confortable', 'Aucun'),
('Webinar Communication Non Violente (CNV)', 'Introduction aux principes de la CNV', 160.00, 90, 'webinar', 'Formation', 'debutant', 40, 'Ordinateur, connexion internet', 'Aucun'),
('Atelier Gestion du Temps', 'Techniques pour mieux organiser ses journees', 140.00, 120, 'atelier', 'Developpement personnel', 'debutant', 22, 'Papier, stylo', 'Aucun'),
('Conference Sante du Dos', 'Conseils posturaux et exercices preventifs', 180.00, 60, 'conference', 'Sante physique', 'debutant', 60, 'Aucun', 'Aucun'),
('Coaching Prise de Parole', 'Ameliorer son aisance et son impact a l\'oral', 120.00, 60, 'consultation', 'Developpement personnel', NULL, 1, 'Aucun', 'Aucun'),
('Pilates en Entreprise', 'Renforcement musculaire doux et controle postural', 125.00, 60, 'atelier', 'Bien-etre physique', 'intermediaire', 15, 'Tapis de sol', 'Aucun'),
('Formation Secourisme (PSC1)', 'Formation aux premiers secours', 220.00, 420, 'atelier', 'Formation', 'debutant', 10, 'Mannequins, materiel secourisme', 'Aucun'),
('Atelier Creativite', 'Stimuler l\'innovation et la resolution de problemes', 170.00, 120, 'atelier', 'Cohesion d\'equipe', 'debutant', 20, 'Materiel artistique divers', 'Aucun'),
('Team Building Jeu de Piste', 'Decouverte ludique d\'un quartier ou d\'un parc', 280.00, 150, 'evenement', 'Cohesion d\'equipe', 'debutant', 40, 'Smartphone avec data', 'Aucun'),
('Consultation Ergonomie Poste de Travail', 'Analyse et conseils pour adapter son poste', 85.00, 45, 'consultation', 'Sante physique', NULL, 1, 'Aucun', 'Photo du poste de travail'),
('Qi Gong en Entreprise', 'Gymnastique douce chinoise pour l\'energie et la detente', 115.00, 60, 'atelier', 'Bien-etre physique', 'debutant', 18, 'Aucun', 'Aucun'),
('Webinar Intelligence Emotionnelle', 'Comprendre et gerer ses emotions et celles des autres', 175.00, 90, 'webinar', 'Formation', 'intermediaire', 45, 'Ordinateur, connexion internet', 'Aucun'),
('Atelier Cuisine Saine et Rapide', 'Idees recettes pour des dejeuners equilibres au bureau', 155.00, 120, 'atelier', 'Sante physique', 'debutant', 10, 'Cuisine equipee, ingredients', 'Aucun'),
('Conference Cybersecurite au Quotidien', 'Sensibilisation aux risques et bonnes pratiques numeriques', 190.00, 60, 'conference', 'Formation', 'debutant', 70, 'Aucun', 'Aucun');

INSERT INTO personnes (nom, prenom, email, mot_de_passe, telephone, date_naissance, genre, photo_url, role_id, entreprise_id, statut, derniere_connexion) VALUES
('Admin', 'System', 'admin@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '', 1, NULL, 'actif', '2024-03-17 18:30:00'),
('Salarie', 'Test', 'salarie@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '', 2, NULL, 'actif', '2024-03-17 18:30:00'),
('Prestataire', 'Test', 'prestataire@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '', 3, NULL, 'actif', '2024-03-17 18:30:00'),
('Entreprise', 'Test', 'entreprise@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '00 00 00 00 00', '1990-01-01', 'Autre', '', 4, NULL, 'actif', '2024-03-17 18:30:00'),
('Dupont', 'Marie', 'marie.dupont@techsolutions.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '06 12 34 56 78', '1990-05-15', 'F', '', 2, 1, 'actif', '2024-03-17 14:30:00'),
('Durand', 'Sophie', 'sophie.durand@prestataire.com', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0600000001', '1988-03-15', 'F', '', 3, NULL, 'actif', NOW()),
('Leroy', 'Isabelle', 'isabelle.leroy@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0123456701', '1980-04-12', 'F', '', 4, 1, 'actif', NOW()),
('Personne 8', 'Prenom 8', 'personne8@entreprise4.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0808080808', '1990-08-08', 'M', '', 2, 4, 'actif', NOW()),
('Personne 9', 'Prenom 9', 'personne9@entreprise5.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0909090909', '1990-09-09', 'F', '', 2, 5, 'actif', NOW()),
('Martin', 'Luc', 'luc.martin@innovcorp.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0611223344', '1985-11-20', 'M', '', 2, 6, 'actif', NOW()),
('Bernard', 'Claire', 'claire.bernard@wellnessltd.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0622334455', '1992-07-03', 'F', '', 2, 7, 'actif', NOW()),
('Thomas', 'David', 'david.thomas@synergy.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0633445566', '1978-01-12', 'M', '', 4, 8, 'actif', NOW()),
('Petit', 'Laura', 'laura.petit@psychologue.pro', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0644556677', '1980-09-25', 'F', '', 3, NULL, 'actif', NOW()),
('Robert', 'Julien', 'julien.robert@ecobuild.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0655667788', '1995-03-30', 'M', '', 2, 9, 'actif', NOW()),
('Richard', 'Emilie', 'emilie.richard@azurconseil.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0666778899', '1989-06-18', 'F', '', 4, 10, 'actif', NOW()),
('Moreau', 'Nicolas', 'nicolas.moreau@coachsportif.net', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0677889900', '1983-12-01', 'M', '', 3, NULL, 'actif', NOW()),
('Laurent', 'Alice', 'alice.laurent@biosante.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0688990011', '1991-08-08', 'F', '', 2, 11, 'actif', NOW()),
('Simon', 'Mathieu', 'mathieu.simon@digitalwave.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0699001122', '1998-02-14', 'M', '', 2, 12, 'actif', NOW()),
('Michel', 'Camille', 'camille.michel@gastronomie.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0601122334', '1993-05-22', 'F', '', 4, 13, 'actif', NOW()),
('Lefevre', 'Antoine', 'antoine.lefevre@formateur.org', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0612233445', '1975-10-17', 'M', '', 3, NULL, 'actif', NOW()),
('Garcia', 'Manon', 'manon.garcia@sportfit.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0623344556', '1996-11-09', 'F', '', 2, 14, 'actif', NOW()),
('David', 'Hugo', 'hugo.david@logitrans.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0634455667', '1987-07-21', 'M', '', 2, 15, 'actif', NOW()),
('Martinez', 'Chloe', 'chloe.martinez@educaform.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0645566778', '1990-01-31', 'F', '', 4, 16, 'actif', NOW()),
('Girard', 'Lucas', 'lucas.girard@dieteticien.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0656677889', '1986-04-05', 'M', '', 3, NULL, 'actif', NOW()),
('Bonnet', 'Lea', 'lea.bonnet@mediaprod.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0667788990', '1994-09-13', 'F', '', 2, 17, 'actif', NOW()),
('Morel', 'Theo', 'theo.morel@habitatvert.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0678899001', '1982-03-06', 'M', '', 2, 18, 'actif', NOW()),
('Fournier', 'Juliette', 'juliette.fournier@autopro.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0689900112', '1997-06-28', 'F', '', 4, 19, 'actif', NOW()),
('Roux', 'Maxime', 'maxime.roux@sophrologue.com', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0690011223', '1981-11-14', 'M', '', 3, NULL, 'actif', NOW()),
('Vincent', 'Eva', 'eva.vincent@fintechbanque.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0602233445', '1999-01-07', 'F', '', 2, 20, 'actif', NOW());

INSERT INTO services (id, type, description, actif, ordre, max_effectif_inferieur_egal, activites_incluses, rdv_medicaux_inclus, chatbot_questions_limite, conseils_hebdo_personnalises, tarif_annuel_par_salarie) VALUES
(1, 'Starter Pack', 'Pour les petites equipes (jusqu\'a 30 salaries)', TRUE, 10, 30, 2, 1, 6, FALSE, 180.00),
(2, 'Basic Pack', 'Solution equilibree (jusqu\'a 250 salaries)', TRUE, 20, 250, 3, 2, 20, FALSE, 150.00),
(3, 'Premium Pack', 'Offre complete pour grandes entreprises (251+ salaries)', TRUE, 30, NULL, 4, 3, NULL, TRUE, 100.00);

INSERT INTO contrats (entreprise_id, service_id, date_debut, date_fin, nombre_salaries, statut, conditions_particulieres) VALUES
(1, 3, '2024-01-01', '2024-12-31', 150, 'actif', 'Acces a toutes les prestations premium'),
(2, 3, '2024-02-01', '2025-01-31', 300, 'actif', 'Acces illimite aux prestations'),
(3, 2, '2024-03-01', '2024-08-31', 35, 'actif', 'Acces aux prestations de base'),
(4, 1, '2024-04-01', '2025-03-31', 50, 'actif', 'Acces aux services de base'),
(13, 1, '2024-05-15', '2024-11-14', 5, 'en_attente', 'Conditions a valider'),
(17, 1, '2024-07-10', '2024-10-09', 9, 'resilie', 'Resiliation anticipee cause fermeture');

INSERT INTO contrats_prestations (contrat_id, prestation_id) VALUES
(1, 1),
(2, 2),
(3, 3),
(1, 2),
(2, 3),
(3, 1),
(4, 2);

INSERT INTO devis (entreprise_id, service_id, nombre_salaries_estimes, date_creation, date_validite, montant_total, montant_ht, tva, statut, conditions_paiement, delai_paiement, est_personnalise, notes_negociation) VALUES
(1, 3, 150, '2024-01-15', '2024-02-15', 18000.00, 15000.00, 20.00, 'accepte', 'Paiement a 30 jours', 30, TRUE, 'Conditions standards personnalisees.'),
(2, 3, 300, '2024-02-01', '2024-03-01', 36000.00, 30000.00, 20.00, 'accepte', 'Paiement a 45 jours', 45, TRUE, 'Conditions standards personnalisees.'),
(3, 2, 40, '2024-02-15', '2024-03-15', 7200.00, 6000.00, 20.00, 'refuse', 'Paiement a 30 jours', 30, TRUE, 'Conditions standards personnalisees.'),
(4, 1, 8, '2024-03-01', '2024-04-01', 1728.00, 1440.00, 20.00, 'en_attente', 'Paiement a 60 jours', 60, TRUE, 'Conditions standards personnalisees.'),
(5, 2, 25, '2024-04-05', '2024-05-05', 4500.00, 3750.00, 20.00, 'en_attente', 'Paiement a 30 jours', 30, TRUE, 'Conditions standards personnalisees.'),
(1, 2, 180, '2024-05-05', '2024-06-05', 32400.00, 27000.00, 20.00, 'en_attente', 'Paiement a 30 jours', 30, TRUE, 'Conditions standards personnalisees.'),
(2, 3, 400, '2024-05-10', '2024-06-10', 48000.00, 40000.00, 20.00, 'accepte', 'Paiement a 45 jours', 45, TRUE, 'Conditions standards personnalisees.'),
(4, 1, 5, '2024-05-20', '2024-06-20', 1080.00, 900.00, 20.00, 'accepte', 'Paiement a 30 jours', 30, TRUE, 'Conditions standards personnalisees.'),
(13, 1, 5, '2024-07-01', '2024-08-01', 1080.00, 900.00, 20.00, 'accepte', 'Paiement comptant', 0, TRUE, 'Conditions standards personnalisees.'),
(17, 1, 9, '2024-07-20', '2024-08-20', 1944.00, 1620.00, 20.00, 'accepte', 'Paiement a 45 jours', 45, TRUE, 'Conditions standards personnalisees.');

INSERT INTO factures (entreprise_id, devis_id, numero_facture, date_emission, date_echeance, montant_total, montant_ht, tva, statut, mode_paiement) VALUES
(1, NULL, 'FACT-2024-001', '2024-01-20', '2024-02-20', 18000.00, 15000.00, 20.00, 'payee', 'virement'),  
(2, NULL, 'FACT-2024-002', '2024-02-05', '2024-03-22', 36000.00, 30000.00, 20.00, 'payee', 'carte'),      
(4, NULL, 'FACT-2024-003', '2024-03-10', '2024-04-10', 1728.00, 1440.00, 20.00, 'en_attente', 'prelevement'), 
(5, NULL, 'FACT-2024-004', '2024-04-10', '2024-05-10', 3000.00, 2500.00, 20.00, 'payee', 'virement'), 
(1, NULL, 'FACT-2024-005', '2024-04-12', '2024-04-12', 24000.00, 20000.00, 20.00, 'payee', 'carte'), 
(2, NULL, 'FACT-2024-006', '2024-04-20', '2024-05-20', 2200.00, 1833.33, 20.00, 'en_attente', 'prelevement'), 
(3, NULL, 'FACT-2024-007', '2024-04-22', '2024-04-22', 4320.00, 3600.00, 20.00, 'payee', 'virement'), 
(4, NULL, 'FACT-2024-008', '2024-05-01', '2024-06-15', 1750.00, 1458.33, 20.00, 'annulee', 'virement'), 
(5, NULL, 'FACT-2024-009', '2024-05-05', '2024-07-05', 8640.00, 7200.00, 20.00, 'payee', 'prelevement'), 
(1, NULL, 'FACT-2024-010', '2024-05-10', '2024-06-10', 1350.00, 1125.00, 20.00, 'en_attente', 'carte'), 
(2, NULL, 'FACT-2024-011', '2024-05-12', '2024-06-27', 48000.00, 40000.00, 20.00, 'payee', 'virement'), 
(3, NULL, 'FACT-2024-012', '2024-05-20', '2024-05-20', 700.00, 583.33, 20.00, 'en_attente', 'carte'), 
(4, NULL, 'FACT-2024-013', '2024-05-22', '2024-06-22', 1080.00, 900.00, 20.00, 'payee', 'prelevement'),  
(6, NULL, 'FACT-2024-014', '2024-05-28', '2024-06-28', 27000.00, 22500.00, 20.00, 'en_attente', 'virement'), 
(3, NULL, 'FACT-2024-015', '2024-04-25', '2024-05-25', 1200.00, 1000.00, 20.00, 'annulee', 'virement'), 
(7, NULL, 'FACT-2024-016', '2024-06-05', '2024-07-20', 1600.00, 1333.33, 20.00, 'en_attente', 'prelevement'), 
(8, NULL, 'FACT-2024-017', '2024-06-07', '2024-06-07', 64800.00, 54000.00, 20.00, 'payee', 'carte'), 
(9, NULL, 'FACT-2024-018', '2024-06-12', '2024-07-12', 850.00, 708.33, 20.00, 'annulee', 'virement'), 
(10, NULL, 'FACT-2024-019', '2024-06-20', '2024-08-20', 3200.00, 2666.67, 20.00, 'en_attente', 'prelevement'), 
(11, NULL, 'FACT-2024-020', '2024-06-22', '2024-06-22', 35640.00, 29700.00, 20.00, 'payee', 'virement'), 
(12, NULL, 'FACT-2024-021', '2024-06-28', '2024-07-28', 1950.00, 1625.00, 20.00, 'en_attente', 'carte'), 
(13, NULL, 'FACT-2024-022', '2024-07-03', '2024-07-03', 1080.00, 900.00, 20.00, 'payee', 'carte'),    
(14, NULL, 'FACT-2024-023', '2024-07-08', '2024-08-23', 2700.00, 2250.00, 20.00, 'annulee', 'virement'), 
(15, NULL, 'FACT-2024-024', '2024-07-12', '2024-09-12', 23040.00, 19200.00, 20.00, 'payee', 'prelevement'), 
(16, NULL, 'FACT-2024-025', '2024-07-18', '2024-08-18', 1400.00, 1166.67, 20.00, 'en_attente', 'carte'), 
(17, NULL, 'FACT-2024-026', '2024-07-22', '2024-09-06', 1944.00, 1620.00, 20.00, 'payee', 'virement'),  
(18, NULL, 'FACT-2024-027', '2024-07-28', '2024-07-28', 900.00, 750.00, 20.00, 'annulee', 'virement'), 
(19, NULL, 'FACT-2024-028', '2024-08-05', '2024-09-05', 25080.00, 20900.00, 20.00, 'payee', 'prelevement'), 
(20, NULL, 'FACT-2024-029', '2024-08-08', '2024-10-08', 1550.00, 1291.67, 20.00, 'en_attente', 'carte');

INSERT INTO rendez_vous (personne_id, prestation_id, praticien_id, date_rdv, duree, lieu, type_rdv, statut, notes) VALUES
(5, 1, 6, '2024-03-20 10:00:00', 45, 'Cabinet 101', 'presentiel', 'planifie', 'Premiere consultation'),
(5, 2, 16, '2024-03-21 14:00:00', 60, 'Salle de yoga', 'presentiel', 'confirme', 'Seance de groupe'),
(5, 3, 20, '2024-03-22 15:00:00', 90, 'En ligne', 'visio', 'planifie', 'Webinar en groupe'), 
(6, 2, 16, '2024-04-01 09:00:00', 60, 'Salle de conference', 'presentiel', 'planifie', 'Session de groupe'), 
(8, 1, 13, '2024-04-05 11:00:00', 45, 'Cabinet 102', 'presentiel', 'termine', 'Suivi consultation'), 
(9, 1, 13, '2024-04-08 16:00:00', 45, 'En ligne', 'visio', 'termine', 'Consultation a distance'), 
(5, 4, 28, '2024-04-10 09:30:00', 60, 'Salle Zen', 'presentiel', 'confirme', 'Atelier meditation'),
(6, 4, 28, '2024-04-12 11:00:00', 60, 'En ligne', 'visio', 'planifie', 'Meditation guidee online'), 
(8, 5, 20, '2024-04-15 14:00:00', 120, 'Salle Formation A', 'presentiel', 'planifie', 'Session leadership'), 
(9, 5, 20, '2024-04-18 10:00:00', 120, 'En ligne', 'visio', 'confirme', 'Formation leadership a distance'), 
(10, 1, 13, '2024-04-22 10:00:00', 45, 'Cabinet 101', 'presentiel', 'planifie', 'Suivi'), 
(11, 2, 16, '2024-04-25 14:00:00', 60, 'Salle de yoga', 'presentiel', 'confirme', NULL), 
(12, 3, 20, '2024-04-29 15:00:00', 90, 'En ligne', 'visio', 'termine', 'Participation webinar stress'),
(14, 4, 28, '2024-05-02 09:30:00', 60, 'Salle Zen', 'presentiel', 'planifie', NULL), 
(15, 6, 20, '2024-05-06 10:00:00', 60, 'Bureau 205', 'presentiel', 'confirme', 'Coaching carriere Martin'),
(17, 7, 24, '2024-05-08 12:30:00', 90, 'Cuisine B', 'presentiel', 'termine', 'Atelier nutrition'),
(18, 8, 6, '2024-05-10 14:00:00', 15, 'Espace detente Etage 3', 'presentiel', 'planifie', 'Massage Amma'), 
(21, 9, 20, '2024-05-13 09:00:00', 180, 'Salle Formation B', 'presentiel', 'confirme', 'Formation RPS'), 
(22, 11, 24, '2024-05-15 11:00:00', 50, 'Cabinet Diet', 'presentiel', 'planifie', 'Consultation dietetique'), 
(23, 12, 28, '2024-05-17 18:00:00', 60, 'Salle Zen 2', 'presentiel', 'confirme', 'Sophrologie groupe'), 
(25, 13, 20, '2024-05-20 14:00:00', 90, 'En ligne', 'visio', 'planifie', 'Webinar CNV'),
(26, 14, 20, '2024-05-22 10:00:00', 120, 'Salle Atelier C', 'presentiel', 'termine', 'Atelier Gestion du Temps'), 
(27, 16, 20, '2024-05-24 16:00:00', 60, 'Bureau 310', 'presentiel', 'confirme', 'Coaching Prise de Parole'),
(29, 17, 16, '2024-05-27 12:00:00', 60, 'Salle Pilates', 'presentiel', 'planifie', 'Pilates'),
(10, 21, 6, '2024-05-29 09:00:00', 45, 'Poste de travail M. Martin', 'presentiel', 'termine', 'Consultation Ergo'), 
(11, 22, 6, '2024-05-31 13:00:00', 60, 'Parc Monceau', 'presentiel', 'confirme', 'Qi Gong exterieur'),
(12, 23, 13, '2024-06-03 11:00:00', 90, 'En ligne', 'visio', 'planifie', 'Webinar Intel Emotionnelle'),
(14, 24, 24, '2024-06-05 12:30:00', 120, 'Cuisine A', 'presentiel', 'confirme', 'Atelier Cuisine Saine');

INSERT INTO evaluations (personne_id, prestation_id, note, commentaire, date_evaluation) VALUES
(5, 1, 5, 'Excellent service, tres professionnel', '2024-04-21'),
(5, 2, 4, 'Tres a l\'ecoute, m\'a beaucoup aide.', '2024-04-23'),
(6, 2, 4, 'Bonne session, instructive', '2024-04-02'),
(8, 2, 5, 'Professeur de yoga tres dynamique.', '2024-05-10'),
(9, 1, 4, 'Consultation utile pour faire le point.', '2024-05-12'),
(5, 3, 4, 'Webinar interessant et bien structure.', '2024-04-16');

INSERT INTO evenements (titre, description, date_debut, date_fin, lieu, type, capacite_max, niveau_difficulte, materiel_necessaire, prerequis) VALUES
('Conference sur le Bien-etre au Travail', 'Une conference interactive sur les bonnes pratiques', '2024-04-01 09:00:00', '2024-04-01 11:00:00', 'Salle de Conference Paris', 'conference', 100, 'intermediaire', 'Aucun', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', '2024-04-15 14:00:00', '2024-04-15 15:30:00', 'En ligne', 'webinar', 50, 'debutant', 'Ordinateur, connexion internet', 'Aucun'),
('Atelier de Communication', 'Ameliorer ses competences en communication', '2024-05-01 10:00:00', '2024-05-01 12:00:00', 'Salle de reunion', 'atelier', 20, 'intermediaire', 'Aucun', 'Aucun'),
('Defi Sportif: Course d\'orientation', 'Course d\'orientation par equipes en foret', '2024-05-15 09:00:00', '2024-05-15 13:00:00', 'Foret de Fontainebleau', 'defi_sportif', 60, 'intermediaire', 'Tenue de sport, boussole', 'Bonne condition physique'),
('Conference Nutrition & Performance', 'Comment optimiser son alimentation pour etre plus performant', '2024-06-01 14:00:00', '2024-06-01 15:30:00', 'Auditorium BC', 'conference', 80, 'debutant', 'Aucun', 'Aucun'),
('Atelier Sophrologie', 'Apprendre des techniques de relaxation par la sophrologie', '2024-06-10 18:00:00', '2024-06-10 19:00:00', 'Salle Zen', 'atelier', 15, 'debutant', 'Tenue confortable', 'Aucun'),
('Webinar Sommeil Reparateur', 'Conseils pratiques pour ameliorer la qualite de son sommeil', '2024-06-20 11:00:00', '2024-06-20 12:00:00', 'En ligne', 'webinar', 70, 'debutant', 'Ordinateur, connexion internet', 'Aucun'),
('Defi Sportif: Tournoi de Volley', 'Tournoi amical de volley-ball inter-entreprises', '2024-07-05 14:00:00', '2024-07-05 18:00:00', 'Gymnase Municipal', 'defi_sportif', 48, 'debutant', 'Tenue de sport', 'Aucun'),
('Conference Gestion du Temps', 'Optimiser son organisation et sa productivite', '2024-07-15 09:30:00', '2024-07-15 11:00:00', 'Salle de Conference Paris', 'conference', 90, 'intermediaire', 'Aucun', 'Aucun'),
('Atelier Cuisine Saine', 'Preparer des repas equilibres et rapides pour le midi', '2024-08-01 12:30:00', '2024-08-01 14:00:00', 'Cuisine BC', 'atelier', 12, 'debutant', 'Tablier', 'Aucun'),
('Webinar Prevention TMS', 'Identifier et prevenir les troubles musculo-squelettiques', '2024-08-15 10:00:00', '2024-08-15 11:30:00', 'En ligne', 'webinar', 60, 'intermediaire', 'Ordinateur, connexion internet', 'Aucun'),
('Evenement Team Building Creatif', 'Atelier peinture collectif pour renforcer la cohesion', '2024-09-05 15:00:00', '2024-09-05 17:00:00', 'Atelier d\'artiste partenaire', 'autre', 25, 'debutant', 'Aucun', 'Aucun'),
('Conference Securite Numerique', 'Les bonnes pratiques pour proteger ses donnees', '2024-09-20 14:00:00', '2024-09-20 15:00:00', 'Auditorium BC', 'conference', 100, 'debutant', 'Aucun', 'Aucun'),
('Atelier Premiers Secours Mental', 'Reconnaitre et reagir face a la detresse psychologique', '2024-10-02 09:00:00', '2024-10-02 12:00:00', 'Salle Formation A', 'atelier', 16, 'intermediaire', 'Support de cours', 'Aucun'),
('Webinar Deconnexion Digitale', 'Gerer son utilisation des ecrans et preserver son equilibre', '2024-10-16 11:30:00', '2024-10-16 12:30:00', 'En ligne', 'webinar', 55, 'debutant', 'Ordinateur, connexion internet', 'Aucun'),
('Defi Sportif: Randonnee Urbaine', 'Decouverte culturelle et sportive de la ville', '2024-10-26 10:00:00', '2024-10-26 13:00:00', 'Depart Place de la Concorde', 'defi_sportif', 50, 'debutant', 'Bonnes chaussures', 'Aucun'),
('Conference Neurosciences et Apprentissage', 'Comment le cerveau apprend et comment l\'optimiser', '2024-11-07 10:00:00', '2024-11-07 11:30:00', 'Amphi BC Troyes', 'conference', 75, 'intermediaire', 'Aucun', 'Aucun'),
('Atelier Yoga du Rire', 'Liberer les tensions et booster la bonne humeur', '2024-11-18 13:00:00', '2024-11-18 13:45:00', 'Salle Polyvalente', 'atelier', 30, 'debutant', 'Aucun', 'Aucun'),
('Webinar Gerer les Conflits', 'Approches constructives pour desamorcer les tensions', '2024-11-28 14:30:00', '2024-11-28 16:00:00', 'En ligne', 'webinar', 40, 'intermediaire', 'Ordinateur, connexion internet', 'Aucun'),
('Evenement Solidaire: Collecte de Jouets', 'Mobilisation pour une association partenaire', '2024-12-01 09:00:00', '2024-12-15 18:00:00', 'Hall d\'accueil BC Paris', 'autre', NULL, NULL, 'Jouets en bon etat', 'Aucun'),
('Defi Sportif: Initiation Escalade', 'Decouverte de l\'escalade en salle', '2024-12-06 18:00:00', '2024-12-06 20:00:00', 'Salle d\'escalade partenaire', 'defi_sportif', 15, 'debutant', 'Tenue de sport', 'Certificat medical recommande'),
('Conference Bilan Annee & Perspectives', 'Evenement interne BC pour les clients', '2024-12-18 17:00:00', '2024-12-18 19:00:00', 'Grand Auditorium BC', 'conference', 200, NULL, 'Aucun', 'Invitation'),
('Atelier DIY Cosmetiques Naturels', 'Fabriquer ses propres produits de soin', '2025-01-10 14:00:00', '2025-01-10 16:00:00', 'Atelier BC', 'atelier', 10, 'debutant', 'Materiel fourni', 'Aucun'),
('Webinar Ergonomie du Teletravail', 'Optimiser son espace de travail a domicile', '2025-01-22 10:30:00', '2025-01-22 11:30:00', 'En ligne', 'webinar', 65, 'debutant', 'Ordinateur, connexion internet', 'Aucun'),
('Defi Sportif: Tournoi de Badminton', 'Competition amicale individuelle ou en double', '2025-02-07 17:00:00', '2025-02-07 20:00:00', 'Gymnase Universitaire', 'defi_sportif', 32, 'intermediaire', 'Tenue de sport, raquette', 'Aucun'),
('Conference Mieux se Connaitre (MBTI)', 'Introduction au modele MBTI pour la connaissance de soi', '2025-02-19 09:00:00', '2025-02-19 10:30:00', 'Salle Conference Nice', 'conference', 50, 'debutant', 'Aucun', 'Aucun'),
('Atelier Gestion des Emotions', 'Identifier, comprendre et reguler ses emotions', '2025-03-05 15:00:00', '2025-03-05 17:00:00', 'Salle Calme', 'atelier', 18, 'debutant', 'Aucun', 'Aucun'),
('Webinar Initiation a la Pleine Conscience', 'Techniques simples pour etre present a l\'instant', '2025-03-19 12:00:00', '2025-03-19 12:45:00', 'En ligne', 'webinar', 80, 'debutant', 'Ordinateur, connexion internet', 'Aucun'),
('Evenement Plantation d\'Arbres', 'Action ecologique en partenariat', '2025-03-29 09:30:00', '2025-03-29 12:30:00', 'Foret Regionale', 'autre', 40, 'debutant', 'Gants, bottes', 'Inscription prealable');

INSERT INTO communautes (nom, description, type, niveau, capacite_max) VALUES
('Yoga & Meditation', 'Groupe de pratique du yoga et de la meditation', 'bien_etre', 'debutant', 20),
('Running Club', 'Club de course a pied pour tous niveaux', 'sport', 'intermediaire', 30),
('Club de Lecture', 'Groupe de lecture et discussion', 'autre', 'debutant', 15),
('Developpement Personnel', 'Echanges et ateliers sur le dev perso', 'autre', 'intermediaire', 25),
('Nutrition Saine', 'Partage de recettes et conseils nutrition', 'sante', 'debutant', 40),
('Defi Sportif Inter-entreprises', 'Organisation de defis sportifs collectifs', 'sport', 'avance', 50);

INSERT INTO dons (personne_id, montant, type, description, date_don, statut) VALUES
(5, 50.00, 'financier', 'Don pour le programme de bien-etre', '2024-03-01', 'valide'),
(5, NULL, 'materiel', 'Don de materiel informatique (ecran)', '2024-03-15', 'valide'),
(6, 100.00, 'financier', 'Don pour le programme de lecture', '2024-04-01', 'valide'),
(8, 25.00, 'financier', 'Soutien projet environnemental', '2024-05-05', 'valide'),
(9, NULL, 'materiel', 'Don de livres pour le club lecture', '2024-05-18', 'en_attente'),
(6, 75.00, 'financier', 'Contribution a la caisse solidaire', '2024-05-20', 'valide');

INSERT INTO notifications (personne_id, titre, message, type, lien, lu, date_lecture) VALUES
(5, 'Nouveau Rendez-vous', 'Votre rendez-vous a ete confirme', 'success', '/rendez-vous/1', false, NULL),
(5, 'Paiement Reçu', 'Votre paiement a ete reçu avec succes', 'success', '/factures/1', true, '2024-03-17 10:30:00'),
(6, 'Nouvelle Evaluation', 'Votre evaluation a ete enregistree', 'info', '/evaluations/1', false, NULL);

INSERT INTO factures_prestataires (id, prestataire_id, numero_facture, date_facture, periode_debut, periode_fin, montant_total, statut, date_paiement) VALUES
(1, 6, 'FP-202404-001', '2024-05-01', '2024-04-01', '2024-04-30', 370.00, 'impayee', NULL);

INSERT INTO facture_prestataire_lignes (facture_prestataire_id, rendez_vous_id, description, montant) VALUES
(1, 4, 'RDV - Yoga en Entreprise', 120.00),
(1, 18, 'RDV - Formation Risques Psycho-sociaux (RPS)', 250.00);

INSERT INTO factures_prestataires (id, prestataire_id, numero_facture, date_facture, periode_debut, periode_fin, montant_total, statut, date_paiement) VALUES
(2, 13, 'FP-202404-002', '2024-05-01', '2024-04-01', '2024-04-30', 415.00, 'payee', '2024-05-10 14:00:00');

INSERT INTO facture_prestataire_lignes (facture_prestataire_id, rendez_vous_id, description, montant) VALUES
(2, 5, 'RDV - Consultation Psychologique', 80.00),
(2, 6, 'RDV - Consultation Psychologique', 80.00),
(2, 11, 'RDV - Consultation Psychologique', 80.00),
(2, 27, 'RDV - Webinar Intelligence Emotionnelle', 175.00);

INSERT INTO factures_prestataires (id, prestataire_id, numero_facture, date_facture, periode_debut, periode_fin, montant_total, statut, date_paiement) VALUES
(3, 20, 'FP-202404-003', '2024-05-01', '2024-04-01', '2024-04-30', 700.00, 'impayee', NULL);

INSERT INTO facture_prestataire_lignes (facture_prestataire_id, rendez_vous_id, description, montant) VALUES
(3, 3, 'RDV - Webinar Gestion du Stress', 150.00),
(3, 9, 'RDV - Formation Leadership', 200.00),
(3, 10, 'RDV - Formation Leadership', 200.00),
(3, 13, 'RDV - Webinar Communication Non Violente (CNV)', 160.00);

INSERT INTO factures_prestataires (id, prestataire_id, numero_facture, date_facture, periode_debut, periode_fin, montant_total, statut, date_paiement) VALUES
(4, 20, 'FP-202405-001', '2024-06-01', '2024-05-01', '2024-05-31', 540.00, 'impayee', NULL);

INSERT INTO facture_prestataire_lignes (facture_prestataire_id, rendez_vous_id, description, montant) VALUES
(4, 15, 'RDV - Coaching Carriere', 100.00),
(4, 21, 'RDV - Webinar Communication Non Violente (CNV)', 160.00),
(4, 22, 'RDV - Atelier Gestion du Temps', 140.00),
(4, 26, 'RDV - Coaching Prise de Parole', 120.00);


INSERT INTO habilitations (prestataire_id, type, nom_document, document_url, organisme_emission, date_obtention, date_expiration, statut, notes) VALUES
(6, 'diplome', 'Master Psychologie Clinique', '/docs/habilitations/sophie_durand_master_psy.pdf', 'Universite Paris V', '2012-06-20', NULL, 'verifiee', 'Diplome valide'),
(6, 'certification', 'Certification Massage Amma', '/docs/habilitations/sophie_durand_certif_amma.pdf', 'Ecole Zen Attitude', '2018-03-10', '2026-03-09', 'verifiee', 'Certificat a renouveler'),
(13, 'diplome', 'Doctorat en Psychologie du Travail', '/docs/habilitations/laura_petit_doctorat.pdf', 'Universite Lyon II', '2008-09-15', NULL, 'verifiee', NULL),
(16, 'certification', 'BEES Metiers de la Forme', '/docs/habilitations/nicolas_moreau_bees.pdf', 'CREPS IDF', '2005-05-30', NULL, 'verifiee', 'Equivalent BPJEPS AF'),
(16, 'certification', 'Certification Pilates Matwork I & II', '/docs/habilitations/nicolas_moreau_pilates.pdf', 'Balanced Body', '2015-11-10', '2025-11-09', 'verifiee', NULL),
(20, 'diplome', 'Master Ressources Humaines', '/docs/habilitations/antoine_lefevre_master_rh.pdf', 'IAE Paris', '2002-07-01', NULL, 'verifiee', NULL),
(20, 'certification', 'Formateur Professionnel d\'Adultes (FPA)', '/docs/habilitations/antoine_lefevre_fpa.pdf', 'AFPA', '2010-12-15', NULL, 'verifiee', NULL),
(24, 'diplome', 'BTS Dietetique', '/docs/habilitations/lucas_girard_bts.pdf', 'Lycee Rabelais', '2009-06-25', NULL, 'en_attente_validation', 'Document en attente de validation'),
(28, 'certification', 'Certification Sophrologue RNCP', '/docs/habilitations/maxime_roux_rncp.pdf', 'Institut de Formation a la Sophrologie', '2014-09-01', '2024-08-31', 'expiree', 'Certification expirée, demande de renouvellement en cours');



INSERT INTO prestataires_prestations (prestataire_id, prestation_id) VALUES
(6, 1), (6, 8), (6, 12), (6, 21), (6, 22), (Psychologue/Massage/Sophro/Ergo/Qi Gong?)
(13, 1), (13, 6), (13, 9), (13, 23), (Psychologue/Coach Carriere/RPS/Intel Emotionnelle)
(16, 2), (16, 17), (16, 22), (Coach Sportif - Yoga/Pilates/Qi Gong)
(20, 3), (20, 5), (20, 6), (20, 9), (20, 13), (20, 14), (20, 16), (20, 18), (20, 23), (20, 25), (Formateur - divers)
(24, 7), (24, 11), (24, 24), (Dieteticien)
(28, 4), (28, 12); (Sophrologue)


INSERT INTO prestataires_disponibilites (prestataire_id, type, date_debut, date_fin, heure_debut, heure_fin, jour_semaine, recurrence_fin, notes) VALUES
(ID 6)
(6, 'recurrente', NULL, NULL, '09:00:00', '12:00:00', 1, NULL, 'Lundi matin'), 
(6, 'recurrente', NULL, NULL, '14:00:00', '18:00:00', 1, NULL, 'Lundi apres-midi'), -midi
(6, 'recurrente', NULL, NULL, '09:00:00', '12:00:00', 3, '2024-12-31', 'Mercredi matin (jusqu''a fin 2024)'), (jusqu'a fin 2024) 
(6, 'specifique', '2024-08-01 00:00:00', '2024-08-15 23:59:59', NULL, NULL, NULL, NULL, 'Conges Aout'), 
(6, 'indisponible', '2024-09-16 09:00:00', '2024-09-16 18:00:00', NULL, NULL, NULL, NULL, 'Formation interne'), 

(ID 13)
(13, 'recurrente', NULL, NULL, '09:00:00', '17:00:00', 2, NULL, 'Mardi'), 
(13, 'recurrente', NULL, NULL, '09:00:00', '17:00:00', 4, NULL, 'Jeudi'), 

(ID 16)
(16, 'recurrente', NULL, NULL, '12:00:00', '14:00:00', 1, NULL, 'Lundi midi'), 
(16, 'recurrente', NULL, NULL, '18:00:00', '20:00:00', 3, NULL, 'Mercredi soir'), 
(16, 'recurrente', NULL, NULL, '12:00:00', '14:00:00', 5, NULL, 'Vendredi midi'), 

(ID 20)
(20, 'recurrente', NULL, NULL, '09:00:00', '18:00:00', 1, NULL, 'Lundi'),
(20, 'recurrente', NULL, NULL, '09:00:00', '18:00:00', 2, NULL, 'Mardi'),
(20, 'recurrente', NULL, NULL, '09:00:00', '18:00:00', 3, NULL, 'Mercredi'),
(20, 'recurrente', NULL, NULL, '09:00:00', '18:00:00', 4, NULL, 'Jeudi'),
(20, 'recurrente', NULL, NULL, '09:00:00', '18:00:00', 5, NULL, 'Vendredi'),
(20, 'indisponible', '2024-10-07 00:00:00', '2024-10-11 23:59:59', NULL, NULL, NULL, NULL, 'Semaine de formation externe'),

(ID 24)
(24, 'recurrente', NULL, NULL, '10:00:00', '16:00:00', 5, NULL, 'Vendredi'), 
(24, 'specifique', '2024-11-04 10:00:00', '2024-11-04 12:00:00', NULL, NULL, NULL, NULL, 'Disponible exceptionnellement Lundi 4 Nov matin'), 

(ID 28)
(28, 'recurrente', NULL, NULL, '17:00:00', '20:00:00', 2, NULL, 'Mardi soir'), 
(28, 'recurrente', NULL, NULL, '17:00:00', '20:00:00', 4, NULL, 'Jeudi soir'); 