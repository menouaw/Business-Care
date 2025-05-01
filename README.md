# Business Care - Plateforme de Bien-être et Services pour Entreprises

Business Care est une application web complète dédiée à la gestion des services de bien-être pour les entreprises. La plateforme permet aux entreprises de souscrire à des contrats de services pour offrir des prestations de bien-être à leurs employés, telles que des cours de yoga, des séminaires sur la gestion du stress, des consultations avec des nutritionnistes, et bien plus encore.

## Architecture et Technologies

### Frontend
- HTML5, CSS3, JavaScript (ES6+)
- Framework CSS : Bootstrap 5
- jQuery pour les interactions dynamiques
- AJAX pour les requêtes asynchrones

### Backend
- PHP 8.1+ pour la logique serveur
- API REST pour la communication entre le client et le serveur
- Java pour l'application de reporting autonome
- MySQL pour la persistance des données

### Sécurité
- Sessions PHP sécurisées
- Protection contre les attaques CSRF
- Validation des données côté serveur
- Authentification à deux facteurs pour les comptes administrateurs
- HTTPS obligatoire
- Règles de firewalling précises

## Structure du Projet

```
business-care/
├── api/                     # API REST pour toutes les communications client-serveur
│   ├── admin/               # Endpoints pour l'interface admin et l'app Java
│   └── client/              # Endpoints pour l'interface client et mobile
├── assets/                  # Ressources statiques (CSS, JS, images)
├── build/                   # Fichiers générés lors du build
├── cloud-services/          # Intégrations avec services cloud (Datalog, Key Vault)
├── database/                # Scripts de base de données
│   ├── migrations/          # Changements incrémentels de schéma
│   ├── schemas/             # Définition du schéma principal
│   └── seeders/             # Données initiales
├── desktop-app/             # Application desktop complémentaire
├── docker/                  # Configurations Docker
├── i18n/                    # Fichiers de traduction multilingue
├── infrastructure/          # Configuration d'infrastructure
├── integrations/            # Intégrations externes (Stripe, OneSignal)
│   └── onesignal/           # Intégration pour notifications push
├── java-app/                # Application Java pour le reporting PDF
├── mobile-app/              # Application mobile
├── shared/                  # Fichiers partagés entre web-admin et web-client
│   ├── web-admin/           # Bibliothèques partagées pour l'admin
│   └── web-client/          # Bibliothèques partagées pour le client
├── tests/                   # Tests automatisés
├── web-admin/               # Interface d'administration
└── web-client/              # Interface utilisateur client
```

## Interfaces Principales

### Interface Client (web-client)
Permet aux entreprises et à leurs employés d'accéder aux services de bien-être.
- Gestion des comptes entreprises
- Espace dédié aux employés avec tutoriel interactif à la première connexion
- Recherche et réservation de services
- Consultation des contrats et factures
- Participation à des évènements et communautés

### Interface Admin (web-admin)
Permet aux administrateurs de Business Care de gérer la plateforme.
- Gestion des entreprises clientes
- Gestion des prestataires de services
- Facturation et suivi des contrats
- Modération des communautés
- Génération de rapports analytiques

### Application Java de Reporting
Application autonome générant des rapports d'activité périodiques au format PDF.
- Statistiques sur les comptes clients
- Analyse des évènements
- Performance des services
- Graphiques et tableaux de synthèse
- Génération automatique quotidienne à 2h00 du matin

## Installation

### Prérequis
- PHP 8.1+
- MySQL 8.0+
- Serveur Web (Apache/Nginx)
- Java JDK 17+ (pour l'application de reporting)
- Maven (pour compiler l'application Java)
- Composer (pour les dépendances PHP)
- Docker et Docker Compose (pour le déploiement conteneurisé)

### Installation avec Docker (recommandée)

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/your-organization/business-care.git
   cd business-care
   ```

2. **Configuration des variables d'environnement**
   ```bash
   cp .env.example .env
   # Modifier les variables dans le fichier .env selon votre environnement
   ```

3. **Lancer les conteneurs Docker**
   ```bash
   docker compose up --build -d
   ```

4. **Accéder à l'application**
   - Interface admin: `https://votre-domaine.com/web-admin/`
   - Interface client: `https://votre-domaine.com/web-client/`

### Installation manuelle

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/your-organization/business-care.git
   cd business-care
   ```

2. **Installation des dépendances PHP**
   ```bash
   composer install
   ```

3. **Configuration de la base de données**
   ```bash
   # Créer la base de données
   mysql -u root -p < database/setup.sql
   
   # Importer le schéma
   mysql -u root -p business_care < database/schemas/business_care.sql
   
   # Importer les triggers et vues
   mysql -u root -p business_care < database/schemas/triggers.sql
   mysql -u root -p business_care < database/schemas/views.sql
   
   # Importer les données d'exemple
   mysql -u root -p business_care < database/seeders/sample_data.sql
   ```

4. **Configuration du serveur web**
   - Configurer un vhost pointant vers le répertoire racine du projet
   - Activer la réécriture d'URL (mod_rewrite pour Apache)
   - Sécuriser l'accès avec HTTPS

5. **Compilation de l'application Java**
   ```bash
   cd java-app
   mvnd clean package
   ```

## Fonctionnalités Principales

### Gestion des Entreprises
- Inscription et gestion de profil
- Consultation des contrats actifs
- Suivi des services utilisés par les employés
- Accès aux factures et paiements générés automatiquement en PDF

### Portail des Employés
- Accès aux services disponibles
- Réservation de prestations
- Participation aux évènements
- Interaction au sein des communautés
- Réception de notifications push

### Administration
- Gestion complète des utilisateurs
- Création et modification des services
- Suivi financier et facturation
- Reporting et analyses

## Conteneurisation et Déploiement
Le projet est entièrement conteneurisé via Docker pour faciliter le développement et la production.
Un fichier `docker-compose.yml` est fourni à la racine du projet pour orchestrer les différents services:
- Serveur web (Nginx)
- PHP-FPM
- MySQL
- Application Java

Les parties 1 et 2 du projet sont déployées via Docker en cas de panne, hébergées sur l'infrastructure mise en place dans la Mission 3.

## Multilinguisme
L'application est entièrement multilingue via le dossier `i18n/` contenant les fichiers de traduction.
La langue par défaut est le français, mais l'anglais est également disponible.
Il est possible d'ajouter des langues sans modifier le code ni utiliser Google Traduction.

## Sécurité et Sauvegarde
- Des règles de firewalling très précises ne laissant passer que les flux désirés
- L'accès HTTPS obligatoire via SSL/TLS
- Un système de sauvegarde régulière des données
- La connexion sécurisée avec des jetons d'authentification
- Utilisation de services Cloud pour l'authentification

## Intégrations

### Stripe
Intégration complète pour la gestion des paiements et la génération automatique de factures.

### OneSignal
Intégration pour l'envoi de notifications push aux employés.

### Services Cloud
- Datalog pour la journalisation
- Key Vault pour la gestion sécurisée des secrets

## API REST
L'API REST est documentée et accessible via `/api/`. Elle est utilisée pour gérer l'ensemble des traitements de l'application.
Les deux principales sections sont:
- `/api/client/` - Endpoints pour l'interface client
- `/api/admin/` - Endpoints pour l'interface admin et l'application Java

## Gestion de Projet
- Utilisation obligatoire de GitHub pour le versionnement du code
- Mise en place d'un Trello pour la gestion des tâches

## Documentation
La documentation complète du projet est disponible via PHPDocumentor.
Pour générer la documentation:
```bash
php phpDocumentor.phar -c phpdoc.xml
```