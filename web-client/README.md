# Business Care - Interface Client

L'interface client est la partie frontend permettant aux clients et salariés d'entreprises d'accéder aux services de Business Care. Cette interface centralise tous les outils nécessaires pour bénéficier des prestations proposées.

## Structure du projet

```
web-client/                  # Dossier principal de l'interface client
├── includes/                # Fichiers d'inclusion PHP
│   ├── init.php             # Initialisation du système
│   └── page_functions/      # Fonctions spécifiques à chaque page
├── modules/                 # Modules fonctionnels
│   ├── profile/             # Gestion du profil utilisateur
│   ├── providers/           # Prestataires de services
│   │   ├── index.php        # Liste des prestataires
│   │   ├── view.php         # Profil du prestataire
│   │   ├── search.php       # Recherche de prestataires
│   │   ├── calendar.php     # Calendrier de disponibilités
│   │   ├── ratings.php      # Évaluations et avis
│   │   ├── categories.php   # Catégories de prestataires
│   │   ├── contracts.php    # Contrats avec les prestataires
│   │   ├── services.php     # Services offerts
│   │   ├── invoices.php     # Factures des prestations
│   │   └── settings.php     # Paramètres prestataires
│   ├── contracts/           # Suivi des contrats
│   ├── companies/           # Gestion des entreprises clientes
│   │   ├── index.php        # Liste des entreprises
│   │   ├── view.php         # Détails d'une entreprise
│   │   ├── edit.php         # Modification d'une entreprise
│   │   ├── employees.php    # Gestion des collaborateurs
│   │   ├── contracts.php    # Contrats de l'entreprise
│   │   ├── quotes.php       # Devis et propositions
│   │   ├── invoices.php     # Factures de l'entreprise
│   │   ├── payments.php     # Historique des paiements
│   │   ├── reports.php      # Rapports et analyses
│   │   └── settings.php     # Paramètres de l'entreprise
│   ├── employees/           # Gestion des salariés
│   │   ├── index.php        # Tableau de bord salarié
│   │   ├── view.php         # Profil du salarié
│   │   ├── edit.php         # Modification du profil
│   │   ├── services.php     # Services disponibles
│   │   ├── reservations.php # Réservations de services
│   │   ├── appointments.php # Rendez-vous médicaux
│   │   ├── history.php      # Historique d'activités
│   │   ├── communities.php  # Espace communautaire
│   │   ├── donations.php    # Gestion des dons
│   │   ├── events.php       # Défis et événements
│   │   └── settings.php     # Préférences utilisateur
│   └── appointments/        # Gestion des rendez-vous
├── templates/               # Modèles d'interface
│   ├── header.php           # En-tête commune
│   ├── footer.php           # Pied de page commun
│   └── sidebar.php          # Barre latérale de navigation
├── assets/                  # Ressources statiques (css, js, images)
├── index.php                # Page d'accueil/tableau de bord
├── connexion.php            # Page de connexion
├── inscription.php          # Page d'inscription
└── deconnexion.php          # Script de déconnexion
```

### Fichiers partagés

Les fichiers communs partagés entre plusieurs composants de l'application se trouvent dans le dossier `/shared/web-client/` :
```
shared/web-client/           # Fichiers partagés pour l'interface client
├── config.php               # Configuration de l'application
├── db.php                   # Fonctions de base de données
├── auth.php                 # Fonctions d'authentification
├── functions.php            # Fonctions utilitaires
└── logging.php              # Fonctions de journalisation
```

## API REST

L'API REST se trouve dans le dossier `/api` à la racine du projet et comprend les points d'entrée suivants pour les clients :

```
/api/                       # Point d'entrée principal
├── client/                 # Endpoints pour l'interface client
│   ├── auth.php            # Authentification
│   ├── profile.php         # Gestion du profil
│   ├── providers.php       # Prestataires de services
│   ├── contracts.php       # Suivi des contrats
│   ├── companies.php       # Gestion des entreprises
│   ├── employees.php       # Gestion des salariés
│   └── appointments.php    # Gestion des rendez-vous
```

### Points d'entrée API disponibles

- `POST /api/client/auth` - Authentification
- `PUT /api/client/auth` - Modification de mot de passe
- `DELETE /api/client/auth` - Déconnexion

- `GET /api/client/profile` - Détails du profil utilisateur
- `PUT /api/client/profile` - Mise à jour du profil

- `GET /api/client/providers` - Liste des prestataires de services
- `GET /api/client/providers/{id}` - Profil d'un prestataire
- `GET /api/client/providers/{id}/services` - Services offerts par un prestataire
- `GET /api/client/providers/{id}/ratings` - Évaluations d'un prestataire
- `GET /api/client/providers/categories` - Liste des catégories de prestataires

