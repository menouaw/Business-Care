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
('Gestion du stress au travail', '', 'Apprenez des techniques pour mieux gerer la pression et preserver votre equilibre.', 'Stress', 
'Le stress chronique peut avoir des effets nefaste sur votre sante physique et mentale.\n\nVoici quelques techniques simples :\n1. Respiration profonde : Inspirez lentement par le nez, retenez quelques secondes, expirez lentement par la bouche. Repetez 5 fois pour calmer le systeme nerveux.\n2. Pause active : Levez-vous et marchez quelques minutes toutes les heures. Changer d\'environnement aide a clarifier les idees.\n3. Priorisation : Utilisez la matrice d\'Eisenhower (urgent/important) pour organiser vos taches et vous concentrer sur l\'essentiel.\n4. Communication : Exprimez vos difficulte a votre manager ou a un collegue de confiance. Ne restez pas isole.\n5. Deconnexion : Definissez des limites claires entre travail et vie personnelle. Essayez de vous deconnecter (notifications, emails) en dehors des heures de bureau.\n\nN\'oubliez pas de faire des pauses regulières, meme courtes, pour deconnecter et recharger vos batteries.'),
('Ameliorer son sommeil', '', 'Des conseils pratiques pour retrouver un sommeil reparateur et une meilleure energie.', 'Sommeil',
'Un bon sommeil est crucial pour la concentration, l\'humeur et la sante generale.\n\nConseils :\n- Regularite : Couchez-vous et levez-vous a heures regulieres, meme le week-end, pour stabiliser votre horloge biologique.\n- Environnement : Creez un environnement propice au sommeil : chambre sombre, calme et fraiche (idealement entre 18-20°C).\n- Ecrans : Evitez les ecrans (telephone, tablette, ordinateur) au moins 30 a 60 minutes avant le coucher. La lumiere bleue perturbe la production de melatonine.\n- Alimentation et hydratation : Limitez la cafeine et l\'alcool, surtout en fin de journee. Evitez les repas lourds ou de trop boire juste avant de dormir.\n- Relaxation : Pratiquez une activite relaxante avant de dormir (lecture apaisante, musique douce, bain chaud, meditation legere).'),
('Alimentation equilibree au bureau', '', 'Comment bien manger au travail pour maintenir votre energie et votre concentration.', 'Nutrition',
'Manger sainement au bureau est possible et essentiel ! Cela booste votre energie, votre concentration et votre bien-etre general.\n\nIdees :\n- Preparation : Preparez vos dejeuners la veille : salades composees, soupes, plats maison rechauffes. C\'est plus sain et economique.\n- Snacks sains : Anticipez les petites faims avec des fruits frais, yaourts nature, oléagineux (amandes, noix), legumes croquants (carottes, concombre).\n- Hydratation : Buvez de l\'eau regulierement tout au long de la journee (visez 1,5 L). Une gourde sur le bureau aide !\n- Eviter les pieges : Limitez les distributeurs automatiques (souvent riches en sucre/sel) et les fast-foods trop frequents.\n- Pleine conscience : Prenez le temps de manger assis, loin de votre ecran. Machez lentement et savourez votre repas pour une meilleure digestion et sante.\n\nRecette rapide : Salade Quinoa-Poulet-Avocat\nIngrédients : Quinoa cuit, blanc de poulet grille coupe en des, 1/2 avocat en tranches, tomates cerises coupees en deux, quelques feuilles d\'epinards frais, vinaigrette legere (huile d\'olive, jus de citron, sel, poivre).\nMelangez le tout dans une boite hermetique. Simple, sain et delicieux !'),
('5 minutes de meditation guidee', '', 'Une courte pause meditativ pour recentrer votre esprit et apaiser le mental.', 'Stress',
'Installez-vous confortablement (chaise ou sol), fermez les yeux ou fixez un point devant vous avec un regard doux.\nPortez votre attention sur votre respiration. Sentez l\'air entrer par le nez et sortir. Observez le mouvement de votre ventre ou de votre poitrine.\nQuand des pensees, emotions ou sensations arrivent, c\'est normal. Observez-les sans jugement, comme des nuages passant dans le ciel, puis ramenez doucement votre attention a votre souffle.\nRestez ainsi pendant 5 minutes. Si cela semble long au debut, commencez par 2 ou 3 minutes.\nTerminez en reprenant conscience de votre corps et de la piece autour de vous. Sentez le calme qui s\'est installe.\nCette simple pratique reguliere peut reduire significativement le stress et ameliorer la concentration.'),
('Hydratation : pourquoi et comment ?', '', 'L\'importance vitale de boire de l\'eau et des astuces simples pour y parvenir.', 'Nutrition',
'Notre corps est compose majoritairement d\'eau (environ 60 %). Une bonne hydratation est vitale pour le fonctionnement de nos organes, notre niveau d\'energie, notre clarte mentale et la sante de notre peau.\n\nPourquoi est-ce si important ?\n- Transporte les nutriments essentiels aux cellules.\n- Elimine les toxines et les dechets metaboliques.\n- Regule la temperature corporelle (transpiration).\n- Lubrifie les articulations.\n- Aide a la concentration et previent les maux de tete.\n\nComment boire suffisamment ?\n- Gourde a portee de main : Gardez une bouteille ou une gourde d\'eau sur votre bureau et remplissez-la regulierement.\n- Boire avant la soif : N\'attendez pas d\'avoir soif, c\'est deja un signe de deshydratation legere.\n- Varier les plaisirs : Alternez avec des tisanes non sucrees, de l\'eau infusee (citron, menthe, concombre) pour changer.\n- Aliments riches en eau : Consommez des fruits et legumes comme le concombre, la pasteque, l\'orange, la salade.\n- Adapter selon les besoins : Augmentez votre apport en cas d\'activite physique, de forte chaleur ou de fievre.\nObjectif moyen : environ 1,5 a 2 L d\'eau pure par jour, a adapter individuellement.'),
('Communication non violente (CNV) : introduction', '', 'Les bases pour communiquer avec plus d\'empathie, de clarte et d\'efficacite.', 'Communication',
'La Communication Non Violente (CNV) est une approche developpee par Marshall Rosenberg qui aide a creer des relations basees sur le respect mutuel et la cooperation.\n\nElle repose sur 4 etapes cles pour exprimer ce qui se passe en nous et entendre l\'autre avec empathie :\n1. Observation (O) : Decrire les faits concrets et specifiques que nous observons, sans jugement ni interpretation. (Ex. : "Quand je vois des dossiers non classes sur le bureau commun...")\n2. Sentiment (S) : Exprimer l\'emotion ressenti face a cette observation. Utiliser "Je me sens..." (Ex. : "...je me sens un peu frustre(e)...")\n3. Besoin (B) : Identifier le besoin fondamental (autonomie, respect, clarte, ordre, soutien...) qui est satisfait ou insatisfait et qui est a l\'origine du sentiment. (Ex. : "...car j\'ai besoin d\'ordre et de clarte dans notre espace de travail partage.")\n4. Demande (D) : Formuler une demande concrete, positive, realisable et negociable, visant a satisfaire le besoin identifie. Preferer une demande a une exigence. (Ex. : "Serais-tu d\'accord pour que nous prenions 5 minutes ensemble pour decider comment organiser cet espace ?")\n\nEcoute empathique : La CNV s\'applique aussi a l\'ecoute. Tentez de deviner les sentiments et besoins de l\'autre derriere ses mots, meme s\'ils sont exprimes maladroitement.\n\nPratiquer la CNV demande de l\'entrainement mais ameliore significativement la qualite des relations professionnelles et personnelles.');

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
