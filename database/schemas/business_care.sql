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

CREATE TABLE sites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    adresse TEXT,
    code_postal VARCHAR(10),
    ville VARCHAR(100),
    entreprise_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE CASCADE,
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
    site_id INT NULL,
    statut ENUM('actif', 'inactif', 'en_attente', 'suspendu') DEFAULT 'actif',
    derniere_connexion DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE SET NULL ON UPDATE CASCADE,
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

CREATE TABLE consultation_creneaux (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prestation_id INT NOT NULL,
    praticien_id INT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    is_booked BOOLEAN DEFAULT FALSE,
    site_id INT NULL,
    FOREIGN KEY (prestation_id) REFERENCES prestations(id) ON DELETE CASCADE,
    FOREIGN KEY (praticien_id) REFERENCES personnes(id) ON DELETE SET NULL,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_prestation_time (prestation_id, start_time, is_booked),
    UNIQUE KEY unique_slot (prestation_id, praticien_id, start_time)
);


CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('Starter Pack', 'Basic Pack', 'Premium Pack') NOT NULL UNIQUE,
    description TEXT,
    actif BOOLEAN DEFAULT TRUE,
    ordre INT DEFAULT 0,
    max_effectif_inferieur_egal INT NULL,
    activites_incluses INT NOT NULL DEFAULT 0,
    rdv_medicaux_inclus INT NOT NULL DEFAULT 0,
    chatbot_questions_limite INT NULL,
    rdv_medicaux_supplementaires_prix DECIMAL(6,2) NULL,
    conseils_hebdo_personnalises BOOLEAN DEFAULT FALSE,
    tarif_annuel_par_salarie DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    prix_base_indicatif DECIMAL(10,2) NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_actif (actif)
);

CREATE TABLE contrats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entreprise_id INT NOT NULL,
    service_id INT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE,
    nombre_salaries INT,
    statut ENUM('actif', 'expire', 'resilie', 'en_attente') DEFAULT 'actif',
    conditions_particulieres TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    INDEX idx_dates (date_debut, date_fin),
    INDEX idx_statut (statut),
    INDEX idx_service (service_id)
);

CREATE TABLE devis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entreprise_id INT NOT NULL,
    service_id INT NULL,
    est_personnalise BOOLEAN DEFAULT FALSE,
    date_creation DATE NOT NULL,
    date_validite DATE,
    montant_total DECIMAL(10,2) NOT NULL,
    montant_ht DECIMAL(10,2),
    nombre_salaries_estimes INT,
    notes_negociation TEXT DEFAULT NULL,
    tva DECIMAL(5,2),
    statut ENUM('en_attente', 'accepte', 'refuse', 'expire') DEFAULT 'en_attente',
    conditions_paiement TEXT,
    delai_paiement INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    INDEX idx_dates (date_creation, date_validite),
    INDEX idx_statut (statut),
    INDEX idx_service (service_id)
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
    date_paiement DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id),
    FOREIGN KEY (devis_id) REFERENCES devis(id) ON DELETE SET NULL,
    INDEX idx_numero (numero_facture),
    INDEX idx_dates (date_emission, date_echeance),
    INDEX idx_statut (statut)
);

CREATE TABLE rendez_vous (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    prestation_id INT NOT NULL,
    praticien_id INT,
    date_rdv DATETIME NOT NULL,
    duree INT NOT NULL,
    lieu VARCHAR(255),
    type_rdv ENUM('presentiel', 'visio', 'telephone', 'consultation'),
    statut ENUM('planifie', 'confirme', 'annule', 'termine', 'no_show') DEFAULT 'planifie',
    notes TEXT,
    consultation_creneau_id INT NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personne_id) REFERENCES personnes(id),
    FOREIGN KEY (prestation_id) REFERENCES prestations(id),
    FOREIGN KEY (praticien_id) REFERENCES personnes(id),
    FOREIGN KEY (consultation_creneau_id) REFERENCES consultation_creneaux(id) ON DELETE SET NULL,
    INDEX idx_date (date_rdv),
    INDEX idx_statut (statut),
    INDEX idx_praticien (praticien_id),
    INDEX idx_consultation_creneau (consultation_creneau_id)
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
    site_id INT NULL,
    organise_par_bc BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE SET NULL ON UPDATE CASCADE,
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

