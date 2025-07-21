<?php

namespace App\Models;

class Product extends BaseModel {
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name', 'sku', 'description', 'category_id', 'supplier_id',
        'cost_price', 'selling_price', 'quantity', 'reorder_level',
        'tax_rate', 'weight', 'dimensions', 'status'
    ];

    public function getProducts($filters = [], $limit = null, $offset = 0) {
        $whereClause = [];
        $params = [];
        $types = '';
        
        if (!empty($filters['category_id'])) {
            $whereClause[] = "category_id = ?";
            $params[] = $filters['category_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['supplier_id'])) {
            $whereClause[] = "supplier_id = ?";
            $params[] = $filters['supplier_id'];
            $types .= 'i';
        }
        
        if (isset($filters['status'])) {
            $whereClause[] = "status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $search = "%" . $filters['search'] . "%";
            $whereClause[] = "(name LIKE ? OR sku LIKE ? OR description LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= 'sss';
        }
        
        $sql = "SELECT p.*, c.name as category_name, s.name as supplier_name 
                FROM {$this->table} p
                LEFT JOIN product_categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id";
        
        if (!empty($whereClause)) {
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        $sql .= " ORDER BY p.id DESC";
        
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

    public function updateStock($id, $quantity, $type = 'increment') {
        $product = $this->findById($id);
        
        if (!$product) {
            return false;
        }
        
        $newQuantity = ($type === 'increment') 
            ? $product['quantity'] + $quantity 
            : $product['quantity'] - $quantity;
        
        // Prevent negative stock
        if ($newQuantity < 0) {
            $newQuantity = 0;
        }
        
        return $this->update($id, ['quantity' => $newQuantity]);
    }

    public function getLowStockProducts($threshold = null) {
        $sql = "SELECT * FROM {$this->table} WHERE quantity <= reorder_level";
        
        if ($threshold !== null) {
            $sql = "SELECT * FROM {$this->table} WHERE quantity <= ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $threshold);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}