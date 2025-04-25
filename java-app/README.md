# Application Java de Reporting pour Business Care

## Objectif

Cette application Java autonome est conçue pour générer des rapports d'activité périodiques au format PDF à destination des responsables de Business Care. Elle récupère les données nécessaires via l'API REST de l'application principale et les synthétise sous forme de graphiques et de listes pour faciliter la prise de décision.

Le rapport généré doit contenir au moins trois pages :
*   **Page 1 :** Statistiques sur les comptes clients (4 graphiques, Top 5 clients).
*   **Page 2 :** Statistiques sur les événements (4 graphiques, Top 5 événements).
*   **Page 3 :** Statistiques sur les prestations/services (4 graphiques, Top 5 prestations).

L'application doit également s'assurer qu'elle utilise un jeu de données d'au moins 30 enregistrements par catégorie (clients, événements, prestations), en générant des données aléatoires si nécessaire (fonctionnalité à implémenter).

## Stack Technique

*   **Langage :** Java (JDK 17+ recommandé)
*   **Gestion de build et dépendances :** Apache Maven
*   **Génération PDF :** iText ([https://itextpdf.com/](https://itextpdf.com/)) - Bibliothèque pour créer et manipuler des documents PDF.
*   **Génération Graphiques :** JFreeChart ([https://www.jfree.org/jfreechart/](https://www.jfree.org/jfreechart/)) - Framework pour générer divers types de graphiques.
*   **Client HTTP :** Apache HttpClient 5 ([https://hc.apache.org/httpcomponents-client-5.3.x/](https://hc.apache.org/httpcomponents-client-5.3.x/)) - Utilisé pour envoyer des requêtes HTTP à l'API REST de Business Care et recevoir les réponses. Essentiel pour la communication avec le backend.
*   **Traitement JSON :** Jackson Databind ([https://github.com/FasterXML/jackson-databind](https://github.com/FasterXML/jackson-databind)) - Permet de convertir facilement les objets Java en JSON (pour l'envoi de données à l'API, comme les identifiants de connexion) et le JSON reçu de l'API en objets Java (comme la liste des entreprises ou les détails de l'utilisateur).

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
3.  Le JAR exécutable sera généré dans le répertoire `target/`. Le nom ressemble généralement à `java-app-<version>-jar-with-dependencies.jar` (par exemple, `java-app-1.0-SNAPSHOT-jar-with-dependencies.jar`).

## Configuration

L'application récupère sa configuration depuis les variables d'environnement :

*   `API_BASE_URL`: L'URL de base de l'API Admin de Business Care. Par défaut : `http://localhost/api/admin`.
    *   Dans l'environnement Docker fourni (`docker-compose.yml`), cette variable est automatiquement définie à `http://nginx/api/admin` pour le service `java-app`.
*   `API_USER`: L'adresse email de l'utilisateur admin pour s'authentifier auprès de l'API. Par défaut : `admin@businesscare.fr`.
*   `API_PASSWORD`: Le mot de passe de l'utilisateur admin. Par défaut : `admin123`.

Assurez-vous que ces variables sont définies dans votre environnement d'exécution si vous n'utilisez pas les valeurs par défaut ou Docker Compose.

## Exécution

1.  Assurez-vous que le JAR a été construit (voir la section Build).
2.  Définissez les variables d'environnement nécessaires si besoin (voir Configuration).
3.  Exécutez le JAR depuis le répertoire `java-app` :

    ```bash
    java -jar target/java-app-<version>-jar-with-dependencies.jar
    ```
    (Remplacez `<version>` par la version actuelle).

L'application tentera de s'authentifier, de récupérer les données, puis (une fois implémenté) de générer le rapport.

## Sortie (Output)

L'application est conçue pour générer un fichier PDF nommé `report.pdf` dans le sous-répertoire `output/` du répertoire `java-app` (`java-app/output/report.pdf`).

*   Le répertoire `output/` sera créé automatiquement s'il n'existe pas.
*   Dans l'environnement Docker, le répertoire local `./java-app/output` est monté sur `/app/output` dans le conteneur, rendant le PDF généré accessible directement sur la machine hôte.

## Intégration avec `web-admin`

Le rapport PDF généré est destiné à être visualisé depuis l'interface `web-admin`.

*   Un lien direct vers le fichier `java-app/output/report.pdf` peut être ajouté dans `web-admin` (par exemple, dans `web-admin/modules/reports/view_java_report.php`).
*   Cela nécessite que le serveur web (Nginx/Apache) soit configuré pour servir les fichiers statiques du répertoire `java-app/output/` (par exemple via un alias ou une directive `location`).

## État Actuel du Développement

L'application progresse. La structure de base, la configuration, les modèles de données, et la logique de génération de la première page du rapport (Statistiques Clients) sont en place. Les prochaines étapes concernent l'implémentation des pages 2 et 3 (événements et prestations) et la gestion des données aléatoires si nécessaire.

*   [X] Configuration (`ApiConfig`, `ConfigLoader`, `Constants`)
*   [X] Modèles de données (`model/` POJOs et Enums, y compris `ClientStats`)
*   [X] Authentification auprès de l'API (`ApiClient.login`)
*   [X] Récupération des données Clients/Contrats/Factures/Devis (`ApiClient` - méthodes `getCompanies`, `getContracts`, `getQuotes`, `getInvoices`)
*   [ ] Récupération des données Événements/Prestations (`ApiClient` - méthodes à ajouter)
*   [X] Traitement des données financières client (`service/ReportService.processClientFinancialData`)
*   [ ] Traitement des données événements/prestations (`service/ReportService` - méthodes à ajouter)
*   [ ] Vérification du volume de données et génération de données aléatoires si nécessaire.
*   [X] Implémentation de la génération des graphiques clients (JFreeChart - `chart/ChartGenerator`)
*   [ ] Implémentation de la génération des graphiques événements/prestations (`chart/ChartGenerator` - méthodes à ajouter)
*   [X] Implémentation de la structure et de la génération de la Page 1 du PDF (iText - `pdf/PdfGenerator.generateClientFinancialPage`)
*   [ ] Implémentation de la génération des Pages 2 et 3 du PDF (`pdf/PdfGenerator` - méthodes à ajouter)
*   [X] Gestion d'erreurs de base (via `ApiException` et `try-catch` dans `ReportApplication`).

## Détails d'Implémentation - Reporting Financier (Page 1)

Cette section détaille les étapes **implémentées** pour la première page du rapport PDF axée sur les "Statistiques des comptes clients", en s'appuyant sur la structure de projet existante (`src/main/java/com/businesscare/reporting/`).

1.  **Configuration (`client/ApiConfig.java`, `util/ConfigLoader.java`, `util/Constants.java`):** [**Terminé**]
    *   Les classes pour gérer la configuration via les variables d'environnement (avec valeurs par défaut) sont en place.

2.  **Modèles de Données (package `model/`):** [**Terminé**]
    *   Les POJOs (`Company`, `Contract`, `Quote`, `Invoice`, `QuotePrestation`, `User`, `AuthResponse`, `ApiResponse`, `ErrorResponse`, `ClientStats`, Enums) sont définis avec les annotations Jackson nécessaires pour le mapping JSON.

3.  **Améliorations du Client API (`client/ApiClient.java`):** [**Terminé**]
    *   `ApiClient` est instancié avec `ApiConfig`.
    *   Les méthodes `login`, `getCompanies`, `getContracts`, `getQuotes`, `getInvoices` sont implémentées, utilisant le jeton d'authentification et désérialisant le JSON en objets `model`.
    *   La gestion des erreurs via `ApiException` est intégrée.

4.  **Traitement des Données (`service/ReportService.java`):** [**Terminé**]
    *   La méthode `processClientFinancialData` traite les listes `Company`, `Contract`, `Invoice`.
    *   Elle effectue les calculs nécessaires (revenu total/client, distributions par secteur/taille, statut des contrats, Top 5 clients) et retourne un objet `ClientStats`.

5.  **Génération des Graphiques (`chart/ChartGenerator.java`):** [**Terminé**]
    *   Les méthodes `createContractStatusChart`, `createClientDistributionBySectorChart`, `createClientDistributionBySizeChart`, et `createClientRevenueDistributionChart` génèrent les quatre graphiques requis pour la Page 1 en utilisant JFreeChart à partir de l'objet `ClientStats`.

6.  **Génération du PDF (`pdf/PdfGenerator.java`):** [**Terminé**]
    *   La méthode `generateClientFinancialPage` utilise iText 7 pour :
        *   Créer la première page du document.
        *   Ajouter le titre "Statistiques des comptes clients".
        *   Convertir les quatre objets `JFreeChart` clients en images et les ajouter à la page.
        *   Ajouter la liste formatée "Top 5 des clients".

7.  **Flux Principal de l'Application (`main/ReportApplication.java`):** [**Terminé pour Page 1**]
    *   La méthode `main` orchestre la séquence pour la Page 1 :
        *   Chargement de `ApiConfig`.
        *   Instanciation des services (`ApiClient`, `ReportService`, `PdfGenerator`).
        *   Authentification via `apiClient.login()`.
        *   Appel des méthodes `get...()` de l'API client.
        *   Appel de `reportService.processClientFinancialData(...)`.
        *   Appel des méthodes `create...Chart(...)` de `ChartGenerator`.
        *   Appel de `pdfGenerator.generateClientFinancialPage(...)`.
        *   Sauvegarde du document PDF (`output/report.pdf`).
        *   Journalisation (SLF4j) et gestion des erreurs (`try-catch`) pour le flux principal.

8.  **Dépendances & Exigences API:** [**Vérifié/Implémenté**]
    *   Les points de terminaison API PHP `/api/admin/companies`, `/api/admin/contracts`, `/api/admin/quotes` et `/api/admin/invoices` sont fonctionnels (selon les informations précédentes).
    *   Les dépendances Java (iText 7, JFreeChart, Jackson, HttpClient, SLF4j) sont configurées dans `pom.xml`.
