<?php
require_once 'config.php';

/**
 * Retourne une instance PDO unique pour la connexion à la base de données MySQL.
 *
 * Cette fonction implémente le pattern singleton pour s'assurer qu'une seule connexion PDO soit utilisée
 * pendant l'exécution du script. En cas d'échec de la connexion, le script est arrêté avec un message d'erreur,
 * détaillé si DEBUG_MODE est activé et générique en production.
 *
 * @return PDO La connexion PDO à la base de données.
 */
function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("[ERREUR] Connexion a la base de donnees impossible: " . $e->getMessage());
            } else {
                die("[ERREUR] Connexion a la base de donnees impossible. Veuillez reessayer plus tard.");
            }
        }
    }
    return $pdo;
}

/**
 * Valide un nom de table en vérifiant qu'il ne contient que des lettres, des chiffres et des underscores.
 *
 * Lève une exception si le nom de table ne respecte pas le format attendu.
 *
 * @param string $table Nom de la table à valider.
 * @return string Le même nom de table si la validation réussit.
 * @throws Exception Si le nom de table contient des caractères non autorisés.
 */
function validateTableName($table) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        throw new Exception("Nom de table invalide");
    }
    return $table;
}

/**
 * Exécute une requête SQL préparée et retourne l'objet statement résultant.
 *
 * Initialise la connexion à la base de données via PDO, prépare la requête SQL et l'exécute avec les paramètres donnés.
 * En cas d'erreur, affiche un message détaillé si le mode debug est activé, sinon un message générique, puis termine le script.
 *
 * @param string $sql La requête SQL à exécuter.
 * @param array $params Les paramètres à lier dans la requête.
 * @return PDOStatement L'objet PDOStatement résultant de l'exécution de la requête.
 */
function executeQuery($sql, $params = []) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("[ERREUR] Impossible d'executer la requete: " . $e->getMessage());
        } else {
            die("[ERREUR] Une erreur est survenue lors du traitement de votre requete.");
        }
    }
}

/**
 * Compte le nombre de lignes dans une table de la base de données.
 *
 * Valide le nom de la table et exécute une requête SQL pour récupérer le nombre total d'enregistrements.
 * Une clause WHERE optionnelle peut être spécifiée pour filtrer le comptage.
 *
 * @param string $table Nom de la table à examiner.
 * @param string $where (Optionnel) Clause WHERE pour filtrer les lignes.
 *
 * @return mixed Nombre de lignes dans la table (habituellement un entier).
 *
 * @throws Exception Si le nom de la table ne respecte pas le format attendu.
 */
function countTableRows($table, $where = '') {
    $table = validateTableName($table);
    
    $sql = "SELECT COUNT(*) FROM $table";
    $params = [];
    if ($where) {
        $sql .= " WHERE $where";
    }
    
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn();
}

/**
 * Récupère l'ensemble des lignes d'une table avec des options de filtrage, de tri et de pagination.
 *
 * La fonction valide le nom de la table, construit dynamiquement une requête SQL intégrant les clauses
 * WHERE, ORDER BY, LIMIT et OFFSET selon les paramètres fournis, et renvoie le tableau des résultats.
 *
 * @param string $table Nom de la table à interroger.
 * @param string $where Expression SQL optionnelle pour filtrer les résultats.
 * @param string $orderBy Clause SQL optionnelle pour ordonner les résultats.
 * @param int $limit Nombre maximal de résultats à retourner (0 pour aucune limite).
 * @param int $offset Décalage à appliquer lors de la pagination (en vigueur si une limite est définie).
 *
 * @return array Tableau contenant l'ensemble des lignes récupérées.
 */
function fetchAll($table, $where = '', $orderBy = '', $limit = 0, $offset = 0) {
    $table = validateTableName($table);
    
    $sql = "SELECT * FROM $table";
    if ($where) {
        $sql .= " WHERE $where";
    }
    if ($orderBy) {
        $sql .= " ORDER BY $orderBy";
    }
    if ($limit) {
        $sql .= " LIMIT $limit";
        if ($offset) {
            $sql .= " OFFSET $offset";
        }
    }
    
    $stmt = executeQuery($sql);
    return $stmt->fetchAll();
}

