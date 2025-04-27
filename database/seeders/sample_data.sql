-- source C:/MAMP/htdocs/Business-Care/database/seeders/sample_data.sql


USE business_care;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE utilisateur_interets_conseils;
TRUNCATE TABLE consultation_creneaux;
TRUNCATE TABLE evenement_inscriptions;
TRUNCATE TABLE communaute_messages;
TRUNCATE TABLE contrats_prestations;
TRUNCATE TABLE remember_me_tokens;
TRUNCATE TABLE logs;
TRUNCATE TABLE notifications;
TRUNCATE TABLE evaluations;
TRUNCATE TABLE dons;
TRUNCATE TABLE rendez_vous;
TRUNCATE TABLE factures;
TRUNCATE TABLE devis;
TRUNCATE TABLE devis_prestations;
TRUNCATE TABLE contrats;
TRUNCATE TABLE preferences_utilisateurs;
TRUNCATE TABLE remember_me_tokens;
TRUNCATE TABLE api_tokens;
TRUNCATE TABLE personnes;
TRUNCATE TABLE sites; 
TRUNCATE TABLE entreprises;
TRUNCATE TABLE prestations;
TRUNCATE TABLE communautes;
TRUNCATE TABLE evenements;
TRUNCATE TABLE conseils;
TRUNCATE TABLE associations;
TRUNCATE TABLE signalements;
TRUNCATE TABLE services;
TRUNCATE TABLE roles;
TRUNCATE TABLE support_tickets;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO roles (nom, description) VALUES
('admin', 'Administrateur systeme'),
('salarie', 'Salarie d\'une entreprise'),
('prestataire', 'Prestataire de services / Praticien'), 
('entreprise', 'Representant Entreprise cliente'); 

INSERT INTO entreprises (id, nom, siret, adresse, code_postal, ville, telephone, email, site_web, logo_url, taille_entreprise, secteur_activite, date_creation) VALUES
(1, 'Tech Solutions SA', '12345678901234', '123 Rue de l\'Innovation', '75001', 'Paris', '01 23 45 67 89', 'contact@techsolutions.fr', 'www.techsolutions.fr', '/logos/techsolutions.png', '51-200', 'Technologie', '2020-01-15'),
(2, 'Sante Plus', '98765432109876', '456 Avenue de la Sante', '75002', 'Paris', '01 98 76 54 32', 'contact@santeplus.fr', 'www.santeplus.fr', '/logos/santeplus.png', '201-500', 'Sante', '2019-06-20'),
(3, 'Bien-etre Corp', '45678901234567', '789 Boulevard du Bien-etre', '75003', 'Paris', '01 45 67 89 01', 'contact@bienetrecorp.fr', 'www.bienetrecorp.fr', '/logos/bienetrecorp.png', '11-50', 'Bien-etre', '2021-03-10');

INSERT INTO sites (id, nom, adresse, code_postal, ville, entreprise_id) VALUES
(1, 'Siège Social Tech Solutions', '123 Rue de l\'Innovation', '75001', 'Paris', 1),
(2, 'Agence Sante Plus Centre', '456 Avenue de la Sante', '75002', 'Paris', 2),
(3, 'Bureau Bien-etre Corp', '789 Boulevard du Bien-etre', '75003', 'Paris', 3),
(4, 'Agence Troyes Bien-etre Corp', '1 Rue de la République', '10000', 'Troyes', 3);


