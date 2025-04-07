-- source C:/MAMP/htdocs/Business-Care/database/schemas/business_care.sql

CREATE DATABASE business_care CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE business_care;

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nom (nom)
);

CREATE TABLE entreprises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    siret VARCHAR(14) UNIQUE,
    adresse TEXT,
    code_postal VARCHAR(10),
    ville VARCHAR(100),
    telephone VARCHAR(20),
    email VARCHAR(255),
    site_web VARCHAR(255),
    logo_url VARCHAR(255),
    taille_entreprise ENUM('1-10', '11-50', '51-200', '201-500', '500+') NULL DEFAULT NULL,
    secteur_activite VARCHAR(100),
    date_creation DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_siret (siret),
    INDEX idx_ville (ville)
);

CREATE TABLE personnes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    date_naissance DATE,
    genre ENUM('M', 'F', 'Autre'),
    photo_url VARCHAR(255),
    role_id INT NOT NULL,
    entreprise_id INT,
    statut ENUM('actif', 'inactif', 'en_attente', 'suspendu') DEFAULT 'actif',
    derniere_connexion DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id),
    INDEX idx_email (email),
    INDEX idx_role (role_id),
    INDEX idx_entreprise (entreprise_id)
);

CREATE TABLE prestations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    duree INT,
    type ENUM('conference', 'webinar', 'atelier', 'consultation', 'evenement', 'autre') NOT NULL,
    categorie VARCHAR(100),
    niveau_difficulte ENUM('debutant', 'intermediaire', 'avance'),
    capacite_max INT,
    materiel_necessaire TEXT,
    prerequis TEXT,
    date_heure_disponible DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_categorie (categorie)
);

CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL UNIQUE, -- Ex: 'Starter Pack', 'Consultation Ponctuelle'
    description TEXT,                 -- Ex: 'Pour les petites équipes (jusqu\'à 30 salariés)'
    actif BOOLEAN DEFAULT TRUE,       -- Pour activer/désactiver l'offre dans la liste
    ordre INT DEFAULT 0,              -- Pour définir l'ordre d'affichage
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nom (nom),
    INDEX idx_actif (actif)
);

CREATE TABLE contrats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entreprise_id INT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE,
    montant_mensuel DECIMAL(10,2),
    nombre_salaries INT,
    type_contrat ENUM('standard', 'premium', 'entreprise', 'starter', 'basic') NOT NULL,
    statut ENUM('actif', 'expire', 'resilie', 'en_attente') DEFAULT 'actif',
    conditions_particulieres TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id),
    INDEX idx_dates (date_debut, date_fin),
    INDEX idx_statut (statut)
);

CREATE TABLE devis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entreprise_id INT NOT NULL,
    date_creation DATE NOT NULL,
    date_validite DATE,
    montant_total DECIMAL(10,2) NOT NULL,
    montant_ht DECIMAL(10,2),
    tva DECIMAL(5,2),
    statut ENUM('en_attente', 'accepte', 'refuse', 'expire') DEFAULT 'en_attente',
    conditions_paiement TEXT,
    delai_paiement INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id),
    INDEX idx_dates (date_creation, date_validite),
    INDEX idx_statut (statut)
);

CREATE TABLE factures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entreprise_id INT NOT NULL,
    devis_id INT NULL,
    contrat_id INT NULL,
    numero_facture VARCHAR(50) UNIQUE,
    date_emission DATE NOT NULL,
    date_echeance DATE,
    montant_total DECIMAL(10,2) NOT NULL,
    montant_ht DECIMAL(10,2),
    tva DECIMAL(5,2),
    statut ENUM('en_attente', 'payee', 'annulee', 'retard', 'impayee') DEFAULT 'en_attente',
    mode_paiement ENUM('virement', 'carte', 'prelevement'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id),
    FOREIGN KEY (devis_id) REFERENCES devis(id),
    FOREIGN KEY (contrat_id) REFERENCES contrats(id),
    INDEX idx_numero (numero_facture),
    INDEX idx_dates (date_emission, date_echeance),
    INDEX idx_statut (statut)
);

