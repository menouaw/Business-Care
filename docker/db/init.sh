#!/bin/bash
set -e

MYSQL_CMD="mysql -u root -p$MYSQL_ROOT_PASSWORD"

echo "Initialisation de la base de données..."

echo "Sélection de la base de données ${MYSQL_DATABASE}..."
$MYSQL_CMD -e "USE ${MYSQL_DATABASE};" || { echo "Erreur: la base de données ${MYSQL_DATABASE} n'existe pas"; exit 1; }

echo "Importation du schéma dans ${MYSQL_DATABASE}..."
$MYSQL_CMD ${MYSQL_DATABASE} < /docker-entrypoint-initdb.d/schemas/business_care.sql

echo "Importation des vues dans ${MYSQL_DATABASE}..."
$MYSQL_CMD ${MYSQL_DATABASE} < /docker-entrypoint-initdb.d/schemas/views.sql

echo "Importation des triggers dans ${MYSQL_DATABASE}..."
$MYSQL_CMD ${MYSQL_DATABASE} < /docker-entrypoint-initdb.d/schemas/triggers.sql

echo "Création des utilisateurs de la base de données et attribution des privilèges..."
$MYSQL_CMD -e "DROP USER IF EXISTS '${MYSQL_USER}'@'%';"
$MYSQL_CMD -e "CREATE USER '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';"
$MYSQL_CMD -e "GRANT SELECT, INSERT, UPDATE, DELETE ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';"

$MYSQL_CMD -e "DROP USER IF EXISTS 'business_care_backup'@'%';"
$MYSQL_CMD -e "CREATE USER 'business_care_backup'@'%' IDENTIFIED BY '${MYSQL_BACKUP_PASSWORD}';"
$MYSQL_CMD -e "GRANT SELECT, LOCK TABLES ON ${MYSQL_DATABASE}.* TO 'business_care_backup'@'%';"

$MYSQL_CMD -e "FLUSH PRIVILEGES;"

if [ -f /docker-entrypoint-initdb.d/seeders/sample_data.sql ]; then
    echo "Importation des données d'essai dans ${MYSQL_DATABASE}..."
    $MYSQL_CMD ${MYSQL_DATABASE} < /docker-entrypoint-initdb.d/seeders/sample_data.sql
else
    echo "Aucune donnée d'essai trouvée à /docker-entrypoint-initdb.d/seeders/sample_data.sql"
fi

echo "Script d'initialisation de la base de données terminé." 