INSERT INTO prestations (nom, description, prix, duree, type, categorie, niveau_difficulte, capacite_max, materiel_necessaire, prerequis) VALUES
('Consultation Psychologique', 'Seance individuelle de 45 minutes avec un psychologue qualifie', 80.00, 45, 'consultation', 'Sante mentale', NULL, 1, 'Aucun', 'Aucun'),
('Yoga en Entreprise', 'Seance de yoga adaptee au milieu professionnel', 120.00, 60, 'atelier', 'Bien-etre physique', 'debutant', 20, 'Tapis de yoga', 'Aucun'),
('Webinar Gestion du Stress', 'Formation en ligne sur la gestion du stress', 150.00, 90, 'webinar', 'Formation', 'debutant', 50, 'Ordinateur, connexion internet', 'Aucun'),
('Consultation Nutritionniste', 'Bilan et conseils personnalisés avec un nutritionniste', 90.00, 60, 'consultation', 'Nutrition', NULL, 1, 'Aucun', 'Aucun'),
('Meditation Pleine Conscience', 'Atelier pratique de méditation', 100.00, 60, 'atelier', 'Bien-etre mental', 'debutant', 15, 'Coussin (optionnel)', 'Aucun'), 
('Formation Ergonomie Bureau', 'Adapter son poste de travail', 250.00, 120, 'atelier', 'Ergonomie', 'debutant', 12, 'Aucun', 'Aucun'), 
('Coaching de Vie Individuel', 'Accompagnement personnalisé objectifs', 150.00, 60, 'consultation', 'Developpement personnel', NULL, 1, 'Aucun', 'Aucun'), 
('Atelier Communication Assertive', 'Mieux communiquer ses besoins', 180.00, 120, 'atelier', 'Communication', 'intermediaire', 15, 'Aucun', 'Aucun'), 
('Sophrologie Relaxation', 'Techniques de relaxation profonde', 70.00, 50, 'consultation', 'Bien-etre mental', NULL, 1, 'Aucun', 'Aucun'), 
('Conference Sommeil Réparateur', 'Comprendre et améliorer son sommeil', 300.00, 90, 'conference', 'Sommeil', 'debutant', 100, 'Aucun', 'Aucun'), 
('Massage Amma Assis', 'Massage court sur chaise', 25.00, 20, 'consultation', 'Bien-etre physique', NULL, 1, 'Aucun', 'Aucun'); 

INSERT INTO personnes (nom, prenom, email, mot_de_passe, telephone, date_naissance, genre, photo_url, role_id, entreprise_id, site_id, statut, derniere_connexion) VALUES
('Admin', 'System', 'admin@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0000000000', '1990-01-01', 'Autre', '/photos/admin.jpg', 1, NULL, NULL, 'actif', NOW()), 
('Salarie', 'Test', 'salarie@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0000000000', '1990-01-01', 'Autre', '/photos/salarie.jpg', 2, NULL, NULL, 'actif', NOW()), 
('Prestataire', 'Test', 'prestataire@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0000000000', '1990-01-01', 'Autre', '/photos/prestataire.jpg', 3, NULL, NULL, 'actif', NOW()), 
('Entreprise', 'Test', 'entreprise@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0000000000', '1990-01-01', 'Autre', '/photos/entreprise.jpg', 4, NULL, NULL, 'actif', NOW()), 
('Dupont', 'Marie', 'marie.dupont@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0612345678', '1990-05-15', 'F', '/photos/marie.dupont.jpg', 2, 1, 1, 'actif', NOW() - INTERVAL 1 DAY), 
('Martin', 'Jean', 'jean.martin@santeplus.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0623456789', '1985-08-20', 'M', '/photos/jean.martin.jpg', 2, 2, 2, 'actif', NOW() - INTERVAL 2 DAY), 
('Bernard', 'Chloé', 'chloe.bernard@bienetrecorp.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0634567890', '1992-03-10', 'F', '/photos/chloe.bernard.jpg', 2, 3, 4, 'actif', NOW() - INTERVAL 3 DAY), 
('Dubois', 'Pierre', 'pierre.dubois@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0645678901', '1988-12-05', 'M', '/photos/pierre.dubois.jpg', 2, 1, 1, 'actif', NOW() - INTERVAL 4 DAY), 
('Robert', 'Lucas', 'lucas.robert@santeplus.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0667890123', '1991-04-15', 'M', '/photos/lucas.robert.jpg', 2, 2, 2, 'actif', NOW() - INTERVAL 5 DAY), 
('Richard', 'Julie', 'julie.richard@bienetrecorp.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0678901234', '1987-09-30', 'F', '/photos/julie.richard.jpg', 2, 3, 3, 'actif', NOW() - INTERVAL 6 DAY), 
('Durand', 'Sophie', 'sophie.durand@santeplus.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0689012345', '1993-02-20', 'F', '/photos/sophie.durand.jpg', 3, NULL, NULL, 'actif', NOW() - INTERVAL 7 DAY), 
('Moreau', 'Hugo', 'hugo.moreau@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0690123456', '1996-06-25', 'M', '/photos/hugo.moreau.jpg', 2, 1, 1, 'actif', NOW() - INTERVAL 8 DAY), 
('Duamel', 'Heloise', 'duamelle.heloise@bienetrecorp.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0612345677', '1995-03-15', 'F', '/photos/heloise.duamel.jpg', 2, 3, 3, 'actif', NOW() - INTERVAL 9 DAY), 
('Dupois', 'Jacques', 'jacques.dupois@techsolutions.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0612345666', '1995-03-15', 'M', '/photos/jacques.dupois.jpg', 2, 1, 1, 'inactif', NOW() - INTERVAL 10 DAY),
('Representant', 'SantePlus', 'rep.santeplus@businesscare.fr', '$2y$10$CGP1gfg0khtXjAZcJFC6iO3oYisjwlPfkm8tQ8Q/OxWpFdR7tOiqO', '0000000001', '1980-01-01', 'Autre', '/photos/default.jpg', 4, 2, 2, 'actif', NOW());

