<?php

namespace App\Models;

class Customer extends BaseModel {
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone',
        'company', 'address', 'city', 'state', 'zip_code',
        'country', 'status', 'notes'
    ];

    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getCustomers($search = null, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        $types = '';
        
        if ($search) {
            $searchTerm = "%" . $search . "%";
            $sql .= " WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR company LIKE ?";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
            $types = 'sssss';
        }
        
        $sql .= " ORDER BY id DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $limit;
            $types .= 'ii';
        }
        
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getOrdersCount($customerId) {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE customer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int) $row['count'];
    }

    public function getTotalSpent($customerId) {
        $sql = "SELECT SUM(total_amount) as total FROM orders WHERE customer_id = ? AND status = 'completed'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (float) $row['total'] ?? 0;
    }
}