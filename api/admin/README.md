# Business Care - API Admin pour l'Application Java de Reporting

Ce document décrit les points de terminaison (endpoints) de l'API fournis sous `/api/admin/` spécifiquement destinés à être utilisés par l'application Java autonome de reporting.

## Authentification

Toutes les requêtes vers les points de terminaison protégés listés ci-dessous nécessitent une authentification via un Jeton de Porteur (Bearer Token).

**Mécanisme d'Authentification:**

1.  L'application Java doit d'abord obtenir un jeton en appelant le point de terminaison de Connexion (Login).
2.  Pour les requêtes suivantes vers les points de terminaison protégés, l'application Java doit inclure le jeton obtenu dans l'en-tête `Authorization`:
    `Authorization: Bearer <votre_jeton>`

### 1. Connexion (Login)

*   **Méthode:** `POST`
*   **URL:** `/api/admin/auth`
*   **Authentification:** Aucune requise.
*   **Corps de la Requête (Request Body):**
    ```json
    {
      "email": "admin_user@example.com",
      "password": "votre_mot_de_passe"
    }
    ```
*   **Réponse de Succès (200 OK):**
    ```json
    {
      "error": false,
      "message": "Authentification réussie",
      "token": "chaine_jeton_bearer_generee",
      "user": {
        "id": 1,
        "nom": "Admin",
        "prenom": "Super",
        "email": "admin_user@example.com",
        "role_id": 1
        // ... autres champs utilisateur non sensibles
      }
    }
    ```
*   **Réponses d'Erreur:**
    *   `400 Bad Request`: Email ou mot de passe manquant.
    *   `401 Unauthorized`: Email ou mot de passe invalide.
    *   `500 Internal Server Error`: Problème côté serveur lors de la connexion.

### 2. Déconnexion (Logout) (Optionnel pour l'App Java)

*   **Méthode:** `DELETE`
*   **URL:** `/api/admin/auth`
*   **Authentification:** Jeton Bearer requis.
*   **Réponse de Succès (200 OK):**
    ```json
    {
      "error": false,
      "message": "Déconnexion réussie"
    }
    ```
*   **Réponses d'Erreur:**
    *   `400 Bad Request`: Échec de la déconnexion (ex: jeton déjà invalide).
    *   `401 Unauthorized`: Authentification requise ou jeton invalide.
    *   `500 Internal Server Error`: Problème côté serveur lors de la déconnexion.

---

## Points de Terminaison de Données (Data Endpoints)

Tous les points de terminaison ci-dessous nécessitent un Jeton Bearer valide dans l'en-tête `Authorization`. Ils supposent que l'utilisateur authentifié possède les permissions nécessaires (ex: Rôle Admin).

### Sociétés (`entreprises`)

*   **Endpoint:** `/api/admin/companies`
*   **Méthode:** `GET`
*   **Description:** Récupère une liste de toutes les sociétés clientes.
*   **Paramètres URL:** Aucun pour la liste. Utiliser `?id={company_id}` pour les détails.
*   **Réponse de Succès (200 OK - Liste):**
    ```json
    {
      "error": false,
      "data": [
        {
          "id": 1,
          "nom": "Client Corp A",
          "siret": "12345678901234",
          "ville": "Paris",
          "taille_entreprise": "51-200",
          "secteur_activite": "Tech"
          // ... autres champs pertinents de la table 'entreprises'
        },
        // ... plus de sociétés
      ]
    }
    ```
*   **Réponse de Succès (200 OK - Détails `?id=1`):**
    ```json
    {
        "error": false,
        "data": {
            "id": 1,
            "nom": "Client Corp A",
            "siret": "12345678901234",
            "adresse": "123 Rue Example",
            "code_postal": "75001",
            "ville": "Paris",
            "telephone": "0123456789",
            "email": "contact@clienta.com",
            // ... tous les champs de la table 'entreprises'
            // Potentiellement ajouter les IDs des contrats, devis, factures liés ici
            "contracts": [10, 15],
            "quotes": [5, 8],
            "invoices": [20, 25]
        }
    }
    ```
*   **Réponses d'Erreur:**
    *   `401 Unauthorized`: Jeton invalide/manquant.
    *   `403 Forbidden`: Permissions insuffisantes.
    *   `404 Not Found`: ID de société non trouvé (pour la requête de détails).
    *   `500 Internal Server Error`: Problème côté serveur.

### Contrats (`contrats`)

*   **Endpoint:** `/api/admin/contracts`
*   **Méthode:** `GET`
*   **Description:** Récupère une liste de tous les contrats. Peut être filtrée par société.
*   **Paramètres URL:**
    *   `?company_id={company_id}` (Optionnel): Filtrer les contrats par société.
    *   `?id={contract_id}`: Obtenir les détails d'un contrat spécifique.