INSERT INTO contrats (entreprise_id, service_id, date_debut, date_fin, nombre_salaries, statut, conditions_particulieres) VALUES
(1, 3, '2024-01-01', '2025-12-31', 150, 'actif', 'Acces a toutes les prestations premium'),
(2, 2, '2024-02-01', NULL, 300, 'actif', 'Acces illimite aux prestations'),
(3, 1, '2024-03-01', '2025-08-31', 35, 'actif', 'Acces aux prestations de base');

INSERT INTO devis (entreprise_id, date_creation, date_validite, montant_total, montant_ht, tva, statut, conditions_paiement, delai_paiement) VALUES
(1, '2024-01-15', '2024-02-15', 1500.00, 1250.00, 20.00, 'accepte', 'Paiement a 30 jours', 30), 
(2, '2024-02-01', '2024-03-01', 2000.00, 1666.67, 20.00, 'accepte', 'Paiement a 45 jours', 45), 
(3, '2024-02-15', '2024-03-15', 1800.00, 1500.00, 20.00, 'refuse', 'Paiement a 30 jours', 30), 
(1, '2024-01-10', '2024-02-10', 3200.00, 2666.67, 20.00, 'accepte', 'Paiement a 15 jours', 15), 
(2, '2024-02-20', '2024-03-20', 2500.00, 2083.33, 20.00, 'en_attente', 'Paiement a 30 jours', 30), 
(3, '2024-01-25', '2024-02-25', 2800.00, 2333.33, 20.00, 'accepte', 'Paiement a 30 jours', 30); 

INSERT INTO evaluations (personne_id, prestation_id, note, commentaire, date_evaluation) VALUES
(5, 1, 5, 'Excellent service, tres professionnel', '2024-04-21'),
(6, 1, 4, 'Très à l\'écoute, m\'a beaucoup aidé.', '2024-04-23'),
(8, 6, 5, 'Formation ergo très claire et pratique.', '2024-04-24');

INSERT INTO evenements (titre, description, date_debut, date_fin, lieu, type, capacite_max, niveau_difficulte, materiel_necessaire, prerequis, site_id) VALUES
('Conference Bien-etre Paris', 'Conference interactive sur site', NOW() + INTERVAL 1 WEEK, NOW() + INTERVAL 1 WEEK + INTERVAL 2 HOUR, 'Salle Paris A', 'conference', 100, 'intermediaire', 'Aucun', 'Aucun', 1),
('Webinar Gestion du Stress', 'Formation en ligne accessible à tous', NOW() + INTERVAL 2 WEEK, NOW() + INTERVAL 2 WEEK + INTERVAL 90 MINUTE, 'En ligne', 'webinar', 50, 'debutant', 'PC', 'Aucun', NULL),
('Atelier Ergonomie Sante+', 'Amenager son espace de travail', NOW() + INTERVAL 3 WEEK, NOW() + INTERVAL 3 WEEK + INTERVAL 2 HOUR, 'Centre Sante+ Salle B', 'atelier', 30, 'debutant', 'Aucun', 'Aucun', 2); 

INSERT INTO communautes (nom, description, type, niveau, capacite_max) VALUES
('Yoga & Meditation', 'Groupe de pratique du yoga et de la meditation', 'bien_etre', 'debutant', 20), 
('Running Club', 'Club de course a pied pour tous niveaux', 'sport', 'intermediaire', 30); 

INSERT INTO dons (personne_id, association_id, montant, type, description, date_don, statut) VALUES
(5, 1, 50.00, 'financier', 'Don pour le programme de bien-etre', '2024-03-01', 'valide'),
(6, 2, NULL, 'materiel', 'Don de materiel informatique (ecran)', '2024-03-15', 'valide'),
(7, 3, 100.00, 'financier', 'Soutien au programme de sante mentale', '2024-03-20', 'valide'),
(2, 1, 25.00, 'financier', 'Petit don pour Salarie Test', '2024-05-12', 'valide');