/**
 * Récupère une seule ligne depuis une table selon des critères spécifiés.
 *
 * La fonction valide le nom de la table, construit une requête SELECT avec la clause WHERE et, 
 * si spécifié, un critère d'ordonnancement, avant de limiter le résultat à une seule ligne.
 * Elle retourne le premier enregistrement correspondant ou false si aucun résultat n'est trouvé.
 *
 * @param string $table Nom de la table ciblée (doit contenir uniquement des caractères alphanumériques et des underscores).
 * @param string $where Clause SQL pour filtrer les enregistrements.
 * @param string $orderBy (Optionnel) Critères d'ordonnancement appliqués à la requête.
 * @return mixed Le premier enregistrement correspondant ou false en l'absence de résultat.
 */
function fetchOne($table, $where, $orderBy = '') {
    $table = validateTableName($table);
    
    $sql = "SELECT * FROM $table WHERE $where";
    if ($orderBy) {
        $sql .= " ORDER BY $orderBy";
    }
    $sql .= " LIMIT 1";
    
    $stmt = executeQuery($sql);
    return $stmt->fetch();
}

/**
 * Insère une nouvelle ligne dans la table spécifiée.
 *
 * Valide le nom de la table et insère les données fournies sous forme de tableau associatif,
 * où les clés représentent les colonnes à remplir. La fonction retourne l'identifiant de la dernière
 * ligne insérée en cas de succès, ou false si l'insertion échoue.
 *
 * @param string $table Nom de la table dans laquelle insérer la ligne.
 * @param array  $data  Tableau associatif contenant les colonnes et les valeurs à insérer.
 *
 * @return string|false Identifiant de la dernière ligne insérée en cas de succès, false sinon.
 *
 * @throws Exception Si le nom de la table ne respecte pas le format autorisé.
 */
function insertRow($table, $data) {
    $table = validateTableName($table);
    
    $fields = array_keys($data);
    $placeholders = array_map(function ($field) {
        return ":$field";
    }, $fields);
    
    $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = executeQuery($sql, $data);
    return $stmt->rowCount() > 0 ? getDbConnection()->lastInsertId() : false;
}

/**
 * Met à jour des lignes dans une table de la base de données.
 *
 * Construit et exécute une requête UPDATE pour modifier les colonnes spécifiées dans le tableau associatif
 * $data sur les enregistrements répondant à la clause SQL $where. Le nom de la table est d'abord validé pour
 * garantir sa conformité et prévenir les injections SQL.
 *
 * @param string $table Nom de la table à mettre à jour.
 * @param array $data Tableau associatif des colonnes à mettre à jour et de leurs nouvelles valeurs.
 * @param string $where Clause SQL définissant les lignes à modifier.
 * @param array $whereParams (optionnel) Paramètres supplémentaires à associer à la clause WHERE.
 *
 * @return int Nombre de lignes affectées par la mise à jour.
 */
function updateRow($table, $data, $where, $whereParams = []) {
    $table = validateTableName($table);
    
    $fields = array_keys($data);
    $setClause = array_map(function ($field) {
        return "$field = :$field";
    }, $fields);
    
    $sql = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE $where";
    
    $params = array_merge($data, $whereParams);
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

/**
 * Supprime des lignes d'une table selon une condition SQL donnée.
 *
 * Valide le nom de la table, construit et exécute une requête DELETE en appliquant la clause WHERE et les paramètres fournis, puis retourne le nombre de lignes supprimées.
 *
 * @param string $table Nom de la table pour l'opération.
 * @param string $where Clause SQL (WHERE) définissant les conditions de suppression.
 * @param array $params Liste facultative des valeurs à lier à la requête.
 *
 * @return int Nombre de lignes affectées par la suppression.
 */
function deleteRow($table, $where, $params = []) {
    $table = validateTableName($table);
    
    $sql = "DELETE FROM $table WHERE $where";
    
    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

function beginTransaction() {
    getDbConnection()->beginTransaction();
}

function commitTransaction() {
    getDbConnection()->commit();
}

function rollbackTransaction() {
    getDbConnection()->rollBack();
} 