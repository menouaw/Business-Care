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
        error_log(message: "PDOException in executeQuery: " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
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

function fetchAll($table, $where = '', $orderBy = '', $limit = 0, $offset = 0, $params = [])
{
    $table = validateTableName($table);

    $sql = "SELECT * FROM $table";
    if ($where) {
        $sql .= " WHERE $where";
    }
    if ($orderBy) {
        if (is_string($orderBy) && !empty(trim($orderBy))) {
            if (preg_match('/^[a-zA-Z0-9_,\s\.\(\)]+(?:\s+(?:ASC|DESC))?(?:,\s*[a-zA-Z0-9_,\s\.\(\)]+(?:\s+(?:ASC|DESC))?)*$/', $orderBy)) {
                $sql .= " ORDER BY " . $orderBy;
            } else {
                error_log("[WARNING] Invalid characters detected in orderBy clause in fetchAll for table '$table': " . $orderBy);
            }
        } else {
            error_log("[WARNING] Invalid type or empty orderBy parameter passed to fetchAll for table '$table'. Expected string, got: " . gettype($orderBy));
        }
    }
    if ($limit) {
        $sql .= " LIMIT $limit";
        if ($offset) {
            $sql .= " OFFSET $offset";
        }
    }

    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

function fetchOne($table, $where, $params = [], $orderBy = '')
{
    $table = validateTableName($table);

    $sql = "SELECT * FROM $table WHERE $where";
    if ($orderBy) {
        if (is_string($orderBy) && !empty(trim($orderBy))) {
            if (preg_match('/^[a-zA-Z0-9_,\s\.\(\)]+(?:\s+(?:ASC|DESC))?(?:,\s*[a-zA-Z0-9_,\s\.\(\)]+(?:\s+(?:ASC|DESC))?)*$/', $orderBy)) {
                $sql .= " ORDER BY " . $orderBy;
            } else {
                error_log("[WARNING] Invalid characters detected in orderBy clause in fetchOne for table '$table': " . $orderBy);
            }
        } else {
            error_log("[WARNING] Invalid type or empty orderBy parameter passed to fetchOne for table '$table'. Expected string, got: " . gettype($orderBy));
        }
    }
    $sql .= " LIMIT 1";

    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("[DEBUG] fetchOne called for table: $table | WHERE: $where | SQL: $sql");
    }

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

/**
 * Démarre une transaction SQL
 * 
 * @return void
 */
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
