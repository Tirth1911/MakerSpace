<?php
/**
 * Database Connection Config
 */

class Database {
    private $host = "127.0.0.1";
    private $port = "3307";
    private $db_name = "yuvalay_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // Render basic error message or return null
            error_log("Database connection error: " . $exception->getMessage());
            die("Database Connection Error. Please verify database is running.");
        }

        return $this->conn;
    }
}