CREATE TABLE associations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL UNIQUE,
    resume TEXT DEFAULT NULL,
    histoire TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE dons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    association_id INT NULL DEFAULT NULL,
    montant DECIMAL(10,2) NULL,
    type ENUM('financier', 'materiel') NOT NULL,
    description TEXT NULL,
    date_don DATE NOT NULL,
    statut VARCHAR(50) DEFAULT 'enregistré',
    stripe_session_id VARCHAR(255) NULL DEFAULT NULL,
    stripe_payment_intent_id VARCHAR(255) NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personne_id) REFERENCES personnes(id),
    FOREIGN KEY (association_id) REFERENCES associations(id) ON DELETE SET NULL ON UPDATE CASCADE,
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

CREATE TABLE api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,          
    expires_at DATETIME NOT NULL,       
    last_used_at DATETIME NULL,          
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES personnes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_api_token (token),
    INDEX idx_api_user_id (user_id),
    INDEX idx_api_expires (expires_at)
);

CREATE TABLE preferences_utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    langue ENUM('fr', 'en') DEFAULT 'fr',
    notif_email BOOLEAN DEFAULT TRUE,
    theme ENUM('clair', 'sombre') DEFAULT 'clair',
    FOREIGN KEY (personne_id) REFERENCES personnes(id),
    UNIQUE KEY unique_personne_id (personne_id)
);

CREATE TABLE devis_prestations (
    devis_id INT NOT NULL,
    prestation_id INT NOT NULL,
    quantite INT DEFAULT 1,
    prix_unitaire_devis DECIMAL(10,2) NOT NULL,
    description_specifique TEXT,
    PRIMARY KEY (devis_id, prestation_id),
    FOREIGN KEY (devis_id) REFERENCES devis(id) ON DELETE CASCADE,
    FOREIGN KEY (prestation_id) REFERENCES prestations(id) ON DELETE CASCADE
);

CREATE TABLE contrats_prestations (
    contrat_id INT NOT NULL,
    prestation_id INT NOT NULL,
    PRIMARY KEY (contrat_id, prestation_id),
    FOREIGN KEY (contrat_id) REFERENCES contrats(id) ON DELETE CASCADE,
    FOREIGN KEY (prestation_id) REFERENCES prestations(id) ON DELETE CASCADE
);

CREATE TABLE factures_prestataires (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prestataire_id INT NOT NULL,
    numero_facture VARCHAR(50) UNIQUE,
    date_facture DATE NOT NULL,
    periode_debut DATE,
    periode_fin DATE,
    montant_total DECIMAL(10,2) NOT NULL,
    statut ENUM('generation_attendue', 'impayee', 'payee') DEFAULT 'generation_attendue',
    date_paiement DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prestataire_id) REFERENCES personnes(id) ON DELETE CASCADE,
    INDEX idx_numero (numero_facture),
    INDEX idx_periode (periode_debut, periode_fin),
    INDEX idx_statut (statut),
    INDEX idx_prestataire (prestataire_id)
);

CREATE TABLE facture_prestataire_lignes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    facture_prestataire_id INT NOT NULL,
    rendez_vous_id INT UNIQUE,
    description TEXT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facture_prestataire_id) REFERENCES factures_prestataires(id) ON DELETE CASCADE,
    FOREIGN KEY (rendez_vous_id) REFERENCES rendez_vous(id) ON DELETE SET NULL,
    INDEX idx_facture_id (facture_prestataire_id)
);