- `GET /api/client/contracts` - Liste des contrats de l'entreprise
- `GET /api/client/contracts/{id}` - Détail d'un contrat spécifique

- `GET /api/client/appointments` - Liste des rendez-vous
- `GET /api/client/appointments/{id}` - Détail d'un rendez-vous
- `POST /api/client/appointments` - Création d'un rendez-vous
- `PUT /api/client/appointments/{id}` - Modification d'un rendez-vous
- `DELETE /api/client/appointments/{id}` - Annulation d'un rendez-vous

## Configuration

1. S'assurer que la base de données MySQL est configurée
2. Vérifier que le script SQL depuis `/database/setup.sql` a été importé
3. Modifier les paramètres de connexion dans `/shared/web-client/config.php` si nécessaire
4. Les préférences utilisateur sont stockées dans la table `preferences_utilisateurs`

## Fonctionnalités principales

- Tableau de bord personnalisé pour les clients
- Recherche et filtrage des prestataires de services
- Prise de rendez-vous et gestion des réservations
- Suivi des contrats et services souscrits
- Gestion du profil utilisateur et préférences
- Historique des rendez-vous et évaluations
- Gestion des paiements et factures

## Développement

### Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web Apache avec mod_rewrite activé
- Composer pour la gestion des dépendances

### Installation pour le développement

1. Cloner le dépôt GitHub
2. S'assurer que la base de données est configurée
3. Exécuter `composer install` pour installer les dépendances
4. Configurer le fichier `shared/web-client/config.php`

## Architecture du code

### Structure des fichiers

Pour maintenir une organisation claire du code, les fonctions sont séparées selon leur responsabilité:

- `/shared/web-client/` - Contient les fichiers partagés du système
  - `config.php` - Configuration générale et constantes
  - `db.php` - Connexion à la base de données et fonctions de requêtes
  - `auth.php` - Fonctions d'authentification et gestion des sessions
  - `functions.php` - Fonctions utilitaires générales
  - `logging.php` - Journalisation des événements système

- `/web-client/includes/` - Contient les fichiers spécifiques à l'interface client
  - `init.php` - Initialisation du système
  - `/page_functions/` - Fonctions spécifiques à chaque page
    - `dashboard.php` - Fonctions pour le tableau de bord
    - `login.php` - Fonctions de traitement du login
    - `/modules/` - Fonctions spécifiques aux modules
      - `profile.php` - Gestion du profil
      - `providers.php` - Gestion des prestataires
      - `contracts.php` - Gestion des contrats
      - `companies.php` - Gestion des entreprises
      - `employees.php` - Gestion des salariés
      - `appointments.php` - Gestion des rendez-vous

### API Client

L'API client suit une architecture RESTful et gère:

1. **Authentification** (`auth.php`):
   - Login des clients avec vérification du rôle
   - Changement de mot de passe
   - Déconnexion et gestion des tokens

2. **Profil** (`profile.php`):
   - Récupération des informations du profil utilisateur
   - Mise à jour des informations personnelles
   - Gestion des préférences utilisateur

3. **Prestataires** (`providers.php`):
   - Listage des prestataires de services
   - Profils détaillés et disponibilités
   - Évaluations et avis des clients
   - Services proposés et tarification
   - Catégories de prestataires

4. **Entreprises** (`companies.php`):
   - Gestion des informations des entreprises clientes
   - Accès aux collaborateurs, contrats et facturation
   - Génération de devis personnalisés
   - Suivi des paiements et abonnements

5. **Salariés** (`employees.php`):
   - Gestion des comptes et profils des salariés
   - Accès aux services, réservations et rendez-vous
   - Participation aux communautés et défis
   - Gestion des dons et engagement associatif

6. **Contrats** (`contracts.php`):
   - Récupération des contrats de l'entreprise du client
   - Accès aux détails d'un contrat, services inclus et historique des paiements

7. **Rendez-vous** (`appointments.php`):
   - Gestion complète des rendez-vous (création, modification, annulation)
   - Consultation de l'historique des rendez-vous
   - Filtrage par statut et date

### Avantages de cette organisation

1. **Interface intuitive**: Centrée sur les besoins des clients
2. **Accès personnalisé**: Chaque client accède uniquement à ses données
3. **Réactivité**: Interface optimisée pour une utilisation sur différents appareils
4. **Facilité d'utilisation**: Processus guidés pour la prise de rendez-vous
5. **Transparence**: Accès complet à l'historique et aux contrats
6. **Personnalisation**: Préférences utilisateur pour adapter l'expérience

## Sécurité

- Authentification sécurisée des utilisateurs
- Vérification des rôles et permissions
- Protection des données personnelles
- Validation et nettoyage de toutes les entrées utilisateur
- Utilisation de jetons pour l'authentification à l'API
- Protection contre les attaques XSS et CSRF
- Journalisation des activités de sécurité