INSERT INTO notifications (personne_id, titre, message, type, lien, lu, date_lecture) VALUES
(5, 'Nouveau Rendez-vous', 'Votre rendez-vous a ete confirme', 'success', '/rendez-vous/1', false, NULL),
(6, 'Paiement Reçu', 'Votre paiement a ete reçu avec succes', 'success', '/factures/1', true, NOW() - INTERVAL 5 DAY),
(7, 'Nouvel evenement', 'Un nouvel evenement est disponible', 'info', '/evenements/2', false, NULL),
(2, 'Bienvenue Salarie Test!', 'Votre compte est prêt.', 'info', '/mon-profil.php', false, NULL);

INSERT INTO logs (personne_id, action, details, ip_address, created_at) VALUES
(5, 'login', 'Connexion réussie', '192.168.1.10', NOW() - INTERVAL 5 DAY),
(6, 'rdv_creation', 'Création RDV ID 2 (Yoga)', '192.168.1.11', NOW() - INTERVAL 4 DAY),
(7, 'profile_update', 'Mise à jour du numéro de téléphone', '192.168.1.12', NOW() - INTERVAL 3 DAY),
(1, 'admin_action', 'Désactivation contrat ID 3', '127.0.0.1', NOW() - INTERVAL 2 DAY),
(2, 'login', 'Connexion réussie', '192.168.1.20', NOW() - INTERVAL 1 HOUR);

INSERT INTO contrats_prestations (contrat_id, prestation_id) VALUES
(1, 1), 
(2, 2), 
(3, 3); 

INSERT INTO services (id, type, description, actif, ordre, tarif_annuel_par_salarie, prix_base_indicatif) VALUES
(1, 'Starter Pack', 'Pour les petites équipes (jusqu\'à 30 salariés)', TRUE, 10, 180.00, 100.00),
(2, 'Basic Pack', 'Solution équilibrée (jusqu\'à 250 salariés)', TRUE, 20, 150.00, 500.00),
(3, 'Premium Pack', 'Offre complète pour grandes entreprises (251+ salariés)', TRUE, 30, 100.00, 1000.00);

INSERT INTO conseils (titre, icone, resume, categorie, contenu) VALUES
('Gestion du Stress au Travail', 'fas fa-brain', 'Apprenez des techniques pour mieux gérer la pression.', 'Stress', 'Le stress chronique peut avoir des effets néfastes sur votre santé physique et mentale.\n\nVoici quelques techniques simples :\n1. Respiration profonde : Inspirez lentement par le nez, retenez quelques secondes, expirez lentement par la bouche. Répétez 5 fois.\n2. Pause active : Levez-vous et marchez quelques minutes toutes les heures.\n3. Priorisation : Utilisez la matrice d\'Eisenhower (urgent/important) pour organiser vos tâches.\n4. Communication : Exprimez vos difficultés à votre manager ou à un collègue de confiance.\n\nN\'oubliez pas de faire des pauses régulières, même courtes, pour déconnecter.'),
('Améliorer son Sommeil', 'fas fa-moon', 'Des conseils pratiques pour retrouver un sommeil réparateur.', 'Sommeil', 'Un bon sommeil est crucial pour la concentration, l\'humeur et la santé générale.\n\nConseils :\n- Couchez-vous et levez-vous à heures régulières, même le week-end.\n- Créez un environnement propice au sommeil : chambre sombre, calme et fraîche.\n- Évitez les écrans (téléphone, tablette, ordinateur) au moins 30 minutes avant le coucher.\n- Limitez la caféine et l\'alcool, surtout en fin de journée.\n- Pratiquez une activité relaxante avant de dormir (lecture, musique douce, bain chaud).'),
('Alimentation Équilibrée au Bureau', 'fas fa-apple-alt', 'Comment bien manger au travail, même pressé.', 'Nutrition', 'Manger sainement au bureau est possible ! Cela booste votre énergie et votre concentration.\n\nIdées :\n- Préparez vos déjeuners : salades composées, soupes, plats maison réchauffés.\n- Snacks sains : fruits frais, yaourts nature, oléagineux (amandes, noix), légumes croquants.\n- Hydratez-vous : buvez de l\'eau régulièrement tout au long de la journée.\n- Évitez les distributeurs automatiques et les fast-foods trop fréquents.\n\n**Recette Rapide : Salade Quinoa-Poulet-Avocat**\nIngrédients : Quinoa cuit, blanc de poulet grillé coupé en dés, 1/2 avocat en tranches, tomates cerises coupées en deux, quelques feuilles d\'épinards frais, vinaigrette légère (huile d\'olive, jus de citron, sel, poivre).\nMélangez le tout dans une boîte hermétique. Simple, sain et délicieux !'),
('L\'Importance de l\'Activité Physique', 'fas fa-running', 'Intégrer l\'exercice dans votre routine quotidienne.', 'Activité Physique', 'L\'activité physique est essentielle pour le corps et l\'esprit. Elle aide à réduire le stress, améliorer le sommeil et maintenir un poids santé.\n\nComment bouger plus :\n- Privilégiez les escaliers à l\'ascenseur.\n- Descendez un arrêt de bus/métro plus tôt et marchez.\n- Profitez de la pause déjeuner pour faire une courte marche.\n- Fixez-vous des objectifs réalisables : 30 minutes de marche rapide par jour, par exemple.\n- Trouvez une activité qui vous plaît : natation, danse, vélo, randonnée...');

