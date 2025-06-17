<?php
// php/database.php - Database Connection and Helper Functions

require_once 'config.php';

class Database {
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_DATABASE . ";charset=utf8mb4";
                self::$connection = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                if (APP_DEBUG === 'true') {
                    die("Database connection failed: " . $e->getMessage());
                } else {
                    die("Database connection failed. Please try again later.");
                }
            }
        }

        return self::$connection;
    }

    public static function query($sql, $params = []) {
        try {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (APP_DEBUG === 'true') {
                die("Query failed: " . $e->getMessage() . " SQL: " . $sql);
            } else {
                die("Database error. Please try again later.");
            }
        }
    }

    public static function fetch($sql, $params = []) {
        return self::query($sql, $params)->fetch();
    }

    public static function fetchAll($sql, $params = []) {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        self::query($sql, $data);
        return self::getConnection()->lastInsertId();
    }

    public static function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);

        return self::query($sql, $params)->rowCount();
    }

    public static function delete($table, $where, $whereParams = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return self::query($sql, $whereParams)->rowCount();
    }
}

// Database helper functions
function db_query($sql, $params = []) {
    return Database::query($sql, $params);
}

function db_fetch($sql, $params = []) {
    return Database::fetch($sql, $params);
}

function db_fetch_all($sql, $params = []) {
    return Database::fetchAll($sql, $params);
}

function db_insert($table, $data) {
    return Database::insert($table, $data);
}

function db_update($table, $data, $where, $whereParams = []) {
    return Database::update($table, $data, $where, $whereParams);
}

function db_delete($table, $where, $whereParams = []) {
    return Database::delete($table, $where, $whereParams);
}
?>
