<?php
require_once 'config.php';

/**
 * Retourne une instance PDO pour se connecter à la base de données.
 *
 * Établit une connexion à la base de données en utilisant les constantes de configuration (DB_HOST, DB_NAME, DB_CHARSET, DB_USER, DB_PASS)
 * et la met en cache afin d'éviter des connexions répétées. En cas d'échec, le script s'interrompt avec un message d'erreur standard.
 *
 * @return PDO La connexion PDO active.
 */
function getDbConnection()
{
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
            die("[FAILURE] Connexion a la base de donnees impossible. Veuillez reessayer plus tard.");
        }
    }
    return $pdo;
}

/**
 * Valide le nom d'une table pour éviter les injections SQL
 * 
 * @param string $table Nom de table à valider
 * @return string Nom de table validé
 * @throws Exception Si le nom de table est invalide
 */
function validateTableName($table)
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        throw new Exception("[FAILURE] Nom de table invalide");
    }
    return $table;
}

/**
 * Exécute une requête SQL préparée via PDO.
 *
 * Cette fonction prépare la requête SQL fournie et l'exécute avec les paramètres donnés. En cas d'erreur lors de l'exécution, le script est interrompu avec un message d'erreur standardisé.
 *
 * @param string $sql Requête SQL à préparer et exécuter.
 * @param array $params Valeurs à lier aux paramètres de la requête.
 * @return PDOStatement Instance de PDOStatement représentant le résultat de la requête.
 */
function executeQuery($sql, $params = [])
{
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        die("[FAILURE] Impossible d'executer la requete: " . $e->getMessage());
    }
}

/**
 * Compte le nombre d'enregistrements d'une table, avec une clause WHERE optionnelle.
 *
 * Le nom de la table est d'abord validé pour garantir qu'il ne contient que des caractères autorisés.
 * La requête SQL construite compte les enregistrements correspondant à la clause conditionnelle fournie,
 * ou compte tous les enregistrements si aucune clause n'est spécifiée.
 *
 * @param string $table Nom de la table (doit contenir uniquement des caractères alphanumériques et underscores).
 * @param string $where Clause SQL optionnelle pour filtrer les enregistrements.
 * @return int Nombre d'enregistrements répondant aux critères.
 */
function countTableRows($table, $where = '')
{
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
 * Récupère l'ensemble des enregistrements d'une table en appliquant des filtres optionnels.
 *
 * Cette fonction construit et exécute une requête SELECT sur la table spécifiée. Elle
 * permet d'appliquer une clause WHERE pour filtrer les enregistrements, une clause ORDER BY
 * pour trier les résultats, ainsi qu'une pagination via les paramètres limit et offset.
 * Noter qu'une valeur de 0 pour le paramètre limit désactive la limitation du nombre de lignes retournées.
 *
 * @param string $table Nom de la table cible.
 * @param string $where (Optionnel) Clause WHERE pour filtrer les enregistrements.
 * @param string $orderBy (Optionnel) Clause ORDER BY pour trier les résultats.
 * @param int $limit (Optionnel) Nombre maximum de lignes à récupérer. Une valeur de 0 désactive la limite.
 * @param int $offset (Optionnel) Position de départ pour la récupération des enregistrements.
 * @return array Tableau contenant l'ensemble des enregistrements récupérés.
 */
function fetchAll($table, $where = '', $orderBy = '', $limit = 0, $offset = 0)
{
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


function fetchOne($table, $where, $orderBy = '', $params = []) {
    $table = validateTableName($table);

    $sql = "SELECT * FROM $table WHERE $where";
    if ($orderBy) {
        $sql .= " ORDER BY $orderBy";
    }
    $sql .= " LIMIT 1";
    
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}


function insertRow($table, $data)
{
    $table = validateTableName($table);

    $fields = array_keys($data);
    $placeholders = array_map(function ($field) {
        return ":$field";
    }, $fields);

    $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";

    $stmt = executeQuery($sql, $data);
    return $stmt->rowCount() > 0 ? getDbConnection()->lastInsertId() : false;
}


function updateRow($table, $data, $where, $whereParams = [])
{
    $table = validateTableName($table);
    
    if (empty($data)) {
        throw new Exception("Aucune donnée fournie pour la mise à jour.");
    }

    $fields = array_keys($data);
    $setClause = array_map(function ($field) {
        return "`$field` = :set_$field"; 
    }, $fields);
    
    $sql = "UPDATE `$table` SET " . implode(', ', $setClause) . " WHERE $where";
    
    $setParams = [];
    foreach ($data as $key => $value) {
        $setParams[":set_$key"] = $value;
    }

    $finalWhereParams = [];
    foreach ($whereParams as $key => $value) {
         $finalWhereParams[(strpos($key, ':') === 0 ? $key : ':' . $key)] = $value;
    }

    $params = array_merge($setParams, $finalWhereParams);

    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Erreur updateRow: " . $e->getMessage() . " SQL: " . $sql . " Params: " . json_encode($params));
        throw $e;
    }
}


function deleteRow($table, $where, $params = [])
{
    $table = validateTableName($table);

    $sql = "DELETE FROM $table WHERE $where";

    $stmt = executeQuery($sql, $params);
    return $stmt->rowCount();
}

function beginTransaction()
{
    getDbConnection()->beginTransaction();
}

function commitTransaction()
{
    getDbConnection()->commit();
}

function rollbackTransaction()
{
    getDbConnection()->rollBack();
}
