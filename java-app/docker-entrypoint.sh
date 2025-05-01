#!/bin/sh
set -e

echo "Génération du rapport initial..."
java -jar /app/app.jar

echo "Démarrage du service cron actif..."
crond -f