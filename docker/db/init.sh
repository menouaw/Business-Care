

set -e


MYSQL_CMD="mysql -u root -p$MYSQL_ROOT_PASSWORD"

echo "Database initialization script started..."










echo "Selecting database ${MYSQL_DATABASE}..."
$MYSQL_CMD -e "USE ${MYSQL_DATABASE};"

echo "Importing schema into ${MYSQL_DATABASE}..."
$MYSQL_CMD ${MYSQL_DATABASE} < /docker-entrypoint-initdb.d/schemas/business_care.sql


echo "Importing views into ${MYSQL_DATABASE}..."
$MYSQL_CMD ${MYSQL_DATABASE} < /docker-entrypoint-initdb.d/schemas/views.sql


echo "Importing triggers into ${MYSQL_DATABASE}..."
$MYSQL_CMD ${MYSQL_DATABASE} < /docker-entrypoint-initdb.d/schemas/triggers.sql


echo "Creating database users and granting privileges..."
$MYSQL_CMD -e "DROP USER IF EXISTS '${MYSQL_USER}'@'%';"
$MYSQL_CMD -e "CREATE USER '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';"
$MYSQL_CMD -e "GRANT SELECT, INSERT, UPDATE, DELETE ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';"

$MYSQL_CMD -e "DROP USER IF EXISTS 'business_care_backup'@'%';"
$MYSQL_CMD -e "CREATE USER 'business_care_backup'@'%' IDENTIFIED BY 'business_care_backup_password';" 
$MYSQL_CMD -e "GRANT SELECT, LOCK TABLES ON ${MYSQL_DATABASE}.* TO 'business_care_backup'@'%';"

$MYSQL_CMD -e "FLUSH PRIVILEGES;"


if [ -f /docker-entrypoint-initdb.d/seeders/sample_data.sql ]; then
    echo "Importing seed data into ${MYSQL_DATABASE}..."
    $MYSQL_CMD ${MYSQL_DATABASE} < /docker-entrypoint-initdb.d/seeders/sample_data.sql
else
    echo "No seed data found at /docker-entrypoint-initdb.d/seeders/sample_data.sql"
fi

echo "Database initialization script finished." 