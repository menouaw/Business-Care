<?php
require_once 'config.php';


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
            die("[FAILURE] Connexion a la base de donnees impossible: " . $e->getMessage());
        }
    }
    return $pdo;
}


function validateTableName($table)
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        throw new Exception("[FAILURE] Nom de table invalide");
    }
    return $table;
}


function executeQuery($sql, $params = [])
{
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare($sql);
        if (!is_array($params)) {
            error_log("Warning: executeQuery called with non-array params. SQL: " . $sql);
            $params = [];
        }
        $stmt->execute($params);

        return $stmt;
    } catch (PDOException $e) {
        error_log("PDOException in executeQuery: " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
        throw $e;
    }
}

function countTableRows($table, $where = '', $params = [])
{
    $table = validateTableName($table);

    $sql = "SELECT COUNT(*) FROM $table";
    if ($where) {
        $sql .= " WHERE $where";
    }

    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn();
}

/**
 * Construit la clause ORDER BY pour une requête SQL, en validant la chaîne.
 *
 * @param mixed $orderBy La clause ORDER BY potentielle.
 * @param string $table Le nom de la table (pour logging).
 * @return string La clause SQL ORDER BY formatée ou une chaîne vide.
 */
function _buildOrderByClause($orderBy, string $table): string
{
    if (is_string($orderBy) && !empty(trim($orderBy))) {
        
        if (preg_match('/^[a-zA-Z0-9_,\s\.\(\)]+(?:\s+(?:ASC|DESC))?(?:,\s*[a-zA-Z0-9_,\s\.\(\)]+(?:\s+(?:ASC|DESC))?)*$/', $orderBy)) {
            return " ORDER BY " . $orderBy;
        } else {
            error_log("[WARNING] Invalid characters detected in orderBy clause in _buildOrderByClause for table '$table': " . $orderBy);
        }
    } elseif (!empty($orderBy)) {
        error_log("[WARNING] Invalid type or empty orderBy parameter passed to _buildOrderByClause for table '$table'. Expected string, got: " . gettype($orderBy));
    }
    return ''; 
}

/**
 * Construit la clause LIMIT/OFFSET pour une requête SQL, en validant les nombres.
 *
 * @param mixed $limit La limite potentielle.
 * @param mixed $offset Le décalage potentiel.
 * @return string La clause SQL LIMIT/OFFSET formatée ou une chaîne vide.
 */
function _buildLimitOffsetClause($limit, $offset): string
{
    $clause = '';
    if (is_numeric($limit) && (int)$limit > 0) {
        $clause .= " LIMIT " . (int)$limit;
        
        if (is_numeric($offset) && (int)$offset >= 0) {
            $clause .= " OFFSET " . (int)$offset;
        }
    } elseif (!empty($limit)) {
        error_log("[WARNING] Invalid limit parameter passed to _buildLimitOffsetClause. Expected positive number, got: " . print_r($limit, true));
    }
    return $clause;
}

function fetchAll($table, $where = '', $orderBy = '', $limit = 0, $offset = 0, $params = [])
{
    $table = validateTableName($table);

    $sql = "SELECT * FROM $table";
    if ($where) {
        $sql .= " WHERE $where";
    }

    
    $sql .= _buildOrderByClause($orderBy, $table);
    $sql .= _buildLimitOffsetClause($limit, $offset);

    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

function fetchOne($table, $where, $params = [], $orderBy = '')
{
    $table = validateTableName($table);

    $sql = "SELECT * FROM $table WHERE $where";

    
    $sql .= _buildOrderByClause($orderBy, $table);

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

    try {
        $stmt = executeQuery($sql, $data);

        return true;
    } catch (PDOException $e) {

        if (strpos((string)$e->getCode(), '01') === 0) {
            error_log("PDO Warning caught within insertRow (considered success): " . $e->getMessage() . " | SQL: $sql | Params: " . json_encode($data));

            return true;
        } else {

            error_log("PDO Error caught within insertRow: " . $e->getMessage() . " | SQL: $sql | Params: " . json_encode($data));
            return false;
        }
    } catch (Exception $e) {
        error_log("General Exception caught within insertRow: " . $e->getMessage() . " | SQL: $sql | Params: " . json_encode($data));
        return false;
    }
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