CREATE TABLE rendez_vous (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    prestation_id INT NOT NULL,
    praticien_id INT NULL,
    date_rdv DATETIME NOT NULL,
    duree INT NOT NULL,
    lieu VARCHAR(255),
    type_rdv ENUM('presentiel', 'visio', 'telephone'),
    statut ENUM('planifie', 'confirme', 'annule', 'termine', 'no_show') DEFAULT 'planifie',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personne_id) REFERENCES personnes(id),
    FOREIGN KEY (prestation_id) REFERENCES prestations(id),
    FOREIGN KEY (praticien_id) REFERENCES personnes(id),
    INDEX idx_date (date_rdv),
    INDEX idx_statut (statut),
    INDEX idx_praticien (praticien_id)
);

CREATE TABLE evenements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NULL,
    lieu VARCHAR(255),
    type ENUM('conference', 'webinar', 'atelier', 'defi_sportif', 'autre') NOT NULL,
    capacite_max INT NULL,
    niveau_difficulte ENUM('debutant', 'intermediaire', 'avance') NULL,
    materiel_necessaire TEXT NULL,
    prerequis TEXT NULL,
    prestation_liee_id INT NULL,
    organisateur_id INT NULL,
    cout_participation DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prestation_liee_id) REFERENCES prestations(id),
    INDEX idx_dates (date_debut, date_fin),
    INDEX idx_type (type)
);

CREATE TABLE communautes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('sport', 'bien_etre', 'sante', 'autre') NOT NULL,
    niveau ENUM('debutant', 'intermediaire', 'avance') NULL,
    capacite_max INT NULL,
    est_ouverte BOOLEAN DEFAULT TRUE,
    animateur_principal_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (animateur_principal_id) REFERENCES personnes(id),
    INDEX idx_type (type)
);

CREATE TABLE dons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    association_id INT NULL,
    montant DECIMAL(10,2) NULL,
    type ENUM('financier', 'materiel') NOT NULL,
    description TEXT NULL,
    date_don DATE NOT NULL,
    statut ENUM('en_attente', 'valide', 'refuse') DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personne_id) REFERENCES personnes(id),
    INDEX idx_date (date_don),
    INDEX idx_type (type)
);

CREATE TABLE evaluations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    prestation_id INT NULL,
    rendez_vous_id INT NULL,
    evenement_id INT NULL,
    prestataire_id INT NULL,
    note INT CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    date_evaluation DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personne_id) REFERENCES personnes(id),
    FOREIGN KEY (prestation_id) REFERENCES prestations(id),
    FOREIGN KEY (rendez_vous_id) REFERENCES rendez_vous(id) ON DELETE SET NULL,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE SET NULL,
    FOREIGN KEY (prestataire_id) REFERENCES personnes(id),
    INDEX idx_date (date_evaluation),
    INDEX idx_evaluation_cible (prestation_id, rendez_vous_id, evenement_id, prestataire_id)
);

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error', 'rappel_rdv', 'nouvel_evenement', 'message_communaute') NOT NULL,
    lien VARCHAR(255) NULL,
    lu BOOLEAN DEFAULT FALSE,
    date_lecture DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personne_id) REFERENCES personnes(id),
    INDEX idx_personne_lu (personne_id, lu),
    INDEX idx_type (type)
);

CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personne_id) REFERENCES personnes(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_date (created_at)
);

CREATE TABLE remember_me_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES personnes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token),
    INDEX idx_expires (expires_at)
);

CREATE TABLE preferences_utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    langue ENUM('fr', 'en') DEFAULT 'fr',
    notif_email BOOLEAN DEFAULT TRUE,
    notif_push BOOLEAN DEFAULT TRUE,
    theme ENUM('clair', 'sombre', 'systeme') DEFAULT 'systeme',
    tutoriel_vu BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (personne_id) REFERENCES personnes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_personne_id (personne_id)
);

CREATE TABLE contrats_prestations (
    contrat_id INT NOT NULL,
    prestation_id INT NOT NULL,
    quantite_incluse INT NULL,
    frequence ENUM('unique', 'mois', 'trimestre', 'an') NULL,
    PRIMARY KEY (contrat_id, prestation_id),
    FOREIGN KEY (contrat_id) REFERENCES contrats(id) ON DELETE CASCADE,
    FOREIGN KEY (prestation_id) REFERENCES prestations(id) ON DELETE CASCADE
);

