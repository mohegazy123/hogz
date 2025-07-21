<?php

namespace App\Models;

class OrderItem extends BaseModel {
    protected $table = 'order_items';
    protected $primaryKey = 'id';
    protected $fillable = [
        'order_id', 'product_id', 'quantity', 'unit_price',
        'total_price', 'tax_amount', 'discount_amount'
    ];
    protected $timestamps = false;

    public function getItemsByOrderId($orderId) {
        $sql = "SELECT oi.*, p.name as product_name, p.sku as product_sku 
                FROM {$this->table} oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}