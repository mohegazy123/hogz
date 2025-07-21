<?php

namespace App\Config;

class Database {
    private $host;
    private $username;
    private $password;
    private $dbName;
    private $conn;
    private static $instance;

    private function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        $this->dbName = $_ENV['DB_NAME'] ?? 'erp_system';
        
        $this->connect();
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            $this->conn = new \mysqli($this->host, $this->username, $this->password, $this->dbName);
            
            if ($this->conn->connect_error) {
                throw new \Exception('Database connection failed: ' . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
        } catch (\Exception $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql) {
        return $this->conn->query($sql);
    }

    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function escapeString($value) {
        return $this->conn->real_escape_string($value);
    }

    public function getLastInsertId() {
        return $this->conn->insert_id;
    }

    public function getAffectedRows() {
        return $this->conn->affected_rows;
    }

    public function beginTransaction() {
        $this->conn->autocommit(false);
        return $this->conn->begin_transaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}