*   **Réponse de Succès (200 OK - Liste):**
    ```json
    {
      "error": false,
      "data": [
        {
          "id": 10,
          "entreprise_id": 1,
          "service_id": 2, // ex: Basic Pack
          "date_debut": "2023-01-15",
          "date_fin": "2024-01-14",
          "nombre_salaries": 150,
          "statut": "actif"
          // ... autres champs pertinents de la table 'contrats'
        },
        // ... plus de contrats
      ]
    }
    ```
*   **Réponse de Succès (200 OK - Détails `?id=10`):**
    ```json
    {
        "error": false,
        "data": {
            "id": 10,
            "entreprise_id": 1,
            "service_id": 2,
            "date_debut": "2023-01-15",
            "date_fin": "2024-01-14",
            "nombre_salaries": 150,
            "statut": "actif",
            "conditions_particulieres": "Note sur les termes spécifiques...",
            // Inclure les détails du service associé ?
            "service_details": { "id": 2, "type": "Basic Pack", ... }
            // ... tous les champs de la table 'contrats'
        }
    }
    ```
*   **Réponses d'Erreur:**
    *   `401 Unauthorized`.
    *   `403 Forbidden`.
    *   `404 Not Found`: ID de contrat non trouvé.
    *   `500 Internal Server Error`.

### Services / Prestations (`services` / `prestations`)

*Note: Déterminer si ceux-ci sont séparés ou combinés. Supposant que `prestations` représente les services individuels offerts.*

*   **Endpoint:** `/api/admin/services` (Supposant que cela cible la table `prestations`)
*   **Méthode:** `GET`
*   **Description:** Récupère une liste de tous les services/prestations disponibles.
*   **Paramètres URL:** Aucun pour la liste. Utiliser `?id={prestation_id}` pour les détails.
*   **Réponse de Succès (200 OK - Liste):**
    ```json
    {
      "error": false,
      "data": [
        {
          "id": 1,
          "nom": "Atelier Gestion du Stress",
          "description": "Workshop interactif...",
          "prix": 500.00,
          "type": "atelier",
          "categorie": "Bien-être"
          // ... autres champs pertinents de la table 'prestations'
          // Potentiellement ajouter le compte d'utilisation (ex: associated_event_count)
        },
        // ... plus de services
      ]
    }
    ```
*   **Réponse de Succès (200 OK - Détails `?id=1`):**
    ```json
    {
        "error": false,
        "data": {
            "id": 1,
            "nom": "Atelier Gestion du Stress",
            "description": "Workshop interactif...",
            "prix": 500.00,
            "duree": 120, // minutes
            "type": "atelier",
            "categorie": "Bien-être",
            "niveau_difficulte": "debutant",
            "capacite_max": 20,
            // ... tous les champs de la table 'prestations'
            // Potentiellement ajouter la liste des IDs d'événements où utilisé
            "associated_events": [ 5, 12, 23]
        }
    }
    ```
*   **Réponses d'Erreur:**
    *   `401 Unauthorized`.
    *   `403 Forbidden`.
    *   `404 Not Found`: ID de service non trouvé.
    *   `500 Internal Server Error`.

### Événements (`evenements`)

*   **Endpoint:** `/api/admin/events` (Nécessite la création de `api/admin/events.php`)
*   **Méthode:** `GET`
*   **Description:** Récupère une liste de tous les événements.
*   **Paramètres URL:** Aucun pour la liste. Utiliser `?id={event_id}` pour les détails.
*   **Réponse de Succès (200 OK - Liste):**
    ```json
    {
      "error": false,
      "data": [
        {
          "id": 5,
          "titre": "Webinar Nutrition",
          "date_debut": "2024-06-15 14:00:00",
          "date_fin": "2024-06-15 15:00:00",
          "type": "webinar",
          "capacite_max": 100
          // ... autres champs pertinents de la table 'evenements'
          // Potentiellement ajouter le compte de réservations (ex: inscription_count)
        },
        // ... plus d'événements
      ]
    }
    ```
*   **Réponse de Succès (200 OK - Détails `?id=5`):**
    ```json
    {
        "error": false,
        "data": {
            "id": 5,
            "titre": "Webinar Nutrition",
            "description": "Présentation sur l'alimentation saine...",
            "date_debut": "2024-06-15 14:00:00",
            "date_fin": "2024-06-15 15:00:00",
            "lieu": "Online",
            "type": "webinar",
            "capacite_max": 100,
            // ... tous les champs de la table 'evenements'
            // Potentiellement ajouter la liste des IDs de services associés ou détails d'inscription
            "associated_services": [2, 7],
            "inscriptions": [ {"personne_id": 101, "statut": "inscrit"}, ... ]
        }
    }
    ```
