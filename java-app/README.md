# Application Java de Reporting pour Business Care

## Objectif

Cette application Java autonome est conçue pour générer des rapports d'activité périodiques au format PDF à destination des responsables de Business Care. Elle récupère les données nécessaires via l'API REST de l'application principale et les synthétise sous forme de graphiques et de listes pour faciliter la prise de décision.

Le rapport généré contient trois pages :
*   **Page 1 :** Statistiques sur les comptes clients (4 graphiques, Top 5 clients).
*   **Page 2 :** Statistiques sur les évènements (4 graphiques, Top 5 évènements).
*   **Page 3 :** Statistiques sur les prestations/services (4 graphiques, Top 5 prestations).

## Stack Technique

*   **Langage :** Java (JDK 17)
*   **Gestion de build et dépendances :** Apache Maven
*   **Génération PDF :** iText 8.0.4 - Bibliothèque pour créer et manipuler des documents PDF.
*   **Génération Graphiques :** JFreeChart 1.5.4 - Framework pour générer divers types de graphiques.
*   **Client HTTP :** Apache HttpClient 5.3.1 - Utilisé pour envoyer des requêtes HTTP à l'API REST de Business Care.
*   **Traitement JSON :** Jackson Databind 2.17.0 - Convertit les objets Java en JSON et vice-versa.
*   **Logging :** SLF4J 2.0.12 - Façade de journalisation.

## Structure du Projet

L'application est organisée en plusieurs packages :

*   **`com.businesscare.reporting.main`** : Point d'entrée de l'application (ReportApplication)
*   **`com.businesscare.reporting.client`** : Communication avec l'API (ApiClient, ApiConfig)
*   **`com.businesscare.reporting.model`** : Modèles de données et enums
*   **`com.businesscare.reporting.service`** : Logique métier et traitement des données (ReportService)
*   **`com.businesscare.reporting.chart`** : Génération des graphiques (ChartGenerator)
*   **`com.businesscare.reporting.pdf`** : Génération du PDF (PdfGenerator)
*   **`com.businesscare.reporting.util`** : Classes utilitaires (ConfigLoader, Constants)
*   **`com.businesscare.reporting.exception`** : Exceptions personnalisées

## Prérequis

