#!/bin/sh
set -e

echo "Génération du rapport initial..."
java -jar /app/app.jar

echo "Démarrage du planificateur pour exécuter le rapport quotidiennement à 2:00 AM..."
while true; do
    current_time=$(date +%H:%M)
    if [ "$current_time" = "02:00" ]; then
        echo "Exécution du rapport quotidien à $(date)"
        java -jar /app/app.jar
    fi
    sleep 30
done 