*   **Réponses d'Erreur:**
    *   `401 Unauthorized`.
    *   `403 Forbidden`.
    *   `404 Not Found`: ID d'événement non trouvé.
    *   `500 Internal Server Error`.

### Factures (`factures`)

*   **Endpoint:** `/api/admin/invoices` (Nécessite la création de `api/admin/invoices.php`)
*   **Méthode:** `GET`
*   **Description:** Récupère une liste de toutes les factures. Peut être filtrée par société.
*   **Paramètres URL:**
    *   `?company_id={company_id}` (Optionnel): Filtrer les factures par société.
    *   `?id={invoice_id}`: Obtenir les détails d'une facture spécifique.
*   **Réponse de Succès (200 OK - Liste):**
    ```json
    {
      "error": false,
      "data": [
        {
          "id": 20,
          "entreprise_id": 1,
          "numero_facture": "F2024-0020",
          "date_emission": "2024-02-01",
          "date_echeance": "2024-03-01",
          "montant_total": 1200.00,
          "statut": "payee"
          // ... autres champs pertinents de la table 'factures'
        },
        // ... plus de factures
      ]
    }
    ```
*   **Réponse de Succès (200 OK - Détails `?id=20`):**
    ```json
    {
        "error": false,
        "data": {
            "id": 20,
            "entreprise_id": 1,
            "devis_id": 5,
            "numero_facture": "F2024-0020",
            "date_emission": "2024-02-01",
            "date_echeance": "2024-03-01",
            "montant_total": 1200.00,
            "montant_ht": 1000.00,
            "tva": 200.00,
            "statut": "payee",
            "mode_paiement": "virement",
            "date_paiement": "2024-02-25 10:00:00",
            // ... tous les champs de la table 'factures'
            // Potentiellement ajouter les lignes de facture si nécessaire
            "line_items": [ ... ]
        }
    }
    ```
*   **Réponses d'Erreur:**
    *   `401 Unauthorized`.
    *   `403 Forbidden`.
    *   `404 Not Found`: ID de facture non trouvé.
    *   `500 Internal Server Error`.

### Devis (`devis`)

*   **Endpoint:** `/api/admin/quotes` (Nécessite la création de `api/admin/quotes.php`)
*   **Méthode:** `GET`
*   **Description:** Récupère une liste de tous les devis. Peut être filtrée par société.
*   **Paramètres URL:**
    *   `?company_id={company_id}` (Optionnel): Filtrer les devis par société.
    *   `?id={quote_id}`: Obtenir les détails d'un devis spécifique.
*   **Réponse de Succès (200 OK - Liste):**
    ```json
    {
      "error": false,
      "data": [
        {
          "id": 5,
          "entreprise_id": 1,
          "date_creation": "2023-12-01",
          "date_validite": "2023-12-31",
          "montant_total": 1200.00,
          "statut": "accepté"
          // ... autres champs pertinents de la table 'devis'
        },
        // ... plus de devis
      ]
    }
    ```
*   **Réponse de Succès (200 OK - Détails `?id=5`):**
    ```json
    {
        "error": false,
        "data": {
            "id": 5,
            "entreprise_id": 1,
            "service_id": 2,
            "nombre_salaries_estimes": 150,
            "date_creation": "2023-12-01",
            "date_validite": "2023-12-31",
            "montant_total": 1200.00,
            "montant_ht": 1000.00,
            "tva": 200.00,
            "statut": "accepté",
            // ... tous les champs de la table 'devis'
            // Potentiellement ajouter les lignes de prestation (devis_prestations)
            "prestations": [
                {"prestation_id": 1, "quantite": 1, "prix_unitaire_devis": 500.00, "description_specifique": "Session sur site"},
                {"prestation_id": 7, "quantite": 10, "prix_unitaire_devis": 70.00, "description_specifique": "Accès plateforme"}
            ]
        }
    }
    ```
*   **Réponses d'Erreur:**
    *   `401 Unauthorized`.
    *   `403 Forbidden`.
    *   `404 Not Found`: ID de devis non trouvé.
    *   `500 Internal Server Error`.

---

**Note:** Les champs exacts retournés dans l'objet `data` pour chaque point de terminaison devront être finalisés lors de l'implémentation en fonction des besoins spécifiques de l'application Java de reporting et des données disponibles dans les tables de la base de données. Assurez-vous que les données liées (comme les détails d'un service dans un contrat, ou les lignes d'items dans une facture/devis) sont incluses lorsque nécessaire pour éviter des appels API excessifs depuis l'application Java.
