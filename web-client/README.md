# Business Care - Interface Client

L'interface client est la partie frontend permettant aux clients et salariés d'entreprises d'accéder aux services de Business Care. Cette interface centralise tous les outils nécessaires pour bénéficier des prestations proposées.

## Structure actuelle du projet

```
web-client/                  # Dossier principal de l'interface client
├── includes/                # Fichiers d'inclusion PHP
│   ├── init.php             # Initialisation du système et inclusion des fichiers partagés
│   └── page_functions/      # Fonctions spécifiques à chaque page
│       ├── login.php        # Fonctions pour la gestion de l'authentification
│       └── modules/         # Fonctions spécifiques aux modules fonctionnels
│           ├── companies.php  # Fonctions pour la gestion des entreprises
│           ├── employees.php  # Fonctions pour la gestion des salariés
│           └── providers.php  # Fonctions pour la gestion des prestataires
├── modules/                 # Modules fonctionnels
│   ├── providers/           # Prestataires de services
│   ├── companies/           # Gestion des entreprises clientes
│   └── employees/           # Gestion des salariés
├── templates/               # Modèles d'interface
│   ├── header.php           # En-tête commune
│   ├── footer.php           # Pied de page commun
│   ├── companies/           # Templates spécifiques aux entreprises
│   ├── employees/           # Templates spécifiques aux salariés 
│   └── services/            # Templates de services
├── index.php                # Page d'accueil/tableau de bord
├── login.php                # Page de connexion
└── logout.php               # Script de déconnexion
```

### Fichiers partagés

Les fichiers communs partagés entre plusieurs composants de l'application se trouvent dans le dossier `/shared/web-client/` :

```
shared/web-client/           # Fichiers partagés pour l'interface client
├── config.php               # Configuration générale de l'application
├── db.php                   # Fonctions de connexion et requêtes de base de données
├── auth.php                 # Authentification et gestion des sessions
├── functions.php            # Fonctions utilitaires générales
└── logging.php              # Journalisation des évènements système
```

### Ressources statiques

Les ressources statiques sont organisées dans le dossier `assets/` à la racine du projet :

```
assets/                      # Ressources statiques
├── css/                     # Feuilles de style CSS
│   ├── client.css           # Styles spécifiques pour l'interface client
│   └── admin.css            # Styles spécifiques pour l'interface admin
├── js/                      # Scripts JavaScript
│   └── admin.js             # Fonctionnalités JavaScript pour l'interface admin
├── images/                  # Images et icônes
│   ├── logo/                # Logos et identité visuelle
│   ├── cover/               # Images d'en-tête et bannières 
│   └── exemple/             # Images d'exemple et de démonstration
└── fonts/                   # Polices de caractères
```

## État actuel du développement

Le projet Business Care est en cours de développement. La structure de base est en place, mais de nombreux fichiers sont encore vides ou en cours d'implémentation. Les principaux modules identifiés (providers, companies, employees) sont créés et prêts à recevoir leur contenu fonctionnel.

### Prochaines étapes de développement

1. Implémenter les fichiers d'authentification (login.php)
2. Développer les templates de base (header.php, footer.php)
3. Compléter les modules fonctionnels prioritaires:
   - Gestion des entreprises clientes
   - Gestion des salariés
   - Gestion des prestataires de services

## API REST

L'API REST se trouve dans le dossier `/api` à la racine du projet et comprend les points d'entrée suivants pour les clients :

```
/api/                       # Point d'entrée principal
├── client/                 # Endpoints pour l'interface client
│   ├── auth.php            # Authentification et gestion des sessions
│   ├── profile.php         # Gestion du profil utilisateur
│   ├── services.php        # Gestion des services disponibles
│   ├── contracts.php       # Suivi et gestion des contrats
│   └── appointments.php    # Gestion complète des rendez-vous
```

### Points d'entrée API disponibles

- **auth.php**: 
  - Authentification des utilisateurs
  - Gestion des sessions et tokens
  - Modification des mots de passe

- **profile.php**: 
  - Récupération et mise à jour des informations de profil
  - Gestion des préférences utilisateur

- **services.php**: 
  - Catalogue des services disponibles
  - Recherche et filtrage des services
  - Détails et disponibilités des services

