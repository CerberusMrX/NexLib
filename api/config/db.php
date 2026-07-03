<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: "localhost";
        $this->db_name = getenv('DB_NAME') ?: "library_db";
        $this->username = getenv('DB_USER') ?: "root";
        $this->password = getenv('DB_PASS') ?: "";
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Auto-migration for new columns ensures smooth updates without CLI access
            try {
                $this->conn->exec("ALTER TABLE books ADD COLUMN image_url VARCHAR(500) DEFAULT NULL");
            } catch(PDOException $e) {}
        } catch(PDOException $exception) {
            if (class_exists('Response')) {
                Response::error("Connection error: " . $exception->getMessage(), 500);
            } else {
                header('Content-Type: application/json');
                echo json_encode(["status" => "error", "message" => "Connection error: " . $exception->getMessage()]);
                exit();
            }
        }
        return $this->conn;
    }
}
?>
