-- source C:/MAMP/htdocs/Business-Care/database/schemas/triggers.sql

USE business_care;

DELIMITER //

-- notifications des changements de statut de rendez-vous
CREATE TRIGGER before_rendez_vous_update
BEFORE UPDATE ON rendez_vous
FOR EACH ROW
BEGIN
    IF NEW.statut != OLD.statut THEN
        INSERT INTO notifications (personne_id, titre, message, type)
        VALUES (
            NEW.personne_id,
            'Mise a jour du rendez-vous',
            CONCAT('Le statut de votre rendez-vous a ete mis a jour: ', NEW.statut),
            'info'
        );
    END IF;
END//

-- notifications pour les nouvelles factures
CREATE TRIGGER after_facture_insert
AFTER INSERT ON factures
FOR EACH ROW
BEGIN
    INSERT INTO notifications (personne_id, titre, message, type, lien)
    SELECT 
        p.id,
        'Nouvelle Facture',
        CONCAT('Une nouvelle facture de ', NEW.montant_total, '€ a ete creee'),
        'info',
        CONCAT('/factures/', NEW.id)
    FROM personnes p
    JOIN entreprises e ON p.entreprise_id = e.id
    WHERE e.id = NEW.entreprise_id;
END//

-- notifications pour les factures payées
CREATE TRIGGER before_facture_update
BEFORE UPDATE ON factures
FOR EACH ROW
BEGIN
    IF NEW.statut = 'payee' AND OLD.statut != 'payee' THEN
        INSERT INTO notifications (personne_id, titre, message, type, lien)
        SELECT 
            p.id,
            'Facture Payee',
            CONCAT('La facture ', NEW.numero_facture, ' a ete payee'),
            'success',
            CONCAT('/factures/', NEW.id)
        FROM personnes p
        JOIN entreprises e ON p.entreprise_id = e.id
        WHERE e.id = NEW.entreprise_id;
    END IF;
END//

DELIMITER ;

-- preferences utilisateurs
DELIMITER //
CREATE TRIGGER after_personne_insert
AFTER INSERT ON personnes
FOR EACH ROW
BEGIN
    INSERT INTO preferences_utilisateurs (personne_id, langue) VALUES (NEW.id, 'fr');
END//

DELIMITER ;


DELIMITER //

-- notifications pour les factures prestataires
CREATE TRIGGER after_facture_prestataire_insert
AFTER INSERT ON factures_prestataires
FOR EACH ROW
BEGIN
    IF NEW.statut != 'generation_attendue' THEN
        INSERT INTO notifications (personne_id, titre, message, type, lien)
        VALUES (
            NEW.prestataire_id,
            'Nouvelle Facture Prestataire',
            CONCAT('Votre facture No ', NEW.numero_facture, ' pour la periode du ', DATE_FORMAT(NEW.periode_debut, '%d/%m/%Y'), ' au ', DATE_FORMAT(NEW.periode_fin, '%d/%m/%Y'), ' (', NEW.montant_total, '€) a ete generee.'),
            'info',
            CONCAT('/prestataire/facturation/', NEW.id)
        );
    END IF;
END//

CREATE TRIGGER after_facture_prestataire_update
AFTER UPDATE ON factures_prestataires
FOR EACH ROW
BEGIN
    IF NEW.statut = 'payee' AND OLD.statut != 'payee' THEN
        INSERT INTO notifications (personne_id, titre, message, type, lien)
        VALUES (
            NEW.prestataire_id,
            'Facture Prestataire Payee',
            CONCAT('Votre facture No ', NEW.numero_facture, ' (', NEW.montant_total, '€) a ete payee le ', DATE_FORMAT(NEW.date_paiement, '%d/%m/%Y'), '.'),
            'success',
            CONCAT('/prestataire/facturation/', NEW.id)
        );
    END IF;
END//

DELIMITER ;


DELIMITER //

CREATE TRIGGER after_prestataire_prestation_insert
AFTER INSERT ON prestataires_prestations
FOR EACH ROW
BEGIN
    DECLARE prestation_nom VARCHAR(255);
    SELECT nom INTO prestation_nom FROM prestations WHERE id = NEW.prestation_id;

    INSERT INTO notifications (personne_id, titre, message, type, lien)
    VALUES (
        NEW.prestataire_id,
        'Nouvelle Prestation Assignee',
        CONCAT('Vous pouvez desormais proposer la prestation: ', IFNULL(prestation_nom, 'ID ' + NEW.prestation_id), '.'),
        'info',
        '/prestataire/gestion/prestation'
    );
END//

CREATE TRIGGER after_prestataire_prestation_delete
AFTER DELETE ON prestataires_prestations
FOR EACH ROW
BEGIN
    DECLARE prestation_nom VARCHAR(255);
    SELECT nom INTO prestation_nom FROM prestations WHERE id = OLD.prestation_id;

    INSERT INTO notifications (personne_id, titre, message, type, lien)
    VALUES (
        OLD.prestataire_id,
        'Prestation Retiree',
        CONCAT('Vous ne proposez plus la prestation: ', IFNULL(prestation_nom, 'ID ' + OLD.prestation_id), '.'),
        'warning',
        '/prestataire/gestion/prestation'
    );
END//

DELIMITER ;
