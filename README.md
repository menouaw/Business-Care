# Business-Care
Projet annuel de deuxième année

## Aperçu du Projet

Business-Care est une application web conçue pour [**Veuillez décrire brièvement l'objectif principal de l'application Business-Care ici. Ex: "gérer les services et les interactions entre les entreprises, les employés et les prestataires."**]

## Stack Technologique

*   **Backend:** PHP (API située dans `/api`)
*   **Frontend:** PHP, HTML, CSS, JavaScript (Interfaces séparées pour Admin dans `/web-admin` et Client dans `/web-client`)
*   **Base de données:** [**Veuillez spécifier la base de données utilisée, ex: MySQL, PostgreSQL**] (Schéma et migrations dans `/database`)
*   **Serveur Web:** Probablement Apache ou Nginx (Une configuration peut être nécessaire pour la réécriture d'URL basée sur `.htaccess` dans `/api`)
*   **Dépendances:** Gérées via Composer (`composer.json`)

## Structure du Projet

Le projet est organisé dans les répertoires principaux suivants :

*   `api/`: Contient les points d'accès de l'API backend utilisés par les interfaces admin et client.
*   `assets/`: Contient les fichiers statiques comme CSS, JavaScript, images et polices.
*   `database/`: Inclut les schémas de base de données, migrations, seeders et scripts d'installation.
*   `shared/`: Contient le code PHP partagé (comme la configuration, la connexion à la base de données, les fonctions) utilisé par `web-admin` et `web-client`.
*   `web-admin/`: L'interface d'administration pour gérer l'application.
*   `web-client/`: L'interface destinée aux clients/employés.
*   `i18n/`: Probablement destiné aux fichiers d'internationalisation/localisation.
*   `tests/`: Pour les tests de l'application.
*   `cloud-services/`, `infrastructure/`, `integrations/`, `java-app/`, `mobile-app/`, `desktop-app/`: Répertoires de placeholders ou sous-projets liés.

## Installation et Configuration

1.  **Prérequis:**
    *   PHP (spécifier la version si connue)
    *   [Serveur de Base de Données spécifié ci-dessus]
    *   Composer
    *   Serveur Web (Apache/Nginx)
2.  **Cloner le dépôt:** `git clone <url-du-depot>`
3.  **Installer les dépendances PHP:** `composer install`
4.  **Configurer la base de données:**
    *   Créer une base de données (ex: `business_care`).
    *   Importer le schéma depuis `database/schemas/business_care.sql`.
    *   Optionnellement, exécuter les seeders depuis `database/seeders/`.
5.  **Configurer la connexion à la base de données:** Mettre à jour les identifiants de la base de données dans `shared/web-admin/config.php` et `shared/web-client/config.php`.
6.  **Configurer votre serveur web:** Définir la racine des documents (document root) de vos hôtes virtuels pour pointer respectivement vers les répertoires `web-admin` et `web-client`. Assurez-vous que la réécriture d'URL (mod_rewrite pour Apache) est activée, en particulier pour l'API.
7.  **(Configuration Docker - Bientôt disponible):** Les instructions pour exécuter l'application avec Docker seront ajoutées ici.

## Utilisation

*   **Interface Admin:** Accessible via `http://<votre-domaine-admin>/`
*   **Interface Client:** Accessible via `http://<votre-domaine-client>/`

---

## Structure des Fichiers (Détaillée)

```
tree: |____api
| |____.htaccess
| |____admin
| | |____auth.php
| | |____companies.php
| | |____contracts.php
| | |____services.php
| | |____users.php
| |____client
| | |____appointments.php
| | |____auth.php
| | |____contracts.php
| | |____profile.php
| | |____services.php
| |____index.php
|____assets
| |____css
| | |____admin.css
| | |____client.css
| |____fonts
| |____images
| | |____.gitkeep
| | |____cover
| | | |____cucumber.jpg
| | | |____goldOnBlack.jpg
| | | |____goldOnBlackSimpleLogo.jpg
| | | |____peach.jpg
| | | |____sky.gif
| | |____exemple
| | | |____mugs.png
| | | |____sacs.png
| | |____logo
| | | |____goldOnBlack.jpg
| | | |____goldOnWhite.jpg
| | | |____noBgBlack.png
| | | |____noBgColor.png
| | | |____noBgWhite.png
| | | |____no_padding.png
| | | |____whiteBgColor.png
| |____js
| | |____admin.js
| | |____client.js
|____build
|____cloud-services
|____composer.json
|____database
| |____migrations
| |____schemas
| | |____business_care.sql
| | |____triggers.sql
| | |____views.sql
| |____seeders
| | |____sample_data.sql
| |____setup.sql
|____desktop-app
|____i18n
|____infrastructure
|____integrations
|____java-app
|____mobile-app
|____phpdoc.xml
|____phpDocumentor.phar
|____README.md
|____shared
| |____web-admin
| | |____auth.php
| | |____config.php
| | |____db.php
| | |____functions.php
| | |____logging.php
| |____web-client
| | |____auth.php
| | |____config.php
| | |____db.php
| | |____functions.php
| | |____logging.php
|____tests
|____web-admin
| |____includes
| | |____init.php
| | |____page_functions
| | | |____dashboard.php
| | | |____login.php
| | | |____modules
| | | | |____appointments.php
| | | | |____billing.php
| | | | |____companies.php
| | | | |____contracts.php
| | | | |____donations.php
| | | | |____providers.php
| | | | |____quotes.php
| | | | |____services.php
| | | | |____users.php
| |____index.php
| |____install-admin.php
| |____login.php
| |____logout.php
| |____modules
| | |____appointments
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____index.php
| | | |____view.php
| | |____billing
| | | |____actions.php
| | | |____index.php
| | | |____view.php
| | |____companies
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____index.php
| | | |____view.php
| | |____contracts
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____index.php
| | | |____view.php
| | |____donations
| | | |____view.php
| | |____moderation
| | |____newsletter
| | |____offices
| | |____providers
| | | |____actions.php
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____edit_habilitation.php
| | | |____index.php
| | | |____view.php
| | |____quotes
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____index.php
| | | |____view.php
| | |____services
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____index.php
| | | |____view.php
| | |____users
| | | |____add.php
| | | |____delete.php
| | | |____edit.php
| | | |____index.php
| | | |____view.php
| |____README.md
| |____templates
| | |____footer.php
| | |____header.php
| | |____sidebar.php
|____web-client
| |____includes
| | |____init.php
| | |____page_functions
| | | |____login.php
| | | |____modules
| | | | |____companies.php
| | | | |____employees.php
| | | | |____providers.php
| |____index.php
| |____login.php
| |____logout.php
| |____modules
| | |____companies
| | | |____contact.php
| | | |____contracts.php
| | | |____employees.php
| | | |____index.php
| | | |____invoices.php
| | | |____quotes.php
| | | |____settings.php
| | |____employees
| | | |____appointments.php
| | | |____communities.php
| | | |____donations.php
| | | |____edit.php
| | | |____events.php
| | | |____history.php
| | | |____index.php
| | | |____reservations.php
| | | |____services.php
| | | |____settings.php
| | | |____view.php
| | |____providers
| | | |____calendar.php
| | | |____categories.php
| | | |____contracts.php
| | | |____index.php
| | | |____invoices.php
| | | |____ratings.php
| | | |____search.php
| | | |____services.php
| | | |____settings.php
| | | |____view.php
| |____README.md
| |____templates
| | |____footer.php
| | |____header.php
