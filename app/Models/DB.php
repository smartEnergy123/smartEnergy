<?php

namespace APP\Models;

use App\Services\MessageService;
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
        if(!getenv('DB_HOST')){
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
        }
        
    $this->dbname = getenv('DB_NAME');
    $this->password = getenv('DB_PASSWORD');
    $this->username = getenv('DB_USERNAME');
    $this->host = getenv('DB_HOST');

        echo '<br>dbname = ' . $this->dbname;
        echo '<br>password = ' . $this->password;
        echo '<br>username = ' . $this->username;
        echo '<br>HOST = ' . $this->host;

    }

    public function connection()
    {
        try {
            $dsn = "mysql:dbhost={$this->host};dbname={$this->dbname};port=3306;charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $error) {
            echo "DB Connection Failed. ErrorType: " . $error->getMessage();
        };

        return $this->conn;
    }


    // retrieve and update
    public function execute(string $query, $params = [])
    {
        $sql = $this->connection()->prepare($query);
        return $sql->execute($params);
    }

    // gets a single user data
    public function fetchSingleData(string $query, $params = [])
    {

        $sql = $this->connection()->prepare($query);
        $sql->execute($params);
        return $sql->fetch(PDO::FETCH_ASSOC);
    }

    // gets all the users data
    public function fetchAllData(string $query, $params = [])
    {
        $sql = $this->connection()->prepare($query);
        $sql->execute($params);
        return $sql->fetch(PDO::FETCH_ASSOC);
    }
}
