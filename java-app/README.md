# Application Java de Reporting pour Business Care

## Objectif

Cette application Java autonome est conçue pour générer des rapports d'activité périodiques au format PDF à destination des responsables de Business Care. Elle récupère les données nécessaires via l'API REST de l'application principale et les synthétise sous forme de graphiques et de listes pour faciliter la prise de décision.

Le rapport généré contient trois pages :
*   **Page 1 :** Statistiques sur les comptes clients (4 graphiques, Top 5 clients).
*   **Page 2 :** Statistiques sur les évènements (4 graphiques, Top 5 évènements).
*   **Page 3 :** Statistiques sur les prestations/services (4 graphiques).

## Stack Technique

*   **Langage :** Java (JDK 17)
*   **Gestion de build et dépendances :** Apache Maven
*   **Génération PDF :** iText 8.0.4 - Bibliothèque pour créer et manipuler des documents PDF.
*   **Génération Graphiques :** JFreeChart 1.5.4 - Framework pour générer divers types de graphiques.
*   **Client HTTP :** Apache HttpClient 5.3.1 - Utilisé pour envoyer des requêtes HTTP à l'API REST de Business Care.
*   **Traitement JSON :** Jackson Databind 2.17.0 - Convertit les objets Java en JSON et vice-versa (inclut `jackson-datatype-jsr310` pour les dates/heures Java).
*   **Logging :** SLF4J 2.0.12 (avec implémentation `slf4j-simple`) - Façade de journalisation.

## Structure du Projet

L'application est organisée en plusieurs packages sous `src/main/java/com/businesscare/reporting`:

*   **`main`** : Point d'entrée de l'application (`ReportApplication`)
*   **`client`** : Communication avec l'API (`ApiClient`, `ApiConfig`)
*   **`model`** : Modèles de données (POJOs et Enums)
*   **`service`** : Logique métier et traitement des données (`ReportService`)
*   **`chart`** : Génération des graphiques (`ChartGenerator`)
*   **`pdf`** : Génération du PDF (`PdfGenerator`)
*   **`util`** : Classes utilitaires (`ConfigLoader`, `Constants`)
*   **`exception`** : Exceptions personnalisées (`ApiException`)

*(Note: Le répertoire `src/test/java` contenant les tests unitaires existe mais est exclu de cette description.)*

## Prérequis

