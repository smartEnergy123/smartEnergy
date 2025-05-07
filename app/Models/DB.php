<?php

use Dotenv\Dotenv;
use PDO;
use PDOException;


require_once __DIR__ . '/../../vendor/autoload.php';

class DB
{
    private $username;
    private $password;
    private $host;
    private $dbname;
    private $conn;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
        $this->dbname = getenv('DB_NAME');
        $this->password = getenv('DB_PASSWORD');
        $this->username = getenv('DB_USERNAME');
        $this->host = getenv('DB_HOST');
    }

    public function connection()
    {
        try {
            $dsn = "mysql:dbhost={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $error) {
            echo "DB Connection Failed. ErrorType: " . $error->getMessage();
        };

        return $this->conn;
    }
}
