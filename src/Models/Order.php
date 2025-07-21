<?php

namespace App\Models;

class Order extends BaseModel {
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $fillable = [
        'order_number', 'customer_id', 'user_id', 'order_date',
        'status', 'total_amount', 'tax_amount', 'discount_amount',
        'payment_method', 'payment_status', 'shipping_address',
        'billing_address', 'notes'
    ];

    public function create(array $data) {
        $this->db->beginTransaction();
        
        try {
            // Create the order
            $orderId = parent::create($data);
            
            // Process order items
            if ($orderId && !empty($data['items'])) {
                $orderItemModel = new OrderItem();
                $productModel = new Product();
                
                foreach ($data['items'] as $item) {
                    $item['order_id'] = $orderId;
                    $orderItemModel->create($item);
                    
                    // Update product stock
                    $productModel->updateStock($item['product_id'], $item['quantity'], 'decrement');
                }
            }
            
            $this->db->commit();
            return $orderId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getOrderWithItems($orderId) {
        $order = $this->findById($orderId);
        
        if (!$order) {
            return null;
        }
        
        $orderItemModel = new OrderItem();
        $order['items'] = $orderItemModel->getItemsByOrderId($orderId);
        
        return $order;
    }

    public function getOrdersByCustomer($customerId, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE customer_id = ? ORDER BY order_date DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?, ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iii", $customerId, $offset, $limit);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $customerId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getOrdersByDateRange($startDate, $endDate) {
        $sql = "SELECT * FROM {$this->table} WHERE order_date BETWEEN ? AND ? ORDER BY order_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function updateOrderStatus($orderId, $status) {
        return $this->update($orderId, ['status' => $status]);
    }

    public function updatePaymentStatus($orderId, $paymentStatus) {
        return $this->update($orderId, ['payment_status' => $paymentStatus]);
    }
}