*   **JDK** (Java Development Kit) version 17 ou supérieure.
*   **Apache Maven** ([https://maven.apache.org/download.cgi](https://maven.apache.org/download.cgi))
*   L'**API REST** de Business Care doit être accessible (`http://localhost/api/admin` par défaut, ou via Docker `http://nginx/api/admin`).

## Build

1.  Naviguez vers le répertoire `java-app` à la racine du projet Business-Care.
2.  Exécutez la commande Maven suivante pour compiler le code et créer un JAR exécutable ("fat JAR") incluant toutes les dépendances :

    ```bash
    # Optionnel: Pour builder sans exécuter les tests (si présents)
    # mvnd clean package -DskipTests
    mvnd clean package
    ```
3.  Le JAR exécutable sera généré dans le répertoire `target/` avec le nom `reporting-app.jar`.

## Configuration

L'application récupère sa configuration depuis les variables d'environnement :

*   `API_BASE_URL`: L'URL de base de l'API Admin de Business Care. Par défaut : `http://localhost/api/admin`.
    *   Dans l'environnement Docker fourni (`docker-compose.yml`), cette variable est automatiquement définie à `http://nginx/api/admin` pour le service `java-app`.
*   `API_USER`: L'adresse email de l'utilisateur admin pour s'authentifier auprès de l'API. Par défaut : `admin@businesscare.fr`.
*   `API_PASSWORD`: Le mot de passe de l'utilisateur admin. Par défaut : `admin123`.

## Exécution (via Docker)

L'application est conçue pour être exécutée **une seule fois** lors du démarrage de son conteneur Docker.

1.  Assurez-vous que l'image Docker a été construite (voir la section Build et Containerisation).
2.  Lancez l'ensemble des services avec `docker-compose up -d`.
3.  Le conteneur `java-app` exécutera la commande `java -jar /app/app.jar` dès son lancement, générant ainsi le rapport.
4.  Une fois l'exécution terminée, le conteneur s'arrêtera (sauf si configuré autrement dans `docker-compose.yml`).
5.  Les logs de l'exécution sont visibles via `docker logs business_care_java_app` (avant qu'il ne s'arrête ou si vous le relancez).

L'exécution manuelle via `java -jar target/reporting-app.jar` est toujours possible localement pour des tests ou une génération ponctuelle.

## Sortie (Output)

L'application génère un fichier PDF nommé `report_JJ-MM-AAAA.pdf` (où `JJ-MM-AAAA` représente la date de génération) dans le sous-répertoire `output/` du répertoire `java-app` (`java-app/output/report_JJ-MM-AAAA.pdf`).

*   Le répertoire `output/` est créé automatiquement s'il n'existe pas lors de l'exécution.
*   Dans l'environnement Docker, le répertoire local `./java-app/output` est monté sur `/app/output` dans le conteneur via un volume défini dans `docker-compose.yml`, rendant les PDFs générés accessibles sur la machine hôte.

## Intégration avec `web-admin`

Les rapports PDF générés sont destinés à être visualisés depuis l'interface `web-admin`.

*   Étant donné que le nom du fichier change quotidiennement (ou à chaque exécution), l'interface `web-admin` ne peut pas utiliser un lien direct statique.
*   Une approche possible serait pour `web-admin` (par exemple, une page `web-admin/modules/reports/index.php`) de lister les fichiers `.pdf` présents dans le répertoire `./java-app/output` (accessible car il est dans le même projet ou via un chemin relatif/absolu configuré) et de proposer des liens vers les rapports existants, en triant par date par exemple.
*   Cela nécessite que le serveur web (Nginx/Apache) soit configuré pour servir les fichiers statiques du répertoire `java-app/output/` (via un alias ou une directive `location` dans la configuration Nginx ou Apache).

## Flux d'exécution

L'application suit le flux suivant lors de son unique exécution :

1. **Configuration :** Chargement des paramètres via ConfigLoader.
2. **Authentification :** Login auprès de l'API admin via ApiClient.
3. **Collecte des données :** Récupération des informations sur les entreprises, contrats, devis, factures, évènements et prestations.
4. **Traitement :** Calcul des statistiques client, évènement et prestation via ReportService.
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
* `/api/admin/events.php` - Données des évènements
* `/api/admin/services.php` - Données des prestations/services

## Fonctionnalités implémentées

L'application a implémenté les fonctionnalités requises pour la génération de rapport :

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
* [X] Génération des Pages 2 et 3 du PDF (évènements et prestations) (`PdfGenerator`)
* [X] Gestion d'erreurs (`ApiException`, `ReportGenerationException`, et `try-catch` dans `ReportApplication`)

## Containerisation

Un `Dockerfile` (`java-app/Dockerfile`) est fourni pour containeriser l'application.

*   Il utilise une approche **multi-stage** :
    *   Une étape `build` avec `maven:3.9-eclipse-temurin-17` pour compiler le code et créer le JAR exécutable.
    *   Une étape finale basée sur `eclipse-temurin:17-jre-alpine` (image JRE légère).
*   L'étape finale copie le JAR `reporting-app.jar` dans le conteneur.
*   Elle crée un répertoire `/app/output` pour les rapports générés.
*   Le `CMD` final du Dockerfile est `java -jar /app/app.jar`, ce qui **lance l'application une seule fois** au démarrage du conteneur pour générer le rapport.
*   Le fichier `docker-compose.yml` à la racine du projet définit le service `java-app`, construit l'image à partir de ce Dockerfile, monte le volume local `./java-app/output` sur `/app/output` dans le conteneur, et fournit les variables d'environnement nécessaires (`API_BASE_URL`, `API_USER`, `API_PASSWORD`) pour que l'application puisse s'authentifier auprès de l'API.

Pour lancer l'application via Docker Compose :
```bash
docker compose up -d --build # La première fois ou pour reconstruire
# ou
docker compose up -d         # Pour démarrer les conteneurs existants (lancera la génération du rapport)
```