/*
CREATE TABLE prestataires_prestations (
    prestataire_id INT NOT NULL,
    prestation_id INT NOT NULL,
    habilitation_verifiee BOOLEAN DEFAULT FALSE,
    tarif_negocie DECIMAL(10,2) NULL,
    PRIMARY KEY (prestataire_id, prestation_id),
    FOREIGN KEY (prestataire_id) REFERENCES personnes(id) ON DELETE CASCADE,
    FOREIGN KEY (prestation_id) REFERENCES prestations(id) ON DELETE CASCADE
);

CREATE TABLE evenements_participants (
    evenement_id INT NOT NULL,
    personne_id INT NOT NULL,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut_inscription ENUM('inscrit', 'annule', 'present', 'absent') DEFAULT 'inscrit',
    notes_participant TEXT NULL,
    PRIMARY KEY (evenement_id, personne_id),
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (personne_id) REFERENCES personnes(id) ON DELETE CASCADE,
    INDEX idx_personne (personne_id),
    INDEX idx_statut (statut_inscription)
);

CREATE TABLE signalements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entreprise_id INT NULL,
    personne_id INT NULL,
    categorie VARCHAR(100),
    description TEXT NOT NULL,
    date_signalement DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('nouveau', 'en_cours_investigation', 'en_attente_reponse', 'resolu', 'classe_sans_suite') DEFAULT 'nouveau',
    priorite ENUM('basse', 'moyenne', 'haute', 'urgente') DEFAULT 'moyenne',
    assigne_a INT NULL,
    resolution_details TEXT NULL,
    date_resolution DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE SET NULL,
    FOREIGN KEY (personne_id) REFERENCES personnes(id) ON DELETE SET NULL,
    FOREIGN KEY (assigne_a) REFERENCES personnes(id) ON DELETE SET NULL,
    INDEX idx_statut (statut),
    INDEX idx_priorite (priorite),
    INDEX idx_date (date_signalement),
    INDEX idx_assigne (assigne_a)
);

CREATE TABLE participations_benevoles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    association_id INT NOT NULL,
    action_details TEXT NOT NULL,
    date_action DATE NOT NULL,
    duree_realisee_heures DECIMAL(4,1) NULL,
    statut ENUM('prevu', 'realise', 'annule_par_salarie', 'annule_par_asso') DEFAULT 'prevu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personne_id) REFERENCES personnes(id) ON DELETE CASCADE,
    INDEX idx_personne (personne_id),
    INDEX idx_date_action (date_action),
    INDEX idx_statut (statut)
);

CREATE TABLE conseils (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,                    -- Titre accrocheur du conseil
    contenu TEXT NOT NULL,                          -- Le texte détaillé du conseil
    resume TEXT NULL,                               -- Court résumé ou accroche (optionnel)
    categorie VARCHAR(100) NULL,                    -- Catégorie thématique (ex: 'Bien-être mental', 'Nutrition', 'Activité physique', 'Gestion du stress')
    mots_cles VARCHAR(255) NULL,                    -- Mots-clés pour la recherche (séparés par des virgules, par exemple)
    auteur_nom VARCHAR(100) NULL,                   -- Nom de l'auteur si externe ou si on ne veut pas lier à 'personnes'
    auteur_personne_id INT NULL,                    -- Qui a rédigé le conseil (admin BC, prestataire?). Référence la table 'personnes'
    date_publication DATE,                          -- Date à laquelle le conseil est visible
    est_publie BOOLEAN DEFAULT TRUE,                -- Permet de gérer les brouillons ou de dépublier un conseil
    image_url VARCHAR(255) NULL,                    -- URL d'une image d'illustration (optionnel)
    lien_externe VARCHAR(255) NULL,                 -- Lien vers une ressource externe si pertinent (optionnel)
    ordre_affichage INT DEFAULT 0,                  -- Pour trier l'affichage des conseils si nécessaire
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auteur_personne_id) REFERENCES personnes(id) ON DELETE SET NULL, -- Garde le conseil si l'auteur est supprimé
    INDEX idx_categorie (categorie),
    INDEX idx_publication (est_publie, date_publication),
    INDEX idx_auteur (auteur_personne_id)
);

*/