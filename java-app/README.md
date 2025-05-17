# Application de Reporting Business Care

## Objectif
Application Java générant des rapports d'activité périodiques en PDF **et JSON** pour les responsables de Business Care, consommant les données via API REST et produisant des visualisations exploitables.

## Structure du Rapport
Le rapport comprend :
- Page de titre
- Statistiques clients (4 graphiques : statut des contrats, secteur client, taille client, revenus par client)
- Classement Top 5 clients
- Statistiques événements (2 graphiques : distribution par type - camembert et barres)
- Statistiques services (2 graphiques : type de prestation, catégorie de prestation)

## Stack Technique
- **Runtime :** Java JDK 17
- **Build :** Apache Maven
- **Génération PDF :** iText 8.0.4
- **Graphiques :** JFreeChart 1.5.4
- **Client HTTP :** Apache HttpClient 5.3.1
- **Traitement JSON :** Jackson Databind 2.17.0 (avec jackson-datatype-jsr310)
- **Logging :** SLF4J 2.0.12 avec implémentation slf4j-simple

## Architecture
```
src/main/java/com/businesscare/reporting/
├── main/         # Point d'entrée (ReportApplication)
├── client/       # Client API (ApiClient, ApiConfig)
├── model/        # Modèles de données (POJOs et Enums)
├── service/      # Logique métier (ReportService)
├── chart/        # Génération de graphiques (ChartGenerator)
├── pdf/          # Génération PDF (PdfGenerator)
├── util/         # Utilitaires (ConfigLoader, Constants)
└── exception/    # Exceptions personnalisées (ApiException)
```

## Prérequis
- JDK 17+
- Apache Maven
- Accès à l'API Business Care

## Build
```bash
cd java-app
mvnd clean package
# Résultat : target/reporting-app.jar
```

## Configuration
Variables d'environnement :
- `API_BASE_URL` : URL de l'API Admin (défaut : `http://192.168.213.22/api/admin`)
- `API_USER` : Email admin (défaut : `admin@businesscare.fr`)
- `API_PASSWORD` : Mot de passe admin (défaut : `admin123`)

## Exécution

Pour lancer le rapport json: 
```bash
mvn exec:java -Dexec.mainClass=com.businesscare.reporting.jsonmain.JsonReportLauncher
```

Standard :
```bash
java -jar target/reporting-app.jar
```

Docker :
```bash
docker compose up -d
```

Le conteneur :
- Génère un rapport initial au démarrage
- Génère des rapports quotidiens à 02:00
- Logs accessibles via `docker logs business_care_java_app`

## Sortie
- Rapports PDF nommés `report_JJ-MM-AAAA.pdf` dans `java-app/output/`
- Fichiers JSON nommés `report_JJ-MM-AAAA.json` dans `java-app/output/json/`
- Répertoire de sortie créé automatiquement si nécessaire
- Avec Docker : montage du volume local `./java-app/output` vers `/app/output` dans le conteneur

## Intégration API
Utilise ces endpoints :
- `/api/admin/auth.php`
- `/api/admin/companies.php`
- `/api/admin/contracts.php`
- `/api/admin/quotes.php`
- `/api/admin/invoices.php`
- `/api/admin/events.php`
- `/api/admin/services.php`

## Flux d'Exécution
1. **Configuration :** Chargement des paramètres depuis l'environnement
2. **Authentification :** Établissement d'une session API
3. **Collecte de données :** Récupération des données métier depuis l'API
4. **Traitement :** Calcul des métriques statistiques
5. **Génération JSON :** Création du fichier de données JSON
6. **Génération de graphiques :** Création de 8 graphiques de visualisation
7. **Assemblage PDF :** Compilation en document structuré
8. **Sortie :** Sauvegarde des rapports dans le répertoire configuré

## Conteneurisation
- Dockerfile multi-étapes :
  - Étape de build : `maven:3.9-eclipse-temurin-17`
  - Runtime : `eclipse-temurin:17-jre-alpine`
- Planification via `docker-entrypoint.sh`
- Montage de volume pour la persistance des sorties
- Injection de variables d'environnement pour les identifiants

```bash
docker compose up -d --build  # Première fois/reconstruction
docker compose up -d          # Démarrage des conteneurs existants
```
