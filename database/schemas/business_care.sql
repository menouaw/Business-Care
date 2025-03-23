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
    taille_entreprise ENUM('1-10', '11-50', '51-200', '201-500', '500+'),
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_categorie (categorie)
);

CREATE TABLE contrats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entreprise_id INT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE,
    montant_mensuel DECIMAL(10,2),
    nombre_salaries INT,
    type_contrat ENUM('standard', 'premium', 'entreprise') NOT NULL,
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
    devis_id INT,
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
    INDEX idx_numero (numero_facture),
    INDEX idx_dates (date_emission, date_echeance),
    INDEX idx_statut (statut)
);

CREATE TABLE rendez_vous (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    prestation_id INT NOT NULL,
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
    INDEX idx_date (date_rdv),
    INDEX idx_statut (statut)
);

CREATE TABLE evenements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME,
    lieu VARCHAR(255),
    type ENUM('conference', 'webinar', 'atelier', 'defi_sportif', 'autre') NOT NULL,
    capacite_max INT,
    niveau_difficulte ENUM('debutant', 'intermediaire', 'avance'),
    materiel_necessaire TEXT,
    prerequis TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dates (date_debut, date_fin),
    INDEX idx_type (type)
);

CREATE TABLE communautes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('sport', 'bien_etre', 'sante', 'autre') NOT NULL,
    niveau ENUM('debutant', 'intermediaire', 'avance'),
    capacite_max INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type)
);

CREATE TABLE dons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    montant DECIMAL(10,2),
    type ENUM('financier', 'materiel') NOT NULL,
    description TEXT,
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
    prestation_id INT NOT NULL,
    note INT CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    date_evaluation DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personne_id) REFERENCES personnes(id),
    FOREIGN KEY (prestation_id) REFERENCES prestations(id),
    INDEX idx_date (date_evaluation)
);

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') NOT NULL,
    lien VARCHAR(255),
    lu BOOLEAN DEFAULT FALSE,
    date_lecture DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personne_id) REFERENCES personnes(id),
    INDEX idx_lu (lu),
    INDEX idx_type (type)
);

CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personne_id) REFERENCES personnes(id),
    INDEX idx_action (action),
    INDEX idx_date (created_at)
);