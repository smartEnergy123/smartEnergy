<?php

namespace App\Models;

use PDO;
use PDOException;
use Dotenv\Dotenv;

// Ensure Composer's autoloader is included. This line is crucial for Dotenv to be found.
// This path assumes DB.php is in app/Models/ and vendor/ is in the project root.
require_once __DIR__ . '/../../vendor/autoload.php';

class DB
{
    private $username;
    private $password;
    private $host;
    private $dbname;
    private $conn; // This will hold the PDO connection object

    public function closeConnection()
    {
        $this->conn = null;
    }

    public function __construct()
    {
        // Load environment variables using Dotenv
        // The path to .env file is two directories up from app/Models/DB.php
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $this->dbname = $_ENV['DB_NAME'];
        $this->password = $_ENV['DB_PASSWORD'];
        $this->username = $_ENV['DB_USERNAME'];
        $this->host = $_ENV['DB_HOST'];

        // Attempt to establish the database connection immediately
        try {
            // DSN (Data Source Name) string for MySQL
            // Ensure port is correct, 3306 is standard for MySQL
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};port=3306;charset=utf8mb4";

            // PDO options for error handling and fetching mode
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Default fetch mode to associative arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation for better security and performance
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Log the detailed error message to PHP's error log
            error_log("DB Connection Failed: " . $e->getMessage());

            // Instead of echoing, which can break JSON responses,
            // let the exception propagate or handle it in a way
            // that doesn't interfere with API output.
            // Since ApplianceController's constructor checks $this->db->connection(),
            // it will catch this failure and send a proper JSON error.
            $this->conn = null; // Ensure connection is null on failure
        }
    }

    /**
     * Returns the PDO connection object.
     * This method is used by the controller to check if the connection was successful.
     *
     * @return PDO|null The PDO connection object if successful, null otherwise.
     */
    public function connection()
    {
        return $this->conn;
    }

    /**
     * Executes a SQL query (for INSERT, UPDATE, DELETE statements).
     *
     * @param string $query The SQL query string.
     * @param array $params An associative array of parameters for the prepared statement.
     * @return bool True on success, false on failure.
     * @throws PDOException If the query preparation or execution fails.
     */
    public function execute(string $query, $params = [])
    {
        if (!$this->conn) {
            throw new PDOException("Database connection not established.");
        }
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Fetches a single row of data from the database.
     *
     * @param string $query The SQL query string.
     * @param array $params An associative array of parameters for the prepared statement.
     * @return array|null An associative array of the fetched row, or null if no row is found.
     * @throws PDOException If the query preparation or execution fails.
     */
    public function fetchSingleData(string $query, $params = [])
    {
        if (!$this->conn) {
            throw new PDOException("Database connection not established.");
        }
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null; // Return null if no row is found
    }

    /**
     * Fetches all rows of data from the database.
     *
     * @param string $query The SQL query string.
     * @param array $params An associative array of parameters for the prepared statement.
     * @return array An array of associative arrays of the fetched rows, or an empty array if no rows are found.
     * @throws PDOException If the query preparation or execution fails.
     */
    public function fetchAllData(string $query, $params = [])
    {
        if (!$this->conn) {
            throw new PDOException("Database connection not established.");
        }
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result ?: []; // Return an empty array if no rows are found
    }

    /**
     * Executes an INSERT query and returns the ID of the last inserted row.
     * This is particularly useful for tables with auto-incrementing primary keys.
     *
     * @param string $query The SQL INSERT query string.
     * @param array $params An associative array of parameters for the prepared statement.
     * @return int|false The ID of the last inserted row on success, or false on failure.
     * @throws PDOException If the query preparation or execution fails, or connection is not established.
     */
    public function insertAndGetId(string $query, $params = [])
    {
        if (!$this->conn) {
            throw new PDOException("Database connection not established.");
        }
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute($params)) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Starts a new database transaction.
     *
     * @return bool True on success.
     * @throws PDOException If a transaction cannot be started or connection is not established.
     */
    public function beginTransaction(): bool
    {
        if (!$this->conn) {
            throw new PDOException("Database connection not established.");
        }
        return $this->conn->beginTransaction();
    }

    /**
     * Commits the current database transaction.
     *
     * @return bool True on success.
     * @throws PDOException If the transaction cannot be committed or connection is not established.
     */
    public function commit(): bool
    {
        if (!$this->conn) {
            throw new PDOException("Database connection not established.");
        }
        return $this->conn->commit();
    }

    /**
     * Rolls back the current database transaction.
     *
     * @return bool True on success.
     * @throws PDOException If the transaction cannot be rolled back or connection is not established.
     */
    public function rollBack(): bool
    {
        if (!$this->conn) {
            throw new PDOException("Database connection not established.");
        }
        return $this->conn->rollBack();
    }
}
