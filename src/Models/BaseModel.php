<?php

namespace App\Models;

use App\Config\Database;

abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $columns = [];
    protected $fillable = [];
    protected $timestamps = true;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        $id = (int) $id;
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getAll($limit = null, $offset = 0, $orderBy = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$offset}, {$limit}";
        }
        
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function create(array $data) {
        $filteredData = $this->filterData($data);
        
        if ($this->timestamps) {
            $filteredData['created_at'] = date('Y-m-d H:i:s');
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $columns = implode(', ', array_keys($filteredData));
        $placeholders = implode(', ', array_fill(0, count($filteredData), '?'));
        $values = array_values($filteredData);
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        
        $types = '';
        foreach ($values as $val) {
            if (is_int($val)) $types .= 'i';
            elseif (is_float($val)) $types .= 'd';
            else $types .= 's';
        }
        
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        
        return $this->db->getLastInsertId();
    }

    public function update($id, array $data) {
        $filteredData = $this->filterData($data);
        
        if ($this->timestamps) {
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $setClause = [];
        foreach (array_keys($filteredData) as $column) {
            $setClause[] = "{$column} = ?";
        }
        
        $setClause = implode(', ', $setClause);
        $values = array_values($filteredData);
        
        // Add ID to values
        $values[] = $id;
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        
        $types = '';
        foreach ($values as $val) {
            if (is_int($val)) $types .= 'i';
            elseif (is_float($val)) $types .= 'd';
            else $types .= 's';
        }
        
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        
        return $success && $this->db->getAffectedRows() > 0;
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        return $this->db->getAffectedRows() > 0;
    }

    public function count($whereClause = '') {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if ($whereClause) {
            $sql .= " WHERE {$whereClause}";
        }
        
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        
        return (int) $row['count'];
    }

    public function query($sql, $params = []) {
        if (empty($params)) {
            return $this->db->query($sql);
        }
        
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }
            
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }

    protected function filterData(array $data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
}