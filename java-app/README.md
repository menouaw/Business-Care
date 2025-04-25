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

L'application est actuellement à l'état de **squelette de base**. Le point d'entrée (`ReportApplication.java`) est défini, mais la logique principale reste à implémenter :

*   [ ] Authentification auprès de l'API.
*   [ ] Récupération des données depuis les différents endpoints de l'API (sociétés, contrats, devis, factures, événements, services).
*   [ ] Vérification du volume de données et génération de données aléatoires si nécessaire.
*   [ ] Implémentation de la génération des graphiques (JFreeChart).
*   [ ] Implémentation de la structure et de la génération du document PDF (iText).
*   [ ] Gestion d'erreurs robuste.