INSERT INTO utilisateur_interets_conseils (personne_id, categorie_conseil) VALUES
(7, 'Stress'),            
(7, 'Activité Physique'),  
(7, 'Nutrition'),         
(5, 'Stress'),            
(5, 'Sommeil'),           
(5, 'Nutrition'),         
(6, 'Activité Physique'),  
(6, 'Stress');            

INSERT INTO consultation_creneaux (prestation_id, praticien_id, start_time, end_time, is_booked, site_id) VALUES
(1, 3, NOW() + INTERVAL 2 DAY + INTERVAL '09:00' HOUR_MINUTE, NOW() + INTERVAL 2 DAY + INTERVAL '09:45' HOUR_MINUTE, FALSE, 1), 
(1, 3, NOW() + INTERVAL 2 DAY + INTERVAL '10:00' HOUR_MINUTE, NOW() + INTERVAL 2 DAY + INTERVAL '10:45' HOUR_MINUTE, FALSE, 1), 
(1, 11, NOW() + INTERVAL 3 DAY + INTERVAL '14:00' HOUR_MINUTE, NOW() + INTERVAL 3 DAY + INTERVAL '14:45' HOUR_MINUTE, TRUE, 2), 
(1, 11, NOW() + INTERVAL 3 DAY + INTERVAL '15:00' HOUR_MINUTE, NOW() + INTERVAL 3 DAY + INTERVAL '15:45' HOUR_MINUTE, FALSE, 2), 
(4, 11, NOW() + INTERVAL 4 DAY + INTERVAL '10:00' HOUR_MINUTE, NOW() + INTERVAL 4 DAY + INTERVAL '11:00' HOUR_MINUTE, FALSE, 2), 
(4, 11, NOW() + INTERVAL 4 DAY + INTERVAL '11:15' HOUR_MINUTE, NOW() + INTERVAL 4 DAY + INTERVAL '12:15' HOUR_MINUTE, FALSE, 2), 
(4, 3, NOW() + INTERVAL 5 DAY + INTERVAL '09:00' HOUR_MINUTE, NOW() + INTERVAL 5 DAY + INTERVAL '10:00' HOUR_MINUTE, FALSE, 1), 
(9, 3, NOW() + INTERVAL 6 DAY + INTERVAL '16:00' HOUR_MINUTE, NOW() + INTERVAL 6 DAY + INTERVAL '16:50' HOUR_MINUTE, FALSE, 1), 
(9, 3, NOW() + INTERVAL 6 DAY + INTERVAL '17:00' HOUR_MINUTE, NOW() + INTERVAL 6 DAY + INTERVAL '17:50' HOUR_MINUTE, FALSE, NULL), 
(7, 3, NOW() + INTERVAL 2 DAY + INTERVAL '11:00' HOUR_MINUTE, NOW() + INTERVAL 2 DAY + INTERVAL '12:00' HOUR_MINUTE, FALSE, 1), 
(7, 11, NOW() + INTERVAL 2 DAY + INTERVAL '12:15' HOUR_MINUTE, NOW() + INTERVAL 2 DAY + INTERVAL '13:15' HOUR_MINUTE, FALSE, 2), 
(7, 3, NOW() + INTERVAL 4 DAY + INTERVAL '16:00' HOUR_MINUTE, NOW() + INTERVAL 4 DAY + INTERVAL '17:00' HOUR_MINUTE, FALSE, NULL); 
