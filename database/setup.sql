-- source C:/MAMP/htdocs/Business-Care/database/setup.sql

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS business_care;
SET FOREIGN_KEY_CHECKS = 1;

-- Creation de la base de donnees et des tables
source C:/MAMP/htdocs/Business-Care/database/schemas/business_care.sql;

-- Creation des vues
source C:/MAMP/htdocs/Business-Care/database/schemas/views.sql;

-- Creation des triggers
source C:/MAMP/htdocs/Business-Care/database/schemas/triggers.sql;

-- Sélection de la base de données
USE business_care;

-- Suppression des utilisateurs existants
DROP USER IF EXISTS 'business_care_user'@'%';
DROP USER IF EXISTS 'business_care_backup'@'%';

-- Creation d'un utilisateur dedie pour l'application
CREATE USER 'business_care_user'@'%' IDENTIFIED BY 'business_care_password';

-- Attribution des privileges necessaires
GRANT SELECT, INSERT, UPDATE, DELETE ON business_care.* TO 'business_care_user'@'%';

-- Creation d'un utilisateur pour les sauvegardes
CREATE USER 'business_care_backup'@'%' IDENTIFIED BY 'business_care_backup_password';
GRANT SELECT, LOCK TABLES ON business_care.* TO 'business_care_backup'@'%';

-- Application des privileges
FLUSH PRIVILEGES;