*   **JDK** (Java Development Kit) version 17 ou supérieure.
*   **Apache Maven** ([https://maven.apache.org/download.cgi](https://maven.apache.org/download.cgi))
*   L'**API REST** de Business Care doit être accessible (`http://localhost/api/admin` par défaut, ou via Docker `http://nginx/api/admin`).

## Build

1.  Naviguez vers le répertoire `java-app` à la racine du projet Business-Care.
2.  Exécutez la commande Maven suivante pour compiler le code, exécuter les tests (s'il y en a) et créer un JAR exécutable ("fat JAR") incluant toutes les dépendances :

    ```bash
    mvn clean package
    ```
3.  Le JAR exécutable sera généré dans le répertoire `target/` avec le nom `reporting-app.jar`.

## Configuration

L'application récupère sa configuration depuis les variables d'environnement :

*   `API_BASE_URL`: L'URL de base de l'API Admin de Business Care. Par défaut : `http://localhost/api/admin`.
    *   Dans l'environnement Docker fourni (`docker-compose.yml`), cette variable est automatiquement définie à `http://nginx/api/admin` pour le service `java-app`.
*   `API_USER`: L'adresse email de l'utilisateur admin pour s'authentifier auprès de l'API. Par défaut : `admin@businesscare.fr`.
*   `API_PASSWORD`: Le mot de passe de l'utilisateur admin. Par défaut : `admin123`.

## Exécution

1.  Assurez-vous que le JAR a été construit (voir la section Build).
2.  Définissez les variables d'environnement nécessaires si besoin (voir Configuration).
3.  Exécutez le JAR depuis le répertoire `java-app` :

    ```bash
    java -jar target/reporting-app.jar
    ```

L'application s'authentifie, récupère les données via l'API, traite ces données et génère un rapport PDF complet.

## Sortie (Output)

L'application génère un fichier PDF nommé `report.pdf` dans le sous-répertoire `output/` du répertoire `java-app` (`java-app/output/report.pdf`).

*   Le répertoire `output/` est créé automatiquement s'il n'existe pas.
*   Dans l'environnement Docker, le répertoire local `./java-app/output` est monté sur `/app/output` dans le conteneur, rendant le PDF généré accessible sur la machine hôte.

## Intégration avec `web-admin`

Le rapport PDF généré est destiné à être visualisé depuis l'interface `web-admin`.

*   Un lien direct vers le fichier `java-app/output/report.pdf` peut être ajouté dans `web-admin` (par exemple, dans `web-admin/modules/reports/view_java_report.php`).
*   Cela nécessite que le serveur web (Nginx/Apache) soit configuré pour servir les fichiers statiques du répertoire `java-app/output/` (via un alias ou une directive `location`).

## Flux d'exécution

L'application suit le flux suivant :

1. **Configuration :** Chargement des paramètres via ConfigLoader.
2. **Authentification :** Login auprès de l'API admin via ApiClient.
3. **Collecte des données :** Récupération des informations sur les entreprises, contrats, devis, factures, événements et prestations.
4. **Traitement :** Calcul des statistiques client, événement et prestation via ReportService.
5. **Génération des graphiques :** Création de 12 graphiques (4 par catégorie) avec ChartGenerator et JFreeChart.
6. **Création du PDF :** Génération d'un document PDF à 3 pages avec tableaux et graphiques via PdfGenerator et iText.
7. **Sauvegarde :** Enregistrement du rapport dans le répertoire output/.

## Points d'API utilisés

L'application utilise les endpoints suivants de l'API :

* `/api/admin/auth.php` - Authentification
* `/api/admin/companies.php` - Données des entreprises clientes
* `/api/admin/contracts.php` - Données des contrats
* `/api/admin/quotes.php` - Données des devis
* `/api/admin/invoices.php` - Données des factures
* `/api/admin/events.php` - Données des événements
* `/api/admin/services.php` - Données des prestations/services

## Fonctionnalités implémentées

L'application a implémenté toutes les fonctionnalités requises :

* [X] Configuration (`ApiConfig`, `ConfigLoader`, `Constants`)
* [X] Modèles de données (`model/` POJOs et Enums pour tous les types de données)
* [X] Authentification auprès de l'API (`ApiClient.login`)
* [X] Récupération des données Clients/Contrats/Factures/Devis (`ApiClient`)
* [X] Récupération des données Évènements/Prestations (`ApiClient`)
* [X] Traitement des données financières client (`ReportService.processClientFinancialData`)
* [X] Traitement des données évènements (`ReportService.processEventData`)
* [X] Traitement des données prestations (`ReportService.processPrestationData`)
* [X] Génération des graphiques clients (`ChartGenerator`)
* [X] Génération des graphiques évènements/prestations (`ChartGenerator`)
* [X] Structure et génération de la Page 1 du PDF (clients) (`PdfGenerator.generateClientFinancialPage`)
* [X] Génération des Pages 2 et 3 du PDF (événements et prestations) (`PdfGenerator`)
* [X] Gestion d'erreurs (`ApiException`, `ReportGenerationException`, et `try-catch` dans `ReportApplication`)

## Containerisation

Un Dockerfile est fourni pour containeriser l'application. Pour exécuter l'application dans Docker :

1. Construire l'image : `docker build -t business-care/reporting-app .`
2. Exécuter le conteneur : `docker run -e API_BASE_URL=http://nginx/api/admin -v ./output:/app/output business-care/reporting-app`

Alternativement, utilisez docker-compose qui configure automatiquement les services, réseaux et volumes.
