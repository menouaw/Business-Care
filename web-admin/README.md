# Business Care - Panneau d'Administration

Le panneau d'administration est la partie backend permettant de gerer toutes les fonctionnalites de Business Care.

## Structure du projet

```
web-admin/                  # Dossier principal de l'administration
├── includes/               # Fichiers d'inclusion PHP
│   ├── config.php          # Configuration de l'application
│   ├── db.php              # Fonctions de base de donnees
│   ├── auth.php            # Fonctions d'authentification
│   └── functions.php       # Fonctions utilitaires
├── modules/                # Modules fonctionnels
│   ├── users/              # Gestion des utilisateurs
│   ├── companies/          # Gestion des entreprises
│   ├── contracts/          # Gestion des contrats
│   ├── services/           # Gestion des services
│   ├── financial/          # Gestion financière
│   └── reports/            # Rapports et statistiques
├── templates/              # Modèles d'interface
│   ├── header.php          # En-tête commune
│   ├── footer.php          # Pied de page commun
│   └── sidebar.php         # Barre laterale de navigation
├── index.php               # Page d'accueil/tableau de bord
├── login.php               # Page de connexion
└── logout.php              # Script de deconnexion
```

## API REST

L'API REST se trouve dans le dossier `/api` à la racine du projet et comprend les points d'entree suivants :

```
/api/                      # Point d'entree principal
├── admin/                 # Endpoints pour l'administration
│   ├── users.php          # Gestion des utilisateurs
│   ├── companies.php      # Gestion des entreprises
│   ├── contracts.php      # Gestion des contrats
│   ├── services.php       # Gestion des services
│   ├── auth.php           # Authentification
```

### Points d'entree API disponibles

- `GET /api/users` - Liste des utilisateurs (avec pagination)
- `GET /api/users/{id}` - Detail d'un utilisateur
- `POST /api/users` - Creation d'un utilisateur
- `PUT /api/users/{id}` - Mise à jour d'un utilisateur
- `DELETE /api/users/{id}` - Suppression d'un utilisateur

- `POST /api/auth` - Authentification
- `PUT /api/auth` - Modification de mot de passe
- `DELETE /api/auth` - Deconnexion

## Configuration

1. Creer une base de donnees MySQL
2. Importer le script SQL depuis `/database/setup.sql`
3. Modifier les paramètres de connexion dans `/web-admin/includes/config.php`

## Fonctionnalites principales

- Tableau de bord avec statistiques
- Gestion des utilisateurs
- Gestion des entreprises
- Gestion des contrats
- Gestion des services
- Gestion financière
- Rapports et statistiques

## Developpement

### Prerequis

- PHP 7.4 ou superieur
- MySQL 5.7 ou superieur
- Serveur web Apache avec mod_rewrite active 