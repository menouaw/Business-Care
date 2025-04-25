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

## Détails d'Implémentation - Reporting Financier (Page 1)

Cette section détaille les étapes pour implémenter la première page du rapport PDF axée sur les "Statistiques des comptes clients", en s'appuyant sur la structure de projet existante (`src/main/java/com/businesscare/reporting/`).

1.  **Configuration (`client/ApiConfig.java`, `util/ConfigLoader.java`):**
    *   Définir les champs dans `ApiConfig` pour contenir l'URL de base de l'API, l'email de l'utilisateur admin et son mot de passe.
    *   Implémenter `ConfigLoader` dans le package `util` pour lire les variables d'environnement (`API_BASE_URL`, `API_USER`, `API_PASSWORD`). Fournir les valeurs par défaut documentées si les variables d'environnement ne sont pas définies.
    *   Instancier et utiliser l'objet `ApiConfig` peuplé dans le flux principal de l'application (`main/ReportApplication.java`).

2.  **Modèles de Données (package `model/`):**
    *   Définir des Plain Old Java Objects (POJOs) pour chaque structure de données pertinente retournée par l'API :
        *   `Company.java`: Représentant les données de `GET /api/admin/companies`. Inclure des champs comme `id`, `nom`, `siret`, `ville`, `taille_entreprise`, `secteur_activite`, et potentiellement des listes d'IDs pour `contracts`, `quotes`, `invoices` si les détails sont récupérés.
        *   `Contract.java`: Représentant les données de `GET /api/admin/contracts`. Inclure des champs comme `id`, `entreprise_id`, `service_id`, `date_debut`, `date_fin`, `statut`, etc. Envisager d'intégrer `ServiceDetails`.
        *   `Quote.java`: Représentant les données de `GET /api/admin/quotes`. Inclure `id`, `entreprise_id`, `date_creation`, `date_validite`, `montant_total`, `statut`, et potentiellement une liste d'objets `QuotePrestation` pour les lignes d'items.
        *   `Invoice.java`: Représentant les données de `GET /api/admin/invoices`. Inclure `id`, `entreprise_id`, `numero_facture`, `date_emission`, `date_echeance`, `montant_total`, `statut`, `devis_id`, et potentiellement `line_items` dérivés du devis.
        *   `ServiceDetails.java`: Un POJO pour représenter les détails de service intégrés dans un `Contract`.
        *   `QuotePrestation.java`: Un POJO pour les lignes d'items dans un `Quote`.
    *   Déplacer les classes imbriquées existantes (`AuthResponse`, `ApiResponse`, `ErrorResponse`, `User`) de `ApiClient.java` dans ce package en tant que classes de premier niveau distinctes pour une meilleure organisation.
    *   Utiliser les annotations Jackson (`@JsonProperty`, `@JsonIgnoreProperties(ignoreUnknown = true)`) si nécessaire pour un mapping JSON correct.

3.  **Améliorations du Client API (`client/ApiClient.java`):**
    *   S'assurer que `ApiClient` est instancié avec `ApiConfig`.
    *   Implémenter des méthodes pour récupérer les listes de données financières requises, en utilisant le jeton d'authentification :
        *   `public List<Contract> getContracts() throws IOException, ApiException;` (Appelle `GET /api/admin/contracts`)
        *   `public List<Quote> getQuotes() throws IOException, ApiException;` (Appelle `GET /api/admin/quotes`) - *Dépend de l'implémentation de l'API PHP.*
        *   `public List<Invoice> getInvoices() throws IOException, ApiException;` (Appelle `GET /api/admin/invoices`) - *Dépend de l'implémentation de l'API PHP.*
    *   Gérer les réponses de l'API, désérialiser le JSON en utilisant Jackson vers les objets `model` définis, et lancer `ApiException` en cas d'erreur.

4.  **Traitement des Données (`service/DataProcessingService.java`):**
    *   Créer une méthode spécifique pour les statistiques clients, par ex., `public ClientStats processClientData(List<Company> companies, List<Contract> contracts, List<Invoice> invoices)`.
    *   À l'intérieur de cette méthode, effectuer les calculs nécessaires pour la Page 1 :
        *   Lier les factures et contrats aux entreprises en utilisant `entreprise_id`.
        *   Calculer le revenu total par client (somme des factures payées).
        *   Déterminer la distribution des clients par attributs spécifiés (par ex., `secteur_activite`, `taille_entreprise`).
        *   Calculer la distribution du statut des contrats.
        *   Identifier le Top 5 des clients selon des critères définis (par ex., revenu total).
    *   L'objet `ClientStats` retourné doit encapsuler toutes les données agrégées nécessaires pour les graphiques et la liste du Top 5.

5.  **Génération des Graphiques (`chart/ChartGenerator.java`):**
    *   Implémenter des méthodes pour générer les quatre graphiques de statistiques clients requis en utilisant JFreeChart :
        *   `public JFreeChart createClientRevenueChart(ClientStats stats);` (par ex., graphique camembert de la distribution des revenus)
        *   `public JFreeChart createClientDistributionChart(ClientStats stats);` (par ex., diagramme à barres par secteur)
        *   `public JFreeChart createContractStatusChart(ClientStats stats);` (par ex., graphique camembert des contrats actifs/inactifs)
        *   *(Définir le quatrième graphique selon les exigences)*
    *   Ces méthodes prendront l'objet `ClientStats` en entrée et retourneront des instances `JFreeChart` configurées.

6.  **Génération du PDF (`pdf/PdfReportGenerator.java`):**
    *   Concentrer la méthode `generate` (ou une partie dédiée de celle-ci) sur la création de la Page 1.
    *   Utiliser iText pour :
        *   Créer le document et un `PdfWriter` pointant vers `output/report.pdf`.
        *   Ajouter le titre "Statistiques des comptes clients".
        *   Convertir les quatre objets `JFreeChart` clients en objets `Image` iText.
        *   Ajouter les quatre images à la première page, en les organisant logiquement.
        *   Ajouter la liste "Top 5 des clients les plus fidèles", formatée clairement, en utilisant les données de l'objet `ClientStats`.

7.  **Flux Principal de l'Application (`main/ReportApplication.java`):**
    *   Modifier la méthode `main` pour inclure la séquence du reporting financier :
        *   Charger `ApiConfig`.
        *   Instancier `ApiClient` et les autres services.
        *   Effectuer `apiClient.login()`.
        *   Appeler `apiClient.getCompanies()`, `apiClient.getContracts()`, `apiClient.getInvoices()`, `apiClient.getQuotes()`.
        *   *(Optionnel : Vérifier le volume de données et générer des données si nécessaire)*.
        *   Appeler `dataProcessingService.processClientData(...)`.
        *   Appeler `chartGenerator.createClient...Chart(...)` pour les quatre graphiques clients.
        *   Appeler `pdfReportGenerator.generate(...)` (en se concentrant initialement sur la logique de la Page 1).
        *   Inclure une journalisation complète (SLF4j) et la gestion des erreurs (`try-catch` pour `ApiException`, `IOException`).

8.  **Dépendances & Exigences API:**
    *   S'assurer que le backend de l'API PHP fournit des points de terminaison `/api/admin/quotes` et `/api/admin/invoices` fonctionnels retournant les structures de données nécessaires, y compris les lignes d'items pour les détails.
    *   Vérifier que toutes les dépendances Java (iText, JFreeChart, Jackson, HttpClient, SLF4j) sont correctement configurées dans `pom.xml`.
