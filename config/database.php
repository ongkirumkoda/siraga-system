<?php
// config/database.php
class Database {
    private $host = '127.0.0.1';  // atau 'localhost'
    private $user = 'root';
    private $pass = '';
    private $dbname = 'siraga_db1';  // SESUAIKAN dengan nama database di phpMyAdmin
    private $conn;
    private static $instance = null;
    
    // Singleton pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        try {
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // === CRUD OPERATIONS ===
    
    // Query with parameters (prevent SQL injection)
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            $values = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $values[] = $param;
            }
            
            $stmt->bind_param($types, ...$values);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    // Get single row
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Get all rows
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        $rows = [];
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    // Insert data and return inserted ID
    public function insert($table, $data) {
        $keys = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?, ', count($values) - 1) . '?';
        
        $sql = "INSERT INTO $table (" . implode(', ', $keys) . ") VALUES ($placeholders)";
        $stmt = $this->query($sql, $values);
        
        return $this->conn->insert_id;
    }
    
    // Update data
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $setClause[] = "$key = ?";
            $values[] = $value;
        }
        
        $values = array_merge($values, $whereParams);
        $sql = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE $where";
        
        $stmt = $this->query($sql, $values);
        return $stmt->affected_rows;
    }
    
    // Delete data
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->affected_rows;
    }
    
    // Count rows
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as total FROM $table";
        if ($where) {
            $sql .= " WHERE $where";
        }
        
        $result = $this->fetchOne($sql, $params);
        return $result['total'];
    }
    
    // Check if record exists
    public function exists($table, $where, $params = []) {
        $sql = "SELECT 1 FROM $table WHERE $where LIMIT 1";
        $result = $this->fetchOne($sql, $params);
        return !empty($result);
    }
    
    // Get last insert ID
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    // Begin transaction
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }
    
    // Commit transaction
    public function commit() {
        $this->conn->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        $this->conn->rollback();
    }
    
    // Escape string
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    // Close connection (optional)
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Untuk penggunaan cepat:
function db() {
    return Database::getInstance();
}
?>