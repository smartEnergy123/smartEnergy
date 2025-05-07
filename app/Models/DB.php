<?php

class DB
{
    private $username;
    private $password;
    private $host;
    private $dbname;
    private $conn;

    public function __construct() {}

    public function connection()
    {
        try {
            $dsn = "mysql:dbhost={$this->host};dbname={$this->dbname};";
        } catch (PDOException $error) {
            echo "DB Connection Failed. ErrorType: " . $error->getMessage();
        };
    }
}
