-- Script de configuration de la base de donnees Business Care
-- Ce script doit etre execute en tant qu'administrateur MySQL

-- Creation de la base de donnees et des tables
source schemas/business_care.sql;

-- Creation des vues
source schemas/views.sql;

-- Creation des triggers
source schemas/triggers.sql;

-- Creation d'un utilisateur dedie pour l'application
CREATE USER IF NOT EXISTS 'business_care_user'@'localhost' IDENTIFIED BY 'business_care_password';

-- Attribution des privileges necessaires
GRANT SELECT, INSERT, UPDATE, DELETE ON business_care.* TO 'business_care_user'@'localhost';

-- Creation d'un utilisateur en lecture seule pour les rapports
CREATE USER IF NOT EXISTS 'business_care_report'@'localhost' IDENTIFIED BY 'business_care_report_password';
GRANT SELECT ON business_care.* TO 'business_care_report'@'localhost';

-- Creation d'un utilisateur pour les sauvegardes
CREATE USER IF NOT EXISTS 'business_care_backup'@'localhost' IDENTIFIED BY 'business_care_backup_password';
GRANT SELECT, LOCK TABLES ON business_care.* TO 'business_care_backup'@'localhost';

-- Application des privileges
FLUSH PRIVILEGES;

-- Creation d'une procedure stockee pour la sauvegarde
DELIMITER //

CREATE PROCEDURE backup_database()
BEGIN
    DECLARE backup_file VARCHAR(255);
    SET backup_file = CONCAT('backup_business_care_', DATE_FORMAT(NOW(), '%Y%m%d_%H%i%S'), '.sql');
    
    SET @backup_command = CONCAT('mysqldump -u business_care_backup -p business_care > ', backup_file);
    PREPARE stmt FROM @backup_command;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END//

DELIMITER ;

-- Creation d'une procedure stockee pour la restauration
DELIMITER //

CREATE PROCEDURE restore_database(IN backup_file VARCHAR(255))
BEGIN
    SET @restore_command = CONCAT('mysql -u business_care_backup -p business_care < ', backup_file);
    PREPARE stmt FROM @restore_command;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END//

DELIMITER ;

-- Creation d'une procedure stockee pour nettoyer les logs anciens
DELIMITER //

CREATE PROCEDURE clean_old_logs(IN days_to_keep INT)
BEGIN
    DELETE FROM logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
END//

DELIMITER ;

-- Creation d'une procedure stockee pour nettoyer les notifications anciennes
DELIMITER //

CREATE PROCEDURE clean_old_notifications(IN days_to_keep INT)
BEGIN
    DELETE FROM notifications 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
    AND lu = TRUE;
END//

DELIMITER ;

-- Creation d'un evenement pour le nettoyage automatique des logs
CREATE EVENT clean_logs_event
ON SCHEDULE EVERY 1 WEEK
DO CALL clean_old_logs(90);

-- Creation d'un evenement pour le nettoyage automatique des notifications
CREATE EVENT clean_notifications_event
ON SCHEDULE EVERY 1 WEEK
DO CALL clean_old_notifications(30);

-- Creation d'un evenement pour la sauvegarde automatique
CREATE EVENT backup_database_event
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL 1 HOUR
DO CALL backup_database();

-- Activation du planificateur d'evenements
SET GLOBAL event_scheduler = ON;

-- Creation d'une procedure stockee pour verifier l'etat de la base de donnees
DELIMITER //

CREATE PROCEDURE check_database_status()
BEGIN
    -- Verification des tables
    SELECT 
        TABLE_NAME,
        TABLE_ROWS as nombre_lignes,
        UPDATE_TIME as derniere_mise_a_jour
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = 'business_care';

    -- Verification des vues
    SELECT 
        TABLE_NAME,
        VIEW_DEFINITION
    FROM information_schema.VIEWS 
    WHERE TABLE_SCHEMA = 'business_care';

    -- Verification des triggers
    SELECT 
        TRIGGER_NAME,
        EVENT_MANIPULATION,
        EVENT_OBJECT_TABLE,
        ACTION_STATEMENT
    FROM information_schema.TRIGGERS 
    WHERE TRIGGER_SCHEMA = 'business_care';

    -- Verification des procedures stockees
    SELECT 
        ROUTINE_NAME,
        ROUTINE_TYPE,
        ROUTINE_DEFINITION
    FROM information_schema.ROUTINES 
    WHERE ROUTINE_SCHEMA = 'business_care';
END//

DELIMITER ;

-- Creation d'une procedure stockee pour reinitialiser les mots de passe
DELIMITER //

CREATE PROCEDURE reset_user_password(IN user_email VARCHAR(255), IN new_password VARCHAR(255))
BEGIN
    UPDATE personnes 
    SET mot_de_passe = SHA2(new_password, 256)
    WHERE email = user_email;
END//

DELIMITER ; 