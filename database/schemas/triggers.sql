USE business_care;

DELIMITER //

CREATE TRIGGER after_personne_insert
AFTER INSERT ON personnes
FOR EACH ROW
BEGIN
    INSERT INTO logs (personne_id, action, details)
    VALUES (NEW.id, 'creation_compte', CONCAT('Creation du compte pour ', NEW.email));
END//

CREATE TRIGGER before_rendez_vous_update
BEFORE UPDATE ON rendez_vous
FOR EACH ROW
BEGIN
    IF NEW.statut != OLD.statut THEN
        INSERT INTO notifications (personne_id, titre, message, type)
        VALUES (
            NEW.personne_id,
            'Mise à jour du rendez-vous',
            CONCAT('Le statut de votre rendez-vous a ete mis à jour: ', NEW.statut),
            'info'
        );
    END IF;
END//

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

CREATE TRIGGER after_evaluation_insert
AFTER INSERT ON evaluations
FOR EACH ROW
BEGIN
    INSERT INTO logs (personne_id, action, details)
    VALUES (
        NEW.personne_id,
        'nouvelle_evaluation',
        CONCAT('Nouvelle evaluation de ', NEW.note, '/5 pour la prestation ', NEW.prestation_id)
    );
END//

DELIMITER ; 