- **contracts.php**: 
  - Liste et détails des contrats
  - Historique des contrats
  - Suivi de l'état des contrats

- **appointments.php**: 
  - Création, modification et annulation de rendez-vous
  - Consultation de l'historique des rendez-vous
  - Filtrage par statut, date et prestataire

## Base de données

Le projet utilise une base de données MySQL avec le schéma suivant:

### Tables principales
- `roles` - Définit les rôles utilisateur dans le système
- `entreprises` - Stocke les informations des entreprises clientes
- `personnes` - Contient les données des utilisateurs (employés, prestataires, etc.)
- `prestations` - Catalogue des services disponibles
- `contrats` - Contrats entre Business Care et les entreprises
- `devis` - Devis émis pour les entreprises
- `factures` - Factures générées et leur statut
- `rendez_vous` - Gestion des rendez-vous et consultations
- `evenements` - Évènements organisés (conférences, webinars, etc.)
- `communautes` - Espaces communautaires pour les salariés
- `dons` - Suivi des dons (financiers ou matériels)
- `evaluations` - Évaluations des prestations par les utilisateurs
- `notifications` - Système de notifications internes
- `logs` - Journalisation des activités
- `remember_me_tokens` - Jetons de connexion persistante
- `preferences_utilisateurs` - Préférences de langue, thème, etc.

## Architecture du code

### Structure des fichiers

Pour maintenir une organisation claire du code, les fonctions sont séparées selon leur responsabilité:

- `/shared/web-client/` - Contient les fichiers partagés du système
  - `config.php` - Configuration générale et constantes
  - `db.php` - Connexion à la base de données et fonctions de requêtes
  - `auth.php` - Fonctions d'authentification et gestion des sessions
  - `functions.php` - Fonctions utilitaires générales
  - `logging.php` - Journalisation des évènements système

- `/web-client/includes/` - Contient les fichiers spécifiques à l'interface client
  - `init.php` - Initialisation du système et inclusion des fichiers partagés
  - `/page_functions/` - Fonctions spécifiques à chaque page
    - `login.php` - Fonctions de traitement du login (à implémenter)
    - `/modules/` - Fonctions spécifiques aux modules
      - `companies.php` - Fonctions de gestion des entreprises
        * `getCompaniesList()` - Liste des entreprises
        * `getCompanyDetails()` - Détails d'une entreprise
        * `getCompanyEmployees()` - Employés d'une entreprise
        * `getCompanyContracts()` - Contrats d'une entreprise
        * `generateCompanyQuote()` - Génération de devis
        * `getCompanyInvoices()` - Factures d'une entreprise
        * `getCompanyPayments()` - Paiements d'une entreprise
        * `generateCompanyReports()` - Rapports d'analyse
        * `updateCompanySettings()` - Paramètres d'une entreprise
      - `employees.php` - Fonctions de gestion des salariés
        * `getEmployeesList()` - Liste des salariés
        * `getEmployeeDetails()` - Détails d'un salarié
        * `updateEmployeeProfile()` - Mise à jour de profil
        * `getEmployeeAvailableServices()` - Services disponibles
        * `getEmployeeReservations()` - Réservations
        * `getEmployeeAppointments()` - Rendez-vous médicaux
        * `getEmployeeActivityHistory()` - Historique d'activités
        * `getEmployeeCommunities()` - Communautés accessibles
        * `manageEmployeeDonations()` - Gestion des dons
        * `getEmployeeEvents()` - Évènements et défis
        * `updateEmployeeSettings()` - Préférences utilisateur
      - `providers.php` - Fonctions de gestion des prestataires
        * `getProvidersList()` - Liste des prestataires
        * `getProviderDetails()` - Détails d'un prestataire
        * `searchProviders()` - Recherche de prestataires
        * `getProviderCalendar()` - Calendrier de disponibilité
        * `getProviderRatings()` - Évaluations
        * `getProviderCategories()` - Catégories de prestataires
        * `getProviderContracts()` - Contrats
        * `getProviderServices()` - Services proposés
        * `getProviderInvoices()` - Factures
        * `updateProviderSettings()` - Paramètres