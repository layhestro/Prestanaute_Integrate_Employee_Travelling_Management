<?php
class ConnectionToDatabase {
    private $host;
    private $username;
    private $password;
    private $dbname;
    private $connection;

    public function __construct() {
        $this->host = "";
        $this->username = "";
        $this->password = "";
        $this->dbname = "";
    }

    public function connect(): void {
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->dbname);

        if ($this->connection->connect_error) {
            throw new Exception("Failed to connect to database: " . $this->connection->connect_error);
        }

        // Set the character set to utf8mb4 for proper encoding
        $this->connection->set_charset("utf8mb4");
    }

    public function disconnect(): void {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    public function getConnection(): mysqli {
        return $this->connection;
    }
    
}
?>