CREATE TABLE habilitations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prestataire_id INT NOT NULL,
    type ENUM('diplome', 'certification', 'agrement', 'autre') NOT NULL,
    nom_document VARCHAR(255),
    document_url VARCHAR(255),
    organisme_emission VARCHAR(150),
    date_obtention DATE,
    date_expiration DATE,
    statut ENUM('en_attente_validation', 'verifiee', 'rejetee', 'expiree') DEFAULT 'en_attente_validation',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prestataire_id) REFERENCES personnes(id) ON DELETE CASCADE,
    INDEX idx_prestataire_id (prestataire_id),
    INDEX idx_statut (statut),
    INDEX idx_date_expiration (date_expiration)
);

CREATE TABLE prestataires_prestations (
    prestataire_id INT NOT NULL,
    prestation_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (prestataire_id, prestation_id),
    FOREIGN KEY (prestataire_id) REFERENCES personnes(id) ON DELETE CASCADE,
    FOREIGN KEY (prestation_id) REFERENCES prestations(id) ON DELETE CASCADE
);

CREATE TABLE prestataires_disponibilites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prestataire_id INT NOT NULL,
    type ENUM('recurrente', 'specifique', 'indisponible') NOT NULL,
    date_debut DATETIME,
    date_fin DATETIME,
    heure_debut TIME,
    heure_fin TIME,
    jour_semaine TINYINT,
    recurrence_fin DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prestataire_id) REFERENCES personnes(id) ON DELETE CASCADE,
    INDEX idx_prestataire_id (prestataire_id),
    INDEX idx_type (type),
    INDEX idx_date_debut (date_debut),
    INDEX idx_jour_semaine (jour_semaine)
);

CREATE TABLE communaute_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    communaute_id INT NOT NULL,
    personne_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (communaute_id) REFERENCES communautes(id) ON DELETE CASCADE,
    FOREIGN KEY (personne_id) REFERENCES personnes(id) ON DELETE CASCADE,
    INDEX idx_communaute_date (communaute_id, created_at)
);


CREATE TABLE evenement_inscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personne_id INT NOT NULL,
    evenement_id INT NOT NULL,
    statut ENUM('inscrit', 'annule') DEFAULT 'inscrit',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personne_id) REFERENCES personnes(id) ON DELETE CASCADE,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_inscription (personne_id, evenement_id), 
    INDEX idx_statut (statut)
);

CREATE TABLE signalements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sujet VARCHAR(255) NULL, 
    description TEXT NOT NULL, 
    statut ENUM('nouveau', 'en_cours', 'resolu', 'clos') DEFAULT 'nouveau', 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_statut (statut)
);

CREATE TABLE conseils (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    icone VARCHAR(50),
    resume TEXT,
    categorie VARCHAR(100),
    contenu LONGTEXT NOT NULL
);

CREATE TABLE support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entreprise_id INT NULL, 
    personne_id INT NULL, 
    sujet VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    statut ENUM('nouveau', 'en_cours', 'resolu', 'clos') DEFAULT 'nouveau', 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE SET NULL,
    FOREIGN KEY (personne_id) REFERENCES personnes(id) ON DELETE SET NULL,
    INDEX idx_statut (statut),
    INDEX idx_entreprise_id (entreprise_id),
    INDEX idx_personne_id (personne_id)
);

CREATE TABLE communaute_membres (
    communaute_id INT NOT NULL,          
    personne_id INT NOT NULL,            
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    PRIMARY KEY (communaute_id, personne_id), 
    FOREIGN KEY (communaute_id) REFERENCES communautes(id) ON DELETE CASCADE, 
    FOREIGN KEY (personne_id) REFERENCES personnes(id) ON DELETE CASCADE    
);

CREATE TABLE interets_utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE personne_interets (
    personne_id INT NOT NULL,
    interet_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (personne_id, interet_id),
    FOREIGN KEY (personne_id) REFERENCES personnes(id) ON DELETE CASCADE,
    FOREIGN KEY (interet_id) REFERENCES interets_utilisateurs(id) ON DELETE CASCADE
);

