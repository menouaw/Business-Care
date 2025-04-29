#!/bin/bash
set -e

echo "Génération du rapport initial..."
/app/run-report.sh

echo "Démarrage du planificateur pour exécuter le rapport quotidiennement à 2:00 AM..."
while true; do
    current_time=$(date +%H:%M)
    if [ "$current_time" = "02:00" ]; then
        echo "Exécution du rapport quotidien à $(date)"
        /app/run-report.sh
    fi
done 