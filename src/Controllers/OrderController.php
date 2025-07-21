<?php

namespace App\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;

class OrderController extends BaseController {
    private $orderModel;
    private $productModel;
    private $customerModel;
    
    public function __construct() {
        $this->orderModel = new Order();
        $this->productModel = new Product();
        $this->customerModel = new Customer();
    }
    
    public function index() {
        $this->requireAuth();
        
        $queryData = $this->getQueryData();
        $page = isset($queryData['page']) ? (int)$queryData['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Get filters
        $filters = [];
        if (!empty($queryData['start_date']) && !empty($queryData['end_date'])) {
            $filters['date_range'] = [
                'start' => $queryData['start_date'] . ' 00:00:00',
                'end' => $queryData['end_date'] . ' 23:59:59'
            ];
            $orders = $this->orderModel->getOrdersByDateRange($filters['date_range']['start'], $filters['date_range']['end']);
        } else {
            $orders = $this->orderModel->getAll($limit, $offset, 'order_date DESC');
        }
        
        $totalOrders = $this->orderModel->count();
        $totalPages = ceil($totalOrders / $limit);
        
        $this->render('order/index', [
            'title' => 'Orders',
            'orders' => $orders,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters
        ]);
    }
    
    public function create() {
        $this->requireAuth();
        
        $errors = [];
        $orderData = [
            'customer_id' => '',
            'order_date' => date('Y-m-d'),
            'status' => 'pending',
            'payment_method' => '',
            'payment_status' => 'unpaid',
            'shipping_address' => '',
            'billing_address' => '',
            'notes' => '',
            'items' => []
        ];
        
        if ($this->isPost()) {
            $postData = $this->getPostData();
            
            // Validate required fields
            $requiredFields = ['customer_id', 'order_date', 'status', 'payment_method', 'payment_status'];
            $errors = $this->validateRequired($postData, $requiredFields);
            
            // Validate items
            if (empty($postData['items']) || !is_array($postData['items'])) {
                $errors['items'] = 'At least one item is required';
            } else {
                $totalAmount = 0;
                $taxAmount = 0;
                $discountAmount = 0;
                
                foreach ($postData['items'] as $index => $item) {
                    if (empty($item['product_id']) || empty($item['quantity']) || !is_numeric($item['quantity'])) {
                        $errors["item_{$index}"] = 'Product and valid quantity are required';
                        continue;
                    }
                    
                    $product = $this->productModel->findById($item['product_id']);
                    
                    if (!$product) {
                        $errors["item_{$index}_product"] = 'Selected product not found';
                        continue;
                    }
                    
                    if ($item['quantity'] > $product['quantity']) {
                        $errors["item_{$index}_stock"] = 'Insufficient stock for ' . $product['name'];
                        continue;
                    }
                    
                    $unitPrice = $product['selling_price'];
                    $itemTax = $product['tax_rate'] ? ($unitPrice * $product['tax_rate'] / 100) : 0;
                    $itemDiscount = isset($item['discount']) ? $item['discount'] : 0;
                    $itemTotal = ($unitPrice + $itemTax - $itemDiscount) * $item['quantity'];
                    
                    $totalAmount += $itemTotal;
                    $taxAmount += $itemTax * $item['quantity'];
                    $discountAmount += $itemDiscount * $item['quantity'];
                    
                    // Add calculated fields
                    $postData['items'][$index]['unit_price'] = $unitPrice;
                    $postData['items'][$index]['tax_amount'] = $itemTax * $item['quantity'];
                    $postData['items'][$index]['discount_amount'] = $itemDiscount * $item['quantity'];
                    $postData['items'][$index]['total_price'] = $itemTotal;
                }
                
                $postData['total_amount'] = $totalAmount;
                $postData['tax_amount'] = $taxAmount;
                $postData['discount_amount'] = $discountAmount;
            }
            
            if (empty($errors)) {
                // Generate order number
                $postData['order_number'] = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
                $postData['user_id'] = $this->getUserId();
                
                // Create the order
                $orderId = $this->orderModel->create($postData);
                
                if ($orderId) {
                    $this->setFlash('success', 'Order created successfully!');
                    $this->redirect('/orders/view/' . $orderId);
                } else {
                    $errors['create'] = 'Failed to create order. Please try again.';
                }
            }
            
            // Repopulate form data
            $orderData = $postData;
        }
        
        // Get customers and products for select dropdowns
        $customers = $this->customerModel->getAll();
        $products = $this->productModel->getProducts(['status' => 'active']);
        
        $this->render('order/create', [
            'title' => 'Create Order',
            'orderData' => $orderData,
            'customers' => $customers,
            'products' => $products,
            'errors' => $errors
        ]);
    }
    
    public function view($id) {
        $this->requireAuth();
        
        $order = $this->orderModel->getOrderWithItems($id);
        
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('/orders');
        }
        
        $customer = $this->customerModel->findById($order['customer_id']);
        
        $this->render('order/view', [
            'title' => 'View Order',
            'order' => $order,
            'customer' => $customer
        ]);
    }
    
    public function update($id) {
        $this->requireAuth();
        
        $order = $this->orderModel->findById($id);
        
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('/orders');
        }
        
        if ($this->isPost()) {
            $postData = $this->getPostData();
            
            // Prepare update data
            $updateData = [];
            
            if (isset($postData['status']) && $postData['status'] !== $order['status']) {
                $updateData['status'] = $postData['status'];
            }
            
            if (isset($postData['payment_status']) && $postData['payment_status'] !== $order['payment_status']) {
                $updateData['payment_status'] = $postData['payment_status'];
            }
            
            if (isset($postData['notes'])) {
                $updateData['notes'] = $postData['notes'];
            }
            
            if (!empty($updateData)) {
                $success = $this->orderModel->update($id, $updateData);
                
                if ($success) {
                    $this->setFlash('success', 'Order updated successfully!');
                } else {
                    $this->setFlash('error', 'Failed to update order');
                }
            } else {
                $this->setFlash('info', 'No changes were made to the order');
            }
        }
        
        $this->redirect('/orders/view/' . $id);
    }
    
    public function delete($id) {
        $this->requireAuth();
        
        if ($this->isPost()) {
            $order = $this->orderModel->findById($id);
            
            if (!$order) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('/orders');
            }
            
            // In a real application, you might want to consider soft deletion
            // or adding permissions to check who can delete orders
            $success = $this->orderModel->delete($id);
            
            if ($success) {
                $this->setFlash('success', 'Order deleted successfully!');
            } else {
                $this->setFlash('error', 'Failed to delete order');
            }
        }
        
        $this->redirect('/orders');
    }
    
    public function invoice($id) {
        $this->requireAuth();
        
        $order = $this->orderModel->getOrderWithItems($id);
        
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('/orders');
        }
        
        $customer = $this->customerModel->findById($order['customer_id']);
        
        $this->render('order/invoice', [
            'title' => 'Invoice #' . $order['order_number'],
            'order' => $order,
            'customer' => $customer
        ]);
    }
}