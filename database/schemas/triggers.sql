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
