<?php
// php/database.php - Database Connection and Helper Functions
// This file is like the translator between our app and the MySQL database
// It handles all the technical stuff so other parts of the app can just ask for data easily

require_once 'config.php';

// This is our main Database class - think of it as the database manager
class Database {
    // We keep one connection to the database that everyone shares (like having one phone line for the whole office)
    private static $connection = null;

    // This function gets us connected to the database
    public static function getConnection() {
        // If we don't have a connection yet, let's make one
        if (self::$connection === null) {
            try {
                // Build the connection string - it's like dialing a phone number with area code
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_DATABASE . ";charset=utf8mb4";

                // Actually connect to the database with our username and password
                self::$connection = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,    // Tell us if something goes wrong
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Give us data as nice arrays
                    PDO::ATTR_EMULATE_PREPARES => false,            // Use real prepared statements (safer)
                ]);
            } catch (PDOException $e) {
                // Oops, connection failed - show different messages based on debug mode
                if (APP_DEBUG === 'true') {
                    die("Database connection failed: " . $e->getMessage()); // Show technical details
                } else {
                    die("Database connection failed. Please try again later."); // Show user-friendly message
                }
            }
        }

        return self::$connection;
    }

    // This function runs any SQL query - it's like asking the database a question
    public static function query($sql, $params = []) {
        try {
            $stmt = self::getConnection()->prepare($sql); // Prepare the question
            $stmt->execute($params); // Ask the question with the provided parameters
            return $stmt; // Return the answer
        } catch (PDOException $e) {
            // Something went wrong with our question
            if (APP_DEBUG === 'true') {
                die("Query failed: " . $e->getMessage() . " SQL: " . $sql); // Show what broke
            } else {
                die("Database error. Please try again later."); // Generic error message
            }
        }
    }

    // Get just one row of data (like asking "who is employee #123?")
    public static function fetch($sql, $params = []) {
        return self::query($sql, $params)->fetch();
    }

    // Get multiple rows of data (like asking "show me all employees in IT department")
    public static function fetchAll($sql, $params = []) {
        return self::query($sql, $params)->fetchAll();
    }

    // Add new data to a table (like hiring a new employee)
    public static function insert($table, $data) {
        // Build the INSERT SQL statement dynamically
        $columns = implode(',', array_keys($data));           // List of column names
        $placeholders = ':' . implode(', :', array_keys($data)); // Placeholders for values
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        self::query($sql, $data);
        return self::getConnection()->lastInsertId(); // Return the ID of the new record
    }

    // Update existing data (like changing someone's phone number)
    public static function update($table, $data, $where, $whereParams = []) {
        // Build the SET part of the UPDATE statement
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams); // Combine data and where parameters

        return self::query($sql, $params)->rowCount(); // Return how many rows were updated
    }

    // Delete data (like removing a former employee)
    public static function delete($table, $where, $whereParams = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return self::query($sql, $whereParams)->rowCount(); // Return how many rows were deleted
    }
}

// These are shortcut functions so we don't have to type "Database::" every time
// It's like having speed dial instead of dialing the full number

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
